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
            'through' => '1.0.55',
            'date' => '2026-09-05',
            'summary' => 'The block the next few releases are being written into. It stays out of the update log until a release moves past 1.0.50, because until then it has nothing to describe.',
        ],
        [
            'through' => '1.0.50',
            'date' => '2026-09-05',
            'summary' => 'There is one way to create a letting now: Add lease opens the guided assistant, and the older form beside it is retired; the assistant finds a property, a unit, a tenant, an agent or an owner by typing rather than by scrolling a list that only ever held the first hundred, a person added along the way can be told straight away whether Patrimoine may write to them, and the VAT on your fee starts at the rate your organisation has set. An owner payout receipt is also a historical record now: it says what the account held when that money was released, a payment recorded afterwards with an earlier date belongs to the next payout rather than rewriting one already made, and an owner\'s two accounts are counted separately all the way down — deposit-side debt no longer blocks a valid withdrawal, a reserve transfer sits on its own line without inflating a statement, a payout can say its money came from a returned deposit, and a shared amount can never round anybody below zero. Your sign-in email is changed through three verified steps of its own — your password, a code to your current address, a code to the new one — and nobody else can rewrite it for you; and a full audit of 1.0.49 was answered in 1.0.50: nothing of your organisation shows on the sign-in screen or in a password-reset email before you sign in, a termination completes after a deposit settled with deductions, an unpaid draft can be deleted, each sign-in and code limit counts on its own, receipts are numbered by your organisation like every other document, the assistant no longer starts with an advance marked received, an owner must be registered as one, and closing your organisation sends you written confirmation.',
        ],
        [
            'through' => '1.0.45',
            'date' => '2026-08-31',
            'summary' => 'An owner\'s payout receipt itemises every movement behind the figure — the rent by unit and period, the fees and the tax on them, and each expense — and every lease has a View button that opens the whole letting on one screen. The security deposit entered on a lease is now genuinely received into the tenant\'s deposit account, money that arrives before the tenancy is accepted as arriving then, a letting that begins on the 31st keeps the 31st for the whole of its life, and the guided assistant asks exactly what the lease form asks, one page per section. A record Patrimoine will not delete can be archived instead — it says what archiving does and asks why, the record leaves the lists and the dropdowns while every document and ledger line still names it, and it comes back from the Archive page whenever you want it; the lists themselves are about half as tall, and a party\'s type and roles carry a colour of their own; Settings now lists every device your account is signed in on and lets you sign any of them out on the spot, and a session that goes unused ends on its own instead of lasting for ever; the lease assistant asks for the agent beside the commission that is theirs and always opens on its first page, Paid every is written in months rather than calendar names, the Extend and Terminate drawers have their date pickers back, and a letting\'s financial history is now a paged table in the wide drawer.',
        ],
        [
            'through' => '1.0.40',
            'date' => '2026-08-29',
            'summary' => 'Help became Support: one entry in your own menu opens a page where you can write to us, with the guide, the error codes and the update log beside it. The guide is read one guide at a time now, found by searching rather than by scrolling past everything else, and its pictures no longer tower over the words they illustrate. Accounting reads dates the way the rest of Patrimoine does, every document number carries the year it belongs to, and most of what Patrimoine sends by e-mail now leaves after your request instead of during it. Patrimoine has also been redrawn: the application, the console, the website, the e-mails and the documents now share one design, one typeface served from Patrimoine itself rather than from Google, one mark and one set of icons, and the Activity Log and the Financial Journal have become two tabs of one entry called Audit, the lease assistant shows every amount grouped and in your currency and says which field it is refusing, your browser no longer fills in forms anywhere but sign-in and sign-up, and an owner\'s payout receipt now shows what came in and what was deducted to reach the amount they were paid',
        ],
        [
            'through' => '1.0.35',
            'date' => '2026-08-29',
            'summary' => 'Your photograph now sits at the top of the screen, the lease assistant can be left half-finished and picked up later, long lists are read a page at a time, and Users and Licence became tabs of Settings, which also summarises your account and lets an administrator close it for good. Anybody can now download everything held about them, an administrator can produce or erase one person’s data, the privacy policy says plainly what is kept and for how long, and the Guide covers every task step by step with pictures of the screens — the same guide is public on patrimoine365.com. Organisations working in French now read French throughout, including the activity log and the messages your own browser raises, every refusal points at the right explanation, the bell tells you when money has arrived that has not yet been filed to a tenant’s account, and the activity log and financial journal are taken away as XLSX or CSV rather than PDF',
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
