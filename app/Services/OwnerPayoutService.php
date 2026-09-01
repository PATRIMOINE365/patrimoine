<?php

namespace App\Services;

use App\Models\OwnerAccount;
use App\Models\OwnerPayout;
use App\Models\OwnerPayoutAllocation;
use App\Services\Documents\OwnerPayoutBreakdownService;
use App\Models\OwnerTransaction;
use App\Services\Accounting\OwnerFinancialJournalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles payouts of owner funds held by Patrimoine.
 *
 * Patrimoine business rules:
 * - An owner may be paid only from a positive available balance.
 * - A payout may span several Buildings, Units and rent periods.
 * - Eligible owner credits are consumed FIFO for traceability.
 * - Historical owner debits consume the oldest credits before payouts.
 * - The payout itself creates a debit in the owner ledger.
 * - A payout can never exceed the owner's available balance.
 * - All operations are performed inside a database transaction.
 *
 * Important accounting distinction:
 *
 * OwnerAccount::balance() gives the correct NET amount held for an owner.
 *
 * OwnerPayoutAllocation provides attribution by identifying which historical
 * positive credits are ultimately represented by a payout.
 *
 * Expenses, management fees, agent commissions and debit adjustments do not
 * point directly to individual credits. For payout attribution purposes,
 * these debits are therefore treated as consuming the oldest owner credits
 * first.
 */
class OwnerPayoutService
{
    public function __construct(
        private readonly OwnerFinancialJournalService $journal,
        private readonly OwnerPayoutBreakdownService $breakdown
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
             * Owner balance is derived from the complete ledger:
             *
             *     credits - debits
             *
             * Credits may include:
             * - collected rent entitlement;
             * - owner deposits;
             * - positive adjustments.
             *
             * Debits may include:
             * - property expenses;
             * - management fees;
             * - agent commissions;
             * - previous payouts;
             * - negative adjustments.
             *
             * Negative balances naturally carry forward.
             */
            /*
             * V1.0.8: withdrawals draw ONLY from the Payout account —
             * the rent-derived side. Money in the Deposit/Expense
             * account is earmarked and must be reserve-transferred back
             * before it becomes withdrawable.
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
             * Create the payout header first.
             *
             * If any later validation/allocation fails, the surrounding
             * database transaction rolls this record back automatically.
             */
            $payout = OwnerPayout::create([
                'owner_account_id' => $account->id,
                'amount' => $amount,
                'payout_date' => $payoutDate,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            /*
             * Determine how much of the owner's historical credits has
             * already been economically consumed by non-payout debits.
             *
             * These include:
             * - expenses;
             * - management fees;
             * - agent commissions;
             * - debit adjustments.
             *
             * Payout debits are deliberately excluded because previous
             * payout consumption is already represented explicitly by
             * OwnerPayoutAllocation records.
             *
             * Example:
             *
             * Rent credit     + 10,000
             * Expense         -  3,000
             *
             * Only GHS 7,000 of that historical credit remains economically
             * available for payout attribution.
             */
            $debitsToAbsorb = (int) OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->where('direction', 'debit')
                ->where('category', '!=', 'payout')
                ->sum('amount');

            /*
             * Amount of the new payout still requiring attribution.
             */
            $remaining = $amount;

            /*
             * Owner credits are processed FIFO.
             *
             * transaction_date is the primary ordering key.
             * id provides deterministic ordering where two credits share the
             * same effective transaction date.
             */
            $credits = OwnerTransaction::query()
                ->where('owner_account_id', $account->id)
                ->where('direction', 'credit')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($credits as $credit) {
                if ($remaining <= 0) {
                    break;
                }

                /*
                 * First consume historical non-payout debits against the
                 * oldest credits.
                 *
                 * This is an attribution calculation only. No new ledger
                 * transaction is created here because those debits already
                 * exist in OwnerTransaction.
                 */
                $absorbedByDebits = min(
                    $debitsToAbsorb,
                    $credit->amount
                );

                $debitsToAbsorb -= $absorbedByDebits;

                /*
                 * Previous payouts may already have consumed part of this
                 * positive credit. Those amounts are represented directly by
                 * OwnerPayoutAllocation records.
                 */
                $alreadyPaidOut = (int) $credit
                    ->payoutAllocations()
                    ->sum('amount');

                /*
                 * Calculate the portion of this credit that is still both:
                 *
                 * 1. not economically consumed by non-payout debits; and
                 * 2. not already attributed to an earlier payout.
                 */
                $creditAvailable = $credit->amount
                    - $absorbedByDebits
                    - $alreadyPaidOut;

                if ($creditAvailable <= 0) {
                    continue;
                }

                /*
                 * Consume only what is required from this credit before
                 * moving to the next FIFO credit.
                 */
                $allocationAmount = min(
                    $remaining,
                    $creditAvailable
                );

                OwnerPayoutAllocation::create([
                    'owner_payout_id' => $payout->id,
                    'owner_transaction_id' => $credit->id,
                    'amount' => $allocationAmount,
                ]);

                $remaining -= $allocationAmount;
            }

            /*
             * Since we already validated that the owner has sufficient NET
             * balance, failure to attribute the full payout indicates an
             * inconsistent ledger or allocation history.
             *
             * Rolling back is safer than recording an unattributable payout.
             */
            if ($remaining !== 0) {
                throw new RuntimeException(
                    __('business.owner.payout_allocation_failed')
                );
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
            $payout->forceFill([
                'statement' => $this->breakdown->compose($payout),
                'statement_frozen_at' => now(),
            ])->save();

            return $payout;
        });
    }
}
