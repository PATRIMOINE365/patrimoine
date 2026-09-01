<?php

/*
|--------------------------------------------------------------------------
| The how-to guide (English)
|--------------------------------------------------------------------------
|
| One manual, read in the Guide tab inside Patrimoine and on the public
| pages at patrimoine365.com/documentation/guides.
|
| This file carries the shape as well as the words: categories, the tasks
| inside them, and the numbered steps inside those. English is the
| authority for the shape — GuideTest fails if French drifts from it.
|
| A step may name a `shot`. Screenshots are captured from the running
| application by scripts/capture-guide.mjs, never drawn and never taken by
| hand, so that a redesign is followed by a re-run rather than a hunt. A
| shot named here must exist in scripts/guide-shots.json.
|
| Write steps as instructions to a person: what to press, what to type,
| what happens next. Explain the rule behind a step only where somebody
| would otherwise get it wrong.
|
*/

return [

    'title' => 'How-to guide',
    'description' => 'Every task in Patrimoine, step by step, with pictures of the screens you will be looking at.',

    'categories' => [

        /*
        |----------------------------------------------------------------------
        | Getting started
        |----------------------------------------------------------------------
        */

        'getting_started' => [
            'title' => 'Getting started',
            'summary' => 'Signing in, finding your way around, and setting Patrimoine up the way you read.',

            'tasks' => [

                'signing_in' => [
                    'title' => 'Sign in',
                    'intro' => 'Every sign-in takes two things: your password, and a six-digit code sent to your email address. The code is asked for every time, not only on a new device.',
                    'steps' => [
                        ['text' => 'Go to app.patrimoine365.com. Enter your email address and password, then press Sign in.', 'shot' => 'login'],
                        ['text' => 'Patrimoine emails you a six-digit code. Open your inbox and copy it.'],
                        ['text' => 'Type the code and press Verify. The code is good for a few minutes and for one sign-in only.', 'shot' => 'mfa'],
                        ['text' => 'If the code has not arrived after a minute, press Send a new code. The previous one stops working the moment a new one is sent.', 'note' => 'Check the spam folder before asking for a third code — repeated requests are throttled.'],
                    ],
                    'after' => 'You land on the Dashboard. Your session lasts until you sign out or close the browser.',
                ],

                'finding_your_way' => [
                    'title' => 'Find your way around',
                    'intro' => 'Everything is reachable from the sidebar on the left, which is grouped by what the work is rather than by what part of the software it belongs to.',
                    'steps' => [
                        ['text' => 'Workspace holds the day-to-day: Dashboard, Properties, Parties and Leases.', 'shot' => 'sidebar'],
                        ['text' => 'Finance holds the money: Tenants, Owners, Accounting and Reports.'],
                        ['text' => 'Administration holds the rest: Settings and Audit. It is visible to administrators only.'],
                        ['text' => 'The top bar shows the organisation you are signed in to, today\'s date, the notification bell, and your own photograph. Press the photograph for your profile and to sign out.', 'shot' => 'topbar'],
                    ],
                ],

                'language_currency' => [
                    'title' => 'Set the language and currency',
                    'intro' => 'Both are set for the whole organisation, not per person, so that a document produced by one colleague reads the same as one produced by another.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings from the Administration group in the sidebar.'],
                        ['text' => 'Choose the Preferences tab.', 'shot' => 'settings-preferences'],
                        ['text' => 'Pick the language. English and French are both complete: every screen, every document and every email follows it.'],
                        ['text' => 'Pick the currency and the default VAT rate. The currency decides how every amount is written, including the thousands separator.'],
                        ['text' => 'Under Communications, three switches: whether Patrimoine emails your tenants, owners and agents at all; whether the data-protection tools appear on the parties list; and whether that list is ordered by surname. All three belong to the organisation, so a list read at two desks reads the same way.'],
                        ['text' => 'Press Save. The screen changes language immediately; documents follow from the next one produced.'],
                    ],
                ],

                'appearance' => [
                    'title' => 'Switch between light and dark',
                    'intro' => 'Appearance is yours alone, kept in your browser. It does not affect colleagues and it does not affect documents.',
                    'steps' => [
                        ['text' => 'Press your photograph at the top right.'],
                        ['text' => 'Choose the light or dark setting in the profile panel.', 'shot' => 'profile-drawer'],
                        ['text' => 'The change is immediate and is remembered on this browser.'],
                    ],
                ],

                'profile_photo' => [
                    'title' => 'Add your photograph',
                    'intro' => 'Your photograph sits at the top right of every screen and beside your name on the Users page, so an administrator can tell accounts apart at a glance. Until you add one, your initials appear on a colour of their own.',
                    'steps' => [
                        ['text' => 'Press your photograph or initials at the top right, then Edit profile.'],
                        ['text' => 'Press Choose a picture and pick a file. JPG, PNG, WEBP and GIF all work, and a HEIC photograph from an iPhone works in Safari.', 'shot' => 'profile-photo'],
                        ['text' => 'Drag the picture inside the round window and zoom with the slider or your scroll wheel until it is framed the way you want.'],
                        ['text' => 'Press Save. To change the framing later, press Reframe — it reopens the picture exactly where you left it, so you do not need the original file again.'],
                    ],
                ],

                'paging_lists' => [
                    'title' => 'Read a long list',
                    'intro' => 'Any list longer than 25 rows is shown a page at a time.',
                    'steps' => [
                        ['text' => 'The line at the foot of the list says how many rows are on screen out of how many exist.', 'shot' => 'pagination'],
                        ['text' => 'Press a page number to jump straight to it. The page you are reading is the filled one.'],
                        ['text' => 'Change Rows per page to 25, 50 or 100. Your choice is remembered for that list on this browser.'],
                        ['text' => 'Searching or filtering returns you to the first page, so a match further in cannot hide behind a page number.'],
                    ],
                ],

                'signed_in_devices' => [
                    'title' => 'See where you are signed in',
                    'intro' => 'Settings keeps a list of every device your account is currently signed in on. If one of them is a phone you no longer have, you can take it out of the list yourself and it stops working immediately.',
                    'steps' => [
                        ['text' => 'Open Settings and choose the Devices tab.'],
                        ['text' => 'Each row names the device, when it was last used and where from. The one you are reading this on is marked This device.', 'shot' => 'devices'],
                        ['text' => 'Press Sign out beside a device you no longer recognise. It cannot reach your organisation again without signing in from the start, code included.'],
                        ['text' => 'Sign out every other device does the same to all of them at once and leaves you signed in here.'],
                    ],
                    'after' => 'A device you stop using signs itself out on its own — a browser after half a day, the mobile application after two months — and every device has to sign in again eventually however often it is used.',
                ],

                'ask_for_help' => [
                    'title' => 'Ask us for help',
                    'intro' => 'Support, the guide you are reading, the error codes and the update log are all on one page, reached from your own photograph at the top right.',
                    'steps' => [
                        ['text' => 'Press your photograph at the top right and choose Support.'],
                        ['text' => 'The page opens on Contact support. Write what you were trying to do and what happened instead, then press Send to support.'],
                        ['text' => 'If a message carried a code beginning PM-, put it in. It tells us exactly which refusal you met without a description of the screen.'],
                        ['text' => 'Your name, your organisation and the address we answer are taken from your account, so there is nothing else to fill in. We reply by email.'],
                        ['text' => 'The other tabs on the same page are the guide, the full list of error codes, and what changed in each release.'],
                    ],
                    'after' => 'Anybody may write to us — a Viewer who cannot do their work needs to say so as much as an administrator does.',
                ],

                'roles' => [
                    'title' => 'Understand who can do what',
                    'intro' => 'Patrimoine has three roles. They are fixed: what each one may do is the same in every organisation.',
                    'steps' => [
                        ['text' => 'An Administrator can do everything, including Settings, Users, the Licence and both tabs of Audit.'],
                        ['text' => 'A Property Manager does all the day-to-day work — properties, parties, leases, payments, owners and reports — and may delete records, but cannot reach the Administration group.'],
                        ['text' => 'A Viewer can read and can export reports, and can change nothing.'],
                        ['text' => 'Controls a role may not use are not merely disabled, they are not shown. The server enforces the same rule, so a hidden control cannot be reached another way.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Properties
        |----------------------------------------------------------------------
        */

        'properties' => [
            'title' => 'Properties and units',
            'summary' => 'Buildings, who owns them, and the units inside them.',

            'tasks' => [

                'add_property' => [
                    'title' => 'Add a property',
                    'intro' => 'A property is a building. Even a single house is a building with one unit, because that is what lets it be let, invoiced and reported on like everything else.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Properties from the sidebar and press Add Property.', 'shot' => 'properties-list'],
                        ['text' => 'Give the building a name and an address. The name is what appears on documents, so use the name people call it by.', 'shot' => 'property-drawer'],
                        ['text' => 'Press Save. The building appears in the portfolio with no units and no owners yet.'],
                    ],
                    'after' => 'Record who owns it next, then add its units.',
                ],

                'record_ownership' => [
                    'title' => 'Record who owns a property',
                    'intro' => 'Ownership drives the whole owner side of the accounts: who is entitled to the rent, who carries the expenses, and what each statement says. Shares must total 100%.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'On the Properties page, press Edit on the building, then open its ownership section.'],
                        ['text' => 'Press Add owner and choose a party. Anybody you name here must already exist as a party with the owner role.', 'shot' => 'property-owners'],
                        ['text' => 'Enter that owner\'s percentage share.'],
                        ['text' => 'Add the remaining owners until the shares total exactly 100%. Patrimoine refuses to save anything else.'],
                        ['text' => 'Press Save.'],
                    ],
                ],

                'add_units' => [
                    'title' => 'Add units to a property',
                    'intro' => 'A unit is what is actually let: a flat, a shop, an office, or the whole house. A lease is always against a unit, never against the building.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'On the Properties page, press Add Unit on the building.'],
                        ['text' => 'Give the unit a name or number, and a description if it helps somebody recognise it.', 'shot' => 'unit-drawer'],
                        ['text' => 'Press Save, and repeat for each unit.'],
                        ['text' => 'Press View Units on the building to see them all and their current letting state.'],
                    ],
                ],

                'edit_delete_property' => [
                    'title' => 'Edit or delete a property',
                    'intro' => 'Editing is unrestricted. Deleting is not: anything with financial history behind it is kept, because deleting it would leave the accounts unable to explain themselves.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Press Edit on the building to change its name, address or ownership.'],
                        ['text' => 'Press Delete to remove it. Patrimoine tells you exactly what would go with it before anything happens. The button only says Delete while nothing refers to the property — once something does, it says Archive instead.'],
                        ['text' => 'A building with units that have ever been let cannot be deleted. Nor can a unit with a lease behind it.'],
                        ['text' => 'Confirm to delete. There is no undo.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Parties
        |----------------------------------------------------------------------
        */

        'parties' => [
            'title' => 'Parties',
            'summary' => 'Everybody Patrimoine deals with: tenants, owners, agents and your own organisation.',

            'tasks' => [

                'add_party' => [
                    'title' => 'Add a party',
                    'intro' => 'A party is a person or a company. One party record carries every role that person plays, so somebody who rents one flat and owns another is one party, not two.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Parties from the sidebar and press Add Party.', 'shot' => 'parties-list'],
                        ['text' => 'Choose Person or Company. A company also has a contact person, with their own telephone number.', 'shot' => 'party-drawer'],
                        ['text' => 'Enter the name, email address and telephone number. Choose the country beside the number — a number cannot be dialled without knowing which country it belongs to.'],
                        ['text' => 'Tick every role this party plays: owner, tenant, agent, or your managing organisation.'],
                        ['text' => 'Press Save.'],
                    ],
                ],

                'party_roles' => [
                    'title' => 'Give a party more than one role',
                    'intro' => 'Roles are not exclusive. The same record can be an owner of one building and the tenant of another, and Patrimoine keeps the two sides of their account entirely separate.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Press Edit on the party.'],
                        ['text' => 'Tick the additional role and press Save.'],
                        ['text' => 'The party now appears in the list for each of its roles — under Tenants, under Owners, or both.'],
                        ['text' => 'Your own company must keep the managing organisation role. Patrimoine refuses to remove it until another party has been named in its place.'],
                    ],
                ],

                'party_emails' => [
                    'title' => 'Control what a party is sent',
                    'intro' => 'Patrimoine sends invoices, receipts, reminders, notices and vouchers to parties. You can switch that off for one party or for all of them.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'For one party: press Edit and turn off emails on their record. They are then sent nothing, and anybody who tries is told why.', 'shot' => 'party-email-control'],
                        ['text' => 'For everybody: open Settings, and under Communications turn off Send emails to parties.'],
                        ['text' => 'With it off, an individual party can still be allowed back on from their own record.'],
                        ['text' => 'Emails to Patrimoine users — sign-in codes, invitations, password resets — are never affected by either switch.'],
                    ],
                ],

                'party_data' => [
                    'title' => 'Produce everything held about a party',
                    'intro' => 'A tenant or an owner may ask what you hold about them, and you are the one who has to answer: their data is yours to control, not ours. This produces the whole of it as one file.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Parties and find the person.'],
                        ['text' => 'Press Data on their row. The file downloads immediately.', 'shot' => 'party-data'],
                        ['text' => 'It holds their record, their roles, their leases, their invoices and payments, what is held for them, and every entry in the activity log about them.'],
                        ['text' => 'The file is JSON, which is the format a request for portable data expects. Send it as it is, or open it in a spreadsheet if the person would rather read a table.'],
                    ],
                    'after' => 'Producing it is itself recorded in the activity log, so you can show when a request was answered.',
                ],

                'party_erasure' => [
                    'title' => 'Erase a person who asks to be forgotten',
                    'intro' => 'Erasure destroys the person, not the accounts. Everything identifying goes for good; the invoices and journal entries stay and refer to them by a reference instead of a name.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Erase is hidden unless an administrator has switched the data-protection tools on in Settings, under Preferences. Do that first.'],
                        ['text' => 'Open Parties, find the person, and press Erase.', 'shot' => 'party-erase'],
                        ['text' => 'Read what goes: name, email address, telephone numbers, postal address, identity and registration numbers, bank details and notes. All of it, permanently.'],
                        ['text' => 'Read what stays: the invoices, payments and journal entries. The law that requires those kept is the same law that lets you refuse to destroy them, so they remain \u2014 naming the person only as "Erased party #248".'],
                        ['text' => 'Type the name exactly as the record shows it, and enter your own password.'],
                        ['text' => 'Press Erase this person. It cannot be undone, and Patrimoine will never email them again.'],
                    ],
                    'after' => 'Consider producing their data first, if they asked for a copy as well as an erasure. Afterwards there is nothing left to produce.',
                ],

                'delete_party' => [
                    'title' => 'Delete a party',
                    'intro' => 'A party with no history can be deleted outright. One with history cannot, for the same reason a property cannot.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Press Delete on the party.'],
                        ['text' => 'Patrimoine lists what depends on them — leases, payments, owner accounts — before anything happens.'],
                        ['text' => 'If nothing does, confirm and they are gone. If something does, the deletion is refused and the reason is named.'],
                    ],
                ],

            ],
        ],


        /*
        |----------------------------------------------------------------------
        | Leases
        |----------------------------------------------------------------------
        */

        'leases' => [
            'title' => 'Leases',
            'summary' => 'Creating a letting, changing it while it runs, and ending it properly.',

            'tasks' => [

                'lease_composition' => [
                    'title' => 'See a whole letting at once',
                    'intro' => 'A letting is entered across several pages and then lives in several places. View reads it all back on one screen.',
                    'who' => 'Everyone',
                    'steps' => [
                        ['text' => 'Open Leases and press View on the lease you want.', 'shot' => 'lease-view'],
                        ['text' => 'The panel holds the property and unit, who owns it and in what shares, the tenant, the agent and their commission, the dates, the rent terms, what is held, the increases and the fee.'],
                        ['text' => 'It is read-only. Each of these values is changed where it belongs — Edit for the terms, Rent increments for the increases — so there is only ever one place a figure can be altered.'],
                    ],
                ],

                'lease_wizard' => [
                    'title' => 'Create a lease with the guided assistant',
                    'intro' => 'The assistant builds a whole letting in one sitting — the property, the unit, the owner, the tenant and the lease itself — asking one thing at a time. It asks for exactly what the Add lease drawer asks for, one section per page, so a lease made here and a lease made there are the same lease.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Leases and press Guided lease.', 'shot' => 'leases-list'],
                        ['text' => 'The first page explains the words the assistant uses — party, owner, unit — so nothing later has to be guessed at. Read it once and press Next.', 'shot' => 'wizard-intro'],
                        ['text' => 'Property and tenant. Choose the property and the unit being let, or create either; record who owns it and their shares, if Patrimoine does not know yet; then choose the tenant. Anything not recorded yet can be created here as you go.', 'shot' => 'wizard-property'],
                        ['text' => 'Lease period. Set the start date and either a duration or an end date, and the notice date if the letting has one. The start date is the anniversary for the whole letting: a lease beginning on the 31st is billed on the 31st of every month that has one, and on the last day of the months that do not.', 'shot' => 'wizard-dates'],
                        ['text' => 'Rent terms. The rent field is a MONTH of rent, whatever the payment frequency — a lease paid every 3 Months at 1,000 a month is invoiced 3,000 a quarter. Paid every counts months from the start date rather than calendar quarters, which is why it reads 1 Month, 3 Months, 6 Months and 12 Months. Set it, then the due day, the VAT on your fee, any first-period adjustment, and the deposit.', 'shot' => 'wizard-rent'],
                        ['text' => 'Entering a deposit receives it: the money goes into this lease\'s own Security Deposit account. Say when it changed hands and how. The date may be before the lease starts, because a deposit is usually what secures the unit.'],
                        ['text' => 'Advance payment. Enter the advance and how much of it is held back as a rent reserve; Patrimoine shows you what is left as consumable advance. If the advance has already been received, say when and how it was paid — that date may also precede the lease. When it came in as cash the cashier is whoever is signed in, and Patrimoine fills that in itself.'],
                        ['text' => 'Rent increment. Set a scheduled increase, if there is one. Its date cannot fall before the lease begins.'],
                        ['text' => 'Fees and commission. Choose the agent, if the letting has one, and say what they are owed; the commission field appears the moment an agent is chosen, directly under their name. Then set your management fee and any notes about the letting.'],
                        ['text' => 'Read the review page. It shows everything that is about to be created, in one list.', 'shot' => 'wizard-review'],
                        ['text' => 'Press Create and activate. Everything is created together; nothing is saved before this point. Save as draft sits at the top of the page beside Cancel, and is offered on every page including this one.'],
                    ],
                    'after' => 'The lease is live, its deposit is held, and its first invoice follows on the schedule you set.',
                ],

                'lease_drafts' => [
                    'title' => 'Leave the assistant half-finished and come back',
                    'intro' => 'Save as draft keeps the assistant exactly as you left it, however little you have filled in. It is not a half-made lease — no record is created until you activate it.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Press Save as draft at any point before the review page.'],
                        ['text' => 'The draft appears under Unfinished assistants at the top of the Leases page, named after whoever started it and the day they did.', 'shot' => 'lease-drafts'],
                        ['text' => 'Press Continue. Everything you had typed comes back, and the assistant opens on the first page — press Next to walk to wherever you had got to.'],
                        ['text' => 'Press Discard to throw it away. Discarding an unfinished assistant deletes nothing else — there was never a lease.'],
                    ],
                ],

                'create_lease_directly' => [
                    'title' => 'Create a lease directly',
                    'intro' => 'When the property, unit and tenant already exist, the direct form is quicker than the assistant.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Leases and press Add Lease.'],
                        ['text' => 'Choose the unit and the tenant. Both must already exist.', 'shot' => 'lease-drawer'],
                        ['text' => 'Set the start date, the end date or duration, the rent and how often it is due. The rent field is a MONTH of rent whatever the frequency, and the start date stays the anniversary for the whole letting.'],
                        ['text' => 'Enter the deposit, if there is one. Entering it receives it: the money goes into this lease\'s own Security Deposit account, and three fields appear asking when it changed hands and how. That date may be before the lease starts, because a deposit is usually what secures the unit.'],
                        ['text' => 'Add the management fee and the agent commission if they apply.'],
                        ['text' => 'Press Save.'],
                    ],
                ],

                'extend_lease' => [
                    'title' => 'Extend a lease',
                    'intro' => 'Extending writes a new term against the same lease, so the history of what was agreed and when stays readable.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open the lease and press Extend.'],
                        ['text' => 'Set the new end date, and the new rent if it changes.', 'shot' => 'lease-extend'],
                        ['text' => 'Press Save. The lease keeps its number and its history; the new term sits alongside the old one.'],
                    ],
                ],

                'rent_increment' => [
                    'title' => 'Schedule a rent increase',
                    'intro' => 'An increment is dated in advance and applied by Patrimoine on the day it falls due, so nobody has to remember to do it.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open the lease and find its rent increments.'],
                        ['text' => 'Enter the new rent and the date it takes effect.', 'shot' => 'rent-increment'],
                        ['text' => 'Press Save. Nothing changes yet.'],
                        ['text' => 'On the effective date the nightly run applies it, and invoices from then on carry the new rent.'],
                        ['text' => 'A scheduled increment that has not yet applied can be cancelled.'],
                    ],
                ],

                'terminate_lease' => [
                    'title' => 'Terminate a lease and settle',
                    'intro' => 'Termination closes the letting and settles the deposit in the same act: what is owed is deducted, and the remainder is returned. It cannot be undone.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open the lease and press Terminate.'],
                        ['text' => 'Set the termination date.', 'shot' => 'lease-terminate'],
                        ['text' => 'Enter any deductions from the deposit, with a reason for each. Unpaid rent and damage are the usual ones.'],
                        ['text' => 'Read the settlement figure. It is the deposit less the deductions, and it is what the tenant is owed back.'],
                        ['text' => 'Confirm. Patrimoine writes the settlement, produces the refund voucher, and closes the lease. Final settlement is irreversible.'],
                    ],
                ],

                'lease_history' => [
                    'title' => 'See a lease\'s financial history',
                    'intro' => 'Everything that has ever happened on one letting, in one place: invoices raised, payments received, deposits held, vouchers issued.',
                    'steps' => [
                        ['text' => 'Open the lease and press Financial history.'],
                        ['text' => 'Every movement is one row: the date, what it was, its reference, the fund it touched, how it was paid and how much. Twenty-five rows a page, or 50 or 100, and every page one press away.', 'shot' => 'lease-history'],
                        ['text' => 'Where a movement produced a document, Open in the last column brings it up.'],
                    ],
                ],

                'delete_lease' => [
                    'title' => 'Delete a lease',
                    'intro' => 'A lease with no money against it can be deleted. One with invoices or payments cannot, and Patrimoine says so before you commit to anything.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open the lease and find the danger zone at the foot of the page.'],
                        ['text' => 'Press Delete. Patrimoine lists exactly what the deletion would take with it. Only a draft lease that has taken no money can be deleted; any other says Archive instead.'],
                        ['text' => 'Confirm if the list is empty. If it is not, the lease stays and the reason is named.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Money in
        |----------------------------------------------------------------------
        */

        'money_in' => [
            'title' => 'Money in',
            'summary' => 'Taking rent, holding deposits, and everything that touches a tenant\'s balance.',

            'tasks' => [

                'record_payment' => [
                    'title' => 'Record a rent payment',
                    'intro' => 'Recording a payment is the single most common thing anybody does in Patrimoine. It writes the receipt, applies the money to what is owed, and posts the accounting entries in one act.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Tenants and select the tenant.', 'shot' => 'tenants-list'],
                        ['text' => 'Press Record deposit — this is where money received from a tenant is entered, whatever it is for.', 'shot' => 'tenant-deposit'],
                        ['text' => 'Enter the amount, the date it was received, and how it was paid: cash, cheque, bank transfer or mobile money.'],
                        ['text' => 'Name who received it and add the reference, if there is one.'],
                        ['text' => 'Press Save. The receipt is produced immediately and can be emailed to the tenant from the same screen.'],
                    ],
                    'after' => 'The money is applied to open invoices oldest first. See the next task for exactly how.',
                ],

                'fifo' => [
                    'title' => 'Understand how a payment is applied',
                    'intro' => 'Patrimoine never asks which invoice a payment is for. It applies money to the oldest unpaid invoice first, then the next, and so on — the rule accountants expect and the one that keeps arrears honest.',
                    'steps' => [
                        ['text' => 'A payment is applied to the oldest open invoice until that invoice is settled.'],
                        ['text' => 'Whatever is left goes to the next oldest, and so on.'],
                        ['text' => 'Anything remaining once every invoice is settled stays on the payment, waiting to be filed into one of the tenant\'s accounts. It is NOT applied to the next invoice on its own.'],
                        ['text' => 'File it from the payment itself, choosing the rent reserve, the consumable advance or the deposit. Until you do, the money is on no balance and in no account.'],
                        ['text' => 'The bell keeps telling you money has been received and not filed, with the amount, so nothing sits unnoticed.'],
                        ['text' => 'The allocation is shown on the payment itself, so you can always see which invoices one payment answered.', 'shot' => 'payment-allocation'],
                    ],
                ],

                'tenant_withdrawal' => [
                    'title' => 'Withdraw from a tenant fund',
                    'intro' => 'Money held for a tenant — a deposit, or a credit balance — can be paid back out. The withdrawal produces its own numbered receipt.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Tenants, select the tenant, and press Withdraw.'],
                        ['text' => 'Choose the account the money comes from and enter the amount.', 'shot' => 'tenant-withdrawal'],
                        ['text' => 'Patrimoine will not let you withdraw more than the account holds.'],
                        ['text' => 'Press Save. The withdrawal receipt is produced and can be emailed.'],
                    ],
                ],

                'tenant_expense' => [
                    'title' => 'Record a tenant expense',
                    'intro' => 'Something paid on the tenant\'s behalf — a repair they are liable for, a utility bill — charged against their account.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Tenants, select the tenant, and press Record expense.'],
                        ['text' => 'Enter the amount, the date and a description of what it was.', 'shot' => 'tenant-expense'],
                        ['text' => 'Press Save. The expense is charged to the tenant and appears on their statement.'],
                    ],
                ],

                'tenant_transfer' => [
                    'title' => 'Transfer between tenant accounts',
                    'intro' => 'Moving money a tenant already holds from one of their accounts to another — a credit balance towards a deposit, for instance. No money enters or leaves.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Tenants, select the tenant, and press Transfer.'],
                        ['text' => 'Choose the account it comes from and the account it goes to.', 'shot' => 'tenant-transfer'],
                        ['text' => 'Enter the amount and the date, and say why.'],
                        ['text' => 'Press Save. A transfer voucher is produced, and it can be emailed like any other document.'],
                    ],
                ],

                'tenant_adjustment' => [
                    'title' => 'Adjust a tenant balance',
                    'intro' => 'A correction, and only a correction. Deposits, expenses and withdrawals each have their own action; using an adjustment for one of those hides what really happened.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Tenants, select the tenant, and press Adjust.'],
                        ['text' => 'Choose the account and enter what the balance should be.', 'shot' => 'tenant-adjustment'],
                        ['text' => 'Patrimoine shows the current balance, the corrected balance and the difference between them before you save.'],
                        ['text' => 'Explain why the correction is needed. The reason is kept with the entry for good.'],
                        ['text' => 'Press Save.'],
                    ],
                ],

                'tenant_accounts' => [
                    'title' => 'See what a tenant holds and owes',
                    'intro' => 'One screen for the whole relationship: every account, its balance, and every movement through it.',
                    'steps' => [
                        ['text' => 'Open Tenants and select the tenant.'],
                        ['text' => 'Press View accounts.', 'shot' => 'tenant-accounts'],
                        ['text' => 'Each account shows its balance and its movements, newest first.'],
                        ['text' => 'Press any movement to open the document behind it.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Owners
        |----------------------------------------------------------------------
        */

        'owners' => [
            'title' => 'Owners',
            'summary' => 'What each owner is entitled to, what they have been charged, and paying them.',

            'tasks' => [

                'owner_entitlement' => [
                    'title' => 'See what an owner is entitled to',
                    'intro' => 'An owner earns from rent that has actually been collected, not from rent that has been invoiced. The management fee and any agent commission are taken off as the rent arrives.',
                    'steps' => [
                        ['text' => 'Open Owners and select the owner.', 'shot' => 'owners-list'],
                        ['text' => 'The account shows what has been earned, what has been charged, what has already been paid out, and what remains available.', 'shot' => 'owner-account'],
                        ['text' => 'An owner with a share of several buildings has one consolidated account, with the properties listed beneath it.'],
                    ],
                ],

                'owner_deposit' => [
                    'title' => 'Record money received from an owner',
                    'intro' => 'An owner sometimes puts money in — to cover a repair, or to clear a negative balance.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Owners, select the owner, and press Record deposit.'],
                        ['text' => 'Enter the amount, the date, how it was paid and what it is for.', 'shot' => 'owner-deposit'],
                        ['text' => 'Press Save.'],
                    ],
                ],

                'owner_expense' => [
                    'title' => 'Record a property expense',
                    'intro' => 'A cost against one of the owner\'s properties — a repair, maintenance, a service charge. It reduces what the owner is entitled to.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Owners, select the owner, and press Record expense.'],
                        ['text' => 'Choose which property the expense belongs to.', 'shot' => 'owner-expense'],
                        ['text' => 'Enter the amount, the date and a description — "air-conditioner repair" rather than "repair".'],
                        ['text' => 'Press Save. Where a building has several owners, the cost is shared in proportion to their shares.'],
                    ],
                ],

                'owner_payout' => [
                    'title' => 'Pay an owner',
                    'intro' => 'Paying out what an owner has earned. Patrimoine will not let you pay out more than is actually available.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Owners, select the owner, and press Make payout.'],
                        ['text' => 'The available balance is shown. Enter the amount and the date, or press Withdraw all to fill the field with everything owed.', 'shot' => 'owner-payout'],
                        ['text' => 'Choose how it was paid and name who authorised it.'],
                        ['text' => 'Press Save. The payout is matched against the earnings it settles, oldest first, and a numbered receipt is produced.'],
                        ['text' => 'The receipt shows the owner how the figure was reached. A summary sits under the amount, and under that every movement since they last collected, itemised: the rent by unit and the period it was for, the fee and the VAT on it, and each expense with its building and date. The three tables add up to the payout, so they can check it without asking you for a statement.'],
                    ],
                ],

                'owner_adjustment' => [
                    'title' => 'Adjust an owner account',
                    'intro' => 'For accounting corrections only. Deposits, expenses and payouts each have their own action.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Owners, select the owner, and press Adjust.'],
                        ['text' => 'Choose the direction: a credit increases the owner\'s balance, a debit reduces it.', 'shot' => 'owner-adjustment'],
                        ['text' => 'Enter the amount and the date, and explain why the correction is needed.'],
                        ['text' => 'Press Save.'],
                    ],
                ],

                'owner_statement' => [
                    'title' => 'Produce an owner statement',
                    'intro' => 'The document an owner actually wants: what their properties earned, what was spent, what was deducted and what they were paid, over a period they can check.',
                    'steps' => [
                        ['text' => 'Open Owners and select the owner.'],
                        ['text' => 'Press Statement and choose the period.', 'shot' => 'owner-statement'],
                        ['text' => 'Produce it as a PDF to send, or as XLSX or CSV to work with.'],
                        ['text' => 'It can be emailed to the owner directly, provided their record allows emails.'],
                    ],
                ],

                'owner_bills' => [
                    'title' => 'Bill an owner and take the payment',
                    'intro' => 'Where an owner is to be invoiced for something rather than have it deducted — a management fee billed separately, say — Patrimoine raises a numbered bill and tracks whether it has been paid.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open Owners, select the owner, and find their expense bills.'],
                        ['text' => 'Raise the bill with its amount, date and description.', 'shot' => 'owner-bill'],
                        ['text' => 'When it is paid, press Pay on the bill and record how.'],
                        ['text' => 'A payment recorded in error can be cancelled from the same place, which reopens the bill.'],
                    ],
                ],

            ],
        ],


        /*
        |----------------------------------------------------------------------
        | Invoicing and automation
        |----------------------------------------------------------------------
        */

        'invoicing' => [
            'title' => 'Invoicing and automation',
            'summary' => 'Rent invoices, what Patrimoine does on its own each night, and paying an invoice off.',

            'tasks' => [

                'how_invoices_are_raised' => [
                    'title' => 'Understand how rent invoices are raised',
                    'intro' => 'You do not raise rent invoices by hand. Patrimoine raises them from the lease, on the schedule the lease sets, so a letting cannot be forgotten.',
                    'steps' => [
                        ['text' => 'Each active lease says what the rent is and how often it falls due.'],
                        ['text' => 'The nightly run raises the next invoice as each period comes round, numbered in sequence.', 'shot' => 'invoices-list'],
                        ['text' => 'The day the lease began is the anniversary, and it is kept for the whole letting. A lease starting on the 31st is billed on 31 January, 28 February, 31 March, 30 April — the last day of a month too short to hold the 31st, and the 31st again the month after. The anniversary is never lost to a short month.'],
                        ['text' => 'An invoice that already exists for a period is never raised twice, however many times the run happens.'],
                        ['text' => 'The invoice is emailed to the tenant, unless emails are switched off for them or for the organisation.'],
                    ],
                ],

                'nightly_run' => [
                    'title' => 'Know what happens each night',
                    'intro' => 'Six jobs run automatically. Between them they keep the books moving without anybody logging in.',
                    'steps' => [
                        ['text' => 'Rent invoices are raised for any lease whose next period has begun.'],
                        ['text' => 'Rent increments that have reached their effective date are applied.'],
                        ['text' => 'Reminders go out for invoices that are due or overdue.'],
                        ['text' => 'Licences approaching expiry produce their notices.'],
                        ['text' => 'Each run is recorded, so it can be established afterwards that it happened and what it did.'],
                    ],
                ],

                'pay_invoice' => [
                    'title' => 'Pay an invoice off',
                    'intro' => 'Rent is normally settled by recording a payment from the tenant, which is applied oldest invoice first. An invoice can also be paid directly where that is clearer.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open the invoice from the tenant, the lease, or the invoice list.'],
                        ['text' => 'Press Pay.', 'shot' => 'invoice-pay'],
                        ['text' => 'Enter the amount, the date and how it was paid.'],
                        ['text' => 'Press Save. The receipt is produced and the invoice shows what remains, if anything.'],
                    ],
                ],

                'cancel_invoice_payment' => [
                    'title' => 'Cancel a payment made in error',
                    'intro' => 'A payment recorded against the wrong invoice, or entered twice, is cancelled rather than deleted: the correction stays visible in the accounts.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Open the invoice and find the payment.'],
                        ['text' => 'Press Cancel payment and say why.'],
                        ['text' => 'Confirm. The invoice reopens for the amount concerned, and the reversal is posted to the journal rather than the original entry being erased.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Reports
        |----------------------------------------------------------------------
        */

        'reports' => [
            'title' => 'Reports',
            'summary' => 'Getting figures out of Patrimoine, on screen or as a file.',

            'tasks' => [

                'run_report' => [
                    'title' => 'Run a report',
                    'intro' => 'Nine reports cover the portfolio, the money and the arrears. Every one is run the same way.',
                    'steps' => [
                        ['text' => 'Open Reports from the sidebar and choose the report you want.', 'shot' => 'reports-list'],
                        ['text' => 'Set the period and any filters — a building, an owner, a tenant.', 'shot' => 'report-filters'],
                        ['text' => 'Press Run report. The result appears on screen.', 'shot' => 'report-result'],
                        ['text' => 'Change a filter and run it again; nothing is saved until you export.'],
                    ],
                ],

                'export_report' => [
                    'title' => 'Export a report',
                    'intro' => 'Every report can leave Patrimoine in three forms: PDF to send, XLSX to work with, CSV to load somewhere else.',
                    'steps' => [
                        ['text' => 'Run the report first. Exports carry exactly what is on screen, filters included.'],
                        ['text' => 'Press PDF, XLSX or CSV.', 'shot' => 'report-export'],
                        ['text' => 'PDF opens in a new tab; XLSX and CSV download.'],
                        ['text' => 'Every role may export, including Viewers.'],
                    ],
                ],

                'dashboard' => [
                    'title' => 'Read the dashboard',
                    'intro' => 'The dashboard is the state of the business in one screen, and every figure on it is a link to the records behind it.',
                    'steps' => [
                        ['text' => 'Open Dashboard from the sidebar.', 'shot' => 'dashboard'],
                        ['text' => 'The cards show what has been collected, what is outstanding, and what is happening this month.'],
                        ['text' => 'The collections trend is the whole of the year you are reading, January to December. Months still to come sit at zero until they arrive, so the shape of the year is visible from January onwards.'],
                        ['text' => 'The bell at the top right carries what needs attention: unpaid invoices, unpaid expenses, leases ending.', 'shot' => 'notifications'],
                        ['text' => 'Press any figure to open the list it was counted from.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | The journal and the log
        |----------------------------------------------------------------------
        */

        'journal' => [
            'title' => 'Audit',
            'summary' => 'The two records Patrimoine keeps of itself: what the money did, and what people did. Both live under Audit, one tab each.',

            'tasks' => [

                'financial_journal' => [
                    'title' => 'Read the financial journal',
                    'intro' => 'Every movement of money in Patrimoine is double-entry bookkeeping underneath. The journal is that bookkeeping, readable, with the document that caused each entry one press away.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Audit from the Administration group and press the Financial journal tab.', 'shot' => 'journal-list'],
                        ['text' => 'Entries run newest first, each with its own number, its date and its total.'],
                        ['text' => 'Filter by date, by type of transaction, or by account.'],
                        ['text' => 'Press an entry to see its lines — what was debited, what was credited, and what document produced it.', 'shot' => 'journal-entry'],
                    ],
                ],

                'opening_balances' => [
                    'title' => 'Understand opening balances',
                    'intro' => 'An organisation that had a life before Patrimoine starts with balances rather than from nothing. Once the opening position is set and the gate closed, it cannot be quietly moved.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Opening balances are imported once, at the point the organisation starts using Patrimoine.'],
                        ['text' => 'They are reconciled: what was imported must agree with what the journal says.'],
                        ['text' => 'After the cutover the gate is closed, and further entries have to be made as ordinary transactions.'],
                        ['text' => 'The reconciliation can be re-run at any time to prove the position still balances.'],
                    ],
                ],

                'activity_log' => [
                    'title' => 'Read the activity log',
                    'intro' => 'Who did what, when, and from where. The log is append-only: nothing in it can be edited or removed, by anybody, including us.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Audit from the Administration group. It opens on Activity monitor.', 'shot' => 'activity-list'],
                        ['text' => 'Each event records the person, the action, the record it touched, and the time.'],
                        ['text' => 'It also records the address, browser and device the action came from.'],
                        ['text' => 'Filter by person, by action or by date, then press an event for the whole of it.', 'shot' => 'activity-entry'],
                        ['text' => 'Export the log as XLSX or CSV, for an auditor who wants it outside Patrimoine. There is no PDF: a log that is kept for ever grows past what a page can hold.'],
                    ],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Administration
        |----------------------------------------------------------------------
        */

        'admin' => [
            'title' => 'Administration',
            'summary' => 'Your organisation, the people in it, your plan, and your data.',

            'tasks' => [

                'settings_home' => [
                    'title' => 'Find your way around Settings',
                    'intro' => 'Settings holds the whole of the account: the organisation itself, the people who can sign in, and your plan.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings from the Administration group in the sidebar.', 'shot' => 'settings-account'],
                        ['text' => 'Organisation holds your own details, which appear on every document you produce.'],
                        ['text' => 'Users, Licence, Preferences, Data and About are the other tabs. The older /users and /license addresses still work and open the right tab.'],
                        ['text' => 'The panel beside the form summarises the account: your plan, the people, the leases, the parties, and when the account was opened.'],
                    ],
                ],

                'organisation_details' => [
                    'title' => 'Set your organisation details',
                    'intro' => 'These are what a tenant or an owner sees on an invoice, a receipt or a statement, so they are worth getting right once.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings and stay on the Organisation tab.'],
                        ['text' => 'Fill in the name, address, telephone number with its country, and email address.', 'shot' => 'settings-organisation'],
                        ['text' => 'Add the registration and tax numbers if your documents must carry them.'],
                        ['text' => 'Add the bank details that should appear on invoices.'],
                        ['text' => 'Press Save. New documents carry the new details; documents already produced are unchanged.'],
                    ],
                ],

                'invite_user' => [
                    'title' => 'Invite a colleague',
                    'intro' => 'People are invited by email and set their own password. Nobody else ever knows it, including you.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings and choose the Users tab.', 'shot' => 'users-list'],
                        ['text' => 'Press Add user.'],
                        ['text' => 'Enter their name and email address, and choose the role they should have.', 'shot' => 'user-drawer'],
                        ['text' => 'Press Save. They are emailed an invitation.'],
                        ['text' => 'They follow the link, set a password, and can then sign in with a code like everybody else.'],
                    ],
                ],

                'change_role' => [
                    'title' => 'Change a role or switch somebody off',
                    'intro' => 'A role can be changed at any time, and takes effect the next time that person loads a screen. Somebody who has left is deactivated rather than deleted, so the history of what they did stays readable.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings, choose Users, and press Edit on the person.'],
                        ['text' => 'Change the role and press Save.'],
                        ['text' => 'To stop somebody signing in, switch them to inactive. Their sessions end and their tokens stop working immediately.'],
                        ['text' => 'Their name stays on everything they did. The activity log is not rewritten.'],
                    ],
                ],

                'licence' => [
                    'title' => 'See your plan and what it allows',
                    'intro' => 'The Licence tab shows the plan you are on, what it includes, and how much of it you are using.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings and choose the Licence tab.', 'shot' => 'settings-licence'],
                        ['text' => 'The current plan is shown with your usage against each of its limits.'],
                        ['text' => 'The table beneath compares the plans.'],
                        ['text' => 'To subscribe, extend or change plan, contact us — plans are issued rather than bought from this screen.'],
                    ],
                ],

                'backup_restore' => [
                    'title' => 'Back up and restore your registry',
                    'intro' => 'Your registry — parties, buildings and their ownership, units and leases — can be exported whole and put back. Financial history is deliberately not importable: it is immutable, and a way to write it from a file would be a way to rewrite the accounts.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings and choose the Data tab.', 'shot' => 'settings-data'],
                        ['text' => 'Press Export to download the registry as a workbook. Do this before any large change.'],
                        ['text' => 'To restore, choose the file and press Review restore. Nothing is written yet.'],
                        ['text' => 'Read the dry run: it says exactly what would be created and what would be matched to records you already have.', 'shot' => 'settings-restore'],
                        ['text' => 'Press Apply this restore only if the dry run says what you expect. Records are matched rather than duplicated, so restoring twice does not double your registry.'],
                    ],
                ],

                'my_data' => [
                    'title' => 'Download your own data',
                    'intro' => 'Anybody with an account can take a copy of everything Patrimoine holds about them, without asking anybody. No role is needed and no administrator is involved.',
                    'steps' => [
                        ['text' => 'Press your photograph at the top right, then Edit profile.'],
                        ['text' => 'Press Download my data.', 'shot' => 'my-data'],
                        ['text' => 'The file holds your account details, the tokens your account can be used with, and every action you have taken \u2014 including the address, browser and device each came from.'],
                        ['text' => 'Your password is never in it. It is stored only as a hash and cannot be turned back into anything.'],
                    ],
                ],

                'organisation_data' => [
                    'title' => 'Download everything the organisation holds',
                    'intro' => 'A complete copy of the whole organisation, financial history included, as one structured file. Wider than the registry export beside it: that one is the portable registry, this is everything.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings and choose the Data tab.'],
                        ['text' => 'Press Download everything.', 'shot' => 'organisation-data'],
                        ['text' => 'Password hashes are stripped before the file is written. Everything else is there.'],
                        ['text' => 'Use it to answer somebody who asks what you hold, or to keep a copy of your own outside Patrimoine.'],
                    ],
                ],

                'archive' => [
                    'title' => 'Archive a record you cannot delete',
                    'intro' => 'Patrimoine refuses to delete anything the accounting still refers to. Archiving is what it offers instead: the record leaves the lists, and nothing else about it moves. It asks the way deletion asks, because to anybody who did not do it the record has simply gone.',
                    'who' => 'Administrators and Property Managers',
                    'steps' => [
                        ['text' => 'Look at the button on the row. It says Delete while the record can still be removed outright, and Archive once it cannot. Both are red, because both take the record off every screen. Parties, properties, units and leases can all be archived.'],
                        ['text' => 'Press Archive. A drawer opens saying exactly what archiving does — the record leaves every list and every dropdown, nothing else changes, and it can be put back — and asks why.'],
                        ['text' => 'Write the reason. It is kept with the record and shown on the Archive page, so the next person to ask why something is missing has the answer in front of them.'],
                        ['text' => 'A lease has to be terminated first. Archiving a letting that is still running would hide a tenancy still being invoiced and still holding a deposit, so Patrimoine refuses it with PM-3097. Terminate the lease, complete its settlement, and Archive is then available on the same row.'],
                        ['text' => 'Nothing else changes. Every invoice, receipt, journal entry and activity-log line still names it, because the record itself has not moved.'],
                        ['text' => 'Open Archive, under Audit, to see everything that has been put away, why, and who put it there. Search across the names and the reasons, or narrow to one kind of record with the chips.', 'shot' => 'archive-list'],
                        ['text' => 'Press Restore to put one back. It asks for a reason too — the record returns to every list and every dropdown at once — and that reason goes to the activity log, because a restored record is not archived for any reason at all.'],
                    ],
                ],

                'close_account' => [
                    'title' => 'Close the account',
                    'intro' => 'Permanently deleting the organisation and everything in it. It cannot be undone, by you or by us: there is no recycle bin and no copy kept.',
                    'who' => 'Administrators',
                    'steps' => [
                        ['text' => 'Open Settings, stay on the Organisation tab, and go to the foot of the page.', 'shot' => 'settings-danger'],
                        ['text' => 'Press Close account. The panel counts what will go: the people, the leases, the parties.', 'shot' => 'close-account-drawer'],
                        ['text' => 'Type the organisation name back, exactly as it is shown.'],
                        ['text' => 'Enter your own password.'],
                        ['text' => 'Press Delete everything. The properties, leases, invoices, payments and the financial journal all go with it, and you are signed out.'],
                    ],
                    'after' => 'If what you want is to stop paying, or to put Patrimoine aside for a while, write to support instead — that is a conversation, and this is not.',
                ],

            ],
        ],

    ],

];
