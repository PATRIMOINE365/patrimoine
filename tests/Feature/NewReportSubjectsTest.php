<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.7 report subjects: Occupancy, Arrears Aging and Funds Held.
 */
class NewReportSubjectsTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    /**
     * Deterministic snapshot date used by the aging assertions.
     */
    private const AS_OF = '2026-08-21';

    public function test_report_subject_endpoints_require_authentication(): void
    {
        foreach (
            [
                '/api/reports/occupancy',
                '/api/reports/arrears',
                '/api/reports/funds',
            ] as $endpoint
        ) {
            $this->getJson(
                $endpoint
            )->assertUnauthorized();
        }
    }

    public function test_occupancy_report_returns_snapshot_totals_and_building_table(): void
    {
        $this->createReportSubjectsFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/occupancy?as_of='
                .self::AS_OF
            )
            ->assertOk()
            ->assertJsonPath('as_of', self::AS_OF)
            ->assertJsonPath('totals.units', 4)
            ->assertJsonPath('totals.occupied', 2)
            ->assertJsonPath('totals.vacant', 2)
            ->assertJsonPath('totals.occupancy_rate', 50)
            ->assertJsonPath('classification.commercial.units', 1)
            ->assertJsonPath('classification.commercial.occupied', 1)
            ->assertJsonPath('classification.commercial.vacant', 0)
            ->assertJsonPath('classification.residential.units', 3)
            ->assertJsonPath('classification.residential.occupied', 1)
            ->assertJsonPath('classification.residential.vacant', 2)
            ->assertJsonPath('buildings.0.name', 'Report Building A')
            ->assertJsonPath('buildings.0.units', 2)
            ->assertJsonPath('buildings.0.occupied', 2)
            ->assertJsonPath('buildings.0.vacant', 0)
            ->assertJsonPath('buildings.0.occupancy_rate', 100)
            ->assertJsonPath('buildings.0.commercial_units', 1)
            ->assertJsonPath('buildings.1.name', 'Report Building B')
            ->assertJsonPath('buildings.1.units', 2)
            ->assertJsonPath('buildings.1.occupied', 0)
            ->assertJsonPath('buildings.1.occupancy_rate', 0)
            ->assertJsonPath('buildings.1.commercial_units', 0);
    }

    public function test_occupancy_report_honours_the_as_of_date(): void
    {
        $this->createReportSubjectsFixture();

        $this->authenticateApiUser();

        /*
         * The commercial lease only starts on 2026-02-01, so an earlier
         * snapshot must count that unit as vacant.
         */
        $this
            ->getJson(
                '/api/reports/occupancy?as_of=2026-01-15'
            )
            ->assertOk()
            ->assertJsonPath('totals.occupied', 1)
            ->assertJsonPath('totals.vacant', 3)
            ->assertJsonPath('classification.commercial.occupied', 0);
    }

    public function test_occupancy_report_rejects_invalid_as_of_date(): void
    {
        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/occupancy?as_of=21-08-2026'
            )
            ->assertUnprocessable();
    }

    public function test_arrears_report_buckets_outstanding_amounts_by_days_overdue(): void
    {
        $context =
            $this->createReportSubjectsFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/arrears?as_of='
                .self::AS_OF
            )
            ->assertOk()
            ->assertJsonPath('as_of', self::AS_OF)
            ->assertJsonPath('totals.current', 5000)
            ->assertJsonPath('totals.days_31_60', 6000)
            ->assertJsonPath('totals.days_61_90', 0)
            ->assertJsonPath('totals.over_90', 3000)
            ->assertJsonPath('totals.total', 14000)
            ->assertJsonPath('totals.invoice_count', 3)
            ->assertJsonPath('tenants.0.tenant.id', $context['tenantOne']->id)
            ->assertJsonPath('tenants.0.tenant.name', 'Report Tenant One')
            ->assertJsonPath('tenants.0.lease.id', $context['leaseOne']->id)
            ->assertJsonPath('tenants.0.building.name', 'Report Building A')
            ->assertJsonPath('tenants.0.unit.name', 'Unit A1')
            ->assertJsonPath('tenants.0.invoice_count', 2)
            ->assertJsonPath('tenants.0.current', 5000)
            ->assertJsonPath('tenants.0.days_31_60', 6000)
            ->assertJsonPath('tenants.0.over_90', 0)
            ->assertJsonPath('tenants.0.total', 11000)
            ->assertJsonPath('tenants.1.tenant.id', $context['tenantTwo']->id)
            ->assertJsonPath('tenants.1.invoice_count', 1)
            ->assertJsonPath('tenants.1.over_90', 3000)
            ->assertJsonPath('tenants.1.total', 3000);
    }

    public function test_funds_report_returns_tenant_and_owner_funds_held(): void
    {
        $context =
            $this->createReportSubjectsFixture();

        $this->authenticateApiUser();

        $this
            ->getJson(
                '/api/reports/funds'
            )
            ->assertOk()
            ->assertJsonPath(
                'tenant_funds.summary.rent_reserve.account_count',
                1
            )
            ->assertJsonPath(
                'tenant_funds.summary.rent_reserve.total_held',
                8000
            )
            ->assertJsonPath(
                'tenant_funds.summary.consumable_advance.account_count',
                1
            )
            ->assertJsonPath(
                'tenant_funds.summary.consumable_advance.total_held',
                5000
            )
            ->assertJsonPath(
                'tenant_funds.summary.security_deposit.account_count',
                2
            )
            ->assertJsonPath(
                'tenant_funds.summary.security_deposit.total_held',
                35000
            )
            ->assertJsonPath(
                'tenant_funds.summary.total_held',
                48000
            )
            ->assertJsonPath(
                'tenant_funds.tenants.0.tenant.id',
                $context['tenantOne']->id
            )
            ->assertJsonPath(
                'tenant_funds.tenants.0.rent_reserve',
                8000
            )
            ->assertJsonPath(
                'tenant_funds.tenants.0.consumable_advance',
                5000
            )
            ->assertJsonPath(
                'tenant_funds.tenants.0.security_deposit',
                20000
            )
            ->assertJsonPath(
                'tenant_funds.tenants.0.total',
                33000
            )
            ->assertJsonPath(
                'tenant_funds.tenants.1.tenant.id',
                $context['tenantTwo']->id
            )
            ->assertJsonPath(
                'tenant_funds.tenants.1.total',
                15000
            )
            ->assertJsonPath(
                'owner_funds.summary.account_count',
                1
            )
            ->assertJsonPath(
                'owner_funds.summary.total_held',
                40000
            )
            ->assertJsonPath(
                'owner_funds.owners.0.owner.id',
                $context['ownerOne']->id
            )
            ->assertJsonPath(
                'owner_funds.owners.0.owner.name',
                'Report Owner One'
            )
            ->assertJsonPath(
                'owner_funds.owners.0.balance',
                40000
            );
    }

    public function test_viewer_can_access_report_subject_data_endpoints(): void
    {
        $this->createReportSubjectsFixture();

        $this->authenticateApiUser('viewer');

        foreach (
            [
                '/api/reports/occupancy',
                '/api/reports/arrears',
                '/api/reports/funds',
            ] as $endpoint
        ) {
            $this->getJson(
                $endpoint
            )->assertOk();
        }
    }

    public function test_report_subject_exports_return_expected_content_types(): void
    {
        $this->createReportSubjectsFixture();

        /*
         * The Viewer role carries export_reports, so it must be able to
         * download every representation.
         */
        $this->authenticateApiUser('viewer');

        $expectations = [
            'pdf' => 'application/pdf',

            'csv' => 'text/csv; charset=UTF-8',

            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        foreach (
            [
                'occupancy',
                'arrears',
                'funds',
            ] as $subject
        ) {
            foreach ($expectations as $format => $contentType) {
                $this
                    ->get(
                        '/api/reports/'
                        .$subject
                        .'/'
                        .$format
                    )
                    ->assertOk()
                    ->assertHeader(
                        'Content-Type',
                        $contentType
                    );
            }
        }
    }

    public function test_report_subject_exports_require_authentication(): void
    {
        foreach (
            [
                'occupancy',
                'arrears',
                'funds',
            ] as $subject
        ) {
            $this->getJson(
                '/api/reports/'
                .$subject
                .'/pdf'
            )->assertUnauthorized();
        }
    }

    /**
     * Seed a small but complete reporting portfolio:
     *
     * - 2 buildings, 4 units (one commercial);
     * - 2 active leases (residential + commercial);
     * - rent invoices with varied due dates and one partial payment;
     * - funded tenant accounts across all three fund types;
     * - one owner holding a positive balance and one owing money.
     *
     * @return array<string, mixed>
     */
    private function createReportSubjectsFixture(): array
    {
        $buildingA =
            Building::create([
                'name' => 'Report Building A',
            ]);

        $buildingB =
            Building::create([
                'name' => 'Report Building B',
            ]);

        $unitA1 =
            Unit::create([
                'building_id' => $buildingA->id,
                'name' => 'Unit A1',
                'is_commercial' => false,
            ]);

        $unitA2 =
            Unit::create([
                'building_id' => $buildingA->id,
                'name' => 'Unit A2',
                'is_commercial' => true,
            ]);

        Unit::create([
            'building_id' => $buildingB->id,
            'name' => 'Unit B1',
            'is_commercial' => false,
        ]);

        Unit::create([
            'building_id' => $buildingB->id,
            'name' => 'Unit B2',
            'is_commercial' => false,
        ]);

        $tenantOne =
            Party::create([
                'type' => 'organisation',
                'name' => 'Report Tenant One',
                'legal_name' => 'Report Tenant One',
                'phone' => '0200098001',
                'email' => 'report-tenant-one@example.test',
            ]);

        PartyRole::create([
            'party_id' => $tenantOne->id,
            'role' => 'tenant',
        ]);

        $tenantTwo =
            Party::create([
                'type' => 'organisation',
                'name' => 'Report Tenant Two',
                'legal_name' => 'Report Tenant Two',
                'phone' => '0200098002',
                'email' => 'report-tenant-two@example.test',
            ]);

        PartyRole::create([
            'party_id' => $tenantTwo->id,
            'role' => 'tenant',
        ]);

        $leaseOne =
            Lease::create([
                'unit_id' => $unitA1->id,
                'tenant_id' => $tenantOne->id,
                'start_date' => '2026-01-01',
                'rent_amount' => 5000,
                'status' => 'active',
            ]);

        $leaseTwo =
            Lease::create([
                'unit_id' => $unitA2->id,
                'tenant_id' => $tenantTwo->id,
                'start_date' => '2026-02-01',
                'rent_amount' => 3000,
                'status' => 'active',
            ]);

        /*
         * Invoice due 5 days before the snapshot: current (0-30) bucket.
         */
        $this->createRentInvoice(
            $leaseOne,
            'INV-RS-0001',
            '2026-08-16',
            5000,
            'issued'
        );

        /*
         * Invoice due 45 days before the snapshot with a partial payment
         * of 4,000: 6,000 outstanding in the 31-60 bucket.
         */
        $partialInvoice =
            $this->createRentInvoice(
                $leaseOne,
                'INV-RS-0002',
                '2026-07-07',
                10000,
                'partial'
            );

        $partialPayment =
            Payment::create([
                'lease_id' => $leaseOne->id,
                'amount' => 4000,
                'payment_date' => '2026-07-10',
                'payment_method' => 'cash',
                'cash_receiver_name' => 'Report Receiver',
                'reference' => 'REPORT-PAY-001',
            ]);

        PaymentAllocation::create([
            'payment_id' => $partialPayment->id,
            'invoice_id' => $partialInvoice->id,
            'amount' => 4000,
        ]);

        /*
         * Invoice due 100 days before the snapshot: over-90 bucket.
         */
        $this->createRentInvoice(
            $leaseTwo,
            'INV-RS-0003',
            '2026-05-13',
            3000,
            'issued'
        );

        /*
         * Fully settled and not-yet-due invoices never appear in arrears.
         */
        $this->createRentInvoice(
            $leaseOne,
            'INV-RS-0004',
            '2026-08-01',
            5000,
            'paid'
        );

        $this->createRentInvoice(
            $leaseOne,
            'INV-RS-0005',
            '2026-09-10',
            5000,
            'issued'
        );

        /*
         * Tenant funds: reserve 10,000 - 2,000, advance 5,000 and
         * deposits of 20,000 and 15,000.
         */
        $this->createFundedAccount(
            $leaseOne,
            'rent_reserve',
            [
                ['credit', 'reserve_funding', 10000],
                ['debit', 'rent_consumption', 2000],
            ]
        );

        $this->createFundedAccount(
            $leaseOne,
            'consumable_advance',
            [
                ['credit', 'advance_funding', 5000],
            ]
        );

        $this->createFundedAccount(
            $leaseOne,
            'security_deposit',
            [
                ['credit', 'deposit_funding', 20000],
            ]
        );

        $this->createFundedAccount(
            $leaseTwo,
            'security_deposit',
            [
                ['credit', 'deposit_funding', 15000],
            ]
        );

        /*
         * Owner funds: one positive balance of 40,000 held and one
         * negative balance that must never appear in the report.
         */
        $ownerOne =
            $this->createOwnerWithBalance(
                'Report Owner One',
                'report-owner-one@example.test',
                [
                    ['credit', 'owner_deposit', 50000],
                    ['debit', 'payout', 10000],
                ]
            );

        $ownerTwo =
            $this->createOwnerWithBalance(
                'Report Owner Two',
                'report-owner-two@example.test',
                [
                    ['credit', 'owner_deposit', 1000],
                    ['debit', 'expense', 3000],
                ]
            );

        return compact(
            'buildingA',
            'buildingB',
            'unitA1',
            'unitA2',
            'tenantOne',
            'tenantTwo',
            'leaseOne',
            'leaseTwo',
            'ownerOne',
            'ownerTwo'
        );
    }

    private function createRentInvoice(
        Lease $lease,
        string $number,
        string $dueDate,
        int $total,
        string $status
    ): Invoice {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $number,
            'type' => 'rent',
            'period_start' => $dueDate,
            'period_end' => $dueDate,
            'issue_date' => $dueDate,
            'due_date' => $dueDate,
            'status' => $status,
            'total_amount' => $total,
            'vat_rate' => 0,
            'net_amount' => $total,
            'vat_amount' => 0,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int}>  $movements
     */
    private function createFundedAccount(
        Lease $lease,
        string $type,
        array $movements
    ): TenantFundAccount {
        $account =
            TenantFundAccount::create([
                'lease_id' => $lease->id,
                'type' => $type,
                'status' => 'active',
            ]);

        foreach ($movements as [$direction, $category, $amount]) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'direction' => $direction,
                'category' => $category,
                'amount' => $amount,
                'transaction_date' => '2026-03-01',
            ]);
        }

        return $account;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int}>  $movements
     */
    private function createOwnerWithBalance(
        string $name,
        string $email,
        array $movements
    ): Party {
        $owner =
            Party::create([
                'type' => 'organisation',
                'name' => $name,
                'legal_name' => $name,
                'phone' => '0200098100',
                'email' => $email,
            ]);

        PartyRole::create([
            'party_id' => $owner->id,
            'role' => 'owner',
        ]);

        $account =
            OwnerAccount::create([
                'party_id' => $owner->id,
                'status' => 'active',
            ]);

        foreach ($movements as [$direction, $category, $amount]) {
            OwnerTransaction::create([
                'owner_account_id' => $account->id,
                'direction' => $direction,
                'category' => $category,
                'amount' => $amount,
                'transaction_date' => '2026-03-01',
            ]);
        }

        return $owner;
    }
}
