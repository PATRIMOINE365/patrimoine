<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BuildingActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_create_records_ownership_snapshot(): void
    {
        $owner = $this->owner(
            'Create Owner',
            'create-owner@example.test'
        );

        Sanctum::actingAs($this->administrator());

        $response = $this
            ->postJson('/api/buildings', [
                'name' => 'Activity Building',
                'owners' => [
                    [
                        'party_id' => $owner->id,
                        'ownership_percentage' => 100,
                    ],
                ],
            ])
            ->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'building.created',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('id'),
            $event->entity_id
        );

        $this->assertSame(
            '100.00',
            $event->snapshot['owners'][0][
                'ownership_percentage'
            ]
        );
    }

    public function test_building_update_records_ownership_change_once(): void
    {
        $ownerOne = $this->owner(
            'Owner One',
            'owner-one@example.test'
        );

        $ownerTwo = $this->owner(
            'Owner Two',
            'owner-two@example.test'
        );

        $building = Building::create([
            'name' => 'Before Building',
        ]);

        $building->ownerships()->create([
            'party_id' => $ownerOne->id,
            'ownership_percentage' => 100,
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/buildings/{$building->id}",
                [
                    'name' => 'After Building',
                    'owners' => [
                        [
                            'party_id' => $ownerOne->id,
                            'ownership_percentage' => 60,
                        ],
                        [
                            'party_id' => $ownerTwo->id,
                            'ownership_percentage' => 40,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'building.updated',
            $event->action
        );

        $this->assertSame(
            'Before Building',
            $event->before_values['name']
        );

        $this->assertSame(
            'After Building',
            $event->after_values['name']
        );

        $this->assertArrayHasKey(
            'owners',
            $event->before_values
        );

        $this->assertArrayHasKey(
            'owners',
            $event->after_values
        );
    }

    public function test_same_building_state_is_not_logged(): void
    {
        $owner = $this->owner(
            'Same Owner',
            'same-owner@example.test'
        );

        $building = Building::create([
            'name' => 'Same Building',
        ]);

        $building->ownerships()->create([
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->patchJson(
                "/api/buildings/{$building->id}",
                [
                    'name' => 'Same Building',
                    'owners' => [
                        [
                            'party_id' => $owner->id,
                            'ownership_percentage' => 100,
                        ],
                    ],
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_successful_building_delete_preserves_snapshot(): void
    {
        $owner = $this->owner(
            'Delete Owner',
            'delete-owner@example.test'
        );

        $building = Building::create([
            'name' => 'Delete Building',
        ]);

        $building->ownerships()->create([
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $buildingId = $building->id;

        Sanctum::actingAs($this->administrator());

        $this
            ->deleteJson(
                "/api/buildings/{$buildingId}"
            )
            ->assertNoContent();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'building.deleted',
            $event->action
        );

        $this->assertSame(
            (string) $buildingId,
            $event->entity_id
        );

        $this->assertSame(
            'Delete Building',
            $event->snapshot['name']
        );
    }

    public function test_blocked_building_delete_creates_no_event(): void
    {
        $building = Building::create([
            'name' => 'Protected Building',
        ]);

        Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        Sanctum::actingAs($this->administrator());

        $this
            ->deleteJson(
                "/api/buildings/{$building->id}"
            )
            ->assertStatus(409);

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    private function owner(
        string $name,
        string $email
    ): Party {
        $party = Party::create([
            'type' => 'person',
            'name' => $name,
            'phone' => '0200000099',
            'email' => $email,
        ]);

        $party->roles()->create([
            'role' => 'owner',
        ]);

        return $party;
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
