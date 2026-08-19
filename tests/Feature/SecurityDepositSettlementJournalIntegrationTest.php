<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\Party;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\SecurityDepositSettlementJournalService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\SecurityDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SecurityDepositSettlementJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_pre_cutover_settlement_does_not_post_journal(): void
    {
        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 3000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $lease,
                '2026-08-19'
            );

        $this->assertSame(
            7000,
            $settlement->refund_amount
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'accounting_cutovers',
            0
        );
    }

    public function test_post_cutover_deduction_and_refund_post_balanced_journal(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 3000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $lease,
                '2026-08-19',
                null,
                'bank_transfer'
            );

        $this->assertSame(
            7000,
            $settlement->refund_amount
        );

        $this->assertSame(
            'bank_transfer',
            $settlement->refund_payment_method
        );

        $applied =
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_APPLIED
                )
                ->firstOrFail();

        $refund =
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_REFUND
                )
                ->firstOrFail();

        $this->assertTrue(
            $applied->isBalanced()
        );

        $this->assertTrue(
            $refund->isBalanced()
        );

        $this->assertSame(
            3000,
            $applied->debitTotal()
        );

        $this->assertSame(
            7000,
            $refund->debitTotal()
        );
    }

    public function test_deposit_applied_credits_security_deposit_recovery(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 3000
            );

        app(
            SecurityDepositService::class
        )->settle(
            $lease,
            '2026-08-19',
            null,
            'bank_transfer'
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_APPLIED
                )
                ->firstOrFail();

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === SystemChartOfAccounts::
                            SECURITY_DEPOSIT_HELD
                    && $line->debit_amount === 3000
                    && $line->credit_amount === 0
            )
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === SystemChartOfAccounts::
                            SECURITY_DEPOSIT_RECOVERY
                    && $line->debit_amount === 0
                    && $line->credit_amount === 3000
            )
        );
    }

    public function test_refund_credits_actual_payment_asset(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 2000
            );

        app(
            SecurityDepositService::class
        )->settle(
            $lease,
            '2026-08-19',
            null,
            'momo'
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_REFUND
                )
                ->firstOrFail();

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === SystemChartOfAccounts::
                            SECURITY_DEPOSIT_HELD
                    && $line->debit_amount === 8000
            )
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === SystemChartOfAccounts::
                            MOBILE_PAYMENT_CLEARING
                    && $line->credit_amount === 8000
            )
        );
    }

    public function test_excess_deductions_create_debt_invoice_journal(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 13000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $lease,
                '2026-08-19'
            );

        $this->assertSame(
            3000,
            $settlement->tenant_debt_amount
        );

        $invoice =
            Invoice::query()
                ->findOrFail(
                    $settlement->debt_invoice_id
                );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_DEBT_INVOICE
                )
                ->firstOrFail();

        $this->assertSame(
            Invoice::class,
            $entry->source_type
        );

        $this->assertSame(
            $invoice->id,
            $entry->source_id
        );

        $this->assertSame(
            3000,
            $entry->debitTotal()
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === SystemChartOfAccounts::
                            SECURITY_DEPOSIT_DEBT_RECEIVABLE
                    && $line->debit_amount === 3000
            )
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === SystemChartOfAccounts::
                            SECURITY_DEPOSIT_RECOVERY
                    && $line->credit_amount === 3000
            )
        );
    }

    public function test_exact_deductions_create_only_applied_event(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 10000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $lease,
                '2026-08-19'
            );

        $this->assertSame(
            0,
            $settlement->refund_amount
        );

        $this->assertSame(
            0,
            $settlement->tenant_debt_amount
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );

        $this->assertDatabaseHas(
            'journal_entries',
            [
                'transaction_type' =>
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_APPLIED,
            ]
        );
    }

    public function test_full_refund_requires_payment_method_after_cutover(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 0
            );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Security Deposit refund payment method is required.'
        );

        app(
            SecurityDepositService::class
        )->settle(
            $lease,
            '2026-08-19'
        );
    }

    public function test_no_refund_does_not_require_payment_method(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 13000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $lease,
                '2026-08-19'
            );

        $this->assertSame(
            0,
            $settlement->refund_amount
        );

        $this->assertNull(
            $settlement->refund_payment_method
        );
    }

    public function test_security_deposit_journal_posting_is_idempotent(): void
    {
        $this->createCompletedCutover();

        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 3000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $lease,
                '2026-08-19',
                null,
                'cash'
            );

        $deductionTransaction =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'deposit_deduction'
                )
                ->firstOrFail();

        $refundTransaction =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'refund'
                )
                ->firstOrFail();

        $journal =
            app(
                SecurityDepositSettlementJournalService::class
            );

        $before =
            JournalEntry::count();

        $journal->postApplied(
            $deductionTransaction,
            $settlement
        );

        $journal->postRefund(
            $refundTransaction,
            $settlement
        );

        $this->assertSame(
            $before,
            JournalEntry::count()
        );
    }

    public function test_runtime_settlement_does_not_initialize_cutover(): void
    {
        [
            'lease' => $lease,
        ] =
            $this->createContext(
                depositAmount: 10000,
                deductionAmount: 2000
            );

        app(
            SecurityDepositService::class
        )->settle(
            $lease,
            '2026-08-19'
        );

        $this->assertDatabaseCount(
            'accounting_cutovers',
            0
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );
    }

    /**
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount
     * }
     */
    private function createContext(
        int $depositAmount,
        int $deductionAmount
    ): array {
        $building =
            Building::create([
                'name' =>
                    'Phase 3 Step 5 Building',
            ]);

        $unit =
            Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Phase 3 Step 5 Unit',
            ]);

        $tenant =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 3 Step 5 Tenant',

                'phone' =>
                    '0200000505',

                'email' =>
                    'phase3-step5@example.test',
            ]);

        $lease =
            Lease::create([
                'unit_id' =>
                    $unit->id,

                'tenant_id' =>
                    $tenant->id,

                'start_date' =>
                    '2026-01-01',

                'rent_amount' =>
                    5000,

                'security_deposit_amount' =>
                    $depositAmount,

                'status' =>
                    'terminated',
            ]);

        $account =
            TenantFundAccount::create([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    'security_deposit',

                'status' =>
                    'active',
            ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                'deposit_funding',

            'amount' =>
                $depositAmount,

            'transaction_date' =>
                '2026-01-01',
        ]);

        if ($deductionAmount > 0) {
            SecurityDepositDeduction::create([
                'lease_id' =>
                    $lease->id,

                'description' =>
                    'Phase 3 Step 5 deduction',

                'amount' =>
                    $deductionAmount,

                'deduction_date' =>
                    '2026-08-18',
            ]);
        }

        return [
            'lease' =>
                $lease,

            'account' =>
                $account,
        ];
    }

    private function createCompletedCutover(): AccountingCutover
    {
        return AccountingCutover::create([
            'cutover_key' =>
                AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' =>
                '2026-08-19',

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
}
