/*
|--------------------------------------------------------------------------
| Patrimoine Settings
|--------------------------------------------------------------------------
|
| Browser-side functionality for application-wide Patrimoine settings.
|
| Patrimoine 1.0 currently supports one Managing Organisation.
|
| The backend represents that organisation as a Party and stores its Party
| ID in ApplicationSetting. This module intentionally works only through
| the dedicated /api/managing-organisation endpoint instead of manipulating
| Party records directly.
|
*/

import {
    apiRequest,
    formValue,
    nullableFormValue,
    parseJsonResponse,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Public Initializer
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Settings page.
 *
 * The initializer safely exits on all other Patrimoine pages.
 */
export async function initializeSettings() {
    const form =
        document.getElementById(
            'managing-organisation-form'
        );

    if (! form) {
        return;
    }

    form.addEventListener(
        'submit',
        submitManagingOrganisation
    );

    await loadManagingOrganisationSettings();
}

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

/**
 * Load the currently configured Managing Organisation.
 *
 * HTTP 404 is valid for a fresh installation. In that case the form simply
 * remains empty and becomes the initial organisation configuration form.
 */
async function loadManagingOrganisationSettings() {
    hideSettingsError();
    hideSettingsSuccess();

    try {
        const response =
            await apiRequest(
                '/api/managing-organisation'
            );

        if (response.status === 404) {
            return;
        }

        const organisation =
            await parseJsonResponse(
                response
            );

        populateManagingOrganisationForm(
            organisation
        );
    } catch (error) {
        showSettingsError(
            error instanceof Error
                ? error.message
                : 'Unable to load Managing Organisation.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Form Population
|--------------------------------------------------------------------------
*/

/**
 * Populate the Settings form from the Managing Organisation Party returned
 * by the API.
 */
function populateManagingOrganisationForm(
    organisation
) {
    setFormValue(
        'organisation-legal-name',
        organisation.legal_name
    );

    setFormValue(
        'organisation-address',
        organisation.address
    );

    setFormValue(
        'organisation-phone',
        organisation.phone
    );

    setFormValue(
        'organisation-alternate-phone',
        organisation.alternate_phone
    );

    setFormValue(
        'organisation-email',
        organisation.email
    );

    setFormValue(
        'organisation-contact-name',
        organisation.contact_person_name
    );

    setFormValue(
        'organisation-contact-phone',
        organisation.contact_person_phone
    );

    setFormValue(
        'organisation-contact-email',
        organisation.contact_person_email
    );

    setFormValue(
        'organisation-registration-number',
        organisation.registration_number
    );

    setFormValue(
        'organisation-vat-tin',
        organisation.vat_tin
    );

    setFormValue(
        'organisation-bank-name',
        organisation.bank_name
    );

    setFormValue(
        'organisation-bank-account-name',
        organisation.bank_account_name
    );

    setFormValue(
        'organisation-bank-account-number',
        organisation.bank_account_number
    );

    setFormValue(
        'organisation-bank-branch',
        organisation.bank_branch
    );

    setFormValue(
        'organisation-notes',
        organisation.notes
    );
}

/**
 * Safely set a form field.
 */
function setFormValue(
    id,
    value
) {
    const element =
        document.getElementById(id);

    if (! element) {
        return;
    }

    element.value =
        value ?? '';
}

/*
|--------------------------------------------------------------------------
| Submission
|--------------------------------------------------------------------------
*/

/**
 * Create or update the singleton Managing Organisation.
 *
 * The API decides whether a new underlying Party needs to be created or
 * whether the existing Managing Organisation Party should be updated.
 */
async function submitManagingOrganisation(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'managing-organisation-form'
        );

    const submitButton =
        document.getElementById(
            'managing-organisation-submit-button'
        );

    if (
        ! form
        || ! submitButton
    ) {
        return;
    }

    hideSettingsError();
    hideSettingsSuccess();

    if (! form.reportValidity()) {
        return;
    }

    const payload = {
        legal_name:
            formValue(
                'organisation-legal-name'
            ),

        address:
            formValue(
                'organisation-address'
            ),

        phone:
            nullableFormValue(
                'organisation-phone'
            ),

        alternate_phone:
            nullableFormValue(
                'organisation-alternate-phone'
            ),

        email:
            nullableFormValue(
                'organisation-email'
            ),

        contact_person_name:
            formValue(
                'organisation-contact-name'
            ),

        contact_person_phone:
            formValue(
                'organisation-contact-phone'
            ),

        contact_person_email:
            formValue(
                'organisation-contact-email'
            ),

        registration_number:
            nullableFormValue(
                'organisation-registration-number'
            ),

        vat_tin:
            nullableFormValue(
                'organisation-vat-tin'
            ),

        bank_name:
            nullableFormValue(
                'organisation-bank-name'
            ),

        bank_account_name:
            nullableFormValue(
                'organisation-bank-account-name'
            ),

        bank_account_number:
            nullableFormValue(
                'organisation-bank-account-number'
            ),

        bank_branch:
            nullableFormValue(
                'organisation-bank-branch'
            ),

        notes:
            nullableFormValue(
                'organisation-notes'
            ),
    };

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            'Saving…';

        const response =
            await apiRequest(
                '/api/managing-organisation',
                {
                    method: 'PUT',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        const organisation =
            await parseJsonResponse(
                response
            );

        /*
         * Refresh the form from the server representation so the browser
         * always reflects the persisted record.
         */
        populateManagingOrganisationForm(
            organisation
        );

        /*
         * The application header normally loads this value during shell
         * initialization. Updating it immediately avoids requiring a page
         * refresh after changing the organisation.
         */
        const headerName =
            document.getElementById(
                'organisation-name'
            );

        if (headerName) {
            headerName.textContent =
                organisation.legal_name
                || 'Patrimoine';
        }

        showSettingsSuccess(
            'Managing Organisation saved successfully.'
        );
    } catch (error) {
        showSettingsError(
            error instanceof Error
                ? error.message
                : 'Unable to save Managing Organisation.'
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            'Save Organisation';
    }
}

/*
|--------------------------------------------------------------------------
| Feedback
|--------------------------------------------------------------------------
*/

/**
 * Display an error on the Settings page.
 */
function showSettingsError(message) {
    const box =
        document.getElementById(
            'settings-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
}

/**
 * Clear Settings errors.
 */
function hideSettingsError() {
    const box =
        document.getElementById(
            'settings-error'
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
 * Display a successful save notification.
 */
function showSettingsSuccess(message) {
    const box =
        document.getElementById(
            'settings-success'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
}

/**
 * Clear successful-operation feedback.
 */
function hideSettingsSuccess() {
    const box =
        document.getElementById(
            'settings-success'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}
