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
    escapeHtml,
    formValue,
    getPresentationConfiguration,
    loadPresentationConfiguration,
    nullableFormValue,
    parseJsonResponse,
    translate,
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
    const workspace =
        document.getElementById(
            'settings-workspace'
        );

    if (! workspace) {
        return;
    }

    initializeAboutSection();
    initializeRegistryPortability();

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
| V1.0.7 About
|--------------------------------------------------------------------------
*/

/**
 * Show the running application release.
 *
 * The release ships with the cached public presentation configuration,
 * so displaying it needs no additional request beyond the bootstrap one.
 */
async function initializeAboutSection() {
    const element =
        document.getElementById(
            'settings-app-version'
        );

    if (! element) {
        return;
    }

    const configuration =
        await loadPresentationConfiguration();

    const release =
        String(
            configuration.release
            || ''
        ).trim();

    element.textContent =
        release !== ''
            ? `v${release}`
            : '—';
}

/*
|--------------------------------------------------------------------------
| V1.0.7 Registry Backup & Restore
|--------------------------------------------------------------------------
|
| Administrator-only Registry portability. Exports download through
| authenticated fetches (the API accepts only Bearer-token requests, so
| plain new-tab links cannot authenticate); imports POST multipart
| FormData through apiRequest, which deliberately leaves the
| Content-Type header to the browser for FormData bodies.
|
*/

const REGISTRY_MIME_TYPES = {
    csv:
        'text/csv',

    xlsx:
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

    pdf:
        'application/pdf',
};

function initializeRegistryPortability() {
    const section =
        document.getElementById(
            'settings-backup-section'
        );

    if (! section) {
        return;
    }

    section
        .querySelectorAll(
            '[data-registry-export]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () =>
                        downloadRegistryExport(
                            button,
                            `/api/registry/export?entity=${encodeURIComponent(
                                button.dataset.entity
                            )}&format=${encodeURIComponent(
                                button.dataset.format
                            )}`,
                            button.dataset.format,
                            `registry-${button.dataset.entity}.${button.dataset.format}`
                        )
                );
            }
        );

    const fullButton =
        document.getElementById(
            'settings-export-full'
        );

    fullButton?.addEventListener(
        'click',
        () =>
            downloadRegistryExport(
                fullButton,
                '/api/registry/export/full',
                'xlsx',
                'registry-full.xlsx'
            )
    );

    const pdfButton =
        document.getElementById(
            'settings-export-pdf'
        );

    pdfButton?.addEventListener(
        'click',
        () => {
            const entity =
                formValue(
                    'settings-export-pdf-entity'
                )
                || 'parties';

            downloadRegistryExport(
                pdfButton,
                `/api/registry/export/pdf?entity=${encodeURIComponent(entity)}`,
                'pdf',
                `registry-${entity}.pdf`
            );
        }
    );

    document
        .getElementById(
            'settings-import-run'
        )
        ?.addEventListener(
            'click',
            runRegistryImport
        );
}

/**
 * Download one Registry export through an authenticated request.
 *
 * CSV/XLSX save as files. The PDF review opens in a new tab instead,
 * because it exists for eyeballing a backup rather than restoring it.
 *
 * @param {HTMLButtonElement} button
 * @param {string} endpoint
 * @param {'csv'|'xlsx'|'pdf'} format
 * @param {string} fallbackFilename
 */
async function downloadRegistryExport(
    button,
    endpoint,
    format,
    fallbackFilename
) {
    const originalLabel =
        button.textContent
            .trim();

    hideSettingsError();

    try {
        button.disabled =
            true;

        button.textContent =
            translate(
                'settings.exporting'
            );

        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            REGISTRY_MIME_TYPES[
                                format
                            ]
                            || '*/*',
                    },
                }
            );

        if (! response.ok) {
            await parseJsonResponse(
                response
            );

            return;
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        if (format === 'pdf') {
            window.open(
                url,
                '_blank',
                'noopener'
            );
        } else {
            const link =
                document.createElement(
                    'a'
                );

            link.href =
                url;

            link.download =
                attachmentFilename(
                    response,
                    fallbackFilename
                );

            document.body.appendChild(
                link
            );

            link.click();

            link.remove();
        }

        /*
         * Revocation is deferred briefly so the browser has finished
         * consuming the object URL before it is released.
         */
        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
        );
    } catch (error) {
        showSettingsError(
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_export'
                )
        );
    } finally {
        button.disabled =
            false;

        button.textContent =
            originalLabel;
    }
}

/**
 * Prefer the attachment filename supplied by Laravel.
 *
 * @param {Response} response
 * @param {string} fallback
 * @returns {string}
 */
function attachmentFilename(
    response,
    fallback
) {
    const disposition =
        response.headers.get(
            'Content-Disposition'
        )
        || '';

    const match =
        disposition.match(
            /filename\s*=\s*"?([^";]+)"?/i
        );

    return match?.[1]?.trim()
        || fallback;
}

/**
 * Restore a Registry backup from the selected file.
 *
 * entity=full posts the multi-sheet workbook to the dedicated full
 * restore endpoint; every other entity posts to the per-entity one.
 */
async function runRegistryImport() {
    const fileInput =
        document.getElementById(
            'settings-import-file'
        );

    const button =
        document.getElementById(
            'settings-import-run'
        );

    if (
        ! fileInput
        || ! button
    ) {
        return;
    }

    hideSettingsError();
    hideImportResult();

    const file =
        fileInput.files?.[0];

    if (! file) {
        showSettingsError(
            translate(
                'settings.import_select_file'
            )
        );

        return;
    }

    const entity =
        formValue(
            'settings-import-entity'
        )
        || 'parties';

    const dryRun =
        Boolean(
            document.getElementById(
                'settings-import-dry-run'
            )?.checked
        );

    const body =
        new FormData();

    body.append(
        'file',
        file
    );

    body.append(
        'dry_run',
        dryRun
            ? '1'
            : '0'
    );

    if (entity !== 'full') {
        body.append(
            'entity',
            entity
        );
    }

    const originalLabel =
        button.textContent
            .trim();

    try {
        button.disabled =
            true;

        button.textContent =
            translate(
                'settings.importing'
            );

        const response =
            await apiRequest(
                entity === 'full'
                    ? '/api/registry/import/full'
                    : '/api/registry/import',
                {
                    method:
                        'POST',

                    body,
                }
            );

        const result =
            await parseJsonResponse(
                response
            );

        renderImportResult(
            entity,
            result
        );
    } catch (error) {
        showSettingsError(
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_import'
                )
        );
    } finally {
        button.disabled =
            false;

        button.textContent =
            originalLabel;
    }
}

/**
 * Markup for one entity's import counts.
 *
 * @param {string|null} entityLabel
 * @param {{created: number, updated: number, unchanged: number, skipped: Array}} counts
 * @returns {string}
 */
function importCountsMarkup(
    entityLabel,
    counts
) {
    const skipped =
        Array.isArray(
            counts.skipped
        )
            ? counts.skipped
            : [];

    const countItem = (
        labelKey,
        value
    ) => `
        <div>
            <dt
                class="
                    text-xs font-medium
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        labelKey
                    )
                )}
            </dt>

            <dd
                class="
                    mt-0.5 text-sm font-semibold
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(
                    String(
                        Number(
                            value
                            || 0
                        )
                    )
                )}
            </dd>
        </div>
    `;

    return `
        <div class="mt-3 first:mt-0">
            ${
                entityLabel
                    ? `
                        <div
                            class="
                                text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(entityLabel)}
                        </div>
                    `
                    : ''
            }

            <dl
                class="
                    mt-2 grid grid-cols-2 gap-3
                    sm:grid-cols-4
                "
            >
                ${countItem(
                    'settings.import_created',
                    counts.created
                )}

                ${countItem(
                    'settings.import_updated',
                    counts.updated
                )}

                ${countItem(
                    'settings.import_unchanged',
                    counts.unchanged
                )}

                ${countItem(
                    'settings.import_skipped',
                    skipped.length
                )}
            </dl>

            ${
                skipped.length > 0
                    ? `
                        <ul
                            class="
                                mt-2 list-disc space-y-1
                                pl-5 text-xs
                                text-[var(--pm-danger-text)]
                            "
                        >
                            ${skipped
                                .map(
                                    (entry) => `
                                        <li>
                                            ${escapeHtml(
                                                translate(
                                                    'settings.import_skipped_row',
                                                    {
                                                        row:
                                                            entry?.row
                                                            ?? '',

                                                        reason:
                                                            entry?.reason
                                                            ?? '',
                                                    }
                                                )
                                            )}
                                        </li>
                                    `
                                )
                                .join('')}
                        </ul>
                    `
                    : ''
            }
        </div>
    `;
}

/**
 * Render the import outcome panel.
 *
 * Per-entity imports return flat counts; the full restore returns
 * {results: {entity: counts}}.
 */
function renderImportResult(
    entity,
    result
) {
    const panel =
        document.getElementById(
            'settings-import-result'
        );

    if (! panel) {
        return;
    }

    const dryRunNotice =
        result.dry_run
            ? `
                <div
                    class="
                        mt-1 text-xs font-medium
                        text-[var(--pm-info-text)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'settings.import_dry_run_notice'
                        )
                    )}
                </div>
            `
            : '';

    const sections =
        entity === 'full'
            ? Object
                .entries(
                    result.results
                    || {}
                )
                .map(
                    ([name, counts]) =>
                        importCountsMarkup(
                            translate(
                                `settings.entity_${name}`
                            ),
                            counts
                            || {}
                        )
                )
                .join('')
            : importCountsMarkup(
                null,
                result
            );

    panel.innerHTML = `
        <div
            class="
                text-sm font-semibold
                text-[var(--pm-text)]
            "
        >
            ${escapeHtml(
                translate(
                    'settings.import_result_heading'
                )
            )}
        </div>

        ${dryRunNotice}

        ${sections}
    `;

    panel.classList.remove(
        'hidden'
    );
}

function hideImportResult() {
    const panel =
        document.getElementById(
            'settings-import-result'
        );

    if (! panel) {
        return;
    }

    panel.innerHTML = '';

    panel.classList.add(
        'hidden'
    );
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
                : translate(
                    'settings.unable_to_load'
                )
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
        'organisation-default-vat-rate',
        organisation.default_vat_rate
        ?? '18.00'
    );

    setFormValue(
        'organisation-language',
        organisation.language
        ?? 'en'
    );

    setFormValue(
        'organisation-currency',
        organisation.currency
        ?? 'GHS'
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

    const currentPresentation =
        getPresentationConfiguration();

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


        default_vat_rate:
            Number(
                formValue(
                    'organisation-default-vat-rate'
                )
            ),

        language:
            formValue(
                'organisation-language'
            ),

        currency:
            formValue(
                'organisation-currency'
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
            translate(
                'settings.saving'
            );

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

        const presentationChanged =
            organisation.language
                !== currentPresentation.language
            || organisation.currency
                !== currentPresentation.currency;

        if (presentationChanged) {
            /*
             * Browser presentation configuration is cached for this page.
             * Reload once so every static and dynamic surface adopts the
             * newly persisted organisation language/currency consistently.
             */
            window.location.reload();

            return;
        }

        showSettingsSuccess(
            translate(
                'settings.saved'
            )
        );
    } catch (error) {
        showSettingsError(
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_to_save'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            translate(
                'settings.save'
            );
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
