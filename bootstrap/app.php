<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApplyApplicationLocale;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureUserHasRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Patrimoine uses one Managing Organisation language for both
         * browser and API presentation. Apply it early to every request.
         */
        $middleware->append(
            ApplyApplicationLocale::class
        );
        /*
        * Patrimoine currently authenticates through API endpoints rather
        * than a traditional server-rendered login page.
        *
        * Unauthenticated requests therefore receive an authentication
        * response instead of being redirected to a route named "login".
        */
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => null
        );

        /*
        * Application authorization middleware.
        *
        * Usage example:
        *     ->middleware('role:property_manager')
        */
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Every Patrimoine API exception is rendered as JSON regardless of
         * the client's Accept header.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool =>
                $request->is('api/*'),
        );
    })
    ->create();
