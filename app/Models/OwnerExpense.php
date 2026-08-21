<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an expense incurred against a Building or Unit.
 *
 * The expense itself records the full cost. Individual owner shares are
 * posted separately into each owner's ledger according to Building
 * ownership percentages.
 *
 * V1.0.7: an expense may instead be billed DIRECTLY to one owner via an
 * OwnerExpenseBill. Such lines have a NULL building_id and reference
 * their bill through owner_expense_bill_id.
 */
class OwnerExpense extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'building_id',
        'unit_id',
        'owner_expense_bill_id',
        'description',
        'amount',
        'expense_date',
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
            'expense_date' => 'date',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Owner expense bill this line belongs to, when the expense was
     * billed directly to one owner rather than allocated by Building
     * ownership.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(
            OwnerExpenseBill::class,
            'owner_expense_bill_id'
        );
    }
}
