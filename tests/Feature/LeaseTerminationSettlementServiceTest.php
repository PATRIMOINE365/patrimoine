<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\LeaseTermination\LeaseTerminationSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LeaseTerminationSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private function lease(
        string $status = 'notice'
    ): Lease {
        $building = Building::create([
            'name' =>
                'Settlement Building',
        ]);

        $unit = Unit::create([
            'building_id' =>
                $building->id,

            'name' =>
                'Unit 9D',
        ]);

        $tenant = Party::create([
            'type' =>
                'person',

            'name' =>
                'Settlement Tenant',

            'phone' =>
                '0209000001',

            'email' =>
                'phase9d@example.test',
        ]);

        return Lease::create([
            'unit_id' =>
                $unit->id,

            'tenant_id' =>
                $tenant->id,

            'start_date' =>
                '2026-01-01',

            'rent_amount' =>
                5000,

            'status' =>
                $status,

            'termination_notice_date' =>
                $status === 'notice'
                    ? '2026-08-01'
                    : null,

            'termination_date' =>
                $status === 'notice'
                    ? '2026-08-31'
                    : null,

            'termination_final_rent_mode' =>
                $status === 'notice'
                    ? 'full'
                    : null,
        ]);
    }

    private function fund(
        Lease $lease,
        string $type,
        int $amount
    ): TenantFundAccount {
        $account =
            TenantFundAccount::create([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    $type,

                'status' =>
                    'active',
            ]);

        if ($amount > 0) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' =>
                    $account->id,

                'direction' =>
                    'credit',

                'category' =>
                    match ($type) {
                        'rent_reserve' =>
                            'reserve_funding',

                        'consumable_advance' =>
                            'advance_funding',

                        'security_deposit' =>
                            'deposit_funding',

                        default =>
                            'adjustment',
                    },

                'amount' =>
                    $amount,

                'transaction_date' =>
                    '2026-08-01',
            ]);
        }

        return $account;
    }

    private function invoice(
        Lease $lease,
        string $type,
        int $amount,
        string $status = 'issued'
    ): Invoice {
        return Invoice::create([
            'lease_id' =>
                $lease->id,

            'invoice_number' =>
                'INV-9D-'
                .$lease->id
                .'-'
                .$type
                .'-'
                .Invoice::query()->count(),

            'type' =>
                $type,

            'period_start' =>
                '2026-08-01',

            'period_end' =>
                '2026-08-31',

            'issue_date' =>
                '2026-08-01',

            'due_date' =>
                '2026-08-01',

            'status' =>
                $status,

            'total_amount' =>
                $amount,

            'vat_rate' =>
                0,

            'net_amount' =>
                $amount,

            'vat_amount' =>
                0,
        ]);
    }

    public function test_summary_uses_invoice_outstanding_and_lease_specific_fund_ledgers(): void
    {
        $lease =
            $this->lease();

        $this->invoice(
            $lease,
            'rent',
            5000
        );

        $this->fund(
            $lease,
            'rent_reserve',
            3000
        );

        $this->fund(
            $lease,
            'consumable_advance',
            2000
        );

        $this->fund(
            $lease,
            'security_deposit',
            7000
        );

        $summary =
            app(
                LeaseTerminationSettlementService::class
            )->calculate(
                $lease
            );

        $this->assertSame(
            5000,
            $summary[
                'debt'
            ][
                'total_outstanding'
            ]
        );

        $this->assertSame(
            3000,
            $summary[
                'funds'
            ][
                'rent_reserve_remaining'
            ]
        );

        $this->assertSame(
            2000,
            $summary[
                'funds'
            ][
                'consumable_advance_remaining'
            ]
        );

        $this->assertSame(
            7000,
            $summary[
                'funds'
            ][
                'security_deposit_held'
            ]
        );

        /*
         * Held money is NOT automatically offset against Lease debt.
         */
        $this->assertSame(
            5000,
            $summary[
                'settlement'
            ][
                'amount_still_owed_by_tenant'
            ]
        );

        $this->assertFalse(
            $summary[
                'settlement'
            ][
                'can_complete'
            ]
        );
    }

    public function test_cancelled_invoice_is_not_outstanding_debt(): void
    {
        $lease =
            $this->lease();

        $this->invoice(
            $lease,
            'rent',
            5000,
            'cancelled'
        );

        $summary =
            app(
                LeaseTerminationSettlementService::class
            )->calculate(
                $lease
            );

        $this->assertSame(
            0,
            $summary[
                'debt'
            ][
                'total_outstanding'
            ]
        );

        $this->assertTrue(
            $summary[
                'settlement'
            ][
                'can_complete'
            ]
        );
    }

    public function test_security_deposit_deductions_are_presented_without_mutating_fund_balance(): void
    {
        $lease =
            $this->lease();

        $account =
            $this->fund(
                $lease,
                'security_deposit',
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' =>
                $lease->id,

            'description' =>
                'Damage',

            'amount' =>
                3000,

            'deduction_date' =>
                '2026-08-20',
        ]);

        $summary =
            app(
                LeaseTerminationSettlementService::class
            )->calculate(
                $lease
            );

        $this->assertSame(
            10000,
            $summary[
                'security_deposit'
            ][
                'held'
            ]
        );

        $this->assertSame(
            3000,
            $summary[
                'security_deposit'
            ][
                'deduction_total'
            ]
        );

        $this->assertSame(
            7000,
            $summary[
                'security_deposit'
            ][
                'potential_remaining_after_deductions'
            ]
        );

        /*
         * Calculator is strictly read only.
         */
        $this->assertSame(
            10000,
            $account
                ->fresh()
                ->balance()
        );

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            1
        );
    }

    public function test_deductions_above_held_security_deposit_are_explicit_uncovered_obligation(): void
    {
        $lease =
            $this->lease();

        $this->fund(
            $lease,
            'security_deposit',
            5000
        );

        SecurityDepositDeduction::create([
            'lease_id' =>
                $lease->id,

            'description' =>
                'Repairs',

            'amount' =>
                8000,

            'deduction_date' =>
                '2026-08-20',
        ]);

        $summary =
            app(
                LeaseTerminationSettlementService::class
            )->calculate(
                $lease
            );

        $this->assertSame(
            3000,
            $summary[
                'security_deposit'
            ][
                'uncovered_deductions'
            ]
        );

        $this->assertSame(
            3000,
            $summary[
                'settlement'
            ][
                'amount_still_owed_by_tenant'
            ]
        );

        $codes =
            collect(
                $summary[
                    'settlement'
                ][
                    'blockers'
                ]
            )->pluck(
                'code'
            );

        $this->assertTrue(
            $codes->contains(
                'uncovered_security_deposit_deductions'
            )
        );
    }

    public function test_debt_and_tenant_funds_remain_separate_manual_settlement_positions(): void
    {
        $lease =
            $this->lease();

        $this->invoice(
            $lease,
            'rent',
            4000
        );

        $this->fund(
            $lease,
            'consumable_advance',
            10000
        );

        $summary =
            app(
                LeaseTerminationSettlementService::class
            )->calculate(
                $lease
            );

        /*
         * Even though enough Advance exists, no automatic consumption occurs.
         */
        $this->assertSame(
            4000,
            $summary[
                'settlement'
            ][
                'amount_still_owed_by_tenant'
            ]
        );

        $this->assertSame(
            10000,
            $summary[
                'funds'
            ][
                'consumable_advance_remaining'
            ]
        );

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            1
        );
    }

    public function test_fully_resolved_lease_has_no_blockers(): void
    {
        $lease =
            $this->lease();

        $summary =
            app(
                LeaseTerminationSettlementService::class
            )->calculate(
                $lease
            );

        $this->assertSame(
            [],
            $summary[
                'settlement'
            ][
                'blockers'
            ]
        );

        $this->assertTrue(
            $summary[
                'settlement'
            ][
                'can_complete'
            ]
        );
    }

    public function test_active_lease_cannot_use_termination_settlement_summary(): void
    {
        $lease =
            $this->lease(
                'active'
            );

        $this->expectException(
            RuntimeException::class
        );

        app(
            LeaseTerminationSettlementService::class
        )->calculate(
            $lease
        );
    }
}
