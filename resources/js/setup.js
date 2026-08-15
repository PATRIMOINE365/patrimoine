/*
|--------------------------------------------------------------------------
| Patrimoine Initial Setup
|--------------------------------------------------------------------------
|
| This module serves only the one-time installation workflow.
|
| Setup itself deliberately remains English. Once configuration succeeds,
| normal Patrimoine presentation follows the Managing Organisation language.
|
*/

document.addEventListener(
    'DOMContentLoaded',
    async () => {
        const pathname =
            window.location.pathname;

        if (
            pathname !== '/setup'
            && pathname !== '/login'
        ) {
            return;
        }

        const required =
            await setupRequired();

        if (required === null) {
            if (pathname === '/setup') {
                showSetupError(
                    'Unable to determine installation status.'
                );
            }

            return;
        }

        /*
         * A fresh installation entering through / or /login is moved to the
         * one-time setup page before authentication is attempted.
         */
        if (
            pathname === '/login'
            && required
        ) {
            window.location.replace(
                '/setup'
            );

            return;
        }

        /*
         * Once setup is complete, the public setup screen is no longer
         * available through normal browser navigation.
         */
        if (
            pathname === '/setup'
            && ! required
        ) {
            window.location.replace(
                '/login'
            );

            return;
        }

        if (pathname === '/setup') {
            initializeSetupForm();
        }
    }
);


/**
 * Ask the public API whether Patrimoine still requires first-time setup.
 */
async function setupRequired()
{
    try {
        const response =
            await fetch(
                '/api/setup/status',
                {
                    headers: {
                        Accept:
                            'application/json',
                    },
                }
            );

        if (! response.ok) {
            return null;
        }

        const data =
            await response.json();

        return data.setup_required === true;
    } catch {
        return null;
    }
}


/**
 * Reveal and register the setup form.
 */
function initializeSetupForm()
{
    const loading =
        document.getElementById(
            'setup-loading'
        );

    const form =
        document.getElementById(
            'setup-form'
        );

    if (! form) {
        return;
    }

    loading?.classList.add(
        'hidden'
    );

    form.classList.remove(
        'hidden'
    );

    form.addEventListener(
        'submit',
        completeSetup
    );
}


/**
 * Submit one atomic initial-installation request.
 */
async function completeSetup(event)
{
    event.preventDefault();

    clearSetupError();

    const button =
        document.getElementById(
            'setup-submit'
        );

    if (button) {
        button.disabled = true;
        button.textContent =
            'Completing Setup…';
    }

    const payload = {
        administrator_name:
            valueOf(
                'administrator-name'
            ),

        administrator_email:
            valueOf(
                'administrator-email'
            ),

        administrator_password:
            valueOf(
                'administrator-password'
            ),

        administrator_password_confirmation:
            valueOf(
                'administrator-password-confirmation'
            ),

        legal_name:
            valueOf(
                'organisation-legal-name'
            ),

        address:
            valueOf(
                'organisation-address'
            ),

        phone:
            nullableValueOf(
                'organisation-phone'
            ),

        email:
            nullableValueOf(
                'organisation-email'
            ),

        contact_person_name:
            valueOf(
                'contact-person-name'
            ),

        contact_person_phone:
            valueOf(
                'contact-person-phone'
            ),

        contact_person_email:
            valueOf(
                'contact-person-email'
            ),

        default_vat_rate:
            Number(
                valueOf(
                    'setup-vat-rate'
                )
            ),

        language:
            valueOf(
                'setup-language'
            ),

        currency:
            valueOf(
                'setup-currency'
            ),
    };

    try {
        const response =
            await fetch(
                '/api/setup',
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
                        JSON.stringify(
                            payload
                        ),
                }
            );

        const data =
            await response.json();

        if (! response.ok) {
            throw new Error(
                validationMessage(
                    data
                )
            );
        }

        /*
         * The selected language has now been persisted. Login will load the
         * public presentation configuration and render in that language.
         */
        window.location.replace(
            '/login'
        );
    } catch (error) {
        showSetupError(
            error instanceof Error
                ? error.message
                : 'Initial setup could not be completed.'
        );
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent =
                'Complete Setup';
        }
    }
}


/**
 * Extract the first useful Laravel validation error.
 */
function validationMessage(data)
{
    if (
        data
        && typeof data.message === 'string'
        && data.message !== 'The given data was invalid.'
    ) {
        return data.message;
    }

    if (
        data
        && data.errors
        && typeof data.errors === 'object'
    ) {
        for (
            const messages
            of Object.values(
                data.errors
            )
        ) {
            if (
                Array.isArray(messages)
                && messages.length > 0
            ) {
                return String(
                    messages[0]
                );
            }
        }
    }

    return 'Initial setup could not be completed.';
}


function valueOf(id)
{
    const element =
        document.getElementById(
            id
        );

    return element
        ? String(
            element.value
        ).trim()
        : '';
}


function nullableValueOf(id)
{
    const value =
        valueOf(
            id
        );

    return value !== ''
        ? value
        : null;
}


function showSetupError(message)
{
    const element =
        document.getElementById(
            'setup-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        message;

    element.classList.remove(
        'hidden'
    );
}


function clearSetupError()
{
    const element =
        document.getElementById(
            'setup-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        '';

    element.classList.add(
        'hidden'
    );
}
