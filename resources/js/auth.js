/*
|--------------------------------------------------------------------------
| Patrimoine Authentication and Application Shell
|--------------------------------------------------------------------------
|
| This module manages browser authentication and the authenticated
| application shell.
|
| Responsibilities:
|
| - login;
| - logout;
| - current-user validation;
| - sidebar user identity;
| - mobile sidebar behaviour;
| - managing organisation identity in the top bar.
|
*/

import {
    apiRequest,
    clearToken,
    initials,
    parseJsonResponse,
    saveToken,
    token,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

/**
 * Initialize the login page when present.
 *
 * Returns true when the current document is the login page. The application
 * bootstrap uses this result to avoid initializing protected modules on
 * the login screen.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeLogin() {
    const form =
        document.getElementById(
            'login-form'
        );

    if (! form) {
        return false;
    }

    /*
     * Verify an existing token before showing the login page again.
     */
    if (token()) {
        try {
            const response =
                await apiRequest(
                    '/api/auth/me'
                );

            if (response.ok) {
                window.location.replace(
                    '/dashboard'
                );

                return true;
            }
        } catch {
            clearToken();
        }
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const button =
                document.getElementById(
                    'login-button'
                );

            const errorBox =
                document.getElementById(
                    'login-error'
                );

            const emailInput =
                document.getElementById(
                    'email'
                );

            const passwordInput =
                document.getElementById(
                    'password'
                );

            if (
                ! button
                || ! errorBox
                || ! emailInput
                || ! passwordInput
            ) {
                return;
            }

            errorBox.classList.add(
                'hidden'
            );

            errorBox.textContent =
                '';

            button.disabled =
                true;

            button.textContent =
                'Signing in…';

            try {
                /*
                 * Login itself cannot use apiRequest() because no token
                 * exists yet.
                 */
                const response =
                    await fetch(
                        '/api/auth/login',
                        {
                            method:
                                'POST',

                            headers: {
                                Accept:
                                    'application/json',

                                'Content-Type':
                                    'application/json',
                            },

                            body:
                                JSON.stringify({
                                    email:
                                        emailInput
                                            .value
                                            .trim(),

                                    password:
                                        passwordInput
                                            .value,

                                    device_name:
                                        'patrimoine-web',
                                }),
                        }
                    );

                const data =
                    await parseJsonResponse(
                        response
                    );

                /*
                 * AuthController returns:
                 *
                 * {
                 *     "token_type": "Bearer",
                 *     "access_token": "...",
                 *     "user": {...}
                 * }
                 */
                if (
                    typeof data.access_token
                    !== 'string'
                    || data
                        .access_token
                        .trim()
                    === ''
                ) {
                    throw new Error(
                        'Authentication succeeded but no API token was returned.'
                    );
                }

                saveToken(
                    data.access_token
                );

                window.location.replace(
                    '/dashboard'
                );
            } catch (error) {
                errorBox.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Unable to sign in.';

                errorBox.classList.remove(
                    'hidden'
                );
            } finally {
                button.disabled =
                    false;

                button.textContent =
                    'Sign in';
            }
        }
    );

    return true;
}

/*
|--------------------------------------------------------------------------
| Authenticated Application Shell
|--------------------------------------------------------------------------
*/

/**
 * Validate the current browser session and initialize the authenticated
 * application shell.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeAuthenticatedShell() {
    if (
        ! document.body.dataset
            .authRequired
    ) {
        return false;
    }

    if (! token()) {
        window.location.replace(
            '/login'
        );

        return false;
    }

    try {
        const response =
            await apiRequest(
                '/api/auth/me'
            );

        const user =
            await parseJsonResponse(
                response
            );

        renderCurrentUser(
            user
        );
    } catch {
        return false;
    }

    initializeSidebar();
    initializeLogout();

    await loadManagingOrganisation();

    return true;
}

/**
 * Render the authenticated user's name, role, and initials.
 *
 * @param {object} user
 */
function renderCurrentUser(user) {
    const nameElement =
        document.getElementById(
            'sidebar-user-name'
        );

    const roleElement =
        document.getElementById(
            'sidebar-user-role'
        );

    const avatarElement =
        document.getElementById(
            'sidebar-avatar'
        );

    if (nameElement) {
        nameElement.textContent =
            user.name
            || 'Property Manager';
    }

    if (roleElement) {
        roleElement.textContent =
            String(
                user.role
                || 'property_manager'
            )
                .replaceAll(
                    '_',
                    ' '
                )
                .replace(
                    /\b\w/g,
                    (character) =>
                        character
                            .toUpperCase()
                );
    }

    if (avatarElement) {
        avatarElement.textContent =
            initials(
                user.name
            );
    }
}

/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/

/**
 * Initialize responsive sidebar interactions.
 */
function initializeSidebar() {
    const sidebar =
        document.getElementById(
            'sidebar'
        );

    const overlay =
        document.getElementById(
            'sidebar-overlay'
        );

    const openButton =
        document.getElementById(
            'sidebar-open'
        );

    const closeButton =
        document.getElementById(
            'sidebar-close'
        );

    if (
        ! sidebar
        || ! overlay
        || ! openButton
    ) {
        return;
    }

    const close = () => {
        sidebar.classList.add(
            '-translate-x-full'
        );

        overlay.classList.add(
            'hidden'
        );
    };

    const open = () => {
        sidebar.classList.remove(
            '-translate-x-full'
        );

        overlay.classList.remove(
            'hidden'
        );
    };

    openButton.addEventListener(
        'click',
        open
    );

    overlay.addEventListener(
        'click',
        close
    );

    closeButton?.addEventListener(
        'click',
        close
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
            ) {
                close();
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Sign Out button.
 */
function initializeLogout() {
    const button =
        document.getElementById(
            'logout-button'
        );

    if (! button) {
        return;
    }

    button.addEventListener(
        'click',
        async () => {
            if (button.disabled) {
                return;
            }

            button.disabled =
                true;

            try {
                await apiRequest(
                    '/api/auth/logout',
                    {
                        method:
                            'POST',
                    }
                );
            } catch {
                /*
                 * Local logout must still complete if the server token
                 * has already expired or was already revoked.
                 */
            } finally {
                clearToken();

                window.location.replace(
                    '/login'
                );
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Managing Organisation Identity
|--------------------------------------------------------------------------
*/

/**
 * Load the application-wide managing organisation identity.
 *
 * Fresh installations legitimately have no configured organisation, in
 * which case the Patrimoine product name remains visible.
 */
async function loadManagingOrganisation() {
    const element =
        document.getElementById(
            'organisation-name'
        );

    if (! element) {
        return;
    }

    try {
        const response =
            await apiRequest(
                '/api/managing-organisation'
            );

        if (
            response.status === 404
        ) {
            element.textContent =
                'Patrimoine';

            return;
        }

        const organisation =
            await parseJsonResponse(
                response
            );

        element.textContent =
            organisation.legal_name
            || organisation.name
            || 'Patrimoine';
    } catch {
        element.textContent =
            'Patrimoine';
    }
}
