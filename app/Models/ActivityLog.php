<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable Patrimoine human-action Activity Log event.
 *
 * Activity Log is an audit trail, not an accounting journal. Events are
 * created through ActivityLogService and are never updated or deleted by
 * normal application behavior.
 */
#[Fillable([
    'user_id',
    'actor_name',
    'actor_email',
    'actor_role',
    'action',
    'entity_type',
    'entity_id',
    'entity_label',
    'before_values',
    'after_values',
    'snapshot',
    'metadata',
    'ip_address',
    'user_agent',
    'browser',
    'platform',
    'device',
])]
class ActivityLog extends Model
{
    /**
     * Activity Log events have only their original creation timestamp.
     */
    public const UPDATED_AT = null;

    /**
     * Convert structured historical fields to PHP arrays.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'snapshot' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Return the current User record when it still exists.
     *
     * Historical presentation must use the frozen actor_* fields rather
     * than assuming this relationship still exists or remains unchanged.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Activity Log rows are immutable after creation.
     */
    protected static function booted(): void
    {
        static::updating(
            function (): bool {
                return false;
            }
        );

        static::deleting(
            function (): bool {
                return false;
            }
        );
    }
}
