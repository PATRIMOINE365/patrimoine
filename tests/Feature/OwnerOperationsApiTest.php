<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
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
 * Verifies transactional owner financial APIs.
 */
class OwnerOperationsApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create a single-owner Building and its automatically provisioned
     * consolidated OwnerAccount.
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

        /*
         * Creating Building ownership automatically provisions the Owner's
         * consolidated financial account.
         */
        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        /*
         * Reuse the automatically provisioned account.
         *
         * OwnerAccount has a unique party_id, so explicitly creating another
         * account here would violate the one-account-per-owner invariant.
         */
        $account = $owner
            ->ownerAccount()
            ->firstOrFail();

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
            $context['account']
                ->fresh()
                ->balance()
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
     * Owner deposits increase the OwnerAccount balance and preserve
     * the source/payment details required by the Payments workflow.
     */
    public function test_owner_deposit_can_be_recorded(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/deposits",
            [
                'amount' => 5000,
                'transaction_date' => '2026-08-11',

                /*
                 * Owner money is actual incoming money and therefore records
                 * the operational payment method used to receive it.
                 */
                'payment_method' => 'bank_transfer',

                /*
                 * This deposit is being supplied specifically for repair and
                 * maintenance work on the Owner's property.
                 */
                'deposit_purpose' => 'repair_maintenance',

                'building_id' => $context['building']->id,
                'unit_id' => $context['unit']->id,
                'reference' => 'DEP-API-001',
                'notes' => 'Funds supplied for air-conditioner repairs.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'owner_deposit'
            )
            ->assertJsonPath(
                'transaction.direction',
                'credit'
            )
            ->assertJsonPath(
                'transaction.amount',
                5000
            )
            ->assertJsonPath(
                'transaction.payment_method',
                'bank_transfer'
            )
            ->assertJsonPath(
                'transaction.deposit_purpose',
                'repair_maintenance'
            )
            ->assertJsonPath(
                'transaction.building_id',
                $context['building']->id
            )
            ->assertJsonPath(
                'transaction.unit_id',
                $context['unit']->id
            )
            ->assertJsonPath(
                'owner_account.balance',
                5000
            );

        $this->assertDatabaseHas('owner_transactions', [
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'unit_id' => $context['unit']->id,
            'direction' => 'credit',
            'category' => 'owner_deposit',
            'amount' => 5000,
            'payment_method' => 'bank_transfer',
            'deposit_purpose' => 'repair_maintenance',
            'reference' => 'DEP-API-001',
        ]);
    }

    /**
     * Cash owner deposits automatically attribute receipt to the
     * authenticated User without requiring a typed collector.
     */
    public function test_cash_owner_deposit_uses_authenticated_user_as_cash_receiver(): void
    {
        $context = $this->createContext();

        $user = \App\Models\User::factory()->create([
            'name' => 'Owner Cash Receiver',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/deposits",
            [
                'amount' => 5000,
                'transaction_date' => '2026-08-11',
                'payment_method' => 'cash',
                'deposit_purpose' => 'repair_maintenance',
                'building_id' => $context['building']->id,
                'unit_id' => $context['unit']->id,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.cash_receiver_user_id',
                $user->id
            )
            ->assertJsonPath(
                'transaction.cash_receiver_name',
                'Owner Cash Receiver'
            )
            ->assertJsonPath(
                'transaction.collector_name',
                null
            )
            ->assertJsonPath(
                'owner_account.balance',
                5000
            );

        $this->assertDatabaseHas('owner_transactions', [
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'unit_id' => $context['unit']->id,
            'category' => 'owner_deposit',
            'direction' => 'credit',
            'amount' => 5000,
            'payment_method' => 'cash',
            'deposit_purpose' => 'repair_maintenance',
            'cash_receiver_user_id' => $user->id,
            'cash_receiver_name' => 'Owner Cash Receiver',
            'collector_name' => null,
        ]);
    }

    /**
     * Typed legacy collector information cannot override the
     * authenticated User for a cash owner deposit.
     */
    public function test_cash_owner_deposit_ignores_typed_legacy_collector_name(): void
    {
        $context = $this->createContext();

        $user = \App\Models\User::factory()->create([
            'name' => 'Authenticated Owner Receiver',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/deposits",
            [
                'amount' => 2500,
                'transaction_date' => '2026-08-11',
                'payment_method' => 'cash',
                'deposit_purpose' => 'repair_maintenance',
                'building_id' => $context['building']->id,
                'unit_id' => $context['unit']->id,
                'collector_name' => 'Fake Typed Collector',
                'reference' => 'DEP-CASH-001',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.payment_method',
                'cash'
            )
            ->assertJsonPath(
                'transaction.deposit_purpose',
                'repair_maintenance'
            )
            ->assertJsonPath(
                'transaction.cash_receiver_user_id',
                $user->id
            )
            ->assertJsonPath(
                'transaction.cash_receiver_name',
                'Authenticated Owner Receiver'
            )
            ->assertJsonPath(
                'transaction.collector_name',
                null
            )
            ->assertJsonPath(
                'owner_account.balance',
                2500
            );

        $this->assertDatabaseHas('owner_transactions', [
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'unit_id' => $context['unit']->id,
            'category' => 'owner_deposit',
            'direction' => 'credit',
            'amount' => 2500,
            'payment_method' => 'cash',
            'deposit_purpose' => 'repair_maintenance',
            'cash_receiver_user_id' => $user->id,
            'cash_receiver_name' => 'Authenticated Owner Receiver',
            'collector_name' => null,
            'reference' => 'DEP-CASH-001',
        ]);
    }

    /**
     * Manual debit adjustments reduce owner balance.
     */
    public function test_owner_adjustment_can_be_recorded(): void
    {
        $context =
            $this->createContext();

        $ownerAccount =
            $context['account'];

        /*
         * V1.0.5 Adjustment semantics:
         *
         * The caller supplies the correct final balance.
         * Patrimoine derives direction and delta and uses today.
         */
        $response =
            $this->postJson(
                "/api/owner-accounts/{$ownerAccount->id}/adjustments",
                [
                    'corrected_balance' =>
                        2500,

                    'reason' =>
                        'Correct verified Owner balance.',

                    'reference' =>
                        'OWNER-ADJ-001',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.category',
                'adjustment'
            )
            ->assertJsonPath(
                'transaction.direction',
                'credit'
            )
            ->assertJsonPath(
                'transaction.amount',
                2500
            )
            ->assertJsonPath(
                'adjustment.previous_balance',
                0
            )
            ->assertJsonPath(
                'adjustment.corrected_balance',
                2500
            )
            ->assertJsonPath(
                'adjustment.difference',
                2500
            )
            ->assertJsonPath(
                'owner_account.balance',
                2500
            );

        $this->assertSame(
            2500,
            $ownerAccount
                ->fresh()
                ->balance()
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
            ->assertJsonPath(
                'payout.amount',
                7000
            )
            ->assertJsonPath(
                'allocated_amount',
                7000
            )
            ->assertJsonPath(
                'unallocated_amount',
                0
            )
            ->assertJsonPath(
                'owner_balance',
                3000
            );
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

        /*
         * Each BuildingOwner creation automatically provisions the
         * corresponding consolidated OwnerAccount.
         */
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

        $firstAccount = $firstOwner
            ->ownerAccount()
            ->firstOrFail();

        $secondAccount = $secondOwner
            ->ownerAccount()
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

    /**
     * Owner payout business-rule failures follow the configured language.
     */
    public function test_owner_payout_business_error_renders_in_french(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $context = $this->createContext();

        $this
            ->postJson(
                "/api/owner-accounts/{$context['account']->id}/payouts",
                [
                    'amount' => 100,
                    'payout_date' => now()->toDateString(),
                    'payment_method' => 'bank_transfer',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.payout.0',
                'Aucun fonds n’est disponible pour un paiement à ce propriétaire.'
            );
    }
}
