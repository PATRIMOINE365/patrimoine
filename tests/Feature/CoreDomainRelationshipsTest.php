<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the foundational Patrimoine domain relationships.
 *
 * These tests protect the core assumptions on which leases,
 * payments, owner accounting, and reporting depend.
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

        $this->assertCount(
            2,
            $party->roles
        );

        $this->assertTrue(
            $party->roles->contains(
                'role',
                'owner'
            )
        );

        $this->assertTrue(
            $party->roles->contains(
                'role',
                'agent'
            )
        );
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

        $this->assertCount(
            2,
            $building->units
        );

        $this->assertSame(
            $building->id,
            $building
                ->units
                ->first()
                ->building
                ->id
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

        $this->assertCount(
            2,
            $building->ownerships
        );

        $this->assertSame(
            '66.67',
            $building
                ->ownerships
                ->first()
                ->ownership_percentage
        );

        $this->assertSame(
            $firstOwner->id,
            $building
                ->ownerships
                ->first()
                ->party
                ->id
        );
    }

    /**
     * A Party must receive a consolidated OwnerAccount as soon as that
     * Party becomes a Building Owner.
     *
     * OwnerAccount existence must not depend on:
     *
     * - an active Lease;
     * - tenant rent having been collected;
     * - an advance payment;
     * - a previous owner deposit;
     * - an existing owner-ledger transaction.
     *
     * This guarantees that a genuine landlord may provide funds for repairs,
     * maintenance or other property expenses even when the property is vacant
     * and the owner's current balance is zero.
     */
    public function test_building_owner_automatically_receives_owner_account(): void
    {
        $building = Building::create([
            'name' => 'Vacant Owner Account Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Vacant Property Owner',
            'phone' => '0200000003',
            'email' => 'vacant-owner@example.test',
        ]);

        /*
         * Before ownership is created there is no financial OwnerAccount.
         */
        $this->assertDatabaseMissing(
            'owner_accounts',
            [
                'party_id' => $owner->id,
            ]
        );

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        /*
         * Establishing ownership must immediately create the consolidated
         * financial account, even though no Lease or financial activity exists.
         */
        $this->assertDatabaseHas(
            'owner_accounts',
            [
                'party_id' => $owner->id,
                'status' => 'active',
            ]
        );

        $account = OwnerAccount::query()
            ->where(
                'party_id',
                $owner->id
            )
            ->firstOrFail();

        /*
         * A newly created OwnerAccount starts with no ledger activity and
         * therefore has a derived zero balance.
         */
        $this->assertSame(
            0,
            $account->balance()
        );

        /*
         * The Party relationship must expose the same OwnerAccount.
         */
        $this->assertSame(
            $account->id,
            $owner
                ->fresh()
                ->ownerAccount
                ->id
        );
    }

    /**
     * One owner keeps one consolidated OwnerAccount even when the same Party
     * owns more than one Building.
     */
    public function test_owner_account_is_not_duplicated_across_buildings(): void
    {
        $firstBuilding = Building::create([
            'name' => 'Owner Account Building One',
        ]);

        $secondBuilding = Building::create([
            'name' => 'Owner Account Building Two',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Multi Property Owner',
            'phone' => '0200000004',
            'email' => 'multi-property-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $firstBuilding->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        BuildingOwner::create([
            'building_id' => $secondBuilding->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        /*
         * Patrimoine 1.0 uses one consolidated financial account per owner,
         * regardless of how many Buildings the Party owns.
         */
        $this->assertSame(
            1,
            OwnerAccount::query()
                ->where(
                    'party_id',
                    $owner->id
                )
                ->count()
        );

        $this->assertSame(
            2,
            BuildingOwner::query()
                ->where(
                    'party_id',
                    $owner->id
                )
                ->count()
        );
    }
}
