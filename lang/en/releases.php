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
