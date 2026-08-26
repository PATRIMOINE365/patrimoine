<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ApplicationLocaleService;
use App\Support\OrganisationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the authenticated user's organisation as the tenant for the
 * remainder of the request.
 *
 * The Authenticated auth event performs the same binding the moment any
 * guard resolves a user, so this middleware is a belt-and-braces
 * guarantee (and the place where a suspended organisation is turned
 * away) rather than the only line of defence.
 */
class SetOrganisationContext
{
    public function __construct(
        private ApplicationLocaleService $locale
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->organisation_id !== null) {
            OrganisationContext::bind((int) $user->organisation_id);

            /*
             * The global locale middleware ran before authentication,
             * when no organisation was bound; now that the tenant is
             * known, apply its configured language.
             */
            $this->locale->applyLanguage();

            $organisation = $user->organisation;

            if ($organisation !== null && ! $organisation->isActive()) {
                /*
                 * A suspended organisation keeps its data but loses
                 * access. Tokens are not revoked so reactivation is
                 * instant.
                 */
                abort(
                    403,
                    __('api.auth.organisation_suspended')
                );
            }
        }

        return $next($request);
    }
}
