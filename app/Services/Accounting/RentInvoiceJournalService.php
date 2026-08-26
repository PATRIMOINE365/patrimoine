<?php

namespace App\Services\Accounting;

use App\Models\Invoice;
use App\Models\JournalEntry;
use RuntimeException;

/**
 * Mirrors newly-created post-cutover rent Invoices into the immutable
 * Financial Journal.
 *
 * Historical transactions are represented by the controlled opening balance.
 * This service therefore performs no posting until that cutover is complete.
 */
class RentInvoiceJournalService
{
    public const TRANSACTION_TYPE =
        AccountingEventMap::EVENT_RENT_INVOICE;

    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting
    ) {
    }

    /**
     * Post one rent Invoice to the Financial Journal.
     *
     * Returns NULL before cutover or for a non-rent Invoice.
     */
    public function post(
        Invoice $invoice
    ): ?JournalEntry {
        if (! $this->runtimeGate->enabled()) {
            return null;
        }

        if ($invoice->type !== 'rent') {
            return null;
        }

        $amount = (int) $invoice->total_amount;

        if ($amount <= 0) {
            /*
             * Zero-value Invoices are valid operational records, for example
             * an explicit zero proration. They do not represent an accounting
             * movement and therefore do not create a Journal transaction.
             */
            return null;
        }

        if ($invoice->getKey() === null) {
            throw new RuntimeException(
                'Rent Invoice must be persisted before Financial Journal posting.'
            );
        }

        $mapping = $this->eventMap->fixed(
            AccountingEventMap::EVENT_RENT_INVOICE
        );

        return $this->journalPosting->post(
            [
                'journal_date' =>
                    $invoice->issue_date
                        ->toDateString(),

                'transaction_type' =>
                    self::TRANSACTION_TYPE,

                'description' => __(
                    'financial_journal.descriptions.rent_invoice',
                    ['reference' => $invoice->invoice_number]
                ),

                'source_type' =>
                    Invoice::class,

                'source_id' =>
                    (int) $invoice->getKey(),

                'idempotency_key' =>
                    self::idempotencyKey($invoice),

                'snapshot' => [
                    'invoice_id' =>
                        (int) $invoice->getKey(),

                    'invoice_number' =>
                        $invoice->invoice_number,

                    'lease_id' =>
                        (int) $invoice->lease_id,

                    'invoice_type' =>
                        $invoice->type,

                    'period_start' =>
                        $invoice->period_start
                            ->toDateString(),

                    'period_end' =>
                        $invoice->period_end
                            ->toDateString(),

                    'total_amount' =>
                        $amount,

                    'net_amount' =>
                        (int) $invoice->net_amount,

                    'vat_amount' =>
                        (int) $invoice->vat_amount,

                    'vat_rate' =>
                        (string) $invoice->vat_rate,
                ],
            ],
            [
                [
                    'account_code' =>
                        $mapping['debit'],

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'memo' =>
                        'Tenant rent receivable',
                ],
                [
                    'account_code' =>
                        $mapping['credit'],

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Rent billing clearing',
                ],
            ]
        );
    }

    /**
     * Permanent retry-safe identity for the Invoice accounting event.
     */
    public static function idempotencyKey(
        Invoice $invoice
    ): string {
        if ($invoice->getKey() === null) {
            throw new RuntimeException(
                'Rent Invoice must be persisted before generating an idempotency key.'
            );
        }

        return
            'rent-invoice:'
            .$invoice->getKey();
    }
}
