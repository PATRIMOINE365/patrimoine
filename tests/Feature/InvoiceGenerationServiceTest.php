<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Services\InvoiceGenerationService;
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
     * @param array<string, mixed> $overrides
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
     * VAT is snapshotted and split from the VAT-inclusive gross amount.
     */
    public function test_vat_is_snapshotted_on_invoice(): void
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

        $this->assertSame('18.00', $invoice->vat_rate);
        $this->assertSame(10000, $invoice->net_amount);
        $this->assertSame(1800, $invoice->vat_amount);
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
                    fn (Invoice $invoice) =>
                        $invoice->period_start->toDateString()
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
}
