<?php

namespace App\Services;

use App\Models\OwnerAccount;
use App\Models\OwnerPayout;
use App\Models\OwnerPayoutAllocation;
use App\Models\OwnerTransaction;
use App\Services\Accounting\OwnerFinancialJournalService;
use App\Services\Documents\OwnerPayoutBreakdownService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles payouts of owner funds held by Patrimoine.
 *
 * Patrimoine business rules:
 * - An owner may be paid only from a positive Payout-account balance;
 *   Deposit/Expense money must be reserve-transferred back first.
 * - A payout may span several Buildings, Units and rent periods.
 * - Eligible funds are consumed FIFO for traceability, and every
 *   consumed portion remembers WHICH ledger credit it came from —
 *   including deposit money released by a reserve transfer, so the
 *   receipt can say where the money originated.
 * - The payout itself creates a debit in the owner ledger.
 * - Validation, allocation, the ledger debit, the journal posting and
 *   the frozen statement all happen in ONE database transaction.
 *
 * V1.0.48 (audit finding 2): attribution comes from
 * OwnerPayoutAllocationEngine, which replays the ledger keeping the
 * Payout and Deposit/Expense pools apart. The old shortcut subtracted
 * every historical debit from every historical credit regardless of
 * pool, so an owner whose deposit side was in debt could not withdraw
 * perfectly available rent money. The fail-closed safeguard — refuse
 * rather than record an unattributable payout — is deliberately kept:
 * it is the only reason that fault was ever visible.
 */
class OwnerPayoutService
{
    public function __construct(
        private readonly OwnerFinancialJournalService $journal,
        private readonly OwnerPayoutBreakdownService $breakdown,
        private readonly OwnerPayoutAllocationEngine $allocations,
        private readonly OwnerLedgerProjection $projection
    ) {
    }

    /**
     * Create and allocate an owner payout.
     *
     * @param  OwnerAccount  $account  Owner account receiving the payout.
     * @param  int  $amount  Whole-currency payout amount.
     * @param  string  $payoutDate  Effective payout date in YYYY-MM-DD format.
     * @param  string  $paymentMethod  Payment channel such as cash, bank_transfer or momo.
     * @param  string|null  $reference  Optional external payment reference.
     * @param  string|null  $notes  Optional administrative notes.
     *
     * @throws RuntimeException When the payout violates accounting rules.
     */
    public function create(
        OwnerAccount $account,
        int $amount,
        string $payoutDate,
        string $paymentMethod,
        ?string $reference = null,
        ?string $notes = null
    ): OwnerPayout {
        return DB::transaction(function () use (
            $account,
            $amount,
            $payoutDate,
            $paymentMethod,
            $reference,
            $notes
        ): OwnerPayout {
            /*
             * Lock the account row so concurrent payout operations cannot
             * independently read the same owner balance and both pay it out.
             */
            $account = OwnerAccount::query()
                ->lockForUpdate()
                ->findOrFail($account->id);

            if ($amount <= 0) {
                throw new RuntimeException(
                    __('business.owner.payout_positive')
                );
            }

            /*
             * Lock the ledger rows the replay will read, in the same
             * FIFO order the previous implementation locked credits, so
             * a concurrent expense or transfer cannot slip between the
             * validation and the allocation.
             */
            OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            /*
             * Withdrawals draw ONLY from the Payout account — the
             * rent-derived side. Money in the Deposit/Expense account is
             * earmarked and must be reserve-transferred back before it
             * becomes withdrawable; deposit-side DEBT likewise stays on
             * its own side and never consumes payout funds.
             */
            $availableBalance = $account->payoutAccountBalance();

            if ($availableBalance <= 0) {
                throw new RuntimeException(
                    __('business.owner.payout_no_funds')
                );
            }

            if ($amount > $availableBalance) {
                throw new RuntimeException(
                    __('business.owner.payout_exceeds_balance')
                );
            }

            /*
             * Attribution: which remaining portions of which credits
             * this payout consumes, FIFO, origins preserved. The engine
             * cross-checks itself against the balance above and refuses
             * — rolling everything back — when the ledger holds
             * something the arithmetic does not understand. Recording
             * an unattributable payout would be worse than refusing a
             * valid one.
             */
            $planned = $this->allocations->planAllocations(
                (int) $account->id,
                $amount
            );

            $payout = OwnerPayout::create([
                'owner_account_id' => $account->id,
                'amount' => $amount,
                'payout_date' => $payoutDate,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            foreach ($planned as $portion) {
                OwnerPayoutAllocation::create([
                    'owner_payout_id' => $payout->id,
                    'owner_transaction_id' => $portion['origin'],
                    'amount' => $portion['amount'],
                ]);
            }

            /*
             * Record the actual money leaving Patrimoine.
             *
             * This debit is the authoritative ledger movement that reduces
             * the owner's consolidated account balance.
             *
             * OwnerPayoutAllocation records provide source attribution;
             * this OwnerTransaction provides the actual accounting debit.
             */
            $ledgerTransaction =
                OwnerTransaction::create([
                    'owner_account_id' => $account->id,
                    'direction' => 'debit',
                    'category' => 'payout',
                    'amount' => $amount,
                    'transaction_date' => $payoutDate,
                    'payment_method' => $paymentMethod,
                    'reference' => $reference,
                    'notes' => $notes ?? 'Owner payout.',
                ]);

            $payout->refresh();

            $this->journal->postPayout(
                $payout,
                $ledgerTransaction
            );

            /*
             * V1.0.47: freeze what this payout was made against.
             *
             * Composed here, once, with the ledger in the state that
             * justified the payment — and written onto the payout so the
             * receipt renders from it rather than asking the database
             * again later. Patrimoine allows backdating, so asking again
             * is asking a different question: a movement recorded
             * tomorrow with a date from May would otherwise walk into
             * this receipt and out of the one that actually releases it.
             *
             * Last, after the ledger debit and the journal posting, so
             * the statement includes the payment it describes.
             */
            $statement = $this->breakdown->compose($payout);

            /*
             * V1.0.48 pre-freeze invariants: a statement is checked
             * BEFORE it becomes permanent, because a frozen document is
             * never silently rewritten afterwards. Finding 3 sat in
             * issued receipts precisely because nothing looked at them
             * on the way to the freezer.
             */
            $this->assertStatementReconciles(
                $statement,
                (int) $account->id,
                $amount
            );

            $payout->forceFill([
                'statement' => $statement,
                'statement_frozen_at' => now(),
            ])->save();

            return $payout;
        });
    }

    /**
     * Refuse to freeze a statement that does not add up.
     *
     * Three independent checks:
     *  - the itemised tables must total to the summary lines above them;
     *  - the closing figure must match the shared projection at the
     *    same ledger boundary — the receipt and the account may not
     *    disagree about the same rows;
     *  - the projection's own payout + deposit = total invariant runs
     *    on the way (it throws from inside balancesThrough).
     *
     * @param  array<string, mixed>|null  $statement
     */
    private function assertStatementReconciles(
        ?array $statement,
        int $accountId,
        int $amount
    ): void {
        if ($statement === null) {
            throw new RuntimeException(
                __('business.owner.payout_allocation_failed')
            );
        }

        $tablesReconcile =
            array_sum(array_column($statement['received'], 'amount'))
                === $statement['received_total']
            && array_sum(array_column($statement['deductions'], 'amount'))
                === $statement['deductions_total']
            && array_sum(array_column($statement['expenses'], 'amount'))
                === $statement['expenses_total'];

        $arithmeticCloses =
            $statement['available']
                === $statement['brought_forward']
                    + $statement['received_total']
                    - $statement['deductions_total']
                    - $statement['expenses_total']
            && $statement['carried_forward']
                === $statement['available']
                    - $amount
                    - (int) $statement['other_payouts'];

        /*
         * The receipt's carried-forward figure and the account's own
         * net position at the same boundary are the same question asked
         * two ways; they must give one answer.
         */
        $agreesWithLedger =
            $statement['carried_forward']
                === $this->projection->balancesThrough(
                    $accountId,
                    (int) $statement['through_movement_id']
                )['total'];

        if (
            ! $tablesReconcile
            || ! $arithmeticCloses
            || ! $agreesWithLedger
        ) {
            throw new RuntimeException(
                __('business.owner.payout_allocation_failed')
            );
        }
    }
}
