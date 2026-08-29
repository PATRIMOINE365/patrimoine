/*
|--------------------------------------------------------------------------
| Patrimoine Browser Application Bootstrap
|--------------------------------------------------------------------------
|
| This file intentionally remains small.
|
| Feature-specific behaviour lives in dedicated modules:
|
| - auth.js        Authentication and application shell
| - dashboard.js   Dashboard functionality
| - properties.js  Property and Unit management
| - parties.js     Party management
| - leases.js      Lease management
| - settings.js    Application-wide settings
| - core.js        Shared browser/API helpers
|
| Future application areas such as Payments and Reports will be imported
| here as their UI modules are implemented.
|
*/

import {
    initializeAuthenticatedShell,
    initializeLogin,
} from './auth.js';

import {
    initializeSignup,
    initializeVerifyEmail,
} from './signup.js';

import {
    initializeTheme,
} from './theme.js';

import {
    initializePhoneInputs,
    refreshPhoneInputs,
} from './phone-input.js';

import {
    initializePublicPresentationControls,
} from './public-presentation.js';

import {
    applyCachedPresentationLanguage,
    applyTranslations,
    loadPresentationConfiguration,
    initializeMoneyInputs,
    initializeErrorCodeLinks,
} from './core.js';

import {
    initializeDashboard,
} from './dashboard.js';

import {
    initializeProperties,
} from './properties.js';

import {
    initializeParties,
} from './parties.js';

import {
    initializeLeases,
} from './leases.js';

import {
    initializeLeaseWizard,
} from './lease-wizard.js';

import {
    initializeOwners,
} from './owners.js';

import {
    initializeTenants,
} from './tenants.js';

import {
    initializeReports,
} from './reports.js';

import {
    initializeSettings,
} from './settings.js';

import {
    initializeUsers,
} from './users.js';

import {
    initializeActivityLog,
} from './activity-log.js';

import {
    initializeFinancialJournal,
} from './financial-journal.js';

import {
    initializeAccounting,
} from './accounting.js';

import {
    initializeHelp,
} from './help.js';

import {
    initializeLicense,
} from './license.js';

import {
    initializeAdmin,
} from './admin.js';

import {
    initializeForgotPassword,
    initializeInvitation,
    initializeResetPassword,
} from './password.js';


/*
|--------------------------------------------------------------------------
| Application Bootstrap
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    async () => {
        /*
         * Theme is personal browser presentation state and does not require
         * authentication or organisation configuration.
         */
        initializeTheme();

        /*
         * Translate from the cached language FIRST, synchronously.
         *
         * Menu items are ordinary links, so each navigation is a fresh
         * Blade document. When Blade could not resolve an organisation it
         * renders English, and awaiting the presentation endpoint before
         * translating left that English visible for a whole network round
         * trip. Applying the cached language up front closes that window;
         * the endpoint still corrects the page immediately below.
         */
        applyCachedPresentationLanguage();

        /*
         * Presentation configuration is public and must be available before
         * either login or authenticated application UI is initialized.
         *
         * The loader falls back safely to compatibility defaults when the
         * endpoint cannot be reached.
         */
        await loadPresentationConfiguration();

        /*
         * Apply organisation-wide language before Login or protected
         * application modules initialize.
         */
        applyTranslations();

        /*
         * V1.0.12: theme + language toggles on the public screens
         * (present only in the auth layout; a no-op everywhere else).
         */
        initializePublicPresentationControls();

        /*
         * V1.0.8: live thousands grouping on all monetary inputs,
         * using the organisation currency's separator.
         */
        initializeMoneyInputs();

        /*
         * V1.0.30: the country picker beside every telephone number. The
         * listeners are delegated, so drawers rendered later are covered
         * without being told; the refresh only paints what is on screen
         * already, such as the profile drawer in the shell.
         */
        initializePhoneInputs();

        refreshPhoneInputs();

        /*
         * Login and authenticated application screens are mutually
         * exclusive.
         */
        const loginPage =
            await initializeLogin();

        if (loginPage) {
            return;
        }

        /*
         * Public password ownership screens do not require an API token.
         */
        if (
            initializeForgotPassword()
            || initializeResetPassword()
            || initializeInvitation()
            || initializeSignup()
            || initializeVerifyEmail()
        ) {
            return;
        }

        /*
         * V1.0.11: the platform administration console runs its own
         * shell and its own authentication bootstrap.
         */
        if (await initializeAdmin()) {
            return;
        }

        /*
         * Verify authentication before any protected business module
         * communicates with the Patrimoine API.
         */
        const authenticated =
            await initializeAuthenticatedShell();

        if (! authenticated) {
            return;
        }

        /*
         * Each initializer detects its own page and returns immediately
         * when its corresponding DOM elements do not exist.
         *
         * This allows one Vite bundle to serve the entire application.
         */
        /*
         * Codes become links wherever an error is shown, so somebody
         * reading one can go straight to what it means.
         */
        initializeErrorCodeLinks();

        await initializeDashboard();

        await initializeProperties();

        await initializeParties();

        await initializeLeases();

        await initializeLeaseWizard();

        await initializeOwners();

        await initializeTenants();

        await initializeAccounting();

        await initializeReports();

        await initializeSettings();

        await initializeUsers();

        await initializeActivityLog();

        await initializeFinancialJournal();

        await initializeHelp();

        await initializeLicense();
    }
);
