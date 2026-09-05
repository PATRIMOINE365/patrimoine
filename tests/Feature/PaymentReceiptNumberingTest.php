<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Unit;
use App\Services\Documents\ReceiptDocumentService;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * V1.0.50: a receipt is numbered by its organisation, from the shared
 * counter, like every other document — not from the payment's
 * installation-wide database id.
 */
class PaymentReceiptNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_are_numbered_per_organisation_from_one(): void
    {
        $lease = $this->lease();

        $first = $this->payment($lease);
        $second = $this->payment($lease);

        $year = now()->year;

        $this->assertSame(
            sprintf('RCT-%04d-000001', $year),
            $first->receipt_number
        );

        $this->assertSame(
            sprintf('RCT-%04d-000002', $year),
            $second->receipt_number
        );

        /*
         * Another organisation starts from one as well: nothing about
         * this organisation's volume shows through.
         */
        $other = Organisation::factory()->create();

        $theirs = OrganisationContext::runAs(
            (int) $other->id,
            fn (): Payment => $this->payment($this->lease())
        );

        $this->assertSame(
            sprintf('RCT-%04d-000001', $year),
            $theirs->receipt_number
        );

        $this->assertNotSame($theirs->id, $first->id);
    }

    public function test_the_receipt_number_is_what_the_document_carries(): void
    {
        $payment = $this->payment($this->lease());

        $this->assertSame(
            'Patrimoine-Receipt-'.$payment->receipt_number.'.pdf',
            app(ReceiptDocumentService::class)->filename($payment)
        );

        $this->assertSame(
            $payment->receipt_number,
            $payment->receiptNumber()
        );
    }

    public function test_a_payment_recorded_before_the_counter_keeps_its_old_number(): void
    {
        $payment = $this->payment($this->lease());

        /*
         * What the migration writes onto every pre-existing row.
         */
        $payment->forceFill([
            'receipt_number' => sprintf('RCT-%06d', $payment->id),
        ])->save();

        $this->assertSame(
            sprintf('RCT-%06d', $payment->id),
            $payment->fresh()->receiptNumber()
        );

        /*
         * And a row with no number at all still renders in the old shape
         * rather than failing.
         */
        $payment->forceFill(['receipt_number' => null])->save();

        $this->assertSame(
            sprintf('RCT-%06d', $payment->id),
            $payment->fresh()->receiptNumber()
        );
    }

    private function lease(): Lease
    {
        $building = Building::create([
            'name' => 'Receipt Numbering Building '.uniqid(),
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Receipt Numbering Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Receipt Numbering Tenant',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'status' => 'active',
        ]);
    }

    private function payment(Lease $lease): Payment
    {
        return Payment::create([
            'lease_id' => $lease->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);
    }
}
