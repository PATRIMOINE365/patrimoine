<?php

use App\Http\Middleware\ApplyApplicationLocale;
use App\Http\Middleware\SlideAccessTokenExpiry;
use App\Support\ErrorCodes;
use Illuminate\Http\JsonResponse;
use App\Http\Middleware\AuthenticateSignedDocumentAccess;
use App\Http\Middleware\EnsureUserHasCapability;
use App\Http\Middleware\EnsureLicenseFeature;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetOrganisationContext;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        /*
         * V1.0.44: the same API, mounted a second time under its version.
         *
         * Until now every route lived at /api with no version segment,
         * which is survivable only while the client and the server ship
         * together. An installed application cannot be upgraded on
         * demand, so from the first release that has one there has to be
         * a way to introduce a breaking change beside the old behaviour
         * instead of on top of it. That way is the version segment.
         *
         * /api and /api/v1 are the same routes today. The unversioned
         * prefix stays for the browser application and for anything
         * already written against it; new clients call the version.
         */
        then: function (): void {
            foreach ((array) config('patrimoine.api.supported', ['v1']) as $version) {
                Route::middleware('api')
                    ->prefix('api/'.$version)
                    ->name($version.'.')
                    ->group(base_path('routes/api.php'));
            }
        },
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
         * V1.0.51: hardening headers and a nonce-bound Content-Security-
         * Policy on every response, so the console (and everything else)
         * is protected the same way on every host it runs on.
         */
        $middleware->append(
            SecurityHeaders::class
        );

        /*
         * The first-paint language hint is written by the browser, so it
         * cannot participate in Laravel's cookie encryption — an encrypted
         * cookie the browser cannot produce would simply be discarded.
         * It carries a language code and nothing else.
         */
        $middleware->encryptCookies(
            except: [
                \App\Services\ApplicationLocaleService::LANGUAGE_COOKIE,
            ]
        );

        /*
         * V1.0.10 multi-tenancy: once any guard has resolved a user,
         * bind that user's organisation as the tenant for the rest of
         * the request (and refuse suspended organisations). Appending
         * to both web and api groups plus anchoring after the
         * authentication contract in the priority list guarantees it
         * runs immediately after auth resolves.
         */
        $middleware->appendToGroup('api', SetOrganisationContext::class);
        $middleware->appendToGroup('web', SetOrganisationContext::class);

        /*
         * V1.0.44: a token stays alive for as long as it is used.
         *
         * The window is pushed forward after the response, so a request
         * that was going to fail still fails for its own reason, and it
         * can never be pushed past the ceiling the token was minted
         * with. Sanctum refuses an arrived expiry at the same door it
         * refuses every other invalid token.
         */
        $middleware->appendToGroup('api', SlideAccessTokenExpiry::class);

        $middleware->appendToPriorityList(
            AuthenticatesRequests::class,
            SetOrganisationContext::class
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

            /*
             * V1.0.10: plan-dependent feature gating, e.g.
             * ->middleware('license:reports').
             */
            'license' => EnsureLicenseFeature::class,

            /*
             * V1.0.11: platform administration console access.
             */
            'platform.admin' => EnsurePlatformAdmin::class,
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

        /*
         * Every failure carries its code.
         *
         * By the time an exception is rendered the message key is gone and
         * only the sentence remains, so the code is recovered from the
         * catalogue by matching that sentence, or from the status where a
         * request failed with no message of its own.
         *
         * The code is added to the body rather than replacing anything:
         * clients keep reading 'message' and 'errors' exactly as before,
         * and gain something to show the customer and quote to us.
         */
        $exceptions->respond(
            function ($response, Throwable $exception, Request $request) {
                if (! $response instanceof JsonResponse) {
                    return $response;
                }

                $payload = $response->getData(true);

                if (! is_array($payload)) {
                    return $response;
                }

                /*
                 * A record that cannot be found is rendered by Laravel with
                 * the ORM's own sentence — "No query results for model
                 * [App\Models\Building] 1". Three things wrong with sending
                 * that to anybody: it names an internal class to whoever
                 * asked, it is English whatever language the organisation
                 * reads in, and it belongs to no catalogue entry, so the
                 * code beside it could only ever come from the status.
                 *
                 * It is also the answer a customer gets when they reach for
                 * another organisation's record, which is the right answer —
                 * 404 rather than 403, because 403 would confirm the record
                 * exists — and it should not then leak what kind of record
                 * it was.
                 */
                if (
                    $response->getStatusCode() === 404
                    && is_string($payload['message'] ?? null)
                    && str_contains($payload['message'], 'No query results for model')
                ) {
                    $payload['message'] = __('api.not_found');

                    $response->setData($payload);
                }

                if (isset($payload['code'])) {
                    return $response;
                }

                $code = ErrorCodes::forResponse(
                    $payload,
                    $response->getStatusCode()
                );

                if ($code === null) {
                    return $response;
                }

                $payload['code'] = $code;

                $response->setData($payload);

                return $response;
            }
        );
    })
    ->create();
