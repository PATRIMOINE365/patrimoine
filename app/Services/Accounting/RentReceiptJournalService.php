<?php

namespace App\Services\Accounting;

use App\Models\PaymentAllocation;
use RuntimeException;

/**
 * Mirrors post-cutover rent Payment allocations into the immutable
 * Financial Journal.
 *
 * Important accounting boundaries:
 *
 * - only money actually allocated to a rent Invoice is a rent receipt;
 * - unallocated tenant money is not a rent receipt;
 * - Tenant Fund classifications are separate accounting events;
 * - non-rent receivables such as Security Deposit debt are excluded;
 * - the debit asset account follows the operational Payment method;
 * - historical pre-cutover activity is represented only by opening balances.
 */
class RentReceiptJournalService
{
    public const TRANSACTION_TYPE =
        AccountingEventMap::EVENT_RENT_RECEIPT;

    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting
    ) {
    }

    public function postAllocation(
        PaymentAllocation $allocation
    ): void {
        /*
         * Runtime Journal integration must never create or initialize the
         * V1.0.5 accounting cutover itself.
         */
        if (! $this->runtimeGate->enabled()) {
            return;
        }

        $allocation->loadMissing([
            'payment',
            'invoice',
        ]);

        $payment =
            $allocation->payment;

        $invoice =
            $allocation->invoice;

        if ($payment === null) {
            throw new RuntimeException(
                'Rent receipt allocation has no Payment.'
            );
        }

        if ($invoice === null) {
            throw new RuntimeException(
                'Rent receipt allocation has no Invoice.'
            );
        }

        /*
         * Ordinary PaymentAllocation records can also settle non-rent
         * receivables. Only contractual rent is a rent-receipt event.
         */
        if (! $invoice->isRentInvoice()) {
            return;
        }

        $amount =
            (int) $allocation->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Rent receipt Journal amount must be greater than zero.'
            );
        }

        $paymentMethod =
            trim(
                (string) $payment->payment_method
            );

        if ($paymentMethod === '') {
            throw new RuntimeException(
                'Rent receipt Payment method is required.'
            );
        }

        /*
         * The asset side is contextual because Cash, Bank Transfer and
         * Mobile Payment each post to a different system account.
         */
        $assetAccount =
            $this->eventMap->paymentAsset(
                $paymentMethod
            );

        /*
         * Rent receipt is deliberately contextual in AccountingEventMap.
         *
         * The fixed accounting side is the reduction of Tenant Rent
         * Receivable. The map remains the authoritative source of that
         * permanent accounting semantic.
         */
        $mapping =
            $this->eventMap->contextual(
                self::TRANSACTION_TYPE
            );

        $receivableAccount =
            $this->fixedCreditAccount(
                $mapping
            );

        $this->journalPosting->post(
            [
                'journal_date' =>
                    $payment
                        ->payment_date
                        ->toDateString(),

                'transaction_type' =>
                    self::TRANSACTION_TYPE,

                'description' =>
                    'Rent receipt allocation #'
                    .$allocation->getKey(),

                'source_type' =>
                    PaymentAllocation::class,

                'source_id' =>
                    $allocation->getKey(),

                'idempotency_key' =>
                    self::idempotencyKey(
                        $allocation
                    ),

                'snapshot' => [
                    'payment_allocation_id' =>
                        $allocation->getKey(),

                    'payment_id' =>
                        $payment->getKey(),

                    'invoice_id' =>
                        $invoice->getKey(),

                    'lease_id' =>
                        $payment->lease_id,

                    'payment_method' =>
                        $paymentMethod,

                    'amount' =>
                        $amount,
                ],
            ],
            [
                [
                    'account_code' =>
                        $assetAccount,

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'memo' =>
                        'Tenant rent collection',
                ],
                [
                    'account_code' =>
                        $receivableAccount,

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Reduce Tenant rent receivable',
                ],
            ]
        );
    }

    public static function idempotencyKey(
        PaymentAllocation $allocation
    ): string {
        return
            'rent-receipt-allocation:'
            .$allocation->getKey();
    }

    /**
     * Resolve the fixed credit side exposed by AccountingEventMap for the
     * contextual rent-receipt event.
     *
     * @param  array<string, mixed>  $mapping
     */
    private function fixedCreditAccount(
        array $mapping
    ): string {
        /*
         * Current V1.0.5 contextual-map shape:
         *
         *     [
         *         'credit' => '<system account>',
         *         ...
         *     ]
         */
        $credit =
            $mapping['credit']
            ?? null;

        if (
            is_string($credit)
            && trim($credit) !== ''
        ) {
            return trim(
                $credit
            );
        }

        /*
         * Defensive support for a possible explicit fixed-side structure
         * without silently hard-coding an accounting account here.
         */
        $fixedAccount =
            $mapping['fixed_account']
            ?? null;

        $fixedSide =
            $mapping['fixed_side']
            ?? null;

        if (
            $fixedSide === 'credit'
            && is_string($fixedAccount)
            && trim($fixedAccount) !== ''
        ) {
            return trim(
                $fixedAccount
            );
        }

        throw new RuntimeException(
            'Rent receipt contextual accounting mapping does not expose its fixed credit account.'
        );
    }
}
