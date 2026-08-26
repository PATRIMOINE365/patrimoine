<?php

namespace App\Support;

use App\Exceptions\MissingOrganisationContextException;
use Closure;

/**
 * Holds the organisation the current unit of work belongs to.
 *
 * Patrimoine V1.1.0 is multi-tenant: every business row carries an
 * organisation_id and every query is constrained to exactly one
 * organisation. This class is the single authority on WHICH organisation
 * that is.
 *
 * The context is bound:
 *
 * - for HTTP requests, from the authenticated user's organisation
 *   (SetOrganisationContext middleware + the Authenticated auth event);
 * - for console work, explicitly per organisation via runAs(), so a
 *   scheduled job can never process two organisations in one binding.
 *
 * The instance is registered as a scoped container singleton, so each
 * request (and each test application) starts unbound. Business writes
 * performed without a bound context fail loudly instead of guessing.
 */
final class OrganisationContext
{
    /**
     * The bound organisation primary key, when bound.
     */
    private ?int $organisationId = null;

    /**
     * Resolve the shared context instance for the current application
     * lifecycle.
     */
    public static function current(): self
    {
        return app(self::class);
    }

    /**
     * Bind the given organisation as the current tenant.
     */
    public static function bind(int $organisationId): void
    {
        self::current()->organisationId = $organisationId;
    }

    /**
     * Determine whether an organisation is currently bound.
     */
    public static function bound(): bool
    {
        return self::current()->organisationId !== null;
    }

    /**
     * The bound organisation id, or null when no organisation is bound.
     */
    public static function idOrNull(): ?int
    {
        return self::current()->organisationId;
    }

    /**
     * The bound organisation id.
     *
     * @throws MissingOrganisationContextException when unbound
     */
    public static function id(): int
    {
        $id = self::current()->organisationId;

        if ($id === null) {
            throw new MissingOrganisationContextException(
                'No organisation context is bound for the current operation.'
            );
        }

        return $id;
    }

    /**
     * Remove the current binding.
     */
    public static function forget(): void
    {
        self::current()->organisationId = null;
    }

    /**
     * Execute the callback bound to the given organisation, restoring
     * the previous binding afterwards even when the callback throws.
     *
     * This is the required entry point for console and scheduled work
     * that iterates organisations.
     */
    public static function runAs(int $organisationId, Closure $callback): mixed
    {
        $previous = self::current()->organisationId;

        self::bind($organisationId);

        try {
            return $callback();
        } finally {
            self::current()->organisationId = $previous;
        }
    }
}
