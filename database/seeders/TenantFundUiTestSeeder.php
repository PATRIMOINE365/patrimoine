<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SecurityDepositDeduction;

/**
 * Create disposable operational scenarios used to verify the complete
 * Patrimoine V1 tenant-fund browser workflow.
 *
 * These records deliberately use conspicuous "UI TEST" names and references
 * so they can easily be distinguished from normal development data.
 *
 * Scenarios:
 *
 * 01 - an unclassified Tenant Payment;
 * 02 - a funded Consumable Advance with outstanding rent;
 * 03 - a funded Rent Reserve on a Lease already in Notice;
 * 04 - a funded Security Deposit on a terminated Lease.
 */
class TenantFundUiTestSeeder extends Seeder
{
    /**
     * Seed the browser-verification scenarios.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            /*
             * Re-running this seeder should not create duplicate scenarios.
             *
             * If the first Building exists, assume the UI-test dataset has
             * already been created and leave it untouched so any operations
             * performed during testing are preserved.
             */
            if (
                Building::query()
                    ->where('name', 'UI TEST - Tenant Funds Building')
                    ->exists()
            ) {
                $this->command?->warn(
                    'Tenant Fund UI test data already exists. Seeder skipped.'
                );

                return;
            }

            /*
             * One Owner and Building are sufficient for all scenarios.
             *
             * Ownership is important because consuming Advance or Reserve
             * against rent creates Owner entitlement.
             */
            $owner = Party::create([
                'type' => 'person',
                'name' => 'UI TEST Owner',
                'phone' => '0209999000',
                'email' => 'ui-test-owner@patrimoine.test',
            ]);

            PartyRole::create([
                'party_id' => $owner->id,
                'role' => 'owner',
            ]);

            $building = Building::create([
                'name' => 'UI TEST - Tenant Funds Building',
                'location' => 'Patrimoine Test Data',
                'notes' =>
                    'Disposable records for tenant-fund UI verification.',
            ]);

            BuildingOwner::create([
                'building_id' => $building->id,
                'party_id' => $owner->id,
                'ownership_percentage' => 100.00,
            ]);

            $this->createMixedRentAndDepositDebtScenario(
    $building
);

            $this->createUnclassifiedPaymentScenario(
                $building
            );

            $this->createConsumableAdvanceScenario(
                $building
            );

            $this->createRentReserveScenario(
                $building
            );

            $this->createSecurityDepositScenario(
                $building
            );
        });

        $this->command?->info(
            'Tenant Fund UI test scenarios created.'
        );
    }

    /**
     * Scenario 01:
     *
     * GHS 15,000 has been received but is not allocated to an Invoice and
     * has not yet been classified into any tenant-held fund.
     */
    private function createUnclassifiedPaymentScenario(
        Building $building
    ): void {
        $lease = $this->createLease(
            building: $building,
            unitName: 'UI TEST 01 - Unclassified Payment',
            tenantName: 'UI TEST 01 Tenant',
            tenantPhone: '0209999001',
            tenantEmail: 'ui-test-01@patrimoine.test',
            status: 'active'
        );

        Payment::create([
            'lease_id' => $lease->id,
            'amount' => 15000,
            'payment_date' => '2026-08-13',
            'payment_method' => 'bank_transfer',
            'reference' => 'UI-TEST-UNCLASSIFIED-15000',
            'notes' =>
                'UI TEST: GHS 15,000 intentionally left unclassified.',
        ]);
    }

    /**
     * Scenario 02:
     *
     * GHS 18,000 of actual Consumable Advance exists and an outstanding
     * GHS 12,000 Invoice is available against which it may be consumed.
     */
    private function createConsumableAdvanceScenario(
        Building $building
    ): void {
        $lease = $this->createLease(
            building: $building,
            unitName: 'UI TEST 02 - Consumable Advance',
            tenantName: 'UI TEST 02 Tenant',
            tenantPhone: '0209999002',
            tenantEmail: 'ui-test-02@patrimoine.test',
            status: 'active',
            advanceAmount: 18000
        );

        $invoice = $this->createInvoice(
            lease: $lease,
            number: 'UI-TEST-ADV-INV-001',
            amount: 12000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 18000,
            'payment_date' => '2026-08-13',
            'payment_method' => 'bank_transfer',
            'reference' => 'UI-TEST-ADVANCE-18000',
            'notes' =>
                'UI TEST: money already classified as Consumable Advance.',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'consumable_advance',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'payment_id' => $payment->id,
            'direction' => 'credit',
            'category' => 'advance_funding',
            'amount' => 18000,
            'transaction_date' => '2026-08-13',
            'reference' => 'UI-TEST-ADVANCE-18000',
            'notes' =>
                'UI TEST opening Consumable Advance balance.',
        ]);
    }

    /**
     * Scenario 03:
     *
     * The Lease is already in Notice, making the GHS 24,000 Rent Reserve
     * eligible for consumption against an outstanding GHS 12,000 Invoice.
     */
    private function createRentReserveScenario(
        Building $building
    ): void {
        $lease = $this->createLease(
            building: $building,
            unitName: 'UI TEST 03 - Rent Reserve Notice',
            tenantName: 'UI TEST 03 Tenant',
            tenantPhone: '0209999003',
            tenantEmail: 'ui-test-03@patrimoine.test',
            status: 'notice',
            noticeDate: '2026-08-01',
            advanceAmount: 24000,
            reserveAmount: 24000
        );

        $this->createInvoice(
            lease: $lease,
            number: 'UI-TEST-RESERVE-INV-001',
            amount: 12000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 24000,
            'payment_date' => '2026-08-01',
            'payment_method' => 'bank_transfer',
            'reference' => 'UI-TEST-RESERVE-24000',
            'notes' =>
                'UI TEST: money already classified as Rent Reserve.',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'payment_id' => $payment->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 24000,
            'transaction_date' => '2026-08-01',
            'reference' => 'UI-TEST-RESERVE-24000',
            'notes' =>
                'UI TEST opening Rent Reserve balance.',
        ]);
    }

    /**
     * Scenario 04:
     *
     * A terminated Lease holds an actual GHS 10,000 Security Deposit.
     *
     * This scenario can proceed directly through itemized deductions,
     * settlement and generation of the final Security Deposit voucher.
     */
    private function createSecurityDepositScenario(
        Building $building
    ): void {
        $lease = $this->createLease(
            building: $building,
            unitName: 'UI TEST 04 - Security Deposit',
            tenantName: 'UI TEST 04 Tenant',
            tenantPhone: '0209999004',
            tenantEmail: 'ui-test-04@patrimoine.test',
            status: 'terminated',
            noticeDate: '2026-07-01',
            securityDepositAmount: 10000
        );

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 10000,
            'payment_date' => '2026-03-01',
            'payment_method' => 'bank_transfer',
            'reference' => 'UI-TEST-DEPOSIT-10000',
            'notes' =>
                'UI TEST: money already classified as Security Deposit.',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'payment_id' => $payment->id,
            'direction' => 'credit',
            'category' => 'deposit_funding',
            'amount' => 10000,
            'transaction_date' => '2026-03-01',
            'reference' => 'UI-TEST-DEPOSIT-10000',
            'notes' =>
                'UI TEST opening Security Deposit balance.',
        ]);
    }

/**
 * Scenario 07:
 *
 * Verify one Tenant Payment can settle both:
 *
 * - ordinary contractual rent; and
 * - Security Deposit close-out debt.
 *
 * Expected position before collection:
 *
 * - Rent Invoice: GHS 3,000 outstanding;
 * - Security Deposit debt: GHS 2,000 outstanding;
 * - Total tenant obligation: GHS 5,000.
 *
 * The rent Invoice deliberately has the earlier due date so FIFO settles
 * rent first and Security Deposit debt second.
 */
private function createMixedRentAndDepositDebtScenario(
    Building $building
): void {
    $lease = $this->createLease(
        building: $building,
        unitName:
            'UI TEST 07 - Mixed Rent and Deposit Debt',
        tenantName:
            'UI TEST 07 Tenant',
        tenantPhone:
            '0209999007',
        tenantEmail:
            'ui-test-07-tenant@example.test',
        status:
            'terminated',
        noticeDate:
            '2026-07-01',
        securityDepositAmount:
            5000
    );

    /*
     * Create an ordinary rent receivable that predates the Security Deposit
     * close-out debt. FIFO should therefore settle this Invoice first.
     */
    Invoice::create([
        'lease_id' =>
            $lease->id,

        'invoice_number' =>
            'UI-TEST-07-RENT-001',

        'type' =>
            'rent',

        'period_start' =>
            '2026-07-01',

        'period_end' =>
            '2026-07-31',

        'issue_date' =>
            '2026-07-01',

        'due_date' =>
            '2026-07-01',

        'status' =>
            'issued',

        'total_amount' =>
            3000,

        'vat_rate' =>
            0,

        'net_amount' =>
            3000,

        'vat_amount' =>
            0,

        'proration_amount' =>
            null,

        'notes' =>
            'UI TEST 07 ordinary rent receivable.',
    ]);

    /*
     * The tenant originally lodged GHS 5,000 as Security Deposit.
     */
    $depositPayment = Payment::create([
        'lease_id' =>
            $lease->id,

        'amount' =>
            5000,

        'payment_date' =>
            '2026-03-01',

        'payment_method' =>
            'bank_transfer',

        'reference' =>
            'UI-TEST-07-DEPOSIT-5000',

        'notes' =>
            'UI TEST 07 opening Security Deposit.',
    ]);

    $depositAccount = TenantFundAccount::create([
        'lease_id' =>
            $lease->id,

        'type' =>
            'security_deposit',

        'status' =>
            'active',
    ]);

    TenantFundTransaction::create([
        'tenant_fund_account_id' =>
            $depositAccount->id,

        'payment_id' =>
            $depositPayment->id,

        'direction' =>
            'credit',

        'category' =>
            'deposit_funding',

        'amount' =>
            5000,

        'transaction_date' =>
            '2026-03-01',

        'reference' =>
            'UI-TEST-07-DEPOSIT-5000',

        'notes' =>
            'UI TEST 07 opening Security Deposit balance.',
    ]);

    /*
     * GHS 7,000 of final deductions against a GHS 5,000 deposit creates
     * exactly GHS 2,000 of tenant debt.
     */
    \App\Models\SecurityDepositDeduction::create([
        'lease_id' =>
            $lease->id,

        'description' =>
            'UI TEST 07 close-out repairs',

        'amount' =>
            7000,

        'deduction_date' =>
            '2026-08-13',

        'reference' =>
            'UI-TEST-07-DEDUCTION-001',

        'notes' =>
            'Creates GHS 2,000 Security Deposit close-out debt.',
    ]);

    /*
     * Use the real settlement service. This must create the
     * security_deposit_debt Invoice automatically.
     */
    app(
        \App\Services\SecurityDepositService::class
    )->settle(
        lease:
            $lease,
        settlementDate:
            '2026-08-13',
        notes:
            'UI TEST 07 mixed receivable scenario.'
    );
}


    /**
     * Create the common Party / Unit / Lease structure used by a scenario.
     */
    private function createLease(
        Building $building,
        string $unitName,
        string $tenantName,
        string $tenantPhone,
        string $tenantEmail,
        string $status,
        ?string $noticeDate = null,
        int $advanceAmount = 0,
        int $reserveAmount = 0,
        int $securityDepositAmount = 0
    ): Lease {
        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => $unitName,
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => $tenantName,
            'phone' => $tenantPhone,
            'email' => $tenantEmail,
        ]);

        PartyRole::create([
            'party_id' => $tenant->id,
            'role' => 'tenant',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-03-01',
            'end_date' =>
                $status === 'terminated'
                    ? '2026-07-31'
                    : '2027-02-28',
            'status' => $status,
            'termination_notice_date' => $noticeDate,
            'rent_amount' => 12000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18.00,
            'security_deposit_amount' =>
                $securityDepositAmount,
            'advance_payment_amount' =>
                $advanceAmount,
            'rent_reserve_amount' =>
                $reserveAmount,
            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
            'notes' =>
                "Disposable Patrimoine tenant-fund UI scenario: {$unitName}.",
        ]);
    }

    /**
     * Create one clean outstanding rent Invoice.
     */
    private function createInvoice(
        Lease $lease,
        string $number,
        int $amount
    ): Invoice {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $number,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => $amount,
            'vat_rate' => 18.00,

            /*
             * Exact VAT split is irrelevant to the tenant-fund UI test.
             * These values still form a valid whole-unit historical snapshot.
             */
            'net_amount' => 10169,
            'vat_amount' => 1831,
            'proration_amount' => null,
            'notes' =>
                'Disposable outstanding Invoice for tenant-fund UI testing.',
        ]);
    }
}
