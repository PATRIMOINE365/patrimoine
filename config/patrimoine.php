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
            'name' => 'English',
        ],

        'fr' => [
            'locale' => 'fr',
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

];
