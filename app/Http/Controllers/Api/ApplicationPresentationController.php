<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json(
            $configuration
        );
    }
}
