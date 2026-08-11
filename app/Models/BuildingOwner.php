<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an ownership allocation between a Building and a Party.
 *
 * Example:
 * Building A
 * - Owner 1: 60%
 * - Owner 2: 40%
 *
 * Patrimoine 1.0 keeps ownership at Building level rather than Unit level.
 */
class BuildingOwner extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'building_id',
        'party_id',
        'ownership_percentage',
    ];

    /**
     * Cast ownership percentages to two decimal places.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ownership_percentage' => 'decimal:2',
        ];
    }

    /**
     * Building associated with this ownership allocation.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Party that owns this portion of the Building.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
