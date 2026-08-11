<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links part of an OwnerPayout to the owner-ledger credit it settles.
 */
class OwnerPayoutAllocation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_payout_id',
        'owner_transaction_id',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(
            related: OwnerPayout::class,
            foreignKey: 'owner_payout_id'
        );
    }

    public function ownerTransaction(): BelongsTo
    {
        return $this->belongsTo(OwnerTransaction::class);
    }
}
