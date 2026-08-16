<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationLocaleService;
use Illuminate\Http\JsonResponse;

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
        ApplicationLocaleService $locale,
        ApplicationIdentityService $identity
    ): JsonResponse {
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

        return response()->json(
            $configuration
        );
    }
}
