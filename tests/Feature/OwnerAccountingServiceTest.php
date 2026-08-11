<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\OwnerAccount;
use App\Models\OwnerExpense;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Services\OwnerAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies owner-ledger accounting and property-expense allocation.
 */
class OwnerAccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a Building with two owners.
     *
     * @return array{
     *     building: Building,
     *     firstOwner: Party,
     *     secondOwner: Party
     * }
     */
    private function createBuildingWithOwners(
        float $firstPercentage = 60.00,
        float $secondPercentage = 40.00
    ): array {
        $building = Building::create([
            'name' => 'Owner Accounting Test Building',
        ]);

        $firstOwner = Party::create([
            'type' => 'person',
            'name' => 'Owner One',
            'phone' => '0200000070',
            'email' => 'owner-one@example.test',
        ]);

        $secondOwner = Party::create([
            'type' => 'person',
            'name' => 'Owner Two',
            'phone' => '0200000071',
            'email' => 'owner-two@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $firstOwner->id,
            'ownership_percentage' => $firstPercentage,
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $secondOwner->id,
            'ownership_percentage' => $secondPercentage,
        ]);

        return compact(
            'building',
            'firstOwner',
            'secondOwner'
        );
    }

    /**
     * Building expenses are divided according to ownership percentages.
     */
    public function test_expense_is_allocated_by_ownership_percentage(): void
    {
        $context = $this->createBuildingWithOwners();

        $expense = OwnerExpense::create([
            'building_id' => $context['building']->id,
            'description' => 'Roof repair',
            'amount' => 10000,
            'expense_date' => '2026-08-11',
        ]);

        app(OwnerAccountingService::class)->allocateExpense($expense);

        $firstAccount = OwnerAccount::where(
            'party_id',
            $context['firstOwner']->id
        )->firstOrFail();

        $secondAccount = OwnerAccount::where(
            'party_id',
            $context['secondOwner']->id
        )->firstOrFail();

        $this->assertSame(6000, $firstAccount->debitedAmount());
        $this->assertSame(4000, $secondAccount->debitedAmount());

        /*
         * With no owner credits yet, both balances are negative.
         * Those negative balances are intentionally carried forward.
         */
        $this->assertSame(-6000, $firstAccount->balance());
        $this->assertSame(-4000, $secondAccount->balance());
    }

    /**
     * Integer rounding must never cause allocated shares to differ from
     * the original property expense.
     */
    public function test_rounding_preserves_total_expense_amount(): void
    {
        $context = $this->createBuildingWithOwners(
            33.33,
            66.67
        );

        $expense = OwnerExpense::create([
            'building_id' => $context['building']->id,
            'description' => 'Minor maintenance',
            'amount' => 10001,
            'expense_date' => '2026-08-11',
        ]);

        $transactions = app(OwnerAccountingService::class)
            ->allocateExpense($expense);

        $allocatedTotal = collect($transactions)->sum('amount');

        $this->assertSame(10001, $allocatedTotal);
    }

    /**
     * Financial allocation is blocked when ownership does not total 100%.
     */
    public function test_expense_allocation_requires_complete_ownership(): void
    {
        $context = $this->createBuildingWithOwners(
            60.00,
            30.00
        );

        $expense = OwnerExpense::create([
            'building_id' => $context['building']->id,
            'description' => 'Invalid ownership test',
            'amount' => 10000,
            'expense_date' => '2026-08-11',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Building ownership percentages must total 100%.'
        );

        app(OwnerAccountingService::class)->allocateExpense($expense);
    }

    /**
     * Owner balance is always derived from credits minus debits.
     */
    public function test_owner_balance_carries_negative_amount_forward(): void
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Balance Test Owner',
            'phone' => '0200000072',
            'email' => 'balance-owner@example.test',
        ]);

        $account = OwnerAccount::create([
            'party_id' => $owner->id,
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'expense',
            'amount' => 5000,
            'transaction_date' => '2026-08-01',
        ]);

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'owner_deposit',
            'amount' => 2000,
            'transaction_date' => '2026-08-05',
        ]);

        $this->assertSame(2000, $account->creditedAmount());
        $this->assertSame(5000, $account->debitedAmount());
        $this->assertSame(-3000, $account->balance());
    }



    /**
     * Owner entitlement is created only when tenant rent is actually collected.
     */
    public function test_collected_rent_is_shared_between_owners(): void
    {
        $context = $this->createBuildingWithOwners(60.00, 40.00);

        $unit = \App\Models\Unit::create([
            'building_id' => $context['building']->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Rent Tenant',
            'phone' => '0200000080',
            'email' => 'rent-tenant@example.test',
        ]);

        $lease = \App\Models\Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'status' => 'active',
        ]);

        $invoice = \App\Models\Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-OWNER-001',
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

        /*
        * Only GHS 4,000 has actually been received.
        */
        $payment = \App\Models\Payment::create([
            'lease_id' => $lease->id,
            'amount' => 4000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-OWNER-001',
        ]);

        $allocation = \App\Models\PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 4000,
        ]);

        app(OwnerAccountingService::class)
            ->postCollectedRentEntitlement($allocation);

        $firstAccount = OwnerAccount::where(
            'party_id',
            $context['firstOwner']->id
        )->firstOrFail();

        $secondAccount = OwnerAccount::where(
            'party_id',
            $context['secondOwner']->id
        )->firstOrFail();

        /*
        * Only collected cash is distributed:
        *
        * GHS 4,000 × 60% = GHS 2,400
        * GHS 4,000 × 40% = GHS 1,600
        */
        $this->assertSame(2400, $firstAccount->creditedAmount());
        $this->assertSame(1600, $secondAccount->creditedAmount());

        /*
        * The unpaid GHS 6,000 portion of the Invoice creates no owner
        * entitlement yet.
        */
        $this->assertSame(
            4000,
            $firstAccount->creditedAmount()
            + $secondAccount->creditedAmount()
        );
    }







    /**
     * Percentage management fees reduce owner balances according to
     * ownership percentages.
     */
    public function test_percentage_management_fee_is_charged_to_owners(): void
    {
        $context = $this->createBuildingWithOwners(60.00, 40.00);

        $unit = \App\Models\Unit::create([
            'building_id' => $context['building']->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Management Fee Tenant',
            'phone' => '0200000081',
            'email' => 'fee-tenant@example.test',
        ]);

        $lease = \App\Models\Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'management_fee_type' => 'percentage',
            'management_fee_value' => 10,
            'status' => 'active',
        ]);

        $invoice = \App\Models\Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-OWNER-002',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 10000,
            'vat_rate' => 0,
            'net_amount' => 10000,
            'vat_amount' => 0,
        ]);

        app(OwnerAccountingService::class)
            ->postManagementFee($invoice);

        $firstAccount = OwnerAccount::where(
            'party_id',
            $context['firstOwner']->id
        )->firstOrFail();

        $secondAccount = OwnerAccount::where(
            'party_id',
            $context['secondOwner']->id
        )->firstOrFail();

        /*
        * Management fee = 10% of GHS 10,000 = GHS 1,000.
        * Owner shares = GHS 600 and GHS 400.
        */
        $this->assertSame(600, $firstAccount->debitedAmount());
        $this->assertSame(400, $secondAccount->debitedAmount());
    }

    /**
     * One-time Agent commission is distributed across owners.
     */
    public function test_agent_commission_is_charged_to_owners(): void
    {
        $context = $this->createBuildingWithOwners(60.00, 40.00);

        $unit = \App\Models\Unit::create([
            'building_id' => $context['building']->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Commission Tenant',
            'phone' => '0200000082',
            'email' => 'commission-tenant@example.test',
        ]);

        $lease = \App\Models\Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'agent_commission_amount' => 5000,
            'status' => 'active',
        ]);

        app(OwnerAccountingService::class)
            ->postAgentCommission($lease);

        $firstAccount = OwnerAccount::where(
            'party_id',
            $context['firstOwner']->id
        )->firstOrFail();

        $secondAccount = OwnerAccount::where(
            'party_id',
            $context['secondOwner']->id
        )->firstOrFail();

        $this->assertSame(3000, $firstAccount->debitedAmount());
        $this->assertSame(2000, $secondAccount->debitedAmount());
    }

    /**
     * Issuing an Invoice alone must never create owner funds held.
     */
    public function test_unpaid_invoice_creates_no_owner_entitlement(): void
    {
        $context = $this->createBuildingWithOwners(60.00, 40.00);

        $unit = \App\Models\Unit::create([
            'building_id' => $context['building']->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Unpaid Tenant',
            'phone' => '0200000083',
            'email' => 'unpaid@example.test',
        ]);

        $lease = \App\Models\Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'status' => 'active',
        ]);

        \App\Models\Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-OWNER-UNPAID',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 10000,
            'vat_rate' => 0,
            'net_amount' => 10000,
            'vat_amount' => 0,
        ]);

        $this->assertDatabaseCount('owner_accounts', 0);
        $this->assertDatabaseCount('owner_transactions', 0);
    }
    /**
     * Reprocessing the same PaymentAllocation must never credit owners twice.
     */
    public function test_collected_rent_entitlement_is_idempotent(): void
    {
        $context = $this->createBuildingWithOwners(60.00, 40.00);

        $unit = \App\Models\Unit::create([
            'building_id' => $context['building']->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Idempotency Tenant',
            'phone' => '0200000084',
            'email' => 'idempotency@example.test',
        ]);

        $lease = \App\Models\Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'status' => 'active',
        ]);

        $invoice = \App\Models\Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-IDEMPOTENT-001',
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

        $payment = \App\Models\Payment::create([
            'lease_id' => $lease->id,
            'amount' => 4000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
        ]);

        $allocation = \App\Models\PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 4000,
        ]);

        $service = app(OwnerAccountingService::class);

        $service->postCollectedRentEntitlement($allocation);
        $service->postCollectedRentEntitlement($allocation);

        /*
        * The allocation represents only GHS 4,000 collected, therefore the
        * total entitlement must remain GHS 4,000 despite being processed twice.
        */
        $this->assertSame(
            4000,
            OwnerTransaction::where(
                'payment_allocation_id',
                $allocation->id
            )->sum('amount')
        );

        $this->assertSame(
            2,
            OwnerTransaction::where(
                'payment_allocation_id',
                $allocation->id
            )->count()
        );
    }
}
