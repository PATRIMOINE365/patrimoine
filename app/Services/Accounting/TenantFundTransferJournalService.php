<?php

namespace App\Services\Accounting;

use App\Models\TenantFundTransaction;
use App\Models\User;

/**
 * Post Tenant fund Transfers into the immutable V1.0.5 Financial Journal.
 *
 * A Transfer moves held tenant money between two fund accounts of the
 * SAME tenant. Both operational balances are liabilities, so the Journal
 * entry simply reclassifies the liability:
 *
 * - Dr the source Tenant fund liability (money leaves that fund);
 * - Cr the destination Tenant fund liability (money arrives there).
 *
 * No asset account moves because no money enters or leaves Patrimoine.
 */
final class TenantFundTransferJournalService
{
    public function __construct(
        private readonly AccountingRuntimeGate $runtime,
        private readonly AccountingEventMap $events,
        private readonly JournalPostingService $journal,
    ) {}

    public static function idempotencyKey(
        TenantFundTransaction $debitTransaction
    ): string {
        return 'tenant-fund-transfer:'.$debitTransaction->id;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function post(
        TenantFundTransaction $debitTransaction,
        TenantFundTransaction $creditTransaction,
        string $reason,
        User $actor,
        array $context = [],
    ): void {
        if (! $this->runtime->enabled()) {
            return;
        }

        if (
            $debitTransaction->category !== 'transfer'
            || $debitTransaction->direction !== 'debit'
            || $creditTransaction->category !== 'transfer'
            || $creditTransaction->direction !== 'credit'
        ) {
            throw new \InvalidArgumentException(
                'Tenant Transfer Journal requires a debit/credit transfer TenantFundTransaction pair.'
            );
        }

        $debitTransaction->loadMissing(
            'account'
        );

        $creditTransaction->loadMissing(
            'account'
        );

        $sourceAccount = $debitTransaction->account;

        $destinationAccount = $creditTransaction->account;

        if (
            $sourceAccount === null
            || $destinationAccount === null
        ) {
            throw new \InvalidArgumentException(
                'Tenant Transfer Journal requires both Tenant fund accounts.'
            );
        }

        $amount = (int) $debitTransaction->amount;

        if (
            $amount <= 0
            || (int) $creditTransaction->amount !== $amount
        ) {
            throw new \InvalidArgumentException(
                'Tenant Transfer Journal amounts do not match on both legs.'
            );
        }

        /*
         * Resolve the actual liability represented by each Tenant fund.
         *
         * rent_reserve         -> Rent Reserve Held
         * consumable_advance   -> Consumable Advance Held
         * security_deposit     -> Security Deposit Held
         */
        $sourceLiability =
            $this->events->tenantFundLiability(
                $sourceAccount->type
            );

        $destinationLiability =
            $this->events->tenantFundLiability(
                $destinationAccount->type
            );

        /*
         * Move the liability from the source fund to the destination fund:
         *
         * Dr Source Tenant Fund Liability
         * Cr Destination Tenant Fund Liability
         */
        $lines = [
            [
                'account_code' => $sourceLiability,

                'debit' => $amount,

                'credit' => 0,

                'memo' => 'Tenant fund transfer out.',
            ],
            [
                'account_code' => $destinationLiability,

                'debit' => 0,

                'credit' => $amount,

                'memo' => 'Tenant fund transfer in.',
            ],
        ];

        $this->journal->post(
            [
                'journal_date' => $debitTransaction
                    ->transaction_date
                    ->toDateString(),

                'transaction_type' => AccountingEventMap::EVENT_TENANT_FUND_TRANSFER,

                'description' => 'Tenant fund transfer.',

                'source_type' => TenantFundTransaction::class,

                'source_id' => $debitTransaction->id,

                'idempotency_key' => self::idempotencyKey(
                    $debitTransaction
                ),

                'actor_user_id' => $actor->id,

                'actor_name_snapshot' => $actor->name,

                'snapshot' => array_merge(
                    [
                        'debit_transaction_id' => $debitTransaction->id,

                        'credit_transaction_id' => $creditTransaction->id,

                        'source_account_id' => $sourceAccount->id,

                        'source_fund_type' => $sourceAccount->type,

                        'destination_account_id' => $destinationAccount->id,

                        'destination_fund_type' => $destinationAccount->type,

                        'source_lease_id' => $sourceAccount->lease_id,

                        'destination_lease_id' => $destinationAccount->lease_id,

                        'tenant_id' => $sourceAccount
                            ->lease
                            ?->tenant_id,

                        'amount' => $amount,

                        'reason' => $reason,

                        'reference' => $debitTransaction->reference,
                    ],
                    $context
                ),
            ],
            $lines
        );
    }
}
