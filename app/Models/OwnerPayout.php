<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents money paid out to an owner.
 */
class OwnerPayout extends Model
{
    use BelongsToOrganisation;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_account_id',
        'amount',
        'payout_date',
        'payment_method',
        'reference',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payout_date' => 'date',
        ];
    }

    public function ownerAccount(): BelongsTo
    {
        return $this->belongsTo(OwnerAccount::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(OwnerPayoutAllocation::class);
    }

    /**
     * Total amount attributed to underlying owner credits.
     */
    public function allocatedAmount(): int
    {
        return (int) $this->allocations()->sum('amount');
    }

    /**
     * Portion of this payout not yet attributed to ledger credits.
     */
    public function unallocatedAmount(): int
    {
        return $this->amount - $this->allocatedAmount();
    }
}
