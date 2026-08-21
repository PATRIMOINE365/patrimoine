<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaseActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_lease_create_records_one_contractual_event(): void
    {
        [$tenant, $unit] =
            $this->tenantAndUnit();

        Sanctum::actingAs($this->administrator());

        $this
            ->postJson('/api/leases', [
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => '2026-08-01',
                'status' => 'draft',
                'rent_amount' => 5000,
                'payment_frequency' => 'monthly',
                'vat_rate' => 18,
                'proration_amount' => 0,
                'security_deposit_amount' => 0,
                'advance_payment_amount' => 0,
                'rent_reserve_amount' => 0,
                'advance_received' => 0,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'management_fee_type' => 'none',
                'management_fee_value' => 0,
                'agent_commission_amount' => 0,
            ])
            ->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'lease.created',
            $event->action
        );

        $this->assertSame(
            'Lease Tenant',
            $event->snapshot['tenant_name']
        );

        $this->assertSame(
            'Lease Unit',
            $event->snapshot['unit_name']
        );

        $this->assertArrayNotHasKey(
            'invoices',
            $event->snapshot
        );

        $this->assertArrayNotHasKey(
            'payments',
            $event->snapshot
        );
    }

    public function test_lease_update_records_changed_contractual_fields_only(): void
    {
        [$tenant, $unit] =
            $this->tenantAndUnit();

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'status' => 'draft',
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 18,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'advance_received' => 0,
            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/leases/{$lease->id}",
                [
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => '2026-08-01',
                    'status' => 'draft',
                    'rent_amount' => 5500,
                    'payment_frequency' => 'monthly',
                    'vat_rate' => 18,
                    'security_deposit_amount' => 0,
                    'advance_payment_amount' => 0,
                    'rent_reserve_amount' => 0,
                    'advance_received' => 0,
                    'rent_increment_type' => 'none',
                    'rent_increment_value' => 0,
                    'management_fee_type' => 'none',
                    'management_fee_value' => 0,
                    'agent_commission_amount' => 0,
                ]
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'lease.updated',
            $event->action
        );

        $this->assertSame(
            ['rent_amount' => 5000],
            $event->before_values
        );

        $this->assertSame(
            ['rent_amount' => 5500],
            $event->after_values
        );
    }

    public function test_same_lease_state_is_not_logged(): void
    {
        [$tenant, $unit] =
            $this->tenantAndUnit();

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'status' => 'draft',
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 18,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/leases/{$lease->id}",
                [
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => '2026-08-01',
                    'status' => 'draft',
                    'rent_amount' => 5000,
                    'payment_frequency' => 'monthly',
                    'vat_rate' => 18,
                    'security_deposit_amount' => 0,
                    'advance_payment_amount' => 0,
                    'rent_reserve_amount' => 0,
                    'advance_received' => 0,
                    'rent_increment_type' => 'none',
                    'rent_increment_value' => 0,
                    'management_fee_type' => 'none',
                    'management_fee_value' => 0,
                    'agent_commission_amount' => 0,
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_successful_draft_lease_delete_preserves_snapshot(): void
    {
        [$tenant, $unit] =
            $this->tenantAndUnit();

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'status' => 'draft',
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 18,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ]);

        $leaseId = $lease->id;

        Sanctum::actingAs($this->administrator());

        $this
            ->deleteJson(
                "/api/leases/{$leaseId}",
                [
                    'reason' =>
                        'Lease was created in error.',

                    'confirmation' =>
                        'DELETE',

                    'current_password' =>
                        'Password123!',
                ]
            )
            ->assertNoContent();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'lease.deleted',
            $event->action
        );

        $this->assertSame(
            (string) $leaseId,
            $event->entity_id
        );

        $this->assertSame(
            'Lease Tenant',
            $event->snapshot['tenant_name']
        );
    }

    public function test_failed_strong_confirmation_creates_no_delete_event(): void
    {
        [$tenant, $unit] =
            $this->tenantAndUnit();

        $lease = Lease::create([
            'unit_id' =>
                $unit->id,

            'tenant_id' =>
                $tenant->id,

            'start_date' =>
                '2026-08-01',

            'status' =>
                'active',

            'rent_amount' =>
                5000,

            'payment_frequency' =>
                'monthly',

            'vat_rate' =>
                18,

            'security_deposit_amount' =>
                0,

            'advance_payment_amount' =>
                0,

            'rent_reserve_amount' =>
                0,

            'management_fee_type' =>
                'none',

            'management_fee_value' =>
                0,

            'agent_commission_amount' =>
                0,
        ]);

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->deleteJson(
                "/api/leases/{$lease->id}",
                [
                    'reason' =>
                        'Attempted delete.',

                    'confirmation' =>
                        'delete',

                    'current_password' =>
                        'Password123!',
                ]
            )
            ->assertStatus(422);

        $this->assertDatabaseHas(
            'leases',
            [
                'id' =>
                    $lease->id,
            ]
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    /**
     * @return array{0: Party, 1: Unit}
     */
    private function tenantAndUnit(): array
    {
        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Lease Tenant',
            'phone' => '0200000020',
            'email' => 'lease-tenant@example.test',
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $building = Building::create([
            'name' => 'Lease Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Lease Unit',
        ]);

        return [
            $tenant,
            $unit,
        ];
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'role' =>
                UserRole::Administrator,

            'is_active' =>
                true,

            'email_verified_at' =>
                now(),

            'password' =>
                Hash::make(
                    'Password123!'
                ),
        ]);
    }
}
