<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
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
 *
 * Every Party that becomes a Building Owner must also have exactly one
 * consolidated OwnerAccount. This financial account exists independently
 * from Lease activity, rent collection or the current owner balance.
 */
class BuildingOwner extends Model
{
    use BelongsToOrganisation;

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
     * Enforce owner-account creation as a domain invariant.
     *
     * A landlord must be able to deposit money even when:
     *
     * - the Building has no active Lease;
     * - all previous rent has already been paid out;
     * - no tenant advance exists;
     * - the OwnerAccount has never had a transaction.
     *
     * Creating the OwnerAccount when ownership is established ensures the
     * Payments workspace can always find every genuine property owner.
     */
    protected static function booted(): void
    {
        static::created(
            function (BuildingOwner $ownership): void {
                OwnerAccount::firstOrCreate([
                    'party_id' => $ownership->party_id,
                ]);
            }
        );

        /*
         * V1.0.50: the reverse of the invariant above. Bulk deletes
         * bypass model events, so the two places that remove ownerships
         * in bulk — replacing a building's owners and deleting the
         * building — call OwnerAccount::releaseIfUnused() themselves.
         */
        static::deleted(
            function (BuildingOwner $ownership): void {
                OwnerAccount::releaseIfUnused(
                    (int) $ownership->party_id
                );
            }
        );
    }

    /**
     * Building associated with this ownership allocation.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(
            Building::class
        );
    }

    /**
     * Party that owns this portion of the Building.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(
            Party::class
        );
    }
}
