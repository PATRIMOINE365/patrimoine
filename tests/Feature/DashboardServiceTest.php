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
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the operational and financial calculations used by the
 * Patrimoine dashboard.
 */
class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a basic property, tenant, owner and Lease context.
     *
     * @return array{
     *     building: Building,
     *     unit: Unit,
     *     tenant: Party,
     *     owner: Party,
     *     lease: Lease
     * }
     */
    private function createPropertyContext(
        string $leaseStatus = 'active',
        ?string $endDate = null
    ): array {
        $building = Building::create([
            'name' => 'Dashboard Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Dashboard Owner',
            'phone' => '0200000110',
            'email' => 'dashboard-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Dashboard Tenant',
            'phone' => '0200000111',
            'email' => 'dashboard-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'end_date' => $endDate,
            'rent_amount' => 10000,
            'status' => $leaseStatus,
        ]);

        return compact(
            'building',
            'unit',
            'tenant',
            'owner',
            'lease'
        );
    }

    /**
     * Dashboard returns the correct Building and Unit totals.
     */
    public function test_dashboard_counts_buildings_and_units(): void
    {
        $firstBuilding = Building::create([
            'name' => 'Building A',
        ]);

        $secondBuilding = Building::create([
            'name' => 'Building B',
        ]);

        Unit::create([
            'building_id' => $firstBuilding->id,
            'name' => 'A1',
        ]);

        Unit::create([
            'building_id' => $firstBuilding->id,
            'name' => 'A2',
        ]);

        Unit::create([
            'building_id' => $secondBuilding->id,
            'name' => 'B1',
        ]);

        $metrics = app(DashboardService::class)->metrics(
            Carbon::parse('2026-08-11')
        );

        $this->assertSame(2, $metrics['total_buildings']);
        $this->assertSame(3, $metrics['total_units']);
    }

    /**
     * Occupancy is derived from current Lease state rather than stored on
     * the Unit itself.
     */
    public function test_dashboard_derives_occupied_and_vacant_units(): void
    {
        $occupied = $this->createPropertyContext();

        $secondBuilding = Building::create([
            'name' => 'Vacant Building',
        ]);

        Unit::create([
            'building_id' => $secondBuilding->id,
            'name' => 'Vacant Unit',
        ]);

        $service = app(DashboardService::class);

        $asOfDate = Carbon::parse('2026-08-11');

        $this->assertSame(
            1,
            $service->occupiedUnitCount($asOfDate)
        );

        $this->assertSame(
            1,
            $service->vacantUnitCount($asOfDate)
        );

        $this->assertSame(
            $occupied['unit']->id,
            Unit::query()
                ->whereHas('leases', function ($query) {
                    $query->where('status', 'active');
                })
                ->firstOrFail()
                ->id
        );
    }

    /**
     * Terminated Leases do not make a Unit occupied.
     */
    public function test_terminated_lease_does_not_create_occupancy(): void
    {
        $this->createPropertyContext(
            leaseStatus: 'terminated',
            endDate: '2026-07-31'
        );

        $service = app(DashboardService::class);

        $this->assertSame(
            0,
            $service->occupiedUnitCount(
                Carbon::parse('2026-08-11')
            )
        );

        $this->assertSame(
            1,
            $service->vacantUnitCount(
                Carbon::parse('2026-08-11')
            )
        );
    }

    /**
     * Rent due uses only the outstanding portion of unpaid invoices.
     */
    public function test_dashboard_calculates_rent_due_from_outstanding_amounts(): void
    {
        $context = $this->createPropertyContext();

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'partial',
            'total_amount' => 10000,
            'vat_rate' => 0,
            'net_amount' => 10000,
            'vat_amount' => 0,
        ]);

        $payment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 4000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 4000,
        ]);

        $service = app(DashboardService::class);

        $this->assertSame(
            6000,
            $service->rentDueAmount(
                Carbon::parse('2026-08-11')
            )
        );
    }

    /**
     * An Invoice is overdue only after its contractual due date.
     */
    public function test_dashboard_distinguishes_due_from_overdue(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-002',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-11',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $service = app(DashboardService::class);

        $asOfDate = Carbon::parse('2026-08-11');

        $this->assertSame(
            5000,
            $service->rentDueAmount($asOfDate)
        );

        $this->assertSame(
            0,
            $service->rentOverdueAmount($asOfDate)
        );

        $this->assertSame(
            5000,
            $service->rentOverdueAmount(
                Carbon::parse('2026-08-12')
            )
        );
    }

    /**
     * Rent collected this month is based on Payment money actually
     * allocated to rent Invoices.
     */
    public function test_dashboard_reports_current_month_cash_collections(): void
    {
        $context = $this->createPropertyContext();

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-COLLECT',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'partial',
            'total_amount' => 20000,
            'vat_rate' => 0,
            'net_amount' => 20000,
            'vat_amount' => 0,
        ]);

        $firstPayment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 4000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
        ]);

        PaymentAllocation::create([
            'payment_id' => $firstPayment->id,
            'invoice_id' => $invoice->id,
            'amount' => 4000,
        ]);

        $secondPayment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 3000,
            'payment_date' => '2026-08-10',
            'payment_method' => 'momo',
        ]);

        PaymentAllocation::create([
            'payment_id' => $secondPayment->id,
            'invoice_id' => $invoice->id,
            'amount' => 3000,
        ]);

        /*
         * Previous month and therefore excluded.
         */
        $julyPayment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 9000,
            'payment_date' => '2026-07-31',
            'payment_method' => 'cash',
            'collector_name' => 'Test Collector',
        ]);

        PaymentAllocation::create([
            'payment_id' => $julyPayment->id,
            'invoice_id' => $invoice->id,
            'amount' => 9000,
        ]);

        $metrics = app(DashboardService::class)->metrics(
            Carbon::parse('2026-08-11')
        );

        $this->assertSame(
            7000,
            $metrics['rent_collected_this_month']
        );
    }

    /**
     * V1.0.9: expense-invoice settlements and unallocated fund top-ups
     * travel through the payments table too, but they are not rent and
     * must stay out of the collections metric and trend.
     */
    public function test_rent_collections_exclude_expense_settlements_and_top_ups(): void
    {
        $context = $this->createPropertyContext();

        $rentInvoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-RENT-COLLECT',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'paid',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $rentPayment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
        ]);

        PaymentAllocation::create([
            'payment_id' => $rentPayment->id,
            'invoice_id' => $rentInvoice->id,
            'amount' => 5000,
        ]);

        /*
         * Expense-invoice settlement — must not count as rent collected.
         */
        $expenseInvoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'EXP-DASH-001',
            'type' => 'expense',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'paid',
            'total_amount' => 2000,
            'vat_rate' => 0,
            'net_amount' => 2000,
            'vat_amount' => 0,
        ]);

        $expensePayment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 2000,
            'payment_date' => '2026-08-06',
            'payment_method' => 'cash',
            'collector_name' => 'Test Collector',
        ]);

        PaymentAllocation::create([
            'payment_id' => $expensePayment->id,
            'invoice_id' => $expenseInvoice->id,
            'amount' => 2000,
        ]);

        /*
         * Unallocated fund top-up — also not rent collected.
         */
        Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 3000,
            'payment_date' => '2026-08-07',
            'payment_method' => 'momo',
        ]);

        $service = app(DashboardService::class);

        $asOfDate = Carbon::parse('2026-08-11');

        $metrics = $service->metrics($asOfDate);

        $this->assertSame(
            5000,
            $metrics['rent_collected_this_month']
        );

        $trend = collect(
            $service->collectionsTrend($asOfDate)
        );

        $this->assertSame(
            5000,
            $trend->firstWhere('month', '2026-08')['amount']
        );

        /*
         * V1.0.43: the trend is the whole of the year being read, January
         * to December, rather than the trailing six months. It used to
         * say something different every month and never once showed a
         * year, which is the one thing a collections trend is read for.
         */
        $this->assertCount(12, $trend);

        $this->assertSame(
            '2026-01',
            $trend->first()['month']
        );

        $this->assertSame(
            '2026-12',
            $trend->last()['month']
        );

        /*
         * Months still to come sit at zero until they arrive.
         */
        $this->assertSame(
            0,
            $trend->firstWhere('month', '2026-12')['amount']
        );
    }

    /**
     * Owner Funds Held includes only positive owner balances.
     */
    public function test_dashboard_sums_only_positive_owner_balances(): void
    {
        $firstOwner = Party::create([
            'type' => 'person',
            'name' => 'Positive Owner',
            'phone' => '0200000120',
            'email' => 'positive-owner@example.test',
        ]);

        $secondOwner = Party::create([
            'type' => 'person',
            'name' => 'Negative Owner',
            'phone' => '0200000121',
            'email' => 'negative-owner@example.test',
        ]);

        $firstAccount = OwnerAccount::create([
            'party_id' => $firstOwner->id,
        ]);

        $secondAccount = OwnerAccount::create([
            'party_id' => $secondOwner->id,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $firstAccount->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $secondAccount->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 3000,
            'transaction_date' => '2026-08-01',
        ]);

        $this->assertSame(
            10000,
            app(DashboardService::class)->ownerFundsHeld()
        );
    }

    /**
     * Dashboard reports management fees recognized during the current month.
     */
    public function test_dashboard_reports_current_month_management_fees(): void
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Fee Owner',
            'phone' => '0200000130',
            'email' => 'fee-owner@example.test',
        ]);

        $account = OwnerAccount::create([
            'party_id' => $owner->id,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'management_fee',
            'amount' => 1500,
            'transaction_date' => '2026-08-05',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'management_fee',
            'amount' => 500,
            'transaction_date' => '2026-07-31',
        ]);

        $metrics = app(DashboardService::class)->metrics(
            Carbon::parse('2026-08-11')
        );

        $this->assertSame(
            1500,
            $metrics['management_fees_this_month']
        );
    }

    /**
     * Dashboard exposes overdue invoices in chronological order.
     */
    public function test_dashboard_returns_overdue_invoices(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-OLD',
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-05',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-NEW',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-05',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $invoices = app(DashboardService::class)
            ->overdueInvoices(
                Carbon::parse('2026-08-11')
            );

        $this->assertCount(2, $invoices);
        $this->assertSame(
            'INV-DASH-OLD',
            $invoices->first()->invoice_number
        );
    }

    /**
     * Dashboard exposes obligations becoming due soon.
     */
    public function test_dashboard_returns_upcoming_invoices(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-UPCOMING',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-15',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        /*
         * Outside the seven-day window and therefore excluded.
         */
        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-LATER',
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-25',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $invoices = app(DashboardService::class)
            ->upcomingInvoices(
                Carbon::parse('2026-08-11'),
                7
            );

        $this->assertCount(1, $invoices);

        $this->assertSame(
            'INV-DASH-UPCOMING',
            $invoices->first()->invoice_number
        );
    }

    /**
     * Security Deposit debt must not inflate the Dashboard Rent Due metric.
     */
    public function test_dashboard_rent_due_excludes_security_deposit_debt(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-RENT-DUE',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'SDD-DASH-DUE-001',
            'type' => 'security_deposit_debt',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-01',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'issued',
            'total_amount' => 3000,
            'vat_rate' => 0,
            'net_amount' => 3000,
            'vat_amount' => 0,
        ]);

        $this->assertSame(
            5000,
            app(DashboardService::class)->rentDueAmount(
                Carbon::parse('2026-08-11')
            )
        );
    }

    /**
     * Security Deposit debt must not inflate the Dashboard Rent Overdue metric.
     */
    public function test_dashboard_rent_overdue_excludes_security_deposit_debt(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-RENT-OVERDUE',
            'type' => 'rent',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-05',
            'status' => 'issued',
            'total_amount' => 4000,
            'vat_rate' => 0,
            'net_amount' => 4000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'SDD-DASH-OVERDUE-001',
            'type' => 'security_deposit_debt',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-01',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-05',
            'status' => 'issued',
            'total_amount' => 2500,
            'vat_rate' => 0,
            'net_amount' => 2500,
            'vat_amount' => 0,
        ]);

        $this->assertSame(
            4000,
            app(DashboardService::class)->rentOverdueAmount(
                Carbon::parse('2026-08-11')
            )
        );
    }

    /**
     * Dashboard overdue rent list must contain rent invoices only.
     */
    public function test_dashboard_overdue_list_excludes_security_deposit_debt(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-OVERDUE-RENT-ONLY',
            'type' => 'rent',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-05',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'SDD-DASH-OVERDUE-LIST',
            'type' => 'security_deposit_debt',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-01',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-05',
            'status' => 'issued',
            'total_amount' => 2000,
            'vat_rate' => 0,
            'net_amount' => 2000,
            'vat_amount' => 0,
        ]);

        $invoices = app(DashboardService::class)->overdueInvoices(
            Carbon::parse('2026-08-11')
        );

        $this->assertCount(1, $invoices);
        $this->assertSame(
            'INV-DASH-OVERDUE-RENT-ONLY',
            $invoices->first()->invoice_number
        );
    }

    /**
     * Dashboard upcoming rent list must contain rent invoices only.
     */
    public function test_dashboard_upcoming_list_excludes_security_deposit_debt(): void
    {
        $context = $this->createPropertyContext();

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-DASH-UPCOMING-RENT-ONLY',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-15',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'SDD-DASH-UPCOMING-LIST',
            'type' => 'security_deposit_debt',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-01',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-15',
            'status' => 'issued',
            'total_amount' => 2000,
            'vat_rate' => 0,
            'net_amount' => 2000,
            'vat_amount' => 0,
        ]);

        $invoices = app(DashboardService::class)->upcomingInvoices(
            Carbon::parse('2026-08-11'),
            7
        );

        $this->assertCount(1, $invoices);
        $this->assertSame(
            'INV-DASH-UPCOMING-RENT-ONLY',
            $invoices->first()->invoice_number
        );
    }
}
