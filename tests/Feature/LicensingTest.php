<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\License;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Services\LicensingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.10 licensing: plan resolution, limit enforcement and feature
 * gating.
 *
 * The default test organisation has no trial and no licence, so it sits
 * on the Free plan unless a test issues a licence or a trial.
 */
class LicensingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The base TestCase issues a perpetual Professional licence; these
     * tests observe plan behaviour, so each starts from a clean slate.
     */
    protected function setUp(): void
    {
        parent::setUp();

        License::query()->delete();
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'role' => 'administrator',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_plan_resolution_order(): void
    {
        $licensing = app(LicensingService::class);

        /*
         * No trial, no licence: Free.
         */
        $this->assertSame('free', $licensing->plan());

        /*
         * A running trial grants Professional.
         */
        $this->testOrganisation->update([
            'trial_ends_on' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame('professional', $licensing->plan());
        $this->assertTrue($licensing->onTrial());

        /*
         * A licence row beats the trial.
         */
        License::create([
            'organisation_id' => $this->testOrganisation->id,
            'plan' => 'standard',
            'starts_on' => now()->toDateString(),
            'expires_on' => now()->addYear()->toDateString(),
        ]);

        $this->assertSame('standard', $licensing->plan());
        $this->assertFalse($licensing->onTrial());

        /*
         * An expired trial with no licence falls back to Free.
         */
        License::query()->delete();

        $this->testOrganisation->update([
            'trial_ends_on' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame('free', $licensing->plan());
    }

    public function test_free_plan_blocks_second_active_user(): void
    {
        $this->admin();

        /*
         * The admin is user #1; the Free plan allows exactly 1.
         */
        $this->postJson('/api/users', [
            'name' => 'Second User',
            'email' => 'second@example.test',
            'role' => 'property_manager',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_free_plan_blocks_sixth_active_lease_but_never_drafts(): void
    {
        $this->admin();

        $portfolio = $this->buildPortfolio(7);

        /*
         * Five active leases fit the Free quota...
         */
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(
                '/api/leases',
                $this->leasePayload($portfolio[$i], 'active')
            )->assertCreated();
        }

        /*
         * ...the sixth is refused...
         */
        $this->postJson(
            '/api/leases',
            $this->leasePayload($portfolio[5], 'active')
        )->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        /*
         * ...but a DRAFT lease always remains possible, and activating
         * it later is refused while over quota.
         */
        $draft = $this->postJson(
            '/api/leases',
            $this->leasePayload($portfolio[6], 'draft')
        )->assertCreated()->json();

        $draftId = $draft['id'] ?? $draft['data']['id'];

        $this->patchJson('/api/leases/'.$draftId, [
            'status' => 'active',
        ])->assertStatus(422);
    }

    public function test_party_cap_blocks_creation_at_ceiling(): void
    {
        $this->admin();

        $limit = (int) config('licensing.plans.free.limits.parties');

        for ($i = Party::query()->count(); $i < $limit; $i++) {
            Party::create([
                'type' => 'person',
                'name' => 'Filler '.$i,
            ]);
        }

        $this->postJson('/api/parties', [
            'type' => 'person',
            'given_names' => 'One',
            'surname' => 'TooMany',
            'phone' => '+233200000001',
        ])->assertStatus(422);
    }

    public function test_reports_and_exports_are_plan_gated(): void
    {
        $this->admin();

        /*
         * Free: no reports.
         */
        $this->getJson('/api/reports/occupancy')
            ->assertForbidden();

        /*
         * Standard: reports and exports open up.
         */
        License::create([
            'organisation_id' => $this->testOrganisation->id,
            'plan' => 'standard',
            'starts_on' => now()->toDateString(),
            'expires_on' => null,
        ]);

        $this->getJson('/api/reports/occupancy')
            ->assertOk();
    }

    public function test_license_endpoint_reports_plan_usage_and_matrix(): void
    {
        $this->admin();

        $response = $this->getJson('/api/license')
            ->assertOk()
            ->json();

        $this->assertSame('free', $response['plan']);
        $this->assertSame(1, $response['usage']['users']);
        $this->assertArrayHasKey('professional', $response['plans']);
        $this->assertSame(
            1000,
            $response['plans']['professional']['limits']['active_leases']
        );
    }

    public function test_email_counter_tracks_product_mail(): void
    {
        $licensing = app(LicensingService::class);

        $licensing->registerEmail('transactional');
        $licensing->registerEmail('transactional');
        $licensing->registerEmail('automated');

        $this->assertSame(
            3,
            $licensing->usage()['emails_this_month']
        );

        $this->assertDatabaseHas('organisation_email_counters', [
            'organisation_id' => $this->testOrganisation->id,
            'period' => now()->format('Y-m'),
            'transactional_sent' => 2,
            'automated_sent' => 1,
        ]);
    }

    public function test_automated_email_needs_professional_plan_and_allowance(): void
    {
        $licensing = app(LicensingService::class);

        /*
         * Free plan: no automated mail at all.
         */
        $this->assertFalse($licensing->canSendAutomatedEmail());

        $this->testOrganisation->update([
            'trial_ends_on' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertTrue($licensing->canSendAutomatedEmail());
    }

    /**
     * @return list<array{tenant: Party, unit: Unit}>
     */
    private function buildPortfolio(int $units): array
    {
        $building = Building::create([
            'name' => 'Quota Building',
        ]);

        $result = [];

        for ($i = 1; $i <= $units; $i++) {
            $tenant = Party::create([
                'type' => 'person',
                'name' => 'Tenant '.$i,
            ]);

            $tenant->roles()->create([
                'role' => 'tenant',
            ]);

            $result[] = [
                'tenant' => $tenant,
                'unit' => Unit::create([
                    'building_id' => $building->id,
                    'name' => 'U'.$i,
                ]),
            ];
        }

        return $result;
    }

    /**
     * @param  array{tenant: Party, unit: Unit}  $slot
     * @return array<string, mixed>
     */
    private function leasePayload(array $slot, string $status): array
    {
        return [
            'unit_id' => $slot['unit']->id,
            'tenant_id' => $slot['tenant']->id,
            'start_date' => '2026-01-01',
            'status' => $status,
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 0,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'advance_received' => false,
            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'management_fee_type' => 'percentage',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ];
    }
}
