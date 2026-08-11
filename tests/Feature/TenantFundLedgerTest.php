<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the accounting behavior of tenant-held fund accounts.
 *
 * These tests ensure balances are derived from an auditable transaction
 * history rather than stored as mutable balance fields.
 */
class TenantFundLedgerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the minimum lease context required for a fund account.
     */
    private function createLease(): Lease
    {
        $building = Building::create([
            'name' => 'Fund Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Fund Test Tenant',
            'phone' => '0200000040',
            'email' => 'fund-tenant@example.test',
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);
    }

    /**
     * Credits increase a tenant fund balance and debits reduce it.
     */
    public function test_balance_is_derived_from_credit_and_debit_transactions(): void
    {
        $lease = $this->createLease();

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 30000,
            'transaction_date' => '2026-01-01',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'debit',
            'category' => 'rent_consumption',
            'amount' => 5000,
            'transaction_date' => '2026-06-01',
        ]);

        $this->assertSame(30000, $account->creditedAmount());
        $this->assertSame(5000, $account->debitedAmount());
        $this->assertSame(25000, $account->balance());
    }

    /**
     * A Lease can have separate accounts for each held-fund category.
     */
    public function test_lease_can_hold_multiple_distinct_fund_accounts(): void
    {
        $lease = $this->createLease();

        TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'rent_reserve',
        ]);

        TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'consumable_advance',
        ]);

        TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
        ]);

        $this->assertCount(
            3,
            $lease->fresh()->tenantFundAccounts
        );
    }

    /**
     * The database prevents duplicate fund-account types for a Lease.
     */
    public function test_lease_cannot_have_duplicate_fund_account_type(): void
    {
        $lease = $this->createLease();

        TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
        ]);
    }
}
