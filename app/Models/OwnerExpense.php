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
 */
class OwnerExpense extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'building_id',
        'unit_id',
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
}
