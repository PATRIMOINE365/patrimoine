<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\Party;

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
