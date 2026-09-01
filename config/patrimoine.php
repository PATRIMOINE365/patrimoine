<?php

/*
|--------------------------------------------------------------------------
| Patrimoine Presentation Configuration
|--------------------------------------------------------------------------
|
| Language and currency belong to the Managing Organisation and are kept
| deliberately independent.
|
| Stable internal codes are used throughout the application. User-facing
| names belong in translation resources rather than in persisted settings.
|
| Adding another language or currency in a future release should primarily
| require registering it here and providing the corresponding presentation
| resources/formatting rules.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Current Patrimoine Release
    |--------------------------------------------------------------------------
    |
    | A user's release announcement becomes unread whenever this value
    | differs from the release they most recently acknowledged.
    |
    */

    'release' => '1.0.44',

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | The array key is the stable value persisted in ApplicationSetting.
    |
    | "locale" is the Laravel/Intl locale used for presentation.
    |
    */

    'languages' => [

        'en' => [
            'locale' => 'en',
            'date_locale' => 'en_GB',
            'browser_locale' => 'en-GB',
            'name' => 'English',
        ],

        'fr' => [
            'locale' => 'fr',
            'date_locale' => 'fr_FR',
            'browser_locale' => 'fr-FR',
            'name' => 'Français',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | Currency definitions concern presentation only.
    |
    | Patrimoine monetary values remain whole integers and are never converted
    | when the configured display currency changes.
    |
    */

    'currencies' => [

        'GHS' => [
            'code' => 'GHS',
            'name' => 'GHS',
            'group_separator' => ',',
            'symbol' => 'GH₵',
            'symbol_position' => 'before',
        ],

        'FCFA' => [
            'code' => 'FCFA',
            'name' => 'FCFA',
            'group_separator' => ' ',
            'symbol' => 'FCFA',
            'symbol_position' => 'after',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    |
    | Every API route is mounted twice: once at the historical unversioned
    | prefix and once under the current version. An installed mobile client
    | can never be upgraded on demand, so the version segment is what makes
    | a future breaking change possible at all — the old generation keeps
    | talking to its own version while a new one is introduced beside it.
    |
    | 'current' is what new clients should call. 'supported' is what the
    | server still answers; removing a version from this list is a
    | deliberate, announced act.
    |
    */

    'api' => [

        'current' => 'v1',

        'supported' => ['v1'],

        /*
         * The unversioned prefix stays mounted for the first-party web
         * application and for anything already written against it. It is
         * an alias of the current version and nothing else.
         */
        'legacy_prefix' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Access Tokens
    |--------------------------------------------------------------------------
    |
    | A browser token dies with the tab it was minted in; a token in a
    | phone's keychain does not. Both now carry an idle window that every
    | authenticated request slides forward, and an absolute ceiling that
    | nothing can slide past. An abandoned device therefore stops being a
    | credential on its own, while somebody using the product daily is
    | never signed out mid-task.
    |
    | Minutes throughout.
    |
    */

    'tokens' => [

        'web' => [
            'idle' => (int) env('TOKEN_IDLE_WEB', 60 * 12),
            'absolute' => (int) env('TOKEN_ABSOLUTE_WEB', 60 * 24 * 30),
        ],

        'mobile' => [
            'idle' => (int) env('TOKEN_IDLE_MOBILE', 60 * 24 * 60),
            'absolute' => (int) env('TOKEN_ABSOLUTE_MOBILE', 60 * 24 * 180),
        ],

        /*
         * Anything that is neither the first-party browser nor the mobile
         * application: integrations, scripts, the QA harness.
         */
        'api' => [
            'idle' => (int) env('TOKEN_IDLE_API', 60 * 24 * 30),
            'absolute' => (int) env('TOKEN_ABSOLUTE_API', 60 * 24 * 90),
        ],

        /*
         * Sliding the expiry writes to the database, and doing that on
         * every request would turn a read into a write. The window only
         * moves once this many minutes have passed since it last moved.
         */
        'slide_after' => (int) env('TOKEN_SLIDE_AFTER', 5),

    ],

    /*
    |--------------------------------------------------------------------------
    | Client Applications
    |--------------------------------------------------------------------------
    |
    | Read by GET /api/v1/config, which every installed application calls
    | before it shows anything. It is the one place that can tell a client
    | it is too old to run, or that the service is closed for maintenance,
    | without waiting for an app-store review.
    |
    | Raising a floor is an .env change plus `php artisan config:cache`.
    | It does not need a release.
    |
    */

    'clients' => [

        'minimum_version' => [
            'android' => env('CLIENT_MIN_ANDROID', '1.0.0'),
            'ios' => env('CLIENT_MIN_IOS', '1.0.0'),
        ],

        'latest_version' => [
            'android' => env('CLIENT_LATEST_ANDROID'),
            'ios' => env('CLIENT_LATEST_IOS'),
        ],

        'store_url' => [
            'android' => env('CLIENT_STORE_ANDROID'),
            'ios' => env('CLIENT_STORE_IOS'),
        ],

        'maintenance' => [
            'active' => (bool) env('CLIENT_MAINTENANCE', false),
            'message' => env('CLIENT_MAINTENANCE_MESSAGE'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Deep Links
    |--------------------------------------------------------------------------
    |
    | Patrimoine puts addressable links into e-mail that is read months
    | later. Associating those same https paths with the installed
    | application means every link already sent starts opening the app the
    | day it is installed; inventing a private scheme instead would leave
    | all of that mail pointing at the browser for ever.
    |
    | The two association files are served from the values below and are
    | withheld entirely until they are configured — a wrong file is worse
    | than none, because Apple caches it.
    |
    */

    'deep_links' => [

        'apple' => [
            'team_id' => env('DEEP_LINK_APPLE_TEAM_ID'),
            'bundle_id' => env('DEEP_LINK_APPLE_BUNDLE_ID', 'com.patrimoine365.app'),
        ],

        'android' => [
            'package' => env('DEEP_LINK_ANDROID_PACKAGE', 'com.patrimoine365.app'),

            /*
             * Comma-separated SHA-256 signing-certificate fingerprints.
             */
            'fingerprints' => env('DEEP_LINK_ANDROID_FINGERPRINTS'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Compatibility Defaults
    |--------------------------------------------------------------------------
    |
    | Existing V1.0.1 installations did not persist language or currency.
    | These defaults preserve their existing English/GHS behaviour after
    | upgrading to V1.0.2.
    |
    */

    'defaults' => [
        'language' => 'en',
        'currency' => 'GHS',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Negotiation
    |--------------------------------------------------------------------------
    |
    | Language belongs to the Managing Organisation, and both halves of the
    | product read it from there — which is exactly why a sentence returned
    | by the API can always be matched back to its error code.
    |
    | A client may still declare the language it is rendering in, through
    | X-Patrimoine-Language or an ordinary Accept-Language header. Before
    | an organisation is known that declaration decides the language of
    | every reply. Once one is known the organisation decides, unless this
    | is turned on.
    |
    | Turning it on lets a phone set to English read an English interface
    | inside a French organisation. It also means the two can disagree, so
    | it is off until that is what is wanted.
    |
    */

    'client_language_overrides_organisation' => (bool) env(
        'CLIENT_LANGUAGE_OVERRIDES_ORGANISATION',
        false
    ),

];
