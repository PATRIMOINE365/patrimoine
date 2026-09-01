<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * The two files that make an ordinary Patrimoine link open the app.
 *
 * Patrimoine puts addressable links into e-mail that is read months after
 * it is sent - a document, a notification, an error code. Associating the
 * application with those same https paths means every link already in
 * somebody's inbox starts opening the app the day it is installed. A
 * private scheme would have left all of that mail pointing at a browser
 * for ever, which is why the association is set up before the first
 * build rather than after it.
 *
 * Both files are withheld until they are configured. Apple caches the
 * association through its own CDN, so publishing a placeholder is worse
 * than publishing nothing: the wrong answer is the one that sticks.
 */
class AppAssociationController extends Controller
{
    /**
     * apple-app-site-association, for iOS Universal Links.
     */
    public function apple(): JsonResponse
    {
        $teamId = trim((string) config('patrimoine.deep_links.apple.team_id'));

        $bundleId = trim((string) config('patrimoine.deep_links.apple.bundle_id'));

        if ($teamId === '' || $bundleId === '') {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()
            ->json([
                'applinks' => [
                    'details' => [
                        [
                            'appIDs' => [
                                $teamId.'.'.$bundleId,
                            ],

                            /*
                             * The paths the application claims. They are
                             * the frozen deep-link surface recorded in
                             * docs/MOBILE-CONTRACT.md; changing their
                             * shape breaks links already sent, so the
                             * list is deliberately explicit rather than
                             * a wildcard over the whole site.
                             */
                            'components' => self::components(),
                        ],
                    ],
                ],

                /*
                 * Credentials are never shared with the application, and
                 * the web credential declaration is what a password
                 * manager reads to decide whether it may be. Left empty.
                 */
                'webcredentials' => [
                    'apps' => [],
                ],
            ])
            ->header('Content-Type', 'application/json');
    }

    /**
     * assetlinks.json, for Android App Links.
     */
    public function android(): JsonResponse
    {
        $package = trim((string) config('patrimoine.deep_links.android.package'));

        $fingerprints = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string) config('patrimoine.deep_links.android.fingerprints')
                    )
                )
            )
        );

        if ($package === '' || $fingerprints === []) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()
            ->json([
                [
                    'relation' => [
                        'delegate_permission/common.handle_all_urls',
                    ],
                    'target' => [
                        'namespace' => 'android_app',
                        'package_name' => $package,
                        'sha256_cert_fingerprints' => $fingerprints,
                    ],
                ],
            ])
            ->header('Content-Type', 'application/json');
    }

    /**
     * The claimed path components, in Apple's format.
     *
     * @return list<array<string, mixed>>
     */
    private static function components(): array
    {
        $claimed = [];

        foreach (self::CLAIMED_PATHS as $path) {
            $claimed[] = [
                '/' => $path,
                'comment' => 'Patrimoine application path',
            ];
        }

        /*
         * Everything else stays with the browser. The marketing site, the
         * legal pages and the sign-up journey are web journeys and are
         * not claimed.
         */
        return $claimed;
    }

    /**
     * The frozen deep-link surface.
     *
     * These path shapes are a published contract from the first release
     * that has an installed client. Adding to the list is safe; changing
     * or removing an entry breaks links that have already been sent.
     *
     * MobileContractTest asserts every one of them still resolves.
     *
     * @var list<string>
     */
    public const CLAIMED_PATHS = [
        '/dashboard*',
        '/properties*',
        '/parties*',
        '/leases*',
        '/owners*',
        '/tenants*',
        '/accounting*',
        '/reports*',
        '/audit*',
        '/archive*',
        '/settings*',
        '/help*',
        '/errors*',
    ];
}
