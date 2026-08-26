<?php

namespace App\Models\Concerns;

use App\Exceptions\CrossOrganisationAccessException;
use App\Exceptions\MissingOrganisationContextException;
use App\Models\Organisation;
use App\Models\Scopes\OrganisationScope;
use App\Support\OrganisationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks an Eloquent model as owned by exactly one Organisation.
 *
 * Four independent layers keep customer data apart:
 *
 * 1. every query is constrained by OrganisationScope while an
 *    organisation context is bound (all authenticated HTTP traffic);
 * 2. new rows are stamped with the bound organisation automatically,
 *    and creating a row with no bound context and no explicit
 *    organisation_id fails loudly;
 * 3. a row can never be created for, or moved to, an organisation
 *    other than the bound one;
 * 4. the database schema itself makes organisation_id NOT NULL with a
 *    foreign key, so no code path can produce an unowned row.
 */
trait BelongsToOrganisation
{
    /**
     * Boot the trait for the using model.
     */
    public static function bootBelongsToOrganisation(): void
    {
        static::addGlobalScope(new OrganisationScope);

        static::creating(function (Model $model): void {
            $boundId = OrganisationContext::idOrNull();

            if ($model->getAttribute('organisation_id') === null) {
                if ($boundId === null) {
                    throw new MissingOrganisationContextException(sprintf(
                        'Refusing to create %s without an organisation: '
                        .'no organisation context is bound and no '
                        .'organisation_id was provided.',
                        static::class
                    ));
                }

                $model->setAttribute('organisation_id', $boundId);

                return;
            }

            if (
                $boundId !== null
                && (int) $model->getAttribute('organisation_id') !== $boundId
            ) {
                throw new CrossOrganisationAccessException(sprintf(
                    'Refusing to create %s for organisation %d while '
                    .'organisation %d is bound.',
                    static::class,
                    (int) $model->getAttribute('organisation_id'),
                    $boundId
                ));
            }
        });

        static::updating(function (Model $model): void {
            if (! $model->isDirty('organisation_id')) {
                return;
            }

            /*
             * organisation_id is written once at creation and is
             * immutable afterwards. Re-homing a record to another
             * organisation is not a supported operation anywhere in
             * Patrimoine.
             */
            throw new CrossOrganisationAccessException(sprintf(
                'Refusing to move %s #%s to another organisation.',
                static::class,
                (string) $model->getKey()
            ));
        });
    }

    /**
     * The organisation that owns this record.
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
