<?php

/*
|--------------------------------------------------------------------------
| Patrimoine 365 Legal & Company Identity
|--------------------------------------------------------------------------
|
| Patrimoine 365 (the website, APIs and applications) is owned and
| operated by Kality Ltd. This file is the single source of truth for
| that identity and for the product mailboxes referenced in emails,
| documents and the legal pages.
|
| Document versions identify the revision of the Terms of Service and
| Privacy Policy a user accepted at signup. Bump a version whenever the
| corresponding document materially changes.
|
*/

return [

    'company' => [
        'name' => 'Kality Ltd',
        'address' => 'GD-323-3454, Ghana',
        'email' => 'hello@kalitygroup.com',
    ],

    'product' => [
        'name' => 'Patrimoine 365',
        'domain' => 'patrimoine365.com',
    ],

    /*
    |----------------------------------------------------------------------
    | Product mailboxes (@patrimoine365.com)
    |----------------------------------------------------------------------
    |
    | These addresses appear on the legal pages, in email footers and on
    | generated documents. The mailboxes themselves are provisioned by
    | the platform operator.
    |
    */

    'mailboxes' => [
        'hello' => 'hello@patrimoine365.com',
        'support' => 'support@patrimoine365.com',
        'privacy' => 'privacy@patrimoine365.com',
        'legal' => 'legal@patrimoine365.com',
        'billing' => 'billing@patrimoine365.com',
        'security' => 'security@patrimoine365.com',
        'no_reply' => 'no-reply@patrimoine365.com',
    ],

    /*
    |----------------------------------------------------------------------
    | Reaching a person
    |----------------------------------------------------------------------
    |
    | Shown on the Error codes pages for failures a customer cannot put
    | right themselves, and on the branded system pages. One number,
    | reachable by telephone and by WhatsApp.
    |
    */

    'support' => [
        'phone' => '+233544347118',
        'whatsapp' => '+233544347118',
        'phone_display' => '+233 54 434 7118',
    ],

    'terms_version' => '2026-08-26',

    'privacy_version' => '2026-08-29',
];
