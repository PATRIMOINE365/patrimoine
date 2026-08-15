<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\RentReserveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies Patrimoine's Rent Reserve protection and settlement rules.
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
     *     account: TenantFundAccount,
     *     owner: Party
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Reserve Test Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Reserve Test Owner',
            'phone' => '0200000051',
            'email' => 'reserve-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
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
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 15000,
            'transaction_date' => '2026-01-01',
        ]);

        return compact(
            'lease',
            'invoice',
            'account',
            'owner'
        );
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
        $this->assertSame(
            'rent_consumption',
            $transaction->category
        );

        $this->assertSame(5000, $transaction->amount);

        /*
         * GHS 15,000 reserve - GHS 5,000 consumed.
         */
        $this->assertSame(
            10000,
            $context['account']->fresh()->balance()
        );

        /*
         * Reserve consumption actually settles the Invoice.
         */
        $this->assertSame(
            5000,
            $context['invoice']->fresh()->paidAmount()
        );

        $this->assertSame(
            0,
            $context['invoice']->fresh()->outstandingAmount()
        );

        $this->assertSame(
            'paid',
            $context['invoice']->fresh()->status
        );

        /*
         * The released rent now belongs to the owner.
         */
        $ownerAccount = OwnerAccount::query()
            ->where('party_id', $context['owner']->id)
            ->firstOrFail();

        $this->assertSame(
            5000,
            $ownerAccount->creditedAmount()
        );
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
    /**
 * Rent Reserve may settle contractual rent only.
 *
 * Security Deposit close-out debt is a separate tenant receivable and
 * must never consume Rent Reserve or create owner rent entitlement.
 */
public function test_reserve_cannot_settle_security_deposit_debt_invoice(): void
{
    $context = $this->createContext();

    $context['lease']->update([
        'status' => 'notice',
        'termination_notice_date' => '2026-07-15',
    ]);

    $context['invoice']->update([
        'type' => 'security_deposit_debt',
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage(
        'Rent Reserve can only settle rent invoices.'
    );

    app(RentReserveService::class)->consume(
        $context['account'],
        $context['invoice'],
        1000,
        '2026-08-01'
    );
}
}
