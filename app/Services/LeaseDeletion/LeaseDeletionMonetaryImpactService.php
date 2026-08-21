<?php

namespace App\Services\LeaseDeletion;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\TenantFundAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10B2
 *
 * Read-only monetary impact calculation for destructive Lease deletion.
 *
 * This service answers:
 *
 * "What monetary operational state belongs to this Lease and would have
 * to disappear or be neutralized for the Lease to become never-existed?"
 *
 * It MUST NOT:
 * - mutate operational records;
 * - delete records;
 * - alter balances;
 * - create or reverse Journal entries.
 */
class LeaseDeletionMonetaryImpactService
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(Lease $lease): array
    {
        $unknown = [];

        $invoiceImpact =
            $this->invoiceImpact(
                $lease->id,
                $unknown
            );

        $paymentImpact =
            $this->paymentImpact(
                $lease->id,
                $unknown
            );

        $tenantFundImpact =
            $this->tenantFundImpact(
                $lease->id,
                $unknown
            );

        $ownerImpact =
            $this->ownerImpact(
                $lease->id,
                $unknown
            );

        $securityDepositImpact =
            $this->securityDepositImpact(
                $lease->id
            );

        return [
            'lease_id' => (int) $lease->id,

            'invoices' => $invoiceImpact,

            'payments' => $paymentImpact,

            'tenant_funds' => $tenantFundImpact,

            'owner' => $ownerImpact,

            'security_deposit' =>
                $securityDepositImpact,

            'unknown' => $unknown,

            /*
             * Monetary impact is classified only when every amount can be
             * derived from an authoritative operational source.
             *
             * This does NOT mean destructive deletion is authorized.
             */
            'fully_classified' =>
                $unknown === [],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function invoiceImpact(
        int $leaseId,
        array &$unknown
    ): array {
        if (! Schema::hasTable('invoices')) {
            return [
                'count' => 0,
                'ids' => [],
                'total' => 0,
                'paid' => 0,
                'outstanding' => 0,
                'settlement' => [
                    'payment_allocations' => 0,
                    'rent_reserve' => 0,
                    'consumable_advance' => 0,
                    'security_deposit' => 0,
                ],
            ];
        }

        $invoices =
            Invoice::query()
                ->where(
                    'lease_id',
                    $leaseId
                )
                ->orderBy('id')
                ->get();

        $total = 0;
        $paid = 0;
        $outstanding = 0;

        $settlement = [
            'payment_allocations' => 0,
            'rent_reserve' => 0,
            'consumable_advance' => 0,
            'security_deposit' => 0,
        ];

        $rows = [];

        foreach ($invoices as $invoice) {
            try {
                $paymentPaid =
                    $invoice->paymentPaidAmount();

                $reservePaid =
                    $invoice->reservePaidAmount();

                $advancePaid =
                    $invoice->advancePaidAmount();

                $securityDepositPaid =
                    $invoice
                        ->securityDepositAppliedAmount();

                $invoicePaid =
                    $invoice->paidAmount();

                $invoiceOutstanding =
                    $invoice->outstandingAmount();
            } catch (\Throwable $exception) {
                $unknown[] = [
                    'type' =>
                        'invoice_monetary_calculation',

                    'invoice_id' =>
                        (int) $invoice->id,

                    'reason' =>
                        'Invoice monetary impact could not be calculated using Patrimoine authoritative model methods: '
                        . $exception->getMessage(),
                ];

                continue;
            }

            $invoiceTotal =
                (int) $invoice->total_amount;

            $total += $invoiceTotal;
            $paid += $invoicePaid;
            $outstanding +=
                $invoiceOutstanding;

            $settlement[
                'payment_allocations'
            ] += $paymentPaid;

            $settlement[
                'rent_reserve'
            ] += $reservePaid;

            $settlement[
                'consumable_advance'
            ] += $advancePaid;

            $settlement[
                'security_deposit'
            ] += $securityDepositPaid;

            $rows[] = [
                'id' =>
                    (int) $invoice->id,

                'type' =>
                    $invoice->type,

                'status' =>
                    $invoice->status,

                'total' =>
                    $invoiceTotal,

                'paid' =>
                    $invoicePaid,

                'outstanding' =>
                    $invoiceOutstanding,

                'settlement' => [
                    'payment_allocations' =>
                        $paymentPaid,

                    'rent_reserve' =>
                        $reservePaid,

                    'consumable_advance' =>
                        $advancePaid,

                    'security_deposit' =>
                        $securityDepositPaid,
                ],
            ];
        }

        return [
            'count' =>
                $invoices->count(),

            'ids' =>
                $invoices
                    ->pluck('id')
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->all(),

            'rows' =>
                $rows,

            'total' =>
                $total,

            'paid' =>
                $paid,

            'outstanding' =>
                $outstanding,

            'settlement' =>
                $settlement,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function paymentImpact(
        int $leaseId,
        array &$unknown
    ): array {
        if (! Schema::hasTable('payments')) {
            return [
                'count' => 0,
                'total' => 0,
            ];
        }

        if (
            ! Schema::hasColumn(
                'payments',
                'amount'
            )
        ) {
            $unknown[] = [
                'type' => 'payment_amount_schema',
                'reason' =>
                    'Payments do not expose amount.',
            ];

            return [
                'count' => 0,
                'total' => 0,
            ];
        }

        $payments =
            DB::table('payments')
                ->where('lease_id', $leaseId)
                ->orderBy('id')
                ->get();

        return [
            'count' => $payments->count(),

            'ids' =>
                $payments
                    ->pluck('id')
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->all(),

            'total' =>
                (int) $payments->sum('amount'),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function tenantFundImpact(
        int $leaseId,
        array &$unknown
    ): array {
        if (
            ! Schema::hasTable(
                'tenant_fund_accounts'
            )
        ) {
            return [
                'accounts' => [],
                'credits' => 0,
                'debits' => 0,
                'total_balance' => 0,
            ];
        }

        $accounts =
            TenantFundAccount::query()
                ->where(
                    'lease_id',
                    $leaseId
                )
                ->orderBy('id')
                ->get();

        $result = [];
        $totalCredits = 0;
        $totalDebits = 0;
        $totalBalance = 0;

        foreach ($accounts as $account) {
            try {
                $credits =
                    $account->creditedAmount();

                $debits =
                    $account->debitedAmount();

                $balance =
                    $account->balance();
            } catch (\Throwable $exception) {
                $unknown[] = [
                    'type' =>
                        'tenant_fund_monetary_calculation',

                    'account_id' =>
                        (int) $account->id,

                    'reason' =>
                        'Tenant Fund monetary impact could not be calculated using Patrimoine authoritative ledger methods: '
                        . $exception->getMessage(),
                ];

                continue;
            }

            $result[] = [
                'id' =>
                    (int) $account->id,

                'type' =>
                    $account->type,

                'status' =>
                    $account->status,

                'credits' =>
                    $credits,

                'debits' =>
                    $debits,

                'balance' =>
                    $balance,
            ];

            $totalCredits +=
                $credits;

            $totalDebits +=
                $debits;

            $totalBalance +=
                $balance;
        }

        return [
            'accounts' =>
                $result,

            'credits' =>
                $totalCredits,

            'debits' =>
                $totalDebits,

            'total_balance' =>
                $totalBalance,
        ];
    }

    /**
     * Read-only Security Deposit operational monetary history.
     *
     * These figures are reported independently from the current
     * TenantFundAccount balance so deletion impact can explain both:
     *
     * - what has happened historically; and
     * - what is still held now.
     *
     * @return array<string, mixed>
     */
    private function securityDepositImpact(
        int $leaseId
    ): array {
        $applications =
            Schema::hasTable(
                'security_deposit_applications'
            )
                ? DB::table(
                    'security_deposit_applications'
                )
                    ->where(
                        'lease_id',
                        $leaseId
                    )
                    ->get()
                : collect();

        $deductions =
            Schema::hasTable(
                'security_deposit_deductions'
            )
                ? DB::table(
                    'security_deposit_deductions'
                )
                    ->where(
                        'lease_id',
                        $leaseId
                    )
                    ->get()
                : collect();

        $settlements =
            Schema::hasTable(
                'security_deposit_settlements'
            )
                ? DB::table(
                    'security_deposit_settlements'
                )
                    ->where(
                        'lease_id',
                        $leaseId
                    )
                    ->get()
                : collect();

        return [
            'applications' => [
                'count' =>
                    $applications->count(),

                'amount' =>
                    (int) $applications
                        ->sum('amount'),
            ],

            'deductions' => [
                'count' =>
                    $deductions->count(),

                'amount' =>
                    (int) $deductions
                        ->sum('amount'),
            ],

            'settlements' => [
                'count' =>
                    $settlements->count(),

                'deposit_amount' =>
                    (int) $settlements
                        ->sum(
                            'deposit_amount'
                        ),

                'deduction_amount' =>
                    (int) $settlements
                        ->sum(
                            'deduction_amount'
                        ),

                'refund_amount' =>
                    (int) $settlements
                        ->sum(
                            'refund_amount'
                        ),

                'tenant_debt_amount' =>
                    (int) $settlements
                        ->sum(
                            'tenant_debt_amount'
                        ),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $unknown
     *
     * @return array<string, mixed>
     */
    private function ownerImpact(
        int $leaseId,
        array &$unknown
    ): array {
        if (
            ! Schema::hasTable(
                'owner_transactions'
            )
        ) {
            return [
                'count' => 0,
                'transaction_amount' => 0,
                'payout_allocated_amount' => 0,
                'shared_payouts' => [],
            ];
        }

        if (
            ! Schema::hasColumn(
                'owner_transactions',
                'amount'
            )
        ) {
            $unknown[] = [
                'type' =>
                    'owner_transaction_amount_schema',

                'reason' =>
                    'Owner Transactions do not expose amount.',
            ];

            return [
                'count' => 0,
                'transaction_amount' => 0,
                'payout_allocated_amount' => 0,
                'shared_payouts' => [],
            ];
        }

        $transactions =
            DB::table(
                'owner_transactions'
            )
                ->where(
                    'lease_id',
                    $leaseId
                )
                ->orderBy('id')
                ->get();

        $transactionIds =
            $transactions
                ->pluck('id')
                ->map(
                    fn ($id) => (int) $id
                )
                ->all();

        $allocatedAmount = 0;
        $sharedPayouts = [];

        if (
            $transactionIds !== []
            && Schema::hasTable(
                'owner_payout_allocations'
            )
        ) {
            $hasTransactionId =
                Schema::hasColumn(
                    'owner_payout_allocations',
                    'owner_transaction_id'
                );

            $hasAmount =
                Schema::hasColumn(
                    'owner_payout_allocations',
                    'amount'
                );

            if (
                ! $hasTransactionId
                || ! $hasAmount
            ) {
                $unknown[] = [
                    'type' =>
                        'owner_payout_amount_schema',

                    'reason' =>
                        'Owner payout allocation amounts cannot be calculated safely.',
                ];
            } else {
                $allocations =
                    DB::table(
                        'owner_payout_allocations'
                    )
                        ->whereIn(
                            'owner_transaction_id',
                            $transactionIds
                        )
                        ->get();

                $allocatedAmount =
                    (int) $allocations
                        ->sum('amount');

                if (
                    Schema::hasColumn(
                        'owner_payout_allocations',
                        'owner_payout_id'
                    )
                ) {
                    foreach (
                        $allocations
                            ->pluck(
                                'owner_payout_id'
                            )
                            ->filter()
                            ->unique()
                        as $payoutId
                    ) {
                        $all =
                            DB::table(
                                'owner_payout_allocations'
                            )
                                ->where(
                                    'owner_payout_id',
                                    $payoutId
                                )
                                ->get();

                        $inside =
                            $all->filter(
                                fn ($row) =>
                                    in_array(
                                        (int) $row
                                            ->owner_transaction_id,
                                        $transactionIds,
                                        true
                                    )
                            );

                        $outside =
                            $all->reject(
                                fn ($row) =>
                                    in_array(
                                        (int) $row
                                            ->owner_transaction_id,
                                        $transactionIds,
                                        true
                                    )
                            );

                        if ($outside->isNotEmpty()) {
                            $sharedPayouts[] = [
                                'owner_payout_id' =>
                                    (int) $payoutId,

                                'lease_allocated_amount' =>
                                    (int) $inside
                                        ->sum('amount'),

                                'outside_allocated_amount' =>
                                    (int) $outside
                                        ->sum('amount'),
                            ];
                        }
                    }
                }
            }
        }

        $credits =
            (int) $transactions
                ->where(
                    'direction',
                    'credit'
                )
                ->sum('amount');

        $debits =
            (int) $transactions
                ->where(
                    'direction',
                    'debit'
                )
                ->sum('amount');

        return [
            'count' =>
                $transactions->count(),

            'ids' =>
                $transactionIds,

            'credits' =>
                $credits,

            'debits' =>
                $debits,

            /*
             * OwnerAccount balances use:
             *
             *     credits - debits
             *
             * This is therefore the exact Lease-specific contribution to
             * the Owner operational ledger before considering cross-Lease
             * payout entanglement.
             */
            'net_lease_effect' =>
                $credits - $debits,

            'payout_allocated_amount' =>
                $allocatedAmount,

            'shared_payouts' =>
                $sharedPayouts,
        ];
    }
}
