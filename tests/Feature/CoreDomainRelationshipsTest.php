<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the foundational Patrimoine domain relationships.
 *
 * These tests protect the core assumptions on which leases,
 * payments, owner accounting, and reporting will later depend.
 */
class CoreDomainRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A Party may hold more than one functional role.
     */
    public function test_party_can_have_multiple_roles(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Test Owner',
            'phone' => '0200000000',
            'email' => 'owner@example.test',
        ]);

        PartyRole::create([
            'party_id' => $party->id,
            'role' => 'owner',
        ]);

        PartyRole::create([
            'party_id' => $party->id,
            'role' => 'agent',
        ]);

        $this->assertCount(2, $party->roles);
        $this->assertTrue($party->roles->contains('role', 'owner'));
        $this->assertTrue($party->roles->contains('role', 'agent'));
    }

    /**
     * A Building may contain one or many independently leasable Units.
     */
    public function test_building_can_have_units(): void
    {
        $building = Building::create([
            'name' => 'Test Building',
        ]);

        Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 2',
        ]);

        $this->assertCount(2, $building->units);
        $this->assertSame(
            $building->id,
            $building->units->first()->building->id
        );
    }

    /**
     * Ownership is recorded at Building level and may use decimal
     * percentages where the ownership split requires it.
     */
    public function test_building_can_have_multiple_owners(): void
    {
        $building = Building::create([
            'name' => 'Jointly Owned Building',
        ]);

        $firstOwner = Party::create([
            'type' => 'person',
            'name' => 'Owner One',
            'phone' => '0200000001',
            'email' => 'owner1@example.test',
        ]);

        $secondOwner = Party::create([
            'type' => 'person',
            'name' => 'Owner Two',
            'phone' => '0200000002',
            'email' => 'owner2@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $firstOwner->id,
            'ownership_percentage' => 66.67,
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $secondOwner->id,
            'ownership_percentage' => 33.33,
        ]);

        $this->assertCount(2, $building->ownerships);

        $this->assertSame(
            '66.67',
            $building->ownerships->first()->ownership_percentage
        );

        $this->assertSame(
            $firstOwner->id,
            $building->ownerships->first()->party->id
        );
    }
}
