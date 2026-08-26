<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consolidated financial account for one property owner.
 *
 * Balance is derived entirely from ledger transactions:
 *
 *     credits - debits
 *
 * A negative result is intentionally allowed and represents money the
 * owner must recover through future rent or owner deposits.
 */
class OwnerAccount extends Model
{
    use BelongsToOrganisation;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'party_id',
        'status',
    ];

    /**
     * Party represented by this OwnerAccount.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Complete accounting ledger for the owner.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(OwnerTransaction::class);
    }

    /**
     * Payouts made to this owner.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(OwnerPayout::class);
    }

    /**
     * Total owner credits.
     */
    public function creditedAmount(): int
    {
        return (int) $this->transactions()
            ->where('direction', 'credit')
            ->where('category', '<>', 'reserve_transfer')
            ->sum('amount');
    }

    /**
     * Total owner debits.
     */
    public function debitedAmount(): int
    {
        return (int) $this->transactions()
            ->where('direction', 'debit')
            ->where('category', '<>', 'reserve_transfer')
            ->sum('amount');
    }

    /**
     * Current owner funds held by Patrimoine.
     *
     * Negative balances are permitted and intentionally carried forward.
     */
    public function balance(): int
    {
        return $this->creditedAmount() - $this->debitedAmount();
    }

    /**
     * V1.0.8 Deposit/Expense account: the owner's earmarked money.
     *
     * Owner deposits fund it, every expense draws from it, and manual
     * reserve transfers move money between it and the Payout account.
     * It may go negative — expenses beyond the deposits are debt the
     * owner owes the agency, never silently taken from rent money.
     */
    public function depositAccountBalance(): int
    {
        $deposits = (int) $this->transactions()
            ->where('category', 'owner_deposit')
            ->where('direction', 'credit')
            ->sum('amount');

        /*
         * V1.0.8 expense bill payments choose their funding source.
         *
         * Only Deposit-account-funded expenses draw from this balance;
         * a NULL funding source is the historical default and belongs
         * here too. Payout-funded expense payments reduce the Payout
         * account instead, which falls out of the balance()-minus-
         * deposit arithmetic without further work.
         *
         * Cancelled expense payments are credit reversal rows in the
         * same category and funding source, so they are netted out.
         */
        $expenseDebits = (int) $this->transactions()
            ->where('category', 'expense')
            ->where('direction', 'debit')
            ->where(function ($query): void {
                $query
                    ->whereNull('funding_source')
                    ->orWhere('funding_source', 'deposit_account');
            })
            ->sum('amount');

        $expenseCredits = (int) $this->transactions()
            ->where('category', 'expense')
            ->where('direction', 'credit')
            ->where(function ($query): void {
                $query
                    ->whereNull('funding_source')
                    ->orWhere('funding_source', 'deposit_account');
            })
            ->sum('amount');

        $transfersIn = (int) $this->transactions()
            ->where('category', 'reserve_transfer')
            ->where('direction', 'credit')
            ->sum('amount');

        $transfersOut = (int) $this->transactions()
            ->where('category', 'reserve_transfer')
            ->where('direction', 'debit')
            ->sum('amount');

        return $deposits
            - $expenseDebits
            + $expenseCredits
            + $transfersIn
            - $transfersOut;
    }

    /**
     * V1.0.8 Payout account: rent-derived money the owner can withdraw.
     *
     * Withdrawals are capped here; deposit-side money must be
     * reserve-transferred back before it becomes withdrawable.
     */
    public function payoutAccountBalance(): int
    {
        return $this->balance()
            - $this->depositAccountBalance();
    }

    /**
     * V1.0.8: net position of every ledger category in one query.
     *
     * Credits count positive and debits negative, so a category's value
     * reads as its effect on the owner balance. All seven categories are
     * always present, zero included, mirroring how tenant fund accounts
     * are presented.
     *
     * @return array<string, int>
     */
    public function categoryTotals(): array
    {
        $totals = array_fill_keys(
            [
                'rent_entitlement',
                'owner_deposit',
                'management_fee',
                'agent_commission',
                'expense',
                'payout',
                'adjustment',
                'reserve_transfer',
            ],
            0
        );

        $rows = $this->transactions()
            ->selectRaw(
                'category, direction, SUM(amount) as total'
            )
            ->groupBy('category', 'direction')
            ->get();

        foreach ($rows as $row) {
            $signed =
                $row->direction === 'credit'
                    ? (int) $row->total
                    : -(int) $row->total;

            $totals[$row->category] =
                ($totals[$row->category] ?? 0)
                + $signed;
        }

        return $totals;
    }
}
