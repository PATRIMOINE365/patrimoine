<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a tenant-held fund balance associated with a Lease.
 *
 * The Account itself does not store a mutable balance. Its balance is
 * derived from the complete transaction ledger:
 *
 *     credits - debits
 *
 * This prevents the displayed balance from becoming disconnected from
 * the underlying accounting history.
 */
class TenantFundAccount extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lease_id',
        'type',
        'status',
    ];

    /**
     * Lease owning this tenant fund account.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Complete ledger history for this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(TenantFundTransaction::class);
    }

    /**
     * Total credits recorded in the account.
     */
    public function creditedAmount(): int
    {
        return (int) $this->transactions()
            ->where('direction', 'credit')
            ->sum('amount');
    }

    /**
     * Total debits recorded against the account.
     */
    public function debitedAmount(): int
    {
        return (int) $this->transactions()
            ->where('direction', 'debit')
            ->sum('amount');
    }

    /**
     * Current balance derived from the transaction ledger.
     */
    public function balance(): int
    {
        return $this->creditedAmount() - $this->debitedAmount();
    }
}
