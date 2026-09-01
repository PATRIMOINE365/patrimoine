<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationLocaleService;
use App\Support\OrganisationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expose organisation-wide presentation settings to browser clients.
 *
 * The endpoint intentionally contains no sensitive configuration.
 *
 * Login also needs access to the organisation language before a user is
 * authenticated, so this endpoint is public and returns presentation-only
 * metadata.
 */
class ApplicationPresentationController extends Controller
{
    public function __invoke(
        Request $request,
        ApplicationLocaleService $locale,
        ApplicationIdentityService $identity
    ): JsonResponse {
        /*
         * V1.0.10 multi-tenancy: the route stays public so the sign-in
         * screen can render, but when a bearer token accompanies the
         * request the caller receives THEIR organisation presentation
         * settings rather than platform defaults. Resolving the sanctum
         * guard directly performs token authentication without
         * requiring auth middleware on the route.
         */
        $user = $request->user('sanctum');

        if (
            $user instanceof User
            && $user->organisation_id !== null
        ) {
            OrganisationContext::bind(
                (int) $user->organisation_id
            );
        }

        $configuration =
            $locale->browserConfiguration();

        /*
         * The organisation display name is non-sensitive presentation
         * metadata. Exposing it here allows every authenticated role to show
         * the correct application identity without granting Settings access.
         */
        $organisation =
            $identity->managingOrganisation();

        $configuration[
            'organisation_name'
        ] =
            $organisation?->legal_name
            ?? $organisation?->name
            ?? 'Patrimoine';

        /*
         * Lease creation needs the organisation-wide VAT default, but
         * Property Manager and Viewer must not receive access to the full
         * Administrator-only Managing Organisation settings endpoint.
         *
         * The VAT default is non-sensitive application presentation/
         * operational configuration and is therefore exposed alongside the
         * existing language and currency configuration.
         */
        $settings =
            ApplicationSetting::query()
                ->first();

        $configuration[
            'default_vat_rate'
        ] =
            (float) (
                $settings?->default_vat_rate
                ?? 18
            );

        /*
         * V1.0.29: whether the organisation emails its parties at all.
         * Every workspace needs it to explain, before anybody clicks
         * Send, that nothing will leave the building. Non-sensitive
         * operational configuration, like the VAT default above.
         */
        $configuration[
            'party_emails_enabled'
        ] =
            (bool) (
                $settings?->party_emails_enabled ?? true
            );

        /*
         * V1.0.42: whether the data-protection tools are on screen. Off
         * unless the organisation has switched them on, and read here
         * rather than from a capability because it is not about who may
         * use them — it is about whether they are offered at all.
         */
        $configuration[
            'data_tools_enabled'
        ] =
            (bool) (
                $settings?->data_tools_enabled ?? false
            );

        /*
         * V1.0.43: how the parties list is ordered. It used to be a
         * tickbox on the list itself, remembered nowhere, so two people
         * reading the same list read it in different orders. It is the
         * organisation's decision now, and every browser reads it from
         * here.
         */
        $configuration[
            'sort_parties_by_surname'
        ] =
            (bool) (
                $settings?->sort_parties_by_surname ?? false
            );

        /*
         * V1.0.7: the running release is non-sensitive and lets Settings,
         * the Help page and the update log show the current version.
         */
        $configuration['release'] =
            (string) config('patrimoine.release');

        return response()->json(
            $configuration
        );
    }
}
