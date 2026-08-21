<?php

namespace App\Services\LeaseHistory;

use App\Models\AdjustmentVoucher;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\SecurityDepositApplication;
use App\Models\SecurityDepositDeduction;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundTransaction;
use App\Models\WithdrawalReceipt;
use Illuminate\Support\Collection;

/**
 * Build the canonical operational financial history for one Lease.
 *
 * Phase 6 deliberately uses operational records as the historical source
 * of truth. This allows pre-V1.0.5 Lease history to remain complete without
 * reconstructing historical Financial Journal entries.
 *
 * Journal data may later enrich post-cutover events, but Journal existence
 * is never required for an operational history event to appear.
 */
class LeaseFinancialHistoryService
{
    /**
     * Return one chronologically ordered financial history.
     *
     * @return array{
     *     lease: array<string, mixed>,
     *     events: list<array<string, mixed>>
     * }
     */
    public function generate(Lease $lease): array
    {
        $lease->loadMissing([
            'tenant',
            'unit.building',
        ]);

        $events = collect()
            ->concat(
                $this->invoiceEvents($lease)
            )
            ->concat(
                $this->paymentEvents($lease)
            )
            ->concat(
                $this->tenantFundEvents($lease)
            )
            ->concat(
                $this->withdrawalEvents(
                    $lease
                )
            )
            ->concat(
                $this->adjustmentEvents(
                    $lease
                )
            )
            ->concat(
                $this->securityDepositApplicationEvents(
                    $lease
                )
            )
            ->concat(
                $this->securityDepositDeductionEvents(
                    $lease
                )
            )
            ->concat(
                $this->securityDepositSettlementEvents(
                    $lease
                )
            );

        /*
         * One persisted financial consequence may be represented by both a
         * specialized operational record and its underlying Tenant Fund
         * movement.
         *
         * Specialized records are the canonical presentation whenever one
         * exists:
         *
         * - Security Deposit application;
         * - Withdrawal;
         * - Adjustment.
         *
         * The underlying TenantFundTransaction is still preserved in storage,
         * accounting and audit data but must not appear as a duplicate row.
         */
        $specializedFundTransactionIds =
            $this->specializedFundTransactionIds(
                $lease
            );

        $events = $events
            ->reject(
                fn (array $event): bool => $event['source_type']
                        === 'tenant_fund_transaction'
                    && isset(
                        $event['source_id']
                    )
                    && $specializedFundTransactionIds
                        ->contains(
                            (int) $event['source_id']
                        )
            )
            ->sort(
                function (
                    array $left,
                    array $right
                ): int {
                    $dateComparison =
                        strcmp(
                            (string) $left['occurred_on'],
                            (string) $right['occurred_on']
                        );

                    if ($dateComparison !== 0) {
                        return $dateComparison;
                    }

                    return strcmp(
                        (string) $left['sort_key'],
                        (string) $right['sort_key']
                    );
                }
            )
            ->values()
            ->map(
                function (array $event): array {
                    unset(
                        $event['sort_key']
                    );

                    return $event;
                }
            )
            ->all();

        return [
            'lease' => [
                'id' => (int) $lease->id,

                'tenant_id' => (int) $lease->tenant_id,

                'tenant' => $lease->tenant?->name
                    ?? $lease->tenant?->legal_name,

                'building' => $lease->unit?->building?->name,

                'unit' => $lease->unit?->name,

                'status' => $lease->status,

                'start_date' => $lease->start_date?->toDateString(),

                'end_date' => $lease->end_date?->toDateString(),
            ],

            'events' => $events,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function invoiceEvents(
        Lease $lease
    ): Collection {
        return Invoice::query()
            ->where(
                'lease_id',
                $lease->id
            )
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get()
            ->map(
                fn (Invoice $invoice): array => [
                    'event_type' => 'invoice',

                    'occurred_on' => $invoice->issue_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $invoice->issue_date
                            ->toDateString(),
                        10,
                        $invoice->id
                    ),

                    'source_type' => 'invoice',

                    'source_id' => (int) $invoice->id,

                    'reference' => $invoice->invoice_number,

                    'description' => $invoice->type,

                    'direction' => 'receivable',

                    'amount' => (int) $invoice->total_amount,

                    'payment_method' => null,

                    'fund_type' => null,

                    'invoice_id' => (int) $invoice->id,

                    'payment_id' => null,

                    'document' => [
                        'type' => 'invoice',

                        'endpoint' => '/api/invoices/'
                            .$invoice->id
                            .'/pdf',
                    ],

                    'metadata' => [
                        'invoice_type' => $invoice->type,

                        'due_date' => $invoice->due_date
                            ?->toDateString(),

                        'status' => $invoice->status,

                        'paid_amount' => $invoice->paidAmount(),

                        'outstanding_amount' => $invoice->outstandingAmount(),
                    ],
                ]
            );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function paymentEvents(
        Lease $lease
    ): Collection {
        return Payment::query()
            ->where(
                'lease_id',
                $lease->id
            )
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->map(
                fn (Payment $payment): array => [
                    'event_type' => 'payment',

                    'occurred_on' => $payment->payment_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $payment->payment_date
                            ->toDateString(),
                        20,
                        $payment->id
                    ),

                    'source_type' => 'payment',

                    'source_id' => (int) $payment->id,

                    'reference' => $payment->reference,

                    'description' => 'tenant_payment',

                    'direction' => 'in',

                    'amount' => (int) $payment->amount,

                    'payment_method' => $payment->payment_method,

                    'fund_type' => null,

                    'invoice_id' => null,

                    'payment_id' => (int) $payment->id,

                    'document' => [
                        'type' => 'receipt',

                        'endpoint' => '/api/payments/'
                            .$payment->id
                            .'/receipt',
                    ],

                    'metadata' => [
                        'allocated_amount' => $payment->allocatedAmount(),

                        'unallocated_amount' => $payment->unallocatedAmount(),

                        'cash_receiver_name' => $payment->cash_receiver_name
                            ?? $payment->collector_name,
                    ],
                ]
            );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function tenantFundEvents(
        Lease $lease
    ): Collection {
        return TenantFundTransaction::query()
            ->with([
                'account',
            ])
            ->whereHas(
                'account',
                fn ($query) => $query->where(
                    'lease_id',
                    $lease->id
                )
            )
            ->orderBy(
                'transaction_date'
            )
            ->orderBy('id')
            ->get()
            ->map(
                function (
                    TenantFundTransaction $transaction
                ): array {
                    return [
                        'event_type' => $this
                            ->fundEventType(
                                $transaction
                            ),

                        'occurred_on' => $transaction
                            ->transaction_date
                            ->toDateString(),

                        'sort_key' => $this->sortKey(
                            $transaction
                                ->transaction_date
                                ->toDateString(),
                            30,
                            $transaction->id
                        ),

                        'source_type' => 'tenant_fund_transaction',

                        'source_id' => (int) $transaction->id,

                        'reference' => $transaction->reference,

                        'description' => $transaction->category,

                        'direction' => $transaction->direction,

                        'amount' => (int) $transaction->amount,

                        'payment_method' => $transaction
                            ->getAttribute(
                                'payment_method'
                            ),

                        'fund_type' => $transaction
                            ->account?->type,

                        'invoice_id' => $transaction->invoice_id
                                ? (int) $transaction
                                    ->invoice_id
                                : null,

                        'payment_id' => $transaction->payment_id
                                ? (int) $transaction
                                    ->payment_id
                                : null,

                        'document' => null,

                        'metadata' => [
                            'category' => $transaction->category,

                            'notes' => $transaction->notes,

                            'tenant_fund_account_id' => (int) $transaction
                                ->tenant_fund_account_id,
                        ],
                    ];
                }
            );
    }

    /**
     * Represent Tenant Withdrawals by their specialized immutable Receipt
     * whenever one exists.
     *
     * The underlying TenantFundTransaction remains the accounting/ledger
     * source but is suppressed from presentation so one human Withdrawal
     * appears exactly once.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function withdrawalEvents(
        Lease $lease
    ): Collection {
        return WithdrawalReceipt::query()
            ->where(
                'lease_id',
                $lease->id
            )
            ->orderBy(
                'transaction_date'
            )
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    WithdrawalReceipt $receipt
                ): array => [
                    'event_type' => 'withdrawal',

                    'occurred_on' => $receipt
                        ->transaction_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $receipt
                            ->transaction_date
                            ->toDateString(),
                        35,
                        $receipt->id
                    ),

                    'source_type' => 'withdrawal_receipt',

                    'source_id' => (int) $receipt->id,

                    'reference' => $receipt->receipt_number,

                    'description' => 'tenant_withdrawal',

                    'direction' => 'out',

                    'amount' => (int) $receipt->amount,

                    'payment_method' => $receipt->payment_method,

                    'fund_type' => $receipt->fund_type,

                    'invoice_id' => null,

                    'payment_id' => null,

                    'document' => [
                        'type' => 'withdrawal_receipt',

                        'endpoint' => '/api/withdrawal-receipts/'
                            .$receipt->id
                            .'/pdf',
                    ],

                    'metadata' => [
                        'tenant_fund_transaction_id' => (int) $receipt
                            ->tenant_fund_transaction_id,

                        'tenant_fund_account_id' => (int) $receipt
                            ->tenant_fund_account_id,

                        'external_reference' => $receipt->reference,

                        'notes' => $receipt->notes,

                        'tenant_name' => $receipt->tenant_name,

                        'lease_label' => $receipt->lease_label,

                        'building_label' => $receipt->building_label,

                        'unit_label' => $receipt->unit_label,

                        'performed_by_user_id' => $receipt
                            ->performed_by_user_id
                            ? (int) $receipt->performed_by_user_id
                            : null,

                        'performed_by_name' => $receipt
                            ->performed_by_name,
                    ],
                ]
            );
    }

    /**
     * Represent Tenant fund Adjustments using the shared immutable Adjustment
     * Voucher.
     *
     * AdjustmentVoucher identifies its operational adjustment through:
     *
     *     source_type = TenantFundTransaction::class
     *     source_id   = tenant_fund_transactions.id
     *
     * We first determine the Tenant Fund transactions belonging to this Lease,
     * then select only vouchers whose source points to those transactions.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function adjustmentEvents(
        Lease $lease
    ): Collection {
        $transactionIds =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->whereHas(
                    'account',
                    fn ($query) => $query->where(
                        'lease_id',
                        $lease->id
                    )
                )
                ->pluck('id');

        if ($transactionIds->isEmpty()) {
            return collect();
        }

        return AdjustmentVoucher::query()
            ->where(
                'source_type',
                TenantFundTransaction::class
            )
            ->whereIn(
                'source_id',
                $transactionIds
            )
            ->orderBy(
                'adjustment_date'
            )
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    AdjustmentVoucher $voucher
                ): array => [
                    'event_type' => 'adjustment',

                    'occurred_on' => $voucher
                        ->adjustment_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $voucher
                            ->adjustment_date
                            ->toDateString(),
                        36,
                        $voucher->id
                    ),

                    'source_type' => 'adjustment_voucher',

                    'source_id' => (int) $voucher->id,

                    'reference' => $voucher->voucher_number,

                    'description' => 'tenant_adjustment',

                    'direction' => $voucher->difference > 0
                        ? 'increase'
                        : (
                            $voucher->difference < 0
                                ? 'decrease'
                                : 'none'
                        ),

                    /*
                     * Adjustment amount is the financial movement, therefore
                     * history presents the magnitude while metadata preserves
                     * its signed difference and final-balance semantics.
                     */
                    'amount' => abs(
                        (int) $voucher->difference
                    ),

                    'payment_method' => null,

                    'fund_type' => $voucher->account_type,

                    'invoice_id' => null,

                    'payment_id' => null,

                    'document' => [
                        'type' => 'adjustment_voucher',

                        'endpoint' => '/api/adjustment-vouchers/'
                            .$voucher->id
                            .'/pdf',
                    ],

                    'metadata' => [
                        'tenant_fund_transaction_id' => (int) $voucher
                            ->source_id,

                        'tenant_fund_account_id' => (int) $voucher
                            ->account_id,

                        'previous_balance' => (int) $voucher
                            ->previous_balance,

                        'corrected_balance' => (int) $voucher
                            ->corrected_balance,

                        'difference' => (int) $voucher->difference,

                        'reason' => $voucher->reason,

                        'entity_type' => $voucher->entity_type,

                        'entity_id' => $voucher->entity_id
                                ? (int) $voucher->entity_id
                                : null,

                        'entity_label' => $voucher->entity_label,

                        'account_label' => $voucher->account_label,

                        'performed_by_user_id' => $voucher
                            ->performed_by_user_id
                            ? (int) $voucher->performed_by_user_id
                            : null,

                        'performed_by_name' => $voucher
                            ->performed_by_name,
                    ],
                ]
            );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function securityDepositApplicationEvents(
        Lease $lease
    ): Collection {
        return SecurityDepositApplication::query()
            ->with([
                'invoice',
            ])
            ->where(
                'lease_id',
                $lease->id
            )
            ->orderBy(
                'application_date'
            )
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    SecurityDepositApplication $application
                ): array => [
                    'event_type' => 'security_deposit_application',

                    'occurred_on' => $application
                        ->application_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $application
                            ->application_date
                            ->toDateString(),
                        40,
                        $application->id
                    ),

                    'source_type' => 'security_deposit_application',

                    'source_id' => (int) $application->id,

                    'reference' => $application
                        ->invoice
                        ?->invoice_number,

                    'description' => 'security_deposit_applied',

                    'direction' => 'transfer',

                    'amount' => (int) $application->amount,

                    'payment_method' => null,

                    'fund_type' => 'security_deposit',

                    'invoice_id' => (int) $application
                        ->invoice_id,

                    'payment_id' => null,

                    'document' => null,

                    'metadata' => [
                        'tenant_fund_transaction_id' => (int) $application
                            ->tenant_fund_transaction_id,

                        'invoice_type' => $application
                            ->invoice
                            ?->type,

                        'notes' => $application->notes,
                    ],
                ]
            );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function securityDepositDeductionEvents(
        Lease $lease
    ): Collection {
        return SecurityDepositDeduction::query()
            ->where(
                'lease_id',
                $lease->id
            )
            ->orderBy(
                'deduction_date'
            )
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    SecurityDepositDeduction $deduction
                ): array => [
                    'event_type' => 'security_deposit_deduction',

                    'occurred_on' => $deduction
                        ->deduction_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $deduction
                            ->deduction_date
                            ->toDateString(),
                        50,
                        $deduction->id
                    ),

                    'source_type' => 'security_deposit_deduction',

                    'source_id' => (int) $deduction->id,

                    'reference' => $deduction->reference,

                    'description' => $deduction->description,

                    'direction' => 'debit',

                    'amount' => (int) $deduction->amount,

                    'payment_method' => null,

                    'fund_type' => 'security_deposit',

                    'invoice_id' => null,

                    'payment_id' => null,

                    'document' => null,

                    'metadata' => [
                        'notes' => $deduction->notes,
                    ],
                ]
            );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function securityDepositSettlementEvents(
        Lease $lease
    ): Collection {
        return SecurityDepositSettlement::query()
            ->where(
                'lease_id',
                $lease->id
            )
            ->orderBy(
                'settlement_date'
            )
            ->orderBy('id')
            ->get()
            ->map(
                fn (
                    SecurityDepositSettlement $settlement
                ): array => [
                    'event_type' => 'security_deposit_settlement',

                    'occurred_on' => $settlement
                        ->settlement_date
                        ->toDateString(),

                    'sort_key' => $this->sortKey(
                        $settlement
                            ->settlement_date
                            ->toDateString(),
                        60,
                        $settlement->id
                    ),

                    'source_type' => 'security_deposit_settlement',

                    'source_id' => (int) $settlement->id,

                    'reference' => $settlement
                        ->refund_voucher_number,

                    'description' => 'security_deposit_settlement',

                    'direction' => 'settlement',

                    'amount' => (int) $settlement
                        ->deposit_amount,

                    'payment_method' => $settlement
                        ->refund_payment_method,

                    'fund_type' => 'security_deposit',

                    'invoice_id' => null,

                    'payment_id' => null,

                    'document' => [
                        'type' => 'security_deposit_voucher',

                        'endpoint' => '/api/security-deposit-settlements/'
                            .$settlement->id
                            .'/voucher',
                    ],

                    'metadata' => [
                        'deposit_amount' => (int) $settlement
                            ->deposit_amount,

                        'deduction_amount' => (int) $settlement
                            ->deduction_amount,

                        'refund_amount' => (int) $settlement
                            ->refund_amount,

                        'tenant_debt_amount' => (int) $settlement
                            ->tenant_debt_amount,

                        'notes' => $settlement->notes,
                    ],
                ]
            );
    }

    /**
     * Tenant Fund transactions represented by a more specific business record.
     *
     * @return Collection<int, int>
     */
    private function specializedFundTransactionIds(
        Lease $lease
    ): Collection {
        $ids = collect();

        $ids->push(
            ...SecurityDepositApplication::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->pluck(
                    'tenant_fund_transaction_id'
                )
                ->filter()
                ->map(
                    fn ($id): int => (int) $id
                )
                ->all()
        );

        $ids->push(
            ...WithdrawalReceipt::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->pluck(
                    'tenant_fund_transaction_id'
                )
                ->filter()
                ->map(
                    fn ($id): int => (int) $id
                )
                ->all()
        );

        /*
         * Adjustment Voucher has no direct lease_id. Its source pair points
         * to the underlying TenantFundTransaction, whose account owns the
         * Lease context.
         */
        $leaseAdjustmentTransactionIds =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->whereHas(
                    'account',
                    fn ($query) => $query->where(
                        'lease_id',
                        $lease->id
                    )
                )
                ->pluck('id');

        if ($leaseAdjustmentTransactionIds->isNotEmpty()) {
            $ids->push(
                ...AdjustmentVoucher::query()
                    ->where(
                        'source_type',
                        TenantFundTransaction::class
                    )
                    ->whereIn(
                        'source_id',
                        $leaseAdjustmentTransactionIds
                    )
                    ->pluck(
                        'source_id'
                    )
                    ->filter()
                    ->map(
                        fn ($id): int => (int) $id
                    )
                    ->all()
            );
        }

        return $ids
            ->unique()
            ->values();
    }

    /**
     * Translate Tenant Fund ledger categories into stable history event types.
     */
    private function fundEventType(
        TenantFundTransaction $transaction
    ): string {
        return match (
            $transaction->category
        ) {
            'reserve_funding',
            'advance_funding',
            'security_deposit_funding',
            'deposit_funding' => 'fund_deposit',

            'rent_consumption' => 'rent_reserve_consumption',

            'advance_consumption' => 'advance_consumption',

            'withdrawal' => 'withdrawal',

            'adjustment' => 'adjustment',

            'deposit_deduction' => 'security_deposit_movement',

            default => 'fund_movement',
        };
    }

    /**
     * Stable chronological tie-breaker.
     */
    private function sortKey(
        string $date,
        int $priority,
        int $id
    ): string {
        return sprintf(
            '%s-%03d-%012d',
            $date,
            $priority,
            $id
        );
    }
}
