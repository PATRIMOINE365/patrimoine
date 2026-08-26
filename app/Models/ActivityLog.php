<?php

namespace App\Models;

use App\Models\Scopes\OrganisationScope;
use App\Support\OrganisationContext;
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
    'organisation_id',
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
     *
     * V1.1.0 multi-tenancy: unlike other business tables, an activity
     * event MAY belong to no organisation (a failed sign-in against an
     * unknown email address is a platform-level fact). The organisation
     * scope still applies to every read while a context is bound, and
     * rows are stamped from the bound context when the writer did not
     * resolve an organisation explicitly.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new OrganisationScope);

        static::creating(
            function (ActivityLog $log): void {
                if ($log->organisation_id === null) {
                    $log->organisation_id =
                        OrganisationContext::idOrNull();
                }
            }
        );

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
