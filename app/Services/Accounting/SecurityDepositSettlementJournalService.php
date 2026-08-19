<?php

namespace App\Services\Accounting;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\SecurityDepositSettlement;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Mirrors post-cutover Security Deposit settlement consequences into the
 * immutable Financial Journal.
 *
 * Patrimoine's operational close-out has three separate accounting events:
 *
 * 1. Held Security Deposit retained against deductions:
 *
 *      Dr Security Deposit Held
 *      Cr Security Deposit Recovery
 *
 * 2. Deductions exceeding the held Security Deposit:
 *
 *      Dr Security Deposit Debt Receivable
 *      Cr Security Deposit Recovery
 *
 *    This event is represented operationally by the generated
 *    security_deposit_debt Invoice.
 *
 * 3. Remaining Security Deposit refunded:
 *
 *      Dr Security Deposit Held
 *      Cr Cash / Bank / Mobile Payment
 *
 * Before the controlled V1.0.5 accounting cutover this service is a no-op.
 */
class SecurityDepositSettlementJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting
    ) {
    }

    /**
     * Journal the portion of the held deposit retained against deductions.
     */
    public function postApplied(
        TenantFundTransaction $transaction,
        SecurityDepositSettlement $settlement
    ): ?JournalEntry {
        if (! $this->runtimeGate->enabled()) {
            return null;
        }

        $transaction->loadMissing(
            'account'
        );

        $account =
            $transaction->account;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Security Deposit application has no valid fund account.'
            );
        }

        if (
            $account->type !== 'security_deposit'
            || $transaction->direction !== 'debit'
            || $transaction->category !== 'deposit_deduction'
        ) {
            throw new RuntimeException(
                'Invalid Security Deposit application transaction.'
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Security Deposit application Journal amount must be greater than zero.'
            );
        }

        $mapping =
            $this->eventMap->fixed(
                AccountingEventMap::
                    EVENT_SECURITY_DEPOSIT_APPLIED
            );

        return $this->journalPosting->post(
            [
                'journal_date' =>
                    $this->transactionDate(
                        $transaction
                    ),

                'transaction_type' =>
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_APPLIED,

                'description' =>
                    'Security Deposit applied '
                    .$this->settlementIdentity(
                        $settlement
                    ),

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    (int) $transaction->getKey(),

                'idempotency_key' =>
                    self::appliedIdempotencyKey(
                        $transaction
                    ),

                'snapshot' => [
                    'security_deposit_settlement_id' =>
                        (int) $settlement->getKey(),

                    'tenant_fund_transaction_id' =>
                        (int) $transaction->getKey(),

                    'tenant_fund_account_id' =>
                        (int) $account->getKey(),

                    'lease_id' =>
                        (int) $settlement->lease_id,

                    'voucher_number' =>
                        $settlement
                            ->refund_voucher_number,

                    'amount' =>
                        $amount,
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
                        'Reduce Security Deposit held',
                ],
                [
                    'account_code' =>
                        $mapping['credit'],

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Security Deposit deduction recovery',
                ],
            ]
        );
    }

    /**
     * Journal the receivable created for deductions exceeding funds held.
     */
    public function postDebtInvoice(
        Invoice $invoice,
        SecurityDepositSettlement $settlement
    ): ?JournalEntry {
        if (! $this->runtimeGate->enabled()) {
            return null;
        }

        if (! $invoice->exists) {
            throw new RuntimeException(
                'Security Deposit debt Invoice must be persisted before Journal posting.'
            );
        }

        if ($invoice->type !== 'security_deposit_debt') {
            throw new RuntimeException(
                'Expected a Security Deposit debt Invoice.'
            );
        }

        $amount =
            (int) $invoice->total_amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Security Deposit debt Journal amount must be greater than zero.'
            );
        }

        $mapping =
            $this->eventMap->fixed(
                AccountingEventMap::
                    EVENT_SECURITY_DEPOSIT_DEBT_INVOICE
            );

        return $this->journalPosting->post(
            [
                'journal_date' =>
                    $invoice
                        ->issue_date
                        ->toDateString(),

                'transaction_type' =>
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_DEBT_INVOICE,

                'description' =>
                    'Security Deposit debt invoice '
                    .$invoice->invoice_number,

                'source_type' =>
                    Invoice::class,

                'source_id' =>
                    (int) $invoice->getKey(),

                'idempotency_key' =>
                    self::debtInvoiceIdempotencyKey(
                        $invoice
                    ),

                'snapshot' => [
                    'security_deposit_settlement_id' =>
                        (int) $settlement->getKey(),

                    'debt_invoice_id' =>
                        (int) $invoice->getKey(),

                    'invoice_number' =>
                        $invoice->invoice_number,

                    'lease_id' =>
                        (int) $invoice->lease_id,

                    'amount' =>
                        $amount,
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
                        'Security Deposit close-out receivable',
                ],
                [
                    'account_code' =>
                        $mapping['credit'],

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Security Deposit deduction recovery',
                ],
            ]
        );
    }

    /**
     * Journal the actual outgoing refund.
     */
    public function postRefund(
        TenantFundTransaction $transaction,
        SecurityDepositSettlement $settlement
    ): ?JournalEntry {
        if (! $this->runtimeGate->enabled()) {
            return null;
        }

        $transaction->loadMissing(
            'account'
        );

        $account =
            $transaction->account;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Security Deposit refund has no valid fund account.'
            );
        }

        if (
            $account->type !== 'security_deposit'
            || $transaction->direction !== 'debit'
            || $transaction->category !== 'refund'
        ) {
            throw new RuntimeException(
                'Invalid Security Deposit refund transaction.'
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Security Deposit refund Journal amount must be greater than zero.'
            );
        }

        $paymentMethod =
            trim(
                (string) (
                    $settlement
                        ->refund_payment_method
                    ?? ''
                )
            );

        if ($paymentMethod === '') {
            throw new RuntimeException(
                'Security Deposit refund payment method is required after accounting cutover.'
            );
        }

        $mapping =
            $this->eventMap->contextual(
                AccountingEventMap::
                    EVENT_SECURITY_DEPOSIT_REFUND
            );

        $liabilityAccount =
            $mapping['debit']
            ?? null;

        if (
            ! is_string($liabilityAccount)
            || trim($liabilityAccount) === ''
        ) {
            throw new RuntimeException(
                'Security Deposit refund accounting mapping has no liability account.'
            );
        }

        $assetAccount =
            $this->eventMap->paymentAsset(
                $paymentMethod
            );

        return $this->journalPosting->post(
            [
                'journal_date' =>
                    $this->transactionDate(
                        $transaction
                    ),

                'transaction_type' =>
                    AccountingEventMap::
                        EVENT_SECURITY_DEPOSIT_REFUND,

                'description' =>
                    'Security Deposit refund '
                    .$this->settlementIdentity(
                        $settlement
                    ),

                'source_type' =>
                    TenantFundTransaction::class,

                'source_id' =>
                    (int) $transaction->getKey(),

                'idempotency_key' =>
                    self::refundIdempotencyKey(
                        $transaction
                    ),

                'snapshot' => [
                    'security_deposit_settlement_id' =>
                        (int) $settlement->getKey(),

                    'tenant_fund_transaction_id' =>
                        (int) $transaction->getKey(),

                    'tenant_fund_account_id' =>
                        (int) $account->getKey(),

                    'lease_id' =>
                        (int) $settlement->lease_id,

                    'voucher_number' =>
                        $settlement
                            ->refund_voucher_number,

                    'refund_payment_method' =>
                        $paymentMethod,

                    'amount' =>
                        $amount,
                ],
            ],
            [
                [
                    'account_code' =>
                        $liabilityAccount,

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'memo' =>
                        'Release Security Deposit liability',
                ],
                [
                    'account_code' =>
                        $assetAccount,

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'memo' =>
                        'Security Deposit refund paid',
                ],
            ]
        );
    }

    public static function appliedIdempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'security-deposit-applied:'
            .$transaction->getKey();
    }

    public static function debtInvoiceIdempotencyKey(
        Invoice $invoice
    ): string {
        return
            'security-deposit-debt-invoice:'
            .$invoice->getKey();
    }

    public static function refundIdempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'security-deposit-refund:'
            .$transaction->getKey();
    }

    private function transactionDate(
        TenantFundTransaction $transaction
    ): string {
        if ($transaction->transaction_date === null) {
            return now()
                ->toDateString();
        }

        return Carbon::parse(
            $transaction->transaction_date
        )->toDateString();
    }

    private function settlementIdentity(
        SecurityDepositSettlement $settlement
    ): string {
        $voucher =
            trim(
                (string) (
                    $settlement
                        ->refund_voucher_number
                    ?? ''
                )
            );

        return
            $voucher !== ''
                ? $voucher
                : '#'.$settlement->getKey();
    }
}
