<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\Accounting\TenantFundConsumptionJournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantFundConsumptionJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_pre_cutover_consumption_does_not_post_journal(): void
    {
        $transaction =
            $this->createConsumption(
                'rent_reserve',
                500
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );
    }

    public function test_rent_reserve_consumption_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $transaction =
            $this->createConsumption(
                'rent_reserve',
                500
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RESERVE_CONSUMPTION
                )
                ->firstOrFail();

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            500,
            (int) $entry->lines->sum(
                'debit_amount'
            )
        );

        $this->assertSame(
            500,
            (int) $entry->lines->sum(
                'credit_amount'
            )
        );
    }

    public function test_consumable_advance_consumption_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $transaction =
            $this->createConsumption(
                'consumable_advance',
                650
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_ADVANCE_CONSUMPTION
                )
                ->firstOrFail();

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            650,
            (int) $entry->lines->sum(
                'debit_amount'
            )
        );

        $this->assertSame(
            650,
            (int) $entry->lines->sum(
                'credit_amount'
            )
        );
    }

    public function test_rent_reserve_consumption_reduces_liability_and_receivable(): void
    {
        $this->createCompletedCutover();

        $transaction =
            $this->createConsumption(
                'rent_reserve',
                400
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RESERVE_CONSUMPTION
                )
                ->firstOrFail();

        $mapping =
            app(
                AccountingEventMap::class
            )->fixed(
                AccountingEventMap::
                    EVENT_RENT_RESERVE_CONSUMPTION
            );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === $mapping['debit']
                    && $line->debit_amount === 400
                    && $line->credit_amount === 0
            )
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === $mapping['credit']
                    && $line->debit_amount === 0
                    && $line->credit_amount === 400
            )
        );
    }

    public function test_consumable_advance_reduces_liability_and_receivable(): void
    {
        $this->createCompletedCutover();

        $transaction =
            $this->createConsumption(
                'consumable_advance',
                300
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_ADVANCE_CONSUMPTION
                )
                ->firstOrFail();

        $mapping =
            app(
                AccountingEventMap::class
            )->fixed(
                AccountingEventMap::
                    EVENT_ADVANCE_CONSUMPTION
            );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === $mapping['debit']
                    && $line->debit_amount === 300
            )
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn (JournalLine $line): bool =>
                    $line->account_code_snapshot
                        === $mapping['credit']
                    && $line->credit_amount === 300
            )
        );
    }

    public function test_consumption_posting_is_idempotent(): void
    {
        $this->createCompletedCutover();

        $transaction =
            $this->createConsumption(
                'rent_reserve',
                500
            );

        $service =
            app(
                TenantFundConsumptionJournalService::class
            );

        $service->post(
            $transaction
        );

        $service->post(
            $transaction
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'idempotency_key',
                    TenantFundConsumptionJournalService::
                        idempotencyKey(
                            $transaction
                        )
                )
                ->count()
        );
    }

    public function test_funding_credit_is_not_journaled_as_consumption(): void
    {
        $this->createCompletedCutover();

        [
            $account,
            $invoice,
        ] =
            $this->createFundScenario(
                'rent_reserve'
            );

        $transaction =
            TenantFundTransaction::create([
                'tenant_fund_account_id' =>
                    $account->id,

                'invoice_id' =>
                    $invoice->id,

                'direction' =>
                    'credit',

                'category' =>
                    'reserve_funding',

                'amount' =>
                    500,

                'transaction_date' =>
                    '2026-08-19',

                'reference' =>
                    'STEP4-CREDIT',

                'notes' =>
                    'Funding should not be treated as consumption.',
            ]);

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );
    }

    public function test_security_deposit_debit_is_not_step_4_consumption(): void
    {
        $this->createCompletedCutover();

        $transaction =
            $this->createConsumption(
                'security_deposit',
                500
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );
    }

    public function test_step_4_runtime_does_not_initialize_cutover(): void
    {
        $transaction =
            $this->createConsumption(
                'consumable_advance',
                500
            );

        app(
            TenantFundConsumptionJournalService::class
        )->post(
            $transaction
        );

        $this->assertSame(
            0,
            AccountingCutover::count()
        );
    }

    public function test_both_operational_consumption_services_are_wired(): void
    {
        $rentReserve =
            file_get_contents(
                app_path(
                    'Services/RentReserveService.php'
                )
            );

        $advance =
            file_get_contents(
                app_path(
                    'Services/ConsumableAdvanceService.php'
                )
            );

        $this->assertStringContainsString(
            'TenantFundConsumptionJournalService::class',
            $rentReserve
        );

        $this->assertStringContainsString(
            'TenantFundConsumptionJournalService::class',
            $advance
        );
    }

    private function createCompletedCutover(): AccountingCutover
    {
        return AccountingCutover::create([
            'cutover_key' =>
                AccountingCutover::
                    V105_OPENING_BALANCE,

            'cutover_date' =>
                '2026-08-19',

            'status' =>
                AccountingCutover::
                    STATUS_COMPLETED,

            'position_count' =>
                0,

            'journal_entry_count' =>
                0,

            'completed_at' =>
                now(),

            'metadata' =>
                [],
        ]);
    }

    private function createConsumption(
        string $fundType,
        int $amount
    ): TenantFundTransaction {
        [
            $account,
            $invoice,
        ] =
            $this->createFundScenario(
                $fundType
            );

        /*
         * Seed enough held value so the fixture represents a legitimate
         * operational fund position before its debit is consumed.
         */
        TenantFundTransaction::create([
            'tenant_fund_account_id' =>
                $account->id,

            'direction' =>
                'credit',

            'category' =>
                match ($fundType) {
                    'rent_reserve' =>
                        'reserve_funding',

                    'consumable_advance' =>
                        'advance_funding',

                    'security_deposit' =>
                        'deposit_funding',
                },

            'amount' =>
                $amount,

            'transaction_date' =>
                '2026-08-18',

            'reference' =>
                'STEP4-FUNDING',

            'notes' =>
                'Phase 3 Step 4 fixture funding.',
        ]);

        return TenantFundTransaction::create([
            'tenant_fund_account_id' =>
                $account->id,

            'invoice_id' =>
                $invoice->id,

            'direction' =>
                'debit',

            'category' =>
                match ($fundType) {
                    'rent_reserve' =>
                        'rent_consumption',

                    'consumable_advance' =>
                        'advance_consumption',

                    'security_deposit' =>
                        'deposit_deduction',
                },

            'amount' =>
                $amount,

            'transaction_date' =>
                '2026-08-19',

            'reference' =>
                'STEP4-CONSUMPTION',

            'notes' =>
                'Phase 3 Step 4 fixture consumption.',
        ]);
    }

    /**
     * @return array{0: TenantFundAccount, 1: Invoice}
     */
    private function createFundScenario(
        string $fundType
    ): array {
        $building =
            Building::create([
                'name' =>
                    'Phase 3 Step 4 Building',
            ]);

        $unit =
            Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Step 4 Unit',
            ]);

        $tenant =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 3 Step 4 Tenant',

                'phone' =>
                    '0200003400',

                'email' =>
                    uniqid(
                        'step4-tenant-'
                    )
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
                    1000,

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

                'management_fee_type' =>
                    'none',

                'management_fee_value' =>
                    0,

                'agent_commission_amount' =>
                    0,

                'status' =>
                    'active',
            ]);

        $invoice =
            Invoice::create([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    'rent',

                'invoice_number' =>
                    'PH3-S4-'.uniqid(),

                'period_start' =>
                    '2026-08-01',

                'period_end' =>
                    '2026-08-31',

                'issue_date' =>
                    '2026-08-01',

                'due_date' =>
                    '2026-08-01',

                'status' =>
                    'issued',

                'total_amount' =>
                    1000,

                'vat_rate' =>
                    0,

                'net_amount' =>
                    1000,

                'vat_amount' =>
                    0,

                'proration_amount' =>
                    null,

                'notes' =>
                    null,
            ]);

        $account =
            TenantFundAccount::create([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    $fundType,

                'status' =>
                    'active',
            ]);

        return [
            $account,
            $invoice,
        ];
    }
}
