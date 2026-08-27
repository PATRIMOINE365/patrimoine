<?php

namespace App\Http\Middleware;

use App\Services\ApplicationLocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the Managing Organisation language to the current request.
 *
 * Patrimoine language is organisation-wide rather than per-user.
 *
 * Existing/fresh installations safely fall back to the configured
 * compatibility language through ApplicationLocaleService.
 */
class ApplyApplicationLocale
{
    public function __construct(
        private ApplicationLocaleService $locale
    ) {}

    /**
     * Apply the application language before controllers/views execute.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $this->locale->applyLanguage();

        $response = $next(
            $request
        );

        /*
         * Rendered language can now depend on the first-paint language
         * cookie, so any shared or heuristic HTTP cache must key on it.
         */
        $existing =
            $response->headers->get('Vary');

        if (
            $existing === null
            || ! preg_match(
                '/\bCookie\b/i',
                $existing
            )
        ) {
            $response->headers->set(
                'Vary',
                $existing === null || $existing === ''
                    ? 'Cookie'
                    : $existing.', Cookie'
            );
        }

        return $response;
    }
}
