<?php

namespace Tests\Feature;

use App\Mail\EmailChangeCompletedMail;
use App\Mail\EmailChangeCurrentCodeMail;
use App\Mail\EmailChangeProposedCodeMail;
use App\Models\EmailChangeRequest;
use App\Models\Organisation;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.48: the sign-in email changes only through the three-step flow.
 *
 * These are the regressions for the 2026-09-01 account-takeover audit:
 * a stolen bearer token could rewrite the login email through PATCH
 * auth/me with the replacement inheriting verified status, and any
 * organisation administrator could do the same to a colleague through
 * PATCH /api/users/{user}. Both doors must stay closed, and the flow
 * that replaced them must keep its own promises.
 */
class EmailChangeFlowTest extends TestCase
{
    use RefreshDatabase;

    /*
    |----------------------------------------------------------------------
    | Door 1: the profile endpoint no longer moves an address.
    |----------------------------------------------------------------------
    */

    public function test_profile_update_cannot_change_the_email(): void
    {
        $user = $this->verifiedUser('owner@example.test');

        Sanctum::actingAs($user);

        $this
            ->patchJson('/api/auth/me', [
                'surname' => 'Owner',
                'email' => 'thief@example.test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $user->refresh();

        $this->assertSame('owner@example.test', $user->email);
    }

    public function test_profile_update_with_unchanged_email_still_works(): void
    {
        $user = $this->verifiedUser('owner@example.test');

        Sanctum::actingAs($user);

        $this
            ->patchJson('/api/auth/me', [
                'surname' => 'Renamed',
                'email' => 'owner@example.test',
                'phone' => null,
            ])
            ->assertOk();

        $this->assertSame(
            'owner@example.test',
            $user->refresh()->email
        );
    }

    /*
    |----------------------------------------------------------------------
    | Door 2: an organisation administrator cannot rewrite a colleague's
    | address.
    |----------------------------------------------------------------------
    */

    public function test_administrator_cannot_change_a_colleagues_email(): void
    {
        $administrator = $this->verifiedUser(
            'admin@example.test',
            'administrator'
        );

        $colleague = $this->verifiedUser('victim@example.test');

        Sanctum::actingAs($administrator);

        $this
            ->patchJson("/api/users/{$colleague->id}", [
                'email' => 'attacker@example.test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $colleague->refresh();

        $this->assertSame('victim@example.test', $colleague->email);
        $this->assertNotNull($colleague->email_verified_at);
    }

    public function test_administrator_edit_echoing_the_same_email_still_works(): void
    {
        $administrator = $this->verifiedUser(
            'admin@example.test',
            'administrator'
        );

        $colleague = $this->verifiedUser('colleague@example.test');

        Sanctum::actingAs($administrator);

        $this
            ->patchJson("/api/users/{$colleague->id}", [
                'email' => 'colleague@example.test',
                'surname' => 'Renamed',
            ])
            ->assertOk();

        $this->assertSame(
            'colleague@example.test',
            $colleague->refresh()->email
        );
    }

    /*
    |----------------------------------------------------------------------
    | The flow itself: the legitimate path end to end.
    |----------------------------------------------------------------------
    */

    public function test_the_three_steps_change_the_email(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        /*
         * Step 1: password + proposed address opens the request and
         * mails the CURRENT mailbox a code naming the replacement.
         */
        $start = $this
            ->postJson('/api/auth/email-change', [
                'email' => 'new@example.test',
                'current_password' => 'password',
            ])
            ->assertCreated();

        $token = $start->json('change.token');

        $this->assertSame(
            'verify_current',
            $start->json('change.step')
        );

        $currentCode = null;

        Mail::assertSent(
            EmailChangeCurrentCodeMail::class,
            function (EmailChangeCurrentCodeMail $mail) use (&$currentCode): bool {
                $currentCode = $mail->code;

                return $mail->hasTo('old@example.test')
                    && $mail->proposedEmail === 'new@example.test';
            }
        );

        /*
         * The account itself is untouched while the change is pending.
         */
        $this->assertSame('old@example.test', $user->refresh()->email);

        /*
         * Step 2: the current mailbox answers; the NEW mailbox gets its
         * own, different code.
         */
        $this
            ->postJson('/api/auth/email-change/verify-current', [
                'token' => $token,
                'code' => $currentCode,
            ])
            ->assertOk()
            ->assertJsonPath('change.step', 'verify_proposed');

        $proposedCode = null;

        Mail::assertSent(
            EmailChangeProposedCodeMail::class,
            function (EmailChangeProposedCodeMail $mail) use (&$proposedCode): bool {
                $proposedCode = $mail->code;

                return $mail->hasTo('new@example.test');
            }
        );

        $this->assertNotSame($currentCode, $proposedCode);

        /*
         * Still untouched: only the very last step moves anything.
         */
        $this->assertSame('old@example.test', $user->refresh()->email);

        /*
         * Step 3: the new mailbox answers. The address swaps, arrives
         * verified, every session dies, and the completing browser gets
         * a fresh token.
         */
        $response = $this
            ->postJson('/api/auth/email-change/verify-new', [
                'token' => $token,
                'code' => $proposedCode,
            ])
            ->assertOk()
            ->assertJsonPath('email', 'new@example.test');

        $this->assertNotSame('', $response->json('token'));

        $user->refresh();

        $this->assertSame('new@example.test', $user->email);
        $this->assertNotNull($user->email_verified_at);

        /*
         * Exactly one token remains: the fresh one just minted. Every
         * pre-existing session — a stolen copy included — is dead.
         */
        $this->assertSame(1, $user->tokens()->count());

        /*
         * The OLD address is told the account moved.
         */
        Mail::assertSent(
            EmailChangeCompletedMail::class,
            fn (EmailChangeCompletedMail $mail): bool => $mail->hasTo('old@example.test')
        );
    }

    /*
    |----------------------------------------------------------------------
    | The barriers, one by one.
    |----------------------------------------------------------------------
    */

    public function test_initiation_requires_the_current_password(): void
    {
        Mail::fake();

        Sanctum::actingAs(
            $this->verifiedUser('old@example.test')
        );

        $this
            ->postJson('/api/auth/email-change', [
                'email' => 'new@example.test',
                'current_password' => 'wrong-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        Mail::assertNothingSent();

        $this->assertDatabaseCount('email_change_requests', 0);
    }

    public function test_a_taken_address_is_refused_up_front(): void
    {
        $this->verifiedUser('taken@example.test');

        Sanctum::actingAs(
            $this->verifiedUser('old@example.test')
        );

        $this
            ->postJson('/api/auth/email-change', [
                'email' => 'taken@example.test',
                'current_password' => 'password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_a_customer_cannot_take_the_platform_domain(): void
    {
        Sanctum::actingAs(
            $this->verifiedUser('old@example.test')
        );

        $this
            ->postJson('/api/auth/email-change', [
                'email' => 'imposter@'.User::PLATFORM_EMAIL_DOMAIN,
                'current_password' => 'password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_three_wrong_codes_kill_the_request(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        $token = $this->openChange($user);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/auth/email-change/verify-current', [
                'token' => $token,
                'code' => '000000',
            ])->assertStatus(422);
        }

        /*
         * Even the RIGHT code is now worthless: the request is dead,
         * and the account never moved.
         */
        $change = EmailChangeRequest::query()->firstOrFail();

        $this->assertNotNull($change->cancelled_at);
        $this->assertSame('attempts', $change->cancelled_reason);
        $this->assertSame('old@example.test', $user->refresh()->email);
    }

    public function test_a_new_request_supersedes_the_previous_one(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        $firstToken = $this->openChange($user, 'first@example.test');

        $firstCode = $this->lastCurrentCode();

        $this->openChange($user, 'second@example.test');

        /*
         * Proof gathered for the first address cannot survive into the
         * second request — the old token answers nothing any more.
         */
        $this
            ->postJson('/api/auth/email-change/verify-current', [
                'token' => $firstToken,
                'code' => $firstCode,
            ])
            ->assertStatus(422);

        $this->assertSame(
            1,
            EmailChangeRequest::query()
                ->whereNull('cancelled_at')
                ->whereNull('completed_at')
                ->count()
        );
    }

    public function test_an_expired_request_answers_nothing(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        $token = $this->openChange($user);

        $code = $this->lastCurrentCode();

        EmailChangeRequest::query()->update([
            'code_expires_at' => now()->subMinute(),
        ]);

        $this
            ->postJson('/api/auth/email-change/verify-current', [
                'token' => $token,
                'code' => $code,
            ])
            ->assertStatus(422);

        $this->assertSame('old@example.test', $user->refresh()->email);
    }

    public function test_a_completed_request_cannot_be_replayed(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        [$token, $proposedCode] = $this->walkToFinalStep($user);

        $this
            ->postJson('/api/auth/email-change/verify-new', [
                'token' => $token,
                'code' => $proposedCode,
            ])
            ->assertOk();

        /*
         * Same token, same code, second time: nothing.
         */
        $this
            ->postJson('/api/auth/email-change/verify-new', [
                'token' => $token,
                'code' => $proposedCode,
            ])
            ->assertStatus(422);
    }

    public function test_cancelling_leaves_the_account_untouched(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        $this->openChange($user);

        $this
            ->deleteJson('/api/auth/email-change')
            ->assertOk();

        $change = EmailChangeRequest::query()->firstOrFail();

        $this->assertNotNull($change->cancelled_at);
        $this->assertSame('user', $change->cancelled_reason);
        $this->assertSame('old@example.test', $user->refresh()->email);
    }

    public function test_a_pending_address_has_no_account_authority(): void
    {
        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        $this->openChange($user, 'pending@example.test');

        /*
         * Sign-in still belongs to the old address alone; the proposed
         * one is a stranger to authentication.
         */
        $this
            ->postJson('/api/auth/login', [
                'email' => 'pending@example.test',
                'password' => 'password',
            ])
            ->assertStatus(422);

        /*
         * And the user row still carries the old, verified address.
         */
        $user->refresh();

        $this->assertSame('old@example.test', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_resends_are_capped_and_renew_the_code(): void
    {
        /*
         * The route throttle (3/minute) would answer 429 before the
         * application-level cap ever spoke. It exists for real traffic;
         * here it is lifted so the flow's own rules are what is tested.
         */
        $this->withoutMiddleware(
            \Illuminate\Routing\Middleware\ThrottleRequests::class
        );

        Mail::fake();

        $user = $this->verifiedUser('old@example.test');

        Sanctum::actingAs($user);

        $token = $this->openChange($user);

        /*
         * Inside the cooldown a resend is refused.
         */
        $this
            ->postJson('/api/auth/email-change/resend', [
                'token' => $token,
            ])
            ->assertStatus(422);

        /*
         * Past the cooldown each resend works, until the cap.
         */
        for ($resend = 1; $resend <= 3; $resend++) {
            EmailChangeRequest::query()->update([
                'last_sent_at' => now()->subMinutes(2),
            ]);

            $this
                ->postJson('/api/auth/email-change/resend', [
                    'token' => $token,
                ])
                ->assertOk();
        }

        EmailChangeRequest::query()->update([
            'last_sent_at' => now()->subMinutes(2),
        ]);

        $this
            ->postJson('/api/auth/email-change/resend', [
                'token' => $token,
            ])
            ->assertStatus(422);

        /*
         * Only the LAST code sent answers the step.
         */
        $this->assertSame(
            4,
            count($this->allCurrentCodes())
        );

        $this
            ->postJson('/api/auth/email-change/verify-current', [
                'token' => $token,
                'code' => $this->lastCurrentCode(),
            ])
            ->assertOk();
    }

    /*
    |----------------------------------------------------------------------
    | The support bypass: platform staff, never organisation admins.
    |----------------------------------------------------------------------
    */

    public function test_platform_staff_change_a_customers_email_from_the_console(): void
    {
        Mail::fake();

        [$staff, $customerUser] = $this->platformAndCustomer();

        Sanctum::actingAs($staff);

        $this
            ->patchJson("/api/admin/users/{$customerUser->id}/email", [
                'email' => 'recovered@example.test',
            ])
            ->assertOk();

        $customerUser->refresh();

        $this->assertSame('recovered@example.test', $customerUser->email);
        $this->assertNotNull($customerUser->email_verified_at);

        /*
         * Every session died with the old address, and BOTH mailboxes
         * were told.
         */
        $this->assertSame(0, $customerUser->tokens()->count());

        Mail::assertSent(
            EmailChangeCompletedMail::class,
            fn (EmailChangeCompletedMail $mail): bool => $mail->hasTo('lost@example.test')
        );

        Mail::assertSent(
            EmailChangeCompletedMail::class,
            fn (EmailChangeCompletedMail $mail): bool => $mail->hasTo('recovered@example.test')
        );
    }

    public function test_a_customer_administrator_cannot_reach_the_console_bypass(): void
    {
        $administrator = $this->verifiedUser(
            'admin@example.test',
            'administrator'
        );

        $colleague = $this->verifiedUser('victim@example.test');

        Sanctum::actingAs($administrator);

        $this
            ->patchJson("/api/admin/users/{$colleague->id}/email", [
                'email' => 'attacker@example.test',
            ])
            ->assertForbidden();

        $this->assertSame(
            'victim@example.test',
            $colleague->refresh()->email
        );
    }

    /*
    |----------------------------------------------------------------------
    | Helpers
    |----------------------------------------------------------------------
    */

    private function verifiedUser(
        string $email,
        string $role = 'property_manager'
    ): User {
        return User::factory()->create([
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    /**
     * Open a change request and return its token.
     */
    private function openChange(
        User $user,
        string $proposed = 'new@example.test'
    ): string {
        $response = $this
            ->postJson('/api/auth/email-change', [
                'email' => $proposed,
                'current_password' => 'password',
            ])
            ->assertCreated();

        return $response->json('change.token');
    }

    /**
     * Walk a fresh request up to the final step, returning what it
     * needs: the token and the proposed mailbox's code.
     *
     * @return array{0: string, 1: string}
     */
    private function walkToFinalStep(User $user): array
    {
        $token = $this->openChange($user);

        $this
            ->postJson('/api/auth/email-change/verify-current', [
                'token' => $token,
                'code' => $this->lastCurrentCode(),
            ])
            ->assertOk();

        $proposedCode = null;

        Mail::assertSent(
            EmailChangeProposedCodeMail::class,
            function (EmailChangeProposedCodeMail $mail) use (&$proposedCode): bool {
                $proposedCode = $mail->code;

                return true;
            }
        );

        return [$token, $proposedCode];
    }

    /**
     * Every code mailed to the current mailbox so far, oldest first.
     *
     * @return list<string>
     */
    private function allCurrentCodes(): array
    {
        $codes = [];

        Mail::assertSent(
            EmailChangeCurrentCodeMail::class,
            function (EmailChangeCurrentCodeMail $mail) use (&$codes): bool {
                $codes[] = $mail->code;

                return true;
            }
        );

        return $codes;
    }

    private function lastCurrentCode(): string
    {
        $codes = $this->allCurrentCodes();

        return end($codes);
    }

    /**
     * A platform staff administrator and a customer user whose mailbox
     * is lost, each in their own organisation.
     *
     * @return array{0: User, 1: User}
     */
    private function platformAndCustomer(): array
    {
        $platform = Organisation::factory()->create([
            'name' => 'Patrimoine 365',
            'is_platform' => true,
        ]);

        $staff = OrganisationContext::runAs(
            (int) $platform->id,
            fn (): User => User::factory()
                ->forOrganisation($platform)
                ->create([
                    'email' => 'staff@'.User::PLATFORM_EMAIL_DOMAIN,
                    'role' => 'administrator',
                ])
        );

        $customer = Organisation::factory()->create([
            'name' => 'Customer Ltd',
        ]);

        $customerUser = OrganisationContext::runAs(
            (int) $customer->id,
            fn (): User => User::factory()
                ->forOrganisation($customer)
                ->create([
                    'email' => 'lost@example.test',
                    'role' => 'administrator',
                ])
        );

        return [$staff, $customerUser];
    }
}
