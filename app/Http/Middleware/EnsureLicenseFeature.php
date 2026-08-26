<?php

namespace App\Http\Middleware;

use App\Services\LicensingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware gating plan-dependent features.
 *
 * Usage:
 *     ->middleware('license:reports')
 *     ->middleware('license:exports')
 *
 * Data itself is never gated — only plan-dependent functionality
 * (reports, exports, party portal, third-party API access).
 */
class EnsureLicenseFeature
{
    public function __construct(
        private LicensingService $licensing
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ): Response {
        if (! $this->licensing->allows($feature)) {
            abort(
                403,
                __('api.license.feature_unavailable')
            );
        }

        return $next($request);
    }
}
