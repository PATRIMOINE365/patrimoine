<?php

namespace Tests\Feature;

use App\Mail\LicenseIssuedMail;
use App\Mail\PlanExpiryReminderMail;
use App\Mail\PlatformExpiryDigestMail;
use App\Mail\SignupAlertMail;
use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Organisation;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.11 platform administration console: access gate, licensing,
 * suspension, support tools, deletion and the expiry automation.
 */
class PlatformAdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $platformOrganisation;

    private User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformOrganisation = Organisation::factory()->create([
            'name' => 'Kality Ltd',
            'is_platform' => true,
        ]);

        $this->platformAdmin = OrganisationContext::runAs(
            (int) $this->platformOrganisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->platformOrganisation)
                ->create([
                    'email' => 'komla@patrimoine365.com',
                    'role' => 'administrator',
                ])
        );
    }

    private function actAsPlatformAdmin(): void
    {
        Sanctum::actingAs($this->platformAdmin);
    }

    /*
    |----------------------------------------------------------------------
    | Access gate
    |----------------------------------------------------------------------
    */

    public function test_customer_users_cannot_reach_the_console(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'administrator'])
        );

        $this->getJson('/api/admin/dashboard')->assertForbidden();

        $this->getJson('/api/admin/organisations')->assertForbidden();
    }

    public function test_platform_domain_alone_is_not_enough(): void
    {
        /*
         * A customer-organisation user who somehow carries the domain
         * still has no console: platform-organisation membership is
         * required too.
         */
        $impostor = User::factory()->create([
            'email' => 'fake@patrimoine365.com',
            'role' => 'administrator',
        ]);

        Sanctum::actingAs($impostor);

        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_admin_shell_page_renders(): void
    {
        /*
         * The shell is served to any visitor (admin.js performs the
         * authentication bootstrap); this compiles the dedicated admin
         * layout so template errors fail the suite, not the deploy.
         */
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Assign License')
            ->assertSee('Organizations');
    }

    public function test_activity_feed_lists_platform_actions(): void
    {
        $this->actAsPlatformAdmin();

        $this->postJson(
            '/api/admin/organisations/'.$this->testOrganisation->id.'/suspend',
            ['reason' => 'test', 'current_password' => 'password']
        )->assertOk();

        $rows = $this->getJson('/api/admin/activity')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($rows);

        $this->assertSame(
            'platform.organisation_suspended',
            $rows[0]['action']
        );

        $this->assertSame(
            'Test Organisation',
            $rows[0]['customer_organisation']
        );
    }

    public function test_platform_admin_reaches_the_console(): void
    {
        $this->actAsPlatformAdmin();

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'totals',
                'signups_this_month',
                'expiring_soon',
                'top_email_usage',
            ]);
    }

    public function test_bootstrap_command_creates_a_working_platform_admin(): void
    {
        /*
         * Remove the setUp() platform organisation so the command
         * exercises its own firstOrCreate path — the path that once
         * dropped is_platform through mass-assignment protection.
         */
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $this->platformAdmin->id)
            ->delete();

        \Illuminate\Support\Facades\DB::table('organisations')
            ->where('id', $this->platformOrganisation->id)
            ->delete();

        $this->artisan('patrimoine:create-platform-admin')
            ->expectsQuestion('Full name', 'Igor Kutsienyo')
            ->expectsQuestion('Email address', 'igor@patrimoine365.com')
            ->expectsQuestion('Password', 'Sup3rSecret42')
            ->expectsQuestion('Confirm password', 'Sup3rSecret42')
            ->assertExitCode(0);

        $created = User::withoutGlobalScopes()
            ->where('email', 'igor@patrimoine365.com')
            ->sole()
            ->load('organisation');

        $this->assertTrue($created->isPlatformAdmin());

        $this->assertTrue(
            (bool) $created->organisation->is_platform
        );

        /*
         * A rejected domain never creates anything.
         */
        $this->artisan('patrimoine:create-platform-admin')
            ->expectsQuestion('Full name', 'Wrong Domain')
            ->expectsQuestion('Email address', 'wrong@gmail.test')
            ->expectsQuestion('Password', 'Sup3rSecret42')
            ->expectsQuestion('Confirm password', 'Sup3rSecret42')
            ->assertExitCode(1);

        $this->assertSame(
            0,
            User::withoutGlobalScopes()
                ->where('email', 'wrong@gmail.test')
                ->count()
        );
    }

    public function test_me_exposes_the_platform_flag(): void
    {
        $this->actAsPlatformAdmin();

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('is_platform_admin', true);
    }

    public function test_signup_rejects_the_platform_domain(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', [
            'organisation_name' => 'Sneaky Org',
            'given_names' => 'Sly',
            'surname' => 'Fox',
            'email' => 'sly@patrimoine365.com',
            'password' => 'Sup3rSecret42',
            'password_confirmation' => 'Sup3rSecret42',
            'language' => 'en',
            'accept_legal' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_organisations_cannot_recruit_the_domain(): void
    {
        Sanctum::actingAs(
            User::factory()->create(['role' => 'administrator'])
        );

        $this->postJson('/api/users', [
            'name' => 'Wolf In Disguise',
            'email' => 'wolf@patrimoine365.com',
            'role' => 'property_manager',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_platform_organisation_only_recruits_the_domain(): void
    {
        $this->actAsPlatformAdmin();

        $this->postJson('/api/users', [
            'name' => 'Outside Address',
            'email' => 'igor@gmail.test',
            'role' => 'administrator',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Mail::fake();

        $this->postJson('/api/users', [
            'name' => 'Igor Kutsienyo',
            'email' => 'igor@patrimoine365.com',
            'role' => 'administrator',
        ])->assertCreated();
    }

    /*
    |----------------------------------------------------------------------
    | Organisations, licences, suspension
    |----------------------------------------------------------------------
    */

    public function test_console_lists_customers_but_never_the_platform(): void
    {
        $this->actAsPlatformAdmin();

        $names = collect(
            $this->getJson('/api/admin/organisations')
                ->assertOk()
                ->json('data')
        )->pluck('name');

        $this->assertTrue($names->contains('Test Organisation'));
        $this->assertFalse($names->contains('Kality Ltd'));
    }

    public function test_license_issuance_changes_the_plan_and_notifies_admins(): void
    {
        Mail::fake();

        $this->actAsPlatformAdmin();

        /*
         * Drop the default test licence: the customer starts Free.
         */
        License::query()->delete();

        $customerAdmin = User::withoutGlobalScopes()
            ->where('organisation_id', $this->testOrganisation->id)
            ->first()
            ?? OrganisationContext::runAs(
                (int) $this->testOrganisation->id,
                fn (): User => User::factory()
                    ->forOrganisation($this->testOrganisation)
                    ->create(['role' => 'administrator'])
            );

        $this->postJson('/api/admin/licenses', [
            'organisation_id' => $this->testOrganisation->id,
            'plan' => 'standard',
            'starts_on' => now()->toDateString(),
            'expires_on' => now()->addYear()->toDateString(),
            'amount' => 19999,
            'currency' => 'USD',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'TRX-001',
        ])->assertCreated();

        $licensing = app(\App\Services\LicensingService::class);

        $this->assertSame(
            'standard',
            $licensing->planFor($this->testOrganisation->fresh()->load('licenses'))
        );

        Mail::assertQueued(
            LicenseIssuedMail::class,
            fn (LicenseIssuedMail $mail): bool =>
                $mail->hasTo($customerAdmin->email)
        );

        /*
         * Both audit trails hold the action.
         */
        $this->assertSame(
            1,
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->platformOrganisation->id)
                ->where('action', 'platform.license_issued')
                ->count()
        );

        $this->assertSame(
            1,
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->testOrganisation->id)
                ->where('action', 'platform.license_issued')
                ->count()
        );
    }

    public function test_revocation_stops_the_licence_immediately(): void
    {
        $this->actAsPlatformAdmin();

        $license = License::query()->first();

        $this->postJson(
            '/api/admin/licenses/'.$license->id.'/revoke',
            ['current_password' => 'password', 'reason' => 'Unpaid']
        )->assertOk();

        $this->assertFalse($license->fresh()->coversToday());
    }

    public function test_suspension_and_reactivation_flip_the_status(): void
    {
        $this->actAsPlatformAdmin();

        $this->postJson(
            '/api/admin/organisations/'.$this->testOrganisation->id.'/suspend',
            ['reason' => 'Non-payment', 'current_password' => 'password']
        )->assertOk();

        $this->assertSame(
            'suspended',
            $this->testOrganisation->fresh()->status
        );

        /*
         * Sign-in refusal for suspended organisations is proven in
         * RegistrationAndMfaTest; the console side only flips status.
         */
        $this->postJson(
            '/api/admin/organisations/'.$this->testOrganisation->id.'/reactivate'
        )->assertOk();

        $this->assertSame(
            'active',
            $this->testOrganisation->fresh()->status
        );

        /*
         * Both actions audited on the platform side.
         */
        $this->assertSame(
            2,
            ActivityLog::withoutGlobalScopes()
                ->where('organisation_id', $this->platformOrganisation->id)
                ->whereIn('action', [
                    'platform.organisation_suspended',
                    'platform.organisation_reactivated',
                ])
                ->count()
        );
    }

    /*
    |----------------------------------------------------------------------
    | Support tools
    |----------------------------------------------------------------------
    */

    public function test_support_can_deactivate_but_never_strand_a_customer(): void
    {
        $this->actAsPlatformAdmin();

        $admin = User::factory()->create(['role' => 'administrator']);

        $manager = User::factory()->create(['role' => 'property_manager']);

        /*
         * The only active administrator is protected...
         */
        $this->patchJson(
            '/api/admin/users/'.$admin->id.'/active',
            ['is_active' => false, 'current_password' => 'password', 'reason' => 'Left the company']
        )->assertStatus(422);

        /*
         * ...but a manager can be deactivated, loses their sessions,
         * and can be reactivated.
         */
        $manager->createToken('test');

        $this->patchJson(
            '/api/admin/users/'.$manager->id.'/active',
            ['is_active' => false, 'current_password' => 'password', 'reason' => 'Left the company']
        )->assertOk();

        $this->assertFalse((bool) $manager->fresh()->is_active);

        $this->assertSame(0, $manager->tokens()->count());

        $this->patchJson(
            '/api/admin/users/'.$manager->id.'/active',
            ['is_active' => true]
        )->assertOk();
    }

    public function test_support_tools_refuse_platform_staff_targets(): void
    {
        $this->actAsPlatformAdmin();

        $this->postJson(
            '/api/admin/users/'.$this->platformAdmin->id.'/password-reset'
        )->assertNotFound();
    }

    public function test_support_sends_password_reset_across_organisations(): void
    {
        Mail::fake();

        $this->actAsPlatformAdmin();

        $customer = User::factory()->create();

        $this->postJson(
            '/api/admin/users/'.$customer->id.'/password-reset'
        )->assertOk();

        Mail::assertSent(
            \App\Mail\UserPasswordResetMail::class,
            fn ($mail): bool => $mail->hasTo($customer->email)
        );
    }

    /*
    |----------------------------------------------------------------------
    | Deletion
    |----------------------------------------------------------------------
    */

    public function test_deletion_requires_suspension_name_and_password(): void
    {
        $this->actAsPlatformAdmin();

        User::factory()->create();

        /*
         * Active organisation: refused outright.
         */
        $this->deleteJson(
            '/api/admin/organisations/'.$this->testOrganisation->id,
            [
                'name_confirmation' => 'Test Organisation',
                'password' => 'password',
            ]
        )->assertStatus(422);

        $this->testOrganisation->update(['status' => 'suspended']);

        /*
         * Wrong name, wrong password: refused.
         */
        $this->deleteJson(
            '/api/admin/organisations/'.$this->testOrganisation->id,
            [
                'name_confirmation' => 'Wrong Name',
                'password' => 'password',
            ]
        )->assertStatus(422);

        $this->deleteJson(
            '/api/admin/organisations/'.$this->testOrganisation->id,
            [
                'name_confirmation' => 'Test Organisation',
                'password' => 'not-the-password',
            ]
        )->assertStatus(422);

        /*
         * Correct everything: the organisation and every row vanish.
         */
        $this->deleteJson(
            '/api/admin/organisations/'.$this->testOrganisation->id,
            [
                'name_confirmation' => 'Test Organisation',
                'password' => 'password',
            ]
        )->assertOk();

        $this->assertDatabaseMissing('organisations', [
            'id' => $this->testOrganisation->id,
        ]);

        $this->assertSame(
            0,
            User::withoutGlobalScopes()
                ->where('organisation_id', $this->testOrganisation->id)
                ->count()
        );

        /*
         * The platform organisation survives, and the deletion is on
         * its audit trail.
         */
        $this->assertDatabaseHas('organisations', [
            'id' => $this->platformOrganisation->id,
        ]);

        $this->assertSame(
            1,
            ActivityLog::withoutGlobalScopes()
                ->where('action', 'platform.organisation_deleted')
                ->count()
        );
    }

    /*
    |----------------------------------------------------------------------
    | Automation
    |----------------------------------------------------------------------
    */

    public function test_signup_alert_reaches_the_operating_company(): void
    {
        Mail::fake();

        OrganisationContext::forget();

        $this->postJson('/api/auth/register', [
            'organisation_name' => 'Fresh Agency',
            'given_names' => 'Ama',
            'surname' => 'Mensah',
            'email' => 'ama@fresh.test',
            'password' => 'Sup3rSecret42',
            'password_confirmation' => 'Sup3rSecret42',
            'language' => 'en',
            'accept_legal' => true,
        ])->assertCreated();

        Mail::assertQueued(
            SignupAlertMail::class,
            fn (SignupAlertMail $mail): bool =>
                $mail->hasTo('hello@patrimoine365.com')
        );
    }

    public function test_expiry_reminders_fire_at_seven_and_one_days(): void
    {
        Mail::fake();

        /*
         * The default test org holds a perpetual licence — replace it
         * with one ending in exactly 7 days.
         */
        License::query()->delete();

        License::create([
            'organisation_id' => $this->testOrganisation->id,
            'plan' => 'standard',
            'starts_on' => now()->subMonth()->toDateString(),
            'expires_on' => now()->addDays(7)->toDateString(),
        ]);

        $customerAdmin = User::factory()->create([
            'role' => 'administrator',
        ]);

        $this->artisan('patrimoine:send-plan-expiry-reminders')
            ->assertExitCode(0);

        Mail::assertQueued(
            PlanExpiryReminderMail::class,
            fn (PlanExpiryReminderMail $mail): bool =>
                $mail->hasTo($customerAdmin->email)
                && $mail->daysLeft === 7
                && $mail->kind === 'license'
        );

        /*
         * A trial ending tomorrow triggers the 1-day reminder.
         */
        Mail::fake();

        License::query()->delete();

        $this->testOrganisation->update([
            'trial_ends_on' => now()->addDay()->toDateString(),
        ]);

        $this->artisan('patrimoine:send-plan-expiry-reminders')
            ->assertExitCode(0);

        Mail::assertQueued(
            PlanExpiryReminderMail::class,
            fn (PlanExpiryReminderMail $mail): bool =>
                $mail->daysLeft === 1
                && $mail->kind === 'trial'
        );

        /*
         * Nothing near expiry: silence.
         */
        Mail::fake();

        $this->testOrganisation->update([
            'trial_ends_on' => now()->addDays(20)->toDateString(),
        ]);

        $this->artisan('patrimoine:send-plan-expiry-reminders')
            ->assertExitCode(0);

        Mail::assertNotQueued(PlanExpiryReminderMail::class);
    }

    public function test_weekly_digest_reaches_billing(): void
    {
        Mail::fake();

        License::query()->delete();

        $this->testOrganisation->update([
            'trial_ends_on' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('patrimoine:send-platform-expiry-digest')
            ->assertExitCode(0);

        Mail::assertQueued(
            PlatformExpiryDigestMail::class,
            fn (PlatformExpiryDigestMail $mail): bool =>
                $mail->hasTo('billing@patrimoine365.com')
                && count($mail->rows) === 1
        );
    }
}
