<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\RentIncrement;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the V1.0.6 rent-increment HTTP surface: listing, scheduling
 * and cancelling — including role authorization and the guarantee that
 * applying an increment is NOT reachable over HTTP.
 */
class RentIncrementApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    /**
     * Create an active Lease suitable for rent-increment tests.
     */
    private function createLease(
        array $overrides = []
    ): Lease {
        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Increment API Tenant',
            'phone' => '0200090002',
            'email' => 'increment-api-tenant@example.test',
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $building = Building::create([
            'name' => 'Increment API Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Increment API Unit',
        ]);

        return Lease::create(
            array_merge(
                [
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => '2025-01-01',
                    'status' => 'active',
                    'rent_amount' => 10000,
                    'payment_frequency' => 'monthly',
                    'due_day' => 1,
                    'vat_rate' => 18,
                    'proration_amount' => null,
                    'security_deposit_amount' => 0,
                    'advance_payment_amount' => 0,
                    'rent_reserve_amount' => 0,
                    'rent_increment_type' => 'none',
                    'rent_increment_value' => 0,
                    'next_rent_increment_date' => null,
                    'management_fee_type' => 'none',
                    'management_fee_value' => 0,
                    'agent_commission_amount' => 0,
                ],
                $overrides
            )
        );
    }

    public function test_property_manager_can_schedule_an_increment(): void
    {
        Carbon::setTestNow('2026-06-01');

        $this->authenticateApiUser('property_manager');

        $lease = $this->createLease();

        $response = $this->postJson(
            "/api/leases/{$lease->id}/rent-increments",
            [
                'increment_type' => 'percentage',
                'increment_value' => 10,
                'effective_date' => '2026-08-01',
            ]
        );

        $response->assertStatus(201);

        $response->assertJsonPath(
            'rent_increment.old_rent_amount',
            10000
        );

        $response->assertJsonPath(
            'rent_increment.new_rent_amount',
            11000
        );

        $response->assertJsonPath(
            'rent_increment.status',
            'scheduled'
        );

        // Scheduling also stamps the informational date on the Lease.
        $this->assertSame(
            '2026-08-01',
            $lease->refresh()
                ->next_rent_increment_date
                ->toDateString()
        );

        // The action lands in the immutable Activity Log.
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'lease.rent_increment_scheduled',
            'entity_type' => 'lease',
            'entity_id' => (string) $lease->id,
        ]);
    }

    public function test_every_role_can_list_a_leases_increments(): void
    {
        Carbon::setTestNow('2026-06-01');

        $lease = $this->createLease();

        RentIncrement::create([
            'lease_id' => $lease->id,
            'old_rent_amount' => 10000,
            'increment_type' => 'fixed',
            'increment_value' => 2000,
            'new_rent_amount' => 12000,
            'effective_date' => '2026-09-01',
            'status' => 'scheduled',
        ]);

        foreach (['viewer', 'property_manager', 'administrator'] as $role) {
            $this->authenticateApiUser($role);

            $this->getJson("/api/leases/{$lease->id}/rent-increments")
                ->assertOk()
                ->assertJsonCount(1, 'rent_increments')
                ->assertJsonPath(
                    'rent_increments.0.new_rent_amount',
                    12000
                );
        }
    }

    public function test_viewer_cannot_schedule_or_cancel(): void
    {
        Carbon::setTestNow('2026-06-01');

        $this->authenticateApiUser('viewer');

        $lease = $this->createLease();

        $this->postJson(
            "/api/leases/{$lease->id}/rent-increments",
            [
                'increment_type' => 'fixed',
                'increment_value' => 1000,
                'effective_date' => '2026-08-01',
            ]
        )->assertForbidden();

        $increment = RentIncrement::create([
            'lease_id' => $lease->id,
            'old_rent_amount' => 10000,
            'increment_type' => 'fixed',
            'increment_value' => 1000,
            'new_rent_amount' => 11000,
            'effective_date' => '2026-08-01',
            'status' => 'scheduled',
        ]);

        $this->postJson(
            "/api/rent-increments/{$increment->id}/cancel"
        )->assertForbidden();
    }

    public function test_shape_validation_rejects_bad_input(): void
    {
        $this->authenticateApiUser('administrator');

        $lease = $this->createLease();

        $this->postJson(
            "/api/leases/{$lease->id}/rent-increments",
            [
                'increment_type' => 'doubling',
                'increment_value' => -5,
                'effective_date' => 'next year',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'increment_type',
                'increment_value',
                'effective_date',
            ]);
    }

    public function test_business_rules_surface_as_validation_errors(): void
    {
        Carbon::setTestNow('2026-06-01');

        $this->authenticateApiUser('administrator');

        $lease = $this->createLease();

        // First scheduling succeeds…
        $this->postJson(
            "/api/leases/{$lease->id}/rent-increments",
            [
                'increment_type' => 'percentage',
                'increment_value' => 5,
                'effective_date' => '2026-08-01',
            ]
        )->assertStatus(201);

        // …a second pending increment is a business-rule refusal (422).
        $this->postJson(
            "/api/leases/{$lease->id}/rent-increments",
            [
                'increment_type' => 'percentage',
                'increment_value' => 5,
                'effective_date' => '2026-09-01',
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rent_increment']);
    }

    public function test_manager_can_cancel_a_scheduled_increment(): void
    {
        Carbon::setTestNow('2026-06-01');

        $this->authenticateApiUser('property_manager');

        $lease = $this->createLease();

        $this->postJson(
            "/api/leases/{$lease->id}/rent-increments",
            [
                'increment_type' => 'fixed',
                'increment_value' => 2500,
                'effective_date' => '2026-10-01',
            ]
        )->assertStatus(201);

        $increment = RentIncrement::query()
            ->where('lease_id', $lease->id)
            ->firstOrFail();

        $this->postJson(
            "/api/rent-increments/{$increment->id}/cancel"
        )
            ->assertOk()
            ->assertJsonPath(
                'rent_increment.status',
                'cancelled'
            );

        // Cancelling clears the Lease's informational upcoming date.
        $this->assertNull(
            $lease->refresh()->next_rent_increment_date
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'lease.rent_increment_cancelled',
            'entity_type' => 'lease',
            'entity_id' => (string) $lease->id,
        ]);
    }

    public function test_an_applied_increment_cannot_be_cancelled(): void
    {
        $this->authenticateApiUser('administrator');

        $lease = $this->createLease();

        $applied = RentIncrement::create([
            'lease_id' => $lease->id,
            'old_rent_amount' => 10000,
            'increment_type' => 'fixed',
            'increment_value' => 1000,
            'new_rent_amount' => 11000,
            'effective_date' => '2026-01-01',
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $this->postJson(
            "/api/rent-increments/{$applied->id}/cancel"
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rent_increment']);
    }

    public function test_applying_an_increment_has_no_http_route(): void
    {
        $this->authenticateApiUser('administrator');

        $lease = $this->createLease();

        $increment = RentIncrement::create([
            'lease_id' => $lease->id,
            'old_rent_amount' => 10000,
            'increment_type' => 'fixed',
            'increment_value' => 1000,
            'new_rent_amount' => 11000,
            'effective_date' => '2026-01-01',
            'status' => 'scheduled',
        ]);

        // Rent may only change through the scheduler command.
        $this->postJson(
            "/api/rent-increments/{$increment->id}/apply"
        )->assertNotFound();
    }
}
