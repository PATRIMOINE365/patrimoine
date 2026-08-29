/*
|--------------------------------------------------------------------------
| Patrimoine Public Signup & Email Verification (V1.0.10)
|--------------------------------------------------------------------------
|
| Multi-tenant self-service registration:
|
| - /signup        create an organisation + first administrator;
| - /verify-email  consume the emailed verification link.
|
| Both screens live in the same auth box as the login page and follow
| its interaction patterns.
|
*/

import {
    getPresentationConfiguration,
    parseJsonResponse,
    translate,
} from './core.js';

import { readPhoneValue } from './phone-input.js';

/**
 * POST a JSON payload without authentication.
 */
async function postJson(url, payload) {
    const response =
        await fetch(
            url,
            {
                method: 'POST',

                headers: {
                    Accept:
                        'application/json',

                    'Content-Type':
                        'application/json',

                    /*
                     * Localize public responses (validation errors)
                     * in the language the visitor is reading.
                     */
                    'X-Patrimoine-Language':
                        getPresentationConfiguration()
                            ?.language
                        || 'en',
                },

                body:
                    JSON.stringify(payload),
            }
        );

    return parseJsonResponse(response);
}

function showError(box, message) {
    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove('hidden');
}

function clearError(box) {
    if (! box) {
        return;
    }

    box.textContent =
        '';

    box.classList.add('hidden');
}

/**
 * The language the visitor is currently reading the page in.
 *
 * It becomes the new organisation's presentation language.
 */
function currentLanguage() {
    const configured =
        getPresentationConfiguration()?.language;

    if (configured === 'fr' || configured === 'en') {
        return configured;
    }

    try {
        const stored =
            window.localStorage.getItem('patrimoine.language');

        if (stored === 'fr' || stored === 'en') {
            return stored;
        }
    } catch {
        // Storage restrictions never break signup.
    }

    return 'en';
}

/*
|--------------------------------------------------------------------------
| Signup page
|--------------------------------------------------------------------------
*/

/**
 * Initialize the signup page when present.
 *
 * @returns {boolean}
 */
export function initializeSignup() {
    const form =
        document.getElementById('signup-form');

    if (! form) {
        return false;
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const button =
                document.getElementById('signup-button');

            const errorBox =
                document.getElementById('signup-error');

            if (! button || ! errorBox) {
                return;
            }

            clearError(errorBox);

            const label =
                button.querySelector('[data-i18n]');

            button.disabled =
                true;

            if (label) {
                label.textContent =
                    translate('signup.submitting');
            }

            try {
                const data =
                    await postJson(
                        '/api/auth/register',
                        {
                            organisation_name:
                                document
                                    .getElementById('signup-organisation')
                                    .value
                                    .trim(),

                            given_names:
                                document
                                    .getElementById('signup-given-names')
                                    .value
                                    .trim(),

                            surname:
                                document
                                    .getElementById('signup-surname')
                                    .value
                                    .trim(),

                            email:
                                document
                                    .getElementById('signup-email')
                                    .value
                                    .trim(),

                            phone:
                                readPhoneValue('signup-phone').number,

                            phone_country:
                                readPhoneValue('signup-phone').country,

                            password:
                                document
                                    .getElementById('signup-password')
                                    .value,

                            password_confirmation:
                                document
                                    .getElementById('signup-password-confirmation')
                                    .value,

                            language:
                                currentLanguage(),

                            accept_legal:
                                document
                                    .getElementById('signup-legal')
                                    .checked,
                        }
                    );

                const doneEmail =
                    document.getElementById('signup-done-email');

                if (doneEmail) {
                    doneEmail.textContent =
                        data.email
                        || '';
                }

                document
                    .getElementById('signup-step-form')
                    ?.classList
                    .add('hidden');

                document
                    .getElementById('signup-step-done')
                    ?.classList
                    .remove('hidden');
            } catch (error) {
                showError(
                    errorBox,
                    error instanceof Error
                        ? error.message
                        : translate('signup.unable')
                );
            } finally {
                button.disabled =
                    false;

                if (label) {
                    label.textContent =
                        translate('signup.submit');
                }
            }
        }
    );

    /*
     * V1.0.15: the confirmation step offers a fresh verification email
     * for the address that just signed up (shown in #signup-done-email).
     */
    const resendButton =
        document.getElementById('signup-resend-button');

    if (resendButton) {
        resendButton.addEventListener(
            'click',
            async () => {
                const feedback =
                    document.getElementById('signup-resend-feedback');

                const email =
                    document
                        .getElementById('signup-done-email')
                        ?.textContent
                        .trim();

                if (! email) {
                    return;
                }

                resendButton.disabled =
                    true;

                try {
                    const data =
                        await postJson(
                            '/api/auth/resend-verification',
                            {
                                email,
                            }
                        );

                    if (feedback) {
                        feedback.textContent =
                            data.message
                            || translate('verify_email.resent');

                        feedback.classList.remove('hidden');
                    }
                } catch (error) {
                    if (feedback) {
                        feedback.textContent =
                            error instanceof Error
                                ? error.message
                                : translate('verify_email.resend_failed');

                        feedback.classList.remove('hidden');
                    }
                } finally {
                    resendButton.disabled =
                        false;
                }
            }
        );
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| Email verification page
|--------------------------------------------------------------------------
*/

/**
 * Initialize the email-verification landing page when present.
 *
 * @returns {boolean}
 */
export function initializeVerifyEmail() {
    const pendingStep =
        document.getElementById('verify-step-pending');

    if (! pendingStep) {
        return false;
    }

    const successStep =
        document.getElementById('verify-step-success');

    const failedStep =
        document.getElementById('verify-step-failed');

    const token =
        new URLSearchParams(window.location.search)
            .get('token');

    async function consumeToken() {
        try {
            if (
                typeof token !== 'string'
                || token.length !== 64
            ) {
                throw new Error('invalid token');
            }

            await postJson(
                '/api/auth/verify-email',
                {
                    token,
                }
            );

            pendingStep.classList.add('hidden');

            successStep?.classList.remove('hidden');
        } catch {
            pendingStep.classList.add('hidden');

            failedStep?.classList.remove('hidden');
        }
    }

    consumeToken();

    /*
     * The failure state offers to send a fresh verification link.
     */
    const resendForm =
        document.getElementById('verify-resend-form');

    if (resendForm) {
        resendForm.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                const button =
                    document.getElementById('verify-resend-button');

                const feedback =
                    document.getElementById('verify-resend-feedback');

                const emailInput =
                    document.getElementById('verify-resend-email');

                if (! button || ! emailInput) {
                    return;
                }

                button.disabled =
                    true;

                try {
                    const data =
                        await postJson(
                            '/api/auth/resend-verification',
                            {
                                email:
                                    emailInput.value.trim(),
                            }
                        );

                    if (feedback) {
                        feedback.textContent =
                            data.message
                            || translate('verify_email.resent');

                        feedback.classList.remove('hidden');
                    }
                } catch (error) {
                    if (feedback) {
                        feedback.textContent =
                            error instanceof Error
                                ? error.message
                                : translate('verify_email.resend_failed');

                        feedback.classList.remove('hidden');
                    }
                } finally {
                    button.disabled =
                        false;
                }
            }
        );
    }

    return true;
}
