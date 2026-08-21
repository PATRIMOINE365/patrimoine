import {
    translationFor,
} from './translations.js';

/*
|--------------------------------------------------------------------------
| Patrimoine Core Utilities
|--------------------------------------------------------------------------
|
| Shared browser-side infrastructure for the Patrimoine application.
|
| This module deliberately contains only functionality that can be reused
| by multiple application areas:
|
| - Sanctum API token storage;
| - authenticated API requests;
| - Laravel JSON response processing;
| - common display formatting;
| - safe HTML escaping;
| - small form and DOM helpers.
|
| Domain-specific behaviour must remain in the corresponding feature
| module rather than being added here.
|
*/

const TOKEN_KEY =
    'patrimoine_api_token';

/*
|--------------------------------------------------------------------------
| Authentication Token Storage
|--------------------------------------------------------------------------
|
| Patrimoine currently uses Sanctum personal access tokens for the
| first-party web application and API clients.
|
| sessionStorage intentionally scopes the token to the current browser
| session rather than persisting it indefinitely.
|
*/

/**
 * Return the current Sanctum API token, if one exists.
 *
 * @returns {string|null}
 */
export function token() {
    return sessionStorage.getItem(
        TOKEN_KEY
    );
}

/**
 * Save a valid Sanctum personal access token.
 *
 * @param {string} value
 * @throws {Error} When the supplied token is empty.
 */
export function saveToken(value) {
    if (
        typeof value !== 'string'
        || value.trim() === ''
    ) {
        throw new Error(
            'Cannot store an empty authentication token.'
        );
    }

    sessionStorage.setItem(
        TOKEN_KEY,
        value
    );
}

/**
 * Remove the locally stored authentication token.
 */
export function clearToken() {
    sessionStorage.removeItem(
        TOKEN_KEY
    );
}

/*
|--------------------------------------------------------------------------
| API Helpers
|--------------------------------------------------------------------------
*/

/**
 * Send an authenticated request to the Patrimoine API.
 *
 * The helper automatically:
 *
 * - requests JSON responses;
 * - adds JSON Content-Type where appropriate;
 * - attaches the Sanctum Bearer token;
 * - clears an invalid token after HTTP 401;
 * - redirects protected pages back to login after authentication expiry.
 *
 * @param {string} path
 * @param {RequestInit} options
 * @returns {Promise<Response>}
 */
export async function apiRequest(
    path,
    options = {}
) {
    const headers =
        new Headers(
            options.headers || {}
        );

    headers.set(
        'Accept',
        'application/json'
    );

    if (
        options.body
        && ! headers.has(
            'Content-Type'
        )
        && ! (
            options.body
            instanceof FormData
        )
    ) {
        headers.set(
            'Content-Type',
            'application/json'
        );
    }

    const authToken =
        token();

    if (authToken) {
        headers.set(
            'Authorization',
            `Bearer ${authToken}`
        );
    }

    const response =
        await fetch(
            path,
            {
                ...options,
                headers,
            }
        );

    /*
     * A 401 means the token is missing, expired, revoked, or otherwise
     * invalid. Remove it immediately so subsequent requests cannot keep
     * attempting to use stale credentials.
     */
    if (response.status === 401) {
        clearToken();

        if (
            window.location.pathname
            !== '/login'
        ) {
            window.location.replace(
                '/login'
            );
        }

        throw new Error(
            translationFor('core.session_expired')
        );
    }

    return response;
}

/**
 * Parse a Laravel JSON API response.
 *
 * Validation responses normally contain both a generic message and an
 * errors object. Specific validation errors are preferred because they
 * provide more useful information to the user.
 *
 * @param {Response} response
 * @returns {Promise<any>}
 * @throws {Error} For unsuccessful API responses.
 */
export async function parseJsonResponse(
    response
) {
    const data =
        await response
            .json()
            .catch(
                () => ({})
            );

    if (! response.ok) {
        const validationMessage =
            Object
                .values(
                    data.errors || {}
                )
                .flat()
                .filter(Boolean)
                .join(' ');

        const message =
            validationMessage
            || data.message
            || translationFor('core.request_failed');

        throw new Error(
            message
        );
    }

    return data;
}

/*
|--------------------------------------------------------------------------
| Application Presentation
|--------------------------------------------------------------------------
|
| Browser presentation is controlled by the Managing Organisation settings.
|
| The public presentation endpoint is loaded once during application
| bootstrap. Stable compatibility defaults remain available so login and
| other browser screens can still render if the endpoint is temporarily
| unavailable.
|
*/

const PRESENTATION_LANGUAGE_STORAGE_KEY =
    'patrimoine.presentation.language';

/**
 * Return the last organisation language successfully confirmed by the
 * public presentation endpoint.
 *
 * This cache affects first paint only. The endpoint remains authoritative
 * and refreshes the value during every new document load.
 *
 * @returns {'en'|'fr'}
 */
function cachedPresentationLanguage() {
    try {
        const language =
            window.localStorage.getItem(
                PRESENTATION_LANGUAGE_STORAGE_KEY
            );

        if (
            language === 'en'
            || language === 'fr'
        ) {
            return language;
        }
    } catch {
        /*
         * Browser storage restrictions are non-fatal.
         */
    }

    return 'en';
}

const DEFAULT_PRESENTATION_CONFIGURATION = {
    language: cachedPresentationLanguage(),
    currency: 'GHS',
    locale: 'en',
    browser_locale: 'en-GB',

    currency_definition: {
        code: 'GHS',
        symbol: 'GH₵',
        symbol_position: 'before',
        decimal_digits: 0,
        decimal_separator: '.',
        group_separator: ',',
    },

    supported_languages: [
        'en',
        'fr',
    ],

    supported_currencies: [
        'GHS',
        'FCFA',
    ],
};

let presentationConfiguration =
    {
        ...DEFAULT_PRESENTATION_CONFIGURATION,

        currency_definition: {
            ...DEFAULT_PRESENTATION_CONFIGURATION
                .currency_definition,
        },
    };

let presentationConfigurationPromise =
    null;

/**
 * Load organisation-wide browser presentation configuration.
 *
 * The request is public because the login screen must know the organisation
 * language before authentication. The promise is cached so the endpoint is
 * requested at most once during a page load.
 *
 * Failure is deliberately non-fatal. Compatibility defaults allow the
 * application to remain usable.
 *
 * @returns {Promise<object>}
 */
export async function loadPresentationConfiguration() {
    if (presentationConfigurationPromise) {
        return presentationConfigurationPromise;
    }

    presentationConfigurationPromise =
        fetch(
            '/api/presentation-config',
            {
                headers: {
                    Accept:
                        'application/json',
                },
            }
        )
            .then(
                async (response) => {
                    if (! response.ok) {
                        throw new Error(
                            'Unable to load presentation configuration.'
                        );
                    }

                    const configuration =
                        await response.json();

                    presentationConfiguration = {
                        ...presentationConfiguration,
                        ...configuration,

                        currency_definition: {
                            ...presentationConfiguration
                                .currency_definition,

                            ...(
                                configuration
                                    .currency_definition
                                || {}
                            ),
                        },
                    };

                    const confirmedLanguage =
                        presentationConfiguration
                            .language;

                    if (
                        confirmedLanguage === 'en'
                        || confirmedLanguage === 'fr'
                    ) {
                        try {
                            window.localStorage.setItem(
                                PRESENTATION_LANGUAGE_STORAGE_KEY,
                                confirmedLanguage
                            );
                        } catch {
                            /*
                             * Storage restrictions must not affect
                             * presentation loading.
                             */
                        }

                        document.documentElement.lang =
                            confirmedLanguage;

                        document.documentElement.dataset
                            .presentationLanguage =
                            confirmedLanguage;
                    }

                    return presentationConfiguration;
                }
            )
            .catch(
                () =>
                    presentationConfiguration
            );

    return presentationConfigurationPromise;
}

/**
 * Return the currently loaded presentation configuration.
 *
 * @returns {object}
 */
export function getPresentationConfiguration() {
    return presentationConfiguration;
}

/*
|--------------------------------------------------------------------------
| Translation Helpers
|--------------------------------------------------------------------------
*/

/**
 * Translate one stable browser presentation key.
 *
 * Missing translations fall back to English. Translation remains completely
 * independent from the configured currency.
 *
 * @param {string} key
 * @returns {string}
 */
export function translate(
    key,
    replacements = {}
) {
    return translationFor(
        presentationConfiguration.language
        || 'en',
        key,
        replacements
    );
}

/**
 * Apply translations to server-rendered DOM elements.
 *
 * Supported attributes:
 *
 * data-i18n              Replace element textContent.
 * data-i18n-placeholder  Replace form-control placeholder.
 * data-i18n-title        Set the browser document title.
 */
export function applyTranslations() {
    const language =
        presentationConfiguration.language
        || 'en';

    document.documentElement.lang =
        language;

    document
        .querySelectorAll(
            '[data-i18n]'
        )
        .forEach(
            (element) => {
                const key =
                    element.dataset.i18n;

                if (key) {
                    element.textContent =
                        translate(
                            key
                        );
                }
            }
        );

    document
        .querySelectorAll(
            '[data-i18n-placeholder]'
        )
        .forEach(
            (element) => {
                const key =
                    element.dataset
                        .i18nPlaceholder;

                if (key) {
                    element.setAttribute(
                        'placeholder',
                        translate(
                            key
                        )
                    );
                }
            }
        );

    document
        .querySelectorAll(
            '[data-i18n-aria-label]'
        )
        .forEach(
            (element) => {
                const key =
                    element.dataset
                        .i18nAriaLabel;

                if (key) {
                    element.setAttribute(
                        'aria-label',
                        translate(
                            key
                        )
                    );
                }
            }
        );

    document
        .querySelectorAll(
            '[data-i18n-field-help]'
        )
        .forEach(
            (element) => {
                const key =
                    element.dataset
                        .i18nFieldHelp;

                if (key) {
                    element.setAttribute(
                        'data-field-help-text',
                        translate(
                            key
                        )
                    );
                }
            }
        );

    const titleElement =
        document.querySelector(
            '[data-i18n-title]'
        );

    if (titleElement) {
        const key =
            titleElement.dataset
                .i18nTitle;

        if (key) {
            document.title =
                translate(
                    key
                );
        }
    }

    document.documentElement.dataset.presentationLanguage =
        language;
}

/*
|--------------------------------------------------------------------------
| Display Helpers
|--------------------------------------------------------------------------
*/

/**
 * Format a plain numeric value using the organisation language locale.
 *
 * This is for counts and other non-monetary numbers. Money must continue
 * to use formatCurrency() because currency presentation is independent
 * from language.
 *
 * @param {number|string|null} value
 * @param {Intl.NumberFormatOptions} options
 * @returns {string}
 */
export function formatNumber(
    value,
    options = {}
) {
    const numericValue =
        Number(
            value
        );

    const number =
        Number.isFinite(
            numericValue
        )
            ? numericValue
            : 0;

    return new Intl.NumberFormat(
        presentationConfiguration.browser_locale
        || 'en-GB',
        options
    ).format(
        number
    );
}

/**
 * Format a whole-number Patrimoine monetary amount.
 *
 * Currency is presentation metadata only. This function never converts
 * monetary values between currencies.
 *
 * @param {number|string|null} value
 * @returns {string}
 */
export function formatCurrency(
    value
) {
    const numericValue =
        Number(value);

    const amount =
        Number.isFinite(
            numericValue
        )
            ? numericValue
            : 0;

    const definition =
        presentationConfiguration
            .currency_definition
        || {};

    const symbol =
        String(
            definition.symbol
            || presentationConfiguration.currency
            || ''
        );

    const position =
        definition.symbol_position
        === 'after'
            ? 'after'
            : 'before';

    const groupSeparator =
        typeof definition.group_separator
        === 'string'
            ? definition.group_separator
            : ',';

    const absoluteAmount =
        Math.abs(
            Math.round(
                amount
            )
        );

    const grouped =
        String(
            absoluteAmount
        ).replace(
            /\B(?=(\d{3})+(?!\d))/g,
            groupSeparator
        );

    const sign =
        amount < 0
            ? '-'
            : '';

    if (position === 'after') {
        return amount < 0
            ? `- ${grouped} ${symbol}`.trim()
            : `${grouped} ${symbol}`.trim();
    }

    return amount < 0
        ? `${symbol} - ${grouped}`.trim()
        : `${symbol} ${grouped}`.trim();
}

/**
 * Parse a database date without UTC timezone shifting.
 *
 * @param {string|Date|null} value
 * @returns {Date|null}
 */
function presentationDate(
    value
) {
    if (value instanceof Date) {
        return Number.isNaN(
            value.getTime()
        )
            ? null
            : value;
    }

    if (! value) {
        return null;
    }

    const parts =
        String(value)
            .slice(
                0,
                10
            )
            .split('-');

    if (parts.length !== 3) {
        return null;
    }

    const date =
        new Date(
            Number(parts[0]),
            Number(parts[1]) - 1,
            Number(parts[2])
        );

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return null;
    }

    return date;
}

/**
 * Format a database date using the organisation language locale.
 *
 * @param {string|Date|null} value
 * @returns {string}
 */
export function formatDate(
    value
) {
    if (! value) {
        return '';
    }

    const date =
        presentationDate(
            value
        );

    if (! date) {
        return String(value);
    }

    const day =
        String(
            date.getDate()
        ).padStart(
            2,
            '0'
        );

    const month =
        String(
            date.getMonth() + 1
        ).padStart(
            2,
            '0'
        );

    const year =
        date.getFullYear();

    const locale =
        String(
            presentationConfiguration.browser_locale
            || 'en-GB'
        ).toLowerCase();

    /*
     * Patrimoine date presentation standard:
     *
     * French:  DD-MM-YYYY
     * English: DD/MM/YYYY
     *
     * This applies to normal business dates only.
     * Activity Log timestamps retain their dedicated
     * locale-aware date-and-time representation.
     */
    return locale.startsWith('fr')
        ? `${day}-${month}-${year}`
        : `${day}/${month}/${year}`;
}

/**
 * Format a date using the long application date style.
 *
 * Used for presentation such as the Dashboard heading.
 *
 * @param {string|Date|null} value
 * @returns {string}
 */
export function formatLongDate(
    value
) {
    if (! value) {
        return '';
    }

    const date =
        value instanceof Date
            ? value
            : presentationDate(
                value
            );

    if (
        ! date
        || Number.isNaN(
            date.getTime()
        )
    ) {
        return String(value);
    }

    const formattedDate =
        new Intl.DateTimeFormat(
            presentationConfiguration.browser_locale
            || 'en-GB',
            {
                weekday:
                    'long',

                day:
                    'numeric',

                month:
                    'long',

                year:
                    'numeric',
            }
        ).format(
            date
        );

    return formattedDate.replace(
        /(^|\s)(\p{L})/gu,
        (match) => match.toLocaleUpperCase(
            presentationConfiguration.browser_locale
            || 'en-GB'
        )
    );
}

/**
 * Generate up to two initials from a person's display name.
 *
 * @param {string|null} name
 * @returns {string}
 */
export function initials(name) {
    const normalizedName =
        String(
            name
            || 'Property Manager'
        ).trim();

    if (normalizedName === '') {
        return 'PM';
    }

    return normalizedName
        .split(/\s+/)
        .slice(0, 2)
        .map(
            (part) =>
                part
                    .charAt(0)
                    .toUpperCase()
        )
        .join('');
}

/**
 * Escape arbitrary content before inserting it into generated HTML.
 *
 * @param {*} value
 * @returns {string}
 */
export function escapeHtml(
    value
) {
    const element =
        document.createElement(
            'div'
        );

    element.textContent =
        String(
            value ?? ''
        );

    return element.innerHTML;
}

/**
 * Replace an element's text when the element exists.
 *
 * @param {string} id
 * @param {*} value
 */
export function setText(
    id,
    value
) {
    const element =
        document.getElementById(
            id
        );

    if (element) {
        element.textContent =
            String(
                value ?? ''
            );
    }
}

/*
|--------------------------------------------------------------------------
| Form Helpers
|--------------------------------------------------------------------------
*/

/**
 * Return a trimmed string value from a form element.
 *
 * Missing elements resolve to an empty string.
 *
 * @param {string} id
 * @returns {string}
 */
export function formValue(id) {
    return String(
        document
            .getElementById(id)
            ?.value
        || ''
    ).trim();
}

/**
 * Return a trimmed form value, or null when the field is empty.
 *
 * This maps optional browser fields cleanly to Laravel's nullable API
 * request fields.
 *
 * @param {string} id
 * @returns {string|null}
 */
export function nullableFormValue(
    id
) {
    const value =
        formValue(id);

    return value === ''
        ? null
        : value;
}

/* --------------------------------------------------------------------------
   Drawer lifecycle (v1.0.6)
   --------------------------------------------------------------------------

   The single shared implementation of the right-side drawer open/close
   state machine. Page modules must use these helpers instead of
   re-implementing the pm-drawer-active / pm-drawer-open / pm-drawer-closing
   sequence locally.

   Lifecycle:
     open  — clear stale classes, unhide, activate, then (double rAF so the
             browser has painted the off-screen position) slide in.
     close — slide out, then after the CSS transition finishes deactivate
             and re-hide the element.

   The close timer per drawer is tracked in a WeakMap so an open during an
   in-flight close cancels it cleanly.
   -------------------------------------------------------------------------- */

/** Matches the .pm-drawer-panel transition duration in app.css (800ms). */
const DRAWER_TRANSITION_MS = 820;

/** In-flight close timers, keyed by drawer element. */
const drawerCloseTimers = new WeakMap();

/**
 * Resolve a drawer reference (element or element id) to an element.
 *
 * @param {HTMLElement|string} drawer
 * @returns {HTMLElement|null}
 */
function resolveDrawer(drawer) {
    return typeof drawer === 'string'
        ? document.getElementById(drawer)
        : drawer;
}

/**
 * Open a right-side drawer.
 *
 * @param {HTMLElement|string} drawer element or id
 */
export function openDrawer(drawer) {
    const element = resolveDrawer(drawer);

    if (! element) {
        return;
    }

    // Cancel an in-flight close so reopening is always clean.
    const pendingClose = drawerCloseTimers.get(element);

    if (pendingClose) {
        window.clearTimeout(pendingClose);
        drawerCloseTimers.delete(element);
    }

    element.classList.remove('pm-drawer-open', 'pm-drawer-closing');
    element.removeAttribute('hidden');
    element.classList.add('pm-drawer-active');
    element.setAttribute('aria-hidden', 'false');

    // Keep the page behind the drawer from scrolling.
    document.body.classList.add('overflow-hidden');

    // Two frames: first paints the off-screen position, second transitions.
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            element.classList.add('pm-drawer-open');
        });
    });
}

/**
 * Close a right-side drawer.
 *
 * @param {HTMLElement|string} drawer element or id
 * @param {{onClosed?: () => void}} [options] callback after the slide-out
 */
export function closeDrawer(drawer, options = {}) {
    const element = resolveDrawer(drawer);

    if (! element || ! element.classList.contains('pm-drawer-active')) {
        return;
    }

    element.classList.remove('pm-drawer-open');
    element.classList.add('pm-drawer-closing');
    element.setAttribute('aria-hidden', 'true');

    const finish = () => {
        drawerCloseTimers.delete(element);

        element.classList.remove('pm-drawer-active', 'pm-drawer-closing');
        element.setAttribute('hidden', '');

        // Only release page scroll once no other drawer remains open.
        if (! document.querySelector('.pm-drawer.pm-drawer-active:not(.pm-drawer-closing)')) {
            document.body.classList.remove('overflow-hidden');
        }

        if (typeof options.onClosed === 'function') {
            options.onClosed();
        }
    };

    drawerCloseTimers.set(
        element,
        window.setTimeout(finish, DRAWER_TRANSITION_MS)
    );
}

/**
 * Wire a drawer's standard interactions in one call.
 *
 * Openers and closers are element ids (missing ids are ignored so pages
 * can share one wiring path across role-dependent markup). The backdrop
 * click and the Escape key also close the drawer.
 *
 * @param {HTMLElement|string} drawer element or id
 * @param {{
 *   openers?: string[],
 *   closers?: string[],
 *   onOpen?: () => void,
 *   onClose?: () => void,
 * }} [options]
 */
export function wireDrawer(drawer, options = {}) {
    const element = resolveDrawer(drawer);

    if (! element) {
        return;
    }

    const open = () => {
        openDrawer(element);

        if (typeof options.onOpen === 'function') {
            options.onOpen();
        }
    };

    const close = () => {
        closeDrawer(element, { onClosed: options.onClose });
    };

    for (const id of options.openers ?? []) {
        document.getElementById(id)?.addEventListener('click', open);
    }

    for (const id of options.closers ?? []) {
        document.getElementById(id)?.addEventListener('click', close);
    }

    // A click on the translucent backdrop dismisses the drawer.
    element.querySelector('.pm-drawer-backdrop')
        ?.addEventListener('click', close);

    // Escape dismisses the drawer while it is open.
    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape'
            && element.classList.contains('pm-drawer-active')
            && ! element.classList.contains('pm-drawer-closing')
        ) {
            close();
        }
    });

    return { open, close };
}
