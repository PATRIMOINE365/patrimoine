<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when tenant-scoped work is attempted while no organisation
 * context is bound.
 *
 * Failing loudly here is deliberate: silently defaulting to any
 * organisation could file one customer's records under another.
 */
class MissingOrganisationContextException extends RuntimeException
{
}
