<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\AccountingRuntimeGate;
use App\Services\Accounting\RentInvoiceJournalService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\InvoiceGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentInvoiceJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_runtime_accounting_is_disabled_before_cutover(): void
    {
        $this->assertFalse(
            app(
                AccountingRuntimeGate::class
            )->enabled()
        );
    }

    public function test_completed_cutover_enables_runtime_accounting(): void
    {
        $this->createCompletedCutover();

        $this->assertTrue(
            app(
                AccountingRuntimeGate::class
            )->enabled()
        );
    }

    public function test_pre_cutover_invoice_generation_does_not_post_journal(): void
    {
        $lease = $this->createLease();

        $invoice = app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->assertNotNull(
            $invoice->getKey()
        );

        $this->assertDatabaseCount(
            'invoices',
            1
        );

        $this->assertDatabaseCount(
            'accounting_cutovers',
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
    }

    public function test_post_cutover_invoice_generation_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $lease = $this->createLease([
            'rent_amount' =>
                11800,

            'vat_rate' =>
                18,
        ]);

        $invoice = app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );

        $this->assertDatabaseCount(
            'journal_lines',
            2
        );

        $entry = JournalEntry::query()
            ->with('lines')
            ->sole();

        $this->assertSame(
            AccountingEventMap::EVENT_RENT_INVOICE,
            $entry->transaction_type
        );

        $this->assertSame(
            Invoice::class,
            $entry->source_type
        );

        $this->assertSame(
            $invoice->id,
            $entry->source_id
        );

        $this->assertSame(
            RentInvoiceJournalService::idempotencyKey(
                $invoice
            ),
            $entry->idempotency_key
        );

        $this->assertSame(
            '2026-08-01',
            $entry->journal_date->toDateString()
        );

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            11800,
            (int) $entry->lines->sum(
                'debit_amount'
            )
        );

        $this->assertSame(
            11800,
            (int) $entry->lines->sum(
                'credit_amount'
            )
        );

        $debit = $entry->lines
            ->firstWhere(
                'debit_amount',
                11800
            );

        $credit = $entry->lines
            ->firstWhere(
                'credit_amount',
                11800
            );

        $this->assertNotNull($debit);
        $this->assertNotNull($credit);

        $this->assertSame(
            SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
            $debit->account_code_snapshot
        );

        $this->assertSame(
            SystemChartOfAccounts::RENT_BILLING_CLEARING,
            $credit->account_code_snapshot
        );

        $this->assertSame(
            11800,
            $entry->snapshot['total_amount']
        );

        $this->assertSame(
            10000,
            $entry->snapshot['net_amount']
        );

        $this->assertSame(
            1800,
            $entry->snapshot['vat_amount']
        );
    }

    public function test_rent_invoice_journal_posting_is_idempotent(): void
    {
        $this->createCompletedCutover();

        $lease = $this->createLease();

        $invoice = app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );

        $this->assertDatabaseCount(
            'journal_lines',
            2
        );

        app(
            RentInvoiceJournalService::class
        )->post(
            $invoice
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );

        $this->assertDatabaseCount(
            'journal_lines',
            2
        );

        $this->assertSame(
            'rent-invoice:'.$invoice->id,
            JournalEntry::query()
                ->sole()
                ->idempotency_key
        );
    }

    public function test_zero_value_rent_invoice_does_not_create_journal_entry(): void
    {
        $this->createCompletedCutover();

        $lease = $this->createLease([
            'proration_amount' =>
                0,
        ]);

        $invoice = app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->assertSame(
            0,
            $invoice->total_amount
        );

        $this->assertDatabaseCount(
            'invoices',
            1
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

    public function test_phase_3_runtime_service_does_not_initialize_cutover(): void
    {
        $lease = $this->createLease();

        app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->assertDatabaseCount(
            'accounting_cutovers',
            0
        );
    }

    public function test_rent_invoice_idempotency_key_is_stable(): void
    {
        $lease = $this->createLease();

        $invoice = app(
            InvoiceGenerationService::class
        )->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->assertSame(
            'rent-invoice:'.$invoice->id,
            RentInvoiceJournalService::idempotencyKey(
                $invoice
            )
        );
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

    /**
     * Create a real operational Lease using the same domain fixture pattern
     * as InvoiceGenerationServiceTest.
     *
     * @param array<string, mixed> $overrides
     */
    private function createLease(
        array $overrides = []
    ): Lease {
        $building = Building::create([
            'name' =>
                'Phase 3 Rent Journal Building',
        ]);

        $unit = Unit::create([
            'building_id' =>
                $building->id,

            'name' =>
                'Unit 1',
        ]);

        $tenant = Party::create([
            'type' =>
                'person',

            'name' =>
                'Phase 3 Rent Journal Tenant',

            'phone' =>
                '0200003100',

            'email' =>
                'phase3-rent-journal@example.test',
        ]);

        return Lease::create(
            array_merge(
                [
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
                        null,

                    'vat_rate' =>
                        18,

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
                ],
                $overrides
            )
        );
    }
}
