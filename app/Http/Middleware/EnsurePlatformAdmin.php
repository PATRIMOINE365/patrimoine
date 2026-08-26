<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware for the platform administration console.
 *
 * Platform staff are users of the internal Kality Ltd organisation
 * whose verified email address belongs to the platform domain. Both
 * conditions are required: the domain rule alone must never grant
 * console access to an account living inside a customer organisation,
 * and platform-organisation membership alone must never survive an
 * email change away from the domain.
 */
class EnsurePlatformAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isPlatformAdmin()) {
            abort(
                403,
                __('api.auth.forbidden')
            );
        }

        return $next($request);
    }
}
