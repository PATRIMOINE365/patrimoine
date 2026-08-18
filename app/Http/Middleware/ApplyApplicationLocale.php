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

        return $next(
            $request
        );
    }
}
