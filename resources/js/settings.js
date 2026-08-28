/*
|--------------------------------------------------------------------------
| Patrimoine Settings
|--------------------------------------------------------------------------
|
| Browser-side functionality for application-wide Patrimoine settings.
|
| V1.0.9 layout: four hash-deep-linked tabs (Organisation, Preferences,
| Data, About) following the Help page tablist pattern.
|
| Patrimoine 1.0 currently supports one Managing Organisation. The
| backend represents that organisation as a Party and stores its Party
| ID in ApplicationSetting. This module intentionally works only through
| the dedicated /api/managing-organisation endpoint instead of
| manipulating Party records directly.
|
| The Organisation and Preferences tabs edit the SAME record: the module
| loads it once and every save — from either tab — sends one merged
| payload built from both tabs' fields.
|
*/

import {
    apiRequest,
    closeDrawer,
    escapeHtml,
    formValue,
    getPresentationConfiguration,
    loadPresentationConfiguration,
    nullableFormValue,
    openDrawer,
    openPdfInNewTab,
    parseJsonResponse,
    restoreButton,
    setButtonBusy,
    translate,
    wireDrawer,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Tab Registry
|--------------------------------------------------------------------------
|
| Tab names double as the location hash (/settings#data). Each tab owns
| a #settings-tab-{name} pill, a #settings-{name}-panel section, and —
| for the interactive tabs — #settings-{name}-error / -success feedback
| regions rendered near its controls.
|
*/

const SETTINGS_TABS = [
    'organisation',
    'preferences',
    'data',
    'about',
];

const DEFAULT_SETTINGS_TAB =
    'organisation';

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

    initializeSettingsTabs();
    initializeAboutSection();
    initializeRegistryPortability();
    initializeOrganisationForms();

    applySettingsLocationHash();

    window.addEventListener(
        'hashchange',
        applySettingsLocationHash
    );

    await loadManagingOrganisationSettings();
}

/*
|--------------------------------------------------------------------------
| Tabs
|--------------------------------------------------------------------------
*/

function initializeSettingsTabs() {
    SETTINGS_TABS.forEach(
        (tab) => {
            document
                .getElementById(
                    `settings-tab-${tab}`
                )
                ?.addEventListener(
                    'click',
                    () => {
                        /*
                         * The default tab keeps a clean URL; every other
                         * tab records its hash for deep linking.
                         */
                        window.history.replaceState(
                            null,
                            '',
                            tab === DEFAULT_SETTINGS_TAB
                                ? window.location.pathname
                                    + window.location.search
                                : `#${tab}`
                        );

                        selectSettingsTab(
                            tab
                        );
                    }
                );
        }
    );
}

/**
 * Reflect the current location hash into the tab state.
 *
 * Unknown hashes fall back to the default Organisation tab so stale
 * links never leave the page blank.
 */
function applySettingsLocationHash() {
    const requested =
        window.location.hash
            .replace(
                '#',
                ''
            );

    selectSettingsTab(
        SETTINGS_TABS.includes(
            requested
        )
            ? requested
            : DEFAULT_SETTINGS_TAB
    );
}

/**
 * Activate one settings tab pill and reveal its panel.
 *
 * @param {string} activeTab
 */
function selectSettingsTab(
    activeTab
) {
    const activeClasses = [
        'bg-[var(--pm-surface)]',
        'text-[var(--pm-text)]',
        'shadow-sm',
    ];

    const inactiveClasses = [
        'text-[var(--pm-text-muted)]',
        'hover:text-[var(--pm-text)]',
    ];

    SETTINGS_TABS.forEach(
        (tab) => {
            const active =
                tab === activeTab;

            const button =
                document.getElementById(
                    `settings-tab-${tab}`
                );

            if (button) {
                button.setAttribute(
                    'aria-selected',
                    active
                        ? 'true'
                        : 'false'
                );

                button.classList.remove(
                    ...activeClasses,
                    ...inactiveClasses
                );

                button.classList.add(
                    ...(
                        active
                            ? activeClasses
                            : inactiveClasses
                    )
                );
            }

            document
                .getElementById(
                    `settings-${tab}-panel`
                )
                ?.classList.toggle(
                    'hidden',
                    ! active
                );
        }
    );
}

/*
|--------------------------------------------------------------------------
| Per-tab Feedback
|--------------------------------------------------------------------------
|
| Every interactive tab renders its own inline error and success boxes
| next to the controls that produced the outcome. Handlers always clear
| BOTH boxes before starting so stale feedback can never linger.
|
*/

/**
 * Display an error inside one tab's feedback region.
 *
 * @param {string} tab
 * @param {string} message
 */
function showTabError(
    tab,
    message
) {
    clearTabFeedback(
        tab
    );

    const box =
        document.getElementById(
            `settings-${tab}-error`
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
 * Display a success notice inside one tab's feedback region.
 *
 * @param {string} tab
 * @param {string} message
 */
function showTabSuccess(
    tab,
    message
) {
    clearTabFeedback(
        tab
    );

    const box =
        document.getElementById(
            `settings-${tab}-success`
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
 * Clear BOTH the error and the success box of one tab.
 *
 * @param {string} tab
 */
function clearTabFeedback(
    tab
) {
    [
        `settings-${tab}-error`,
        `settings-${tab}-success`,
    ].forEach(
        (id) => {
            const box =
                document.getElementById(
                    id
                );

            if (box) {
                box.textContent = '';

                box.classList.add(
                    'hidden'
                );
            }
        }
    );
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
| V1.0.9 restore flow: the dry run is mandatory. The operator uploads a
| file, runs the dry run, reviews its counts, and only then may apply
| the real restore — behind a confirmation drawer. Changing the file or
| the data set invalidates the pending dry-run state.
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

/**
 * The successfully completed dry run awaiting confirmation, or null.
 *
 * @type {{
 *     entity: string,
 *     fileName: string,
 *     result: object,
 * }|null}
 */
let pendingDryRun =
    null;

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
                const entity =
                    button.dataset.entity;

                const format =
                    button.dataset.format;

                /*
                 * PDF opens through a signed document link on its own
                 * endpoint; CSV/XLSX are direct authenticated downloads.
                 */
                const endpoint =
                    format === 'pdf'
                        ? `/api/registry/export/pdf?entity=${encodeURIComponent(entity)}`
                        : `/api/registry/export?entity=${encodeURIComponent(entity)}&format=${encodeURIComponent(format)}`;

                button.addEventListener(
                    'click',
                    () =>
                        downloadRegistryExport(
                            button,
                            endpoint,
                            format,
                            `registry-${entity}.${format}`
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

    /*
     * Restore controls: dry run, invalidation and the confirmation
     * drawer around the destructive apply step.
     */
    document
        .getElementById(
            'settings-import-run'
        )
        ?.addEventListener(
            'click',
            runRegistryDryRun
        );

    document
        .getElementById(
            'settings-import-file'
        )
        ?.addEventListener(
            'change',
            () => {
                reflectSelectedImportFileName();

                invalidatePendingDryRun();
            }
        );

    document
        .getElementById(
            'settings-import-entity'
        )
        ?.addEventListener(
            'change',
            invalidatePendingDryRun
        );

    document
        .getElementById(
            'settings-import-apply'
        )
        ?.addEventListener(
            'click',
            openRestoreConfirmation
        );

    wireDrawer(
        'settings-restore-drawer',
        {
            closers: [
                'settings-restore-cancel',
                'settings-restore-close',
            ],
        }
    );

    document
        .getElementById(
            'settings-restore-confirm'
        )
        ?.addEventListener(
            'click',
            applyRegistryRestore
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
    /*
     * Every registry handler clears BOTH stale success and stale error
     * feedback in the Data tab before starting.
     */
    clearTabFeedback(
        'data'
    );

    try {
        setButtonBusy(
            button,
            'settings.exporting'
        );

        /*
         * The PDF review opens in a tab through a signed document link
         * so the browser can stream it natively. CSV/XLSX remain
         * authenticated blob downloads.
         */
        if (format === 'pdf') {
            await openPdfInNewTab(
                endpoint,
                translate(
                    'settings.unable_export'
                )
            );

            showTabSuccess(
                'data',
                translate(
                    'settings.export_opened'
                )
            );

            return;
        }

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

        showTabSuccess(
            'data',
            translate(
                'settings.export_success'
            )
        );
    } catch (error) {
        showTabError(
            'data',
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_export'
                )
        );
    } finally {
        restoreButton(
            button
        );
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
 * Mirror the hidden file input's selection into the visible name label.
 *
 * The label carries data-i18n for its empty state, so the attribute is
 * removed while a real file name is displayed and restored when the
 * selection is cleared — keeping client-side language switching correct.
 */
function reflectSelectedImportFileName() {
    const nameElement =
        document.getElementById(
            'settings-import-file-name'
        );

    if (! nameElement) {
        return;
    }

    const file = selectedImportFile();

    if (file) {
        nameElement.removeAttribute(
            'data-i18n'
        );

        nameElement.textContent =
            file.name;

        return;
    }

    nameElement.setAttribute(
        'data-i18n',
        'settings.no_file_selected'
    );

    nameElement.textContent =
        translate(
            'settings.no_file_selected'
        );
}

/**
 * The currently selected restore file, or null.
 *
 * @returns {File|null}
 */
function selectedImportFile() {
    return document.getElementById(
        'settings-import-file'
    )?.files?.[0]
        ?? null;
}

/**
 * The currently selected restore data set.
 *
 * @returns {string}
 */
function selectedImportEntity() {
    return formValue(
        'settings-import-entity'
    )
        || 'parties';
}

/**
 * Forget any completed dry run.
 *
 * Called whenever the file or the data-set selection changes: the
 * pending dry-run counts no longer describe what an apply would do.
 */
function invalidatePendingDryRun() {
    pendingDryRun =
        null;

    hideImportResult();

    document
        .getElementById(
            'settings-import-apply-row'
        )
        ?.classList.add(
            'hidden'
        );
}

/**
 * Validate the selected restore file and run the mandatory dry run.
 *
 * entity=full posts the multi-sheet workbook to the dedicated full
 * restore endpoint; every other entity posts to the per-entity one.
 * A successful dry run reveals the destructive "Apply this restore"
 * button.
 */
async function runRegistryDryRun() {
    const button =
        document.getElementById(
            'settings-import-run'
        );

    if (! button) {
        return;
    }

    clearTabFeedback(
        'data'
    );

    invalidatePendingDryRun();

    const file =
        selectedImportFile();

    if (! file) {
        showTabError(
            'data',
            translate(
                'settings.import_select_file'
            )
        );

        return;
    }

    const entity =
        selectedImportEntity();

    /*
     * A full restore reads one workbook with one sheet per entity —
     * only .xlsx can carry that structure. Reject anything else before
     * uploading.
     */
    if (
        entity === 'full'
        && ! file.name
            .toLowerCase()
            .endsWith('.xlsx')
    ) {
        showTabError(
            'data',
            translate(
                'settings.full_requires_xlsx'
            )
        );

        return;
    }

    const body =
        new FormData();

    body.append(
        'file',
        file
    );

    body.append(
        'dry_run',
        '1'
    );

    if (entity !== 'full') {
        body.append(
            'entity',
            entity
        );
    }

    try {
        setButtonBusy(
            button,
            'settings.dry_run_running'
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

        /*
         * The dry run succeeded: remember it and offer the apply step.
         */
        pendingDryRun = {
            entity,

            fileName:
                file.name,

            result,
        };

        document
            .getElementById(
                'settings-import-apply-row'
            )
            ?.classList.remove(
                'hidden'
            );
    } catch (error) {
        showTabError(
            'data',
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_import'
                )
        );
    } finally {
        restoreButton(
            button
        );
    }
}

/**
 * Markup for one labelled line of the confirmation summary.
 *
 * @param {string} labelKey
 * @param {string} value
 * @returns {string}
 */
function restoreSummaryRow(
    labelKey,
    value
) {
    return `
        <div class="mt-2 first:mt-0">
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
                    break-words
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(
                    value
                )}
            </dd>
        </div>
    `;
}

/**
 * One compact translated counts line, e.g. "Created 1 · Updated 0 …".
 *
 * @param {{created: number, updated: number, unchanged: number, skipped: Array}} counts
 * @returns {string}
 */
function restoreSummaryCountsLine(
    counts
) {
    const skipped =
        Array.isArray(
            counts.skipped
        )
            ? counts.skipped.length
            : 0;

    return [
        [
            'settings.import_created',
            counts.created,
        ],
        [
            'settings.import_updated',
            counts.updated,
        ],
        [
            'settings.import_unchanged',
            counts.unchanged,
        ],
        [
            'settings.import_skipped',
            skipped,
        ],
    ]
        .map(
            ([key, value]) =>
                `${translate(key)} ${Number(value || 0)}`
        )
        .join(' · ');
}

/**
 * Fill the confirmation drawer with the pending dry run's summary:
 * file name, data set and the counts the real restore would produce.
 */
function renderRestoreSummary() {
    const container =
        document.getElementById(
            'settings-restore-summary'
        );

    if (
        ! container
        || ! pendingDryRun
    ) {
        return;
    }

    const countsMarkup =
        pendingDryRun.entity === 'full'
            ? Object
                .entries(
                    pendingDryRun.result.results
                    || {}
                )
                .map(
                    ([name, counts]) => `
                        <div class="mt-2 first:mt-0">
                            <div
                                class="
                                    text-xs font-medium
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(
                                    translate(
                                        `settings.entity_${name}`
                                    )
                                )}
                            </div>

                            <div
                                class="
                                    mt-0.5 text-sm
                                    text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(
                                    restoreSummaryCountsLine(
                                        counts
                                        || {}
                                    )
                                )}
                            </div>
                        </div>
                    `
                )
                .join('')
            : `
                <div
                    class="
                        text-sm
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        restoreSummaryCountsLine(
                            pendingDryRun.result
                        )
                    )}
                </div>
            `;

    container.innerHTML = `
        <dl>
            ${restoreSummaryRow(
                'settings.import_file',
                pendingDryRun.fileName
            )}

            ${restoreSummaryRow(
                'settings.import_entity',
                translate(
                    `settings.entity_${pendingDryRun.entity}`
                )
            )}
        </dl>

        <div
            class="
                mt-4 border-t
                border-[var(--pm-border)]
                pt-3
            "
        >
            <div
                class="
                    text-xs font-semibold uppercase
                    tracking-wide
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'settings.import_result_heading'
                    )
                )}
            </div>

            <div class="mt-2">
                ${countsMarkup}
            </div>
        </div>
    `;
}

/**
 * Open the destructive confirmation drawer for the pending dry run.
 */
function openRestoreConfirmation() {
    if (! pendingDryRun) {
        return;
    }

    /*
     * Defensive guard: the selection must still match the dry run the
     * operator is about to apply. Selection change listeners already
     * invalidate the state, so a mismatch here means the file itself
     * was cleared.
     */
    const file =
        selectedImportFile();

    if (
        ! file
        || file.name !== pendingDryRun.fileName
        || selectedImportEntity() !== pendingDryRun.entity
    ) {
        invalidatePendingDryRun();

        showTabError(
            'data',
            translate(
                'settings.import_select_file'
            )
        );

        return;
    }

    renderRestoreSummary();

    openDrawer(
        'settings-restore-drawer'
    );
}

/**
 * The confirmed destructive step: POST the real (non-dry-run) import.
 */
async function applyRegistryRestore() {
    const button =
        document.getElementById(
            'settings-restore-confirm'
        );

    if (
        ! button
        || ! pendingDryRun
    ) {
        return;
    }

    const file =
        selectedImportFile();

    if (
        ! file
        || file.name !== pendingDryRun.fileName
    ) {
        closeDrawer(
            'settings-restore-drawer'
        );

        invalidatePendingDryRun();

        showTabError(
            'data',
            translate(
                'settings.import_select_file'
            )
        );

        return;
    }

    const entity =
        pendingDryRun.entity;

    const body =
        new FormData();

    body.append(
        'file',
        file
    );

    body.append(
        'dry_run',
        '0'
    );

    if (entity !== 'full') {
        body.append(
            'entity',
            entity
        );
    }

    try {
        setButtonBusy(
            button,
            'settings.restoring'
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

        closeDrawer(
            'settings-restore-drawer'
        );

        /*
         * The restore is done: show its final counts and retire the
         * pending dry-run state (the apply button disappears with it).
         */
        pendingDryRun =
            null;

        document
            .getElementById(
                'settings-import-apply-row'
            )
            ?.classList.add(
                'hidden'
            );

        renderImportResult(
            entity,
            result
        );

        showTabSuccess(
            'data',
            translate(
                'settings.restore_success'
            )
        );
    } catch (error) {
        closeDrawer(
            'settings-restore-drawer'
        );

        showTabError(
            'data',
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_import'
                )
        );
    } finally {
        restoreButton(
            button
        );
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
| Organisation & Preferences Forms
|--------------------------------------------------------------------------
*/

/**
 * Wire both Managing Organisation forms.
 *
 * Each tab has its own form and save button, but both submit the same
 * merged payload — the handlers differ only in which tab receives the
 * inline feedback.
 */
function initializeOrganisationForms() {
    document
        .getElementById(
            'managing-organisation-form'
        )
        ?.addEventListener(
            'submit',
            (event) =>
                submitManagingOrganisation(
                    event,
                    'organisation'
                )
        );

    document
        .getElementById(
            'settings-preferences-form'
        )
        ?.addEventListener(
            'submit',
            (event) =>
                submitManagingOrganisation(
                    event,
                    'preferences'
                )
        );
}

/**
 * Enable or disable both tab fieldsets.
 *
 * The forms stay disabled until the initial GET resolves so the
 * operator can never edit values that are about to be overwritten.
 *
 * @param {boolean} disabled
 */
function setOrganisationFieldsetsDisabled(
    disabled
) {
    [
        'managing-organisation-fieldset',
        'settings-preferences-fieldset',
    ].forEach(
        (id) => {
            const fieldset =
                document.getElementById(
                    id
                );

            if (fieldset) {
                fieldset.disabled =
                    disabled;
            }
        }
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
 * HTTP 404 is valid for a fresh installation: the form shows a neutral
 * "not configured yet" notice, receives the presentation-configuration
 * defaults, and becomes the initial organisation configuration form.
 */
async function loadManagingOrganisationSettings() {
    clearTabFeedback(
        'organisation'
    );

    clearTabFeedback(
        'preferences'
    );

    /*
     * The presentation configuration is the single fallback source for
     * language, currency and the default VAT rate, so it must be
     * available before any field is populated.
     */
    await loadPresentationConfiguration();

    try {
        const response =
            await apiRequest(
                '/api/managing-organisation'
            );

        if (response.status === 404) {
            /*
             * Fresh installation: nothing to load. Announce it instead
             * of leaving a silently empty form, and pre-fill the
             * preference fields from the presentation defaults so the
             * required inputs carry sensible starting values.
             */
            document
                .getElementById(
                    'settings-not-configured'
                )
                ?.classList.remove(
                    'hidden'
                );

            populateManagingOrganisationForm(
                {}
            );

            return;
        }

        const organisation =
            await parseJsonResponse(
                response
            );

        document
            .getElementById(
                'settings-not-configured'
            )
            ?.classList.add(
                'hidden'
            );

        populateManagingOrganisationForm(
            organisation
        );
    } catch (error) {
        const message =
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_to_load'
                );

        showTabError(
            'organisation',
            message
        );

        showTabError(
            'preferences',
            message
        );
    } finally {
        /*
         * The GET has resolved one way or another — the fields become
         * editable even after a failure so the operator is never
         * locked out of correcting the configuration.
         */
        setOrganisationFieldsetsDisabled(
            false
        );
    }
}

/*
|--------------------------------------------------------------------------
| Form Population
|--------------------------------------------------------------------------
*/

/**
 * Populate both tabs' fields from the Managing Organisation Party
 * returned by the API.
 *
 * Missing preference values fall back to the loaded presentation
 * configuration — the one place that knows the application defaults —
 * instead of duplicating hardcoded values here.
 */
function populateManagingOrganisationForm(
    organisation
) {
    const presentation =
        getPresentationConfiguration();

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
        ?? presentation.default_vat_rate
    );

    setFormValue(
        'organisation-language',
        organisation.language
        ?? presentation.language
    );

    setFormValue(
        'organisation-currency',
        organisation.currency
        ?? presentation.currency
    );

    /*
     * V1.0.29: absent means "sending", which is what every organisation
     * did before the switch existed.
     */
    const partyEmails =
        document.getElementById(
            'organisation-party-emails-enabled'
        );

    if (partyEmails) {
        partyEmails.checked =
            organisation.party_emails_enabled ?? true;
    }

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
 * Validate both forms before a merged save.
 *
 * A form living inside a hidden tab panel cannot anchor the browser's
 * validation bubble, so when the OTHER form is the invalid one its tab
 * is activated first and the bubble is shown there.
 *
 * @returns {boolean}
 */
function validateOrganisationForms() {
    const forms = [
        [
            'organisation',
            document.getElementById(
                'managing-organisation-form'
            ),
        ],
        [
            'preferences',
            document.getElementById(
                'settings-preferences-form'
            ),
        ],
    ];

    for (const [tab, form] of forms) {
        if (
            form
            && ! form.checkValidity()
        ) {
            selectSettingsTab(
                tab
            );

            window.history.replaceState(
                null,
                '',
                tab === DEFAULT_SETTINGS_TAB
                    ? window.location.pathname
                        + window.location.search
                    : `#${tab}`
            );

            form.reportValidity();

            return false;
        }
    }

    return true;
}

/**
 * Create or update the singleton Managing Organisation.
 *
 * The API decides whether a new underlying Party needs to be created or
 * whether the existing Managing Organisation Party should be updated.
 * Both tabs submit the SAME merged payload; only the feedback region
 * and the busy button differ.
 *
 * @param {SubmitEvent} event
 * @param {'organisation'|'preferences'} tab
 */
async function submitManagingOrganisation(
    event,
    tab
) {
    event.preventDefault();

    const submitButton =
        document.getElementById(
            tab === 'preferences'
                ? 'settings-preferences-submit-button'
                : 'managing-organisation-submit-button'
        );

    if (! submitButton) {
        return;
    }

    clearTabFeedback(
        'organisation'
    );

    clearTabFeedback(
        'preferences'
    );

    if (! validateOrganisationForms()) {
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

        party_emails_enabled:
            document.getElementById(
                'organisation-party-emails-enabled'
            )?.checked
            ?? true,

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
        setButtonBusy(
            submitButton,
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
         * The organisation exists now, whatever it was before.
         */
        document
            .getElementById(
                'settings-not-configured'
            )
            ?.classList.add(
                'hidden'
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

        showTabSuccess(
            tab,
            translate(
                'settings.saved'
            )
        );
    } catch (error) {
        showTabError(
            tab,
            error instanceof Error
                ? error.message
                : translate(
                    'settings.unable_to_save'
                )
        );
    } finally {
        restoreButton(
            submitButton
        );
    }
}
