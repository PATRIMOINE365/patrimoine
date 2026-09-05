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
     * V1.0.50: let go of an account that has nothing in it once the
     * party owns nothing.
     *
     * Ownership opens an account so a landlord can always be paid; the
     * reverse never happened, so a party whose only property was removed
     * — or who should never have been an owner at all — stayed on the
     * Owners list for good with an empty account. An account that has
     * carried money, a payout or a bill is history and is kept.
     */
    public static function releaseIfUnused(int $partyId): bool
    {
        if (
            BuildingOwner::query()
                ->where('party_id', $partyId)
                ->exists()
        ) {
            return false;
        }

        $account = static::query()
            ->where('party_id', $partyId)
            ->first();

        if ($account === null) {
            return false;
        }

        if (
            $account->transactions()->exists()
            || $account->payouts()->exists()
            || OwnerExpenseBill::query()
                ->where('owner_account_id', $account->id)
                ->exists()
        ) {
            return false;
        }

        return (bool) $account->delete();
    }

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
     *
     * V1.0.48: all three balances come from OwnerLedgerProjection, which
     * computes payout, deposit and total INDEPENDENTLY and verifies
     * total = payout + deposit on every read. Before that, the payout
     * figure was derived as total − deposit, which made the invariant a
     * tautology — and let three services disagree about the same ledger.
     */
    public function balance(): int
    {
        return $this->projectedBalances()['total'];
    }

    /**
     * V1.0.8 Deposit/Expense account: the owner's earmarked money.
     *
     * Owner deposits fund it, every deposit-funded expense draws from
     * it, and manual reserve transfers move money between it and the
     * Payout account. It may go negative — expenses beyond the deposits
     * are debt the owner owes the agency, never silently taken from
     * rent money.
     */
    public function depositAccountBalance(): int
    {
        return $this->projectedBalances()['deposit'];
    }

    /**
     * V1.0.8 Payout account: rent-derived money the owner can withdraw.
     *
     * Withdrawals are capped here; deposit-side money must be
     * reserve-transferred back before it becomes withdrawable.
     */
    public function payoutAccountBalance(): int
    {
        return $this->projectedBalances()['payout'];
    }

    /**
     * @return array{payout: int, deposit: int, total: int}
     */
    private function projectedBalances(): array
    {
        return app(\App\Services\OwnerLedgerProjection::class)
            ->balancesFor((int) $this->id);
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
