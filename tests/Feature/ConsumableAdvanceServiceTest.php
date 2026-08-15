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
use App\Services\ConsumableAdvanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies Patrimoine's Consumable Advance settlement rules.
 */
class ConsumableAdvanceServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a Lease, Invoice and funded Consumable Advance account.
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
            'name' => 'Consumable Advance Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Consumable Advance Owner',
            'phone' => '0200001300',
            'email' => 'advance-owner@example.test',
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
            'name' => 'Consumable Advance Tenant',
            'phone' => '0200001301',
            'email' => 'advance-tenant@example.test',
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
            'invoice_number' => 'INV-ADV-000001',
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
            'type' => 'consumable_advance',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'advance_funding',
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
     * Consumable Advance may settle rent during a normal active Lease.
     */
    public function test_advance_can_settle_invoice_before_notice(): void
    {
        $context = $this->createContext();

        $transaction = app(ConsumableAdvanceService::class)
            ->consume(
                $context['account'],
                $context['invoice'],
                5000,
                '2026-08-01'
            );

        $this->assertSame(
            'advance_consumption',
            $transaction->category
        );

        $this->assertSame(
            10000,
            $context['account']->fresh()->balance()
        );

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

        $ownerAccount = OwnerAccount::query()
            ->where('party_id', $context['owner']->id)
            ->firstOrFail();

        $this->assertSame(
            5000,
            $ownerAccount->creditedAmount()
        );
    }

    /**
     * Partial advance consumption leaves the Invoice partially settled.
     */
    public function test_advance_can_partially_settle_invoice(): void
    {
        $context = $this->createContext();

        app(ConsumableAdvanceService::class)
            ->consume(
                $context['account'],
                $context['invoice'],
                2000,
                '2026-08-01'
            );

        $invoice = $context['invoice']->fresh();

        $this->assertSame(
            2000,
            $invoice->paidAmount()
        );

        $this->assertSame(
            3000,
            $invoice->outstandingAmount()
        );

        $this->assertSame(
            'partial',
            $invoice->status
        );
    }

    /**
     * Consumable Advance cannot exceed its available balance.
     */
    public function test_advance_cannot_be_overdrawn(): void
    {
        $context = $this->createContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Consumable Advance balance is insufficient.'
        );

        app(ConsumableAdvanceService::class)
            ->consume(
                $context['account'],
                $context['invoice'],
                20000,
                '2026-08-01'
            );
    }

    /**
     * Advance cannot settle more than the Invoice outstanding amount.
     */
    public function test_advance_cannot_exceed_invoice_balance(): void
    {
        $context = $this->createContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Consumable Advance exceeds the Invoice outstanding amount.'
        );

        app(ConsumableAdvanceService::class)
            ->consume(
                $context['account'],
                $context['invoice'],
                6000,
                '2026-08-01'
            );
    }

    /**
     * Advance belonging to one Lease cannot settle another Lease's Invoice.
     */
    public function test_advance_cannot_pay_another_lease_invoice(): void
    {
        $context = $this->createContext();

        $otherBuilding = Building::create([
            'name' => 'Other Advance Building',
        ]);

        $otherUnit = Unit::create([
            'building_id' => $otherBuilding->id,
            'name' => 'Other Unit',
        ]);

        $otherTenant = Party::create([
            'type' => 'person',
            'name' => 'Other Tenant',
            'phone' => '0200001302',
            'email' => 'other-advance@example.test',
        ]);

        $otherLease = Lease::create([
            'unit_id' => $otherUnit->id,
            'tenant_id' => $otherTenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $otherInvoice = Invoice::create([
            'lease_id' => $otherLease->id,
            'invoice_number' => 'INV-ADV-OTHER',
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The Invoice does not belong to the Consumable Advance Lease.'
        );

        app(ConsumableAdvanceService::class)
            ->consume(
                $context['account'],
                $otherInvoice,
                1000,
                '2026-08-01'
            );
    }

    /**
     * Draft Leases cannot consume tenant advance funds.
     */
    public function test_draft_lease_cannot_consume_advance(): void
    {
        $context = $this->createContext();

        $context['lease']->update([
            'status' => 'draft',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Consumable Advance cannot be used for a draft Lease.'
        );

        app(ConsumableAdvanceService::class)
            ->consume(
                $context['account'],
                $context['invoice'],
                1000,
                '2026-08-01'
            );
    }
    /**
 * Consumable Advance may settle contractual rent only.
 *
 * Security Deposit debt must remain collectible through the ordinary
 * tenant Payment workflow and must not be converted into owner rent.
 */
public function test_advance_cannot_settle_security_deposit_debt_invoice(): void
{
    $context = $this->createContext();

    $context['invoice']->update([
        'type' => 'security_deposit_debt',
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage(
        'Consumable Advance can only settle rent invoices.'
    );

    app(ConsumableAdvanceService::class)->consume(
        $context['account'],
        $context['invoice'],
        1000,
        '2026-08-01'
    );
}
}
