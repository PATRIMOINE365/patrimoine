import './setup';
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
    initializeTheme,
} from './theme.js';

import {
    applyTranslations,
    loadPresentationConfiguration,
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
    initializeHelp,
} from './help.js';

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
        ) {
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
        await initializeDashboard();

        await initializeProperties();

        await initializeParties();

        await initializeLeases();

        await initializeOwners();

        await initializeTenants();

        await initializeReports();

        await initializeSettings();

        await initializeUsers();

        await initializeActivityLog();

        await initializeFinancialJournal();

        await initializeHelp();
    }
);
