<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\DocumentLinkService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate document requests that carry a valid signed URL.
 *
 * A valid signature authenticates the signed user on the web guard,
 * which the Sanctum guard consults first, so the route's ordinary
 * 'auth:sanctum' and 'capability:*' middleware continue to run and
 * authorize exactly as they would for a Bearer-token request.
 *
 * This middleware is registered before Authenticate in the middleware
 * priority list; it must therefore only ever be attached to the
 * read-only document routes that DocumentLinkService can sign.
 */
class AuthenticateSignedDocumentAccess
{
    public function __construct(
        private readonly DocumentLinkService $links
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! $request->query('signature')) {
            return $next($request);
        }

        if (! $this->links->validate($request)) {
            abort(
                Response::HTTP_FORBIDDEN,
                __('api.documents.link_invalid')
            );
        }

        $user = User::query()->find(
            (int) $request->query('user')
        );

        if (
            $user === null
            || ! $user->isActive()
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
                __('api.documents.link_invalid')
            );
        }

        Auth::guard('web')->setUser(
            $user
        );

        return $next($request);
    }
}
