<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerExpense;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Reports\BuildingReportService;
use App\Services\Reports\ManagingOrganisationReportService;
use App\Services\Reports\OwnerReportService;
use App\Services\Reports\TenantStatementService;
use App\Services\Reports\UnitReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function createContext(): array
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Report Owner',
            'phone' => '0200010001',
            'email' => 'report-owner@example.test',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Report Tenant',
            'phone' => '0200010002',
            'email' => 'report-tenant@example.test',
        ]);

        $building = Building::create([
            'name' => 'Report Building',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit R1',
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
            'invoice_number' => 'INV-REPORT-001',
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
            'reference' => 'REPORT-PAY-001',
        ]);

        $allocation = PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 6000,
        ]);

        /*
        * The BuildingOwner relationship provisions the owner's consolidated
        * OwnerAccount. Reporting uses that existing account for all ledger
        * transactions associated with this owner.
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

        OwnerTransaction::create([
            'owner_account_id' => $ownerAccount->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'direction' => 'debit',
            'category' => 'management_fee',
            'amount' => 600,
            'transaction_date' => '2026-08-05',
        ]);

        $expense = OwnerExpense::create([
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'description' => 'Air conditioner repair',
            'amount' => 1000,
            'expense_date' => '2026-08-06',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $ownerAccount->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 1000,
            'transaction_date' => '2026-08-06',
        ]);

        $reserve = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $reserve->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 3000,
            'transaction_date' => '2026-08-05',
        ]);

        return compact(
            'owner',
            'tenant',
            'building',
            'unit',
            'lease',
            'invoice',
            'payment',
            'ownerAccount',
            'expense',
            'reserve'
        );
        return compact(
    'owner',
    'tenant',
    'building',
    'unit',
    'lease',
    'invoice',
    'payment',
    'ownerAccount',
    'expense',
    'reserve'
);
    }

    /**
 * Create a Security Deposit close-out debt Invoice for a Lease.
 *
 * This receivable is deliberately separate from contractual rent and
 * must therefore be reported independently from rent balances.
 */
private function createSecurityDepositDebtInvoice(
    Lease $lease,
    int $amount = 2000
): Invoice {
    return Invoice::create([
        'lease_id' => $lease->id,
        'invoice_number' => 'SDD-REPORT-001',
        'type' => 'security_deposit_debt',
        'period_start' => '2026-08-10',
        'period_end' => '2026-08-10',
        'issue_date' => '2026-08-10',
        'due_date' => '2026-08-10',
        'status' => 'issued',
        'total_amount' => $amount,
        'vat_rate' => 0,
        'net_amount' => $amount,
        'vat_amount' => 0,
    ]);
}

    public function test_owner_report_uses_owner_ledger(): void
    {
        $context = $this->createContext();

        $report = app(OwnerReportService::class)
            ->generate($context['owner']);

        $this->assertSame(
            6000,
            $report['summary']['rent_entitlement']
        );

        $this->assertSame(
            600,
            $report['summary']['management_fees']
        );

        $this->assertSame(
            1000,
            $report['summary']['expenses']
        );

        $this->assertSame(
            4400,
            $report['summary']['closing_balance']
        );
    }

    public function test_building_report_separates_billing_cash_and_expenses(): void
    {
        $context = $this->createContext();

        $report = app(BuildingReportService::class)
            ->generate($context['building']);

        $this->assertSame(
            10000,
            $report['summary']['invoiced']
        );

        $this->assertSame(
            6000,
            $report['summary']['cash_received']
        );

        $this->assertSame(
            4000,
            $report['summary']['outstanding']
        );

        $this->assertSame(
            1000,
            $report['summary']['property_expenses']
        );
    }

    public function test_unit_report_returns_lease_and_financial_history(): void
    {
        $context = $this->createContext();

        $report = app(UnitReportService::class)
            ->generate($context['unit']);

        $this->assertSame(
            1,
            $report['summary']['leases']
        );

        $this->assertSame(
            10000,
            $report['summary']['invoiced']
        );

        $this->assertSame(
            6000,
            $report['summary']['settled']
        );

        $this->assertSame(
            4000,
            $report['summary']['outstanding']
        );
    }

    public function test_tenant_statement_includes_funds_and_outstanding_rent(): void
    {
        $context = $this->createContext();

        $report = app(TenantStatementService::class)
            ->generate($context['tenant']);

        $this->assertSame(
            10000,
            $report['summary']['invoiced']
        );

        $this->assertSame(
            6000,
            $report['summary']['settled']
        );

        $this->assertSame(
            4000,
            $report['summary']['outstanding']
        );

        $this->assertSame(
            3000,
            $report['summary']['rent_reserve_balance']
        );
    }

    public function test_managing_organisation_report_summarizes_portfolio(): void
    {
        $this->createContext();

        $report = app(
            ManagingOrganisationReportService::class
        )->generate();

        $this->assertSame(
            1,
            $report['portfolio']['buildings']
        );

        $this->assertSame(
            1,
            $report['portfolio']['units']
        );

        $this->assertSame(
            10000,
            $report['billing']['invoiced']
        );

        $this->assertSame(
            6000,
            $report['billing']['cash_received']
        );

        $this->assertSame(
            600,
            $report['owner_accounting']['management_fees']
        );

        $this->assertSame(
            3000,
            $report['tenant_funds']['rent_reserve']
        );
    }

    public function test_report_date_range_filters_financial_activity(): void
    {
        $context = $this->createContext();

        $report = app(OwnerReportService::class)
            ->generate(
                $context['owner'],
                '2026-08-06',
                '2026-08-31'
            );

        /*
         * GHS 6,000 credit and GHS 600 management fee occurred before
         * the selected period and therefore become the opening balance.
         */
        $this->assertSame(
            5400,
            $report['summary']['opening_balance']
        );

        $this->assertSame(
            1000,
            $report['summary']['expenses']
        );

        $this->assertSame(
            4400,
            $report['summary']['closing_balance']
        );
    }
    /**
 * Tenant Statement must distinguish rent from Security Deposit debt.
 */
public function test_tenant_statement_separates_receivable_types(): void
{
    $context = $this->createContext();

    $debtInvoice = $this->createSecurityDepositDebtInvoice(
        $context['lease']
    );

    $report = app(TenantStatementService::class)
        ->generate($context['tenant']);

    /*
     * Existing generic totals remain the complete tenant receivable
     * position for backward compatibility.
     */
    $this->assertSame(
        12000,
        $report['summary']['invoiced']
    );

    $this->assertSame(
        6000,
        $report['summary']['outstanding']
    );

    /*
     * V1.0.1 explicitly separates contractual rent from Security Deposit
     * close-out debt.
     */
    $this->assertSame(
        4000,
        $report['summary']['rent_outstanding']
    );

    $this->assertSame(
        2000,
        $report['summary']['security_deposit_debt_outstanding']
    );

    $this->assertSame(
        6000,
        $report['summary']['total_outstanding']
    );

    $debtRow = collect($report['invoices'])
        ->firstWhere('id', $debtInvoice->id);

    $this->assertNotNull($debtRow);

    $this->assertSame(
        'security_deposit_debt',
        $debtRow['type']
    );
}

/**
 * Managing Organisation report must show separate receivable categories.
 */
public function test_managing_organisation_report_separates_receivable_types(): void
{
    $context = $this->createContext();

    $this->createSecurityDepositDebtInvoice(
        $context['lease']
    );

    $report = app(
        ManagingOrganisationReportService::class
    )->generate();

    $this->assertSame(
        12000,
        $report['billing']['invoiced']
    );

    $this->assertSame(
        10000,
        $report['billing']['rent_invoiced']
    );

    $this->assertSame(
        2000,
        $report['billing']['security_deposit_debt_invoiced']
    );

    $this->assertSame(
        4000,
        $report['billing']['rent_outstanding']
    );

    $this->assertSame(
        2000,
        $report['billing']['security_deposit_debt_outstanding']
    );

    $this->assertSame(
        6000,
        $report['billing']['total_outstanding']
    );
}

/**
 * Building report must show separate receivable categories.
 */
public function test_building_report_separates_receivable_types(): void
{
    $context = $this->createContext();

    $this->createSecurityDepositDebtInvoice(
        $context['lease']
    );

    $report = app(BuildingReportService::class)
        ->generate($context['building']);

    $this->assertSame(
        12000,
        $report['summary']['invoiced']
    );

    $this->assertSame(
        10000,
        $report['summary']['rent_invoiced']
    );

    $this->assertSame(
        2000,
        $report['summary']['security_deposit_debt_invoiced']
    );

    $this->assertSame(
        4000,
        $report['summary']['rent_outstanding']
    );

    $this->assertSame(
        2000,
        $report['summary']['security_deposit_debt_outstanding']
    );

    $this->assertSame(
        6000,
        $report['summary']['total_outstanding']
    );
}

/**
 * Unit report must distinguish rent from Security Deposit debt and expose
 * the Invoice type in its financial history.
 */
public function test_unit_report_separates_receivable_types(): void
{
    $context = $this->createContext();

    $debtInvoice = $this->createSecurityDepositDebtInvoice(
        $context['lease']
    );

    $report = app(UnitReportService::class)
        ->generate($context['unit']);

    $this->assertSame(
        12000,
        $report['summary']['invoiced']
    );

    $this->assertSame(
        10000,
        $report['summary']['rent_invoiced']
    );

    $this->assertSame(
        2000,
        $report['summary']['security_deposit_debt_invoiced']
    );

    $this->assertSame(
        4000,
        $report['summary']['rent_outstanding']
    );

    $this->assertSame(
        2000,
        $report['summary']['security_deposit_debt_outstanding']
    );

    $this->assertSame(
        6000,
        $report['summary']['total_outstanding']
    );

    $debtRow = collect($report['invoices'])
        ->firstWhere('id', $debtInvoice->id);

    $this->assertNotNull($debtRow);

    $this->assertSame(
        'security_deposit_debt',
        $debtRow['type']
    );
}
}
