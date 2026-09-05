<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\Party;
use App\Support\OrganisationContext;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the business identity of this Patrimoine installation.
 *
 * Patrimoine 1.0 is single-tenant. One Party is therefore configured as
 * the managing organisation and acts as the issuer of invoices, receipts,
 * reports and business correspondence.
 *
 * Presentation and notification layers should use this service instead
 * of querying ApplicationSetting directly.
 */
class ApplicationIdentityService
{
    /**
     * Return the configured managing organisation, when one exists.
     */
    public function managingOrganisation(): ?Party
    {
        /*
         * Application identity is used by shared presentation surfaces,
         * including pages that can render before Patrimoine's database
         * schema exists during setup, testing or recovery.
         *
         * In that state the product fallback identity must remain usable
         * instead of preventing the application from rendering.
         */
        if (! Schema::hasTable('application_settings')) {
            return null;
        }

        /*
         * V1.0.50: no organisation bound, no organisation answered.
         *
         * OrganisationScope adds no constraint while nothing is bound, so
         * this query used to return the FIRST organisation's settings to
         * anybody at all: the public sign-in screen, the presentation
         * endpoint before sign-in and the password-reset mail were all
         * signing themselves with organisation 1's legal name. Callers
         * that legitimately need an organisation's identity bind it
         * first — every authenticated request does, and console work
         * goes through OrganisationContext::runAs().
         */
        if (! OrganisationContext::bound()) {
            return null;
        }

        $settings = ApplicationSetting::query()
            ->with('managingOrganisation.roles')
            ->first();

        return $settings?->managingOrganisation;
    }

    /**
     * Return the human-facing business name.
     *
     * The Patrimoine fallback keeps development and test workflows
     * functional before initial organisation setup has been completed.
     */
    public function displayName(): string
    {
        $organisation = $this->managingOrganisation();

        return $organisation?->legal_name
            ?? $organisation?->name
            ?? 'Patrimoine';
    }

    /**
     * Return the configured managing-organisation email address.
     *
     * Null means Laravel should use the application's normal configured
     * mail sender instead.
     */
    public function email(): ?string
    {
        $email = trim(
            (string) $this->managingOrganisation()?->email
        );

        return $email !== ''
            ? $email
            : null;
    }
}
