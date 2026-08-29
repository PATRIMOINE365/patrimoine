/*
|--------------------------------------------------------------------------
| Patrimoine User Management
|--------------------------------------------------------------------------
|
| Administrator-only browser workspace for V1.0.3 application Users.
|
| The API remains the authoritative authorization boundary. This module
| additionally keeps Administrator-only navigation and controls out of the
| normal Property Manager / Viewer browser experience.
|
*/

import {
    apiRequest,
    escapeHtml,
    formValue,
    nullableFormValue,
    parseJsonResponse,
    translate,
    requireDangerConfirmation,
} from './core.js';

import {
    pageSizeFor,
    renderPagination,
} from './pagination.js';

import {
    applyPhoneValue,
    readPhoneValue,
} from './phone-input.js';

import { avatarMarkup } from './avatar.js';

let userSearchTimer =
    null;

let loadedUsersById =
    new Map();

let currentUser =
    null;

let userFormMode =
    'create';

let editingUserId =
    null;

/**
 * Initialize User Management only on its dedicated page.
 */
let userDrawerCloseTimer = null;

/**
 * V1.0.7 structured names: derive given names + surname from a legacy
 * display name when the API payload does not carry the structured fields.
 *
 * The last word becomes the surname; everything before it the given names.
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

    const parts =
        String(
            user?.name
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

export async function initializeUsers() {
    const workspace =
        document.getElementById(
            'users-workspace'
        );

    if (! workspace) {
        return;
    }

    /*
     * initializeAuthenticatedShell() runs before this module and stores the
     * current role on <body>. Direct navigation by a non-Administrator is
     * redirected rather than displaying unusable administrative controls.
     */
    if (
        document.body.dataset
            .currentUserRole
        !== 'administrator'
    ) {
        window.location.replace(
            '/dashboard'
        );

        return;
    }

    initializeUserFilters();
    initializeUserForm();

    try {
        const meResponse =
            await apiRequest(
                '/api/auth/me'
            );

        currentUser =
            await parseJsonResponse(
                meResponse
            );

        await loadUsers();
    } catch (error) {
        showUsersError(
            error instanceof Error
                ? error.message
                : translate(
                    'users.unable_load'
                )
        );
    }
}

/*
|--------------------------------------------------------------------------
| Directory
|--------------------------------------------------------------------------
*/

function initializeUserFilters() {
    const search =
        document.getElementById(
            'users-search'
        );

    search?.addEventListener(
        'input',
        () => {
            clearTimeout(
                userSearchTimer
            );

            userSearchTimer =
                setTimeout(
                    () => loadUsers(1),
                    300
                );
        }
    );

    document
        .getElementById(
            'users-role-filter'
        )
        ?.addEventListener(
            'change',
            () => loadUsers(1)
        );

    document
        .getElementById(
            'users-status-filter'
        )
        ?.addEventListener(
            'change',
            () => loadUsers(1)
        );
}

function userQueryParameters(
    page = 1
) {
    const parameters =
        new URLSearchParams();

    parameters.set(
        'page',
        String(page)
    );

    parameters.set(
        'per_page',
        String(pageSizeFor('users'))
    );

    const search =
        formValue(
            'users-search'
        );

    const role =
        formValue(
            'users-role-filter'
        );

    const status =
        formValue(
            'users-status-filter'
        );

    if (search !== '') {
        parameters.set(
            'search',
            search
        );
    }

    if (role !== '') {
        parameters.set(
            'role',
            role
        );
    }

    if (status !== '') {
        parameters.set(
            'is_active',
            status
        );
    }

    return parameters;
}

async function loadUsers(
    page = 1
) {
    const container =
        document.getElementById(
            'users-list'
        );

    if (! container) {
        return;
    }

    hideUsersError();

    container.innerHTML = `
        <div
            class="
                px-5 py-12 text-center
                text-sm text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(
                translate(
                    'users.loading'
                )
            )}
        </div>
    `;

    try {
        const parameters =
            userQueryParameters(
                page
            );

        const response =
            await apiRequest(
                `/api/users?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderUsers(
            payload
        );

        renderUserPagination(
            payload
        );
    } catch (error) {
        container.innerHTML = '';

        showUsersError(
            error instanceof Error
                ? error.message
                : translate(
                    'users.unable_load'
                )
        );
    }
}

function renderUsers(payload) {
    const container =
        document.getElementById(
            'users-list'
        );

    if (! container) {
        return;
    }

    const users =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    loadedUsersById =
        new Map(
            users.map(
                (user) => [
                    String(user.id),
                    user,
                ]
            )
        );

    if (users.length === 0) {
        container.innerHTML = `
            <div
                class="
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        text-sm font-medium
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'users.none_found'
                        )
                    )}
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-[var(--pm-text-muted)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'users.none_found_description'
                        )
                    )}
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        users
            .map(
                userRow
            )
            .join('');

    attachUserActions(
        container
    );
}

function userRow(user) {
    const ownAccount =
        Number(user.id)
        === Number(
            currentUser?.id
        );

    const statusClass =
        user.is_active
            ? 'bg-[var(--pm-success-background)] text-[var(--pm-success-text)]'
            : 'bg-[var(--pm-surface-subtle)] text-[var(--pm-text-muted)]';

    const statusLabel =
        user.is_active
            ? translate('users.active')
            : translate('users.inactive');

    const setupPending =
        ! user.email_verified_at;

    return `
        <article class="px-5 py-5">
            <div
                class="
                    flex flex-col gap-5
                    xl:flex-row
                    xl:items-center
                    xl:justify-between
                "
            >
                <div class="flex min-w-0 flex-1 items-start gap-3">
                    ${avatarMarkup({
                        name: user.name,
                        avatar: user.avatar,
                        size: 36,
                        className: 'mt-0.5',
                    })}

                <div class="min-w-0 flex-1">
                    <div
                        class="
                            flex flex-wrap
                            items-center gap-2
                        "
                    >
                        <div
                            class="
                                text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(
                                user.name
                            )}
                        </div>

                        ${
                            ownAccount
                                ? `
                                    <span
                                        class="
                                            rounded-full
                                            bg-[color-mix(in_srgb,var(--pm-accent)_10%,var(--pm-surface))]
                                            px-2 py-0.5
                                            text-[11px]
                                            font-medium
                                            text-[var(--pm-accent)]
                                        "
                                    >
                                        ${escapeHtml(
                                            translate(
                                                'users.you'
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        <span
                            class="
                                rounded-full
                                px-2.5 py-1
                                text-xs font-medium
                                ${statusClass}
                            "
                        >
                            ${escapeHtml(
                                statusLabel
                            )}
                        </span>

                        ${
                            setupPending
                                ? `
                                    <span
                                        class="
                                            rounded-full
                                            bg-[var(--pm-warning-background)]
                                            px-2.5 py-1
                                            text-xs font-medium
                                            text-[var(--pm-warning-text)]
                                        "
                                    >
                                        ${escapeHtml(
                                            translate(
                                                'users.invitation_pending'
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>

                    <div
                        class="
                            mt-1.5 text-sm
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            user.email
                        )}
                    </div>

                    <div
                        class="
                            mt-2 flex flex-wrap
                            gap-x-5 gap-y-1
                            text-xs text-[var(--pm-text-muted)]
                        "
                    >
                        <span>
                            ${escapeHtml(
                                roleLabel(
                                    user.role
                                )
                            )}
                        </span>

                        ${
                            user.phone
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            user.phone
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>
                </div>
                </div>

                <div
                    class="
                        flex shrink-0
                        flex-wrap gap-2
                    "
                >
                    <button
                        type="button"
                        data-edit-user
                        data-user-id="${escapeHtml(user.id)}"
                        class="
                            rounded-lg border
                            border-[var(--pm-border)]
                            bg-[var(--pm-surface)] px-3 py-2
                            text-xs font-medium
                            text-[var(--pm-text-secondary)]
                            hover:bg-[var(--pm-hover)]
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'users.edit'
                            )
                        )}
                    </button>

                    ${
                        setupPending
                            ? `
                                <button
                                    type="button"
                                    data-resend-invitation
                                    data-user-id="${escapeHtml(user.id)}"
                                    class="
                                        rounded-lg border
                                        border-[var(--pm-border)]
                                        bg-[var(--pm-surface)] px-3 py-2
                                        text-xs font-medium
                                        text-[var(--pm-text)]
                                        transition
                                        hover:bg-[var(--pm-surface-subtle)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'users.resend_invitation'
                                        )
                                    )}
                                </button>
                            `
                            : `
                                <button
                                    type="button"
                                    data-password-reset
                                    data-user-id="${escapeHtml(user.id)}"
                                    class="
                                        rounded-lg border
                                        border-[var(--pm-border)]
                                        bg-[var(--pm-surface)] px-3 py-2
                                        text-xs font-medium
                                        text-[var(--pm-text)]
                                        transition
                                        hover:bg-[var(--pm-surface-subtle)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'users.send_password_reset'
                                        )
                                    )}
                                </button>
                            `
                    }

                    ${
                        ! ownAccount
                            ? `
                                <button
                                    type="button"
                                    data-delete-user
                                    data-user-id="${escapeHtml(user.id)}"
                                    class="
                                        rounded-lg border
                                        border-[var(--pm-danger-border)]
                                        bg-[var(--pm-surface)] px-3 py-2
                                        text-xs font-medium
                                        text-[var(--pm-danger-text)]
                                        transition
                                        hover:bg-[var(--pm-danger-background)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'users.delete'
                                        )
                                    )}
                                </button>
                            `
                            : ''
                    }
                </div>
            </div>
        </article>
    `;
}

function attachUserActions(
    container
) {
    container
        .querySelectorAll(
            '[data-edit-user]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () =>
                        openEditUserModal(
                            button.dataset
                                .userId
                        )
                );
            }
        );

    container
        .querySelectorAll(
            '[data-resend-invitation]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () =>
                        resendInvitation(
                            button.dataset
                                .userId
                        )
                );
            }
        );

    container
        .querySelectorAll(
            '[data-password-reset]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () =>
                        initiatePasswordReset(
                            button.dataset
                                .userId
                        )
                );
            }
        );

    container
        .querySelectorAll(
            '[data-delete-user]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () =>
                        deleteUser(
                            button.dataset
                                .userId
                        )
                );
            }
        );
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

/**
 * Page through the people who can sign in.
 *
 * The control itself lives in resources/js/pagination.js.
 */
function renderUserPagination(
    payload
) {
    renderPagination(
        'users-pagination',
        payload,
        {
            storageKey: 'users',
            onPage: (page) => loadUsers(page),
            onPageSize: () => loadUsers(1),
        }
    );
}

/*
|--------------------------------------------------------------------------
| User Form
|--------------------------------------------------------------------------
*/

function initializeUserForm() {
    document
        .getElementById(
            'add-user-button'
        )
        ?.addEventListener(
            'click',
            openCreateUserModal
        );

    document
        .getElementById(
            'user-modal-close'
        )
        ?.addEventListener(
            'click',
            closeUserModal
        );

    document
        .getElementById(
            'user-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeUserModal
        );

    document
        .getElementById(
            'user-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeUserModal
        );

    document
        .getElementById(
            'user-form'
        )
        ?.addEventListener(
            'submit',
            submitUserForm
        );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
            ) {
                closeUserModal();
            }
        }
    );
}

function openCreateUserModal() {
    userFormMode =
        'create';

    editingUserId =
        null;

    resetUserForm();

    configureUserModal();

    showUserModal();
}

function openEditUserModal(
    userId
) {
    const user =
        loadedUsersById.get(
            String(userId)
        );

    if (! user) {
        return;
    }

    userFormMode =
        'edit';

    editingUserId =
        Number(user.id);

    resetUserForm();

    const structuredName =
        structuredNameFor(
            user
        );

    document
        .getElementById(
            'user-given-names'
        ).value =
        structuredName.given_names;

    document
        .getElementById(
            'user-surname'
        ).value =
        structuredName.surname;

    document
        .getElementById(
            'user-email'
        ).value =
        user.email ?? '';

    applyPhoneValue(
        'user-phone',
        user.phone,
        user.phone_country
    );

    document
        .getElementById(
            'user-role'
        ).value =
        user.role ?? 'viewer';

    document
        .getElementById(
            'user-active'
        ).checked =
        Boolean(
            user.is_active
        );

    /*
     * The API owns the authoritative safeguards, but the UI does not present
     * impossible self-demotion/self-disable actions.
     */
    const ownAccount =
        Number(user.id)
        === Number(
            currentUser?.id
        );

    const role =
        document.getElementById(
            'user-role'
        );

    const active =
        document.getElementById(
            'user-active'
        );

    if (role) {
        role.disabled =
            ownAccount;
    }

    if (active) {
        active.disabled =
            ownAccount;
    }

    configureUserModal();

    showUserModal();
}

function resetUserForm() {
    document
        .getElementById(
            'user-form'
        )
        ?.reset();

    const role =
        document.getElementById(
            'user-role'
        );

    const active =
        document.getElementById(
            'user-active'
        );

    if (role) {
        role.disabled = false;
        role.value = 'viewer';
    }

    if (active) {
        active.disabled = false;
        active.checked = true;
    }

    hideUserFormError();
}

function configureUserModal() {
    const editing =
        userFormMode === 'edit';

    const title =
        document.getElementById(
            'user-modal-title'
        );

    const description =
        document.getElementById(
            'user-modal-description'
        );

    const button =
        document.getElementById(
            'user-submit-button'
        );

    if (title) {
        title.textContent =
            translate(
                editing
                    ? 'users.edit_user'
                    : 'users.add_user'
            );
    }

    if (description) {
        description.textContent =
            translate(
                editing
                    ? 'users.edit_description'
                    : 'users.create_description'
            );
    }

    if (button) {
        button.textContent =
            translate(
                'actions.save'
            );
    }
}

function showUserModal() {
    const modal =
        document.getElementById(
            'user-modal'
        );

    if (! modal) {
        return;
    }

    /*
     * A drawer may be reopened before the previous close cleanup
     * has fired. Cancel that old cleanup so it cannot interfere
     * with this opening animation.
     */
    if (userDrawerCloseTimer !== null) {
        window.clearTimeout(
            userDrawerCloseTimer
        );

        userDrawerCloseTimer = null;
    }

    /*
     * Start from the real closed position.
     */
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

    /*
     * Force translateX(100%) to be committed before
     * beginning the opening transition.
     */
    if (panel) {
        void panel.getBoundingClientRect();
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

function closeUserModal() {
    const modal =
        document.getElementById(
            'user-modal'
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

    if (userDrawerCloseTimer !== null) {
        window.clearTimeout(
            userDrawerCloseTimer
        );
    }

    userDrawerCloseTimer = window.setTimeout(
        () => {
            modal.classList.remove(
                'pm-drawer-active',
                'pm-drawer-closing'
            );

            document.body.classList.remove(
                'overflow-hidden'
            );

            userFormMode = 'create';
            editingUserId = null;

            resetUserForm();
            userDrawerCloseTimer = null;
        },
        850
    );
}

async function submitUserForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'user-form'
        );

    const button =
        document.getElementById(
            'user-submit-button'
        );

    if (! form || ! button) {
        return;
    }

    if (! form.reportValidity()) {
        return;
    }

    const editing =
        userFormMode === 'edit'
        && Number.isInteger(
            editingUserId
        );

    /*
     * V1.0.7 structured names: the API composes the display name from
     * given_names + surname.
     */
    const payload = {
        given_names:
            nullableFormValue(
                'user-given-names'
            ),

        surname:
            formValue(
                'user-surname'
            ),

        email:
            formValue(
                'user-email'
            ),

        phone:
            readPhoneValue('user-phone').number,

        phone_country:
            readPhoneValue('user-phone').country,
    };

    /*
     * Disabled self-controls are deliberately omitted from an own-account
     * edit, preserving the backend's Administrator safety invariant.
     */
    const roleControl =
        document.getElementById(
            'user-role'
        );

    const activeControl =
        document.getElementById(
            'user-active'
        );

    if (
        roleControl
        && ! roleControl.disabled
    ) {
        payload.role =
            roleControl.value;
    }

    if (
        activeControl
        && ! activeControl.disabled
    ) {
        payload.is_active =
            activeControl.checked;
    }

    try {
        hideUserFormError();

        button.disabled = true;

        button.textContent =
            translate(
                editing
                    ? 'users.saving'
                    : 'users.creating'
            );

        const response =
            await apiRequest(
                editing
                    ? `/api/users/${editingUserId}`
                    : '/api/users',
                {
                    method:
                        editing
                            ? 'PATCH'
                            : 'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closeUserModal();

        showUsersSuccess(
            translate(
                editing
                    ? 'users.updated'
                    : 'users.created'
            )
        );

        await loadUsers(1);
    } catch (error) {
        showUserFormError(
            error instanceof Error
                ? error.message
                : translate(
                    editing
                        ? 'users.unable_update'
                        : 'users.unable_create'
                )
        );
    } finally {
        button.disabled = false;

        button.textContent =
            translate(
                'actions.save'
            );
    }
}

/*
|--------------------------------------------------------------------------
| Account Actions
|--------------------------------------------------------------------------
*/

async function resendInvitation(
    userId
) {
    const user =
        loadedUsersById.get(
            String(userId)
        );

    if (! user) {
        return;
    }

    if (
        ! window.confirm(
            translate(
                'users.resend_confirmation',
                {
                    name:
                        user.name,
                }
            )
        )
    ) {
        return;
    }

    await performUserAction(
        `/api/users/${user.id}/resend-invitation`,
        'users.invitation_resent'
    );
}

async function initiatePasswordReset(
    userId
) {
    const user =
        loadedUsersById.get(
            String(userId)
        );

    if (! user) {
        return;
    }

    if (
        ! window.confirm(
            translate(
                'users.reset_confirmation',
                {
                    name:
                        user.name,
                }
            )
        )
    ) {
        return;
    }

    await performUserAction(
        `/api/users/${user.id}/password-reset`,
        'users.reset_sent'
    );
}

async function performUserAction(
    endpoint,
    successKey
) {
    try {
        hideUsersError();

        const response =
            await apiRequest(
                endpoint,
                {
                    method: 'POST',
                }
            );

        await parseJsonResponse(
            response
        );

        showUsersSuccess(
            translate(
                successKey
            )
        );

        await loadUsers(1);
    } catch (error) {
        showUsersError(
            error instanceof Error
                ? error.message
                : translate(
                    'users.action_failed'
                )
        );
    }
}

async function deleteUser(
    userId
) {
    const user =
        loadedUsersById.get(
            String(userId)
        );

    if (! user) {
        return;
    }

    if (
        ! window.confirm(
            translate(
                'users.delete_confirmation',
                {
                    name:
                        user.name,
                }
            )
        )
    ) {
        return;
    }

    try {
        hideUsersError();

        if (
            ! await requireDangerConfirmation({
                entityLabel: String(user.name ?? user.email ?? ''),
            })
        ) {
            return;
        }

        const response =
            await apiRequest(
                `/api/users/${user.id}`,
                {
                    method: 'DELETE',
                }
            );

        if (
            response.status !== 204
        ) {
            await parseJsonResponse(
                response
            );
        }

        showUsersSuccess(
            translate(
                'users.deleted'
            )
        );

        await loadUsers(1);
    } catch (error) {
        showUsersError(
            error instanceof Error
                ? error.message
                : translate(
                    'users.unable_delete'
                )
        );
    }
}

/*
|--------------------------------------------------------------------------
| Labels / Messages
|--------------------------------------------------------------------------
*/

function roleLabel(role) {
    switch (role) {
        case 'administrator':
            return translate(
                'roles.administrator'
            );

        case 'property_manager':
            return translate(
                'roles.property_manager'
            );

        case 'viewer':
            return translate(
                'roles.viewer'
            );

        default:
            return String(
                role || ''
            );
    }
}

function showUsersError(message) {
    const box =
        document.getElementById(
            'users-error'
        );

    if (! box) {
        return;
    }

    box.textContent = message;
    box.classList.remove('hidden');
}

function hideUsersError() {
    const box =
        document.getElementById(
            'users-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';
    box.classList.add('hidden');
}

function showUsersSuccess(message) {
    const box =
        document.getElementById(
            'users-success'
        );

    if (! box) {
        return;
    }

    box.textContent = message;
    box.classList.remove('hidden');

    window.setTimeout(
        () => {
            box.classList.add(
                'hidden'
            );
        },
        5000
    );
}

function showUserFormError(message) {
    const box =
        document.getElementById(
            'user-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = message;
    box.classList.remove('hidden');
}

function hideUserFormError() {
    const box =
        document.getElementById(
            'user-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';
    box.classList.add('hidden');
}
