<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerExpense;
use App\Models\OwnerPayout;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\OwnerFinancialJournalService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\OwnerAccountingService;
use App\Services\OwnerLedgerService;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Phase 11B1 acceptance coverage for ordinary Owner-side Financial Journal
 * integration.
 *
 * This suite proves:
 *
 * - pre-cutover compatibility;
 * - all six Owner financial event mappings;
 * - balanced postings;
 * - stable idempotency;
 * - operational rollback when Journal posting fails;
 * - API-level rollback includes Activity Log for manual Owner actions.
 */
class OwnerFinancialJournalIntegrationTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_pre_cutover_owner_events_do_not_post_journal(): void
    {
        $context =
            $this->ownerContext(
                managementFeeType: 'percentage',
                managementFeeValue: 10,
                agentCommissionAmount: 1200
            );

        /*
         * Deposit.
         */
        app(
            OwnerLedgerService::class
        )->recordDeposit(
            account: $context['account'],
            amount: 5000,
            transactionDate: '2026-08-20',
            paymentMethod: 'bank_transfer',
            depositPurpose: 'general_funding',
            buildingId: $context['building']->id,
            unitId: $context['unit']->id,
        );

        /*
         * Expense.
         */
        $expense =
            OwnerExpense::create([
                'building_id' =>
                    $context['building']->id,

                'unit_id' =>
                    $context['unit']->id,

                'description' =>
                    'Pre-cutover expense',

                'amount' =>
                    300,

                'expense_date' =>
                    '2026-08-20',
            ]);

        app(
            OwnerAccountingService::class
        )->allocateExpense(
            $expense
        );

        /*
         * Rent entitlement and Management Fee.
         */
        $allocation =
            $this->paymentAllocation(
                $context,
                2000
            );

        $accounting =
            app(
                OwnerAccountingService::class
            );

        $accounting
            ->postCollectedRentEntitlement(
                $allocation
            );

        $accounting
            ->postManagementFee(
                $allocation
            );

        /*
         * Agent Commission.
         */
        $accounting
            ->postAgentCommission(
                $context['lease']
            );

        /*
         * Payout.
         *
         * Existing Deposit and rent entitlement provide sufficient positive
         * Owner balance.
         */
        app(
            OwnerPayoutService::class
        )->create(
            account: $context['account'],
            amount: 1000,
            payoutDate: '2026-08-20',
            paymentMethod: 'bank_transfer',
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );

        /*
         * Runtime services must never initialize cutover implicitly.
         */
        $this->assertDatabaseCount(
            'accounting_cutovers',
            0
        );
    }

    public function test_owner_deposit_posts_correct_balanced_journal(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $transaction =
            app(
                OwnerLedgerService::class
            )->recordDeposit(
                account: $context['account'],
                amount: 5000,
                transactionDate: '2026-08-20',
                paymentMethod: 'bank_transfer',
                depositPurpose: 'general_funding',
                buildingId: $context['building']->id,
                unitId: $context['unit']->id,
                reference: 'OWNER-DEP-JRN-001',
            );

        $entry =
            $this->assertEntry(
                AccountingEventMap::EVENT_OWNER_DEPOSIT,
                5000,
                SystemChartOfAccounts::BANK,
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE
            );

        $this->assertSame(
            OwnerTransaction::class,
            $entry->source_type
        );

        $this->assertSame(
            $transaction->id,
            $entry->source_id
        );

        $this->assertSame(
            'owner-deposit:'.$transaction->id,
            $entry->idempotency_key
        );

        $this->assertSame(
            'bank_transfer',
            $entry->snapshot['payment_method']
        );

        $this->assertSame(
            5000,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_payout_posts_correct_balanced_journal(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        /*
         * Seed available Owner funds directly so this test isolates the
         * payout event itself.
         */
        OwnerTransaction::create([
            'owner_account_id' =>
                $context['account']->id,

            'direction' =>
                'credit',

            'category' =>
                'rent_entitlement',

            'amount' =>
                8000,

            'transaction_date' =>
                '2026-08-19',
        ]);

        $payout =
            app(
                OwnerPayoutService::class
            )->create(
                account: $context['account'],
                amount: 3000,
                payoutDate: '2026-08-20',
                paymentMethod: 'momo',
                reference: 'OWNER-PAYOUT-JRN-001',
            );

        $entry =
            $this->assertEntry(
                AccountingEventMap::EVENT_OWNER_PAYOUT,
                3000,
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
                SystemChartOfAccounts::MOBILE_PAYMENT_CLEARING
            );

        $this->assertSame(
            OwnerPayout::class,
            $entry->source_type
        );

        $this->assertSame(
            $payout->id,
            $entry->source_id
        );

        $this->assertSame(
            'owner-payout:'.$payout->id,
            $entry->idempotency_key
        );

        $ledger =
            OwnerTransaction::query()
                ->where(
                    'category',
                    'payout'
                )
                ->sole();

        $this->assertSame(
            'momo',
            $ledger->payment_method
        );

        $this->assertSame(
            5000,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_expense_posts_correct_balanced_journal(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $expense =
            OwnerExpense::create([
                'building_id' =>
                    $context['building']->id,

                'unit_id' =>
                    $context['unit']->id,

                'description' =>
                    'Journal roof repair',

                'amount' =>
                    2400,

                'expense_date' =>
                    '2026-08-20',

                'reference' =>
                    'OWNER-EXP-JRN-001',
            ]);

        $transactions =
            app(
                OwnerAccountingService::class
            )->allocateExpense(
                $expense
            );

        $this->assertCount(
            1,
            $transactions
        );

        $entry =
            $this->assertEntry(
                AccountingEventMap::EVENT_OWNER_EXPENSE,
                2400,
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
                SystemChartOfAccounts::PROPERTY_EXPENSE_CLEARING
            );

        $this->assertSame(
            OwnerExpense::class,
            $entry->source_type
        );

        $this->assertSame(
            $expense->id,
            $entry->source_id
        );

        $this->assertSame(
            'owner-expense:'.$expense->id,
            $entry->idempotency_key
        );

        $this->assertSame(
            -2400,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_rent_entitlement_posts_correct_balanced_journal(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $allocation =
            $this->paymentAllocation(
                $context,
                4000
            );

        $transactions =
            app(
                OwnerAccountingService::class
            )->postCollectedRentEntitlement(
                $allocation
            );

        $this->assertCount(
            1,
            $transactions
        );

        $entry =
            $this->assertEntry(
                AccountingEventMap::EVENT_OWNER_RENT_ENTITLEMENT,
                4000,
                SystemChartOfAccounts::RENT_BILLING_CLEARING,
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE
            );

        $this->assertSame(
            PaymentAllocation::class,
            $entry->source_type
        );

        $this->assertSame(
            $allocation->id,
            $entry->source_id
        );

        $this->assertSame(
            'owner-rent-entitlement:'.$allocation->id,
            $entry->idempotency_key
        );

        $this->assertSame(
            4000,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_management_fee_posts_correct_balanced_journal(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext(
                managementFeeType: 'percentage',
                managementFeeValue: 10
            );

        $allocation =
            $this->paymentAllocation(
                $context,
                6000
            );

        $transactions =
            app(
                OwnerAccountingService::class
            )->postManagementFee(
                $allocation
            );

        $this->assertCount(
            1,
            $transactions
        );

        $entry =
            $this->assertEntry(
                AccountingEventMap::EVENT_MANAGEMENT_FEE,
                600,
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
                SystemChartOfAccounts::MANAGEMENT_FEE_INCOME
            );

        $this->assertSame(
            PaymentAllocation::class,
            $entry->source_type
        );

        $this->assertSame(
            $allocation->id,
            $entry->source_id
        );

        $this->assertSame(
            'management-fee:'.$allocation->id,
            $entry->idempotency_key
        );

        $this->assertSame(
            -600,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_agent_commission_posts_correct_balanced_journal(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext(
                agentCommissionAmount: 2500
            );

        $transactions =
            app(
                OwnerAccountingService::class
            )->postAgentCommission(
                $context['lease']
            );

        $this->assertCount(
            1,
            $transactions
        );

        $entry =
            $this->assertEntry(
                AccountingEventMap::EVENT_AGENT_COMMISSION,
                2500,
                SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
                SystemChartOfAccounts::AGENT_COMMISSION_PAYABLE
            );

        $this->assertSame(
            Lease::class,
            $entry->source_type
        );

        $this->assertSame(
            $context['lease']->id,
            $entry->source_id
        );

        $this->assertSame(
            'agent-commission:'.$context['lease']->id,
            $entry->idempotency_key
        );

        /*
         * This is the important semantic correction in Phase 11B1:
         * commission reduces funds due to Owners and creates the Agent
         * commission liability.
         */
        $this->assertSame(
            -2500,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_financial_journal_posting_is_idempotent(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $transaction =
            app(
                OwnerLedgerService::class
            )->recordDeposit(
                account: $context['account'],
                amount: 1500,
                transactionDate: '2026-08-20',
                paymentMethod: 'bank_transfer',
                depositPurpose: 'general_funding',
            );

        $journal =
            app(
                OwnerFinancialJournalService::class
            );

        $journal->postDeposit(
            $transaction
        );

        $journal->postDeposit(
            $transaction
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'idempotency_key',
                    'owner-deposit:'.$transaction->id
                )
                ->count()
        );

        $this->assertDatabaseCount(
            'journal_lines',
            2
        );
    }

    public function test_rent_entitlement_journal_posting_is_idempotent(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $allocation =
            $this->paymentAllocation(
                $context,
                3000
            );

        $service =
            app(
                OwnerAccountingService::class
            );

        $service
            ->postCollectedRentEntitlement(
                $allocation
            );

        $service
            ->postCollectedRentEntitlement(
                $allocation
            );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'idempotency_key',
                    'owner-rent-entitlement:'
                    .$allocation->id
                )
                ->count()
        );

        $this->assertSame(
            1,
            OwnerTransaction::query()
                ->where(
                    'payment_allocation_id',
                    $allocation->id
                )
                ->where(
                    'category',
                    'rent_entitlement'
                )
                ->count()
        );
    }

    public function test_owner_deposit_journal_failure_rolls_back_operation_and_activity(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $this->forceJournalFailure(
            'Forced Owner Deposit Journal failure.'
        );

        $this
            ->postJson(
                "/api/owner-accounts/{$context['account']->id}/deposits",
                [
                    'amount' =>
                        5000,

                    'transaction_date' =>
                        '2026-08-20',

                    'payment_method' =>
                        'bank_transfer',

                    'deposit_purpose' =>
                        'general_funding',

                    'building_id' =>
                        $context['building']->id,

                    'unit_id' =>
                        $context['unit']->id,
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'owner_transactions',
            0
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_expense_journal_failure_rolls_back_operation_and_activity(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $this->forceJournalFailure(
            'Forced Owner Expense Journal failure.'
        );

        $this
            ->postJson(
                '/api/owner-expenses',
                [
                    'building_id' =>
                        $context['building']->id,

                    'unit_id' =>
                        $context['unit']->id,

                    'description' =>
                        'Rollback expense',

                    'amount' =>
                        2200,

                    'expense_date' =>
                        '2026-08-20',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'owner_expenses',
            0
        );

        $this->assertDatabaseCount(
            'owner_transactions',
            0
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_payout_journal_failure_rolls_back_operation_and_activity(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        /*
         * Historical/fixture credit provides payout capacity. It is direct
         * fixture state and is not part of the failing payout action.
         */
        OwnerTransaction::create([
            'owner_account_id' =>
                $context['account']->id,

            'direction' =>
                'credit',

            'category' =>
                'rent_entitlement',

            'amount' =>
                6000,

            'transaction_date' =>
                '2026-08-19',
        ]);

        $this->forceJournalFailure(
            'Forced Owner Payout Journal failure.'
        );

        $this
            ->postJson(
                "/api/owner-accounts/{$context['account']->id}/payouts",
                [
                    'amount' =>
                        3000,

                    'payout_date' =>
                        '2026-08-20',

                    'payment_method' =>
                        'bank_transfer',
                ]
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'owner_payouts',
            0
        );

        $this->assertDatabaseCount(
            'owner_payout_allocations',
            0
        );

        /*
         * Only the original fixture credit remains.
         */
        $this->assertDatabaseCount(
            'owner_transactions',
            1
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );

        $this->assertSame(
            6000,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_rent_entitlement_journal_failure_rolls_back_owner_ledger(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext();

        $allocation =
            $this->paymentAllocation(
                $context,
                3000
            );

        $this->forceJournalFailure(
            'Forced Owner Rent Entitlement Journal failure.'
        );

        try {
            app(
                OwnerAccountingService::class
            )->postCollectedRentEntitlement(
                $allocation
            );

            $this->fail(
                'Expected Owner Rent Entitlement Journal failure.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced Owner Rent Entitlement Journal failure.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            0,
            OwnerTransaction::query()
                ->where(
                    'category',
                    'rent_entitlement'
                )
                ->count()
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_management_fee_journal_failure_rolls_back_owner_ledger(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext(
                managementFeeType: 'percentage',
                managementFeeValue: 10
            );

        $allocation =
            $this->paymentAllocation(
                $context,
                5000
            );

        $this->forceJournalFailure(
            'Forced Management Fee Journal failure.'
        );

        try {
            app(
                OwnerAccountingService::class
            )->postManagementFee(
                $allocation
            );

            $this->fail(
                'Expected Management Fee Journal failure.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced Management Fee Journal failure.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            0,
            OwnerTransaction::query()
                ->where(
                    'category',
                    'management_fee'
                )
                ->count()
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_agent_commission_journal_failure_rolls_back_owner_ledger(): void
    {
        $this->activateAccounting();

        $context =
            $this->ownerContext(
                agentCommissionAmount: 1800
            );

        $this->forceJournalFailure(
            'Forced Agent Commission Journal failure.'
        );

        try {
            app(
                OwnerAccountingService::class
            )->postAgentCommission(
                $context['lease']
            );

            $this->fail(
                'Expected Agent Commission Journal failure.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced Agent Commission Journal failure.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            0,
            OwnerTransaction::query()
                ->where(
                    'category',
                    'agent_commission'
                )
                ->count()
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    private function activateAccounting(): AccountingCutover
    {
        return AccountingCutover::create([
            'cutover_key' =>
                AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' =>
                '2026-08-20',

            'status' =>
                AccountingCutover::STATUS_COMPLETED,

            'position_count' =>
                0,

            'journal_entry_count' =>
                0,

            'completed_at' =>
                now(),

            'metadata' => [
                'test_fixture' =>
                    true,
            ],
        ]);
    }

    /**
     * @return array{
     *     building: Building,
     *     owner: Party,
     *     account: OwnerAccount,
     *     unit: Unit,
     *     tenant: Party,
     *     lease: Lease
     * }
     */
    private function ownerContext(
        string $managementFeeType = 'none',
        int $managementFeeValue = 0,
        int $agentCommissionAmount = 0
    ): array {
        $building =
            Building::create([
                'name' =>
                    'Phase 11B1 Owner Journal Building '
                    .uniqid(),
            ]);

        $owner =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 11B1 Owner',

                'phone' =>
                    '0200011'
                    .random_int(
                        100,
                        999
                    ),

                'email' =>
                    'phase11b1-owner-'
                    .uniqid()
                    .'@example.test',
            ]);

        BuildingOwner::create([
            'building_id' =>
                $building->id,

            'party_id' =>
                $owner->id,

            'ownership_percentage' =>
                100.00,
        ]);

        $account =
            $owner
                ->ownerAccount()
                ->firstOrFail();

        $unit =
            Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Phase 11B1 Unit',
            ]);

        $tenant =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 11B1 Tenant',

                'phone' =>
                    '0200022'
                    .random_int(
                        100,
                        999
                    ),

                'email' =>
                    'phase11b1-tenant-'
                    .uniqid()
                    .'@example.test',
            ]);

        $lease =
            Lease::create([
                'unit_id' =>
                    $unit->id,

                'tenant_id' =>
                    $tenant->id,

                'start_date' =>
                    '2026-08-01',

                'rent_amount' =>
                    10000,

                'payment_frequency' =>
                    'monthly',

                'due_day' =>
                    1,

                'vat_rate' =>
                    0,

                'proration_amount' =>
                    null,

                'security_deposit_amount' =>
                    0,

                'advance_payment_amount' =>
                    0,

                'rent_reserve_amount' =>
                    0,

                'management_fee_type' =>
                    $managementFeeType,

                'management_fee_value' =>
                    $managementFeeValue,

                'agent_commission_amount' =>
                    $agentCommissionAmount,

                'status' =>
                    'active',
            ]);

        return [
            'building' =>
                $building,

            'owner' =>
                $owner,

            'account' =>
                $account,

            'unit' =>
                $unit,

            'tenant' =>
                $tenant,

            'lease' =>
                $lease,
        ];
    }

    /**
     * @param array{
     *     lease: Lease
     * } $context
     */
    private function paymentAllocation(
        array $context,
        int $amount
    ): PaymentAllocation {
        $invoice =
            Invoice::create([
                'lease_id' =>
                    $context['lease']->id,

                'invoice_number' =>
                    'PH11B1-INV-'
                    .uniqid(),

                'type' =>
                    'rent',

                'period_start' =>
                    '2026-08-01',

                'period_end' =>
                    '2026-08-31',

                'issue_date' =>
                    '2026-08-01',

                'due_date' =>
                    '2026-08-01',

                'status' =>
                    'partial',

                'total_amount' =>
                    10000,

                'vat_rate' =>
                    0,

                'net_amount' =>
                    10000,

                'vat_amount' =>
                    0,

                'proration_amount' =>
                    null,
            ]);

        $payment =
            Payment::create([
                'lease_id' =>
                    $context['lease']->id,

                'amount' =>
                    $amount,

                'payment_date' =>
                    '2026-08-20',

                'payment_method' =>
                    'bank_transfer',

                'reference' =>
                    'PH11B1-PAY-'
                    .uniqid(),
            ]);

        return PaymentAllocation::create([
            'payment_id' =>
                $payment->id,

            'invoice_id' =>
                $invoice->id,

            'amount' =>
                $amount,
        ]);
    }

    private function assertEntry(
        string $event,
        int $amount,
        string $debitAccount,
        string $creditAccount
    ): JournalEntry {
        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    $event
                )
                ->sole();

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            $amount,
            (int) $entry
                ->lines
                ->sum(
                    'debit_amount'
                )
        );

        $this->assertSame(
            $amount,
            (int) $entry
                ->lines
                ->sum(
                    'credit_amount'
                )
        );

        $this->assertTrue(
            $entry->lines
                ->contains(
                    fn (JournalLine $line): bool =>
                        $line
                            ->account_code_snapshot
                            === $debitAccount
                        && (int) $line
                            ->debit_amount
                            === $amount
                        && (int) $line
                            ->credit_amount
                            === 0
                )
        );

        $this->assertTrue(
            $entry->lines
                ->contains(
                    fn (JournalLine $line): bool =>
                        $line
                            ->account_code_snapshot
                            === $creditAccount
                        && (int) $line
                            ->credit_amount
                            === $amount
                        && (int) $line
                            ->debit_amount
                            === 0
                )
        );

        return $entry;
    }

    private function forceJournalFailure(
        string $message
    ): void {
        $this->mock(
            JournalPostingService::class,
            function ($mock) use (
                $message
            ): void {
                $mock
                    ->shouldReceive(
                        'post'
                    )
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            $message
                        )
                    );
            }
        );
    }
}
