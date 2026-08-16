<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict an authenticated route to one Patrimoine application role.
 *
 * Authentication must run before this middleware so a User is available
 * on the incoming request.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        $user = $request->user();

        /*
         * Normally auth:sanctum has already rejected an anonymous request.
         * Keeping this defensive check prevents accidental authorization if
         * the middleware is ever used without authentication in the future.
         */
        if ($user === null) {
            abort(
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated.'
            );
        }

        $requiredRole =
            UserRole::tryFrom($role);

        /*
         * Unknown configured roles fail closed.
         */
        if ($requiredRole === null) {
            abort(
                Response::HTTP_FORBIDDEN,
                'You are not authorized to perform this action.'
            );
        }

        /*
         * Temporary compatibility rule for Activity B.
         *
         * Administrator inherits the old Property Manager route access until
         * Activity C replaces this legacy role middleware with centralized
         * capability authorization.
         */
        $authorized =
            $user->hasRole($requiredRole)
            || (
                $requiredRole === UserRole::PropertyManager
                && $user->hasRole(UserRole::Administrator)
            );

        if (! $authorized) {
            abort(
                Response::HTTP_FORBIDDEN,
                'You are not authorized to perform this action.'
            );
        }

        return $next($request);
    }
}
