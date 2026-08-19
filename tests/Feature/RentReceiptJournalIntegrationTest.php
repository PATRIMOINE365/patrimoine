<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\RentReceiptJournalService;
use App\Services\OwnerAccountingService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\InvoiceGenerationService;
use App\Services\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RentReceiptJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();

        /*
         * These Phase 3 Step 2 tests isolate the persisted
         * PaymentAllocation → Financial Journal boundary.
         *
         * PaymentAllocationService also invokes the pre-existing Owner
         * Accounting pipeline for collected rent. Ownership allocation,
         * Owner entitlement and Management Fee behaviour already have
         * dedicated regression coverage and are deliberately not part of
         * this Journal integration fixture.
         */
        $ownerAccounting =
            Mockery::mock(
                OwnerAccountingService::class
            );

        $ownerAccounting
            ->shouldReceive(
                'postCollectedRentEntitlement'
            )
            ->zeroOrMoreTimes()
            ->andReturn([]);

        $ownerAccounting
            ->shouldReceive(
                'postManagementFee'
            )
            ->zeroOrMoreTimes()
            ->andReturn([]);

        $this->app->instance(
            OwnerAccountingService::class,
            $ownerAccounting
        );
    }

    public function test_pre_cutover_rent_allocation_does_not_post_journal(): void
    {
        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario();

        $before =
            JournalEntry::count();

        $this->allocate(
            $payment
        );

        $this->assertSame(
            $before,
            JournalEntry::count()
        );
    }

    public function test_post_cutover_rent_allocation_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario();

        $this->allocate(
            $payment
        );

        $entry =
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RECEIPT
                )
                ->latest('id')
                ->first();

        $this->assertNotNull(
            $entry
        );

        $entry->load(
            'lines'
        );

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            $invoice->id,
            $entry->snapshot[
                'invoice_id'
            ] ?? null
        );

        $this->assertSame(
            $payment->id,
            $entry->snapshot[
                'payment_id'
            ] ?? null
        );

        $this->assertSame(
            (int) $entry->lines->sum(
                'debit_amount'
            ),
            (int) $entry->lines->sum(
                'credit_amount'
            )
        );
    }

    public function test_rent_receipt_debits_payment_asset_and_credits_rent_receivable(): void
    {
        $this->createCompletedCutover();

        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario();

        $this->allocate(
            $payment
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RECEIPT
                )
                ->latest('id')
                ->firstOrFail();

        $asset =
            app(
                AccountingEventMap::class
            )->paymentAsset(
                (string) $payment->getAttribute(
                    'payment_method'
                )
            );

        $mapping =
            app(
                AccountingEventMap::class
            )->contextual(
                AccountingEventMap::
                    EVENT_RENT_RECEIPT
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
                            > 0
                )
        );

        $this->assertTrue(
            $entry->lines
                ->contains(
                    fn ($line): bool =>
                        $line
                            ->account_code_snapshot
                            === $mapping['credit']
                        && $line
                            ->credit_amount
                            > 0
                )
        );
    }

    public function test_only_allocated_rent_amount_is_journaled(): void
    {
        $this->createCompletedCutover();

        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario(
                paymentAmount:
                    1500,
                invoiceAmount:
                    1000
            );

        $this->allocate(
            $payment
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RECEIPT
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            1000,
            (int) $entry
                ->lines
                ->sum(
                    'debit_amount'
                )
        );

        $this->assertSame(
            1000,
            (int) $entry
                ->lines
                ->sum(
                    'credit_amount'
                )
        );
    }

    public function test_security_deposit_debt_allocation_is_not_rent_receipt(): void
    {
        $this->createCompletedCutover();

        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario();

        $invoice->forceFill([
            'type' =>
                'security_deposit_debt',
        ])->save();

        $this->allocate(
            $payment
        );

        $this->assertSame(
            0,
            JournalEntry::query()
                ->where(
                    'transaction_type',
                    AccountingEventMap::
                        EVENT_RENT_RECEIPT
                )
                ->count()
        );
    }

    public function test_rent_receipt_posting_is_idempotent_by_allocation(): void
    {
        $this->createCompletedCutover();

        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario();

        $this->allocate(
            $payment
        );

        $allocation =
            PaymentAllocation::query()
                ->firstOrFail();

        $service =
            app(
                RentReceiptJournalService::class
            );

        $service->postAllocation(
            $allocation
        );

        $service->postAllocation(
            $allocation
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where(
                    'idempotency_key',
                    RentReceiptJournalService::
                        idempotencyKey(
                            $allocation
                        )
                )
                ->count()
        );
    }

    public function test_runtime_payment_integration_does_not_initialize_cutover(): void
    {
        [
            $payment,
            $invoice,
        ] =
            $this->createPaymentScenario();

        $this->allocate(
            $payment
        );

        $this->assertSame(
            0,
            AccountingCutover::count()
        );
    }

    private function createCompletedCutover(): void
    {
        AccountingCutover::query()
            ->create([
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

    /**
     * @return array{0: Payment, 1: Invoice}
     */
    private function createPaymentScenario(
        int $paymentAmount = 1000,
        int $invoiceAmount = 1000
    ): array {
        $building =
            Building::create([
                'name' =>
                    'Phase 3 Step 2 Building',
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
                    'Phase 3 Step 2 Tenant',

                'phone' =>
                    '0200003200',

                'email' =>
                    'phase3-step2@example.test',
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
                    $invoiceAmount,

                'payment_frequency' =>
                    'monthly',

                'due_day' =>
                    null,

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
            app(
                InvoiceGenerationService::class
            )->generate(
                $lease,
                \Carbon\Carbon::parse(
                    '2026-08-01'
                )
            );

        /*
         * Tests create Payment directly so this suite exercises the same
         * persisted Payment object consumed by PaymentAllocationService
         * without coupling to HTTP validation.
         */
        $payment =
            new Payment();

        $columns =
            collect(
                \Schema::getColumnListing(
                    $payment->getTable()
                )
            );

        $attributes = [];

        $values = [
            'lease_id' =>
                $lease->id,

            'amount' =>
                $paymentAmount,

            'total_amount' =>
                $paymentAmount,

            'payment_method' =>
                'bank_transfer',

            'payment_date' =>
                '2026-08-19',

            'status' =>
                'completed',

            'reference' =>
                'PH3-S2-'.uniqid(),

            'collector' =>
                null,

            'collector_name' =>
                null,

            'notes' =>
                null,
        ];

        foreach (
            $values as $column => $value
        ) {
            if (
                $columns->contains(
                    $column
                )
            ) {
                $attributes[$column] =
                    $value;
            }
        }

        $payment->forceFill(
            $attributes
        );

        $payment->save();

        return [
            $payment,
            $invoice,
        ];
    }

    private function allocate(
        Payment $payment
    ): void {
        $service =
            app(
                PaymentAllocationService::class
            );

        /*
         * Adapt to the existing public allocation API. Patrimoine currently
         * has one operational PaymentAllocationService entry point; this
         * reflection avoids hard-coding only its parameter spelling while
         * still executing the real service.
         */
        $method =
            collect(
                (new \ReflectionClass(
                    $service
                ))->getMethods(
                    \ReflectionMethod::IS_PUBLIC
                )
            )
                ->first(
                    fn (
                        \ReflectionMethod $method
                    ): bool =>
                        $method->getName()
                        !== '__construct'
                );

        $this->assertNotNull(
            $method,
            'PaymentAllocationService has no public allocation method.'
        );

        $parameters =
            $method->getParameters();

        if (
            count(
                $parameters
            ) === 1
        ) {
            $method->invoke(
                $service,
                $payment
            );

            return;
        }

        throw new \RuntimeException(
            'Phase 3 Step 2 test harness expected PaymentAllocationService '
            .'to expose one Payment argument. Inspect the service before '
            .'changing production accounting integration.'
        );
    }
}
