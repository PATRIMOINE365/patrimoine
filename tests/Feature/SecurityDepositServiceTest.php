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
 * Verifies Patrimoine's Security Deposit settlement rules.
 */
class SecurityDepositServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a Lease with a funded Security Deposit account.
     *
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount
     * }
     */
    private function createContext(
        int $depositAmount = 10000
    ): array {
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

        return compact(
            'lease',
            'account'
        );
    }

    /**
     * Remaining deposit is refunded after itemized deductions.
     */
    public function test_remaining_deposit_is_refunded(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,

            'description' => 'Damaged lock',

            'amount' => 3000,

            'deduction_date' => '2026-08-10',
        ]);

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $context['lease'],
                '2026-08-11'
            );

        $this->assertSame(
            10000,
            $settlement->deposit_amount
        );

        $this->assertSame(
            3000,
            $settlement->deduction_amount
        );

        $this->assertSame(
            7000,
            $settlement->refund_amount
        );

        $this->assertSame(
            0,
            $settlement->tenant_debt_amount
        );

        $this->assertSame(
            sprintf(
                'SDV-%06d',
                $settlement->id
            ),
            $settlement->refund_voucher_number
        );

        $this->assertNull(
            $settlement->debt_invoice_id
        );

        /*
         * After deduction and refund, no Security Deposit funds remain held.
         */
        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );

        $this->assertDatabaseHas(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' => $context['account']->id,

                'category' => 'refund',

                'amount' => 7000,

                'reference' => $settlement
                    ->refund_voucher_number,
            ]
        );
    }

    /**
     * Deductions exceeding the available deposit create tenant debt.
     */
    public function test_excess_deductions_create_tenant_debt(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,

            'description' => 'Major repairs',

            'amount' => 13000,

            'deduction_date' => '2026-08-10',
        ]);

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $context['lease'],
                '2026-08-11'
            );

        $this->assertSame(
            10000,
            $settlement->deposit_amount
        );

        $this->assertSame(
            13000,
            $settlement->deduction_amount
        );

        $this->assertSame(
            0,
            $settlement->refund_amount
        );

        $this->assertSame(
            3000,
            $settlement->tenant_debt_amount
        );

        $settlement->refresh();

        $this->assertNotNull(
            $settlement->debt_invoice_id
        );

        $debtInvoice =
            $settlement
                ->debtInvoice()
                ->firstOrFail();

        $this->assertSame(
            'security_deposit_debt',
            $debtInvoice->type
        );

        $this->assertSame(
            3000,
            $debtInvoice->total_amount
        );

        $this->assertSame(
            3000,
            $debtInvoice->outstandingAmount()
        );

        $this->assertSame(
            'issued',
            $debtInvoice->status
        );

        $this->assertSame(
            sprintf(
                'SDD-%06d',
                $settlement->id
            ),
            $debtInvoice->invoice_number
        );

        $this->assertSame(
            sprintf(
                'SDV-%06d',
                $settlement->id
            ),
            $settlement->refund_voucher_number
        );

        /*
         * The entire available deposit is consumed, but the fund account
         * itself must never become negative.
         */
        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    /**
     * A deposit with no deductions is refunded in full.
     */
    public function test_full_deposit_is_refunded_when_there_are_no_deductions(): void
    {
        $context =
            $this->createContext(
                10000
            );

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $context['lease'],
                '2026-08-11'
            );

        $this->assertSame(
            0,
            $settlement->deduction_amount
        );

        $this->assertSame(
            10000,
            $settlement->refund_amount
        );

        $this->assertSame(
            0,
            $settlement->tenant_debt_amount
        );

        $this->assertSame(
            sprintf(
                'SDV-%06d',
                $settlement->id
            ),
            $settlement->refund_voucher_number
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    /**
     * A voucher number is generated even when no refund is payable.
     *
     * The document represents the final Security Deposit close-out, not merely
     * an outgoing cash transaction.
     */
    public function test_settlement_voucher_number_exists_when_no_refund_is_due(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,

            'description' => 'Repairs',

            'amount' => 10000,

            'deduction_date' => '2026-08-10',
        ]);

        $settlement =
            app(
                SecurityDepositService::class
            )->settle(
                $context['lease'],
                '2026-08-11'
            );

        $this->assertSame(
            0,
            $settlement->refund_amount
        );

        $this->assertSame(
            0,
            $settlement->tenant_debt_amount
        );

        $this->assertSame(
            sprintf(
                'SDV-%06d',
                $settlement->id
            ),
            $settlement->refund_voucher_number
        );
    }

    /**
     * Final settlement cannot be performed twice.
     */
    public function test_security_deposit_cannot_be_settled_twice(): void
    {
        $context =
            $this->createContext();

        app(
            SecurityDepositService::class
        )->settle(
            $context['lease'],
            '2026-08-11'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Security deposit has already been settled for this Lease.'
        );

        app(
            SecurityDepositService::class
        )->settle(
            $context['lease'],
            '2026-08-12'
        );
    }
}
