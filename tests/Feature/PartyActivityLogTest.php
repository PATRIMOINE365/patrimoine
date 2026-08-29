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

class PartyActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_create_records_one_snapshot_event(): void
    {
        Sanctum::actingAs($this->administrator());

        $response = $this
            ->postJson('/api/parties', [
                'type' => 'person',
                'name' => 'Activity Tenant',
                'phone' => '+233200000001',
                'phone_country' => 'GH',
                'email' => 'activity@example.test',
                'roles' => [
                    'tenant',
                    'owner',
                ],
            ])
            ->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'party.created',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('id'),
            $event->entity_id
        );

        $this->assertSame(
            ['owner', 'tenant'],
            $event->snapshot['roles']
        );

        $this->assertSame(
            'Activity Tenant',
            $event->snapshot['name']
        );
    }

    public function test_party_update_records_changed_fields_only(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Before Party',
            'phone' => '+233200000002',
            'phone_country' => 'GH',
            'email' => 'before@example.test',
        ]);

        $party->roles()->create([
            'role' => 'tenant',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/parties/{$party->id}",
                [
                    'type' => 'person',
                    'name' => 'After Party',
                    'phone' => '+233200000002',
                    'phone_country' => 'GH',
                    'email' => 'before@example.test',
                    'roles' => [
                        'tenant',
                        'owner',
                    ],
                ]
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'party.updated',
            $event->action
        );

        $this->assertSame(
            [
                'name' => 'Before Party',
                'roles' => ['tenant'],
            ],
            $event->before_values
        );

        $this->assertSame(
            [
                'name' => 'After Party',
                'roles' => ['owner', 'tenant'],
            ],
            $event->after_values
        );
    }

    public function test_same_party_state_is_not_logged_as_update(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Same Party',
            'phone' => '+233200000003',
            'phone_country' => 'GH',
            'email' => 'same@example.test',
        ]);

        $party->roles()->create([
            'role' => 'tenant',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/parties/{$party->id}",
                [
                    'type' => 'person',
                    'name' => 'Same Party',
                    'phone' => '+233200000003',
                    'phone_country' => 'GH',
                    'email' => 'same@example.test',
                    'roles' => ['tenant'],
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_successful_party_delete_preserves_snapshot(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Delete Party',
            'phone' => '+233200000004',
            'phone_country' => 'GH',
            'email' => 'delete@example.test',
        ]);

        $partyId = $party->id;

        Sanctum::actingAs($this->administrator());

        $this
            ->deleteJson(
                "/api/parties/{$partyId}"
            )
            ->assertNoContent();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'party.deleted',
            $event->action
        );

        $this->assertSame(
            (string) $partyId,
            $event->entity_id
        );

        $this->assertSame(
            'Delete Party',
            $event->snapshot['name']
        );
    }

    public function test_blocked_party_delete_creates_no_event(): void
    {
        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Referenced Tenant',
            'phone' => '+233200000005',
            'phone_country' => 'GH',
            'email' => 'referenced@example.test',
        ]);

        $tenant->roles()->create([
            'role' => 'tenant',
        ]);

        $building = Building::create([
            'name' => 'Referenced Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
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
                "/api/parties/{$tenant->id}"
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
