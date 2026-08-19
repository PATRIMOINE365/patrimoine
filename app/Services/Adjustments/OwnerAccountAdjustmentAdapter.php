<?php

namespace App\Services\Adjustments;

use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Services\Accounting\OwnerAdjustmentJournalService;
use RuntimeException;

/**
 * V1.0.5 Adjustment adapter for Patrimoine's consolidated OwnerAccount.
 *
 * Owner balances are derived from OwnerTransaction ledger rows:
 *
 * - credit increases the balance;
 * - debit decreases the balance.
 *
 * Owner accounts legitimately support negative balances.
 */
final class OwnerAccountAdjustmentAdapter implements AdjustmentAccountAdapter
{
    public function __construct(
        private readonly OwnerAdjustmentJournalService $journal,
    ) {}

    public function supports(
        AdjustmentContext $context
    ): bool {
        return
            $context->accountType
                === AdjustmentAccountType::OWNER_ACCOUNT
            && $context->entityType
                === 'owner_account';
    }

    public function currentBalance(
        AdjustmentContext $context
    ): int {
        $account = OwnerAccount::query()
            ->lockForUpdate()
            ->find($context->entityId);

        if ($account === null) {
            throw new RuntimeException(
                'Owner Account does not exist.'
            );
        }

        return $account->balance();
    }

    public function apply(
        AdjustmentCommand $command,
        AdjustmentCalculation $calculation,
    ): AdjustmentResult {
        if (! $this->supports($command->context)) {
            throw new RuntimeException(
                'Owner Account Adjustment adapter received an unsupported context.'
            );
        }

        /*
         * A request saying that the current balance is already correct is not
         * a financial movement and must not manufacture a zero-value ledger
         * transaction.
         */
        if (! $calculation->changesBalance()) {
            throw new RuntimeException(
                'The corrected balance is already the current Owner balance.'
            );
        }

        $direction =
            $calculation->difference > 0
                ? 'credit'
                : 'debit';

        $transaction =
            OwnerTransaction::create([
                'owner_account_id' => $command->context->entityId,

                'direction' => $direction,

                'category' => 'adjustment',

                'amount' => $calculation->absoluteDifference(),

                'transaction_date' => $command->effectiveDate->toDateString(),

                'reference' => $command->context->metadata['reference']
                    ?? null,

                /*
                 * Retain the human explanation on the existing operational
                 * ledger for backward-compatible reporting/readability.
                 */
                'notes' => $command->reason,
            ]);

        /*
         * ContextualAdjustmentService owns the outer database transaction.
         * Therefore the operational OwnerTransaction and its Financial
         * Journal entry either both succeed or both roll back.
         */
        $this->journal->post(
            transaction: $transaction,
            previousBalance: $calculation->previousBalance,
            correctedBalance: $calculation->correctedBalance,
            difference: $calculation->difference,
            reason: $command->reason,
            actor: $command->performedBy,
            context: [
                'owner_id' => $command->context->metadata['owner_id']
                    ?? null,

                'owner_name' => $command->context->metadata['owner_name']
                    ?? null,

                'entity_label' => $command->context->entityLabel,
            ],
        );

        return new AdjustmentResult(
            context: $command->context,
            calculation: $calculation,
            effectiveDate: $command->effectiveDate,
            reason: $command->reason,
            performedByUserId: $command->performedBy->id,
            transactionType: 'owner_adjustment',
            transactionId: $transaction->id,
            transactionSnapshot: [
                'owner_transaction_id' => $transaction->id,

                'owner_account_id' => $command->context->entityId,

                'previous_balance' => $calculation->previousBalance,

                'corrected_balance' => $calculation->correctedBalance,

                'difference' => $calculation->difference,

                'direction' => $transaction->direction,

                'amount' => $transaction->amount,

                'transaction_date' => $transaction
                    ->transaction_date
                    ->toDateString(),

                'reason' => $command->reason,

                'reference' => $transaction->reference,
            ],
        );
    }
}
