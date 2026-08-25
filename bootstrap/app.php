<?php

use App\Http\Middleware\ApplyApplicationLocale;
use App\Http\Middleware\AuthenticateSignedDocumentAccess;
use App\Http\Middleware\EnsureUserHasCapability;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Production runs behind the Coolify/Traefik reverse proxy inside a
         * private Docker network; trust it so X-Forwarded-Proto is honoured
         * and generated URLs use https.
         */
        $middleware->trustProxies(at: '*');

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
            /*
             * Capability authorization is authoritative from V1.0.3
             * Activity C onward.
             *
             * The legacy role alias remains temporarily registered only for
             * compatibility with code outside the migrated business routes.
             */
            'capability' => EnsureUserHasCapability::class,
            'role' => EnsureUserHasRole::class,

            /*
             * V1.0.8: signed-URL access to PDF document endpoints, so a
             * browser tab can navigate straight to a document without a
             * Bearer header.
             */
            'document.signed' => AuthenticateSignedDocumentAccess::class,
        ]);

        /*
         * Signed-document authentication must run before 'auth:sanctum'
         * even when attached at route level, otherwise the group's auth
         * middleware rejects the token-less signed request first.
         *
         * The kernel priority list anchors authentication through the
         * AuthenticatesRequests contract, not the concrete Authenticate
         * class — anchoring on the wrong one silently appends to the
         * END of the priority list.
         */
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            AuthenticateSignedDocumentAccess::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Every Patrimoine API exception is rendered as JSON regardless of
         * the client's Accept header.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*'),
        );
    })
    ->create();
