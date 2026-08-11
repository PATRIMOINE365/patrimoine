<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the Patrimoine invoice domain rules.
 *
 * These tests protect the separation between contractual billing
 * and later payment/allocation logic.
 */
class InvoiceDomainTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the minimum records required to test invoice behavior.
     *
     * @return array{lease: Lease}
     */
    private function createLeaseContext(): array
    {
        $building = Building::create([
            'name' => 'Invoice Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Invoice Test Tenant',
            'phone' => '0200000020',
            'email' => 'invoice-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 11800,
            'vat_rate' => 18.00,
            'status' => 'active',
        ]);

        return [
            'lease' => $lease,
        ];
    }

    /**
     * An Invoice belongs to exactly one Lease and is visible through
     * the inverse Lease relationship.
     */
    public function test_invoice_belongs_to_lease(): void
    {
        $context = $this->createLeaseContext();

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-2026-000001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 11800,
            'vat_rate' => 18.00,
            'net_amount' => 10000,
            'vat_amount' => 1800,
        ]);

        $this->assertSame(
            $context['lease']->id,
            $invoice->lease->id
        );

        $this->assertTrue(
            $context['lease']->fresh()->invoices->contains($invoice)
        );
    }

    /**
     * VAT information is copied into the Invoice and remains an
     * independent historical snapshot.
     *
     * Changing the Lease VAT later must not alter an existing Invoice.
     */
    public function test_invoice_preserves_historical_vat_snapshot(): void
    {
        $context = $this->createLeaseContext();

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-2026-000002',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 11800,
            'vat_rate' => 18.00,
            'net_amount' => 10000,
            'vat_amount' => 1800,
        ]);

        /*
         * Simulate a future contractual VAT-rate change.
         */
        $context['lease']->update([
            'vat_rate' => 0,
        ]);

        $this->assertSame('0.00', $context['lease']->fresh()->vat_rate);

        /*
         * The already-issued Invoice must retain the original value.
         */
        $this->assertSame('18.00', $invoice->fresh()->vat_rate);
        $this->assertSame(1800, $invoice->fresh()->vat_amount);
    }

    /**
     * Invoice amounts are stored as whole currency units.
     */
    public function test_invoice_preserves_integer_money_values(): void
    {
        $context = $this->createLeaseContext();

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-2026-000003',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 11800,
            'vat_rate' => 18.00,
            'net_amount' => 10000,
            'vat_amount' => 1800,
            'proration_amount' => 0,
        ]);

        $this->assertSame(11800, $invoice->total_amount);
        $this->assertSame(10000, $invoice->net_amount);
        $this->assertSame(1800, $invoice->vat_amount);
        $this->assertSame(0, $invoice->proration_amount);
    }
}
