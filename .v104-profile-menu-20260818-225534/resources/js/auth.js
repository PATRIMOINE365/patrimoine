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
    formatLongDate,
    getPresentationConfiguration,
    initials,
    parseJsonResponse,
    saveToken,
    token,
    translate,
} from './core.js';

import { initializeBrowserAuthorization } from './permissions.js';

import {
    getThemePreference,
    setThemePreference,
} from './theme.js';

const USER_ROLE_STORAGE_KEY =
    'patrimoine.user_role';

function saveUserRole(role) {
    const normalizedRole =
        String(
            role
            || ''
        );

    if (
        ! [
            'administrator',
            'property_manager',
            'viewer',
        ].includes(
            normalizedRole
        )
    ) {
        return;
    }

    try {
        window.sessionStorage.setItem(
            USER_ROLE_STORAGE_KEY,
            normalizedRole
        );
    } catch {
        // Storage restrictions do not affect authentication.
    }

    document.documentElement.dataset.shellRole =
        normalizedRole;
}

function clearUserRole() {
    try {
        window.sessionStorage.removeItem(
            USER_ROLE_STORAGE_KEY
        );
    } catch {
        // Storage restrictions do not affect logout.
    }

    delete document.documentElement.dataset.shellRole;
}

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
                translate(
                    'login.signing_in'
                );

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
                        translate(
                            'login.missing_api_token'
                        )
                    );
                }

                saveToken(
                    data.access_token
                );

                saveUserRole(
                    data.user?.role
                );

                window.location.replace(
                    '/dashboard'
                );
            } catch (error) {
                errorBox.textContent =
                    error instanceof Error
                        ? error.message
                        : translate(
                            'login.unable_to_sign_in'
                        );

                errorBox.classList.remove(
                    'hidden'
                );
            } finally {
                button.disabled =
                    false;

                button.textContent =
                    translate(
                        'login.sign_in'
                    );
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

        saveUserRole(
            user.role
        );

        /*
         * Store the authenticated application role for page modules and
         * reveal only navigation/actions explicitly assigned to that role.
         *
         * Server-side capability middleware remains authoritative.
         */
        document.body.dataset.currentUserRole =
            String(
                user.role
                || ''
            );

        /*
         * Activity H applies the fixed browser capability matrix after the
         * authenticated User has been resolved.
         *
         * Server-side API authorization remains authoritative.
         */
        if (
            ! initializeBrowserAuthorization(
                user
            )
        ) {
            return false;
        }
    } catch {
        return false;
    }

    initializeSidebar();
    initializeLogout();
    initializeShellControls();

    await loadManagingOrganisation();

    return true;
}

/**
 * Render the authenticated user's name, role, and initials.
 *
 * @param {object} user
 */
function renderCurrentUser(user) {
    /*
     * Cache only non-sensitive shell presentation identity.
     *
     * The authoritative user is still /api/auth/me. This cache exists
     * solely to prevent generic placeholder identity from flashing during
     * the next full-document navigation or refresh.
     */
    try {
        window.sessionStorage.setItem(
            'patrimoine.current_user',
            JSON.stringify({
                name:
                    String(
                        user.name
                        || ''
                    ),

                role:
                    String(
                        user.role
                        || ''
                    ),
            })
        );
    } catch {
        /*
         * Storage restrictions must never prevent authentication or shell
         * rendering.
         */
    }

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
            || translate(
                'user.property_manager'
            );
    }

    if (roleElement) {
        const role =
            String(
                user.role
                || 'property_manager'
            );

        roleElement.textContent =
            translate(
                `roles.${role}`
            );
    }

    if (avatarElement) {
        avatarElement.textContent =
            initials(
                user.name
            );
    }

    const topbarAvatar =
        document.getElementById(
            'topbar-avatar'
        );

    const topbarName =
        document.getElementById(
            'topbar-user-name'
        );

    const topbarRole =
        document.getElementById(
            'topbar-user-role'
        );

    const menuName =
        document.getElementById(
            'user-menu-name'
        );

    const menuRole =
        document.getElementById(
            'user-menu-role'
        );

    const displayName =
        user.name
        || translate(
            'user.property_manager'
        );

    const role =
        String(
            user.role
            || 'property_manager'
        );

    const translatedRole =
        translate(
            `roles.${role}`
        );

    if (topbarAvatar) {
        topbarAvatar.textContent =
            initials(
                displayName
            );
    }

    if (topbarName) {
        topbarName.textContent =
            displayName;
    }

    if (topbarRole) {
        topbarRole.textContent =
            translatedRole;
    }

    if (menuName) {
        menuName.textContent =
            displayName;
    }

    if (menuRole) {
        menuRole.textContent =
            translatedRole;
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
| V1.0.4 Application Shell Controls
|--------------------------------------------------------------------------
*/

function initializeShellControls() {
    const dateElement =
        document.getElementById(
            'shell-current-date'
        );

    if (dateElement) {
        dateElement.textContent =
            formatLongDate(
                new Date()
            );
    }

    const refreshButton =
        document.getElementById(
            'shell-refresh'
        );

    refreshButton?.addEventListener(
        'click',
        () => {
            window.location.reload();
        }
    );

    const userToggle =
        document.getElementById(
            'user-menu-toggle'
        );

    const userMenu =
        document.getElementById(
            'user-menu'
        );

    const manageToggle =
        document.getElementById(
            'sidebar-manage-toggle'
        );

    const manageMenu =
        document.getElementById(
            'sidebar-manage-menu'
        );

    const notificationToggle =
        document.getElementById(
            'notification-menu-toggle'
        );

    const notificationMenu =
        document.getElementById(
            'notification-menu'
        );

    const closeUserMenu = () => {
        userMenu?.classList.add(
            'hidden'
        );

        userToggle?.setAttribute(
            'aria-expanded',
            'false'
        );
    };

    const closeManageMenu = () => {
        manageMenu?.classList.add(
            'hidden'
        );

        manageToggle?.setAttribute(
            'aria-expanded',
            'false'
        );
    };

    const closeNotificationMenu = () => {
        notificationMenu?.classList.add(
            'hidden'
        );

        notificationToggle?.setAttribute(
            'aria-expanded',
            'false'
        );
    };

    manageToggle?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();

            closeUserMenu();
            closeNotificationMenu();

            const opening =
                manageMenu?.classList.contains(
                    'hidden'
                );

            manageMenu?.classList.toggle(
                'hidden'
            );

            manageToggle.setAttribute(
                'aria-expanded',
                opening
                    ? 'true'
                    : 'false'
            );
        }
    );

    manageMenu?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();
        }
    );

    userToggle?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();

            closeNotificationMenu();

            const opening =
                userMenu?.classList.contains(
                    'hidden'
                );

            userMenu?.classList.toggle(
                'hidden'
            );

            userToggle.setAttribute(
                'aria-expanded',
                opening
                    ? 'true'
                    : 'false'
            );
        }
    );

    notificationToggle?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();

            closeUserMenu();

            const opening =
                notificationMenu?.classList.contains(
                    'hidden'
                );

            notificationMenu?.classList.toggle(
                'hidden'
            );

            notificationToggle.setAttribute(
                'aria-expanded',
                opening
                    ? 'true'
                    : 'false'
            );
        }
    );

    userMenu?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();
        }
    );

    notificationMenu?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();
        }
    );

    document.addEventListener(
        'click',
        () => {
            closeUserMenu();
            closeNotificationMenu();
            closeManageMenu();
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key === 'Escape') {
                closeUserMenu();
                closeNotificationMenu();
                closeManageMenu();
            }
        }
    );

    const syncThemeOptions = () => {
        const preference =
            getThemePreference();

        document
            .querySelectorAll(
                '[data-theme-option]'
            )
            .forEach(
                (button) => {
                    button.dataset.selected =
                        button.dataset.themeOption
                        === preference
                            ? 'true'
                            : 'false';
                }
            );
    };

    document
        .querySelectorAll(
            '[data-theme-option]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        setThemePreference(
                            button.dataset.themeOption
                        );

                        syncThemeOptions();
                    }
                );
            }
        );

    document.addEventListener(
        'patrimoine:theme-changed',
        syncThemeOptions
    );

    syncThemeOptions();
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
                clearUserRole();

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

    /*
     * Presentation configuration is already loaded before authentication.
     * It includes only non-sensitive display metadata and therefore avoids
     * calling the Administrator-only Settings endpoint.
     */
    const configuration =
        getPresentationConfiguration();

    element.textContent =
        configuration
            .organisation_name
        || 'Patrimoine';
}
