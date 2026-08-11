<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies transactional owner financial APIs.
 */
class OwnerOperationsApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create a single-owner Building and corresponding OwnerAccount.
     *
     * @return array{
     *     building: Building,
     *     owner: Party,
     *     account: OwnerAccount,
     *     unit: Unit
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Owner Operations API Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Owner Operations API Owner',
            'phone' => '0200000900',
            'email' => 'owner-operations@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $account = OwnerAccount::create([
            'party_id' => $owner->id,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        return compact(
            'building',
            'owner',
            'account',
            'unit'
        );
    }

    /**
     * Property expenses are recorded and allocated to owners.
     */
    public function test_owner_expense_is_recorded_and_allocated(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            '/api/owner-expenses',
            [
                'building_id' => $context['building']->id,
                'unit_id' => $context['unit']->id,
                'description' => 'Air-conditioner repair',
                'amount' => 3000,
                'expense_date' => '2026-08-11',
                'reference' => 'EXP-API-001',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'expense.description',
                'Air-conditioner repair'
            );

        $this->assertDatabaseHas('owner_transactions', [
            'owner_account_id' => $context['account']->id,
            'category' => 'expense',
            'direction' => 'debit',
            'amount' => 3000,
        ]);

        $this->assertSame(
            -3000,
            $context['account']->fresh()->balance()
        );
    }

    /**
     * Expense Unit must belong to the supplied Building.
     */
    public function test_owner_expense_rejects_unit_from_another_building(): void
    {
        $context = $this->createContext();

        $otherBuilding = Building::create([
            'name' => 'Other Building',
        ]);

        $otherUnit = Unit::create([
            'building_id' => $otherBuilding->id,
            'name' => 'Other Unit',
        ]);

        $this->postJson(
            '/api/owner-expenses',
            [
                'building_id' => $context['building']->id,
                'unit_id' => $otherUnit->id,
                'description' => 'Invalid expense',
                'amount' => 3000,
                'expense_date' => '2026-08-11',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'unit_id',
            ]);
    }

    /**
     * Owner deposits increase the OwnerAccount balance.
     */
    public function test_owner_deposit_can_be_recorded(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/deposits",
            [
                'amount' => 5000,
                'transaction_date' => '2026-08-11',
                'reference' => 'DEP-API-001',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'owner_deposit'
            )
            ->assertJsonPath(
                'owner_account.balance',
                5000
            );
    }

    /**
     * Manual debit adjustments reduce owner balance.
     */
    public function test_owner_adjustment_can_be_recorded(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/adjustments",
            [
                'direction' => 'debit',
                'amount' => 2000,
                'transaction_date' => '2026-08-11',
                'reason' => 'Correction of overstated owner funds.',
                'reference' => 'ADJ-API-001',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'adjustment'
            )
            ->assertJsonPath(
                'owner_account.balance',
                -2000
            );
    }

    /**
     * Manual adjustments require an audit reason.
     */
    public function test_owner_adjustment_requires_reason(): void
    {
        $context = $this->createContext();

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/adjustments",
            [
                'direction' => 'credit',
                'amount' => 1000,
                'transaction_date' => '2026-08-11',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'reason',
            ]);
    }

    /**
     * Available owner funds may be paid out.
     */
    public function test_owner_payout_can_be_created(): void
    {
        $context = $this->createContext();

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/payouts",
            [
                'amount' => 7000,
                'payout_date' => '2026-08-11',
                'payment_method' => 'bank_transfer',
                'reference' => 'PAY-API-001',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('payout.amount', 7000)
            ->assertJsonPath('allocated_amount', 7000)
            ->assertJsonPath('unallocated_amount', 0)
            ->assertJsonPath('owner_balance', 3000);
    }

    /**
     * Payout cannot exceed the owner's available net balance.
     */
    public function test_owner_payout_cannot_exceed_available_balance(): void
    {
        $context = $this->createContext();

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 5000,
            'transaction_date' => '2026-08-01',
        ]);

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/payouts",
            [
                'amount' => 6000,
                'payout_date' => '2026-08-11',
                'payment_method' => 'momo',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'payout',
            ]);
    }

    /**
     * Expense allocation still respects ownership percentages.
     */
    public function test_owner_expense_can_be_split_between_multiple_owners(): void
    {
        $building = Building::create([
            'name' => 'Shared Expense Building',
        ]);

        $firstOwner = Party::create([
            'type' => 'person',
            'name' => 'First Shared Owner',
            'phone' => '0200000901',
            'email' => 'shared-one@example.test',
        ]);

        $secondOwner = Party::create([
            'type' => 'person',
            'name' => 'Second Shared Owner',
            'phone' => '0200000902',
            'email' => 'shared-two@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $firstOwner->id,
            'ownership_percentage' => 60.00,
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $secondOwner->id,
            'ownership_percentage' => 40.00,
        ]);

        $this->postJson(
            '/api/owner-expenses',
            [
                'building_id' => $building->id,
                'description' => 'Roof repair',
                'amount' => 10000,
                'expense_date' => '2026-08-11',
            ]
        )->assertCreated();

        $firstAccount = OwnerAccount::query()
            ->where('party_id', $firstOwner->id)
            ->firstOrFail();

        $secondAccount = OwnerAccount::query()
            ->where('party_id', $secondOwner->id)
            ->firstOrFail();

        $this->assertSame(
            -6000,
            $firstAccount->balance()
        );

        $this->assertSame(
            -4000,
            $secondAccount->balance()
        );
    }
}
