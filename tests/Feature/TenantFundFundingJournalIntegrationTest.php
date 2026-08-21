<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\Accounting\TenantFundFundingJournalService;
use App\Services\TenantFundAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantFundFundingJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_pre_cutover_funding_does_not_post_journal(): void
    {
        $payment =
            $this->createPayment();

        app(
            TenantFundAllocationService::class
        )->allocate(
            payment: $payment,
            fundType: 'rent_reserve',
            amount: 400,
            transactionDate: '2026-08-19'
        );

        $this->assertSame(
            0,
            JournalEntry::count()
        );
    }

    public function test_rent_reserve_funding_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment();

        $transaction =
            app(
                TenantFundAllocationService::class
            )->allocate(
                payment: $payment,
                fundType: 'rent_reserve',
                amount: 400,
                transactionDate: '2026-08-19'
            );

        $this->assertNotNull(
            $transaction
        );

        $entry =
            $this->entryFor(
                AccountingEventMap::
                    EVENT_RENT_RESERVE_FUNDING
            );

        $this->assertTrue(
            $entry
                ->load('lines')
                ->isBalanced()
        );

        $this->assertSame(
            400,
            (int) $entry
                ->lines
                ->sum('debit_amount')
        );

        $this->assertSame(
            400,
            (int) $entry
                ->lines
                ->sum('credit_amount')
        );
    }

    public function test_consumable_advance_funding_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment();

        app(
            TenantFundAllocationService::class
        )->allocate(
            payment: $payment,
            fundType: 'consumable_advance',
            amount: 300,
            transactionDate: '2026-08-19'
        );

        $entry =
            $this->entryFor(
                AccountingEventMap::
                    EVENT_ADVANCE_FUNDING
            );

        $this->assertTrue(
            $entry
                ->load('lines')
                ->isBalanced()
        );
    }

    public function test_security_deposit_funding_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment();

        app(
            TenantFundAllocationService::class
        )->allocate(
            payment: $payment,
            fundType: 'security_deposit',
            amount: 250,
            transactionDate: '2026-08-19'
        );

        $entry =
            $this->entryFor(
                AccountingEventMap::
                    EVENT_SECURITY_DEPOSIT_FUNDING
            );

        $this->assertTrue(
            $entry
                ->load('lines')
                ->isBalanced()
        );
    }

    public function test_funding_debits_payment_asset_and_credits_fund_liability(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment(
                paymentMethod:
                    'bank_transfer'
            );

        app(
            TenantFundAllocationService::class
        )->allocate(
            payment: $payment,
            fundType: 'security_deposit',
            amount: 250,
            transactionDate: '2026-08-19'
        );

        $entry =
            $this->entryFor(
                AccountingEventMap::
                    EVENT_SECURITY_DEPOSIT_FUNDING
            );

        $entry->load(
            'lines'
        );

        $map =
            app(
                AccountingEventMap::class
            );

        $asset =
            $map->paymentAsset(
                'bank_transfer'
            );

        $liability =
            $map->tenantFundLiability(
                'security_deposit'
            );

        $this->assertTrue(
            $entry->lines
                ->contains(
                    fn ($line): bool =>
                        $line
                            ->account_code_snapshot
                            === $asset
                        && $line
                            ->debit_amount
                            === 250
                )
        );

        $this->assertTrue(
            $entry->lines
                ->contains(
                    fn ($line): bool =>
                        $line
                            ->account_code_snapshot
                            === $liability
                        && $line
                            ->credit_amount
                            === 250
                )
        );
    }

    public function test_split_funding_posts_separate_journal_events(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment(
                amount:
                    1000
            );

        $service =
            app(
                TenantFundAllocationService::class
            );

        $service->allocate(
            payment: $payment,
            fundType: 'rent_reserve',
            amount: 400,
            transactionDate: '2026-08-19'
        );

        $service->allocate(
            payment: $payment,
            fundType: 'consumable_advance',
            amount: 300,
            transactionDate: '2026-08-19'
        );

        $service->allocate(
            payment: $payment,
            fundType: 'security_deposit',
            amount: 200,
            transactionDate: '2026-08-19'
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RESERVE_FUNDING
                )
                ->count()
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_ADVANCE_FUNDING
                )
                ->count()
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_FUNDING
                )
                ->count()
        );
    }

    public function test_funding_journal_posting_is_idempotent(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment();

        $transaction =
            app(
                TenantFundAllocationService::class
            )->allocate(
                payment: $payment,
                fundType: 'rent_reserve',
                amount: 400,
                transactionDate: '2026-08-19'
            );

        $this->assertInstanceOf(
            TenantFundTransaction::class,
            $transaction
        );

        $key =
            TenantFundFundingJournalService::
                idempotencyKey(
                    $transaction
                );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'idempotency_key',
                    $key
                )
                ->count()
        );

        app(
            TenantFundFundingJournalService::class
        )->post(
            $transaction
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'idempotency_key',
                    $key
                )
                ->count()
        );
    }

    public function test_zero_allocation_creates_neither_fund_transaction_nor_journal(): void
    {
        $this->createCompletedCutover();

        $payment =
            $this->createPayment();

        $transaction =
            app(
                TenantFundAllocationService::class
            )->allocate(
                payment: $payment,
                fundType: 'rent_reserve',
                amount: 0,
                transactionDate: '2026-08-19'
            );

        $this->assertNull(
            $transaction
        );

        $this->assertSame(
            0,
            TenantFundTransaction::count()
        );

        $this->assertSame(
            0,
            JournalEntry::count()
        );
    }

    public function test_runtime_funding_does_not_initialize_cutover(): void
    {
        $payment =
            $this->createPayment();

        app(
            TenantFundAllocationService::class
        )->allocate(
            payment: $payment,
            fundType: 'rent_reserve',
            amount: 400,
            transactionDate: '2026-08-19'
        );

        $this->assertSame(
            0,
            AccountingCutover::count()
        );
    }

    public function test_existing_api_funding_boundary_is_wired_to_same_journal_service(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Http/Controllers/Api/TenantFundController.php'
                )
            );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            'TenantFundFundingJournalService::class',
            $source
        );

        $this->assertStringContainsString(
            '->post(',
            $source
        );
    }

    private function entryFor(
        string $type
    ): JournalEntry {
        return JournalEntry::query()
            ->where(
                'transaction_type',
                $type
            )
            ->latest('id')
            ->firstOrFail();
    }

    private function createPayment(
        int $amount = 1000,
        string $paymentMethod = 'bank_transfer'
    ): Payment {
        $building =
            Building::create([
                'name' =>
                    'Phase 3 Step 3 Building',
            ]);

        $unit =
            Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Unit 1',
            ]);

        $tenant =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 3 Step 3 Tenant',

                'phone' =>
                    '0200003300',

                'email' =>
                    'phase3-step3-'
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
                    1000,

                'management_fee_type' =>
                    'none',

                'management_fee_value' =>
                    0,

                'agent_commission_amount' =>
                    0,

                'status' =>
                    'active',
            ]);

        return Payment::create([
            'lease_id' =>
                $lease->id,

            'amount' =>
                $amount,

            'payment_date' =>
                '2026-08-19',

            'payment_method' =>
                $paymentMethod,

            'reference' =>
                'PH3-S3-'
                .uniqid(),

            'collector_name' =>
                $paymentMethod === 'cash'
                    ? 'Phase 3 Step 3 Collector'
                    : null,

            'notes' =>
                null,
        ]);
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
}
