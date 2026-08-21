/*
|--------------------------------------------------------------------------
| Patrimoine Browser Password Workflows
|--------------------------------------------------------------------------
|
| Public invitation/reset flows live here. The authenticated user's own
| password change happens inline in the profile drawer (auth.js).
| Passwords and tokens are never persisted client-side.
|
*/

import {
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
        'pm-auth-message-success',
        'pm-auth-message-error'
    );

    element.classList.add(
        success
            ? 'pm-auth-message-success'
            : 'pm-auth-message-error'
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

