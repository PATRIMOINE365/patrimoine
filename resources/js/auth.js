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

let authenticatedShellUser =
    null;

let profileDrawerCloseTimer =
    null;


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

            const buttonLabel =
                button.querySelector(
                    '[data-i18n="login.sign_in"]'
                );

            button.disabled =
                true;

            button.dataset.busy =
                'true';

            if (buttonLabel) {
                buttonLabel.textContent =
                    translate(
                        'login.signing_in'
                    );
            }

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

                delete button.dataset.busy;

                if (buttonLabel) {
                    buttonLabel.textContent =
                        translate(
                            'login.sign_in'
                        );
                }
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

        authenticatedShellUser =
            user;

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
    initializeProfileControls();

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
     * V1.0.5 CASH RECEIVER UI NORMALIZATION
     *
     * Financial forms never permit a user to type/select a Cash Receiver.
     * The authenticated User resolved by /api/auth/me is the authoritative
     * receiver for normal Cash transactions.
     *
     * Expose only the resolved identity needed by the presentation layer.
     * Backend financial services remain authoritative.
     */
    document.body.dataset.currentUserId =
        user?.id != null
            ? String(user.id)
            : '';

    document.body.dataset.currentUserName =
        user?.name ?? '';

    if (
        typeof window.syncPatrimoineCashReceiverUi
        === 'function'
    ) {
        window.syncPatrimoineCashReceiverUi();
    }

    renderReleaseNotificationState(
        user
    );

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
                id:
                    Number(
                        user.id
                        || 0
                    ),

                name:
                    String(
                        user.name
                        || ''
                    ),

                email:
                    String(
                        user.email
                        || ''
                    ),

                phone:
                    String(
                        user.phone
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

    const menuEmail =
        document.getElementById(
            'user-menu-email'
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

    if (menuEmail) {
        menuEmail.textContent =
            String(
                user.email
                || ''
            );
    }

    if (menuRole) {
        menuRole.textContent =
            translatedRole;
    }
}




/*
|--------------------------------------------------------------------------
| Release Notification
|--------------------------------------------------------------------------
*/

/**
 * Reflect the authenticated user's persisted release acknowledgement state
 * in the notification icon.
 */
function renderReleaseNotificationState(
    user
) {
    const badge =
        document.getElementById(
            'notification-unread-badge'
        );

    if (! badge) {
        return;
    }

    const unread =
        Boolean(
            user
                ?.has_unread_release_notification
        );

    badge.classList.toggle(
        'hidden',
        ! unread
    );

    badge.setAttribute(
        'aria-hidden',
        unread
            ? 'false'
            : 'true'
    );
}


/**
 * Persist acknowledgement of the current release.
 *
 * The badge is removed only after the API confirms that the state has been
 * saved successfully. A failed request therefore leaves the notification
 * visibly unread and allows the user to try again.
 */
async function acknowledgeCurrentReleaseNotification() {
    if (
        ! authenticatedShellUser
        || ! authenticatedShellUser
            .has_unread_release_notification
    ) {
        return;
    }

    const response =
        await apiRequest(
            '/api/auth/release-notification/read',
            {
                method:
                    'POST',
            }
        );

    const state =
        await parseJsonResponse(
            response
        );

    authenticatedShellUser = {
        ...authenticatedShellUser,

        current_release:
            state.current_release,

        last_seen_release:
            state.last_seen_release,

        has_unread_release_notification:
            Boolean(
                state
                    .has_unread_release_notification
            ),
    };

    renderReleaseNotificationState(
        authenticatedShellUser
    );
}

/*
|--------------------------------------------------------------------------
| My Profile
|--------------------------------------------------------------------------
*/

/**
 * Populate and show the signed-in user's own profile drawer.
 */
function openProfileModal() {
    const modal =
        document.getElementById(
            'profile-modal'
        );

    const user =
        authenticatedShellUser;

    if (! modal || ! user) {
        return;
    }

    const name =
        document.getElementById(
            'profile-name'
        );

    const email =
        document.getElementById(
            'profile-email'
        );

    const phone =
        document.getElementById(
            'profile-phone'
        );

    const role =
        document.getElementById(
            'profile-role'
        );

    const status =
        document.getElementById(
            'profile-status'
        );

    if (name) {
        name.value =
            user.name ?? '';
    }

    if (email) {
        email.value =
            user.email ?? '';
    }

    if (phone) {
        phone.value =
            user.phone ?? '';
    }

    if (role) {
        role.value =
            translate(
                `roles.${String(
                    user.role
                    || 'property_manager'
                )}`
            );
    }

    if (status) {
        status.value =
            Boolean(
                user.is_active
            )
                ? translate(
                    'users.active'
                )
                : translate(
                    'users.inactive'
                );
    }

    const newPassword =
        document.getElementById(
            'profile-new-password'
        );

    const currentPassword =
        document.getElementById(
            'profile-current-password'
        );

    if (newPassword) {
        newPassword.value = '';
        newPassword.type = 'password';
    }

    if (currentPassword) {
        currentPassword.value = '';
        currentPassword.type = 'password';
    }

    hideProfileMessage();

    if (
        profileDrawerCloseTimer
        !== null
    ) {
        window.clearTimeout(
            profileDrawerCloseTimer
        );

        profileDrawerCloseTimer =
            null;
    }

    modal.classList.remove(
        'pm-drawer-open',
        'pm-drawer-closing'
    );

    modal.removeAttribute(
        'hidden'
    );

    modal.classList.add(
        'pm-drawer-active'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    const panel =
        modal.querySelector(
            '.pm-drawer-panel'
        );

    if (panel) {
        void panel
            .getBoundingClientRect();
    }

    window.requestAnimationFrame(
        () => {
            window.requestAnimationFrame(
                () => {
                    modal.classList.add(
                        'pm-drawer-open'
                    );
                }
            );
        }
    );
}


/**
 * Close the My Profile drawer using the shared drawer transition.
 */
function closeProfileModal() {
    const modal =
        document.getElementById(
            'profile-modal'
        );

    if (
        ! modal
        || ! modal.classList.contains(
            'pm-drawer-active'
        )
    ) {
        return;
    }

    modal.classList.remove(
        'pm-drawer-open'
    );

    modal.classList.add(
        'pm-drawer-closing'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    if (
        profileDrawerCloseTimer
        !== null
    ) {
        window.clearTimeout(
            profileDrawerCloseTimer
        );
    }

    profileDrawerCloseTimer =
        window.setTimeout(
            () => {
                modal.classList.remove(
                    'pm-drawer-active',
                    'pm-drawer-closing'
                );

                modal.setAttribute(
                    'hidden',
                    ''
                );

                document.body
                    .classList.remove(
                        'overflow-hidden'
                    );

                profileDrawerCloseTimer =
                    null;
            },
            850
        );
}


/**
 * Apply standard error/success presentation inside the profile drawer.
 */
function showProfileMessage(
    message,
    success = false
) {
    const box =
        document.getElementById(
            'profile-form-message'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden',
        'border-[var(--pm-danger-border)]',
        'bg-[var(--pm-danger-background)]',
        'text-[var(--pm-danger-text)]',
        'border-[var(--pm-success-border)]',
        'bg-[var(--pm-success-background)]',
        'text-[var(--pm-success-text)]'
    );

    box.classList.add(
        success
            ? 'border-[var(--pm-success-border)]'
            : 'border-[var(--pm-danger-border)]',
        success
            ? 'bg-[var(--pm-success-background)]'
            : 'bg-[var(--pm-danger-background)]',
        success
            ? 'text-[var(--pm-success-text)]'
            : 'text-[var(--pm-danger-text)]'
    );
}


function hideProfileMessage() {
    const box =
        document.getElementById(
            'profile-form-message'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}


/**
 * Register self-profile drawer actions once per authenticated document.
 */
function initializeProfileControls() {
    const form =
        document.getElementById(
            'profile-form'
        );

    if (! form) {
        return;
    }

    document
        .getElementById(
            'profile-modal-close'
        )
        ?.addEventListener(
            'click',
            closeProfileModal
        );

    document
        .getElementById(
            'profile-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeProfileModal
        );

    document
        .getElementById(
            'profile-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeProfileModal
        );

    document
        .querySelectorAll(
            '[data-password-toggle]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        const input =
                            document.getElementById(
                                button.dataset
                                    .passwordToggle
                            );

                        if (! input) {
                            return;
                        }

                        input.type =
                            input.type === 'password'
                                ? 'text'
                                : 'password';
                    }
                );
            }
        );

    form.addEventListener(
        'submit',
        submitProfileForm
    );
}


/**
 * Persist the current user's own non-security identity fields.
 */
async function submitProfileForm(
    event
) {
    event.preventDefault();

    const form =
        event.currentTarget;

    const button =
        document.getElementById(
            'profile-submit-button'
        );

    if (
        ! form
        || ! button
        || ! form.reportValidity()
    ) {
        return;
    }

    const name =
        document.getElementById(
            'profile-name'
        );

    const email =
        document.getElementById(
            'profile-email'
        );

    const phone =
        document.getElementById(
            'profile-phone'
        );

    const newPassword =
        document.getElementById(
            'profile-new-password'
        );

    const currentPassword =
        document.getElementById(
            'profile-current-password'
        );

    if (
        ! name
        || ! email
        || ! phone
        || ! newPassword
        || ! currentPassword
    ) {
        return;
    }

    /*
     * A password change begins only when the user enters a new password.
     *
     * Browsers/password managers may autofill the current-password field
     * when this drawer opens. That must not turn an ordinary profile edit
     * (for example changing the phone number) into a password-change
     * request.
     */
    const changingPassword =
        newPassword.value.trim() !== '';

    if (
        changingPassword
        && currentPassword.value === ''
    ) {
        showProfileMessage(
            translate(
                'password.profile_current_required'
            )
        );

        return;
    }

    try {
        hideProfileMessage();

        button.disabled =
            true;

        const payload = {
            name:
                name.value
                    .trim(),

            email:
                email.value
                    .trim(),

            phone:
                phone.value
                    .trim()
                    || null,
        };

        if (changingPassword) {
            payload.current_password =
                currentPassword.value;

            payload.password =
                newPassword.value;

            payload.password_confirmation =
                newPassword.value;
        }

        const response =
            await apiRequest(
                '/api/auth/me',
                {
                    method:
                        'PATCH',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        /*
         * Reload the authoritative authenticated-user representation after
         * updating the profile.
         *
         * This keeps every profile field, including phone and account status,
         * synchronized with /api/auth/me rather than relying on the PATCH
         * response shape.
         */
        const refreshedResponse =
            await apiRequest(
                '/api/auth/me'
            );

        const user =
            await parseJsonResponse(
                refreshedResponse
            );

        authenticatedShellUser =
            user;

        renderCurrentUser(
            user
        );

        /*
         * Keep the drawer open after saving, matching normal edit-drawer
         * behaviour while giving immediate visual confirmation.
         */
        if (changingPassword) {
            /*
             * Password changes invalidate the authenticated browser token.
             * Return to login so the user establishes a fresh session.
             */
            clearToken();
            clearUserRole();

            window.location.replace(
                '/login'
            );

            return;
        }

        showProfileMessage(
            translate(
                'password.profile_updated'
            ),
            true
        );
    } catch (error) {
        showProfileMessage(
            error instanceof Error
                ? error.message
                : (
                    document.documentElement.lang
                        .toLowerCase()
                        .startsWith('fr')
                        ? 'Impossible de mettre à jour le profil.'
                        : 'Unable to update profile.'
                )
        );
    } finally {
        button.disabled =
            false;
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

    const profileOpen =
        document.getElementById(
            'my-profile-open'
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

    profileOpen?.addEventListener(
        'click',
        (event) => {
            event.stopPropagation();

            closeUserMenu();

            openProfileModal();
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

            /*
             * Opening the notification panel counts as seeing the current
             * release announcement. Closing/reopening an already-read panel
             * performs no additional API request.
             */
            if (opening) {
                acknowledgeCurrentReleaseNotification()
                    .catch(
                        (error) => {
                            console.error(
                                'Unable to acknowledge release notification.',
                                error
                            );
                        }
                    );
            }
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


/*
|--------------------------------------------------------------------------
| V1.0.5 Cash Receiver UI normalization
|--------------------------------------------------------------------------
|
| Cash Receiver is not a free-form financial field.
|
| Cash:
|   - authenticated User is displayed;
|   - field is locked.
|
| Bank Transfer / Mobile Payment:
|   - Cash Receiver is hidden;
|   - no receiver value is submitted intentionally by the UI.
|
| The server remains authoritative and independently enforces these rules.
|
*/

function patrimoineCashReceiverInputs(root = document)
{
    return root.querySelectorAll([
        'input[name="collector_name"]',
        'input[id*="collector"]',
        'input[data-i18n-placeholder*="collector"]',
        'input[name="cash_receiver_name"]',
        'input[id*="cash-receiver"]',
        'input[id*="cash_receiver"]',
    ].join(','));
}

function patrimoinePaymentMethodForReceiver(input)
{
    const scopes = [
        input.closest('form'),
        input.closest('[role="dialog"]'),
        input.closest('[data-drawer]'),
        input.closest('.drawer'),
        input.closest('.fixed'),
        input.closest('.modal'),
        document,
    ].filter(Boolean);

    const selectors = [
        'select[name="payment_method"]',
        'select[id*="payment-method"]',
        'select[id*="payment_method"]',
    ];

    for (const scope of scopes) {
        for (const selector of selectors) {
            const element =
                scope.querySelector(selector);

            if (element) {
                return element;
            }
        }
    }

    return null;
}

function patrimoineCashReceiverFieldContainer(input)
{
    const label =
        input.id
            ? document.querySelector(
                `label[for="${CSS.escape(input.id)}"]`
            )
            : null;

    if (label) {
        let node =
            input.parentElement;

        while (
            node
            && node !== document.body
        ) {
            if (node.contains(label)) {
                return node;
            }

            node =
                node.parentElement;
        }
    }

    return (
        input.closest('[data-field]')
        || input.closest('.form-group')
        || input.closest('.space-y-2')
        || input.closest('.space-y-1')
        || input.parentElement
    );
}

function patrimoineNormalizeCashReceiverInput(input)
{
    const method =
        patrimoinePaymentMethodForReceiver(
            input
        );

    if (! method) {
        return;
    }

    const isCash =
        method.value === 'cash';

    const currentUserName =
        document.body.dataset.currentUserName
        ?? '';

    const container =
        patrimoineCashReceiverFieldContainer(
            input
        );

    /*
     * Never allow manual receiver entry.
     */
    input.readOnly =
        true;

    input.setAttribute(
        'aria-readonly',
        'true'
    );

    input.autocomplete =
        'off';

    if (isCash) {
        /*
         * Display the authenticated User resolved by auth.js.
         *
         * Do not disable the control because existing browser-side validation
         * may still inspect the value while Phase 3 completes. The backend
         * ignores collector_name and independently assigns the real User.
         */
        input.value =
            currentUserName;

        input.disabled =
            false;

        if (container) {
            container.hidden =
                false;

            container.classList.remove(
                'hidden'
            );
        }
    } else {
        input.value =
            '';

        /*
         * Electronic transactions have no Cash Receiver.
         */
        if (container) {
            container.hidden =
                true;

            container.classList.add(
                'hidden'
            );
        }
    }
}

window.syncPatrimoineCashReceiverUi =
    function syncPatrimoineCashReceiverUi()
    {
        patrimoineCashReceiverInputs()
            .forEach(
                patrimoineNormalizeCashReceiverInput
            );
    };

document.addEventListener(
    'change',
    (event) => {
        const target =
            event.target;

        if (
            ! (
                target
                instanceof HTMLSelectElement
            )
        ) {
            return;
        }

        if (
            target.name === 'payment_method'
            || target.id.includes(
                'payment-method'
            )
            || target.id.includes(
                'payment_method'
            )
        ) {
            window.syncPatrimoineCashReceiverUi();
        }
    }
);

document.addEventListener(
    'DOMContentLoaded',
    () => {
        window.syncPatrimoineCashReceiverUi();

        /*
         * V1.0.4 drawers may insert financial controls after initial page
         * load. Keep those dynamically-created controls normalized as well.
         */
        const observer =
            new MutationObserver(
                () => {
                    window.syncPatrimoineCashReceiverUi();
                }
            );

        observer.observe(
            document.body,
            {
                childList: true,
                subtree: true,
            }
        );
    }
);
