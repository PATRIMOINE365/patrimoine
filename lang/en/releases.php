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
            'version' => '1.0.37',
            'date' => '2026-08-30',
            'title' => 'One look, everywhere',
            'changes' => [
                'Patrimoine has been redrawn. The application, the administration console, the website, the e-mails it sends and the documents it produces now share one design — colours, typeface, spacing, icons and the shape of every button and field. Until now the console had been rebuilt properly and the rest of the product had not, and it showed.',
                'The colours are the Patrimoine identity: a deep green for navigation and the main action, mint kept for the few things it should mark — where you are, what is selected, and money that has come in. Every colour pair on every screen was measured against the accessibility thresholds rather than judged by eye, in both light and dark.',
                'The typeface is Inter, and it is served from Patrimoine itself rather than from Google. The pages used to fetch it from Google Fonts, which told Google the address of everyone who opened one.',
                'The mark is the one from the brand guidelines — two pillars carrying three ledger bars — and it appears in the sidebar, on the sign-in page, in the browser tab, on your home screen and at the top of every e-mail.',
                'Every icon has been redrawn to one set. The same idea is now the same picture wherever you meet it, instead of a different drawing on each screen.',
                'Forms inside a side panel now lay themselves out to fit the panel rather than to fit your window. A two-column form in a narrow panel used to be squeezed into it on a wide screen.',
                'E-mails are on the warm paper colour the identity reserves for correspondence, and invoices, receipts and vouchers are set in the same typeface as the screen they came from.',
                'Nothing about how Patrimoine works has changed. This release moves nothing, renames nothing and removes nothing you were using.',
            ],
        ],
        [
            'version' => '1.0.36',
            'date' => '2026-08-30',
            'title' => 'Support, a guide you can find your way around, and numbers that carry their year',
            'changes' => [
                'Help and Update log have become one entry in your own menu: Support. It opens on a form where you can write to us, with the guide, the error codes and the update log beside it. Your name, your organisation and the address we answer come from your account, so there is nothing else to fill in, and anybody may write — including a Viewer, because being unable to do your work is exactly the thing worth telling us about.',
                'The guide is read one guide at a time now, chosen from a list or found by searching any word in any step. It used to render all seventy tasks and their pictures into a single page to read one of them.',
                'Pictures of drawers no longer tower over the words they illustrate. A drawer is photographed tall and a whole screen wide, and at the same width the tall one arrived three times the height of the other.',
                'The sidebar group called Manage is now Administration, and the Activity Log and Financial Journal have icons that say what they are.',
                'Your profile no longer explains what a photograph is for. Download my data has moved out from beside Save, where it read as a third thing the button might do, and is a link of its own under the password fields.',
                'The tabs on Settings and on Support are no longer drawn inside a second box.',
                'Accounting reads and offers dates the way every other screen does. It was using the browser\'s own control, so a French organisation on an English browser was shown month-first dates. The two fields are one Period now rather than a small From and a small To.',
                'In the lease assistant, ticking Already received no longer pushes Back and Next down the page.',
                'Every document number carries the year it belongs to: INV-2026-000003 rather than INV-000003. Numbers now come from a counter of their own rather than from reading the highest one already issued, which means deleting the newest document can no longer hand its number to the next one written.',
                'Most of the mail Patrimoine sends now leaves after your request rather than during it, so pressing a button no longer waits on our mail provider. Anything you are waiting on — a sign-in code, a verification link, a password reset, an invitation — is still sent while you watch, because a failure there has to be visible.',
            ],
        ],
        [
            'version' => '1.0.35',
            'date' => '2026-08-29',
            'title' => 'Saying the right thing, in the right language',
            'changes' => [
                'When Patrimoine refuses something, the code it shows you is now the right one. A value that was too long, or a number typed where words belong, used to send you to a page explaining a different rule entirely.',
                'Organisations working in French now read French in the activity log. Almost half of the recorded actions had never been translated, and because the log tidies up an unknown action into readable English, nothing looked broken.',
                'Messages your own browser raises — the ones about a field you have left empty — are now in your language rather than the browser\'s.',
                'Every message that names a field now names it in words: "the rent" rather than the column it is stored in. French organisations had been reading English field names inside French sentences.',
                'A record that cannot be found says so plainly, instead of quoting the internal name of the thing that was missing.',
                'The bell now tells you when money has been received that has not yet been filed to any of a tenant\'s accounts, with the amount, so it is not left sitting unnoticed. The guide was wrong about what happens to that money and now says what really does.',
                'The activity log and the financial journal are taken away as XLSX or CSV. The PDF has been withdrawn: both grow for as long as you use Patrimoine, and a document that has to hold all of it eventually cannot be made at all.',
                'Withdrawing more than a tenant fund holds, and the other refusals around withdrawals, now explain themselves in both languages and carry their own codes.',
            ],
        ],
        [
            'version' => '1.0.34',
            'date' => '2026-08-29',
            'title' => 'Your data, on request — and a way to forget somebody',
            'changes' => [
                'Anybody with an account can now take a copy of everything Patrimoine holds about them. Open your profile and press Download my data; no permission is needed and no administrator is involved.',
                'An administrator can produce everything held about one tenant, owner or agent, as a single file. That is what answers somebody who asks what you have on them — their data is yours to account for, not ours.',
                'An administrator can also download the whole organisation at once, financial history included, from Settings › Data.',
                'A person can now be erased. Their name, email address, telephone numbers, postal address, identity and registration numbers, bank details and notes are destroyed permanently — and the invoices, payments and journal entries stay, referring to them by a reference instead of a name. The law that requires those records kept is the same law that lets you refuse to destroy them.',
                'Erasing asks for the name typed back and for your own password, like closing an account, and it cannot be undone.',
                'The privacy policy has been rewritten to say plainly what is kept and for how long, who else touches your data and where it lives, and how to exercise every right — including that the activity log is kept indefinitely, because it is the record that shows what actually happened.',
            ],
        ],
        [
            'version' => '1.0.33',
            'date' => '2026-08-29',
            'title' => 'A how-to guide that shows you the screens',
            'changes' => [
                'The Guide under Help has been rewritten. It now covers every task in Patrimoine — fifty-four of them, in ten groups — as numbered steps, with a picture of the screen you will be looking at beside the step that uses it.',
                'The pictures are of the real application, in your own language, so what you read matches what is in front of you.',
                'Search the guide by what you are trying to do rather than by what the screen is called: the words of every step are searched, not just the titles.',
                'The same guide is now public at patrimoine365.com, so you can send somebody a link to a single task without asking them to sign in first.',
                'The website also gained a fuller list of questions and answers, and a Documentation menu that gathers the guides, the questions and the error codes in one place.',
            ],
        ],
        [
            'version' => '1.0.32',
            'date' => '2026-08-29',
            'title' => 'Pages you can jump to, and Settings that holds the whole account',
            'changes' => [
                'Every list longer than 25 rows is now read a page at a time, with numbered pages you can jump straight to rather than a Next button pressed eight times. The page you are reading is filled in, and the line beside it says how many rows are on screen out of how many exist.',
                'Rows per page offers 25, 50 or 100, and your choice is remembered for that list. Searching or filtering returns you to the first page, so a match further in cannot hide behind a page number.',
                'The same applies to lists that never had pages at all: the error code reference in the Guide, the public list at patrimoine365.com, and the platform console.',
                'Users and Licence are now tabs of Settings rather than separate entries in the sidebar. Old links and bookmarks still work and simply open the right tab.',
                'Settings also shows a summary of your account beside the organisation form: your plan, how many people can sign in, how many leases are running, how many parties are on the books, and when the account was opened.',
                'An administrator can now close the account from the foot of Settings. It permanently deletes the organisation and everything in it, and it asks for the organisation name typed back and your own password before it will run. It cannot be undone.',
                'The lease assistant no longer jumps back to the top of the page when you move between steps, and its Exit button is now Cancel, marked in red like every other action that discards work.',
            ],
        ],
        [
            'version' => '1.0.31',
            'date' => '2026-08-29',
            'title' => 'Your photograph, a shorter update log, and an assistant you can leave',
            'changes' => [
                'Your photograph now sits at the top right of every screen and beside your name on the Users page, so an administrator can tell accounts apart at a glance. Open your profile and choose a picture: drag it inside the round window and zoom with the slider or your scroll wheel until it is framed the way you want.',
                'Until you add one, your initials appear on a colour of your own, so a list of colleagues is still quick to read.',
                'JPG, PNG, WEBP and GIF all work, and a HEIC photograph from an iPhone works in Safari. Reframe reopens the picture exactly where you left it, so you can change the framing later without finding the file again.',
                'This update log is shorter. Patrimoine is one running service, so a release-by-release history was mostly a record of things that are simply true now; it is written in blocks of five releases instead, a couple of sentences each.',
                'Save as draft in the lease assistant no longer needs a property or a tenant first. It keeps the assistant exactly as you left it, however little you have filled in, and it appears under Unfinished assistants at the top of the Leases page, named after whoever started it and the day they did. Continue picks it up on the page you left; Discard throws it away.',
                'The assistant no longer changes height as you move through it, so the buttons stay where you left them, and its dates are typed and shown in your organisation format with the Patrimoine calendar, like every other date in the application.',
                'The assistant also stops offering you fields for a property, unit or party you are not creating. This turned out to affect more than the assistant: anything the application hid could reappear, including panels that should have stayed closed.',
                'The country list beside a telephone number now opens correctly inside a drawer, where it was previously hidden behind it.',
                'Manage in the sidebar is ordered Settings, Users, Licence, Activity Log, Financial Journal, and signing out is marked in red so it cannot be pressed by accident.',
            ],
        ],
        [
            'version' => '1.0.30',
            'date' => '2026-08-29',
            'title' => 'A code on every error, and telephone numbers that can be dialled',
            'changes' => [
                'Every message Patrimoine can show you when something will not go through now carries a code, like PM-4045, and a link explaining it. The page tells you what happened, whether it is something you can put right yourself, and how to reach us when it is not. There is a full list under Help, and the same list on patrimoine365.com for anyone who cannot sign in.',
                'The messages themselves were rewritten. Where one used to name a database field, blame the reader, or say nothing about what to do next, it now names the thing on your screen and says what to try.',
                'Pages you land on when something goes wrong — not found, session expired, too many attempts, under maintenance — now look like the rest of Patrimoine and carry their code too. The list of codes keeps working even while Patrimoine itself is unavailable, which is exactly when you are most likely to need it.',
                'Every telephone number is now recorded with the country it belongs to. Choose the country from the flag beside the field and type the rest of the number as you would say it; the leading zero is not needed. You can find a country by typing its name or its dialling code.',
                'Numbers already saved are untouched and still shown as you typed them. The next time you edit a record, choose the country and the number is stored in full.',
                'Storing numbers this way is what will let Patrimoine reach your tenants and owners by WhatsApp and text message.',
                'The Manage menu is gone. Activity Log, Financial Journal, Users, Settings, Licence and the administration console are now ordinary links in the sidebar, in a group of their own under Finance. Who can see them has not changed.',
            ],
        ],
        [
            'version' => '1.0.29',
            'date' => '2026-08-28',
            'title' => 'Control your emails, and a guided way to create a lease',
            'changes' => [
                'Settings now carries a switch for everything Patrimoine sends to your tenants, owners and agents. Turn it off and nothing leaves: no invoices, receipts, reminders, notices or vouchers. Anyone who tries to send one is told why, rather than being left wondering whether it arrived.',
                'Each party also carries its own choice: follow the organisation, always receive emails, or never receive them. So you can go quiet everywhere and still let two owners through, or keep sending while excluding a single party.',
                'A party Patrimoine will not email is marked in the Parties list, so you see it before you try. Nothing else changes: the invoice is still issued, the receipt still recorded, every document still downloadable.',
                'Emails you receive as a user of Patrimoine — sign-in codes, invitations, password resets — are never affected by any of this.',
                'A new guided assistant creates a lease from beginning to end without leaving the page: the property and unit, the owners, the tenant, the agent and their commission, the dates, the notice and rent increases, the rent and advance, then the fees. It starts from the Assistant button on the Leases page.',
                'The assistant explains the words it uses, skips the owners page when the property already has its owners, offers only vacant units, and fills the end date from a duration you choose — or lets you leave it open, in which case the lease runs until it is terminated.',
                'Nothing is saved until the last page, where you check everything and choose to save a draft or create and activate. Leaving the assistant halfway costs nothing and leaves nothing behind.',
                'A pass over the whole application fixed places that were still reading in the wrong language: the Activity Log, the Financial Journal filter, parts of the sidebar, and one field that showed its own internal name instead of a label. Dates and amounts in documents and exports now follow your organisation language everywhere.',
                'The administration console no longer scrolls sideways on a phone.',
            ],
        ],
        [
            'version' => '1.0.28',
            'date' => '2026-08-28',
            'title' => 'User management, and a tidier Parties list',
            'changes' => [
                'A user created as inactive is now saved without being invited, instead of failing with an error. They are invited automatically the moment somebody activates the account. Someone who has already set their password is never re-invited when their account is switched back on.',
                'Support can create users, activate or deactivate them, and change their role for a customer organisation from the administration console, using the same safeguards as your own Users page.',
                'Your own organisation no longer appears in the Parties list. It was never a counterparty and only caused confusion; it is still edited in Settings as before.',
            ],
        ],
        [
            'version' => '1.0.27',
            'date' => '2026-08-28',
            'title' => 'Fix: adding a user failed',
            'changes' => [
                'Adding a user from the Users page failed with a server error. Creating and renaming users works again.',
            ],
        ],
        [
            'version' => '1.0.26',
            'date' => '2026-08-28',
            'title' => 'Owner statements',
            'changes' => [
                'A Statement button on an owner account produces the document an owner is handed when they collect: rent received and the periods it covers, expenses, management fees, VAT on those fees, and the balance left to pay out. The period is pre-filled from the day after their last payout and can be changed.',
                'Rent is listed by the period it settles, not the month the money arrived, so rent paid late still appears against the months it pays for. A period nobody has paid is simply absent, because that cash is not there to hand over.',
                'The owner account breakdown and the owner report now show VAT on management fees on its own line.',
                'Tidied the Accounting page so its table matches the rest of the application.',
            ],
        ],
        [
            'version' => '1.0.25',
            'date' => '2026-08-28',
            'title' => 'Support tools in the admin console',
            'changes' => [
                'The console has a new Emails page showing everything Patrimoine has sent and everything received at a @patrimoine365.com address, with replying and composing in the same place.',
                'Opening an organisation now shows their owners, tenants, agents, buildings, units, leases, invoices and payments, so support can see exactly what a customer sees.',
                'Support can correct a customer lease on their behalf. Terms that invoices or journal entries were derived from require a written reason, and every correction is recorded in the customer activity log against the staff member who made it.',
            ],
        ],
        [
            'version' => '1.0.24',
            'date' => '2026-08-28',
            'title' => 'A red tab for the admin console',
            'changes' => [
                'The platform administration console now shows the Patrimoine mark in dark red instead of green, so you can pick its browser tab out from your customer tabs at a glance.',
            ],
        ],
        [
            'version' => '1.0.23',
            'date' => '2026-08-27',
            'title' => 'VAT moves to your management fee',
            'changes' => [
                'VAT is now charged on your management fee instead of on rent, and billed to the owner. On 100,000 rent with a 10% fee and a 20% VAT rate, the owner is charged 10,000 in fees plus 2,000 in VAT and receives 88,000.',
                'Rent invoices no longer carry VAT. Invoices issued before this update keep the VAT they were issued with.',
                'Each lease sets its own management fee VAT rate, and may use 0% where VAT does not apply. Leases created before this update keep the rate already stored against them, which now applies to the fee rather than to rent.',
                'A new Accounting page under Finance shows what you have earned in management fees and the VAT you have charged on them, over any period you choose.',
            ],
        ],
        [
            'version' => '1.0.22',
            'date' => '2026-08-27',
            'title' => 'Closing the owner Transfer panel',
            'changes' => [
                'On the Owner account, the Transfer panel can now be closed with its X, by clicking outside it, or with the Escape key. Escape closes one panel at a time, so the account behind it stays open.',
            ],
        ],
        [
            'version' => '1.0.21',
            'date' => '2026-08-27',
            'title' => 'Cheque payments, and an interface that stays in your language',
            'changes' => [
                'Cheque is now a payment method everywhere you record money: tenant payments and withdrawals, owner deposits and payouts, lease advances, expenses and reports.',
                'Moving between menus no longer flashes the interface in English before returning to your language — pages now arrive in your language straight away.',
                'On the Owner account, the Transfer panel now opens on top of the account it belongs to instead of behind it.',
                'Deleting a lease that had received a rent payment was refused as unsafe. Those payments are now recognised properly, so a lease you are entitled to delete can be deleted.',
            ],
        ],
        [
            'version' => '1.0.20',
            'date' => '2026-08-26',
            'title' => 'A Financial Journal that speaks your language',
            'changes' => [
                'Journal entry descriptions are now written in your organisation\'s language. French organisations previously saw these in English; new entries are recorded correctly from now on, while existing entries keep the wording they were posted with.',
                'On the Dashboard, the financial figures now line up neatly even when a tile\'s label runs onto a second line.',
            ],
        ],
        [
            'version' => '1.0.19',
            'date' => '2026-08-26',
            'title' => 'A livelier welcome panel',
            'changes' => [
                'The preview on the sign-in and sign-up pages now shows a real workspace in your language, and in the opposite theme to the page — so you can see how Patrimoine 365 looks in the mode you are not using.',
            ],
        ],
        [
            'version' => '1.0.18',
            'date' => '2026-08-26',
            'title' => 'Email polish',
            'changes' => [
                'Tidied the plain-text version of every email so the Patrimoine 365 name appears once, not twice.',
            ],
        ],
        [
            'version' => '1.0.17',
            'date' => '2026-08-26',
            'title' => 'Better email delivery and a simpler License page',
            'changes' => [
                'Every email now sends in both rich and plain-text form, which helps it reach the inbox instead of the spam or quarantine folder.',
                'The verification email explains what happens next and shows its link in full, so it reads as the routine message it is.',
                'The License page now focuses on what matters day to day: your plan and your usage against its limits. Full plan comparison lives on patrimoine365.com/pricing.',
                'On the Help page, the Guide and Update log tabs now line up neatly with the search field.',
            ],
        ],
        [
            'version' => '1.0.16',
            'date' => '2026-08-26',
            'title' => 'Polish: resend buttons and branded emails',
            'changes' => [
                'The resend-verification actions on the sign-in and sign-up pages are now proper buttons — impossible to miss — and a sign-in resend issue was fixed.',
                'Every email now opens with the Patrimoine 365 logo.',
            ],
        ],
        [
            'version' => '1.0.15',
            'date' => '2026-08-26',
            'title' => 'Verification emails that never leave you stranded',
            'changes' => [
                'The sign-up confirmation screen now offers to resend the verification email — with a reminder to check the spam folder.',
                'Trying to sign in before verifying? The error now comes with a one-click "Resend the verification email" action.',
                'Sign-in and sign-up error messages now follow the language you are reading the page in — French included.',
            ],
        ],
        [
            'version' => '1.0.14',
            'date' => '2026-08-26',
            'title' => 'A finished front door',
            'changes' => [
                'The sign-in and sign-up pages now show a live preview of the Patrimoine 365 dashboard, in your language.',
                'Clicking the Patrimoine 365 logo on the sign-in and sign-up pages takes you to patrimoine365.com.',
            ],
        ],
        [
            'version' => '1.0.13',
            'date' => '2026-08-26',
            'title' => 'One identity everywhere: the new Patrimoine 365 look',
            'changes' => [
                'Patrimoine 365 has a new visual identity — the green house logomark and the Patrimoine 365 wordmark now appear consistently across the application, sign-in pages, emails and PDF documents.',
                'New app icon: saving Patrimoine 365 to your phone\'s home screen (Android or iPhone) or pinning a browser tab now shows the new logo.',
                'A new motto to match: “Property management, minus the drama.”',
            ],
        ],
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
                'Patrimoine 365 is now operated through a dedicated administration console: licences, account health and support requests are handled faster, and every action on your organisation is recorded in your own Activity Log.',
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
