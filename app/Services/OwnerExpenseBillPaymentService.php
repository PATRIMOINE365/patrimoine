<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\OwnerAccount;
use App\Models\OwnerExpenseBill;
use App\Models\OwnerTransaction;
use App\Services\Accounting\JournalReversalService;
use App\Services\Accounting\OwnerFinancialJournalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V1.0.8: pays an owner expense bill, and cancels such a payment.
 *
 * The user chooses which side of the owner's money funds the payment:
 *
 * - the Deposit/Expense account, which may go negative by design —
 *   expenses beyond the deposits are debt the owner owes the agency;
 * - the Payout account, which is rent-derived money and is therefore
 *   strictly capped at its available balance.
 *
 * A cancellation never deletes anything: it records an opposite credit
 * transaction pointing at the payment it reverses and posts an
 * immutable Journal reversal.
 */
class OwnerExpenseBillPaymentService
{
    public function __construct(
        private readonly OwnerFinancialJournalService $journal,
        private readonly JournalReversalService $journalReversals
    ) {
    }

    /**
     * Pay all or part of an expense bill from one owner account side.
     */
    public function pay(
        OwnerExpenseBill $bill,
        string $fundingSource,
        int $amount,
        string $transactionDate
    ): OwnerTransaction {
        return DB::transaction(function () use (
            $bill,
            $fundingSource,
            $amount,
            $transactionDate
        ): OwnerTransaction {
            $bill = OwnerExpenseBill::query()
                ->lockForUpdate()
                ->findOrFail($bill->id);

            $account = OwnerAccount::query()
                ->lockForUpdate()
                ->findOrFail($bill->owner_account_id);

            if (
                ! in_array(
                    $fundingSource,
                    [
                        'deposit_account',
                        'payout_account',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    __('business.owner.bill_payment_source')
                );
            }

            if ($amount <= 0) {
                throw new RuntimeException(
                    __('business.owner.bill_payment_positive')
                );
            }

            if ($amount > $bill->outstandingAmount()) {
                throw new RuntimeException(
                    __('business.owner.bill_payment_exceeds_bill')
                );
            }

            /*
             * Rent-derived money is strictly capped; the Deposit
             * account may go negative by design.
             */
            if (
                $fundingSource === 'payout_account'
                && $amount > $account->payoutAccountBalance()
            ) {
                throw new RuntimeException(
                    __('business.owner.bill_payment_insufficient_payout')
                );
            }

            $building = $bill->expenses()
                ->whereNotNull('building_id')
                ->value('building_id');

            $payment = OwnerTransaction::create([
                'owner_account_id' => $account->id,
                'building_id' => $building,
                'direction' => 'debit',
                'category' => 'expense',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'reference' => $bill->bill_number,
                'notes' => 'Owner expense bill payment.',
                'owner_expense_bill_id' => $bill->id,
                'funding_source' => $fundingSource,
            ]);

            /*
             * Ledger row and Journal posting are one business action.
             * Before the accounting cutover the posting is a no-op.
             */
            $this->journal->postExpenseBillPayment(
                $payment
            );

            return $payment->refresh();
        });
    }

    /**
     * Cancel one bill payment, reverting everything it recorded.
     */
    public function cancel(
        OwnerTransaction $payment,
        string $reason,
        ?int $actorUserId = null
    ): OwnerTransaction {
        return DB::transaction(function () use (
            $payment,
            $reason,
            $actorUserId
        ): OwnerTransaction {
            $payment = OwnerTransaction::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (
                $payment->direction !== 'debit'
                || $payment->category !== 'expense'
                || $payment->owner_expense_bill_id === null
            ) {
                throw new RuntimeException(
                    __('business.owner.not_a_bill_payment')
                );
            }

            if ($payment->isReversed()) {
                throw new RuntimeException(
                    __('business.owner.bill_payment_already_cancelled')
                );
            }

            /*
             * Bills recorded before V1.0.8 settled themselves at
             * creation with per-line ledger debits journaled against
             * the OwnerExpense line. Those historical settlements have
             * no funding source and cannot be safely unwound.
             */
            if ($payment->funding_source === null) {
                throw new RuntimeException(
                    __('business.owner.historical_bill_payment')
                );
            }

            $reason = trim($reason);

            if ($reason === '') {
                throw new RuntimeException(
                    __('business.owner.bill_payment_reason_required')
                );
            }

            $reversal = OwnerTransaction::create([
                'owner_account_id' => $payment->owner_account_id,
                'building_id' => $payment->building_id,
                'direction' => 'credit',
                'category' => 'expense',
                'amount' => $payment->amount,
                'transaction_date' => now()->toDateString(),
                'reference' => $payment->reference,
                'notes' => $reason,
                'owner_expense_bill_id' => $payment->owner_expense_bill_id,
                'funding_source' => $payment->funding_source,
                'reversal_of_transaction_id' => $payment->id,
            ]);

            /*
             * Reverse the payment's Journal entry when one exists.
             * Payments recorded before the accounting cutover have
             * none and are simply skipped.
             */
            $entries = JournalEntry::query()
                ->where('source_type', OwnerTransaction::class)
                ->where('source_id', $payment->id)
                ->get();

            foreach ($entries as $entry) {
                $alreadyReversed = JournalEntry::query()
                    ->where('reversal_of_id', $entry->id)
                    ->exists();

                if (
                    $alreadyReversed
                    || $entry->isReversal()
                    || $entry->isInformational()
                ) {
                    continue;
                }

                $this->journalReversals->reverse(
                    $entry,
                    $reason,
                    $actorUserId
                );
            }

            return $reversal->refresh();
        });
    }
}
