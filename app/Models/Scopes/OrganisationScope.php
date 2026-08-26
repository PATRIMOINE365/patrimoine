<?php

namespace App\Models\Scopes;

use App\Support\OrganisationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query on a tenant-owned model to the organisation
 * bound in OrganisationContext.
 *
 * When no context is bound (console maintenance, the test runner's
 * direct assertions) the scope adds no constraint; binding is enforced
 * where it matters: every authenticated HTTP request binds the
 * requester's organisation before any business query runs, and writes
 * without a bound context are rejected by BelongsToOrganisation.
 */
class OrganisationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $organisationId = OrganisationContext::idOrNull();

        if ($organisationId === null) {
            return;
        }

        $builder->where(
            $model->qualifyColumn('organisation_id'),
            $organisationId
        );
    }
}
