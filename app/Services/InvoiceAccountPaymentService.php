<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\OwnerTransaction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Services\Accounting\JournalReversalService;
use App\Services\Accounting\TenantExpenseSettlementJournalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V1.0.8: pays an Invoice from a tenant fund account, and cancels such
 * a payment again.
 *
 * Payment rules follow the account type's existing business rules:
 *
 * - Consumable Advance may pay rent invoices at any time;
 * - Rent Reserve may pay rent invoices only during termination notice;
 * - the Security Deposit never pays rent (settlement owns it);
 * - expense invoices may be paid from any active fund account.
 *
 * A cancellation never deletes anything: it records an opposite credit
 * transaction pointing at the payment it reverses, mirrors any owner
 * rent entitlement the payment released, and posts immutable Journal
 * reversals. Every balance involved is derived, so the books simply
 * read correctly again.
 */
class InvoiceAccountPaymentService
{
    public function __construct(
        private readonly ConsumableAdvanceService $consumableAdvance,
        private readonly RentReserveService $rentReserve,
        private readonly TenantExpenseSettlementJournalService $settlementJournal,
        private readonly JournalReversalService $journalReversals
    ) {
    }

    /**
     * Categories that represent an account payment against an Invoice.
     *
     * @var list<string>
     */
    public const PAYMENT_CATEGORIES = [
        'advance_consumption',
        'rent_consumption',
        'expense_settlement',
    ];

    /**
     * Pay all or part of an Invoice from a tenant fund account.
     */
    public function pay(
        Invoice $invoice,
        TenantFundAccount $account,
        int $amount,
        string $transactionDate
    ): TenantFundTransaction {
        if ($account->lease_id !== $invoice->lease_id) {
            throw new RuntimeException(
                __('business.invoice_payment.wrong_lease')
            );
        }

        if ($invoice->isExpenseInvoice()) {
            return $this->settleExpenseInvoice(
                $invoice,
                $account,
                $amount,
                $transactionDate
            );
        }

        if (! $invoice->isRentInvoice()) {
            throw new RuntimeException(
                __('business.invoice_payment.unsupported_invoice')
            );
        }

        /*
         * Rent invoices reuse the existing consumption services so all
         * of their business rules, owner entitlement release and
         * Journal postings apply unchanged.
         */
        return match ($account->type) {
            'consumable_advance' => $this->consumableAdvance->consume(
                $account,
                $invoice,
                $amount,
                $transactionDate
            ),

            'rent_reserve' => $this->rentReserve->consume(
                $account,
                $invoice,
                $amount,
                $transactionDate
            ),

            default => throw new RuntimeException(
                __('business.invoice_payment.account_cannot_pay_rent')
            ),
        };
    }

    /**
     * Settle all or part of an expense Invoice from a fund account.
     */
    private function settleExpenseInvoice(
        Invoice $invoice,
        TenantFundAccount $account,
        int $amount,
        string $transactionDate
    ): TenantFundTransaction {
        return DB::transaction(function () use (
            $invoice,
            $account,
            $amount,
            $transactionDate
        ): TenantFundTransaction {
            $account = TenantFundAccount::query()
                ->lockForUpdate()
                ->findOrFail($account->id);

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($account->status !== 'active') {
                throw new RuntimeException(
                    __('business.invoice_payment.account_closed')
                );
            }

            if ($amount <= 0) {
                throw new RuntimeException(
                    __('business.invoice_payment.amount_positive')
                );
            }

            if ($amount > $account->balance()) {
                throw new RuntimeException(
                    __('business.invoice_payment.insufficient_balance')
                );
            }

            if ($amount > $invoice->outstandingAmount()) {
                throw new RuntimeException(
                    __('business.invoice_payment.exceeds_invoice')
                );
            }

            $transaction = TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'invoice_id' => $invoice->id,
                'direction' => 'debit',
                'category' => 'expense_settlement',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'reference' => $invoice->invoice_number,
                'notes' => 'Expense Invoice paid from tenant fund account.',
            ]);

            /*
             * Ledger row and Journal posting are one business action.
             * Before the accounting cutover the posting is a no-op.
             */
            $this->settlementJournal->post(
                $transaction
            );

            $this->synchronizeInvoiceStatus(
                $invoice
            );

            return $transaction->refresh();
        });
    }

    /**
     * Cancel one account payment, reverting everything it recorded.
     */
    public function cancel(
        TenantFundTransaction $payment,
        string $reason,
        ?int $actorUserId = null
    ): TenantFundTransaction {
        return DB::transaction(function () use (
            $payment,
            $reason,
            $actorUserId
        ): TenantFundTransaction {
            $payment = TenantFundTransaction::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (
                $payment->direction !== 'debit'
                || ! in_array(
                    $payment->category,
                    self::PAYMENT_CATEGORIES,
                    true
                )
                || $payment->invoice_id === null
            ) {
                throw new RuntimeException(
                    __('business.invoice_payment.not_a_payment')
                );
            }

            if ($payment->isReversed()) {
                throw new RuntimeException(
                    __('business.invoice_payment.already_cancelled')
                );
            }

            $reason = trim($reason);

            if ($reason === '') {
                throw new RuntimeException(
                    __('business.invoice_payment.reason_required')
                );
            }

            /*
             * Rent consumptions release owner entitlement. Only payments
             * recorded since V1.0.8 tag those rows with their source
             * transaction; older consumptions cannot be safely unwound
             * and stay permanent.
             */
            $entitlements = OwnerTransaction::query()
                ->where('tenant_fund_transaction_id', $payment->id)
                ->where('category', 'rent_entitlement')
                ->where('direction', 'credit')
                ->lockForUpdate()
                ->get();

            if (
                $payment->category !== 'expense_settlement'
                && $entitlements->isEmpty()
            ) {
                throw new RuntimeException(
                    __('business.invoice_payment.historical_payment')
                );
            }

            $reversal = TenantFundTransaction::create([
                'tenant_fund_account_id' => $payment->tenant_fund_account_id,
                'invoice_id' => $payment->invoice_id,
                'direction' => 'credit',
                'category' => $payment->category,
                'amount' => $payment->amount,
                'transaction_date' => now()->toDateString(),
                'reference' => $payment->reference,
                'notes' => $reason,
                'reversal_of_transaction_id' => $payment->id,
            ]);

            foreach ($entitlements as $entitlement) {
                OwnerTransaction::create([
                    'owner_account_id' => $entitlement->owner_account_id,
                    'building_id' => $entitlement->building_id,
                    'unit_id' => $entitlement->unit_id,
                    'lease_id' => $entitlement->lease_id,
                    'invoice_id' => $entitlement->invoice_id,
                    'direction' => 'debit',
                    'category' => 'rent_entitlement',
                    'amount' => $entitlement->amount,
                    'transaction_date' => now()->toDateString(),
                    'reference' => $entitlement->reference,
                    'notes' => $reason,
                    'reversal_of_transaction_id' => $entitlement->id,
                    'tenant_fund_transaction_id' => $payment->id,
                ]);
            }

            $this->reverseJournalEntries(
                sourcePairs: array_merge(
                    [[TenantFundTransaction::class, $payment->id]],
                    $entitlements
                        ->map(fn (OwnerTransaction $entitlement): array => [
                            OwnerTransaction::class,
                            $entitlement->id,
                        ])
                        ->all()
                ),
                reason: $reason,
                actorUserId: $actorUserId
            );

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            $this->synchronizeInvoiceStatus(
                $invoice
            );

            return $reversal->refresh();
        });
    }

    /**
     * Post immutable Journal reversals for every entry the payment
     * produced. Entries posted before the accounting cutover do not
     * exist and are simply skipped.
     *
     * @param  array<int, array{0: string, 1: int}>  $sourcePairs
     */
    private function reverseJournalEntries(
        array $sourcePairs,
        string $reason,
        ?int $actorUserId
    ): void {
        foreach ($sourcePairs as [$sourceType, $sourceId]) {
            $entries = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->get();

            foreach ($entries as $entry) {
                $alreadyReversed = JournalEntry::query()
                    ->where('reversal_of_id', $entry->id)
                    ->exists();

                if (
                    $alreadyReversed
                    || $entry->isReversal()
                    || $entry->isInformational()
                ) {
                    continue;
                }

                $this->journalReversals->reverse(
                    $entry,
                    $reason,
                    $actorUserId
                );
            }
        }
    }

    /**
     * Keep the Invoice lifecycle synchronized with its derived balance.
     */
    private function synchronizeInvoiceStatus(
        Invoice $invoice
    ): void {
        $invoice->refresh();

        if ($invoice->status === 'cancelled') {
            return;
        }

        $invoice->update([
            'status' => match (true) {
                $invoice->paidAmount() === 0 => 'issued',
                $invoice->outstandingAmount() === 0 => 'paid',
                default => 'partial',
            },
        ]);
    }
}
