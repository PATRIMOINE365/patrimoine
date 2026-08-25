<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the read-only OwnerAccount API used by the Owner workspace.
 */
class OwnerAccountApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create an owner with one property.
     *
     * BuildingOwner automatically provisions the OwnerAccount.
     *
     * @return array{
     *     owner: Party,
     *     account: OwnerAccount,
     *     building: Building,
     *     unit: Unit
     * }
     */
    private function createContext(): array
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Owner Account API Owner',
            'phone' => '0200040001',
            'email' => 'owner-account-api@example.test',
        ]);

        $building = Building::create([
            'name' => 'Owner Account API Building',
            'location' => 'Accra',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,

            'party_id' => $owner->id,

            'ownership_percentage' => 100.00,
        ]);

        $account =
            $owner
                ->ownerAccount()
                ->firstOrFail();

        $unit = Unit::create([
            'building_id' => $building->id,

            'name' => 'Unit OA-1',
        ]);

        return compact(
            'owner',
            'account',
            'building',
            'unit'
        );
    }

    /**
     * Owner search returns the consolidated account and financial summary.
     */
    public function test_owner_accounts_can_be_searched(): void
    {
        $context =
            $this->createContext();

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,

            'direction' => 'credit',

            'category' => 'owner_deposit',

            'amount' => 5000,

            'transaction_date' => '2026-08-11',

            'payment_method' => 'bank_transfer',

            'deposit_purpose' => 'general_funding',
        ]);

        $response =
            $this->getJson(
                '/api/owner-accounts?search=Owner+Account+API'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $context['account']->id
            )
            ->assertJsonPath(
                'data.0.party.id',
                $context['owner']->id
            )
            ->assertJsonPath(
                'data.0.balance',
                5000
            )
            ->assertJsonPath(
                'data.0.credited_amount',
                5000
            )
            ->assertJsonPath(
                'data.0.debited_amount',
                0
            )
            ->assertJsonPath(
                'data.0.property_count',
                1
            );
    }

    /**
     * Owner detail exposes properties even when there is no Lease.
     */
    public function test_owner_account_detail_includes_vacant_property(): void
    {
        $context =
            $this->createContext();

        $response =
            $this->getJson(
                "/api/owner-accounts/{$context['account']->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'properties.0.building.id',
                $context['building']->id
            )
            ->assertJsonPath(
                'properties.0.building.units.0.id',
                $context['unit']->id
            )
            ->assertJsonPath(
                'properties.0.ownership_percentage',
                '100.00'
            );
    }

    /**
     * Owner detail exposes current ledger totals and transactions.
     */
    public function test_owner_account_detail_includes_ledger(): void
    {
        $context =
            $this->createContext();

        $deposit =
            OwnerTransaction::create([
                'owner_account_id' => $context['account']->id,

                'building_id' => $context['building']->id,

                'unit_id' => $context['unit']->id,

                'direction' => 'credit',

                'category' => 'owner_deposit',

                'amount' => 7000,

                'transaction_date' => '2026-08-10',

                'payment_method' => 'bank_transfer',

                'deposit_purpose' => 'repair_maintenance',

                'reference' => 'OWNER-API-DEP-001',
            ]);

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,

            'building_id' => $context['building']->id,

            'unit_id' => $context['unit']->id,

            'direction' => 'debit',

            'category' => 'expense',

            'amount' => 3000,

            'transaction_date' => '2026-08-11',

            'reference' => 'OWNER-API-EXP-001',
        ]);

        $response =
            $this->getJson(
                "/api/owner-accounts/{$context['account']->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'credited_amount',
                7000
            )
            ->assertJsonPath(
                'debited_amount',
                3000
            )
            ->assertJsonPath(
                'balance',
                4000
            )
            ->assertJsonPath(
                'transactions.total',
                2
            );

        /*
         * Transactions are newest first, so the expense is first and
         * the older owner deposit follows it.
         */
        $response
            ->assertJsonPath(
                'transactions.data.0.category',
                'expense'
            )
            ->assertJsonPath(
                'transactions.data.1.category',
                'owner_deposit'
            )
            ->assertJsonPath(
                'transactions.data.1.receipt_endpoint',
                "/api/owner-deposits/{$deposit->id}/receipt"
            );
    }

    /**
     * Owner deposits expose receipts, accounting-only movements do not.
     */
    /**
     * V1.0.8: the Accounts drawer presents each ledger category's signed
     * effect on the balance, always all seven, zero included.
     */
    public function test_owner_account_detail_includes_category_totals(): void
    {
        $context =
            $this->createContext();

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'unit_id' => $context['unit']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'unit_id' => $context['unit']->id,
            'direction' => 'debit',
            'category' => 'management_fee',
            'amount' => 1500,
            'transaction_date' => '2026-08-01',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'unit_id' => $context['unit']->id,
            'direction' => 'debit',
            'category' => 'payout',
            'amount' => 4000,
            'transaction_date' => '2026-08-02',
        ]);

        $this->getJson(
            "/api/owner-accounts/{$context['account']->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'category_totals.rent_entitlement',
                10000
            )
            ->assertJsonPath(
                'category_totals.management_fee',
                -1500
            )
            ->assertJsonPath(
                'category_totals.payout',
                -4000
            )
            ->assertJsonPath(
                'category_totals.owner_deposit',
                0
            )
            ->assertJsonPath(
                'category_totals.expense',
                0
            )
            ->assertJsonPath(
                'category_totals.agent_commission',
                0
            )
            ->assertJsonPath(
                'category_totals.adjustment',
                0
            )
            ->assertJsonPath(
                'balance',
                4500
            );
    }

    public function test_only_owner_deposits_expose_receipt_endpoint(): void
    {
        $context =
            $this->createContext();

        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,

            'direction' => 'debit',

            'category' => 'adjustment',

            'amount' => 1000,

            'transaction_date' => '2026-08-11',

            'notes' => 'Administrative correction.',
        ]);

        $response =
            $this->getJson(
                "/api/owner-accounts/{$context['account']->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'transactions.data.0.receipt_endpoint',
                null
            );
    }
}
