<?php

namespace App\Http\Middleware;

use App\Enums\UserCapability;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce one central Patrimoine capability on an authenticated request.
 *
 * Authentication must run before this middleware.
 */
class EnsureUserHasCapability
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $capability
    ): Response {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(
                Response::HTTP_UNAUTHORIZED,
                __('api.auth.unauthenticated')
            );
        }

        $requiredCapability =
            UserCapability::tryFrom($capability);

        /*
         * Invalid middleware configuration must always fail closed.
         */
        if (
            $requiredCapability === null
            || ! $user->canPerform($requiredCapability)
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
                __('api.auth.forbidden')
            );
        }

        return $next($request);
    }
}
