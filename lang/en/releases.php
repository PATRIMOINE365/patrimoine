<?php

/*
|--------------------------------------------------------------------------
| Patrimoine release history (English)
|--------------------------------------------------------------------------
|
| Shown on the in-app Update Log. Ordered newest first. Each entry:
| 'version', 'date' (YYYY-MM-DD), 'title', 'changes' (list of strings).
|
| Keep wording user-facing: what changed for the person using Patrimoine,
| not implementation detail.
|
*/

return [
    'title' => 'Update log',
    'current_version' => 'You are running version :version.',

    'entries' => [
        [
            'version' => '1.0.12',
            'date' => '2026-08-26',
            'title' => 'A warmer front door: theme and language, your way',
            'changes' => [
                'The sign-in and sign-up pages now carry their own light/dark theme switch and English/French language switch, right at the door.',
                'Coming from patrimoine365.com? The language and theme you chose on the website now follow you automatically onto the sign-in and sign-up pages.',
                'The welcome panel on the sign-in pages was refreshed to match the new patrimoine365.com website.',
            ],
        ],
        [
            'version' => '1.0.11',
            'date' => '2026-08-26',
            'title' => 'Plan reminders, licence confirmations, and a stronger platform behind you',
            'changes' => [
                'Your administrators now receive an email 7 days and 1 day before your trial or licence ends, explaining exactly what changes on the Free plan and how to renew.',
                'Whenever a licence is assigned or extended for your organisation, your administrators receive an email confirming the new plan, its start date and its validity.',
                'Kality Ltd now operates Patrimoine 365 through a dedicated administration console: licences, account health and support requests are handled faster, and every action on your organisation is recorded in your own Activity Log.',
                'Account email addresses are further protected: an account can never be moved onto a reserved platform address.',
                'Security and reliability improvements across sign-in, image handling and the platform infrastructure.',
            ],
        ],
        [
            'version' => '1.0.10',
            'date' => '2026-08-26',
            'title' => 'Patrimoine 365: your organisation, your own secure space',
            'changes' => [
                'Patrimoine is now Patrimoine 365, a multi-organisation platform: every organisation works in its own completely isolated space — its data can never be seen by, mixed with, or referenced from any other organisation.',
                'New organisations sign up themselves: the Create your organisation page provisions everything in one step and starts a 30-day Professional trial, no payment card required.',
                'Email addresses are verified at signup: a confirmation link must be clicked before the first sign-in.',
                'Every sign-in is now protected by a second step: a 6-digit security code is emailed to you and entered in the same sign-in box, with a resend option.',
                'A new License & plan page (in Manage) shows your current plan, your usage against its limits, and a full comparison of the Free, Standard and Professional plans.',
                'Plans follow one simple rule: active leases are the only real limit — history, buildings and units never force an upgrade, and going over a limit only pauses creating new records, never your existing data.',
                'Automated rent reminders and increment notices are a Professional feature, with a monthly email allowance; invoices, receipts and vouchers always keep sending on every plan.',
                'New public Terms of Service and Privacy Policy pages (in English and French) are accepted at signup and linked from every email.',
                'Every email Patrimoine sends was redesigned on one professional layout, sent on behalf of your organisation with a proper legal footer.',
                'Every PDF document — invoices, receipts, bills and vouchers — now opens with your organisation letterhead and consistent, brand-aligned styling.',
                'The Financial Journal is active from day one for newly created organisations.',
                'Failed sign-in attempts, including wrong security codes, are recorded in the Activity Log with device and browser details.',
            ],
        ],
        [
            'version' => '1.0.9',
            'date' => '2026-08-26',
            'title' => 'Redesigned Settings, sharper Reports, and a fully wired Dashboard',
            'changes' => [
                'Settings is now a tabbed workspace — Organisation, Preferences, Data and About — with direct links to each tab.',
                'Data backup: every registry (Parties, Buildings, Units, Leases) exports as PDF, XLSX or CSV from one place, next to the one-click Full Backup.',
                'Restoring a backup is safer: Review Restore always checks the file first and shows exactly what would change before you confirm the restore.',
                'Reports remembers nothing stale: changing any filter or date after a run dims the results and disables exports until the report is run again.',
                'Report results show their reference date and row count; every report PDF now carries the organisation letterhead, and CSV/XLSX exports include the summary totals the PDFs already had.',
                'Report columns on screen and in exports now match (Arrears and Funds gained the Lease column), and money columns are right-aligned everywhere.',
                'Reports on phones: the report list becomes a compact selector, and date fields open the same Patrimoine calendar used elsewhere.',
                'Dashboard: Collected This Month and the collections trend now count rent money only — expense settlements and fund top-ups no longer inflate them.',
                'Dashboard rows link straight to the tenant, show invoice numbers and partial-payment progress, and new tiles surface management fees and upcoming rent increments.',
                'Activity Log records the device, browser, platform and IP address of every action, visible on each event and in all exports.',
                'Tenant money drawers are titled like the Owner ones: Record Tenant Deposit, Withdrawal, Expense and Balance Adjustment.',
                'A visual pass over every page and drawer: consistent buttons, labels, badges and dark-mode colors throughout the application.',
            ],
        ],
        [
            'version' => '1.0.8',
            'date' => '2026-08-26',
            'title' => 'Payable expenses, owner accounts, and faster documents',
            'changes' => [
                'Owner money now lives in two accounts: the Payout account (rent-derived, withdrawable) and the Deposit/Expense account, with transfers between them and printable transfer receipts.',
                'Expenses no longer settle themselves: a tenant expense creates an itemized EXP invoice and an owner expense bill stays unpaid until it is explicitly paid.',
                'New Expenses sections on the Tenant and Owner pages list these documents with their payment state.',
                'Pay button on invoices and bills: settle any amount from a chosen account (tenant fund accounts, or the owner\'s Deposit/Payout account) with a review step before recording.',
                'Payments made this way can be cancelled: the cancellation reverts the money, posts an immutable journal reversal and is fully activity-logged.',
                'Payment receipts: paid invoices and bills offer a downloadable receipt listing every payment.',
                'Owner expense drawer: bill one owner directly or split across all owners of a building by ownership percentage; each co-owner gets their own emailed bill.',
                'Owner deposits record their purpose, and deposit receipts open reliably.',
                'Lease creation gained a verification review, duration and notice presets, and searchable tenant/agent pickers; all three tenant fund accounts are provisioned with the lease.',
                'Irreversible deletions now require a typed acknowledgement and your password.',
                'Documents are dramatically faster: PDFs are up to 36x smaller and open natively in a browser tab.',
                'Notification bell now also shows unpaid expense invoices and unpaid owner expense bills.',
            ],
        ],
        [
            'version' => '1.0.7',
            'date' => '2026-08-22',
            'title' => 'Accounts, documents everywhere, and data restore',
            'changes' => [
                'Managers can now do everything except administration (Activity Log, Financial Journal, Users, Settings stay Administrator-only).',
                'People now have separate Given names and Surname fields across tenants, owners, agents and users.',
                'Units can be marked as commercial and filtered accordingly.',
                'Tenant Accounts view with fund-to-fund transfers (with mandatory reason and printable voucher), deposits, withdrawals and adjustments on any account.',
                'Owners: record multi-line expense bills directly to an owner — the itemized bill is emailed and downloadable; payout receipts added.',
                'Every list download is now available as PDF, XLSX and CSV, including the Activity Log.',
                'Registry backup: export properties, units, tenants and leases, and restore them later with a safe, idempotent import.',
                'New reports: Occupancy, Arrears aging, and Funds held.',
                'Redesigned dashboard with occupancy rate, collections trend, expiring leases and upcoming rent increments.',
                'Notification bell now shows overdue rent, upcoming dues, expiring leases and scheduled increases.',
                'New in-app Help page and this Update log; refreshed colors in light and dark mode.',
            ],
        ],
        [
            'version' => '1.0.6',
            'date' => '2026-08-21',
            'title' => 'Design standardization',
            'changes' => [
                'One consistent design system: every screen follows the same colors, drawers, buttons and forms in light and dark mode.',
                'Exactly two drawer sizes with consistent Cancel/Save footers.',
                'Rent increments can be scheduled and cancelled from the API.',
                'Reports work properly on phones and tablets.',
            ],
        ],
        [
            'version' => '1.0.5',
            'date' => '2026-08-20',
            'title' => 'Financial journal and lease lifecycle',
            'changes' => [
                'Immutable double-entry Financial Journal with chart of accounts and opening-balance cutover.',
                'Full lease lifecycle: extension, termination with settlement, and controlled deletion with impact preview.',
                'Tenant fund deposits, withdrawals and adjustments with printable receipts and vouchers.',
                'Payments reporting with PDF, XLSX and CSV exports.',
            ],
        ],
        [
            'version' => '1.0.4',
            'date' => '2026-08-19',
            'title' => 'Modern interface',
            'changes' => [
                'Refreshed application shell, drawers and dark mode.',
                'Release announcements in the notification bell.',
            ],
        ],
        [
            'version' => '1.0.3',
            'date' => '2026-08-17',
            'title' => 'Roles and audit trail',
            'changes' => [
                'Administrator, Manager and Viewer roles with invitations and password workflows.',
                'Tamper-proof Activity Log with filtering and exports.',
            ],
        ],
        [
            'version' => '1.0.2',
            'date' => '2026-08-15',
            'title' => 'Two languages, two currencies',
            'changes' => [
                'Full English and French interface, documents and e-mails.',
                'GHS and FCFA currency presentation.',
            ],
        ],
        [
            'version' => '1.0.1',
            'date' => '2026-08-13',
            'title' => 'Foundation',
            'changes' => [
                'Properties, units, tenants, owners and leases.',
                'Automated rent invoicing, FIFO payment allocation, tenant funds and owner accounting.',
                'PDF invoices, receipts and reports with e-mail delivery.',
            ],
        ],
    ],
];
