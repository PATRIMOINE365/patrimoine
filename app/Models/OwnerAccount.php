<?php

namespace App\Models;

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
            ->sum('amount');
    }

    /**
     * Total owner debits.
     */
    public function debitedAmount(): int
    {
        return (int) $this->transactions()
            ->where('direction', 'debit')
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
