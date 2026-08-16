/*
|--------------------------------------------------------------------------
| Patrimoine Browser Password Workflows
|--------------------------------------------------------------------------
|
| Public invitation/reset flows and the authenticated user's own password
| change UI live here. Passwords and tokens are never persisted client-side.
|
*/

import {
    apiRequest,
    clearToken,
    parseJsonResponse,
    translate,
} from './core.js';

function queryValue(name) {
    return new URLSearchParams(
        window.location.search
    ).get(name) || '';
}

function setMessage(element, message, success = false) {
    if (! element) {
        return;
    }

    element.textContent = message;

    element.classList.remove(
        'hidden',
        'border-red-200',
        'bg-red-50',
        'text-red-700',
        'border-emerald-200',
        'bg-emerald-50',
        'text-emerald-700'
    );

    element.classList.add(
        success
            ? 'border-emerald-200'
            : 'border-red-200',
        success
            ? 'bg-emerald-50'
            : 'bg-red-50',
        success
            ? 'text-emerald-700'
            : 'text-red-700'
    );
}

async function publicPost(url, payload) {
    const response = await fetch(
        url,
        {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        }
    );

    return parseJsonResponse(response);
}

export function initializeForgotPassword() {
    const form =
        document.getElementById(
            'forgot-password-form'
        );

    if (! form) {
        return false;
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const email =
                document.getElementById(
                    'forgot-email'
                );

            const button =
                document.getElementById(
                    'forgot-password-button'
                );

            const message =
                document.getElementById(
                    'forgot-password-message'
                );

            if (! email || ! button) {
                return;
            }

            button.disabled = true;
            button.textContent =
                translate(
                    'password.sending'
                );

            try {
                const data =
                    await publicPost(
                        '/api/auth/forgot-password',
                        {
                            email:
                                email.value.trim(),
                        }
                    );

                setMessage(
                    message,
                    data.message
                    || translate(
                        'password.reset_requested'
                    ),
                    true
                );

                form.reset();
            } catch (error) {
                setMessage(
                    message,
                    error instanceof Error
                        ? error.message
                        : translate(
                            'password.request_failed'
                        )
                );
            } finally {
                button.disabled = false;
                button.textContent =
                    translate(
                        'password.send_reset'
                    );
            }
        }
    );

    return true;
}

export function initializeResetPassword() {
    const form =
        document.getElementById(
            'reset-password-form'
        );

    if (! form) {
        return false;
    }

    const email =
        document.getElementById(
            'reset-email'
        );

    if (email) {
        email.value =
            queryValue('email');
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const password =
                document.getElementById(
                    'reset-password'
                );

            const confirmation =
                document.getElementById(
                    'reset-password-confirmation'
                );

            const button =
                document.getElementById(
                    'reset-password-button'
                );

            const message =
                document.getElementById(
                    'reset-password-message'
                );

            if (
                ! email
                || ! password
                || ! confirmation
                || ! button
            ) {
                return;
            }

            if (
                password.value
                !== confirmation.value
            ) {
                setMessage(
                    message,
                    translate(
                        'password.confirmation_mismatch'
                    )
                );

                return;
            }

            button.disabled = true;
            button.textContent =
                translate(
                    'password.resetting'
                );

            try {
                const data =
                    await publicPost(
                        '/api/auth/reset-password',
                        {
                            email:
                                email.value.trim(),
                            token:
                                queryValue('token'),
                            password:
                                password.value,
                            password_confirmation:
                                confirmation.value,
                        }
                    );

                setMessage(
                    message,
                    data.message
                    || translate(
                        'password.reset_complete'
                    ),
                    true
                );

                form.reset();

                window.setTimeout(
                    () => {
                        window.location.replace(
                            '/login'
                        );
                    },
                    1200
                );
            } catch (error) {
                setMessage(
                    message,
                    error instanceof Error
                        ? error.message
                        : translate(
                            'password.request_failed'
                        )
                );
            } finally {
                button.disabled = false;
                button.textContent =
                    translate(
                        'password.reset_action'
                    );
            }
        }
    );

    return true;
}

export function initializeInvitation() {
    const form =
        document.getElementById(
            'invitation-form'
        );

    if (! form) {
        return false;
    }

    const email =
        document.getElementById(
            'invitation-email'
        );

    if (email) {
        email.value =
            queryValue('email');
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const password =
                document.getElementById(
                    'invitation-password'
                );

            const confirmation =
                document.getElementById(
                    'invitation-password-confirmation'
                );

            const button =
                document.getElementById(
                    'invitation-button'
                );

            const message =
                document.getElementById(
                    'invitation-message'
                );

            if (
                ! email
                || ! password
                || ! confirmation
                || ! button
            ) {
                return;
            }

            if (
                password.value
                !== confirmation.value
            ) {
                setMessage(
                    message,
                    translate(
                        'password.confirmation_mismatch'
                    )
                );

                return;
            }

            button.disabled = true;
            button.textContent =
                translate(
                    'password.setting_password'
                );

            try {
                const data =
                    await publicPost(
                        '/api/auth/invitations/accept',
                        {
                            email:
                                email.value.trim(),
                            token:
                                queryValue('token'),
                            password:
                                password.value,
                            password_confirmation:
                                confirmation.value,
                        }
                    );

                setMessage(
                    message,
                    data.message
                    || translate(
                        'password.invitation_complete'
                    ),
                    true
                );

                form.reset();

                window.setTimeout(
                    () => {
                        window.location.replace(
                            '/login'
                        );
                    },
                    1200
                );
            } catch (error) {
                setMessage(
                    message,
                    error instanceof Error
                        ? error.message
                        : translate(
                            'password.request_failed'
                        )
                );
            } finally {
                button.disabled = false;
                button.textContent =
                    translate(
                        'password.set_password'
                    );
            }
        }
    );

    return true;
}

export function initializeChangePassword() {
    const form =
        document.getElementById(
            'change-password-form'
        );

    if (! form) {
        return false;
    }

    const openButton =
        document.getElementById(
            'change-password-open'
        );

    const modal =
        document.getElementById(
            'change-password-modal'
        );

    const backdrop =
        document.getElementById(
            'change-password-backdrop'
        );

    const closeButton =
        document.getElementById(
            'change-password-close'
        );

    const cancelButton =
        document.getElementById(
            'change-password-cancel'
        );

    const close = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        form.reset();

        document
            .getElementById(
                'change-password-message'
            )
            ?.classList.add('hidden');
    };

    const open = () => {
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };

    openButton?.addEventListener(
        'click',
        open
    );

    backdrop?.addEventListener(
        'click',
        close
    );

    closeButton?.addEventListener(
        'click',
        close
    );

    cancelButton?.addEventListener(
        'click',
        close
    );

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const current =
                document.getElementById(
                    'current-password'
                );

            const password =
                document.getElementById(
                    'change-new-password'
                );

            const confirmation =
                document.getElementById(
                    'change-password-confirmation'
                );

            const button =
                document.getElementById(
                    'change-password-submit'
                );

            const message =
                document.getElementById(
                    'change-password-message'
                );

            if (
                ! current
                || ! password
                || ! confirmation
                || ! button
            ) {
                return;
            }

            if (
                password.value
                !== confirmation.value
            ) {
                setMessage(
                    message,
                    translate(
                        'password.confirmation_mismatch'
                    )
                );

                return;
            }

            button.disabled = true;
            button.textContent =
                translate(
                    'password.changing'
                );

            try {
                await apiRequest(
                    '/api/auth/change-password',
                    {
                        method: 'POST',
                        body: JSON.stringify({
                            current_password:
                                current.value,
                            password:
                                password.value,
                            password_confirmation:
                                confirmation.value,
                        }),
                    }
                );

                /*
                 * The server revokes every API token after a successful
                 * password change, including this browser's token.
                 */
                clearToken();

                window.location.replace(
                    '/login'
                );
            } catch (error) {
                setMessage(
                    message,
                    error instanceof Error
                        ? error.message
                        : translate(
                            'password.request_failed'
                        )
                );
            } finally {
                button.disabled = false;
                button.textContent =
                    translate(
                        'password.change_action'
                    );
            }
        }
    );

    return true;
}
