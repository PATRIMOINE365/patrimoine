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
    initializePayments,
} from './payments.js';

import {
    initializeOwners,
} from './owners.js';

import {
    initializeReports,
} from './reports.js';

import {
    initializeSettings,
} from './settings.js';

/*
|--------------------------------------------------------------------------
| Application Bootstrap
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    async () => {
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

        await initializePayments();

        await initializeOwners();

        await initializeReports();

        await initializeSettings();
    }
);
