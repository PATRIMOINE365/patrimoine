<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicensingService;
use Illuminate\Http\JsonResponse;

/**
 * Expose the authenticated organisation's plan, usage and the full
 * plan matrix for the licence page.
 *
 * Read-only: licences are issued and extended by the platform operator
 * (a future administration console); no self-service purchase exists in
 * V1.1.0.
 */
class LicenseController extends Controller
{
    /**
     * The organisation's current licensing picture.
     */
    public function show(
        LicensingService $licensing
    ): JsonResponse {
        return response()->json(
            $licensing->presentation()
        );
    }
}
