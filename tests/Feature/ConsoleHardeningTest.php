<?php

namespace Tests\Feature;

use App\Mail\OrganisationDeletedMail;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\License;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccessTokenService;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.51: what the console audit found, pinned down.
 *
 * Every test here failed on 1.0.50. They cover the eleven findings:
 * the inert HTML reader, the hardening headers, one password rule,
 * password re-entry (throttled, logged) before anything that locks a
 * customer out, reasons kept, agents that belong to the organisation,
 * a complete platform trail, the platform organisation refused by the
 * last two endpoints, a 409 instead of a 500 without a mail provider,
 * short staff sessions, and the notice to a deleted organisation's
 * administrators.
 */
class ConsoleHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $platform;

    private User $staff;

    private Organisation $customer;

    private User $customerAdmin;

    private User $customerManager;

    private Lease $lease;

    private Party $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platform = Organisation::factory()->create([
            'name' => 'Kality Ltd',
            'is_platform' => true,
        ]);

        $this->staff = OrganisationContext::runAs(
            (int) $this->platform->id,
            fn (): User => User::factory()
                ->forOrganisation($this->platform)
                ->create([
                    'email' => 'staff@patrimoine365.com',
                    'role' => 'administrator',
                    'email_verified_at' => now(),
                ])
        );

        $this->customer = Organisation::factory()->create([
            'name' => 'Hardening Lettings Ltd',
        ]);

        [$this->customerAdmin, $this->customerManager, $this->lease, $this->agent] =
            OrganisationContext::runAs(
                (int) $this->customer->id,
                function (): array {
                    $admin = User::factory()
                        ->forOrganisation($this->customer)
                        ->create([
                            'email' => 'admin@hardening.test',
                            'role' => 'administrator',
                            'email_verified_at' => now(),
                        ]);

                    $manager = User::factory()
                        ->forOrganisation($this->customer)
                        ->create([
                            'email' => 'manager@hardening.test',
                            'role' => 'property_manager',
                            'email_verified_at' => now(),
                        ]);

                    $building = Building::create(['name' => 'Hardening House']);

                    $unit = Unit::create([
                        'building_id' => $building->id,
                        'name' => 'Flat 1',
                    ]);

                    $tenant = Party::create([
                        'type' => 'person',
                        'name' => 'Hardening Tenant',
                    ]);

                    $tenant->roles()->create(['role' => 'tenant']);

                    $agent = Party::create([
                        'type' => 'person',
                        'name' => 'Hardening Agent',
                    ]);

                    $agent->roles()->create(['role' => 'agent']);

                    $lease = Lease::create([
                        'unit_id' => $unit->id,
                        'tenant_id' => $tenant->id,
                        'start_date' => '2026-01-01',
                        'end_date' => '2026-12-31',
                        'rent_amount' => 1000,
                        'payment_frequency' => 'monthly',
                        'due_day' => 1,
                        'vat_rate' => 0,
                        'status' => 'active',
                    ]);

                    return [$admin, $manager, $lease, $agent];
                }
            );

        Sanctum::actingAs($this->staff);
    }

    /*
    |----------------------------------------------------------------------
    | K1 — the inbound HTML reader is inert
    |----------------------------------------------------------------------
    */

    public function test_the_inbound_html_stripper_uses_an_inert_document(): void
    {
        $source = file_get_contents(resource_path('js/admin.js'));

        $this->assertStringContainsString(
            "new DOMParser().parseFromString(String(html), 'text/html')",
            $source,
            'received HTML must be parsed in a document that cannot run it'
        );

        $this->assertStringNotContainsString(
            'container.innerHTML = String(html)',
            $source,
            'assigning received HTML to an element of the live page runs its handlers'
        );
    }

    /*
    |----------------------------------------------------------------------
    | K7 — hardening headers on every reply
    |----------------------------------------------------------------------
    */

    public function test_every_page_carries_the_hardening_headers_and_a_nonce_bound_policy(): void
    {
        $page = $this->get('/login')->assertOk();

        $page->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $page->assertHeader('X-Content-Type-Options', 'nosniff');
        $page->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $page->assertHeaderMissing('X-Powered-By');

        $policy = (string) $page->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'nonce-", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);

        preg_match("/'nonce-([^']+)'/", $policy, $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'the policy carries a nonce');

        /*
         * The same nonce is on the page's inline scripts, or the policy
         * would block the theme and language bootstrap on first paint.
         */
        $this->assertStringContainsString(
            'nonce="'.$matches[1].'"',
            $page->getContent()
        );

        $this->assertNotEmpty($page->headers->get('Permissions-Policy'));
    }

    public function test_api_replies_carry_the_headers_but_no_content_policy(): void
    {
        $reply = $this->getJson('/api/presentation-config')->assertOk();

        $reply->assertHeader('X-Content-Type-Options', 'nosniff');
        $reply->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $reply->assertHeaderMissing('Content-Security-Policy');
        $reply->assertHeaderMissing('X-Powered-By');
    }

    /*
    |----------------------------------------------------------------------
    | K3 — one password rule for everybody
    |----------------------------------------------------------------------
    */

    public function test_a_staff_member_cannot_choose_a_weak_password(): void
    {
        foreach (['aaaaaaaa', '12345678', 'onlylowercase123', 'NoDigitsHereAtAll'] as $weak) {
            $this->postJson('/api/auth/change-password', [
                'current_password' => 'password',
                'password' => $weak,
                'password_confirmation' => $weak,
            ])->assertStatus(422)->assertJsonValidationErrors(['password']);
        }

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'password',
            'password' => 'Str0ngConsolePass',
            'password_confirmation' => 'Str0ngConsolePass',
        ])->assertOk();
    }

    /*
    |----------------------------------------------------------------------
    | K4 / K6 / K9 — password again, throttled, logged, with a reason
    |----------------------------------------------------------------------
    */

    public function test_wrong_password_re_entries_are_counted_logged_and_then_refused(): void
    {
        $this->customer->update(['status' => 'suspended']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->deleteJson('/api/admin/organisations/'.$this->customer->id, [
                'name_confirmation' => $this->customer->name,
                'password' => 'not-it',
            ])->assertStatus(422)->assertJsonValidationErrors(['password']);
        }

        $this->assertSame(
            5,
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->platform->id)
                ->where('action', 'platform.password_reentry_failed')
                ->count(),
            'every failed re-entry is written to the platform trail'
        );

        $this->deleteJson('/api/admin/organisations/'.$this->customer->id, [
            'name_confirmation' => $this->customer->name,
            'password' => 'not-it',
        ])->assertStatus(429);

        $this->assertDatabaseHas('organisations', ['id' => $this->customer->id]);
    }

    public function test_suspending_revoking_deactivating_and_moving_an_address_ask_for_the_password(): void
    {
        $license = License::create([
            'organisation_id' => $this->customer->id,
            'plan' => 'standard',
            'starts_on' => now()->toDateString(),
            'expires_on' => null,
        ]);

        $suspend = '/api/admin/organisations/'.$this->customer->id.'/suspend';
        $revoke = '/api/admin/licenses/'.$license->id.'/revoke';
        $deactivate = '/api/admin/users/'.$this->customerManager->id.'/active';
        $email = '/api/admin/users/'.$this->customerManager->id.'/email';

        /*
         * Without the password: refused, nothing changes.
         */
        $this->postJson($suspend, ['reason' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
        $this->postJson($revoke, ['reason' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
        $this->patchJson($deactivate, ['is_active' => false])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
        $this->patchJson($email, ['email' => 'moved@hardening.test'])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->assertSame('active', $this->customer->fresh()->status);
        $this->assertNull($license->fresh()->revoked_at);
        $this->assertTrue((bool) $this->customerManager->fresh()->is_active);
        $this->assertSame('manager@hardening.test', $this->customerManager->fresh()->email);

        /*
         * With a wrong one: refused and written down.
         */
        $this->postJson($suspend, ['reason' => 'x', 'current_password' => 'not-it'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->assertSame(
            1,
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->platform->id)
                ->where('action', 'platform.password_reentry_failed')
                ->where('metadata->for', 'organisation.suspend')
                ->count()
        );

        /*
         * With the right one and a reason: done, and the reason kept in
         * both trails.
         */
        $this->patchJson($deactivate, [
            'is_active' => false,
            'current_password' => 'password',
            'reason' => 'Left the agency',
        ])->assertOk();

        $this->assertFalse((bool) $this->customerManager->fresh()->is_active);

        foreach ([$this->platform->id, $this->customer->id] as $organisationId) {
            $this->assertSame(
                'Left the agency',
                ActivityLog::withoutGlobalScopes()
                    ->where('organisation_id', $organisationId)
                    ->where('action', 'platform.user_deactivated')
                    ->latest('id')
                    ->value('metadata')['reason'] ?? null
            );
        }

        /*
         * Reactivation locks nobody out, so it asks for no password.
         */
        $this->patchJson($deactivate, [
            'is_active' => true,
            'reason' => 'Back',
        ])->assertOk();

        $this->postJson($revoke, ['current_password' => 'password', 'reason' => 'Unpaid'])->assertOk();

        $this->assertSame(
            'Unpaid',
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->platform->id)
                ->where('action', 'platform.license_revoked')
                ->latest('id')
                ->value('metadata')['reason'] ?? null
        );
    }

    public function test_a_password_reset_and_a_re_sent_verification_keep_their_reason(): void
    {
        Mail::fake();

        $this->postJson(
            '/api/admin/users/'.$this->customerManager->id.'/password-reset',
            ['reason' => 'Asked by telephone']
        )->assertOk();

        $this->assertSame(
            'Asked by telephone',
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->platform->id)
                ->where('action', 'platform.password_reset_sent')
                ->latest('id')
                ->value('metadata')['reason'] ?? null
        );
    }

    /*
    |----------------------------------------------------------------------
    | K2 — the agent must be one of the organisation's own
    |----------------------------------------------------------------------
    */

    public function test_a_lease_correction_refuses_an_agent_that_is_not_the_organisations(): void
    {
        $foreignAgent = Party::create([
            'type' => 'person',
            'name' => 'Somebody Else\'s Agent',
        ]);

        $foreignAgent->roles()->create(['role' => 'agent']);

        $path = '/api/admin/organisations/'.$this->customer->id.'/leases/'.$this->lease->id;

        /*
         * From another organisation (the test organisation's), and one
         * nobody has: both refused as validation, never a server error.
         */
        $this->patchJson($path, ['agent_id' => $foreignAgent->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['agent_id']);

        $this->patchJson($path, ['agent_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['agent_id']);

        $this->assertNull($this->lease->fresh()->agent_id);

        /*
         * The organisation's own agent is offered and accepted.
         */
        $this->getJson($path)
            ->assertOk()
            ->assertJsonPath('agents.0.id', $this->agent->id)
            ->assertJsonPath('agents.0.name', 'Hardening Agent');

        $this->patchJson($path, ['agent_id' => $this->agent->id])
            ->assertOk()
            ->assertJsonPath('changed', ['agent_id']);

        $this->assertSame($this->agent->id, $this->lease->fresh()->agent_id);
    }

    /*
    |----------------------------------------------------------------------
    | K5 / K10 — the platform's own trail is complete, reads included
    |----------------------------------------------------------------------
    */

    public function test_corrections_and_reads_of_customer_data_reach_the_platform_trail(): void
    {
        $path = '/api/admin/organisations/'.$this->customer->id.'/leases/'.$this->lease->id;

        $this->getJson($path)->assertOk();

        $this->patchJson($path, ['notes' => 'Corrected from the console'])->assertOk();

        $this->getJson('/api/admin/organisations/'.$this->customer->id.'/records?dataset=parties')
            ->assertOk();

        $trail = ActivityLog::withoutGlobalScopes()
            ->where('organisation_id', $this->platform->id)
            ->pluck('metadata', 'action');

        $this->assertArrayHasKey('platform.lease.corrected', $trail->all(), 'a correction is in the platform trail');
        $this->assertArrayHasKey('platform.lease_viewed', $trail->all(), 'reading a lease is in the platform trail');
        $this->assertArrayHasKey('platform.records_viewed', $trail->all(), 'reading records is in the platform trail');

        $this->assertSame('parties', $trail['platform.records_viewed']['dataset']);
        $this->assertSame($this->customer->name, $trail['platform.records_viewed']['customer_organisation']);

        /*
         * The console feed itself lists them.
         */
        $actions = collect($this->getJson('/api/admin/activity')->json('data'))->pluck('action');

        $this->assertTrue($actions->contains('platform.lease.corrected'));
        $this->assertTrue($actions->contains('platform.records_viewed'));

        /*
         * And the customer still sees the correction in their own log —
         * but not the reads.
         */
        $customerActions = ActivityLog::withoutGlobalScopes()
            ->where('organisation_id', $this->customer->id)
            ->pluck('action');

        $this->assertTrue($customerActions->contains('platform.lease.corrected'));
        $this->assertFalse($customerActions->contains('platform.records_viewed'));
    }

    public function test_records_and_leases_refuse_the_platform_organisation(): void
    {
        $this->getJson('/api/admin/organisations/'.$this->platform->id.'/records')
            ->assertNotFound();

        $this->getJson('/api/admin/organisations/'.$this->platform->id.'/leases/1')
            ->assertNotFound();
    }

    /*
    |----------------------------------------------------------------------
    | K8 — no provider key is a 409, not a 500
    |----------------------------------------------------------------------
    */

    public function test_the_mail_reader_and_composer_say_not_configured_instead_of_failing(): void
    {
        config(['services.resend.key' => null, 'mail.mailers.resend.key' => null]);

        $this->getJson('/api/admin/emails/abc?box=sent')
            ->assertStatus(409)
            ->assertJsonPath('configured', false);

        $this->postJson('/api/admin/emails', [
            'to' => ['someone@example.test'],
            'subject' => 'Hello',
            'body' => 'Body',
        ])->assertStatus(409);
    }

    /*
    |----------------------------------------------------------------------
    | K9 — a staff session is short whatever client asked
    |----------------------------------------------------------------------
    */

    public function test_a_staff_token_follows_the_platform_policy_whatever_client_minted_it(): void
    {
        $request = Request::create('/api/auth/login', 'POST');
        $request->headers->set('X-Patrimoine-Client', 'api');

        $token = app(AccessTokenService::class)
            ->issue($this->staff, $request, 'QA harness')
            ->accessToken;

        $this->assertSame('platform', $token->client_type);
        $this->assertEqualsWithDelta(60, now()->diffInMinutes($token->expires_at), 1);
        $this->assertEqualsWithDelta(60 * 12, now()->diffInMinutes($token->absolute_expires_at), 1);

        /*
         * A customer through the same client keeps the API policy.
         */
        $customerToken = app(AccessTokenService::class)
            ->issue($this->customerAdmin, $request, 'QA harness')
            ->accessToken;

        $this->assertSame('api', $customerToken->client_type);
        $this->assertGreaterThan(60 * 24, now()->diffInMinutes($customerToken->expires_at));
    }

    /*
    |----------------------------------------------------------------------
    | K4 / K11 — every password route and the console mailer are throttled
    |----------------------------------------------------------------------
    */

    public function test_password_routes_and_the_console_mailer_are_throttled_in_their_own_buckets(): void
    {
        $expected = [
            ['DELETE', 'api/v1/admin/organisations/{organisation}', 'throttle:5,1,console-delete'],
            ['POST', 'api/v1/admin/organisations/{organisation}/suspend', 'throttle:10,1,console-suspend'],
            ['POST', 'api/v1/admin/licenses/{license}/revoke', 'throttle:10,1,console-revoke'],
            ['PATCH', 'api/v1/admin/users/{user}/active', 'throttle:10,1,console-user-active'],
            ['PATCH', 'api/v1/admin/users/{user}/email', 'throttle:10,1,console-user-email'],
            ['POST', 'api/v1/admin/emails', 'throttle:30,60,console-send-mail'],
            ['DELETE', 'api/v1/organisation', 'throttle:5,1,close-organisation'],
            ['DELETE', 'api/v1/leases/{lease}', 'throttle:10,1,lease-delete'],
            ['POST', 'api/v1/parties/{party}/erase', 'throttle:5,1,erase-party'],
            ['POST', 'api/v1/auth/change-password', 'throttle:10,1,change-password'],
        ];

        foreach ($expected as [$method, $uri, $throttle]) {
            $route = collect(Route::getRoutes()->getRoutes())->first(
                fn ($route): bool => $route->uri() === $uri && in_array($method, $route->methods(), true)
            );

            $this->assertNotNull($route, $method.' '.$uri.' exists');

            $this->assertContains(
                $throttle,
                $route->gatherMiddleware(),
                $method.' '.$uri.' is throttled in its own bucket'
            );
        }
    }

    /*
    |----------------------------------------------------------------------
    | K11 — a deleted organisation's administrators are told
    |----------------------------------------------------------------------
    */

    public function test_deleting_an_organisation_from_the_console_tells_its_administrators(): void
    {
        Mail::fake();

        $this->customer->update(['status' => 'suspended']);

        $this->deleteJson('/api/admin/organisations/'.$this->customer->id, [
            'name_confirmation' => $this->customer->name,
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseMissing('organisations', ['id' => $this->customer->id]);

        Mail::assertSent(
            OrganisationDeletedMail::class,
            fn (OrganisationDeletedMail $mail): bool =>
                $mail->hasTo('admin@hardening.test')
                && $mail->organisationName === 'Hardening Lettings Ltd'
        );

        /*
         * The manager is not an administrator and is not written to.
         */
        Mail::assertNotSent(
            OrganisationDeletedMail::class,
            fn (OrganisationDeletedMail $mail): bool => $mail->hasTo('manager@hardening.test')
        );
    }
}
