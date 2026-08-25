<?php

namespace App\Services;

use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Services\Documents\OwnerReserveTransferNumberService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V1.0.8: manual movement between an owner's two sub-balances.
 *
 * Direction `to_expense` moves rent-derived Payout-account money into
 * the Deposit/Expense account (typically to settle a negative expense
 * position). Direction `to_payout` releases Deposit/Expense money back
 * so it becomes withdrawable.
 *
 * The transfer is internal to the single consolidated owner ledger:
 * the total balance never changes, so no Financial Journal entry is
 * posted — both sides live inside the same Owner Funds liability.
 */
class OwnerReserveTransferService
{
    public function __construct(
        private readonly OwnerReserveTransferNumberService $numbers,
    ) {}

    public function transfer(
        OwnerAccount $account,
        string $direction,
        int $amount,
        string $transactionDate,
        string $reason
    ): OwnerTransaction {
        return DB::transaction(
            function () use (
                $account,
                $direction,
                $amount,
                $transactionDate,
                $reason
            ): OwnerTransaction {
                if (! in_array(
                    $direction,
                    ['to_expense', 'to_payout'],
                    true
                )) {
                    throw new RuntimeException(
                        'Reserve transfer direction must be to_expense or to_payout.'
                    );
                }

                if ($amount <= 0) {
                    throw new RuntimeException(
                        __('business.owner.reserve_transfer_positive')
                    );
                }

                if (trim($reason) === '') {
                    throw new RuntimeException(
                        __('business.owner.reserve_transfer_reason_required')
                    );
                }

                /*
                 * Lock the ledger against concurrent movements, then
                 * re-derive the authoritative source balance.
                 */
                $account = OwnerAccount::query()
                    ->lockForUpdate()
                    ->findOrFail($account->id);

                $sourceBalance =
                    $direction === 'to_expense'
                        ? $account->payoutAccountBalance()
                        : $account->depositAccountBalance();

                if ($amount > $sourceBalance) {
                    throw new RuntimeException(
                        __('business.owner.reserve_transfer_exceeds_source')
                    );
                }

                return OwnerTransaction::create([
                    'owner_account_id' => $account->id,

                    /*
                     * credit = INTO the Deposit/Expense account;
                     * debit  = back OUT to the Payout account.
                     */
                    'direction' => $direction === 'to_expense'
                        ? 'credit'
                        : 'debit',

                    'category' => 'reserve_transfer',

                    'amount' => $amount,

                    'transaction_date' => $transactionDate,

                    'reference' => $this->numbers->next(),

                    'notes' => trim($reason),
                ]);
            }
        );
    }
}
