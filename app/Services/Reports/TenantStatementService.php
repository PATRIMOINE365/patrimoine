<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use Carbon\Carbon;
use RuntimeException;

/**
 * Consolidated tenant statement across all Leases belonging to a Party.
 */
class TenantStatementService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(
        Party $tenant,
        ?string $from = null,
        ?string $to = null
    ): array {
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        $tenant->load([
            'tenantLeases.unit.building',
        ]);

        $leaseIds = $tenant->tenantLeases
            ->pluck('id');

        $invoiceQuery = Invoice::query()
            ->whereIn('lease_id', $leaseIds)
            ->where('status', '!=', 'cancelled');

        $this->applyDateRange(
            $invoiceQuery,
            'issue_date',
            $fromDate,
            $toDate
        );

        $invoices = $invoiceQuery
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $paymentQuery = Payment::query()
            ->whereIn('lease_id', $leaseIds);

        $this->applyDateRange(
            $paymentQuery,
            'payment_date',
            $fromDate,
            $toDate
        );

        $payments = $paymentQuery
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $fundAccounts = TenantFundAccount::query()
            ->whereIn('lease_id', $leaseIds)
            ->get();

        return [
            'tenant' => [
                'id' => $tenant->id,
                'type' => $tenant->type,
                'name' => $tenant->name ?? $tenant->legal_name,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
            ],

            'period' => [
                'from' => $from,
                'to' => $to,
            ],

            'summary' => [
                /*
     * Preserve the existing generic totals as the complete tenant
     * receivable position for backward compatibility.
     */
                'invoiced' => (int) $invoices->sum('total_amount'),

                'settled' => (int) $invoices->sum(
                    fn (Invoice $invoice): int => $invoice->paidAmount()
                ),

                /*
     * V1.0.1 explicitly separates contractual rent from Security Deposit
     * close-out debt so non-rent receivables are never presented as rent.
     */
                'rent_outstanding' => (int) $invoices
                    ->where('type', 'rent')
                    ->sum(
                        fn (Invoice $invoice): int => $invoice->outstandingAmount()
                    ),

                'security_deposit_debt_outstanding' => (int) $invoices
                    ->where('type', 'security_deposit_debt')
                    ->sum(
                        fn (Invoice $invoice): int => $invoice->outstandingAmount()
                    ),

                'total_outstanding' => (int) $invoices->sum(
                    fn (Invoice $invoice): int => $invoice->outstandingAmount()
                ),

                'cash_received' => (int) $payments->sum('amount'),

                'rent_reserve_balance' => $this->fundBalance(
                    $fundAccounts,
                    'rent_reserve'
                ),

                'consumable_advance_balance' => $this->fundBalance(
                    $fundAccounts,
                    'consumable_advance'
                ),

                'security_deposit_balance' => $this->fundBalance(
                    $fundAccounts,
                    'security_deposit'
                ),
            ],

            'leases' => $tenant->tenantLeases
                ->map(fn ($lease): array => [
                'id' => $lease->id,
                'building' => $lease->unit->building->name,
                'unit' => $lease->unit->name,
                'status' => $lease->status,
                'start_date' => $lease->start_date->toDateString(),
                'end_date' => $lease->end_date?->toDateString(),
                'rent_amount' => $lease->rent_amount,
                ])
                ->values()
                ->all(),
            'invoices' => $invoices
                ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'lease_id' => $invoice->lease_id,
                'invoice_number' => $invoice->invoice_number,
                'type' => $invoice->type,
                'date' => $invoice->issue_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'amount' => $invoice->total_amount,
                'paid' => $invoice->paidAmount(),
                'outstanding' => $invoice->outstandingAmount(),
                'status' => $invoice->status,

                /*
                 * V1.0.8: active fund-account payments, newest first,
                 * so the browser can offer Pay / Cancel payment and
                 * the payment receipt.
                 */
                'account_payments' => $this->accountPayments($invoice),
                ])
                ->values()
                ->all(),

            'payments' => $payments
                ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'lease_id' => $payment->lease_id,
                'date' => $payment->payment_date->toDateString(),
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'reference' => $payment->reference,
                'allocated' => $payment->allocatedAmount(),
                'unallocated' => $payment->unallocatedAmount(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * V1.0.8: active fund-account payments applied to one Invoice.
     *
     * Cancelled payments (those referenced by a credit reversal row)
     * are excluded entirely; the browser only ever needs payments it
     * could still cancel.
     *
     * @return array<int, array<string, mixed>>
     */
    private function accountPayments(
        Invoice $invoice
    ): array {
        $transactions = $invoice
            ->tenantFundTransactions()
            ->whereIn('category', [
                'advance_consumption',
                'rent_consumption',
                'expense_settlement',
            ])
            ->with('account')
            ->get();

        $reversedIds = $transactions
            ->where('direction', 'credit')
            ->pluck('reversal_of_transaction_id')
            ->filter()
            ->all();

        return $transactions
            ->where('direction', 'debit')
            ->reject(
                fn ($payment): bool => in_array(
                    $payment->id,
                    $reversedIds,
                    true
                )
            )
            ->sortByDesc('id')
            ->values()
            ->map(
                fn ($payment): array => [
                    'id' => $payment->id,
                    'amount' => (int) $payment->amount,
                    'category' => $payment->category,
                    'transaction_date' => $payment
                        ->transaction_date
                        ->toDateString(),
                    'account_type' => $payment->account?->type,

                    /*
                     * Consumptions recorded before V1.0.8 released
                     * owner entitlement without tagging it and can
                     * never be safely unwound.
                     */
                    'cancellable' =>
                        $payment->category === 'expense_settlement'
                        || $payment
                            ->ownerEntitlements()
                            ->exists(),
                ]
            )
            ->all();
    }

    private function fundBalance(
        $accounts,
        string $type
    ): int {
        return (int) $accounts
            ->where('type', $type)
            ->sum(
                fn (TenantFundAccount $account): int => $account->balance()
            );
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                $value
            )->startOfDay();
        } catch (\Throwable) {
            throw new RuntimeException(
                'Report dates must use YYYY-MM-DD format.'
            );
        }
    }

    private function applyDateRange(
        $query,
        string $column,
        ?Carbon $from,
        ?Carbon $to
    ): void {
        if ($from !== null) {
            $query->whereDate(
                $column,
                '>=',
                $from->toDateString()
            );
        }

        if ($to !== null) {
            $query->whereDate(
                $column,
                '<=',
                $to->toDateString()
            );
        }
    }
}
