<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\SecurityDepositDeduction;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\OwnerDepositReceiptDocumentService;
use App\Services\Documents\ReceiptDocumentService;
use App\Services\Documents\SecurityDepositVoucherDocumentService;
use App\Services\SecurityDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies Patrimoine financial document generation.
 *
 * Covered documents:
 *
 * - Tenant Invoices;
 * - Tenant Payment receipts;
 * - Owner Deposit receipts.
 *
 * Incoming tenant and owner money remain separate accounting records,
 * but both must produce auditable customer-facing receipt documents.
 */
class DocumentGenerationTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a complete Invoice and tenant Payment document context.
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
     * Build an Owner Deposit that does not depend on any active Lease.
     *
     * This is important because an owner may fund repairs or other property
     * costs while a Building is vacant and after all previously held owner
     * funds have already been paid out.
     *
     * @return array{
     *     owner: Party,
     *     account: OwnerAccount,
     *     building: Building,
     *     unit: Unit,
     *     deposit: OwnerTransaction
     * }
     */
    private function createOwnerDepositContext(): array
    {
        $building = Building::create([
            'name' => 'Owner Deposit Receipt Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Owner Deposit Receipt Owner',
            'phone' => '0200001550',
            'email' => 'owner-deposit-receipt@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        /*
         * BuildingOwner creation automatically provisions the owner's
         * consolidated financial account.
         */
        $account = OwnerAccount::query()
            ->where(
                'party_id',
                $owner->id
            )
            ->firstOrFail();

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit OD-1',
        ]);

        /*
         * No Lease is created deliberately.
         *
         * Owner deposits are valid independently of tenancy and rent
         * collection activity.
         */
        $deposit = OwnerTransaction::create([
            'owner_account_id' => $account->id,

            'building_id' => $building->id,

            'unit_id' => $unit->id,

            'direction' => 'credit',

            'category' => 'owner_deposit',

            'amount' => 7500,

            'transaction_date' => '2026-08-12',

            'payment_method' => 'bank_transfer',

            'deposit_purpose' => 'repair_maintenance',

            'reference' => 'OWNER-DEP-PDF-001',

            'notes' => 'Funds supplied for vacant-unit repairs.',
        ]);

        return compact(
            'owner',
            'account',
            'building',
            'unit',
            'deposit'
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
     * Tenant Payment receipt service returns a genuine PDF document.
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
     * Owner Deposit receipt service returns a genuine PDF document.
     *
     * The deposit is intentionally unrelated to a Lease.
     */
    public function test_owner_deposit_receipt_service_generates_pdf(): void
    {
        $context =
            $this->createOwnerDepositContext();

        $contents = app(
            OwnerDepositReceiptDocumentService::class
        )->generate(
            $context['deposit']
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
     * Tenant Payment receipt API streams a PDF with a stable filename.
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
     * Owner Deposit receipt API streams a genuine PDF.
     *
     * This protects the receipt action shown beside owner deposits in the
     * unified Payments register.
     */
    public function test_owner_deposit_receipt_can_be_downloaded_through_api(): void
    {
        $context =
            $this->createOwnerDepositContext();

        $response = $this->get(
            "/api/owner-deposits/{$context['deposit']->id}/receipt"
        );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );

        /*
         * Keep the filename assertion flexible enough to allow the service
         * to use its own owner-specific receipt naming convention while still
         * ensuring the response is an inline PDF document.
         */
        $this->assertStringContainsString(
            '.pdf',
            (string) $response->headers->get(
                'Content-Disposition'
            )
        );
    }

    /**
     * An arbitrary owner-ledger transaction must not be presented as an
     * Owner Deposit receipt.
     *
     * Only actual incoming owner money with category owner_deposit qualifies
     * for this customer-facing receipt document.
     */
    public function test_non_deposit_owner_transaction_cannot_generate_receipt(): void
    {
        $context =
            $this->createOwnerDepositContext();

        $expense = OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,

            'building_id' => $context['building']->id,

            'unit_id' => $context['unit']->id,

            'direction' => 'debit',

            'category' => 'expense',

            'amount' => 2000,

            'transaction_date' => '2026-08-12',

            'reference' => 'EXP-NOT-RECEIPT-001',

            'notes' => 'This accounting debit is not incoming owner money.',
        ]);

        $this->getJson(
            "/api/owner-deposits/{$expense->id}/receipt"
        )
            ->assertUnprocessable();
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
     * Missing tenant Payment receipt returns the standard API 404 response.
     */
    public function test_missing_receipt_returns_not_found(): void
    {
        $this->getJson(
            '/api/payments/999999/receipt'
        )->assertNotFound();
    }

    /**
     * Missing Owner Deposit transaction returns the standard API 404 response.
     */
    public function test_missing_owner_deposit_receipt_returns_not_found(): void
    {
        $this->getJson(
            '/api/owner-deposits/999999/receipt'
        )->assertNotFound();
    }

    /**
     * Build a finalized Security Deposit settlement for document testing.
     */
    private function createSecurityDepositSettlementContext(): SecurityDepositSettlement
    {
        $building = Building::create([
            'name' => 'Security Deposit Voucher Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit SDV-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Security Deposit Voucher Tenant',
            'phone' => '0200001560',
            'email' => 'security-voucher@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'security_deposit_amount' => 10000,
            'status' => 'terminated',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'deposit_funding',
            'amount' => 10000,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDepositDeduction::create([
            'lease_id' => $lease->id,
            'description' => 'Damaged lock',
            'amount' => 3000,
            'deduction_date' => '2026-08-10',
            'reference' => 'INSPECTION-SDV-001',
        ]);

        return app(
            SecurityDepositService::class
        )->settle(
            $lease,
            '2026-08-11',
            'Final move-out settlement.'
        );
    }

    /**
     * Security Deposit settlement service generates a genuine PDF voucher.
     */
    public function test_security_deposit_voucher_service_generates_pdf(): void
    {
        $settlement =
            $this->createSecurityDepositSettlementContext();

        $contents =
            app(
                SecurityDepositVoucherDocumentService::class
            )->generate(
                $settlement
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
     * Security Deposit voucher can be downloaded through the API.
     */
    public function test_security_deposit_voucher_can_be_downloaded_through_api(): void
    {
        $settlement =
            $this->createSecurityDepositSettlementContext();

        $response =
            $this->get(
                "/api/security-deposit-settlements/{$settlement->id}/voucher"
            );

        $response
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $this->assertStringContainsString(
            'Patrimoine-Security-Deposit-Voucher-'.
                $settlement->refund_voucher_number.
                '.pdf',
            (string) $response
                ->headers
                ->get(
                    'Content-Disposition'
                )
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $response->getContent()
        );
    }

    /**
     * Missing Security Deposit settlement voucher returns standard 404.
     */
    public function test_missing_security_deposit_voucher_returns_not_found(): void
    {
        $this->getJson(
            '/api/security-deposit-settlements/999999/voucher'
        )->assertNotFound();
    }

    /**
     * Owner deposit receipt validation failures follow French.
     */
    public function test_owner_deposit_receipt_error_renders_in_french(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $context = $this->createOwnerDepositContext();

        $transaction = $context['deposit'];

        $transaction->update([
            'category' => 'adjustment',
        ]);

        $this
            ->getJson(
                "/api/owner-deposits/{$transaction->id}/receipt"
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.owner_transaction.0',
                'Seuls les dépôts de propriétaire peuvent générer un reçu de dépôt de propriétaire.'
            );
    }
}
