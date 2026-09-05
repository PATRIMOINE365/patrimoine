<?php

namespace App\Services\LeaseTermination;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\TenantFundAccount;
use RuntimeException;

/**
 * Calculate the authoritative financial settlement position for a Lease
 * undergoing the V1.0.5 termination workflow.
 *
 * IMPORTANT:
 *
 * This service is deliberately READ ONLY.
 *
 * It does not:
 * - consume Rent Reserve;
 * - consume Consumable Advance;
 * - apply Security Deposit;
 * - record Security Deposit deductions;
 * - create Tenant debt;
 * - issue refunds;
 * - create Withdrawals;
 * - post Journal entries;
 * - complete the Lease termination.
 *
 * Those remain explicit operational workflows. The settlement summary only
 * tells the operator what is currently unresolved.
 */
final class LeaseTerminationSettlementService
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(Lease $lease): array
    {
        $lease = Lease::query()
            ->with([
                'tenant',
                'unit.building',
                'tenantFundAccounts.transactions',
                'securityDepositDeductions',
                'securityDepositSettlement',
            ])
            ->findOrFail($lease->id);

        if (
            ! in_array(
                $lease->status,
                [
                    'notice',
                    'terminated',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Lease termination settlement is available only for a Lease under termination notice or already Terminated.'
            );
        }

        /*
         * Invoice::outstandingAmount() is Patrimoine's authoritative
         * receivable calculation.
         *
         * Cancelled Invoices are excluded because they no longer represent an
         * operational Tenant obligation.
         */
        $invoices = Invoice::query()
            ->where(
                'lease_id',
                $lease->id
            )
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->orderBy(
                'issue_date'
            )
            ->orderBy('id')
            ->get();

        $rentOutstanding =
            (int) $invoices
                ->where(
                    'type',
                    'rent'
                )
                ->sum(
                    fn (Invoice $invoice): int =>
                        $invoice->outstandingAmount()
                );

        $securityDepositDebtOutstanding =
            (int) $invoices
                ->where(
                    'type',
                    'security_deposit_debt'
                )
                ->sum(
                    fn (Invoice $invoice): int =>
                        $invoice->outstandingAmount()
                );

        $otherOutstanding =
            (int) $invoices
                ->reject(
                    fn (Invoice $invoice): bool =>
                        in_array(
                            $invoice->type,
                            [
                                'rent',
                                'security_deposit_debt',
                            ],
                            true
                        )
                )
                ->sum(
                    fn (Invoice $invoice): int =>
                        $invoice->outstandingAmount()
                );

        $outstandingDebt =
            $rentOutstanding
            + $securityDepositDebtOutstanding
            + $otherOutstanding;

        /*
         * Tenant-held money is derived exclusively from the Lease-specific
         * fund ledgers. Contractual Lease amounts must never be substituted
         * for actual current balances.
         */
        $fundBalances = [
            'rent_reserve' => 0,
            'consumable_advance' => 0,
            'security_deposit' => 0,
        ];

        $otherTenantFunds = [];

        foreach (
            $lease->tenantFundAccounts
            as $account
        ) {
            $balance =
                (int) $account->balance();

            if (
                array_key_exists(
                    $account->type,
                    $fundBalances
                )
            ) {
                $fundBalances[
                    $account->type
                ] += $balance;

                continue;
            }

            /*
             * Future/new balance-maintaining Tenant funds must remain visible
             * rather than silently disappearing from termination settlement.
             */
            $otherTenantFunds[] = [
                'account_id' =>
                    (int) $account->id,

                'type' =>
                    $account->type,

                'status' =>
                    $account->status,

                'balance' =>
                    $balance,
            ];
        }

        $otherTenantFundsBalance =
            (int) collect(
                $otherTenantFunds
            )->sum(
                'balance'
            );

        $deductionTotal =
            (int) $lease
                ->securityDepositDeductions
                ->sum(
                    'amount'
                );

        /*
         * Deductions are currently explicit close-out charges but are not
         * themselves treated here as already-posted fund movements.
         *
         * Their covered/uncovered position is therefore presented separately.
         * A later operational workflow must make the corresponding financial
         * movement before termination can complete.
         */
        $securityDepositHeld =
            $fundBalances[
                'security_deposit'
            ];

        /*
         * V1.0.50: once the deposit has been settled, the deductions it
         * was settled against are resolved — either taken out of the
         * deposit (deduction_amount) or turned into a debt invoice
         * (tenant_debt_amount), which the outstanding-debt figure above
         * already counts.
         *
         * Comparing deductions against the deposit still HELD was right
         * only before settlement. Afterwards the held balance is zero
         * precisely because the deposit was applied, so every deduction
         * read as uncovered and no lease with a deduction could ever
         * complete its termination.
         */
        $settlement =
            $lease->securityDepositSettlement;

        $deductionsSettled =
            $settlement === null
                ? 0
                : (int) $settlement->deduction_amount
                    + (int) $settlement->tenant_debt_amount;

        $deductionsCoveredByDeposit =
            min(
                $deductionTotal,
                $deductionsSettled
                + max(
                    0,
                    $securityDepositHeld
                )
            );

        $uncoveredSecurityDepositDeductions =
            max(
                0,
                $deductionTotal
                - $deductionsCoveredByDeposit
            );

        /*
         * Do NOT automatically net Tenant-held money against outstanding debt.
         *
         * V1.0.5 explicitly requires Rent Advance / Rent Reserve /
         * Security Deposit application to remain manual financial actions.
         */
        $amountStillOwedByTenant =
            $outstandingDebt
            + $uncoveredSecurityDepositDeductions;

        /*
         * This is the amount presently held for the Tenant before explicit
         * settlement operations.
         *
         * Security Deposit deductions are displayed separately because the
         * actual ledger movement must be posted by the appropriate workflow.
         */
        $totalTenantFundsHeld =
            $fundBalances[
                'rent_reserve'
            ]
            + $fundBalances[
                'consumable_advance'
            ]
            + $securityDepositHeld
            + $otherTenantFundsBalance;

        /*
         * Refundable amount is intentionally conservative.
         *
         * It represents held funds that would remain refundable after the
         * recorded Security Deposit deduction obligation, without pretending
         * that debt or deductions have already been operationally settled.
         */
        $potentialRefundableAmount =
            $fundBalances[
                'rent_reserve'
            ]
            + $fundBalances[
                'consumable_advance'
            ]
            + max(
                0,
                $securityDepositHeld
                - $deductionTotal
            )
            + $otherTenantFundsBalance;

        $blockers = [];

        if ($outstandingDebt > 0) {
            $blockers[] = [
                'code' =>
                    'outstanding_debt',

                'amount' =>
                    $outstandingDebt,

                'message' =>
                    'The Tenant still has outstanding Lease debt.',
            ];
        }

        if (
            $uncoveredSecurityDepositDeductions
            > 0
        ) {
            $blockers[] = [
                'code' =>
                    'uncovered_security_deposit_deductions',

                'amount' =>
                    $uncoveredSecurityDepositDeductions,

                'message' =>
                    'Security Deposit deductions exceed the currently held Security Deposit.',
            ];
        }

        if (
            $fundBalances[
                'rent_reserve'
            ] > 0
        ) {
            $blockers[] = [
                'code' =>
                    'rent_reserve_held',

                'amount' =>
                    $fundBalances[
                        'rent_reserve'
                    ],

                'message' =>
                    'Rent Reserve remains held for the Tenant.',
            ];
        }

        if (
            $fundBalances[
                'consumable_advance'
            ] > 0
        ) {
            $blockers[] = [
                'code' =>
                    'consumable_advance_held',

                'amount' =>
                    $fundBalances[
                        'consumable_advance'
                    ],

                'message' =>
                    'Consumable Advance remains held for the Tenant.',
            ];
        }

        if ($securityDepositHeld > 0) {
            $blockers[] = [
                'code' =>
                    'security_deposit_held',

                'amount' =>
                    $securityDepositHeld,

                'message' =>
                    'Security Deposit remains held for the Tenant.',
            ];
        }

        if ($otherTenantFundsBalance > 0) {
            $blockers[] = [
                'code' =>
                    'other_tenant_funds_held',

                'amount' =>
                    $otherTenantFundsBalance,

                'message' =>
                    'Other Tenant funds remain unresolved.',
            ];
        }

        return [
            'lease' => [
                'id' =>
                    (int) $lease->id,

                'status' =>
                    $lease->status,

                'termination_notice_date' =>
                    $lease
                        ->termination_notice_date
                        ?->toDateString(),

                'termination_date' =>
                    $lease
                        ->termination_date
                        ?->toDateString(),

                'tenant_id' =>
                    (int) $lease->tenant_id,

                'tenant' =>
                    $lease->tenant?->name
                    ?? $lease->tenant?->legal_name,

                'building' =>
                    $lease
                        ->unit
                        ?->building
                        ?->name,

                'unit' =>
                    $lease
                        ->unit
                        ?->name,
            ],

            'debt' => [
                'rent_outstanding' =>
                    $rentOutstanding,

                'security_deposit_debt_outstanding' =>
                    $securityDepositDebtOutstanding,

                'other_outstanding' =>
                    $otherOutstanding,

                'total_outstanding' =>
                    $outstandingDebt,
            ],

            'funds' => [
                'rent_reserve_remaining' =>
                    $fundBalances[
                        'rent_reserve'
                    ],

                'consumable_advance_remaining' =>
                    $fundBalances[
                        'consumable_advance'
                    ],

                'security_deposit_held' =>
                    $securityDepositHeld,

                'other_tenant_funds' =>
                    $otherTenantFunds,

                'other_tenant_funds_balance' =>
                    $otherTenantFundsBalance,

                'total_held' =>
                    $totalTenantFundsHeld,
            ],

            'security_deposit' => [
                'held' =>
                    $securityDepositHeld,

                'deductions' =>
                    $lease
                        ->securityDepositDeductions
                        ->map(
                            fn ($deduction): array => [
                                'id' =>
                                    (int) $deduction->id,

                                'description' =>
                                    $deduction->description,

                                'amount' =>
                                    (int) $deduction->amount,

                                'deduction_date' =>
                                    $deduction
                                        ->deduction_date
                                        ?->toDateString(),

                                'reference' =>
                                    $deduction->reference,

                                'notes' =>
                                    $deduction->notes,
                            ]
                        )
                        ->values()
                        ->all(),

                'deduction_total' =>
                    $deductionTotal,

                'settled' =>
                    $settlement !== null,

                'settlement_date' =>
                    $settlement
                        ?->settlement_date
                        ?->toDateString(),

                'deductions_settled' =>
                    $deductionsSettled,

                'deductions_covered_by_held_deposit' =>
                    $deductionsCoveredByDeposit,

                'uncovered_deductions' =>
                    $uncoveredSecurityDepositDeductions,

                'potential_remaining_after_deductions' =>
                    max(
                        0,
                        $securityDepositHeld
                        - $deductionTotal
                    ),
            ],

            'settlement' => [
                'amount_still_owed_by_tenant' =>
                    $amountStillOwedByTenant,

                'potential_refundable_amount' =>
                    $potentialRefundableAmount,

                'blockers' =>
                    $blockers,

                'can_complete' =>
                    count($blockers) === 0,
            ],
        ];
    }
}
