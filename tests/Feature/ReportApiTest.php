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
 * Verifies the formal reporting API surface.
 */
class ReportApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create a financially valid reporting context.
     *
     * @return array<string, mixed>
     */
    private function createContext(): array
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'API Report Owner',
            'phone' => '0200020001',
            'email' => 'api-report-owner@example.test',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'API Report Tenant',
            'phone' => '0200020002',
            'email' => 'api-report-tenant@example.test',
        ]);

        $building = Building::create([
            'name' => 'API Report Building',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit API-1',
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
            'invoice_number' => 'INV-API-REPORT-001',
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
            'reference' => 'API-REPORT-PAY-001',
        ]);

        $allocation = PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 6000,
        ]);

        /*
        * Building ownership provisions the OwnerAccount automatically.
        * Reporting fixtures must use the domain-created account rather than
        * attempting to create a duplicate account for the same owner Party.
        */
        $ownerAccount = $owner
            ->ownerAccount()
            ->firstOrFail();

        OwnerTransaction::create([
            'owner_account_id' => $ownerAccount->id,
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
            'unit',
            'lease',
            'invoice',
            'payment'
        );
    }

    public function test_owner_report_can_be_retrieved(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/owners/{$context['owner']->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'owner.id',
                $context['owner']->id
            )
            ->assertJsonPath(
                'summary.rent_entitlement',
                6000
            )
            ->assertJsonPath(
                'summary.closing_balance',
                6000
            );
    }

    public function test_building_report_can_be_retrieved(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/buildings/{$context['building']->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'building.id',
                $context['building']->id
            )
            ->assertJsonPath(
                'summary.invoiced',
                10000
            )
            ->assertJsonPath(
                'summary.cash_received',
                6000
            )
            ->assertJsonPath(
                'summary.outstanding',
                4000
            );
    }

    public function test_unit_report_can_be_retrieved(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/units/{$context['unit']->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'unit.id',
                $context['unit']->id
            )
            ->assertJsonPath(
                'summary.invoiced',
                10000
            )
            ->assertJsonPath(
                'summary.settled',
                6000
            );
    }

public function test_tenant_statement_can_be_retrieved(): void
{
    $context = $this->createContext();

    Invoice::create([
        'lease_id' => $context['lease']->id,
        'invoice_number' => 'SDD-API-TENANT-001',
        'type' => 'security_deposit_debt',
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-10',
        'issue_date' => '2026-08-10',
        'due_date' => '2026-08-10',
        'status' => 'issued',
        'total_amount' => 2000,
        'vat_rate' => 0,
        'net_amount' => 2000,
        'vat_amount' => 0,
    ]);

    $this->getJson(
        "/api/reports/tenants/{$context['tenant']->id}"
    )
        ->assertOk()
        ->assertJsonPath(
            'tenant.id',
            $context['tenant']->id
        )
        ->assertJsonPath(
            'summary.invoiced',
            12000
        )
        ->assertJsonPath(
            'summary.cash_received',
            6000
        )
        ->assertJsonPath(
            'summary.rent_outstanding',
            4000
        )
        ->assertJsonPath(
            'summary.security_deposit_debt_outstanding',
            2000
        )
        ->assertJsonPath(
            'summary.total_outstanding',
            6000
        )
        ->assertJsonPath(
            'invoices.1.type',
            'security_deposit_debt'
        );
}

 public function test_managing_organisation_report_can_be_retrieved(): void
{
    $context = $this->createContext();

    Invoice::create([
        'lease_id' => $context['lease']->id,
        'invoice_number' => 'SDD-API-MGT-001',
        'type' => 'security_deposit_debt',
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-10',
        'issue_date' => '2026-08-10',
        'due_date' => '2026-08-10',
        'status' => 'issued',
        'total_amount' => 2000,
        'vat_rate' => 0,
        'net_amount' => 2000,
        'vat_amount' => 0,
    ]);

    $this->getJson(
        '/api/reports/managing-organisation'
    )
        ->assertOk()
        ->assertJsonPath(
            'portfolio.buildings',
            1
        )
        ->assertJsonPath(
            'portfolio.units',
            1
        )
        ->assertJsonPath(
            'billing.invoiced',
            12000
        )
        ->assertJsonPath(
            'billing.rent_invoiced',
            10000
        )
        ->assertJsonPath(
            'billing.security_deposit_debt_invoiced',
            2000
        )
        ->assertJsonPath(
            'billing.rent_outstanding',
            4000
        )
        ->assertJsonPath(
            'billing.security_deposit_debt_outstanding',
            2000
        )
        ->assertJsonPath(
            'billing.total_outstanding',
            6000
        );
}

    public function test_report_date_range_is_applied(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/buildings/{$context['building']->id}"
            . '?from=2026-08-06&to=2026-08-31'
        )
            ->assertOk()
            ->assertJsonPath(
                'summary.invoiced',
                0
            )
            ->assertJsonPath(
                'summary.cash_received',
                0
            );
    }

    public function test_report_rejects_invalid_date(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/owners/{$context['owner']->id}"
            . '?from=not-a-date'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'from',
            ]);
    }

    public function test_report_rejects_reversed_date_range(): void
    {
        $context = $this->createContext();

        $this->getJson(
            "/api/reports/units/{$context['unit']->id}"
            . '?from=2026-08-31&to=2026-08-01'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to',
            ]);
    }
}
