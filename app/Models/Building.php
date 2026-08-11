<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a physical property/building managed in Patrimoine.
 *
 * Ownership exists at the Building level in Patrimoine 1.0.
 * Individual Units therefore inherit the ownership structure of
 * their parent Building.
 */
class Building extends Model
{
    /**
     * Attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'address',
        'location',
        'latitude',
        'longitude',
        'notes',
    ];

    /**
     * Cast geographic coordinates to decimal values.
     *
     * Strings are deliberately used as the cast precision because
     * Laravel's decimal cast preserves the configured scale.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Units physically belonging to this Building.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Ownership allocations defined for this Building.
     *
     * Application-level validation will ensure that the active
     * ownership percentages total 100%.
     */
    public function ownerships(): HasMany
    {
        return $this->hasMany(BuildingOwner::class);
    }
}
