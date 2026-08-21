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
    closeDrawer,
    escapeHtml,
    formatCurrency,
    formatLongDate,
    formatNumber,
    getPresentationConfiguration,
    initials,
    openDrawer,
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

    /*
     * Load derived notifications in the background so the consolidated
     * unread badge (release announcement OR any danger notification) is
     * correct before the bell panel is first opened.
     */
    refreshNotifications();

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
| V1.0.7 Notification Center
|--------------------------------------------------------------------------
|
| The bell shows derived operational notifications from
| GET /api/notifications. Rows deep-link to the page where the situation
| is handled. The release announcement keeps its dedicated read-state
| flow (POST /api/auth/release-notification/read).
|
*/

/** Last payload returned by GET /api/notifications. */
let notificationsPayload =
    null;

/** Deep link per notification kind. */
const NOTIFICATION_LINKS = {
    rent_overdue:
        '/dashboard',

    rent_due_soon:
        '/dashboard',

    leases_expiring:
        '/leases',

    increments_upcoming:
        '/leases',
};

/** Severity → token-colored indicator dot. */
const NOTIFICATION_SEVERITY_DOTS = {
    danger:
        'bg-[var(--pm-danger-text)]',

    warning:
        'bg-[var(--pm-warning-text)]',

    info:
        'bg-[var(--pm-info-text)]',
};

/**
 * Reflect the authenticated user's persisted release acknowledgement state
 * in the notification icon.
 */
function renderReleaseNotificationState(
    user
) {
    /*
     * Until the notification payload has loaded, the release read-state
     * from /api/auth/me is the only unread signal available.
     */
    if (notificationsPayload) {
        renderNotificationBadge();

        return;
    }

    setNotificationBadgeVisible(
        Boolean(
            user
                ?.has_unread_release_notification
        )
    );
}

function setNotificationBadgeVisible(
    unread
) {
    const badge =
        document.getElementById(
            'notification-unread-badge'
        );

    if (! badge) {
        return;
    }

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
 * The bell is marked unread when the release announcement is unread OR
 * any danger-severity notification is present.
 */
function renderNotificationBadge() {
    const notifications =
        notificationsPayload
            ?.notifications
        || [];

    const release =
        notifications.find(
            (notification) =>
                notification.kind
                === 'release_notes'
        );

    const unread =
        Boolean(release?.unread)
        || notifications.some(
            (notification) =>
                notification.severity
                === 'danger'
        );

    setNotificationBadgeVisible(
        unread
    );
}

/**
 * Load current notifications and render the bell panel.
 *
 * Called during shell initialization (so the unread badge is correct
 * without opening the panel) and every time the panel opens.
 */
async function refreshNotifications() {
    const list =
        document.getElementById(
            'notification-list'
        );

    if (! list) {
        return;
    }

    try {
        const response =
            await apiRequest(
                '/api/notifications'
            );

        notificationsPayload =
            await parseJsonResponse(
                response
            );

        renderNotificationList();
        renderNotificationBadge();
    } catch {
        list.innerHTML = `
            <div
                class="
                    px-3 py-6 text-center
                    text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'notifications.unable_load'
                    )
                )}
            </div>
        `;
    }
}

/**
 * Localized single-line body for one notification.
 */
function notificationBody(
    notification
) {
    if (
        notification.kind
        === 'release_notes'
    ) {
        return translate(
            'notifications.release_notes_body'
        );
    }

    const count =
        Number(
            notification.count
            || 0
        );

    const key =
        count === 1
            ? `notifications.${notification.kind}_body_one`
            : `notifications.${notification.kind}_body`;

    return translate(
        key,
        {
            count:
                formatNumber(
                    count
                ),

            amount:
                formatCurrency(
                    notification.amount
                    || 0
                ),
        }
    );
}

/**
 * Localized title for one notification.
 */
function notificationTitle(
    notification
) {
    if (
        notification.kind
        === 'release_notes'
    ) {
        return translate(
            'notifications.release_notes_title',
            {
                release:
                    String(
                        notification.release
                        || ''
                    ),
            }
        );
    }

    return translate(
        `notifications.${notification.kind}_title`
    );
}

function notificationRowMarkup(
    notification
) {
    const dot =
        NOTIFICATION_SEVERITY_DOTS[
            notification.severity
        ]
        || NOTIFICATION_SEVERITY_DOTS.info;

    const title =
        escapeHtml(
            notificationTitle(
                notification
            )
        );

    const body =
        escapeHtml(
            notificationBody(
                notification
            )
        );

    const content = `
        <span
            class="
                mt-1 h-2.5 w-2.5 shrink-0
                rounded-full
                ${dot}
            "
            aria-hidden="true"
        ></span>

        <span class="min-w-0 flex-1">
            <span
                class="
                    block text-sm font-medium
                    text-[var(--pm-text)]
                "
            >
                ${title}
            </span>

            <span
                class="
                    mt-0.5 block text-xs
                    text-[var(--pm-text-muted)]
                "
            >
                ${body}
            </span>
        </span>
    `;

    if (
        notification.kind
        === 'release_notes'
    ) {
        /*
         * The release row toggles the in-panel release details and
         * acknowledges the announcement instead of navigating away.
         */
        return `
            <button
                type="button"
                data-notification-release
                class="
                    flex w-full items-start gap-3
                    rounded-lg px-3 py-3
                    text-left transition
                    hover:bg-[var(--pm-hover)]
                "
            >
                ${content}
            </button>
        `;
    }

    const href =
        NOTIFICATION_LINKS[
            notification.kind
        ]
        || '/dashboard';

    return `
        <a
            href="${href}"
            class="
                flex items-start gap-3
                rounded-lg px-3 py-3
                transition
                hover:bg-[var(--pm-hover)]
            "
        >
            ${content}
        </a>
    `;
}

function renderNotificationList() {
    const list =
        document.getElementById(
            'notification-list'
        );

    if (! list) {
        return;
    }

    const notifications =
        notificationsPayload
            ?.notifications
        || [];

    if (notifications.length === 0) {
        list.innerHTML = `
            <div
                class="
                    px-3 py-6 text-center
                    text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'notifications.empty'
                    )
                )}
            </div>
        `;

        return;
    }

    list.innerHTML =
        notifications
            .map(
                notificationRowMarkup
            )
            .join('');
}

/**
 * Toggle the in-panel release details and acknowledge the announcement.
 */
function openReleaseNotificationDetails() {
    const panel =
        document.getElementById(
            'notification-release-panel'
        );

    panel?.classList.toggle(
        'hidden'
    );

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

    /*
     * Mirror the acknowledged state into the loaded notification payload
     * so the consolidated badge rule stays accurate without refetching.
     */
    if (notificationsPayload) {
        notificationsPayload = {
            ...notificationsPayload,

            notifications:
                (
                    notificationsPayload
                        .notifications
                    || []
                ).map(
                    (notification) =>
                        notification.kind
                        === 'release_notes'
                            ? {
                                ...notification,

                                unread:
                                    Boolean(
                                        state
                                            .has_unread_release_notification
                                    ),
                            }
                            : notification
                ),
        };

        renderNotificationList();
    }

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
 * V1.0.7 structured names: derive given names + surname from a legacy
 * display name when the API payload does not carry the structured fields.
 *
 * The last word becomes the surname; everything before it the given names.
 *
 * @param {string|null} name
 * @returns {{given_names: string, surname: string}}
 */
function splitDisplayName(name) {
    const parts =
        String(
            name
            || ''
        )
            .trim()
            .split(/\s+/)
            .filter(Boolean);

    if (parts.length === 0) {
        return {
            given_names: '',
            surname: '',
        };
    }

    if (parts.length === 1) {
        return {
            given_names: '',
            surname: parts[0],
        };
    }

    return {
        given_names:
            parts
                .slice(0, -1)
                .join(' '),

        surname:
            parts[
                parts.length - 1
            ],
    };
}

/**
 * Resolve the structured name for a user payload, falling back to
 * splitting the composed display name.
 *
 * @param {object} user
 * @returns {{given_names: string, surname: string}}
 */
function structuredNameFor(user) {
    const givenNames =
        String(
            user?.given_names
            ?? ''
        ).trim();

    const surname =
        String(
            user?.surname
            ?? ''
        ).trim();

    if (
        givenNames !== ''
        || surname !== ''
    ) {
        return {
            given_names: givenNames,
            surname,
        };
    }

    return splitDisplayName(
        user?.name
    );
}

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

    const givenNames =
        document.getElementById(
            'profile-given-names'
        );

    const surname =
        document.getElementById(
            'profile-surname'
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

    const structuredName =
        structuredNameFor(
            user
        );

    if (givenNames) {
        givenNames.value =
            structuredName.given_names;
    }

    if (surname) {
        surname.value =
            structuredName.surname;
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

    openDrawer(
        modal
    );
}


/**
 * Close the My Profile drawer using the shared drawer transition.
 */
function closeProfileModal() {
    closeDrawer(
        'profile-modal'
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

    const givenNames =
        document.getElementById(
            'profile-given-names'
        );

    const surname =
        document.getElementById(
            'profile-surname'
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
        ! givenNames
        || ! surname
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

        /*
         * V1.0.7 structured names: PATCH /api/auth/me accepts
         * given_names + surname and recomposes the display name.
         */
        const payload = {
            given_names:
                givenNames.value
                    .trim()
                    || null,

            surname:
                surname.value
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

        /*
         * Collapse the release details so the next open starts from the
         * notification list again.
         */
        document
            .getElementById(
                'notification-release-panel'
            )
            ?.classList.add(
                'hidden'
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
             * Refresh the derived notifications every time the panel
             * opens so it always reflects the current operational state.
             */
            if (opening) {
                refreshNotifications();
            }
        }
    );

    /*
     * The release row is re-rendered on every refresh; delegate its click
     * through the stable list container.
     */
    document
        .getElementById(
            'notification-list'
        )
        ?.addEventListener(
            'click',
            (event) => {
                const releaseRow =
                    event.target.closest(
                        '[data-notification-release]'
                    );

                if (releaseRow) {
                    openReleaseNotificationDetails();
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
