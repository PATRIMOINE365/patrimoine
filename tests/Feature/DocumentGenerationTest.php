<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\ReceiptDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies Patrimoine Invoice and Receipt PDF generation.
 */
class DocumentGenerationTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a complete Invoice and Payment document context.
     *
     * @return array{
     *     invoice: Invoice,
     *     payment: Payment
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'PDF Test Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'PDF Test Owner',
            'phone' => '0200001500',
            'email' => 'pdf-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit PDF-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'PDF Test Tenant',
            'phone' => '0200001501',
            'email' => 'pdf-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 11800,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-PDF-000001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'partial',
            'total_amount' => 11800,
            'vat_rate' => 18,
            'net_amount' => 10000,
            'vat_amount' => 1800,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 5000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'PDF-TEST-BANK-001',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 5000,
        ]);

        return compact(
            'invoice',
            'payment'
        );
    }

    /**
     * Invoice service returns a genuine PDF document.
     */
    public function test_invoice_service_generates_pdf(): void
    {
        $context = $this->createContext();

        $contents = app(
            InvoiceDocumentService::class
        )->generate(
            $context['invoice']
        );

        /*
         * Every valid PDF starts with the %PDF signature.
         */
        $this->assertStringStartsWith(
            '%PDF-',
            $contents
        );

        /*
         * Ensure this is a substantive generated document rather than an
         * empty or malformed response.
         */
        $this->assertGreaterThan(
            1000,
            strlen($contents)
        );
    }

    /**
     * Receipt service returns a genuine PDF document.
     */
    public function test_receipt_service_generates_pdf(): void
    {
        $context = $this->createContext();

        $contents = app(
            ReceiptDocumentService::class
        )->generate(
            $context['payment']
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $contents
        );

        $this->assertGreaterThan(
            1000,
            strlen($contents)
        );
    }

    /**
     * Invoice API streams a PDF with the expected filename.
     */
    public function test_invoice_pdf_can_be_downloaded_through_api(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/invoices/{$context['invoice']->id}/pdf"
        );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $this->assertStringContainsString(
            'Patrimoine-Invoice-INV-PDF-000001.pdf',
            (string) $response->headers->get(
                'Content-Disposition'
            )
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );
    }

    /**
     * Receipt API streams a PDF with a stable receipt filename.
     */
    public function test_receipt_pdf_can_be_downloaded_through_api(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/payments/{$context['payment']->id}/receipt"
        );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $expectedFilename = sprintf(
            'Patrimoine-Receipt-%06d.pdf',
            $context['payment']->id
        );

        $this->assertStringContainsString(
            $expectedFilename,
            (string) $response->headers->get(
                'Content-Disposition'
            )
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );
    }

    /**
     * Missing Invoice returns the standard API 404 response.
     */
    public function test_missing_invoice_pdf_returns_not_found(): void
    {
        $this->getJson(
            '/api/invoices/999999/pdf'
        )->assertNotFound();
    }

    /**
     * Missing Payment receipt returns the standard API 404 response.
     */
    public function test_missing_receipt_returns_not_found(): void
    {
        $this->getJson(
            '/api/payments/999999/receipt'
        )->assertNotFound();
    }
}
