<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Header record for a batch of expenses billed DIRECTLY to one owner.
 *
 * A bill groups one or more OwnerExpense lines recorded together from
 * the Owners workspace. Direct billed lines carry no Building context
 * (building_id is NULL) because the charge belongs to the owner, not
 * to shared Building ownership.
 *
 * The bill carries the printed identity (bill number, bill date, total)
 * used by the itemized PDF bill and its email delivery.
 */
class OwnerExpenseBill extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_account_id',
        'bill_number',
        'bill_date',
        'total_amount',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'bill_date' => 'date',
        ];
    }

    /**
     * Owner billed by this expense bill.
     */
    public function ownerAccount(): BelongsTo
    {
        return $this->belongsTo(OwnerAccount::class);
    }

    /**
     * Itemized expense lines belonging to this bill.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(OwnerExpense::class);
    }
}
