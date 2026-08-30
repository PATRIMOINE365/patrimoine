<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Mail\MfaCodeMail;
use App\Models\ApplicationSetting;
use App\Models\MfaChallenge;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * V1.0.10 public signup, email verification and MFA sign-in.
 */
class RegistrationAndMfaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Signup and sign-in are public flows: real requests arrive with NO
     * organisation bound. The base TestCase's convenience binding would
     * scope the pre-auth user lookups to the wrong organisation, so
     * these tests start unbound (users created via factories still land
     * in the first organisation).
     */
    protected function setUp(): void
    {
        parent::setUp();

        OrganisationContext::forget();
    }

    /**
     * A complete signup payload.
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'organisation_name' => 'Acme Properties',
            'given_names' => 'Ama',
            'surname' => 'Mensah',
            'email' => 'ama@acme.test',
            'phone' => '+233200000000',
            'phone_country' => 'GH',
            'password' => 'Sup3rSecret42',
            'password_confirmation' => 'Sup3rSecret42',
            'language' => 'en',
            'accept_legal' => true,
        ], $overrides);
    }

    public function test_signup_provisions_a_complete_isolated_organisation(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonPath('email', 'ama@acme.test');

        $organisation = Organisation::query()
            ->where('name', 'Acme Properties')
            ->sole();

        /*
         * 30-day Professional trial, no licence row.
         */
        $this->assertTrue($organisation->onTrial());
        $this->assertCount(0, $organisation->licenses);

        $user = User::query()
            ->where('email', 'ama@acme.test')
            ->sole();

        $this->assertSame($organisation->id, $user->organisation_id);
        $this->assertSame('administrator', $user->role->value);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_token_hash);

        /*
         * Legal acceptance is stamped with the accepted versions.
         */
        $this->assertNotNull($user->legal_accepted_at);
        $this->assertSame(
            config('legal.terms_version'),
            $user->legal_terms_version
        );
        $this->assertSame(
            config('legal.privacy_version'),
            $user->legal_privacy_version
        );

        /*
         * Managing organisation party + settings + chart of accounts
         * live inside the new organisation.
         */
        $party = Party::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->where('type', 'organisation')
            ->sole();

        $this->assertSame('Acme Properties', $party->name);

        $settings = ApplicationSetting::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->sole();

        $this->assertSame('en', $settings->language);

        $this->assertGreaterThan(
            0,
            \App\Models\AccountingAccount::withoutGlobalScopes()
                ->where('organisation_id', $organisation->id)
                ->count()
        );

        Mail::assertSent(
            EmailVerificationMail::class,
            fn (EmailVerificationMail $mail): bool => $mail->hasTo('ama@acme.test')
        );
    }

    public function test_signup_requires_legal_acceptance_and_unique_email(): void
    {
        Mail::fake();

        $this->postJson(
            '/api/auth/register',
            $this->payload(['accept_legal' => false])
        )->assertStatus(422)
            ->assertJsonValidationErrors(['accept_legal']);

        $this->postJson('/api/auth/register', $this->payload())
            ->assertCreated();

        $this->postJson(
            '/api/auth/register',
            $this->payload(['organisation_name' => 'Second Org'])
        )->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unverified_account_cannot_sign_in_until_verified(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->payload())
            ->assertCreated();

        $this->postJson('/api/auth/login', [
            'email' => 'ama@acme.test',
            'password' => 'Sup3rSecret42',
        ])->assertStatus(422);

        /*
         * Capture the verification link's token from the sent mailable.
         */
        $token = null;

        Mail::assertSent(
            EmailVerificationMail::class,
            function (EmailVerificationMail $mail) use (&$token): bool {
                parse_str(
                    (string) parse_url($mail->verificationUrl, PHP_URL_QUERY),
                    $query
                );

                $token = $query['token'] ?? null;

                return true;
            }
        );

        $this->assertIsString($token);

        /*
         * Corrupt the token by APPENDING rather than by replacing its
         * first character: a token that already began with 'x' was
         * "corrupted" back into itself, and this assertion then failed
         * roughly once every sixty runs.
         */
        $this->postJson('/api/auth/verify-email', [
            'token' => $token.'x',
        ])->assertStatus(422);

        $this->postJson('/api/auth/verify-email', [
            'token' => $token,
        ])->assertOk();

        /*
         * Verified: login now opens an MFA challenge.
         */
        $this->postJson('/api/auth/login', [
            'email' => 'ama@acme.test',
            'password' => 'Sup3rSecret42',
        ])->assertOk()
            ->assertJsonPath('mfa_required', true);
    }

    public function test_mfa_rejects_wrong_codes_and_dies_after_max_attempts(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => 'Sup3rSecret42',
        ]);

        $challenge = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Sup3rSecret42',
        ])->assertOk()->json('challenge_token');

        $code = null;

        Mail::assertSent(
            MfaCodeMail::class,
            function (MfaCodeMail $mail) use (&$code): bool {
                $code = $mail->code;

                return true;
            }
        );

        $wrong = $code === '000000' ? '111111' : '000000';

        for ($attempt = 0; $attempt < MfaChallenge::MAX_ATTEMPTS; $attempt++) {
            $this->postJson('/api/auth/mfa/verify', [
                'challenge_token' => $challenge,
                'code' => $wrong,
            ])->assertStatus(422);
        }

        /*
         * Attempts exhausted: even the CORRECT code is now refused.
         */
        $this->postJson('/api/auth/mfa/verify', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_mfa_code_is_single_use(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => 'Sup3rSecret42',
        ]);

        $challenge = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Sup3rSecret42',
        ])->json('challenge_token');

        $code = null;

        Mail::assertSent(
            MfaCodeMail::class,
            function (MfaCodeMail $mail) use (&$code): bool {
                $code = $mail->code;

                return true;
            }
        );

        $this->postJson('/api/auth/mfa/verify', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertOk();

        $this->postJson('/api/auth/mfa/verify', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_mfa_resend_issues_a_fresh_working_code(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => 'Sup3rSecret42',
        ]);

        $challenge = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Sup3rSecret42',
        ])->json('challenge_token');

        $this->postJson('/api/auth/mfa/resend', [
            'challenge_token' => $challenge,
        ])->assertOk();

        $codes = [];

        Mail::assertSent(
            MfaCodeMail::class,
            function (MfaCodeMail $mail) use (&$codes): bool {
                $codes[] = $mail->code;

                return true;
            }
        );

        /*
         * The LAST emailed code is the live one.
         */
        $this->postJson('/api/auth/mfa/verify', [
            'challenge_token' => $challenge,
            'code' => end($codes),
        ])->assertOk();
    }

    public function test_suspended_organisation_cannot_sign_in(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => 'Sup3rSecret42',
        ]);

        $this->testOrganisation->update(['status' => 'suspended']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Sup3rSecret42',
        ])->assertStatus(422);

        Mail::assertNotSent(MfaCodeMail::class);
    }

    public function test_resend_verification_never_discloses_account_existence(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/resend-verification', [
            'email' => 'ghost@nowhere.test',
        ])->assertOk();

        Mail::assertNothingOutgoing();
    }
}
