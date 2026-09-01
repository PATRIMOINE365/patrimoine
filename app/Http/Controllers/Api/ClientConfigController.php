<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What an installed application asks before it shows anything.
 *
 * An installed application cannot be recalled. Once one exists there has
 * to be a way to tell a copy of it that it is too old to run, or that the
 * service is closed for the next hour, without waiting on an app-store
 * review — and the only moment that instruction can be delivered is the
 * moment the application starts.
 *
 * That is the whole reason this endpoint exists in the first release
 * rather than the release where it is first needed. It also carries the
 * feature switches and the outbound links, so there is one call at launch
 * rather than four.
 *
 * Public by design: it is consulted before anybody has signed in, and it
 * says nothing that is not already true of the product.
 */
class ClientConfigController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $maintenance = (array) config('patrimoine.clients.maintenance', []);

        return response()->json([
            /*
             * The release running on this server. A client shows it in
             * its about screen so a support conversation can start with
             * two version numbers instead of a guess.
             */
            'release' => (string) config('patrimoine.release'),

            'api' => [
                'current' => (string) config('patrimoine.api.current', 'v1'),
                'supported' => array_values(
                    (array) config('patrimoine.api.supported', ['v1'])
                ),
            ],

            /*
             * Below this version an application must refuse to run and
             * send the person to the store. At or above it, and below
             * "latest", an update is worth offering but not forcing.
             */
            'minimum_version' => (array) config(
                'patrimoine.clients.minimum_version',
                []
            ),

            'latest_version' => (array) config(
                'patrimoine.clients.latest_version',
                []
            ),

            'store_url' => (array) config(
                'patrimoine.clients.store_url',
                []
            ),

            /*
             * The kill switch. Turning this on closes every installed
             * client at once, which is the only remedy available when
             * something is wrong on the server and the clients are
             * making it worse.
             */
            'maintenance' => [
                'active' => (bool) ($maintenance['active'] ?? false),
                'message' => $maintenance['message'] ?? null,
            ],

            /*
             * Signing up is a web journey and stays one. It leads to
             * choosing a plan and paying for it, and neither of those
             * belongs inside a mobile application.
             */
            'features' => [
                'signup_in_app' => false,
                'payments_in_app' => false,
                'biometric_unlock' => true,
            ],

            'links' => [
                'signup' => url('/signup'),
                'forgot_password' => url('/forgot-password'),
                'terms' => url('/terms'),
                'privacy' => url('/privacy'),
                'errors' => url('/errors'),
                'support' => url('/help'),
            ],

            'languages' => array_values(
                array_keys((array) config('patrimoine.languages', []))
            ),
        ]);
    }
}
