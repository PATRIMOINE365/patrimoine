<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\SecurityDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies Patrimoine's security-deposit settlement rules.
 */
class SecurityDepositServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a Lease with a funded security-deposit account.
     *
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount
     * }
     */
    private function createContext(int $depositAmount = 10000): array
    {
        $building = Building::create([
            'name' => 'Deposit Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Deposit Test Tenant',
            'phone' => '0200000060',
            'email' => 'deposit@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'terminated',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'deposit_funding',
            'amount' => $depositAmount,
            'transaction_date' => '2026-01-01',
        ]);

        return compact('lease', 'account');
    }

    /**
     * Remaining deposit is refunded after itemized deductions.
     */
    public function test_remaining_deposit_is_refunded(): void
    {
        $context = $this->createContext(10000);

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,
            'description' => 'Damaged lock',
            'amount' => 3000,
            'deduction_date' => '2026-08-10',
        ]);

        $settlement = app(SecurityDepositService::class)->settle(
            $context['lease'],
            '2026-08-11',
            'REF-2026-000001'
        );

        $this->assertSame(10000, $settlement->deposit_amount);
        $this->assertSame(3000, $settlement->deduction_amount);
        $this->assertSame(7000, $settlement->refund_amount);
        $this->assertSame(0, $settlement->tenant_debt_amount);

        /*
         * After deduction and refund, no security-deposit funds remain held.
         */
        $this->assertSame(
            0,
            $context['account']->fresh()->balance()
        );
    }

    /**
     * Deductions exceeding the available deposit create tenant debt.
     */
    public function test_excess_deductions_create_tenant_debt(): void
    {
        $context = $this->createContext(10000);

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,
            'description' => 'Major repairs',
            'amount' => 13000,
            'deduction_date' => '2026-08-10',
        ]);

        $settlement = app(SecurityDepositService::class)->settle(
            $context['lease'],
            '2026-08-11'
        );

        $this->assertSame(10000, $settlement->deposit_amount);
        $this->assertSame(13000, $settlement->deduction_amount);
        $this->assertSame(0, $settlement->refund_amount);
        $this->assertSame(3000, $settlement->tenant_debt_amount);

        /*
         * The entire available deposit is consumed, but the fund account
         * itself must never become negative.
         */
        $this->assertSame(
            0,
            $context['account']->fresh()->balance()
        );
    }

    /**
     * A deposit with no deductions is refunded in full.
     */
    public function test_full_deposit_is_refunded_when_there_are_no_deductions(): void
    {
        $context = $this->createContext(10000);

        $settlement = app(SecurityDepositService::class)->settle(
            $context['lease'],
            '2026-08-11',
            'REF-2026-000002'
        );

        $this->assertSame(0, $settlement->deduction_amount);
        $this->assertSame(10000, $settlement->refund_amount);
        $this->assertSame(0, $settlement->tenant_debt_amount);
        $this->assertSame(
            0,
            $context['account']->fresh()->balance()
        );
    }

    /**
     * Final settlement cannot be performed twice.
     */
    public function test_security_deposit_cannot_be_settled_twice(): void
    {
        $context = $this->createContext();

        app(SecurityDepositService::class)->settle(
            $context['lease'],
            '2026-08-11'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Security deposit has already been settled for this Lease.'
        );

        app(SecurityDepositService::class)->settle(
            $context['lease'],
            '2026-08-12'
        );
    }
}
