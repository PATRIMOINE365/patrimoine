<?php

namespace App\Services\Accounting;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Support\Carbon;
use RuntimeException;

class TenantFundWithdrawalJournalService
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
                'Tenant Withdrawal has no valid Tenant fund account.'
            );
        }

        if (
            $transaction->direction !== 'debit'
            || $transaction->category !== 'withdrawal'
        ) {
            throw new RuntimeException(
                'Invalid Tenant Withdrawal transaction.'
            );
        }

        $amount =
            (int) $transaction->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Tenant Withdrawal Journal amount must be greater than zero.'
            );
        }

        $paymentMethod =
            trim(
                (string) $transaction
                    ->payment_method
            );

        if ($paymentMethod === '') {
            throw new RuntimeException(
                'Tenant Withdrawal payment method is required.'
            );
        }

        $mapping =
            $this->eventMap->contextual(
                AccountingEventMap::EVENT_TENANT_WITHDRAWAL
            );

        if (
            ($mapping['variable'] ?? null)
                !== 'tenant_fund_liability'
            || ($mapping['counter_variable'] ?? null)
                !== 'payment_asset'
        ) {
            throw new RuntimeException(
                'Tenant Withdrawal accounting mapping is inconsistent.'
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

                'transaction_type' => AccountingEventMap::EVENT_TENANT_WITHDRAWAL,

                'description' => 'Tenant fund Withdrawal #'
                    .$transaction->id,

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

                    'category' => 'withdrawal',

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

                    'memo' => 'Tenant Withdrawal paid',
                ],
            ]
        );
    }

    public static function idempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return
            'tenant-withdrawal:'
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
