<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that can be put out of the way.
 *
 * Deliberately NOT a global scope. Archiving must not reach the reports or
 * the documents — a set of accounts whose totals move when somebody tidies
 * a list is a set of accounts nobody can trust — so each list opts in by
 * calling notArchived() on its own query.
 */
trait Archivable
{
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'archived_by_user_id'
        );
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * The rows a list should show.
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull(
            $query->getModel()->getTable().'.archived_at'
        );
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull(
            $query->getModel()->getTable().'.archived_at'
        );
    }

    /**
     * Whether this record can still be removed outright.
     *
     * The lists ask so the button can say the truth before it is pressed:
     * a record that can be deleted offers Delete, and one that cannot
     * offers Archive. Appended per list rather than always, because
     * answering it costs a handful of exists() queries and nothing but a
     * list of rows with buttons on them needs to know.
     */
    public function getIsDeletableAttribute(): bool
    {
        return app(\App\Services\BusinessRecordDeletionService::class)
            ->isDeletable($this);
    }
}
