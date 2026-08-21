<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Services\InvoiceGenerationService;
use App\Services\LeaseTermination\LeaseTerminationInvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LeaseTerminationInvoiceSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function createLease(
        string $mode = 'prorate',
        array $overrides = []
    ): Lease {
        $building = Building::create([
            'name' => 'Termination Billing Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Termination Billing Tenant',
            'phone' => '0200009000',
            'email' => uniqid('termination-', true).'@example.test',
        ]);

        return Lease::create(
            array_merge([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => '2026-06-01',
                'rent_amount' => 3100,
                'payment_frequency' => 'monthly',
                'due_day' => 1,
                'vat_rate' => 0,
                'proration_amount' => null,
                'security_deposit_amount' => 0,
                'management_fee_type' => 'none',
                'management_fee_value' => 0,
                'agent_commission_amount' => 0,
                'status' => 'notice',
                'termination_notice_date' => '2026-07-15',
                'termination_date' => '2026-08-15',
                'termination_final_rent_mode' => $mode,
                'termination_previous_status' => 'active',
            ], $overrides)
        );
    }

    public function test_prorate_mode_generates_only_through_termination_period(): void
    {
        $lease = $this->createLease('prorate');

        $invoices = app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-12-31')
            );

        $this->assertCount(3, $invoices);

        $this->assertSame(
            [
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
            ],
            Invoice::query()
                ->where('lease_id', $lease->id)
                ->orderBy('period_start')
                ->get()
                ->map(
                    fn (Invoice $invoice): string => $invoice->period_start->toDateString()
                )
                ->all()
        );

        $final = Invoice::query()
            ->where('lease_id', $lease->id)
            ->whereDate('period_start', '2026-08-01')
            ->firstOrFail();

        // August contains 31 days; termination through 15 August is inclusive.
        $this->assertSame(1500, $final->total_amount);
        $this->assertSame(1500, $final->proration_amount);
    }

    public function test_full_mode_keeps_complete_final_period(): void
    {
        $lease = $this->createLease('full');

        app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-12-31')
            );

        $final = Invoice::query()
            ->where('lease_id', $lease->id)
            ->whereDate('period_start', '2026-08-01')
            ->firstOrFail();

        $this->assertSame(3100, $final->total_amount);
        $this->assertNull($final->proration_amount);

        $this->assertDatabaseMissing('invoices', [
            'lease_id' => $lease->id,
            'period_start' => '2026-09-01',
        ]);
    }

    public function test_none_mode_creates_zero_value_final_period(): void
    {
        $lease = $this->createLease('none');

        app(InvoiceGenerationService::class)
            ->generateDueInvoices(
                $lease,
                Carbon::parse('2026-12-31')
            );

        $final = Invoice::query()
            ->where('lease_id', $lease->id)
            ->whereDate('period_start', '2026-08-01')
            ->firstOrFail();

        $this->assertSame(0, $final->total_amount);
        $this->assertSame(0, $final->proration_amount);

        $this->assertDatabaseMissing('invoices', [
            'lease_id' => $lease->id,
            'period_start' => '2026-09-01',
        ]);
    }

    public function test_direct_generation_after_termination_is_rejected(): void
    {
        $lease = $this->createLease('full');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Billing period falls after the Lease termination date.'
        );

        app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-09-01')
            );
    }

    public function test_real_payment_allocation_marks_invoice_as_touched(): void
    {
        $lease = $this->createLease('full');

        $invoice = app(InvoiceGenerationService::class)
            ->generate(
                $lease,
                Carbon::parse('2026-06-01')
            );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'payment_date' => '2026-06-02',
            'amount' => 1000,
            'payment_method' => 'cash',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);

        $this->assertTrue(
            app(LeaseTerminationInvoiceService::class)
                ->hasFinancialActivity(
                    $invoice->fresh()
                )
        );
    }

    public function test_untouched_future_invoice_is_cancelled(): void
    {
        $lease = $this->createLease('full');

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-TERM-FUTURE',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-01',
            'status' => 'issued',
            'total_amount' => 3100,
            'vat_rate' => 0,
            'net_amount' => 3100,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        app(LeaseTerminationInvoiceService::class)
            ->reconcile($lease);

        $this->assertSame(
            'cancelled',
            $invoice->fresh()->status
        );
    }

    public function test_financially_touched_future_invoice_is_preserved(): void
    {
        $lease = $this->createLease('full');

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-TERM-TOUCHED',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-01',
            'status' => 'issued',
            'total_amount' => 3100,
            'vat_rate' => 0,
            'net_amount' => 3100,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'payment_date' => '2026-08-10',
            'amount' => 1000,
            'payment_method' => 'cash',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);

        app(LeaseTerminationInvoiceService::class)
            ->reconcile($lease);

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );

        $this->assertSame(
            1000,
            $invoice->fresh()->paymentPaidAmount()
        );
    }

    public function test_reconcile_is_idempotent_for_cancelled_future_invoice(): void
    {
        $lease = $this->createLease('full');

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'type' => 'rent',
            'invoice_number' => 'INV-TERM-IDEMPOTENT',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-01',
            'status' => 'issued',
            'total_amount' => 3100,
            'vat_rate' => 0,
            'net_amount' => 3100,
            'vat_amount' => 0,
            'proration_amount' => null,
        ]);

        $service = app(
            LeaseTerminationInvoiceService::class
        );

        $service->reconcile($lease);
        $service->reconcile($lease);

        $this->assertSame(
            'cancelled',
            $invoice->fresh()->status
        );
    }
}
