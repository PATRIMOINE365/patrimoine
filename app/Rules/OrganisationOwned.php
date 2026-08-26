<?php

namespace App\Rules;

use App\Support\OrganisationContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Existence validation that never sees other organisations' rows.
 *
 * The plain string rule "exists:units,id" queries the table directly,
 * bypassing Eloquent's OrganisationScope; a foreign identifier that
 * happens to exist in ANOTHER organisation would pass validation and
 * only fail later (as a 404) when the scoped model lookup runs.
 *
 * Requiring the row to belong to the bound organisation makes cross-
 * organisation identifier probing indistinguishable from a plain
 * validation error.
 */
class OrganisationOwned
{
    /**
     * An exists rule constrained to the bound organisation.
     */
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where(
            static function ($query): void {
                $query->where(
                    'organisation_id',
                    OrganisationContext::id()
                );
            }
        );
    }
}
