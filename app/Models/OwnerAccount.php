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
}
