<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies formal report PDF and CSV exports.
 */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * @return array<string, mixed>
     */
    private function createContext(): array
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Export Owner',
            'phone' => '0200030001',
            'email' => 'export-owner@example.test',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Export Tenant',
            'phone' => '0200030002',
            'email' => 'export-tenant@example.test',
        ]);

        $building = Building::create([
            'name' => 'Export Building',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Export Unit',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-EXPORT-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'partial',
            'total_amount' => 10000,
            'vat_rate' => 0,
            'net_amount' => 10000,
            'vat_amount' => 0,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 6000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
        ]);

        $allocation = PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 6000,
        ]);

        $account = OwnerAccount::create([
            'party_id' => $owner->id,
            'status' => 'active',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'lease_id' => $lease->id,
            'invoice_id' => $invoice->id,
            'payment_allocation_id' => $allocation->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 6000,
            'transaction_date' => '2026-08-05',
        ]);

        return compact(
            'owner',
            'tenant',
            'building',
            'unit'
        );
    }

    public function test_owner_report_pdf_can_be_downloaded(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/reports/owners/{$context['owner']->id}/pdf"
        );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $this->assertStringStartsWith(
            '%PDF',
            $response->getContent()
        );
    }

    public function test_building_report_csv_can_be_downloaded(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/reports/buildings/{$context['building']->id}/csv"
        );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            );

        $this->assertStringContainsString(
            'Invoiced',
            $response->getContent()
        );

        $this->assertStringContainsString(
            '10000',
            $response->getContent()
        );
    }

    public function test_unit_report_pdf_can_be_downloaded(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/reports/units/{$context['unit']->id}/pdf"
        );

        $response->assertOk();

        $this->assertStringStartsWith(
            '%PDF',
            $response->getContent()
        );
    }

    public function test_tenant_statement_csv_can_be_downloaded(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/reports/tenants/{$context['tenant']->id}/csv"
        );

        $response->assertOk();

        $this->assertStringContainsString(
            'Cash Received',
            $response->getContent()
        );

        $this->assertStringContainsString(
            '6000',
            $response->getContent()
        );
    }

    public function test_managing_organisation_pdf_can_be_downloaded(): void
    {
        $this->createContext();

        $response = $this->get(
            '/api/reports/managing-organisation/pdf'
        );

        $response->assertOk();

        $this->assertStringStartsWith(
            '%PDF',
            $response->getContent()
        );
    }

    public function test_export_accepts_report_date_range(): void
    {
        $context = $this->createContext();

        $response = $this->get(
            "/api/reports/owners/{$context['owner']->id}/csv"
            . '?from=2026-08-06&to=2026-08-31'
        );

        $response->assertOk();

        /*
         * The GHS 6,000 owner credit occurred before the selected period.
         * It should therefore appear as the report opening balance.
         */
        $this->assertStringContainsString(
            'Opening Balance',
            $response->getContent()
        );

        $this->assertStringContainsString(
            '6000',
            $response->getContent()
        );
    }

    public function test_export_rejects_invalid_date_range(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/buildings/{$context['building']->id}/pdf"
            . '?from=2026-09-01&to=2026-08-01'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to',
            ]);
    }
}
