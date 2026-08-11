<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\RentReserveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies Patrimoine's Rent Reserve protection rules.
 */
class RentReserveServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a Lease, Invoice and funded Rent Reserve account.
     *
     * @return array{
     *     lease: Lease,
     *     invoice: Invoice,
     *     account: TenantFundAccount
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Reserve Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Reserve Test Tenant',
            'phone' => '0200000050',
            'email' => 'reserve@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-R-000001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'rent_reserve',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 15000,
            'transaction_date' => '2026-01-01',
        ]);

        return compact('lease', 'invoice', 'account');
    }

    /**
     * Rent Reserve must remain untouched before termination notice.
     */
    public function test_reserve_cannot_be_consumed_before_notice(): void
    {
        $context = $this->createContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Rent Reserve cannot be consumed before termination notice.'
        );

        app(RentReserveService::class)->consume(
            $context['account'],
            $context['invoice'],
            5000,
            '2026-08-01'
        );
    }

    /**
     * Reserve may be consumed once termination notice is active.
     */
    public function test_reserve_can_be_consumed_after_notice(): void
    {
        $context = $this->createContext();

        $context['lease']->update([
            'status' => 'notice',
            'termination_notice_date' => '2026-07-15',
        ]);

        $transaction = app(RentReserveService::class)->consume(
            $context['account'],
            $context['invoice'],
            5000,
            '2026-08-01'
        );

        $this->assertSame('debit', $transaction->direction);
        $this->assertSame('rent_consumption', $transaction->category);
        $this->assertSame(5000, $transaction->amount);
        $this->assertSame(10000, $context['account']->fresh()->balance());
    }

    /**
     * Reserve consumption must never exceed the available balance.
     */
    public function test_reserve_cannot_be_overdrawn(): void
    {
        $context = $this->createContext();

        $context['lease']->update([
            'status' => 'notice',
            'termination_notice_date' => '2026-07-15',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Rent Reserve balance is insufficient.'
        );

        app(RentReserveService::class)->consume(
            $context['account'],
            $context['invoice'],
            20000,
            '2026-08-01'
        );
    }
}
