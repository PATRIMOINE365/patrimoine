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
            'Your session has expired. Please sign in again.'
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
            || 'The request could not be completed.';

        throw new Error(
            message
        );
    }

    return data;
}

/*
|--------------------------------------------------------------------------
| Display Helpers
|--------------------------------------------------------------------------
*/

/**
 * Format an integer amount as Ghanaian Cedis.
 *
 * Patrimoine stores monetary amounts as whole-number values in V1.
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

    return new Intl.NumberFormat(
        'en-GH',
        {
            style: 'currency',
            currency: 'GHS',
            maximumFractionDigits: 0,
        }
    ).format(
        amount
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
