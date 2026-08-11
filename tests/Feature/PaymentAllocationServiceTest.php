<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Unit;
use App\Services\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\BuildingOwner;

/**
 * Verifies Patrimoine's FIFO payment-allocation rules.
 */
class PaymentAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a Lease with the minimum related records needed for testing.
     */
    private function createLease(): Lease
    {
        $building = Building::create([
            'name' => 'Payment Test Building',
        ]);

        /*
        * Every financially active Building requires complete ownership so
        * collected rent can be attributed to the correct owner accounts.
        */
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Payment Test Owner',
            'phone' => '0200000031',
            'email' => 'payment-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Payment Test Tenant',
            'phone' => '0200000030',
            'email' => 'payment-tenant@example.test',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);
    }

    /**
     * Create an issued Invoice for the supplied Lease.
     */
    private function createInvoice(
        Lease $lease,
        string $number,
        string $dueDate,
        int $amount
    ): Invoice {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $number,
            'period_start' => $dueDate,
            'period_end' => $dueDate,
            'issue_date' => $dueDate,
            'due_date' => $dueDate,
            'status' => 'issued',
            'total_amount' => $amount,
            'vat_rate' => 0,
            'net_amount' => $amount,
            'vat_amount' => 0,
        ]);
    }

    /**
     * A Payment must settle the oldest outstanding Invoice first.
     */
    public function test_payment_is_allocated_fifo(): void
    {
        $lease = $this->createLease();

        $oldest = $this->createInvoice(
            $lease,
            'INV-2026-000001',
            '2026-01-01',
            5000
        );

        $newer = $this->createInvoice(
            $lease,
            'INV-2026-000002',
            '2026-02-01',
            7000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 12000,
            'payment_date' => '2026-02-10',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-001',
        ]);

        app(PaymentAllocationService::class)->allocate($payment);

        $this->assertSame(5000, $oldest->fresh()->paidAmount());
        $this->assertSame(7000, $newer->fresh()->paidAmount());

        $this->assertSame('paid', $oldest->fresh()->status);
        $this->assertSame('paid', $newer->fresh()->status);

        $this->assertSame(12000, $payment->fresh()->allocatedAmount());
        $this->assertSame(0, $payment->fresh()->unallocatedAmount());
    }

    /**
     * A Payment smaller than an Invoice creates a partial settlement.
     */
    public function test_partial_payment_marks_invoice_partial(): void
    {
        $lease = $this->createLease();

        $invoice = $this->createInvoice(
            $lease,
            'INV-2026-000003',
            '2026-03-01',
            10000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 4000,
            'payment_date' => '2026-03-05',
            'payment_method' => 'momo',
            'reference' => 'MOMO-001',
        ]);

        app(PaymentAllocationService::class)->allocate($payment);

        $this->assertSame(4000, $invoice->fresh()->paidAmount());
        $this->assertSame(6000, $invoice->fresh()->outstandingAmount());
        $this->assertSame('partial', $invoice->fresh()->status);
    }

    /**
     * A Payment larger than all outstanding invoices leaves the excess
     * unapplied rather than inventing another Invoice allocation.
     */
    public function test_overpayment_remains_unallocated(): void
    {
        $lease = $this->createLease();

        $invoice = $this->createInvoice(
            $lease,
            'INV-2026-000004',
            '2026-04-01',
            5000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 8000,
            'payment_date' => '2026-04-01',
            'payment_method' => 'cash',
            'collector_name' => 'Test Collector',
        ]);

        app(PaymentAllocationService::class)->allocate($payment);

        $this->assertSame(5000, $invoice->fresh()->paidAmount());
        $this->assertSame('paid', $invoice->fresh()->status);

        $this->assertSame(5000, $payment->fresh()->allocatedAmount());
        $this->assertSame(3000, $payment->fresh()->unallocatedAmount());
    }

    /**
     * Cancelled invoices must never receive tenant payment allocations.
     */
    public function test_cancelled_invoice_is_skipped(): void
    {
        $lease = $this->createLease();

        $cancelled = $this->createInvoice(
            $lease,
            'INV-2026-000005',
            '2026-01-01',
            5000
        );

        $cancelled->update([
            'status' => 'cancelled',
        ]);

        $active = $this->createInvoice(
            $lease,
            'INV-2026-000006',
            '2026-02-01',
            5000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 5000,
            'payment_date' => '2026-02-01',
            'payment_method' => 'bank_transfer',
        ]);

        app(PaymentAllocationService::class)->allocate($payment);

        $this->assertSame(0, $cancelled->fresh()->paidAmount());
        $this->assertSame(5000, $active->fresh()->paidAmount());
    }
}
