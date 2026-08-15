<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        ApplicationLocaleService $locale
    ): JsonResponse {
        return response()->json(
            $locale->browserConfiguration()
        );
    }
}
