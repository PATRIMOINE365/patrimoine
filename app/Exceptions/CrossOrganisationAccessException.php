<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a write would associate a record with an organisation
 * other than the one currently bound.
 *
 * This is the last line of defence: reads are already constrained by
 * the OrganisationScope, and identifiers from other organisations
 * resolve to 404 long before a write is attempted.
 */
class CrossOrganisationAccessException extends RuntimeException
{
}
