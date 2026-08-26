<?php

namespace App\Services\Accounting;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Carbon;
use RuntimeException;

class TenantFundExpenseJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtimeGate,
        private readonly AccountingEventMap $eventMap,
        private readonly JournalPostingService $journalPosting,
    ) {}

    public function post(
        TenantFundTransaction $transaction
    ): void {
        if (! $this->runtimeGate->enabled()) {
            return;
        }

        $transaction->loadMissing(
            'account'
        );

        $account =
            $transaction->account;

        if (! $account instanceof TenantFundAccount) {
            throw new RuntimeException(
                'Tenant fund expense has no valid Tenant fund account.'
            );
        }

        if (
            $transaction->direction !== 'debit'
            || $transaction->category !== 'expense'
        ) {
            throw new RuntimeException(
                'Invalid Tenant fund expense transaction.'
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Tenant fund expense Journal amount must be greater than zero.'
            );
        }

        $paymentMethod =
            trim(
                (string) $transaction
                    ->payment_method
            );

        if ($paymentMethod === '') {
            throw new RuntimeException(
                'Tenant fund expense payment method is required.'
            );
        }

        $mapping =
            $this->eventMap->contextual(
                AccountingEventMap::EVENT_TENANT_FUND_EXPENSE
            );

        if (
            ($mapping['variable'] ?? null)
                !== 'tenant_fund_liability'
            || ($mapping['counter_variable'] ?? null)
                !== 'payment_asset'
        ) {
            throw new RuntimeException(
                'Tenant fund expense accounting mapping is inconsistent.'
            );
        }

        $liabilityAccount =
            $this->eventMap
                ->tenantFundLiability(
                    $account->type
                );

        $paymentAsset =
            $this->eventMap
                ->paymentAsset(
                    $paymentMethod
                );

        $this->journalPosting->post(
            [
                'journal_date' => $this->transactionDate(
                    $transaction
                ),

                'transaction_type' => AccountingEventMap::EVENT_TENANT_FUND_EXPENSE,

                'description' => __(
                    'financial_journal.descriptions.tenant_fund_withdrawal',
                    ['reference' => $transaction->id]
                ),

                'source_type' => TenantFundTransaction::class,

                'source_id' => $transaction->id,

                'idempotency_key' => self::idempotencyKey(
                    $transaction
                ),

                'snapshot' => [
                    'tenant_fund_transaction_id' => $transaction->id,

                    'tenant_fund_account_id' => $account->id,

                    'lease_id' => $account->lease_id,

                    'fund_type' => $account->type,

                    'category' => 'expense',

                    'payment_method' => $paymentMethod,

                    'amount' => $amount,

                    'reference' => $transaction->reference,
                ],
            ],
            [
                [
                    'account_code' => $liabilityAccount,

                    'debit' => $amount,

                    'credit' => 0,

                    'memo' => 'Reduce Tenant funds held',
                ],
                [
                    'account_code' => $paymentAsset,

                    'debit' => 0,

                    'credit' => $amount,

                    'memo' => 'Tenant fund expense paid',
                ],
            ]
        );
    }

    public static function idempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'tenant-fund-expense:'
            .$transaction->id;
    }

    private function transactionDate(
        TenantFundTransaction $transaction
    ): string {
        if (
            $transaction->transaction_date
            === null
        ) {
            return now()
                ->toDateString();
        }

        return Carbon::parse(
            $transaction->transaction_date
        )->toDateString();
    }
}
