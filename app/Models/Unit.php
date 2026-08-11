<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an individually leasable Unit within a Building.
 *
 * A Building may contain one or many Units.
 *
 * Patrimoine deliberately does NOT store a Unit status such as
 * "Vacant" or "Occupied". That status will be derived from active
 * Lease records to avoid duplicated or inconsistent state.
 */
class Unit extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'building_id',
        'name',
        'description',
    ];

    /**
     * Building containing this Unit.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
