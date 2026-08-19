<?php

namespace App\Services\Accounting;

use App\Models\Invoice;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Mirrors post-cutover consumption of tenant-held rent funds into the
 * immutable Financial Journal.
 *
 * Phase 3 Step 4 covers only:
 *
 * - Rent Reserve consumed against contractual rent; and
 * - Consumable Advance consumed against contractual rent.
 *
 * Security Deposit application, refund and close-out debt remain separate
 * accounting events and are deliberately excluded from this service.
 */
class TenantFundConsumptionJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting
    ) {
    }

    public function post(
        TenantFundTransaction $transaction
    ): void {
        if (! $this->runtimeGate->enabled()) {
            return;
        }

        $transaction->loadMissing([
            'account',
            'invoice',
        ]);

        $account =
            $transaction->account;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Tenant fund consumption has no valid fund account.'
            );
        }

        /*
         * Funding transactions are credits to a tenant-held liability.
         *
         * Phase 3 Step 4 is interested only in actual consumption, which is
         * represented by a debit from that held-fund ledger.
         */
        if ($transaction->direction !== 'debit') {
            return;
        }

        $event =
            $this->consumptionEvent(
                (string) $account->type
            );

        /*
         * Security Deposit is intentionally handled by its own accounting
         * workflow and therefore produces no Step 4 Journal entry here.
         */
        if ($event === null) {
            return;
        }

        $invoice =
            $transaction->invoice;

        if (! $invoice instanceof Invoice) {
            throw new RuntimeException(
                'Tenant fund consumption requires the affected Invoice.'
            );
        }

        if (! $invoice->isRentInvoice()) {
            throw new RuntimeException(
                'Rent Reserve and Consumable Advance may only journal consumption against rent Invoices.'
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Tenant fund consumption Journal amount must be greater than zero.'
            );
        }

        $mapping =
            $this->eventMap->fixed(
                $event
            );

        $debitAccount =
            $mapping['debit']
            ?? null;

        $creditAccount =
            $mapping['credit']
            ?? null;

        if (
            ! is_string($debitAccount)
            || trim($debitAccount) === ''
            || ! is_string($creditAccount)
            || trim($creditAccount) === ''
        ) {
            throw new RuntimeException(
                'Tenant fund consumption accounting mapping is invalid.'
            );
        }

        $this->journalPosting->post(
            [
                'journal_date' =>
                    $this->transactionDate(
                        $transaction
                    ),

                'transaction_type' =>
                    $event,

                'description' =>
                    $this->description(
                        $account,
                        $invoice
                    ),

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    $transaction->getKey(),

                'idempotency_key' =>
                    self::idempotencyKey(
                        $transaction
                    ),

                'snapshot' => [
                    'tenant_fund_transaction_id' =>
                        $transaction->getKey(),

                    'tenant_fund_account_id' =>
                        $account->getKey(),

                    'fund_type' =>
                        $account->type,

                    'lease_id' =>
                        $account->lease_id,

                    'invoice_id' =>
                        $invoice->getKey(),

                    'invoice_number' =>
                        $invoice->invoice_number,

                    'category' =>
                        $transaction->category,

                    'direction' =>
                        $transaction->direction,

                    'amount' =>
                        $amount,
                ],
            ],
            [
                [
                    'account_code' =>
                        $debitAccount,

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'memo' =>
                        'Reduce tenant-held fund liability',
                ],
                [
                    'account_code' =>
                        $creditAccount,

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Settle Tenant Rent Receivable',
                ],
            ]
        );
    }

    public static function idempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'tenant-fund-consumption:'
            .$transaction->getKey();
    }

    private function consumptionEvent(
        string $fundType
    ): ?string {
        return match ($fundType) {
            'rent_reserve' =>
                AccountingEventMap::
                    EVENT_RENT_RESERVE_CONSUMPTION,

            'consumable_advance' =>
                AccountingEventMap::
                    EVENT_ADVANCE_CONSUMPTION,

            /*
             * Security Deposit accounting is deliberately outside Step 4.
             */
            'security_deposit' =>
                null,

            default =>
                null,
        };
    }

    private function transactionDate(
        TenantFundTransaction $transaction
    ): string {
        $value =
            $transaction->transaction_date;

        if ($value === null) {
            return now()->toDateString();
        }

        return Carbon::parse(
            $value
        )->toDateString();
    }

    private function description(
        TenantFundAccount $account,
        Invoice $invoice
    ): string {
        $label =
            $account->type === 'rent_reserve'
                ? 'Rent Reserve consumption'
                : 'Consumable Advance consumption';

        $invoiceIdentity =
            is_string($invoice->invoice_number)
            && trim($invoice->invoice_number) !== ''
                ? trim($invoice->invoice_number)
                : '#'.$invoice->getKey();

        return
            $label
            .' for rent invoice '
            .$invoiceIdentity;
    }
}
