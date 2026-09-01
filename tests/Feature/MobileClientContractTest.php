<?php

namespace Tests\Feature;

use App\Http\Controllers\AppAssociationController;
use App\Mail\MfaCodeMail;
use App\Models\ApplicationSetting;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The contract an installed application depends on.
 *
 * Everything asserted here is something that cannot be fixed later. Once
 * a build is on somebody's phone it cannot be recalled, so the version
 * segment, the shape of a token, the launch-time configuration call and
 * the deep-link paths all have to be right in the first release that has
 * a client - not in the release where each of them is first needed.
 */
class MobileClientContractTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | API versioning
    |--------------------------------------------------------------------------
    */

    public function test_every_route_answers_under_the_version_and_without_it(): void
    {
        $user = User::factory()->create();

        foreach (['/api/auth/me', '/api/v1/auth/me'] as $path) {
            $this->actingAs($user, 'sanctum')
                ->getJson($path)
                ->assertOk()
                ->assertJsonPath('email', $user->email);
        }
    }

    public function test_the_unversioned_prefix_is_an_alias_and_not_a_second_api(): void
    {
        $versioned = $this->getJson('/api/v1/config')->assertOk()->json();

        $legacy = $this->getJson('/api/config')->assertOk()->json();

        $this->assertSame(
            $versioned,
            $legacy,
            'The unversioned prefix must answer exactly as the current version does.'
        );
    }

    public function test_a_document_link_signed_for_the_version_validates_there(): void
    {
        $user = User::factory()->create();

        $signed = app(\App\Services\DocumentLinkService::class);

        $this->assertTrue(
            $signed->isSignable('/api/v1/reports/occupancy/pdf'),
            'A versioned document endpoint must be signable, or the mobile client can never open a PDF.'
        );

        $this->assertTrue(
            $signed->isSignable('/api/reports/occupancy/pdf')
        );

        $this->assertFalse(
            $signed->isSignable('/api/v1/leases')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Launch configuration
    |--------------------------------------------------------------------------
    */

    public function test_the_launch_configuration_is_public_and_says_what_a_client_needs(): void
    {
        config([
            'patrimoine.clients.minimum_version.android' => '2.1.0',
            'patrimoine.clients.maintenance.active' => false,
        ]);

        $this->getJson('/api/v1/config')
            ->assertOk()
            ->assertJsonPath('release', (string) config('patrimoine.release'))
            ->assertJsonPath('api.current', 'v1')
            ->assertJsonPath('minimum_version.android', '2.1.0')
            ->assertJsonPath('maintenance.active', false)
            ->assertJsonPath('features.signup_in_app', false)
            ->assertJsonPath('features.payments_in_app', false)
            ->assertJsonStructure([
                'links' => ['signup', 'terms', 'privacy'],
                'languages',
            ]);
    }

    public function test_maintenance_can_be_declared_without_a_release(): void
    {
        config([
            'patrimoine.clients.maintenance.active' => true,
            'patrimoine.clients.maintenance.message' => 'Back at 18:00.',
        ]);

        $this->getJson('/api/config')
            ->assertOk()
            ->assertJsonPath('maintenance.active', true)
            ->assertJsonPath('maintenance.message', 'Back at 18:00.');
    }

    /*
    |--------------------------------------------------------------------------
    | Deep links
    |--------------------------------------------------------------------------
    */

    public function test_the_association_files_are_withheld_until_they_are_configured(): void
    {
        config([
            'patrimoine.deep_links.apple.team_id' => null,
            'patrimoine.deep_links.android.fingerprints' => null,
        ]);

        $this->get('/.well-known/apple-app-site-association')->assertNotFound();
        $this->get('/.well-known/assetlinks.json')->assertNotFound();
    }

    public function test_the_association_files_publish_once_the_identities_are_known(): void
    {
        config([
            'patrimoine.deep_links.apple.team_id' => 'ABCDE12345',
            'patrimoine.deep_links.apple.bundle_id' => 'com.patrimoine365.app',
            'patrimoine.deep_links.android.package' => 'com.patrimoine365.app',
            'patrimoine.deep_links.android.fingerprints' => 'AA:BB, CC:DD',
        ]);

        $this->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertJsonPath(
                'applinks.details.0.appIDs.0',
                'ABCDE12345.com.patrimoine365.app'
            );

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertJsonPath('0.target.package_name', 'com.patrimoine365.app')
            ->assertJsonPath('0.target.sha256_cert_fingerprints', ['AA:BB', 'CC:DD']);
    }

    public function test_every_claimed_deep_link_path_still_exists(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): string => '/'.ltrim($route->uri(), '/'))
            ->all();

        foreach (AppAssociationController::CLAIMED_PATHS as $claimed) {
            $base = rtrim($claimed, '*');

            $this->assertContains(
                $base,
                $routes,
                $base.' is claimed as a deep link but no longer exists. '
                    .'A link already sent by e-mail would open the application on nothing.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    */

    public function test_a_client_with_no_organisation_is_answered_in_the_language_it_asked_for(): void
    {
        /*
         * The base TestCase binds an organisation for the legacy suite.
         * A visitor at the sign-in screen has none, which is the whole
         * state being exercised here.
         */
        \App\Support\OrganisationContext::forget();

        $this->postJson(
            '/api/auth/login',
            ['email' => 'nobody@example.test'],
            ['Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8']
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.password.0',
                __('validation.required', ['attribute' => __('validation.attributes.password')], 'fr')
            );
    }

    public function test_an_explicit_declaration_outranks_the_device_preference(): void
    {
        \App\Support\OrganisationContext::forget();

        $this->postJson(
            '/api/auth/login',
            ['email' => 'nobody@example.test'],
            [
                'Accept-Language' => 'fr-FR,fr;q=0.9',
                'X-Patrimoine-Language' => 'en',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.password.0',
                __('validation.required', ['attribute' => __('validation.attributes.password')], 'en')
            );
    }

    public function test_the_organisation_language_still_wins_once_one_is_known(): void
    {
        ApplicationSetting::query()->create([
            'organisation_id' => $this->testOrganisation->id,
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $user = User::factory()->create();

        /*
         * An English handset inside a French organisation reads French.
         * Both halves of the product take the language from the same
         * place, which is what makes an error sentence matchable back to
         * its code.
         */
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/leases/999999', ['Accept-Language' => 'en-GB,en'])
            ->assertNotFound()
            ->assertJsonPath('message', __('api.not_found', [], 'fr'));
    }

    public function test_the_client_may_be_allowed_to_choose_by_configuration(): void
    {
        config(['patrimoine.client_language_overrides_organisation' => true]);

        ApplicationSetting::query()->create([
            'organisation_id' => $this->testOrganisation->id,
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson(
                '/api/v1/leases/999999',
                ['X-Patrimoine-Language' => 'en']
            )
            ->assertNotFound()
            ->assertJsonPath('message', __('api.not_found', [], 'en'));
    }

    /*
    |--------------------------------------------------------------------------
    | Tokens
    |--------------------------------------------------------------------------
    */

    public function test_signing_in_names_the_token_for_its_device_and_gives_it_a_lifetime(): void
    {
        $token = $this->signIn(
            deviceName: 'Komla Pixel 8',
            headers: [
                'X-Patrimoine-Client' => 'mobile',
                'X-Patrimoine-Platform' => 'android',
                'X-App-Version' => '1.4.2',
            ]
        );

        $row = PersonalAccessToken::query()->latest('id')->firstOrFail();

        $this->assertSame('Komla Pixel 8', $row->name);
        $this->assertSame('mobile', $row->client_type);
        $this->assertSame('android', $row->platform);
        $this->assertSame('1.4.2', $row->app_version);
        $this->assertNotNull($row->expires_at);
        $this->assertNotNull($row->absolute_expires_at);

        $this->assertTrue(
            $row->expires_at->lessThanOrEqualTo($row->absolute_expires_at),
            'The idle window may never begin beyond the ceiling.'
        );

        $this->assertIsString($token);
    }

    public function test_a_token_with_no_device_name_is_still_recognisable(): void
    {
        $this->signIn(
            deviceName: null,
            headers: [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    .'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36',
            ]
        );

        $row = PersonalAccessToken::query()->latest('id')->firstOrFail();

        $this->assertSame('Chrome on Windows', $row->name);
        $this->assertSame('web', $row->client_type);
    }

    public function test_an_expired_token_is_refused(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('Old phone', ['*'], now()->subMinute());

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_using_a_token_pushes_its_idle_window_forward(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('Desk', ['*'], now()->addMinutes(30));

        $token->accessToken->forceFill([
            'client_type' => 'web',
            'absolute_expires_at' => now()->addDays(30),
        ])->save();

        $before = $token->accessToken->expires_at;

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $after = PersonalAccessToken::query()
            ->find($token->accessToken->getKey())
            ->expires_at;

        $this->assertTrue(
            $after->greaterThan($before),
            'An authenticated request should have extended the idle window.'
        );
    }

    public function test_the_idle_window_can_never_be_pushed_past_the_ceiling(): void
    {
        $user = User::factory()->create();

        $ceiling = now()->addMinutes(20);

        $token = $user->createToken('Nearly done', ['*'], now()->addMinutes(10));

        $token->accessToken->forceFill([
            'client_type' => 'web',
            'absolute_expires_at' => $ceiling,
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $after = PersonalAccessToken::query()
            ->find($token->accessToken->getKey())
            ->expires_at;

        $this->assertTrue(
            $after->lessThanOrEqualTo($ceiling),
            'The ceiling is the one line the sliding window may not cross.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Devices
    |--------------------------------------------------------------------------
    */

    public function test_a_person_sees_their_own_devices_and_nobody_else(): void
    {
        $user = User::factory()->create();

        $other = User::factory()->create();

        $user->createToken('Phone');
        $other->createToken('Somebody else phone');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/devices')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Phone', $names);
        $this->assertNotContains('Somebody else phone', $names);
    }

    public function test_a_device_belonging_to_somebody_else_cannot_be_revoked(): void
    {
        $user = User::factory()->create();

        $other = User::factory()->create();

        $theirs = $other->createToken('Their phone')->accessToken;

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/devices/'.$theirs->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $theirs->getKey(),
        ]);
    }

    public function test_revoking_one_device_stops_it_working(): void
    {
        $user = User::factory()->create();

        $lost = $user->createToken('Lost phone');

        $keeping = $user->createToken('Desk');

        $this->withHeader('Authorization', 'Bearer '.$keeping->plainTextToken)
            ->deleteJson('/api/v1/auth/devices/'.$lost->accessToken->getKey())
            ->assertOk()
            ->assertJsonPath('signed_out', false);

        /*
         * The guard caches the user it resolved for the previous
         * request; without this the next call would be answered from
         * that cache rather than from the token being tested.
         */
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$lost->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_signing_out_every_other_device_leaves_this_one_alone(): void
    {
        $user = User::factory()->create();

        $user->createToken('Old laptop');
        $user->createToken('Old phone');

        $current = $user->createToken('Here');

        $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->deleteJson('/api/v1/auth/devices')
            ->assertOk()
            ->assertJsonPath('revoked', 2);

        $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $this->assertSame(
            1,
            PersonalAccessToken::query()
                ->where('tokenable_id', $user->getKey())
                ->count()
        );
    }

    /**
     * Sign in the way a real client does, and return the access token.
     *
     * @param  array<string, string>  $headers
     */
    private function signIn(
        ?string $deviceName = null,
        array $headers = []
    ): string {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'device.owner@example.test',
            'password' => 'secure-password',
        ]);

        $challenge = $this->withHeaders($headers)->postJson(
            '/api/v1/auth/login',
            [
                'email' => 'device.owner@example.test',
                'password' => 'secure-password',
            ]
        )->assertOk();

        $code = null;

        Mail::assertSent(
            MfaCodeMail::class,
            function (MfaCodeMail $mail) use (&$code, $user): bool {
                $code = $mail->code;

                return $mail->hasTo($user->email);
            }
        );

        $payload = [
            'challenge_token' => $challenge->json('challenge_token'),
            'code' => $code,
        ];

        if ($deviceName !== null) {
            $payload['device_name'] = $deviceName;
        }

        return $this->withHeaders($headers)
            ->postJson('/api/v1/auth/mfa/verify', $payload)
            ->assertOk()
            ->json('access_token');
    }
}
