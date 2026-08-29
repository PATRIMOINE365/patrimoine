<?php

/*
|--------------------------------------------------------------------------
| Update log, as a customer reads it (English)
|--------------------------------------------------------------------------
|
| Patrimoine is one running service: nobody is on an old version and
| nobody can go back to one. A release-by-release history is therefore an
| archive of things that are simply true now, and thirty of them buries
| the two or three a customer actually wants to know.
|
| So the log is written in fives. Each entry covers the releases up to and
| including its own, in a couple of sentences. `through` is the last
| version it covers; the newest entry is the one still being filled, and
| ReleaseLogController shows it under the version that is actually running
| until it reaches its own number.
|
| The full release-by-release history stays in releases.php and is read in
| the administration console.
|
| Newest first.
|
*/

return [
    'title' => 'Update log',
    'current_version' => 'You are running version :version.',

    'entries' => [
        [
            'through' => '1.0.35',
            'date' => '2026-08-29',
            'summary' => 'Your photograph now sits at the top of the screen and beside your name on the Users page, so you can tell at a glance whose account you are looking at. The lease assistant no longer offers you fields for a property you are not creating, and the update log you are reading has been shortened to the things worth knowing.',
        ],
        [
            'through' => '1.0.30',
            'date' => '2026-08-29',
            'summary' => 'Every message Patrimoine shows when something will not go through now carries a code and a page explaining what to do about it, and telephone numbers are recorded with the country they belong to. Owner statements arrived, a guided assistant creates a whole letting in one sitting, and you can now switch off everything Patrimoine sends to a party — or to all of them.',
        ],
        [
            'through' => '1.0.25',
            'date' => '2026-08-28',
            'summary' => 'VAT moved onto the management fee, where accountants expect to find it, and cheques joined the payment methods. The interface stopped slipping back into English on organisations working in French.',
        ],
        [
            'through' => '1.0.20',
            'date' => '2026-08-26',
            'summary' => 'The Financial Journal, the emails Patrimoine sends and the pages you sign in on were all finished in both languages and given the Patrimoine 365 identity. Verification emails stopped leaving anybody stranded, with a way to ask for a new one from the page itself.',
        ],
        [
            'through' => '1.0.15',
            'date' => '2026-08-26',
            'summary' => 'Patrimoine became your organisation\'s own secure space: you sign up, verify your address, and confirm each sign-in with a code sent to you. Plans, licence confirmations and expiry reminders arrived alongside it.',
        ],
        [
            'through' => '1.0.10',
            'date' => '2026-08-26',
            'summary' => 'Owner accounts, payable expenses and owner payouts completed the money side, and Settings, Reports and the Dashboard were rebuilt around them. Documents became available wherever the record they belong to is.',
        ],
        [
            'through' => '1.0.5',
            'date' => '2026-08-20',
            'summary' => 'The first releases built Patrimoine: properties, units, parties, leases, invoices and receipts, on a real double-entry journal that records every financial event permanently. English and French, two currencies, fixed roles and an audit trail of who did what came with them.',
        ],
    ],
];
