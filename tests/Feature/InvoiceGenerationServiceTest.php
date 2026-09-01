<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Services\InvoiceGenerationService;
use App\Services\LeaseTerms\LeaseExtendService;
use App\Services\LeaseTerms\LeaseTermVersionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies automated Lease billing and Invoice generation.
 */
class InvoiceGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a Lease with configurable billing settings.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createLease(
        array $overrides = []
    ): Lease {
        $building = Building::create([
            'name' => 'Invoice Generation Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Invoice Generation Tenant',
            'phone' => '0200001100',
            'email' => 'invoice-generation@example.test',
        ]);

        return Lease::create(
            array_merge([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => '2026-08-01',
                'rent_amount' => 10000,
                'payment_frequency' => 'monthly',
                'due_day' => null,
                'vat_rate' => 18,
                'proration_amount' => null,
                'security_deposit_amount' => 0,
                'management_fee_type' => 'none',
                'management_fee_value' => 0,
                'agent_commission_amount' => 0,
                'status' => 'active',
            ], $overrides)
        );
    }

    /**
     * Monthly Lease creates a one-month Invoice.
     */
    public function test_monthly_invoice_is_generated(): void
    {
        $lease = $this->createLease();

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );

        $this->assertSame(
            '2026-08-01',
            $invoice->period_start->toDateString()
        );

        $this->assertSame(
            '2026-08-31',
            $invoice->period_end->toDateString()
        );

        $this->assertSame(10000, $invoice->total_amount);
        $this->assertSame('issued', $invoice->status);
    }

    /**
     * Quarterly Lease bills three months of rent.
     */
    public function test_quarterly_invoice_uses_three_months_rent(): void
    {
        $lease = $this->createLease([
            'payment_frequency' => 'quarterly',
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );

        $this->assertSame(30000, $invoice->total_amount);

        $this->assertSame(
            '2026-10-31',
            $invoice->period_end->toDateString()
        );
    }

    /**
     * Bi-yearly Lease bills six months of rent.
     */
    public function test_bi_yearly_invoice_uses_six_months_rent(): void
    {
        $lease = $this->createLease([
            'payment_frequency' => 'bi_yearly',
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );

        $this->assertSame(60000, $invoice->total_amount);
    }

    /**
     * Yearly Lease bills twelve months of rent.
     */
    public function test_yearly_invoice_uses_twelve_months_rent(): void
    {
        $lease = $this->createLease([
            'payment_frequency' => 'yearly',
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );

        $this->assertSame(120000, $invoice->total_amount);
    }

    /**
     * Due day defaults to the Lease start-date day.
     */
    public function test_due_day_defaults_to_lease_start_day(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-08-15',
            'due_day' => null,
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-15')
            );

        $this->assertSame(
            '2026-08-15',
            $invoice->due_date->toDateString()
        );
    }

    /**
     * Explicit due-day override is respected.
     */
    public function test_due_day_override_is_used(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-08-15',
            'due_day' => 1,
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-15')
            );

        $this->assertSame(
            '2026-08-01',
            $invoice->due_date->toDateString()
        );
    }

    /**
     * Rent carries no VAT.
     *
     * The Lease VAT rate applies to the managing organisation's fee and is
     * billed to the Owner, so it must never reach the tenant Invoice: the
     * tenant is billed the contractual rent and nothing more.
     */
    public function test_rent_invoice_carries_no_vat(): void
    {
        $lease = $this->createLease([
            'rent_amount' => 11800,
            'vat_rate' => 18,
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );

        $this->assertSame('0.00', $invoice->vat_rate);
        $this->assertSame(11800, $invoice->net_amount);
        $this->assertSame(0, $invoice->vat_amount);
        $this->assertSame(11800, $invoice->total_amount);
    }

    /**
     * Explicit proration value may be zero.
     */
    public function test_explicit_zero_proration_is_preserved(): void
    {
        $lease = $this->createLease([
            'proration_amount' => 0,
        ]);

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );

        $this->assertSame(0, $invoice->proration_amount);
        $this->assertSame(0, $invoice->total_amount);
    }

    /**
     * Duplicate billing period is rejected.
     */
    public function test_duplicate_invoice_period_is_rejected(): void
    {
        $lease = $this->createLease();

        $service = app(InvoiceGenerationService::class);

        $service->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'An Invoice already exists for this Lease billing period.'
        );

        $service->generate(
            $lease,
            Carbon::parse('2026-08-01')
        );
    }

    /**
     * Draft Leases cannot be invoiced.
     */
    public function test_draft_lease_cannot_be_invoiced(): void
    {
        $lease = $this->createLease([
            'status' => 'draft',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invoices can only be generated for active or notice Leases.'
        );

        app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-08-01')
            );
    }

    /**
     * Bulk generation creates all missing periods up to a cutoff date.
     */
    public function test_due_invoices_are_generated_up_to_cutoff(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-06-01',
            'payment_frequency' => 'monthly',
        ]);

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-08-11')
            );

        $this->assertCount(3, $invoices);

        $this->assertSame(
            [
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
            ],
            Invoice::query()
                ->orderBy('period_start')
                ->get()
                ->map(
                    fn (Invoice $invoice) => $invoice->period_start->toDateString()
                )
                ->all()
        );
    }

    /**
     * Bulk generation skips periods already invoiced.
     */
    public function test_due_generation_skips_existing_periods(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-06-01',
        ]);

        $service = app(InvoiceGenerationService::class);

        $service->generate(
            $lease,
            Carbon::parse('2026-06-01')
        );

        $generated = $service->generateDueInvoices(
            $lease,
            Carbon::parse('2026-08-11')
        );

        $this->assertCount(2, $generated);

        $this->assertDatabaseCount(
            'invoices',
            3
        );
    }

    /**
     * Extending a Lease must not cause a subsequently generated historical
     * Invoice to inherit the newest contractual terms.
     */
    public function test_extend_preserves_historical_billing_terms(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-10-01',

            'end_date' => '2026-12-31',

            'rent_amount' => 11800,

            'payment_frequency' => 'monthly',

            'due_day' => 5,

            'vat_rate' => 18,
        ]);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline(
            $lease
        );

        app(
            LeaseExtendService::class
        )->extend(
            lease: $lease,
            validated: [
                'effective_from' => '2027-01-01',

                'end_date' => '2027-12-31',

                'rent_amount' => 20000,

                'payment_frequency' => 'monthly',

                'due_day' => 15,

                'vat_rate' => 0,

                'rent_increment_type' => 'none',

                'rent_increment_value' => 0,

                'next_rent_increment_date' => null,

                'notes' => '2027 terms',
            ]
        );

        $service =
            app(
                InvoiceGenerationService::class
            );

        /*
         * This Invoice is generated AFTER Extend, but belongs to the old
         * contractual period and therefore must use the old version.
         */
        $historical =
            $service->generate(
                $lease,
                Carbon::parse(
                    '2026-12-01'
                )
            );

        $current =
            $service->generate(
                $lease,
                Carbon::parse(
                    '2027-01-01'
                )
            );

        $this->assertSame(
            11800,
            $historical->total_amount
        );

        $this->assertSame(
            '0.00',
            $historical->vat_rate
        );

        $this->assertSame(
            11800,
            $historical->net_amount
        );

        $this->assertSame(
            0,
            $historical->vat_amount
        );

        $this->assertSame(
            '2026-12-05',
            $historical
                ->due_date
                ->toDateString()
        );

        $this->assertSame(
            '2026-12-31',
            $historical
                ->period_end
                ->toDateString()
        );

        $this->assertSame(
            20000,
            $current->total_amount
        );

        $this->assertSame(
            '0.00',
            $current->vat_rate
        );

        $this->assertSame(
            20000,
            $current->net_amount
        );

        $this->assertSame(
            0,
            $current->vat_amount
        );

        $this->assertSame(
            '2027-01-15',
            $current
                ->due_date
                ->toDateString()
        );

        $this->assertSame(
            '2027-01-31',
            $current
                ->period_end
                ->toDateString()
        );
    }

    /**
     * An Invoice already issued before Extend is an immutable historical
     * financial snapshot and must not be regenerated or rewritten.
     */
    public function test_extend_does_not_rewrite_existing_issued_invoice(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-10-01',

            'end_date' => '2026-12-31',

            'rent_amount' => 10000,

            'payment_frequency' => 'monthly',

            'due_day' => 1,

            'vat_rate' => 0,
        ]);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline(
            $lease
        );

        $invoice =
            app(
                InvoiceGenerationService::class
            )->generate(
                $lease,
                Carbon::parse(
                    '2026-12-01'
                )
            );

        $before = [
            'id' => $invoice->id,

            'invoice_number' => $invoice->invoice_number,

            'period_start' => $invoice
                ->period_start
                ->toDateString(),

            'period_end' => $invoice
                ->period_end
                ->toDateString(),

            'issue_date' => $invoice
                ->issue_date
                ->toDateString(),

            'due_date' => $invoice
                ->due_date
                ->toDateString(),

            'status' => $invoice->status,

            'total_amount' => $invoice->total_amount,

            'net_amount' => $invoice->net_amount,

            'vat_amount' => $invoice->vat_amount,

            'vat_rate' => $invoice->vat_rate,
        ];

        app(
            LeaseExtendService::class
        )->extend(
            lease: $lease,
            validated: [
                'effective_from' => '2027-01-01',

                'end_date' => '2027-12-31',

                'rent_amount' => 25000,

                'payment_frequency' => 'quarterly',

                'due_day' => 20,

                'vat_rate' => 18,

                'rent_increment_type' => 'none',

                'rent_increment_value' => 0,

                'next_rent_increment_date' => null,

                'notes' => 'Changed after historical Invoice',
            ]
        );

        $invoice->refresh();

        $after = [
            'id' => $invoice->id,

            'invoice_number' => $invoice->invoice_number,

            'period_start' => $invoice
                ->period_start
                ->toDateString(),

            'period_end' => $invoice
                ->period_end
                ->toDateString(),

            'issue_date' => $invoice
                ->issue_date
                ->toDateString(),

            'due_date' => $invoice
                ->due_date
                ->toDateString(),

            'status' => $invoice->status,

            'total_amount' => $invoice->total_amount,

            'net_amount' => $invoice->net_amount,

            'vat_amount' => $invoice->vat_amount,

            'vat_rate' => $invoice->vat_rate,
        ];

        $this->assertSame(
            $before,
            $after
        );
    }

    /**
     * generateDueInvoices() must cross a term-version boundary using the
     * historical cadence before Extend and the new cadence afterward.
     */
    public function test_due_generation_switches_frequency_at_extension_boundary(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-10-01',

            'end_date' => '2026-12-31',

            'rent_amount' => 10000,

            'payment_frequency' => 'monthly',

            'due_day' => 1,

            'vat_rate' => 0,
        ]);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline(
            $lease
        );

        app(
            LeaseExtendService::class
        )->extend(
            lease: $lease,
            validated: [
                'effective_from' => '2027-01-01',

                'end_date' => '2027-12-31',

                /*
                 * rent_amount remains Patrimoine's monthly base amount.
                 * Quarterly Invoice = 3 × monthly base.
                 */
                'rent_amount' => 12000,

                'payment_frequency' => 'quarterly',

                'due_day' => 10,

                'vat_rate' => 0,

                'rent_increment_type' => 'none',

                'rent_increment_value' => 0,

                'next_rent_increment_date' => null,

                'notes' => 'Quarterly from 2027',
            ]
        );

        app(
            InvoiceGenerationService::class
        )->generateDueInvoices(
            $lease,
            Carbon::parse(
                '2027-04-01'
            )
        );

        $invoices =
            Invoice::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->orderBy(
                    'period_start'
                )
                ->get();

        $this->assertCount(
            5,
            $invoices
        );

        $this->assertSame(
            [
                [
                    'start' => '2026-10-01',

                    'end' => '2026-10-31',

                    'amount' => 10000,

                    'due' => '2026-10-01',
                ],
                [
                    'start' => '2026-11-01',

                    'end' => '2026-11-30',

                    'amount' => 10000,

                    'due' => '2026-11-01',
                ],
                [
                    'start' => '2026-12-01',

                    'end' => '2026-12-31',

                    'amount' => 10000,

                    'due' => '2026-12-01',
                ],
                [
                    'start' => '2027-01-01',

                    'end' => '2027-03-31',

                    'amount' => 36000,

                    'due' => '2027-01-10',
                ],
                [
                    'start' => '2027-04-01',

                    'end' => '2027-06-30',

                    'amount' => 36000,

                    'due' => '2027-04-10',
                ],
            ],
            $invoices
                ->map(
                    fn (Invoice $invoice): array => [
                        'start' => $invoice
                            ->period_start
                            ->toDateString(),

                        'end' => $invoice
                            ->period_end
                            ->toDateString(),

                        'amount' => $invoice->total_amount,

                        'due' => $invoice
                            ->due_date
                            ->toDateString(),
                    ]
                )
                ->all()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The anniversary
    |--------------------------------------------------------------------------
    |
    | V1.0.43. Billing periods used to advance with addMonthsNoOverflow()
    | from the PREVIOUS period start, which loses the anniversary the first
    | time a month is too short to hold it and never gets it back:
    |
    |     31 Jan -> 28 Feb -> 28 Mar -> 28 Apr -> 28 May ...
    |
    | A tenancy that began on the 31st was billed on the 28th for the rest
    | of its life because February happened once.
    |
    */

    /**
     * The day a lease began is the anniversary, borrowed from and
     * returned, never lost.
     */
    public function test_a_lease_beginning_on_the_31st_keeps_the_31st(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-01-31',
            'payment_frequency' => 'monthly',
        ]);

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-07-31')
            );

        $this->assertSame(
            [
                '2026-01-31',
                '2026-02-28',
                '2026-03-31',
                '2026-04-30',
                '2026-05-31',
                '2026-06-30',
                '2026-07-31',
            ],
            collect($invoices)
                ->map(
                    fn (Invoice $invoice): string => $invoice
                        ->period_start
                        ->toDateString()
                )
                ->all(),
            'February borrows the last day it has; March takes the 31st back.'
        );
    }

    /**
     * And in a leap year February borrows the 29th.
     */
    public function test_february_borrows_the_29th_in_a_leap_year(): void
    {
        $lease = $this->createLease([
            'start_date' => '2028-01-31',
            'payment_frequency' => 'monthly',
        ]);

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2028-03-31')
            );

        $this->assertSame(
            [
                '2028-01-31',
                '2028-02-29',
                '2028-03-31',
            ],
            collect($invoices)
                ->map(
                    fn (Invoice $invoice): string => $invoice
                        ->period_start
                        ->toDateString()
                )
                ->all()
        );
    }

    /**
     * The same rule on a quarterly cadence.
     */
    public function test_a_quarterly_lease_keeps_its_anniversary(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-01-31',
            'payment_frequency' => 'quarterly',
        ]);

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2027-01-31')
            );

        $this->assertSame(
            [
                '2026-01-31',
                '2026-04-30',
                '2026-07-31',
                '2026-10-31',
                '2027-01-31',
            ],
            collect($invoices)
                ->map(
                    fn (Invoice $invoice): string => $invoice
                        ->period_start
                        ->toDateString()
                )
                ->all()
        );
    }

    /**
     * Consecutive periods still meet exactly: no day is billed twice and
     * no day falls between two periods.
     */
    public function test_periods_meet_without_a_gap_or_an_overlap(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-01-31',
            'payment_frequency' => 'monthly',
        ]);

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-06-30')
            );

        $previous = null;

        foreach ($invoices as $invoice) {
            if ($previous !== null) {
                $this->assertSame(
                    $previous->period_end->copy()->addDay()->toDateString(),
                    $invoice->period_start->toDateString(),
                    'Each period begins the day after the one before it ends.'
                );
            }

            $previous = $invoice;
        }
    }

    /**
     * A month that HAS the anniversary is unaffected: this rule only
     * changes what happens when the day does not exist.
     */
    public function test_an_ordinary_anniversary_is_untouched(): void
    {
        $lease = $this->createLease([
            'start_date' => '2026-01-15',
            'payment_frequency' => 'monthly',
        ]);

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-04-15')
            );

        $this->assertSame(
            [
                '2026-01-15',
                '2026-02-15',
                '2026-03-15',
                '2026-04-15',
            ],
            collect($invoices)
                ->map(
                    fn (Invoice $invoice): string => $invoice
                        ->period_start
                        ->toDateString()
                )
                ->all()
        );
    }
}
