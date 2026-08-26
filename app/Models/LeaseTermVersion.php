<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable historical snapshot of contractual Lease terms.
 *
 * LeaseTermVersion is append-only. Current terms continue to live on Lease
 * for normal operational reads while this model preserves what previously
 * applied.
 */
class LeaseTermVersion extends Model
{
    use BelongsToOrganisation;

    public const UPDATED_AT = null;

    protected $fillable = [
        'lease_id',
        'version_number',
        'event_type',
        'effective_from',
        'effective_to',
        'terms',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'terms' => 'array',
        ];
    }

    protected static function booted(): void
    {
        /*
         * Historical term versions must never be mutated in place.
         *
         * Corrections/extensions are represented by another appended
         * version.
         */
        static::updating(
            function (): never {
                throw new LogicException(
                    'Lease term versions are immutable.'
                );
            }
        );

        static::deleting(
            function (): never {
                throw new LogicException(
                    'Lease term versions cannot be deleted.'
                );
            }
        );
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(
            Lease::class
        );
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }
}
