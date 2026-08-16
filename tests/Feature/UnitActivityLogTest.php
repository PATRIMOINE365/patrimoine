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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnitActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_create_records_building_context(): void
    {
        $building = Building::create([
            'name' => 'Unit Building',
        ]);

        Sanctum::actingAs($this->administrator());

        $response = $this
            ->postJson('/api/units', [
                'building_id' => $building->id,
                'name' => 'U-1',
                'description' => 'Created unit',
            ])
            ->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'unit.created',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('id'),
            $event->entity_id
        );

        $this->assertSame(
            'Unit Building',
            $event->snapshot['building_name']
        );
    }

    public function test_unit_update_records_changed_fields_only(): void
    {
        $building = Building::create([
            'name' => 'Existing Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'U-2',
            'description' => 'Before',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/units/{$unit->id}",
                [
                    'building_id' => $building->id,
                    'name' => 'U-2',
                    'description' => 'After',
                ]
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'unit.updated',
            $event->action
        );

        $this->assertSame(
            ['description' => 'Before'],
            $event->before_values
        );

        $this->assertSame(
            ['description' => 'After'],
            $event->after_values
        );
    }

    public function test_same_unit_state_is_not_logged(): void
    {
        $building = Building::create([
            'name' => 'Same Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'U-3',
            'description' => 'Same',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/units/{$unit->id}",
                [
                    'building_id' => $building->id,
                    'name' => 'U-3',
                    'description' => 'Same',
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_successful_unit_delete_preserves_snapshot(): void
    {
        $building = Building::create([
            'name' => 'Delete Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Delete Unit',
            'description' => 'Temporary',
        ]);

        $unitId = $unit->id;

        Sanctum::actingAs($this->administrator());

        $this
            ->deleteJson(
                "/api/units/{$unitId}"
            )
            ->assertNoContent();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'unit.deleted',
            $event->action
        );

        $this->assertSame(
            (string) $unitId,
            $event->entity_id
        );

        $this->assertSame(
            'Delete Unit',
            $event->snapshot['name']
        );

        $this->assertSame(
            'Delete Building',
            $event->snapshot['building_name']
        );
    }

    public function test_blocked_unit_delete_creates_no_event(): void
    {
        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Unit Tenant',
            'phone' => '0200000010',
            'email' => 'unit-tenant@example.test',
        ]);

        $building = Building::create([
            'name' => 'Protected Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Protected Unit',
        ]);

        Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'status' => 'draft',
            'rent_amount' => 1000,
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
            ->deleteJson(
                "/api/units/{$unit->id}"
            )
            ->assertStatus(409);

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
