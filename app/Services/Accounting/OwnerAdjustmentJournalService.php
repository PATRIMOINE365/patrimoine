<?php

namespace App\Services\Accounting;

use App\Models\OwnerTransaction;
use App\Models\User;

/**
 * Posts standardized Owner Account corrections to the immutable
 * V1.0.5 Financial Journal.
 *
 * Operational Owner balances use OwnerTransaction:
 *
 * - credit increases Owner Funds Payable;
 * - debit decreases Owner Funds Payable.
 *
 * Adjustment Clearing is always the counter-account.
 */
final class OwnerAdjustmentJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtime,
        private readonly AccountingEventMap $events,
        private readonly JournalPostingService $journal,
    ) {}

    public static function idempotencyKey(
        OwnerTransaction $transaction
    ): string {
        return 'owner-adjustment:'.$transaction->id;
    }

    /**
     * Post the adjustment when Financial Journal runtime is active.
     *
     * Pre-cutover operation intentionally remains Journal-free.
     */
    public function post(
        OwnerTransaction $transaction,
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
                'Owner Adjustment Journal requires an adjustment OwnerTransaction.'
            );
        }

        $amount = (int) $transaction->amount;

        if ($amount <= 0 || abs($difference) !== $amount) {
            throw new \InvalidArgumentException(
                'Owner Adjustment Journal amount does not match the balance correction.'
            );
        }

        $mapping = $this->events->contextual(
            AccountingEventMap::EVENT_ADJUSTMENT
        );

        $balanceAccount =
            SystemChartOfAccounts::OWNER_FUNDS_PAYABLE;

        $counterAccount = $mapping['counter'];

        if ($transaction->direction === 'credit') {
            $lines = [
                [
                    'account_code' => $counterAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => 'Owner balance adjustment increase.',
                ],
                [
                    'account_code' => $balanceAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'Owner balance adjustment increase.',
                ],
            ];
        } else {
            $lines = [
                [
                    'account_code' => $balanceAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => 'Owner balance adjustment decrease.',
                ],
                [
                    'account_code' => $counterAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'Owner balance adjustment decrease.',
                ],
            ];
        }

        $this->journal->post(
            [
                'journal_date' => $transaction
                    ->transaction_date
                    ->toDateString(),

                'transaction_type' => AccountingEventMap::EVENT_ADJUSTMENT,

                'description' => 'Owner Account balance adjustment.',

                'source_type' => OwnerTransaction::class,

                'source_id' => $transaction->id,

                'idempotency_key' => self::idempotencyKey($transaction),

                'actor_user_id' => $actor->id,

                'actor_name_snapshot' => $actor->name,

                'snapshot' => array_merge(
                    [
                        'adjustment_account_type' => 'owner_account',

                        'owner_transaction_id' => $transaction->id,

                        'owner_account_id' => $transaction->owner_account_id,

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
