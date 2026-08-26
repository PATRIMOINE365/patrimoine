<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\SecurityDepositService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Create UI TEST 08.
 *
 * Scenario:
 *
 * - GH₵3,000 outstanding rent;
 * - GH₵2,000 Security Deposit close-out debt;
 * - later we will record a GH₵7,000 tenant Payment through the UI;
 * - FIFO should settle both receivables;
 * - GH₵2,000 should remain genuinely unclassified.
 *
 * This scenario verifies that overpayment remains available after a mixed
 * rent / non-rent settlement without creating duplicate accounting entries.
 */
class MixedTenantOverpaymentUiTestSeeder extends Seeder
{
    /**
     * Seed the UI TEST 08 mixed-receivable overpayment scenario.
     */
    public function run(): void
    {
        /*
         * V1.0.10 multi-tenancy: demo data belongs to a demo
         * organisation; every row created below is stamped through
         * the bound organisation context.
         */
        $demoOrganisation =
            \App\Models\Organisation::query()->firstOrCreate(
                ['name' => 'Demo Organisation'],
                ['status' => 'active']
            );

        \App\Support\OrganisationContext::runAs(
            (int) $demoOrganisation->id,
            function (): void {
                $this->seedScoped();
            }
        );
    }

    /**
     * The original seeding body, executed with the demo
     * organisation context bound.
     */
    private function seedScoped(): void
    {
        DB::transaction(function (): void {
            /*
             * Keep the seeder safely repeatable during UI verification.
             */
            if (
                Party::query()
                    ->where('name', 'UI TEST 08 Tenant')
                    ->exists()
            ) {
                return;
            }

            $owner = Party::create([
                'type' => 'person',
                'name' => 'UI TEST 08 Owner',
                'phone' => '0209999800',
                'email' => 'ui-test-08-owner@example.test',
            ]);

            PartyRole::create([
                'party_id' => $owner->id,
                'role' => 'owner',
            ]);

            $building = Building::create([
                'name' => 'UI TEST 08 - Mixed Overpayment Building',
                'location' => 'Patrimoine Test Data',
                'notes' => 'Disposable UI TEST 08 mixed receivable overpayment scenario.',
            ]);

            BuildingOwner::create([
                'building_id' => $building->id,
                'party_id' => $owner->id,
                'ownership_percentage' => 100.00,
            ]);

            $unit = Unit::create([
                'building_id' => $building->id,
                'name' => 'UI TEST 08 - Mixed Overpayment',
            ]);

            $tenant = Party::create([
                'type' => 'person',
                'name' => 'UI TEST 08 Tenant',
                'phone' => '0209999008',
                'email' => 'ui-test-08-tenant@example.test',
            ]);

            PartyRole::create([
                'party_id' => $tenant->id,
                'role' => 'tenant',
            ]);

            /*
             * The Lease is terminated because the Security Deposit settlement
             * workflow has already occurred, while its historical rent debt
             * may still remain collectible.
             */
            $lease = Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => '2026-03-01',
                'end_date' => '2026-07-31',
                'status' => 'terminated',
                'termination_notice_date' => '2026-07-01',
                'rent_amount' => 12000,
                'payment_frequency' => 'monthly',
                'due_day' => 1,
                'vat_rate' => 18.00,
                'security_deposit_amount' => 5000,
                'advance_payment_amount' => 0,
                'rent_reserve_amount' => 0,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'management_fee_type' => 'none',
                'management_fee_value' => 0,
                'agent_commission_amount' => 0,
                'notes' => 'Disposable Patrimoine UI TEST 08 mixed receivable overpayment scenario.',
            ]);

            /*
             * Create an older rent Invoice so FIFO allocates to rent before
             * the later Security Deposit close-out debt.
             */
            Invoice::create([
                'lease_id' => $lease->id,
                'invoice_number' => 'UI-TEST-08-RENT-001',
                'type' => 'rent',
                'period_start' => '2026-07-01',
                'period_end' => '2026-07-31',
                'issue_date' => '2026-07-01',
                'due_date' => '2026-07-01',
                'status' => 'issued',
                'total_amount' => 3000,
                'vat_rate' => 0,
                'net_amount' => 3000,
                'vat_amount' => 0,
                'proration_amount' => null,
                'notes' => 'UI TEST 08 outstanding rent receivable.',
            ]);

            /*
             * Fund the Security Deposit with GH₵5,000.
             */
            $account = TenantFundAccount::create([
                'lease_id' => $lease->id,
                'type' => 'security_deposit',
                'status' => 'active',
            ]);

            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'direction' => 'credit',
                'category' => 'deposit_funding',
                'amount' => 5000,
                'transaction_date' => '2026-03-01',
                'reference' => 'UI-TEST-08-DEPOSIT-5000',
                'notes' => 'UI TEST 08 opening Security Deposit balance.',
            ]);

            /*
             * GH₵7,000 deductions against GH₵5,000 held deposit create
             * GH₵2,000 tenant debt at settlement.
             */
            SecurityDepositDeduction::create([
                'lease_id' => $lease->id,
                'description' => 'Close-out repairs',
                'amount' => 7000,
                'deduction_date' => '2026-08-13',
                'reference' => 'UI-TEST-08-REPAIRS',
                'notes' => 'UI TEST 08 Security Deposit debt scenario.',
            ]);

            app(SecurityDepositService::class)->settle(
                lease: $lease,
                settlementDate: '2026-08-13',
                notes: 'UI TEST 08 settlement creating GH₵2,000 tenant debt.'
            );
        });

        $this->command?->info(
            'UI TEST 08 mixed receivable overpayment scenario created.'
        );
    }
}
