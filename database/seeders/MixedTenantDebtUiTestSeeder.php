<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\SecurityDepositService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Create UI TEST 07 for mixed tenant receivable collection.
 *
 * This disposable scenario verifies that one tenant Payment can settle:
 *
 * - ordinary contractual rent; and
 * - Security Deposit close-out debt.
 *
 * Expected position before collection:
 *
 * - Rent Invoice outstanding: GHS 3,000;
 * - Security Deposit debt outstanding: GHS 2,000;
 * - Total outstanding: GHS 5,000.
 *
 * The rent Invoice deliberately has the earlier due date so Patrimoine's
 * FIFO allocation settles rent first and Security Deposit debt second.
 */
class MixedTenantDebtUiTestSeeder extends Seeder
{
    /**
     * Seed UI TEST 07.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            /*
             * Remove a previous copy of UI TEST 07 if this dedicated seeder
             * has already been run.
             *
             * This seeder is intended only for disposable local UI testing.
             */
            $existingTenant = Party::query()
                ->where(
                    'email',
                    'ui-test-07-tenant@example.test'
                )
                ->first();

            if ($existingTenant !== null) {
                throw new \RuntimeException(
                    'UI TEST 07 already exists. Do not rerun this seeder against the same disposable scenario.'
                );
            }

            /*
             * Create a dedicated owner and Building so the ordinary rent
             * portion can enter the owner-accounting pipeline normally.
             */
            $owner = Party::create([
                'type' =>
                    'person',

                'name' =>
                    'UI TEST 07 Owner',

                'phone' =>
                    '0209999070',

                'email' =>
                    'ui-test-07-owner@example.test',
            ]);

            PartyRole::create([
                'party_id' =>
                    $owner->id,

                'role' =>
                    'owner',
            ]);

            $building = Building::create([
                'name' =>
                    'UI TEST 07 - Mixed Receivables Building',

                'location' =>
                    'Patrimoine Test Data',

                'notes' =>
                    'Disposable mixed rent and Security Deposit debt UI scenario.',
            ]);

            BuildingOwner::create([
                'building_id' =>
                    $building->id,

                'party_id' =>
                    $owner->id,

                'ownership_percentage' =>
                    100.00,
            ]);

            $unit = Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'UI TEST 07 - Mixed Rent and Deposit Debt',
            ]);

            $tenant = Party::create([
                'type' =>
                    'person',

                'name' =>
                    'UI TEST 07 Tenant',

                'phone' =>
                    '0209999007',

                'email' =>
                    'ui-test-07-tenant@example.test',
            ]);

            PartyRole::create([
                'party_id' =>
                    $tenant->id,

                'role' =>
                    'tenant',
            ]);

            /*
             * The Lease is already terminated because Security Deposit
             * settlement belongs to the final Lease close-out workflow.
             */
            $lease = Lease::create([
                'unit_id' =>
                    $unit->id,

                'tenant_id' =>
                    $tenant->id,

                'start_date' =>
                    '2026-03-01',

                'end_date' =>
                    '2026-07-31',

                'status' =>
                    'terminated',

                'termination_notice_date' =>
                    '2026-07-01',

                'rent_amount' =>
                    3000,

                'payment_frequency' =>
                    'monthly',

                'due_day' =>
                    1,

                'vat_rate' =>
                    0,

                'security_deposit_amount' =>
                    5000,

                'advance_payment_amount' =>
                    0,

                'rent_reserve_amount' =>
                    0,

                'rent_increment_type' =>
                    'none',

                'rent_increment_value' =>
                    0,

                'management_fee_type' =>
                    'none',

                'management_fee_value' =>
                    0,

                'agent_commission_amount' =>
                    0,

                'notes' =>
                    'UI TEST 07 mixed rent and Security Deposit debt collection scenario.',
            ]);

            /*
             * Create the ordinary rent receivable first.
             *
             * Its earlier due date ensures FIFO settles this GHS 3,000
             * before the later Security Deposit debt receivable.
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
             * Record the original GHS 5,000 Security Deposit receipt.
             *
             * The Payment itself remains the original incoming cash record.
             * The dedicated tenant-fund transaction records that this money
             * became Security Deposit rather than rent.
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
                    'UI TEST 07 original Security Deposit receipt.',
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
             * Final deductions exceed the GHS 5,000 deposit by GHS 2,000.
             *
             * The real SecurityDepositService should convert that excess into
             * one security_deposit_debt Invoice automatically.
             */
            SecurityDepositDeduction::create([
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

            app(
                SecurityDepositService::class
            )->settle(
                lease:
                    $lease,

                settlementDate:
                    '2026-08-13',

                notes:
                    'UI TEST 07 settlement generating mixed tenant receivables.'
            );
        });

        $this->command?->info(
            'UI TEST 07 mixed rent and Security Deposit debt scenario created.'
        );
    }
}
