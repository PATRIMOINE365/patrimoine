<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * V1.0.7 update log.
 *
 * Serves the localized release history (lang/{en,fr}/releases.php) plus
 * the currently running version, so every authenticated role can see
 * what changed in the version they are on and in previous releases.
 */
class ReleaseLogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'current_version' => (string) config('patrimoine.release'),
            'title' => __('releases.title'),
            'entries' => __('releases.entries'),
        ]);
    }
}
