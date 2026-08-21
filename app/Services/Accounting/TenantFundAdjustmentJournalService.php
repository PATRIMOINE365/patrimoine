<?php

namespace App\Services\Accounting;

use App\Models\TenantFundTransaction;
use App\Models\User;

/**
 * Post explicit Tenant fund balance corrections into the immutable
 * V1.0.5 Financial Journal.
 *
 * Tenant fund operational balances are liabilities:
 *
 * - credit increases the Tenant fund balance;
 * - debit decreases the Tenant fund balance.
 *
 * Adjustment Clearing is always the counter-account.
 */
final class TenantFundAdjustmentJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtime,
        private readonly AccountingEventMap $events,
        private readonly JournalPostingService $journal,
    ) {}

    public static function idempotencyKey(
        TenantFundTransaction $transaction
    ): string {
        return 'tenant-fund-adjustment:'.$transaction->id;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function post(
        TenantFundTransaction $transaction,
        int $previousBalance,
        int $correctedBalance,
        int $difference,
        string $reason,
        User $actor,
        array $context = [],
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        if (
            $transaction->category !== 'adjustment'
            || ! in_array(
                $transaction->direction,
                ['credit', 'debit'],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Tenant Adjustment Journal requires an adjustment TenantFundTransaction.'
            );
        }

        $transaction->loadMissing(
            'account'
        );

        $account = $transaction->account;

        if ($account === null) {
            throw new \InvalidArgumentException(
                'Tenant Adjustment Journal requires a Tenant fund account.'
            );
        }

        $amount = (int) $transaction->amount;

        if (
            $amount <= 0
            || abs($difference) !== $amount
        ) {
            throw new \InvalidArgumentException(
                'Tenant Adjustment Journal amount does not match the balance correction.'
            );
        }

        $mapping = $this->events->contextual(
            AccountingEventMap::EVENT_ADJUSTMENT
        );

        /*
         * Resolve the actual liability represented by this Tenant fund.
         *
         * rent_reserve         -> Rent Reserve Held
         * consumable_advance   -> Consumable Advance Held
         * security_deposit     -> Security Deposit Held
         */
        $balanceAccount =
            $this->events->tenantFundLiability(
                $account->type
            );

        $counterAccount =
            $mapping['counter'];

        if ($transaction->direction === 'credit') {
            /*
             * Increase Tenant liability:
             *
             * Dr Adjustment Clearing
             * Cr Selected Tenant Fund Liability
             */
            $lines = [
                [
                    'account_code' => $counterAccount,

                    'debit' => $amount,

                    'credit' => 0,

                    'memo' => 'Tenant fund balance adjustment increase.',
                ],
                [
                    'account_code' => $balanceAccount,

                    'debit' => 0,

                    'credit' => $amount,

                    'memo' => 'Tenant fund balance adjustment increase.',
                ],
            ];
        } else {
            /*
             * Decrease Tenant liability:
             *
             * Dr Selected Tenant Fund Liability
             * Cr Adjustment Clearing
             */
            $lines = [
                [
                    'account_code' => $balanceAccount,

                    'debit' => $amount,

                    'credit' => 0,

                    'memo' => 'Tenant fund balance adjustment decrease.',
                ],
                [
                    'account_code' => $counterAccount,

                    'debit' => 0,

                    'credit' => $amount,

                    'memo' => 'Tenant fund balance adjustment decrease.',
                ],
            ];
        }

        $this->journal->post(
            [
                'journal_date' => $transaction
                    ->transaction_date
                    ->toDateString(),

                'transaction_type' => AccountingEventMap::EVENT_ADJUSTMENT,

                'description' => 'Tenant fund balance adjustment.',

                'source_type' => TenantFundTransaction::class,

                'source_id' => $transaction->id,

                'idempotency_key' => self::idempotencyKey(
                    $transaction
                ),

                'actor_user_id' => $actor->id,

                'actor_name_snapshot' => $actor->name,

                'snapshot' => array_merge(
                    [
                        'adjustment_account_type' => $account->type,

                        'tenant_fund_transaction_id' => $transaction->id,

                        'tenant_fund_account_id' => $account->id,

                        'fund_type' => $account->type,

                        'lease_id' => $account->lease_id,

                        'previous_balance' => $previousBalance,

                        'corrected_balance' => $correctedBalance,

                        'difference' => $difference,

                        'direction' => $transaction->direction,

                        'amount' => $amount,

                        'reason' => $reason,

                        'reference' => $transaction->reference,
                    ],
                    $context
                ),
            ],
            $lines
        );
    }
}
