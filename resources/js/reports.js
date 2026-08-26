import {
    apiRequest,
    formatCurrency,
    formatDate,
    formatNumber,
    openPdfInNewTab,
    parseJsonResponse,
    translate,
} from './core.js';

import {
    dateForApi,
    dateForDisplay,
    initializeDateInputs,
} from './date-input.js';

/*
|--------------------------------------------------------------------------
| Patrimoine Reports Workspace
|--------------------------------------------------------------------------
|
| The Reports screen is a browser presentation layer over the existing
| read-only reporting API.
|
| Report calculations remain entirely within Laravel report services.
|
*/

/*
|--------------------------------------------------------------------------
| Report Type Registry
|--------------------------------------------------------------------------
|
| One declarative definition per report type. Everything the workspace
| needs to know about a type — labels, subject requirement, date handling,
| Payments filters, endpoints and renderer — lives here so behaviour never
| drifts between seven separate switches.
|
| dateMode:
|   'period' — From/To reporting period pair.
|   'asof'   — single optional as-of reference date.
|   'none'   — no date criteria at all.
|
| rowCount returns the meaningful primary row count for the results bar,
| or null when a single count would be misleading.
*/

const REPORT_TYPES = {
    'managing-organisation': {
        id: 'managing-organisation',
        titleKey: 'reports.managing_organisation_report',
        descriptionKey: 'reports.managing_organisation_description',
        subject: null,
        dateMode: 'period',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/managing-organisation',
        renderer: (report) => renderManagingOrganisationReport(report),
        rowCount: null,
    },

    owner: {
        id: 'owner',
        titleKey: 'reports.owner_report',
        descriptionKey: 'reports.owner_report_description',
        subject: 'owner',
        dateMode: 'period',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/owners',
        renderer: (report) => renderOwnerReport(report),
        rowCount: (report) =>
            Array.isArray(report?.transactions)
                ? report.transactions.length
                : null,
    },

    building: {
        id: 'building',
        titleKey: 'reports.building_report',
        descriptionKey: 'reports.building_report_description',
        subject: 'building',
        dateMode: 'period',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/buildings',
        renderer: (report) => renderBuildingReport(report),
        rowCount: null,
    },

    unit: {
        id: 'unit',
        titleKey: 'reports.unit_report',
        descriptionKey: 'reports.unit_report_description',
        subject: 'unit',
        dateMode: 'period',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/units',
        renderer: (report) => renderUnitReport(report),
        rowCount: null,
    },

    tenant: {
        id: 'tenant',
        titleKey: 'reports.tenant_statement',
        descriptionKey: 'reports.tenant_statement_description',
        subject: 'tenant',
        dateMode: 'period',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/tenants',
        renderer: (report) => renderTenantReport(report),
        rowCount: null,
    },

    payments: {
        id: 'payments',
        titleKey: 'reports.payments_report',
        descriptionKey: 'reports.payments_report_description',
        subject: null,
        dateMode: 'period',
        hasPaymentFilters: true,
        endpointBase: '/api/reports/payments',
        renderer: (report) => renderPaymentReport(report),
        rowCount: (report) =>
            Array.isArray(report?.payments)
                ? report.payments.length
                : null,
    },

    occupancy: {
        id: 'occupancy',
        titleKey: 'reports.occupancy_report',
        descriptionKey: 'reports.occupancy_report_description',
        subject: null,
        dateMode: 'asof',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/occupancy',
        renderer: (report) => renderOccupancyReport(report),
        rowCount: (report) =>
            Array.isArray(report?.buildings)
                ? report.buildings.length
                : null,
    },

    arrears: {
        id: 'arrears',
        titleKey: 'reports.arrears_report',
        descriptionKey: 'reports.arrears_report_description',
        subject: null,
        dateMode: 'asof',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/arrears',
        renderer: (report) => renderArrearsReport(report),
        rowCount: (report) =>
            Array.isArray(report?.tenants)
                ? report.tenants.length
                : null,
    },

    funds: {
        id: 'funds',
        titleKey: 'reports.funds_report',
        descriptionKey: 'reports.funds_report_description',
        subject: null,
        dateMode: 'none',
        hasPaymentFilters: false,
        endpointBase: '/api/reports/funds',
        renderer: (report) => renderFundsReport(report),
        rowCount: (report) =>
            (report?.tenant_funds?.tenants?.length ?? 0)
            + (report?.owner_funds?.owners?.length ?? 0),
    },
};

/*
 * Subject picker configuration for each subject kind used by the
 * registry above.
 */
const SUBJECT_KINDS = {
    owner: {
        labelKey: 'reports.property_owner',
        placeholderKey: 'reports.search_owner_placeholder',

        endpoint(search) {
            const params = subjectSearchParams(search);

            return `/api/owner-accounts?${params.toString()}`;
        },

        normalize(rows) {
            return rows.map((account) => ({
                id: account.party_id,
                apiId: account.party_id,
                name: partyDisplayName(account.party ?? {}),
                meta: contactSummary(account.party ?? {}),
                secondary: formatCurrency(account.balance ?? 0),
            }));
        },
    },

    tenant: {
        labelKey: 'reports.tenant',
        placeholderKey: 'reports.search_tenant_placeholder',

        endpoint(search) {
            const params = subjectSearchParams(search);

            params.set('role', 'tenant');

            return `/api/parties?${params.toString()}`;
        },

        normalize(rows) {
            return rows.map((party) => ({
                id: party.id,
                apiId: party.id,
                name: partyDisplayName(party),
                meta: contactSummary(party),
            }));
        },
    },

    building: {
        labelKey: 'reports.building',
        placeholderKey: 'reports.search_building_placeholder',

        endpoint(search) {
            const params = subjectSearchParams(search);

            return `/api/buildings?${params.toString()}`;
        },

        normalize(rows) {
            return rows.map((building) => ({
                id: building.id,
                apiId: building.id,
                name: building.name
                    ?? translate('reports.building_number', {
                        number: building.id,
                    }),
                meta: [
                    building.location,
                    building.address,
                ]
                    .filter(Boolean)
                    .join(' · '),
            }));
        },
    },

    unit: {
        labelKey: 'reports.unit',
        placeholderKey: 'reports.search_unit_placeholder',

        endpoint(search) {
            const params = subjectSearchParams(search);

            return `/api/units?${params.toString()}`;
        },

        normalize(rows) {
            return rows.map((unit) => ({
                id: unit.id,
                apiId: unit.id,
                name: unit.name
                    ?? translate('reports.unit_number', {
                        number: unit.id,
                    }),
                meta: unit?.building?.name ?? '',
            }));
        },
    },
};

/*
 * Derived report-type groupings. These are computed from the registry so
 * they can never disagree with it.
 */
const SUBJECTLESS_REPORT_TYPES =
    Object.values(REPORT_TYPES)
        .filter((definition) => definition.subject === null)
        .map((definition) => definition.id);

const AS_OF_REPORT_TYPES =
    Object.values(REPORT_TYPES)
        .filter((definition) => definition.dateMode === 'asof')
        .map((definition) => definition.id);

const PERIODLESS_REPORT_TYPES =
    Object.values(REPORT_TYPES)
        .filter((definition) => definition.dateMode !== 'period')
        .map((definition) => definition.id);

function reportDefinition(type = null) {
    return REPORT_TYPES[type ?? selectedReportType]
        ?? REPORT_TYPES['managing-organisation'];
}

/*
|--------------------------------------------------------------------------
| Workspace State
|--------------------------------------------------------------------------
*/

let selectedReportType = 'managing-organisation';

let selectedSubject = null;

let searchTimer = null;

let activeJsonEndpoint = null;

let activePdfEndpoint = null;

let activeCsvEndpoint = null;

let activeXlsxEndpoint = null;

/*
 * Stale-on-change invalidation: once a report has been rendered, any
 * criteria change marks the output stale until the report is re-run.
 */
let hasResults = false;

let resultsStale = false;

/*
|--------------------------------------------------------------------------
| Initialization
|--------------------------------------------------------------------------
*/

/**
 * Initialize Reports workspace.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeReports() {
    const output = document.getElementById('report-output');

    if (! output) {
        return false;
    }

    initializeReportTypeButtons();

    initializeReportTypeSelect();

    initializeSubjectSearch();

    initializePeriodControls();

    initializeDateInputs('[data-report-date-input]');

    initializeReportDatePickers();

    initializeExportActions();

    initializeStaleInvalidation();

    initializeResetFilters();

    updateReportTypeUi();

    /*
     * Internal workspaces may link directly to a specific report type or
     * subject. Resolve that context only after all controls exist.
     */
    await initializeReportDeepLink();

    return true;
}

/*
|--------------------------------------------------------------------------
| Internal Report Deep Links
|--------------------------------------------------------------------------
*/

/**
 * Resolve supported report context supplied in the page URL.
 *
 * `?type=<registry id>` pre-selects that report type. The V1.0.1 Tenant
 * Management deep link (`?type=tenant&tenant_id=<id>`) additionally
 * resolves the Tenant subject and runs the statement immediately.
 */
async function initializeReportDeepLink() {
    const parameters =
        new URLSearchParams(window.location.search);

    const type = parameters.get('type');

    if (
        ! type
        || ! Object.prototype.hasOwnProperty.call(REPORT_TYPES, type)
    ) {
        return;
    }

    selectReportType(type);

    if (type !== 'tenant') {
        return;
    }

    const tenantId = Number(parameters.get('tenant_id'));

    if (
        ! Number.isInteger(tenantId)
        || tenantId <= 0
    ) {
        return;
    }

    try {
        const response =
            await apiRequest(`/api/parties/${tenantId}`);

        const tenant =
            await parseJsonResponse(response);

        const isTenant =
            Array.isArray(tenant.roles)
            && tenant.roles.some(
                (role) => role.role === 'tenant'
            );

        if (! isTenant) {
            throw new Error(
                translate('reports.not_tenant')
            );
        }

        selectSubject({
            id: tenant.id,
            apiId: tenant.id,
            name: partyDisplayName(tenant),
            meta: contactSummary(tenant),
        });

        await runReport();
    } catch (error) {
        /*
         * A failed deep link is a page-boot failure, so the top-level
         * banner remains the correct surface for it.
         */
        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_open_tenant_statement')
        );

        renderReportError();
    }
}

/**
 * Reflect the executed report criteria in the page URL so the current
 * report can be shared or reloaded.
 */
function reflectUrlState(from, to, asOf) {
    const definition = reportDefinition();

    const parameters =
        new URLSearchParams(window.location.search);

    parameters.set('type', definition.id);

    ['from', 'to', 'as_of', 'tenant_id'].forEach(
        (key) => parameters.delete(key)
    );

    if (definition.dateMode === 'period') {
        if (from) {
            parameters.set('from', from);
        }

        if (to) {
            parameters.set('to', to);
        }
    }

    if (definition.dateMode === 'asof' && asOf) {
        parameters.set('as_of', asOf);
    }

    if (
        definition.id === 'tenant'
        && selectedSubject?.apiId
    ) {
        parameters.set(
            'tenant_id',
            String(selectedSubject.apiId)
        );
    }

    const query = parameters.toString();

    window.history.replaceState(
        null,
        '',
        query
            ? `${window.location.pathname}?${query}`
            : window.location.pathname
    );
}

/*
|--------------------------------------------------------------------------
| Report Type Selection
|--------------------------------------------------------------------------
*/

function initializeReportTypeButtons() {
    document
        .querySelectorAll('[data-report-type]')
        .forEach((button) => {
            button.addEventListener('click', () => {
                selectReportType(
                    button.dataset.reportType
                );
            });
        });
}

/**
 * Compact type selector shown below the xl breakpoint instead of the
 * full-height card list. Both stay in sync through updateReportTypeUi().
 */
function initializeReportTypeSelect() {
    const select =
        document.getElementById('report-type-select');

    if (! select) {
        return;
    }

    select.addEventListener('change', () => {
        selectReportType(select.value);
    });
}

/**
 * Single entry point for switching the active report type.
 *
 * Switching resets the subject, Payments filters and date criteria to
 * their defaults and clears any previous error banner.
 */
function selectReportType(type) {
    if (
        ! Object.prototype.hasOwnProperty.call(REPORT_TYPES, type)
    ) {
        return;
    }

    selectedReportType = type;

    selectedSubject = null;

    clearSubjectSelection();

    resetReportCriteria();

    hideReportsError();

    clearReportOutput();

    updateReportTypeUi();
}

/**
 * Reset Payments filters and date criteria to their defaults.
 */
function resetReportCriteria() {
    [
        'payment-report-tenant',
        'payment-report-lease',
        'payment-report-building',
        'payment-report-unit',
        'payment-report-method',
        'payment-report-cash-receiver',
        'payment-report-reference',
        'report-from',
        'report-to',
        'report-as-of',
    ].forEach((id) => {
        setFieldValue(id, '');
    });
}

function initializeResetFilters() {
    document
        .getElementById('report-reset-filters')
        ?.addEventListener('click', () => {
            resetReportCriteria();

            markResultsStale();

            updateRunButton();
        });
}

function updateReportTypeUi() {
    const definition = reportDefinition();

    document
        .querySelectorAll('[data-report-type]')
        .forEach((button) => {
            button.classList.toggle(
                'is-active',
                button.dataset.reportType === definition.id
            );
        });

    const select =
        document.getElementById('report-type-select');

    if (select && select.value !== definition.id) {
        select.value = definition.id;
    }

    document
        .getElementById('report-subject-section')
        ?.classList.toggle(
            'hidden',
            definition.subject === null
        );

    document
        .getElementById('payment-report-filters')
        ?.classList.toggle(
            'hidden',
            ! definition.hasPaymentFilters
        );

    document
        .getElementById('report-period-fields')
        ?.classList.toggle(
            'hidden',
            definition.dateMode !== 'period'
        );

    document
        .getElementById('report-asof-field')
        ?.classList.toggle(
            'hidden',
            definition.dateMode !== 'asof'
        );

    if (definition.hasPaymentFilters) {
        loadPaymentReportFilterOptions();
    }

    updateSubjectLabels();

    updateReportHeader();

    updateRunButton();
}

function updateSubjectLabels() {
    const label =
        document.getElementById('report-subject-label');

    const input =
        document.getElementById('report-subject-search');

    if (! label || ! input) {
        return;
    }

    const kind =
        SUBJECT_KINDS[reportDefinition().subject];

    label.textContent =
        translate(kind?.labelKey ?? 'reports.search');

    input.placeholder =
        translate(kind?.placeholderKey ?? 'reports.search_placeholder');
}

function updateReportHeader() {
    const definition = reportDefinition();

    const title =
        document.getElementById('report-output-title');

    const subtitle =
        document.getElementById('report-output-subtitle');

    if (! title || ! subtitle) {
        return;
    }

    title.textContent =
        translate(definition.titleKey);

    subtitle.textContent =
        translate(definition.descriptionKey);
}

/*
|--------------------------------------------------------------------------
| Subject Search
|--------------------------------------------------------------------------
*/

let subjectSearchAbort = null;

let activeSubjectResults = [];

let activeSubjectIndex = -1;

function subjectSearchParams(search) {
    const params = new URLSearchParams();

    params.set('search', search);

    params.set('per_page', '10');

    return params;
}

function initializeSubjectSearch() {
    const input =
        document.getElementById('report-subject-search');

    if (! input) {
        return;
    }

    input.addEventListener('input', () => {
        if (selectedSubject) {
            clearSubjectSelection(false);
        }

        window.clearTimeout(searchTimer);

        const search = input.value.trim();

        if (search.length < 2) {
            hideSubjectResults();

            return;
        }

        searchTimer = window.setTimeout(
            async () => {
                await searchSubjects(search);
            },
            300
        );
    });

    input.addEventListener('keydown', (event) => {
        const container =
            document.getElementById('report-subject-results');

        const open =
            container
            && ! container.classList.contains('hidden');

        if (event.key === 'Escape') {
            hideSubjectResults();

            return;
        }

        if (! open) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();

            moveActiveSubjectOption(1);

            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();

            moveActiveSubjectOption(-1);

            return;
        }

        if (
            event.key === 'Enter'
            && activeSubjectIndex >= 0
            && activeSubjectResults[activeSubjectIndex]
        ) {
            event.preventDefault();

            selectSubject(
                activeSubjectResults[activeSubjectIndex]
            );
        }
    });

    /*
     * Close the picker whenever focus moves elsewhere on the page.
     */
    document.addEventListener('click', (event) => {
        const container =
            document.getElementById('report-subject-results');

        if (
            ! container
            || container.classList.contains('hidden')
        ) {
            return;
        }

        if (
            event.target !== input
            && ! container.contains(event.target)
        ) {
            hideSubjectResults();
        }
    });

    document
        .getElementById('report-clear-subject')
        ?.addEventListener('click', () => {
            clearSubjectSelection();

            input.focus();
        });
}

function moveActiveSubjectOption(step) {
    if (activeSubjectResults.length === 0) {
        return;
    }

    const count = activeSubjectResults.length;

    activeSubjectIndex =
        (activeSubjectIndex + step + count) % count;

    highlightActiveSubjectOption();
}

function highlightActiveSubjectOption() {
    const input =
        document.getElementById('report-subject-search');

    document
        .querySelectorAll('[data-report-subject-option]')
        .forEach((option, index) => {
            const active =
                index === activeSubjectIndex;

            option.setAttribute(
                'aria-selected',
                active ? 'true' : 'false'
            );

            option.classList.toggle(
                'bg-[var(--pm-hover)]',
                active
            );

            if (active) {
                input?.setAttribute(
                    'aria-activedescendant',
                    option.id
                );

                option.scrollIntoView({
                    block: 'nearest',
                });
            }
        });

    if (activeSubjectIndex < 0) {
        input?.removeAttribute('aria-activedescendant');
    }
}

async function searchSubjects(search) {
    const container =
        document.getElementById('report-subject-results');

    const kind =
        SUBJECT_KINDS[reportDefinition().subject];

    if (! container || ! kind) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                px-4 py-3
                text-sm text-[var(--pm-text-subtle)]
            "
        >
            ${escapeHtml(translate('reports.searching'))}
        </div>
    `;

    showSubjectResultsContainer();

    /*
     * Only the most recent search may render. Aborting the previous
     * request keeps slow responses from overwriting newer ones.
     */
    subjectSearchAbort?.abort();

    subjectSearchAbort = new AbortController();

    const { signal } = subjectSearchAbort;

    try {
        const response = await apiRequest(
            kind.endpoint(search),
            { signal }
        );

        const data =
            await parseJsonResponse(response);

        if (signal.aborted) {
            return;
        }

        const rows =
            Array.isArray(data?.data)
                ? data.data
                : [];

        renderSubjectResults(
            kind.normalize(rows)
        );
    } catch (error) {
        if (
            signal.aborted
            || error?.name === 'AbortError'
        ) {
            return;
        }

        container.innerHTML = `
            <div
                class="
                    px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            >
                ${escapeHtml(
                    error instanceof Error
                        ? error.message
                        : translate('reports.unable_to_search')
                )}
            </div>
        `;
    }
}

function renderSubjectResults(subjects) {
    const container =
        document.getElementById('report-subject-results');

    if (! container) {
        return;
    }

    activeSubjectResults = subjects;

    activeSubjectIndex = -1;

    if (subjects.length === 0) {
        container.innerHTML = `
            <div
                class="
                    px-4 py-4
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate('reports.no_matching_records')
                )}
            </div>
        `;

        showSubjectResultsContainer();

        return;
    }

    container.innerHTML =
        subjects
            .map(
                (subject, index) => `
                    <button
                        type="button"
                        id="report-subject-option-${index}"
                        role="option"
                        aria-selected="false"
                        data-report-subject-option="${index}"
                        class="
                            block w-full
                            border-b border-[var(--pm-border-subtle)]
                            px-4 py-3 text-left
                            transition
                            last:border-b-0
                            hover:bg-[var(--pm-hover)]
                        "
                    >
                        <div
                            class="
                                flex items-start
                                justify-between gap-4
                            "
                        >
                            <div class="min-w-0">

                                <div
                                    class="
                                        truncate text-sm
                                        font-medium
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(subject.name)}
                                </div>

                                ${
                                    subject.meta
                                        ? `
                                            <div
                                                class="
                                                    mt-1 truncate
                                                    text-xs
                                                    text-[var(--pm-text-muted)]
                                                "
                                            >
                                                ${escapeHtml(subject.meta)}
                                            </div>
                                        `
                                        : ''
                                }

                            </div>

                            ${
                                subject.secondary
                                    ? `
                                        <div
                                            class="
                                                shrink-0 text-xs
                                                font-medium
                                                text-[var(--pm-text-secondary)]
                                            "
                                        >
                                            ${escapeHtml(subject.secondary)}
                                        </div>
                                    `
                                    : ''
                            }

                        </div>
                    </button>
                `
            )
            .join('');

    container
        .querySelectorAll('[data-report-subject-option]')
        .forEach((button) => {
            button.addEventListener('click', () => {
                const subject =
                    activeSubjectResults[
                        Number(
                            button.dataset.reportSubjectOption
                        )
                    ];

                if (subject) {
                    selectSubject(subject);
                }
            });
        });

    showSubjectResultsContainer();
}

function selectSubject(subject) {
    selectedSubject = subject;

    setFieldValue('report-subject-id', subject.apiId);

    setFieldValue('report-subject-search', subject.name);

    setText('report-selected-subject-name', subject.name);

    setText(
        'report-selected-subject-meta',
        subject.meta ?? ''
    );

    document
        .getElementById('report-selected-subject')
        ?.classList.remove('hidden');

    hideSubjectResults();

    markResultsStale();

    updateRunButton();
}

function clearSubjectSelection(clearSearch = true) {
    selectedSubject = null;

    setFieldValue('report-subject-id', '');

    if (clearSearch) {
        setFieldValue('report-subject-search', '');
    }

    document
        .getElementById('report-selected-subject')
        ?.classList.add('hidden');

    markResultsStale();

    updateRunButton();
}

function showSubjectResultsContainer() {
    const container =
        document.getElementById('report-subject-results');

    container?.classList.remove('hidden');

    document
        .getElementById('report-subject-search')
        ?.setAttribute('aria-expanded', 'true');
}

function hideSubjectResults() {
    const container =
        document.getElementById('report-subject-results');

    if (! container) {
        return;
    }

    container.innerHTML = '';

    container.classList.add('hidden');

    activeSubjectResults = [];

    activeSubjectIndex = -1;

    const input =
        document.getElementById('report-subject-search');

    input?.setAttribute('aria-expanded', 'false');

    input?.removeAttribute('aria-activedescendant');
}

/*
|--------------------------------------------------------------------------
| Report Date Pickers
|--------------------------------------------------------------------------
*/

/**
 * Keep Patrimoine DD-MM-YYYY display fields while retaining a native
 * calendar selector.
 */
function initializeReportDatePickers() {
    document
        .querySelectorAll('[data-report-date-picker]')
        .forEach((button) => {
            const fieldId =
                button.dataset.reportDatePicker;

            const visibleInput =
                document.getElementById(fieldId);

            const picker =
                document.getElementById(`${fieldId}-picker`);

            if (! visibleInput || ! picker) {
                return;
            }

            button.addEventListener('click', () => {
                const iso =
                    dateForApi(visibleInput.value);

                if (/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
                    picker.value = iso;
                }

                if (typeof picker.showPicker === 'function') {
                    picker.showPicker();
                } else {
                    picker.click();
                }
            });

            picker.addEventListener('change', () => {
                visibleInput.value =
                    dateForDisplay(picker.value);

                visibleInput.dispatchEvent(
                    new Event('change', {
                        bubbles: true,
                    })
                );
            });
        });
}

/*
|--------------------------------------------------------------------------
| Stale-on-change Invalidation
|--------------------------------------------------------------------------
*/

/**
 * Watch every report criterion. Once a report has been rendered, any
 * change dims the output, disables exports and asks for a re-run so the
 * visible results and export endpoints can never diverge from the
 * criteria on screen.
 */
function initializeStaleInvalidation() {
    [
        'report-from',
        'report-to',
        'report-as-of',
        'payment-report-tenant',
        'payment-report-lease',
        'payment-report-building',
        'payment-report-unit',
        'payment-report-method',
        'payment-report-cash-receiver',
        'payment-report-reference',
    ].forEach((id) => {
        const field = document.getElementById(id);

        if (! field) {
            return;
        }

        field.addEventListener('change', markResultsStale);

        field.addEventListener('input', markResultsStale);
    });
}

function markResultsStale() {
    if (! hasResults || resultsStale) {
        return;
    }

    resultsStale = true;

    document
        .getElementById('report-output')
        ?.classList.add('opacity-50');

    document
        .getElementById('report-stale-notice')
        ?.classList.remove('hidden');

    setExportButtonsDisabled(true);
}

function clearResultsStale() {
    resultsStale = false;

    document
        .getElementById('report-output')
        ?.classList.remove('opacity-50');

    document
        .getElementById('report-stale-notice')
        ?.classList.add('hidden');

    setExportButtonsDisabled(false);
}

function setExportButtonsDisabled(disabled) {
    document
        .querySelectorAll(
            '#report-export-actions button, [data-report-results-exports] button'
        )
        .forEach((button) => {
            button.disabled = disabled;

            button.classList.toggle(
                'opacity-50',
                disabled
            );
        });
}

/*
|--------------------------------------------------------------------------
| Payments Report Filters
|--------------------------------------------------------------------------
*/

let paymentReportOptionsLoaded = false;

/*
 * Payments filter fields participate in stale-on-change invalidation
 * through initializeStaleInvalidation(); they need no listeners of
 * their own because the Run button never depends on filter values.
 */

async function loadPaymentReportFilterOptions() {
    if (paymentReportOptionsLoaded) {
        return;
    }

    paymentReportOptionsLoaded = true;

    try {
        const [
            tenantsResponse,
            leasesResponse,
            buildingsResponse,
            unitsResponse,
        ] = await Promise.all([
            apiRequest('/api/parties?role=tenant&per_page=500'),
            apiRequest('/api/leases?per_page=500'),
            apiRequest('/api/buildings?per_page=500'),
            apiRequest('/api/units?per_page=500'),
        ]);

        const [
            tenantsPayload,
            leasesPayload,
            buildingsPayload,
            unitsPayload,
        ] = await Promise.all([
            parseJsonResponse(tenantsResponse),
            parseJsonResponse(leasesResponse),
            parseJsonResponse(buildingsResponse),
            parseJsonResponse(unitsResponse),
        ]);

        populatePaymentReportSelect(
            'payment-report-tenant',
            collectionRows(tenantsPayload),
            (tenant) => partyDisplayName(tenant)
        );

        populatePaymentReportSelect(
            'payment-report-lease',
            collectionRows(leasesPayload),
            (lease) =>
                [
                    `#${lease.id}`,
                    partyDisplayName(lease?.tenant ?? {}),
                    lease?.unit?.building?.name,
                    lease?.unit?.name,
                ]
                    .filter(Boolean)
                    .join(' · ')
        );

        populatePaymentReportSelect(
            'payment-report-building',
            collectionRows(buildingsPayload),
            (building) =>
                building?.name
                ?? `#${building.id}`
        );

        populatePaymentReportSelect(
            'payment-report-unit',
            collectionRows(unitsPayload),
            (unit) =>
                [
                    unit?.building?.name,
                    unit?.name ?? `#${unit.id}`,
                ]
                    .filter(Boolean)
                    .join(' · ')
        );
    } catch (error) {
        paymentReportOptionsLoaded = false;

        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_load_payment_filters')
        );
    }
}

function collectionRows(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
}

function populatePaymentReportSelect(
    id,
    rows,
    labelBuilder
) {
    const select = document.getElementById(id);

    if (! select) {
        return;
    }

    const firstOption =
        select.options[0]?.cloneNode(true);

    select.innerHTML = '';

    if (firstOption) {
        /*
         * The placeholder option keeps its data-i18n key so a client-side
         * language switch re-translates it, and its label is refreshed
         * here in case the language changed since page render.
         */
        if (firstOption.dataset.i18n) {
            firstOption.textContent =
                translate(firstOption.dataset.i18n);
        }

        select.appendChild(firstOption);
    }

    rows.forEach((row) => {
        const option =
            document.createElement('option');

        option.value = String(row.id);

        option.textContent = labelBuilder(row);

        select.appendChild(option);
    });
}

function paymentReportQuery(from, to) {
    const query = new URLSearchParams();

    const values = {
        from,
        to,
        tenant_id: fieldValue('payment-report-tenant'),
        lease_id: fieldValue('payment-report-lease'),
        building_id: fieldValue('payment-report-building'),
        unit_id: fieldValue('payment-report-unit'),
        payment_method: fieldValue('payment-report-method'),
        cash_receiver: fieldValue('payment-report-cash-receiver'),
        reference: fieldValue('payment-report-reference'),
    };

    Object.entries(values).forEach(([key, value]) => {
        if (
            value !== null
            && value !== undefined
            && String(value).trim() !== ''
        ) {
            query.set(key, value);
        }
    });

    return query;
}

/*
|--------------------------------------------------------------------------
| Period and Report Execution
|--------------------------------------------------------------------------
*/

function initializePeriodControls() {
    document
        .getElementById('run-report-button')
        ?.addEventListener('click', async () => {
            await runReport();
        });
}

function updateRunButton() {
    const button =
        document.getElementById('run-report-button');

    if (! button) {
        return;
    }

    button.disabled =
        reportDefinition().subject !== null
        && ! selectedSubject;
}

async function runReport() {
    hideReportsError();

    const definition = reportDefinition();

    const from =
        dateForApi(fieldValue('report-from'));

    const to =
        dateForApi(fieldValue('report-to'));

    const asOf =
        dateForApi(fieldValue('report-as-of'));

    if (
        definition.dateMode === 'period'
        && from
        && to
        && from > to
    ) {
        renderReportError(
            translate('reports.invalid_period')
        );

        return;
    }

    if (
        definition.subject !== null
        && ! selectedSubject
    ) {
        renderReportError(
            translate('reports.select_subject_first')
        );

        return;
    }

    const endpoints =
        buildReportEndpoints(from, to, asOf);

    activeJsonEndpoint = endpoints.json;

    activePdfEndpoint = endpoints.pdf;

    activeCsvEndpoint = endpoints.csv;

    activeXlsxEndpoint = endpoints.xlsx;

    showReportLoading();

    try {
        const response =
            await apiRequest(activeJsonEndpoint);

        const report =
            await parseJsonResponse(response);

        definition.renderer(report);

        renderResultsBar(report);

        hasResults = true;

        clearResultsStale();

        showExportActions();

        reflectUrlState(from, to, asOf);
    } catch (error) {
        hideExportActions();

        renderReportError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_generate')
        );
    }
}

function buildReportEndpoints(from, to, asOf) {
    const definition = reportDefinition();

    let query;

    if (definition.hasPaymentFilters) {
        query = paymentReportQuery(from, to);
    } else if (definition.dateMode === 'asof') {
        query = new URLSearchParams();

        if (asOf) {
            query.set('as_of', asOf);
        }
    } else if (definition.dateMode === 'period') {
        query = new URLSearchParams();

        if (from) {
            query.set('from', from);
        }

        if (to) {
            query.set('to', to);
        }
    } else {
        query = new URLSearchParams();
    }

    const suffix =
        query.toString()
            ? `?${query.toString()}`
            : '';

    const base =
        definition.subject !== null
            ? `${definition.endpointBase}/${selectedSubject.apiId}`
            : definition.endpointBase;

    return {
        json: `${base}${suffix}`,
        pdf: `${base}/pdf${suffix}`,
        csv: `${base}/csv${suffix}`,
        xlsx: `${base}/xlsx${suffix}`,
    };
}

/*
|--------------------------------------------------------------------------
| Results Header Bar
|--------------------------------------------------------------------------
*/

/**
 * Compact bar at the top of a successful report run: report title, the
 * resolved period or reference date, the primary row count where that is
 * meaningful, and the export actions right next to the results.
 */
function renderResultsBar(report) {
    const output =
        document.getElementById('report-output');

    if (! output) {
        return;
    }

    const definition = reportDefinition();

    const metaParts = [];

    if (report?.as_of) {
        metaParts.push(
            `${translate('reports.as_of')}: ${formatDate(report.as_of)}`
        );
    } else if (definition.dateMode === 'period') {
        metaParts.push(periodSummaryText(report?.period));
    }

    const rowCount =
        typeof definition.rowCount === 'function'
            ? definition.rowCount(report)
            : null;

    if (
        rowCount !== null
        && rowCount !== undefined
    ) {
        metaParts.push(
            translate('reports.result_rows', {
                count: formatNumber(rowCount),
            })
        );
    }

    const exports = [
        activePdfEndpoint
            ? exportBarButton('pdf', 'reports.pdf')
            : '',
        activeXlsxEndpoint
            ? exportBarButton('xlsx', 'reports.xlsx')
            : '',
        activeCsvEndpoint
            ? exportBarButton('csv', 'reports.csv')
            : '',
    ].join('');

    output.insertAdjacentHTML(
        'afterbegin',
        `
            <div
                class="
                    mb-6 flex flex-wrap
                    items-center justify-between gap-3
                    rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)]
                    px-4 py-3
                "
            >
                <div class="min-w-0">
                    <div
                        class="
                            text-sm font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        ${escapeHtml(translate(definition.titleKey))}
                    </div>

                    <div
                        class="
                            mt-0.5 text-xs
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            metaParts
                                .filter(Boolean)
                                .join(' · ')
                        )}
                    </div>
                </div>

                <div
                    class="
                        flex flex-wrap
                        items-center gap-2
                    "
                    data-requires-capability="export_reports"
                    data-report-results-exports
                >
                    ${exports}
                </div>
            </div>
        `
    );

    initializeResultsBarExports(output);
}

function exportBarButton(format, labelKey) {
    return `
        <button
            type="button"
            data-report-export="${escapeHtml(format)}"
            class="
                pm-button-secondary
                text-xs
            "
        >
            ${escapeHtml(translate(labelKey))}
        </button>
    `;
}

function initializeResultsBarExports(output) {
    output
        .querySelectorAll('[data-report-export]')
        .forEach((button) => {
            button.addEventListener('click', async () => {
                await triggerExport(
                    button.dataset.reportExport
                );
            });
        });
}

/**
 * Human summary of a resolved reporting period.
 */
function periodSummaryText(period) {
    const from = period?.from;

    const to = period?.to;

    if (! from && ! to) {
        return translate('reports.reporting_period_all_history');
    }

    const fromText =
        from
            ? formatDate(from)
            : translate('reports.beginning');

    const toText =
        to
            ? formatDate(to)
            : translate('reports.present');

    return `${translate('reports.reporting_period')}: ${fromText} — ${toText}`;
}

/*
|--------------------------------------------------------------------------
| Report Rendering — Payments Report
|--------------------------------------------------------------------------
*/

function renderPaymentReport(report) {
    const summary =
        report?.summary ?? {};

    const payments =
        Array.isArray(report?.payments)
            ? report.payments
            : [];

    const body =
        payments.length > 0
            ? payments
                .map(
                    (payment) => `
                        <tr>
                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    formatDate(payment.payment_date)
                                )}
                            </td>

                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    payment.payment_number
                                    ?? `PAY-${payment.id}`
                                )}
                            </td>

                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    payment?.tenant?.name
                                    ?? '—'
                                )}
                            </td>

                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    [
                                        payment?.building?.name,
                                        payment?.unit?.name,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')
                                    || '—'
                                )}
                            </td>

                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    translatedDomainValue(
                                        'payment_method',
                                        payment.payment_method
                                    )
                                )}
                            </td>

                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    payment.cash_receiver_name
                                    || '—'
                                )}
                            </td>

                            <td class="px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                                ${escapeHtml(
                                    payment.reference
                                    || '—'
                                )}
                            </td>

                            <td
                                class="
                                    px-4 py-3
                                    text-right text-sm
                                    font-medium text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(
                                    formatCurrency(payment.amount ?? 0)
                                )}
                            </td>

                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-payment-report-receipt="${escapeHtml(
                                        payment.receipt_endpoint
                                        ?? ''
                                    )}"
                                    class="
                                        pm-button-secondary
                                        whitespace-nowrap text-xs
                                    "
                                >
                                    ${escapeHtml(
                                        translate('reports.receipt')
                                    )}
                                </button>
                            </td>
                        </tr>
                    `
                )
                .join('')
            : `
                <tr>
                    <td
                        colspan="9"
                        class="
                            px-4 py-10
                            text-center text-sm
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            translate('reports.no_payments_found')
                        )}
                    </td>
                </tr>
            `;

    renderReportHtml(`
        ${metricGrid([
            [
                translate('reports.payment_count'),
                formatNumber(summary.payment_count ?? 0),
            ],
            [
                translate('reports.total_received'),
                formatCurrency(summary.total_received ?? 0),
            ],
        ])}

        ${reportSection(
            translate('reports.payments'),
            `
                <div
                    class="
                        overflow-x-auto
                        rounded-xl
                        border border-[var(--pm-border)]
                    "
                >
                    <table class="min-w-full divide-y divide-[var(--pm-border)]">
                        <thead class="bg-[var(--pm-surface-subtle)]">
                            <tr>
                                ${paymentReportHeading(
                                    translate('reports.date')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.payment_number')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.tenant')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.property')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.payment_method_label')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.cash_receiver')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.reference')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.amount'),
                                    true
                                )}

                                ${paymentReportHeading(
                                    translate('reports.receipt')
                                )}
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--pm-border-subtle)] bg-[var(--pm-surface)]">
                            ${body}
                        </tbody>
                    </table>
                </div>
            `
        )}
    `);

    initializePaymentReportReceiptActions();
}

function paymentReportHeading(
    label,
    right = false
) {
    return `
        <th
            scope="col"
            class="
                whitespace-nowrap
                px-4 py-3
                ${
                    right
                        ? 'text-right'
                        : 'text-left'
                }
                text-xs font-semibold
                uppercase tracking-wide
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(label)}
        </th>
    `;
}

function initializePaymentReportReceiptActions() {
    document
        .querySelectorAll('[data-payment-report-receipt]')
        .forEach((button) => {
            button.addEventListener('click', async () => {
                const endpoint =
                    button.dataset.paymentReportReceipt;

                if (! endpoint) {
                    return;
                }

                await openAuthenticatedDocument(endpoint);
            });
        });
}

/*
|--------------------------------------------------------------------------
| Managing Organisation Report
|--------------------------------------------------------------------------
*/

function renderManagingOrganisationReport(report) {
    const portfolio =
        report.portfolio ?? {};

    const billing =
        report.billing ?? {};

    const owner =
        report.owner_accounting ?? {};

    const funds =
        report.tenant_funds ?? {};

    renderReportHtml(`
        ${periodHtml(report.period)}

        ${metricGrid([
            [
                translate('reports.buildings'),
                formatNumber(portfolio.buildings),
            ],
            [
                translate('reports.units'),
                formatNumber(portfolio.units),
            ],
            [
                translate('reports.owner_accounts'),
                formatNumber(portfolio.owner_accounts),
            ],
            [
                translate('reports.cash_received'),
                formatCurrency(billing.cash_received ?? 0),
            ],
        ])}

        ${reportSection(
            translate('reports.billing'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(billing.invoiced ?? 0),
                ],
                [
                    translate('reports.rent_invoiced'),
                    formatCurrency(billing.rent_invoiced ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_invoiced'),
                    formatCurrency(
                        billing.security_deposit_debt_invoiced ?? 0
                    ),
                ],
                [
                    translate('reports.settled'),
                    formatCurrency(billing.settled ?? 0),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(billing.rent_outstanding ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        billing.security_deposit_debt_outstanding ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(billing.total_outstanding ?? 0),
                ],
                [
                    translate('reports.cash_received'),
                    formatCurrency(billing.cash_received ?? 0),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.owner_accounting'),
            pairGrid([
                [
                    translate('reports.rent_entitlement'),
                    formatCurrency(owner.rent_entitlement ?? 0),
                ],
                [
                    translate('reports.management_fees'),
                    formatCurrency(owner.management_fees ?? 0),
                ],
                [
                    translate('reports.agent_commissions'),
                    formatCurrency(owner.agent_commissions ?? 0),
                ],
                [
                    translate('reports.owner_expenses'),
                    formatCurrency(owner.owner_expenses ?? 0),
                ],
                [
                    translate('reports.owner_payouts'),
                    formatCurrency(owner.owner_payouts ?? 0),
                ],
                [
                    translate('reports.owner_funds_held'),
                    formatCurrency(owner.owner_funds_held ?? 0),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.tenant_funds'),
            pairGrid([
                [
                    translate('reports.rent_reserve'),
                    formatCurrency(funds.rent_reserve ?? 0),
                ],
                [
                    translate('reports.consumable_advance'),
                    formatCurrency(funds.consumable_advance ?? 0),
                ],
                [
                    translate('reports.security_deposit'),
                    formatCurrency(funds.security_deposit ?? 0),
                ],
            ])
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Owner Report
|--------------------------------------------------------------------------
*/

function renderOwnerReport(report) {
    const owner =
        report.owner ?? {};

    const summary =
        report.summary ?? {};

    const transactions =
        Array.isArray(report.transactions)
            ? report.transactions
            : [];

    renderReportHtml(`
        ${identityCard(
            owner.name
            ?? translate('reports.property_owner'),
            [
                owner.phone,
                owner.email,
            ]
                .filter(Boolean)
                .join(' · ')
        )}

        ${periodHtml(report.period)}

        ${metricGrid([
            [
                translate('reports.opening_balance'),
                formatCurrency(summary.opening_balance ?? 0),
            ],
            [
                translate('reports.credits'),
                formatCurrency(summary.credits ?? 0),
            ],
            [
                translate('reports.debits'),
                formatCurrency(summary.debits ?? 0),
            ],
            [
                translate('reports.closing_balance'),
                formatCurrency(summary.closing_balance ?? 0),
            ],
        ])}

        ${reportSection(
            translate('reports.financial_summary'),
            pairGrid([
                [
                    translate('reports.rent_entitlement'),
                    formatCurrency(summary.rent_entitlement ?? 0),
                ],
                [
                    translate('reports.owner_deposits'),
                    formatCurrency(summary.owner_deposits ?? 0),
                ],
                [
                    translate('reports.management_fees'),
                    formatCurrency(summary.management_fees ?? 0),
                ],
                [
                    translate('reports.agent_commissions'),
                    formatCurrency(summary.agent_commissions ?? 0),
                ],
                [
                    translate('reports.property_expenses'),
                    formatCurrency(summary.expenses ?? 0),
                ],
                [
                    translate('reports.payouts'),
                    formatCurrency(summary.payouts ?? 0),
                ],
                [
                    translate('reports.adjustments_credit'),
                    formatCurrency(summary.adjustments_credit ?? 0),
                ],
                [
                    translate('reports.adjustments_debit'),
                    formatCurrency(summary.adjustments_debit ?? 0),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.transactions'),
            ownerTransactionsTable(transactions)
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Building Report
|--------------------------------------------------------------------------
*/

function renderBuildingReport(report) {
    const building =
        report.building ?? {};

    const summary =
        report.summary ?? {};

    const ownership =
        Array.isArray(report.ownership)
            ? report.ownership
            : [];

    const expenses =
        Array.isArray(report.expenses)
            ? report.expenses
            : [];

    renderReportHtml(`
        ${identityCard(
            building.name
            ?? translate('reports.building'),
            [
                building.location,
                building.address,
            ]
                .filter(Boolean)
                .join(' · ')
        )}

        ${periodHtml(report.period)}

        ${metricGrid([
            [
                translate('reports.units'),
                formatNumber(summary.units),
            ],
            [
                translate('reports.leases'),
                formatNumber(summary.leases),
            ],
            [
                translate('reports.rent_outstanding'),
                formatCurrency(summary.rent_outstanding ?? 0),
            ],
            [
                translate('reports.security_deposit_debt'),
                formatCurrency(
                    summary.security_deposit_debt_outstanding ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.financial_summary'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(summary.invoiced ?? 0),
                ],
                [
                    translate('reports.rent_invoiced'),
                    formatCurrency(summary.rent_invoiced ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_invoiced'),
                    formatCurrency(
                        summary.security_deposit_debt_invoiced ?? 0
                    ),
                ],
                [
                    translate('reports.invoice_settled'),
                    formatCurrency(summary.invoice_settled ?? 0),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(summary.rent_outstanding ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        summary.security_deposit_debt_outstanding ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(summary.total_outstanding ?? 0),
                ],
                [
                    translate('reports.cash_received'),
                    formatCurrency(summary.cash_received ?? 0),
                ],
                [
                    translate('reports.property_expenses'),
                    formatCurrency(summary.property_expenses ?? 0),
                ],
                [
                    translate('reports.owner_rent_entitlement'),
                    formatCurrency(
                        summary.owner_rent_entitlement ?? 0
                    ),
                ],
                [
                    translate('reports.management_fees'),
                    formatCurrency(summary.management_fees ?? 0),
                ],
                [
                    translate('reports.agent_commissions'),
                    formatCurrency(summary.agent_commissions ?? 0),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.ownership'),
            ownershipTable(ownership)
        )}

        ${reportSection(
            translate('reports.property_expenses'),
            expenseTable(expenses)
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Unit Report
|--------------------------------------------------------------------------
*/

function renderUnitReport(report) {
    const unit =
        report.unit ?? {};

    const summary =
        report.summary ?? {};

    const leases =
        Array.isArray(report.leases)
            ? report.leases
            : [];

    const invoices =
        Array.isArray(report.invoices)
            ? report.invoices
            : [];

    renderReportHtml(`
        ${identityCard(
            unit.name
            ?? translate('reports.unit'),
            unit?.building?.name
            ?? ''
        )}

        ${periodHtml(report.period)}

        ${metricGrid([
            [
                translate('reports.leases'),
                formatNumber(summary.leases),
            ],
            [
                translate('reports.rent_outstanding'),
                formatCurrency(summary.rent_outstanding ?? 0),
            ],
            [
                translate('reports.security_deposit_debt'),
                formatCurrency(
                    summary.security_deposit_debt_outstanding ?? 0
                ),
            ],
            [
                translate('reports.total_outstanding'),
                formatCurrency(summary.total_outstanding ?? 0),
            ],
        ])}

        ${reportSection(
            translate('reports.financial_summary'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(summary.invoiced ?? 0),
                ],
                [
                    translate('reports.rent_invoiced'),
                    formatCurrency(summary.rent_invoiced ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_invoiced'),
                    formatCurrency(
                        summary.security_deposit_debt_invoiced ?? 0
                    ),
                ],
                [
                    translate('reports.settled'),
                    formatCurrency(summary.settled ?? 0),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(summary.rent_outstanding ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        summary.security_deposit_debt_outstanding ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(summary.total_outstanding ?? 0),
                ],
                [
                    translate('reports.cash_received'),
                    formatCurrency(summary.cash_received ?? 0),
                ],
                [
                    translate('reports.expenses'),
                    formatCurrency(summary.expenses ?? 0),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.lease_history'),
            leaseTable(leases)
        )}

        ${reportSection(
            translate('reports.invoices'),
            invoiceTable(invoices)
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Tenant Statement
|--------------------------------------------------------------------------
*/

function renderTenantReport(report) {
    const tenant =
        report.tenant ?? {};

    const summary =
        report.summary ?? {};

    const leases =
        Array.isArray(report.leases)
            ? report.leases
            : [];

    const invoices =
        Array.isArray(report.invoices)
            ? report.invoices
            : [];

    const payments =
        Array.isArray(report.payments)
            ? report.payments
            : [];

    renderReportHtml(`
        ${identityCard(
            tenant.name
            ?? translate('reports.tenant'),
            [
                tenant.phone,
                tenant.email,
            ]
                .filter(Boolean)
                .join(' · ')
        )}

        ${periodHtml(report.period)}

        ${metricGrid([
            [
                translate('reports.rent_outstanding'),
                formatCurrency(summary.rent_outstanding ?? 0),
            ],
            [
                translate('reports.security_deposit_debt'),
                formatCurrency(
                    summary.security_deposit_debt_outstanding ?? 0
                ),
            ],
            [
                translate('reports.total_outstanding'),
                formatCurrency(summary.total_outstanding ?? 0),
            ],
            [
                translate('reports.cash_received'),
                formatCurrency(summary.cash_received ?? 0),
            ],
        ])}

        ${reportSection(
            translate('reports.receivables'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(summary.invoiced ?? 0),
                ],
                [
                    translate('reports.settled'),
                    formatCurrency(summary.settled ?? 0),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(summary.rent_outstanding ?? 0),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        summary.security_deposit_debt_outstanding ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(summary.total_outstanding ?? 0),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.held_funds'),
            pairGrid([
                [
                    translate('reports.rent_reserve'),
                    formatCurrency(
                        summary.rent_reserve_balance ?? 0
                    ),
                ],
                [
                    translate('reports.consumable_advance'),
                    formatCurrency(
                        summary.consumable_advance_balance ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit'),
                    formatCurrency(
                        summary.security_deposit_balance ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.leases'),
            tenantLeaseTable(leases)
        )}

        ${reportSection(
            translate('reports.invoices'),
            tenantInvoiceTable(invoices)
        )}

        ${reportSection(
            translate('reports.payments'),
            tenantPaymentTable(payments)
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Occupancy Report
|--------------------------------------------------------------------------
*/

function renderOccupancyReport(report) {
    const totals =
        report?.totals ?? {};

    const classification =
        report?.classification ?? {};

    const buildings =
        Array.isArray(report?.buildings)
            ? report.buildings
            : [];

    renderReportHtml(`
        ${asOfHtml(report?.as_of)}

        ${metricGrid([
            [
                translate('reports.units'),
                formatNumber(totals.units ?? 0),
            ],
            [
                translate('reports.occupied'),
                formatNumber(totals.occupied ?? 0),
            ],
            [
                translate('reports.vacant'),
                formatNumber(totals.vacant ?? 0),
            ],
            [
                translate('reports.occupancy_rate'),
                percentValue(totals.occupancy_rate),
            ],
        ])}

        ${reportSection(
            translate('reports.occupancy_by_classification'),
            `
                <div
                    class="
                        grid gap-4
                        sm:grid-cols-2
                    "
                >
                    ${occupancyClassificationCard(
                        translate('reports.commercial'),
                        classification.commercial ?? {}
                    )}

                    ${occupancyClassificationCard(
                        translate('reports.residential'),
                        classification.residential ?? {}
                    )}
                </div>
            `
        )}

        ${reportSection(
            translate('reports.buildings'),
            occupancyBuildingsTable(buildings)
        )}
    `);
}

function occupancyClassificationCard(
    title,
    data
) {
    const pairs = [
        [
            translate('reports.units'),
            formatNumber(data.units ?? 0),
        ],
        [
            translate('reports.occupied'),
            formatNumber(data.occupied ?? 0),
        ],
        [
            translate('reports.vacant'),
            formatNumber(data.vacant ?? 0),
        ],
        [
            translate('reports.occupancy_rate'),
            percentValue(data.occupancy_rate),
        ],
    ];

    return `
        <div
            class="
                rounded-xl
                border border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)] p-5
            "
        >
            <div
                class="
                    text-sm font-semibold
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(title)}
            </div>

            <div
                class="
                    mt-4 grid grid-cols-2
                    gap-3
                "
            >
                ${pairs
                    .map(
                        ([label, value]) => `
                            <div>
                                <div
                                    class="
                                        text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    ${escapeHtml(label)}
                                </div>

                                <div
                                    class="
                                        mt-1 text-sm
                                        font-semibold
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(value)}
                                </div>
                            </div>
                        `
                    )
                    .join('')}
            </div>
        </div>
    `;
}

function occupancyBuildingsTable(rows) {
    return tableHtml(
        [
            translate('reports.building'),
            translate('reports.units'),
            translate('reports.occupied'),
            translate('reports.vacant'),
            translate('reports.occupancy_rate'),
            translate('reports.commercial_units'),
        ],
        rows.map(
            (row) => [
                row.name
                ?? translate('reports.building_number', {
                    number: row.id,
                }),
                formatNumber(row.units ?? 0),
                formatNumber(row.occupied ?? 0),
                formatNumber(row.vacant ?? 0),
                percentValue(row.occupancy_rate),
                formatNumber(row.commercial_units ?? 0),
            ]
        ),
        [
            'left',
            'right',
            'right',
            'right',
            'right',
            'right',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Arrears Aging Report
|--------------------------------------------------------------------------
*/

function renderArrearsReport(report) {
    const totals =
        report?.totals ?? {};

    const tenants =
        Array.isArray(report?.tenants)
            ? report.tenants
            : [];

    renderReportHtml(`
        ${asOfHtml(report?.as_of)}

        ${metricGrid([
            [
                translate('reports.aging_current'),
                formatCurrency(totals.current ?? 0),
            ],
            [
                translate('reports.aging_31_60'),
                formatCurrency(totals.days_31_60 ?? 0),
            ],
            [
                translate('reports.aging_61_90'),
                formatCurrency(totals.days_61_90 ?? 0),
            ],
            [
                translate('reports.aging_over_90'),
                formatCurrency(totals.over_90 ?? 0),
                {
                    emphasis: 'danger',
                },
            ],
            [
                translate('reports.total_arrears'),
                formatCurrency(totals.total ?? 0),
            ],
            [
                translate('reports.open_invoices'),
                formatNumber(totals.invoice_count ?? 0),
            ],
        ])}

        ${reportSection(
            translate('reports.tenants_in_arrears'),
            arrearsTenantsTable(tenants)
        )}
    `);
}

function arrearsTenantsTable(rows) {
    return tableHtml(
        [
            translate('reports.tenant'),
            translate('reports.lease'),
            translate('reports.building'),
            translate('reports.unit'),
            translate('reports.open_invoices'),
            translate('reports.aging_current'),
            translate('reports.aging_31_60'),
            translate('reports.aging_61_90'),
            translate('reports.aging_over_90'),
            translate('reports.total_arrears'),
        ],
        rows.map(
            (row) => [
                row?.tenant?.name
                    ?? translate('reports.unnamed_party'),
                row?.lease?.id
                    ? `#${row.lease.id}`
                    : '',
                row?.building?.name ?? '',
                row?.unit?.name ?? '',
                formatNumber(row.invoice_count ?? 0),
                formatCurrency(row.current ?? 0),
                formatCurrency(row.days_31_60 ?? 0),
                formatCurrency(row.days_61_90 ?? 0),
                formatCurrency(row.over_90 ?? 0),
                formatCurrency(row.total ?? 0),
            ]
        ),
        [
            'left',
            'left',
            'left',
            'left',
            'right',
            'right',
            'right',
            'right',
            'right',
            'right',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Funds Held Report
|--------------------------------------------------------------------------
*/

function renderFundsReport(report) {
    const tenantFunds =
        report?.tenant_funds ?? {};

    const tenantSummary =
        tenantFunds.summary ?? {};

    const tenants =
        Array.isArray(tenantFunds.tenants)
            ? tenantFunds.tenants
            : [];

    const ownerFunds =
        report?.owner_funds ?? {};

    const ownerSummary =
        ownerFunds.summary ?? {};

    const owners =
        Array.isArray(ownerFunds.owners)
            ? ownerFunds.owners
            : [];

    renderReportHtml(`
        ${asOfHtml(report?.as_of)}

        ${reportSection(
            translate('reports.tenant_funds'),
            `
                ${metricGrid([
                    [
                        translate('reports.rent_reserve'),
                        formatCurrency(
                            tenantSummary?.rent_reserve?.total_held
                            ?? 0
                        ),
                        {
                            meta: accountCountLabel(
                                tenantSummary?.rent_reserve?.account_count
                            ),
                        },
                    ],
                    [
                        translate('reports.consumable_advance'),
                        formatCurrency(
                            tenantSummary?.consumable_advance?.total_held
                            ?? 0
                        ),
                        {
                            meta: accountCountLabel(
                                tenantSummary?.consumable_advance?.account_count
                            ),
                        },
                    ],
                    [
                        translate('reports.security_deposit'),
                        formatCurrency(
                            tenantSummary?.security_deposit?.total_held
                            ?? 0
                        ),
                        {
                            meta: accountCountLabel(
                                tenantSummary?.security_deposit?.account_count
                            ),
                        },
                    ],
                    [
                        translate('reports.total_held'),
                        formatCurrency(
                            tenantSummary.total_held ?? 0
                        ),
                    ],
                ])}

                ${fundsTenantsTable(tenants)}
            `
        )}

        ${reportSection(
            translate('reports.owner_funds'),
            `
                ${metricGrid([
                    [
                        translate('reports.owner_accounts'),
                        formatNumber(
                            ownerSummary.account_count ?? 0
                        ),
                    ],
                    [
                        translate('reports.total_held'),
                        formatCurrency(
                            ownerSummary.total_held ?? 0
                        ),
                    ],
                ])}

                ${fundsOwnersTable(owners)}
            `
        )}
    `);
}

function fundsTenantsTable(rows) {
    return tableHtml(
        [
            translate('reports.tenant'),
            translate('reports.lease'),
            translate('reports.building'),
            translate('reports.unit'),
            translate('reports.rent_reserve'),
            translate('reports.consumable_advance'),
            translate('reports.security_deposit'),
            translate('reports.total_held'),
        ],
        rows.map(
            (row) => [
                row?.tenant?.name
                    ?? translate('reports.unnamed_party'),
                row?.lease?.id
                    ? `#${row.lease.id}`
                    : '',
                row?.building?.name ?? '',
                row?.unit?.name ?? '',
                formatCurrency(row.rent_reserve ?? 0),
                formatCurrency(row.consumable_advance ?? 0),
                formatCurrency(row.security_deposit ?? 0),
                formatCurrency(row.total ?? 0),
            ]
        ),
        [
            'left',
            'left',
            'left',
            'left',
            'right',
            'right',
            'right',
            'right',
        ]
    );
}

function fundsOwnersTable(rows) {
    return tableHtml(
        [
            translate('reports.owner'),
            translate('reports.balance'),
        ],
        rows.map(
            (row) => [
                row?.owner?.name
                    ?? translate('reports.unnamed_party'),
                formatCurrency(row.balance ?? 0),
            ]
        ),
        [
            'left',
            'right',
        ]
    );
}

function accountCountLabel(count) {
    return translate('reports.account_count', {
        count: formatNumber(count ?? 0),
    });
}

/*
|--------------------------------------------------------------------------
| Rendering Components
|--------------------------------------------------------------------------
*/

function identityCard(
    title,
    subtitle
) {
    return `
        <div
            class="
                mb-6 rounded-xl
                border border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)] p-5
            "
        >
            <div
                class="
                    text-lg font-semibold
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(title)}
            </div>

            ${
                subtitle
                    ? `
                        <div
                            class="
                                mt-1 text-sm
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(subtitle)}
                        </div>
                    `
                    : ''
            }
        </div>
    `;
}

function periodHtml(period) {
    return `
        <div
            class="
                mb-6 text-xs
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(periodSummaryText(period))}
        </div>
    `;
}

/**
 * Reference-date line for snapshot reports.
 *
 * Snapshot services always resolve as_of to a concrete date, so no
 * textual fallback is required here.
 */
function asOfHtml(asOf) {
    return `
        <div
            class="
                mb-6 text-xs
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(translate('reports.as_of'))}:
            ${escapeHtml(formatDate(asOf))}
        </div>
    `;
}

/**
 * Metric tile grid.
 *
 * Each metric is [label, value] with an optional third options object:
 * { emphasis: 'danger' } paints the tile with danger status tokens and
 * { meta: '…' } adds a muted sub-line under the value.
 */
function metricGrid(metrics) {
    return `
        <div
            class="
                mb-6 grid gap-4
                sm:grid-cols-2
                xl:grid-cols-4
            "
        >
            ${metrics
                .map(
                    ([label, value, options = {}]) => {
                        const danger =
                            options.emphasis === 'danger';

                        return `
                        <div
                            class="
                                rounded-xl border p-4
                                ${
                                    danger
                                        ? `
                                            border-[var(--pm-danger-border)]
                                            bg-[var(--pm-danger-background)]
                                        `
                                        : `
                                            border-[var(--pm-border)]
                                            bg-[var(--pm-surface-subtle)]
                                        `
                                }
                            "
                        >
                            <div
                                class="
                                    text-xs font-medium
                                    uppercase tracking-wide
                                    ${
                                        danger
                                            ? 'text-[var(--pm-danger-text)]'
                                            : 'text-[var(--pm-text-muted)]'
                                    }
                                "
                            >
                                ${escapeHtml(label)}
                            </div>

                            <div
                                class="
                                    mt-2 text-xl
                                    font-semibold
                                    tracking-tight
                                    ${
                                        danger
                                            ? 'text-[var(--pm-danger-text)]'
                                            : 'text-[var(--pm-text)]'
                                    }
                                "
                            >
                                ${escapeHtml(value)}
                            </div>

                            ${
                                options.meta
                                    ? `
                                        <div
                                            class="
                                                mt-1 text-xs
                                                ${
                                                    danger
                                                        ? 'text-[var(--pm-danger-text)]'
                                                        : 'text-[var(--pm-text-muted)]'
                                                }
                                            "
                                        >
                                            ${escapeHtml(options.meta)}
                                        </div>
                                    `
                                    : ''
                            }
                        </div>
                        `;
                    }
                )
                .join('')}
        </div>
    `;
}

function pairGrid(rows) {
    return `
        <div
            class="
                grid gap-3
                sm:grid-cols-2
                xl:grid-cols-3
            "
        >
            ${rows
                .map(
                    ([label, value]) => `
                        <div
                            class="
                                rounded-lg
                                border border-[var(--pm-border)]
                                bg-[var(--pm-surface-subtle)]
                                px-4 py-3
                            "
                        >
                            <div
                                class="
                                    text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(label)}
                            </div>

                            <div
                                class="
                                    mt-1 text-sm
                                    font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(value)}
                            </div>
                        </div>
                    `
                )
                .join('')}
        </div>
    `;
}

function reportSection(
    title,
    body
) {
    return `
        <section
            class="
                mt-7 border-t
                border-[var(--pm-border-subtle)] pt-6
            "
        >
            <h3
                class="
                    mb-4 text-base
                    font-semibold
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(title)}
            </h3>

            ${body}
        </section>
    `;
}

/**
 * Generic report table.
 *
 * `aligns` optionally provides per-column alignment ('left' | 'right').
 * Money and numeric columns are right-aligned on screen to match the PDF
 * exports.
 */
function tableHtml(
    headers,
    rows,
    aligns = []
) {
    if (rows.length === 0) {
        return `
            <div
                class="
                    rounded-xl
                    border border-dashed
                    border-[var(--pm-border)]
                    px-5 py-8
                    text-center
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(translate('reports.no_records_section'))}
            </div>
        `;
    }

    const alignmentClass = (index) =>
        aligns[index] === 'right'
            ? 'text-right'
            : 'text-left';

    return `
        <div
            class="
                overflow-x-auto
                rounded-xl
                border border-[var(--pm-border)]
            "
        >
            <table
                class="
                    min-w-full
                    divide-y divide-[var(--pm-border)]
                    text-sm
                "
            >
                <thead
                    class="
                        bg-[var(--pm-surface-subtle)]
                    "
                >
                    <tr>
                        ${headers
                            .map(
                                (header, index) => `
                                    <th
                                        class="
                                            whitespace-nowrap
                                            px-4 py-3
                                            ${alignmentClass(index)}
                                            text-xs font-semibold
                                            uppercase tracking-wide
                                            text-[var(--pm-text-muted)]
                                        "
                                    >
                                        ${escapeHtml(header)}
                                    </th>
                                `
                            )
                            .join('')}
                    </tr>
                </thead>

                <tbody
                    class="
                        divide-y divide-[var(--pm-border-subtle)]
                        bg-[var(--pm-surface)]
                    "
                >
                    ${rows
                        .map(
                            (row) => `
                                <tr>
                                    ${row
                                        .map(
                                            (value, index) => `
                                                <td
                                                    class="
                                                        whitespace-nowrap
                                                        px-4 py-3
                                                        ${alignmentClass(index)}
                                                        text-[var(--pm-text-secondary)]
                                                    "
                                                >
                                                    ${escapeHtml(value)}
                                                </td>
                                            `
                                        )
                                        .join('')}
                                </tr>
                            `
                        )
                        .join('')}
                </tbody>
            </table>
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| Report Tables
|--------------------------------------------------------------------------
*/

function ownerTransactionsTable(rows) {
    return tableHtml(
        [
            translate('reports.date'),
            translate('reports.direction'),
            translate('reports.category'),
            translate('reports.amount'),
            translate('reports.building'),
            translate('reports.unit'),
            translate('reports.invoice'),
            translate('reports.reference'),
        ],
        rows.map(
            (row) => [
                formatDate(row.date),
                translatedDomainValue('direction', row.direction),
                translatedDomainValue('category', row.category),
                formatCurrency(row.amount ?? 0),
                row.building ?? '',
                row.unit ?? '',
                row.invoice ?? '',
                row.reference ?? '',
            ]
        ),
        [
            'left',
            'left',
            'left',
            'right',
            'left',
            'left',
            'left',
            'left',
        ]
    );
}

function ownershipTable(rows) {
    return tableHtml(
        [
            translate('reports.owner'),
            translate('reports.ownership'),
        ],
        rows.map(
            (row) => [
                row.owner ?? '',
                `${row.percentage ?? 0}%`,
            ]
        ),
        [
            'left',
            'right',
        ]
    );
}

function expenseTable(rows) {
    return tableHtml(
        [
            translate('reports.date'),
            translate('reports.description'),
            translate('reports.amount'),
            translate('reports.unit'),
            translate('reports.reference'),
        ],
        rows.map(
            (row) => [
                formatDate(row.date),
                row.description ?? '',
                formatCurrency(row.amount ?? 0),
                row.unit_id
                    ? translate('reports.unit_number', {
                        number: row.unit_id,
                    })
                    : '',
                row.reference ?? '',
            ]
        ),
        [
            'left',
            'left',
            'right',
            'left',
            'left',
        ]
    );
}

function leaseTable(rows) {
    return tableHtml(
        [
            translate('reports.tenant'),
            translate('reports.start'),
            translate('reports.end'),
            translate('reports.status'),
            translate('reports.rent'),
            translate('reports.frequency'),
        ],
        rows.map(
            (row) => [
                row.tenant ?? '',
                formatDate(row.start_date),
                row.end_date
                    ? formatDate(row.end_date)
                    : '',
                translatedDomainValue('status', row.status),
                formatCurrency(row.rent_amount ?? 0),
                translatedDomainValue(
                    'frequency',
                    row.payment_frequency
                ),
            ]
        ),
        [
            'left',
            'left',
            'left',
            'left',
            'right',
            'left',
        ]
    );
}

function invoiceTable(rows) {
    return tableHtml(
        [
            translate('reports.invoice'),
            translate('reports.type'),
            translate('reports.issue_date'),
            translate('reports.due_date'),
            translate('reports.amount'),
            translate('reports.paid'),
            translate('reports.outstanding'),
            translate('reports.status'),
        ],
        rows.map(
            (row) => [
                row.invoice_number ?? '',
                translatedDomainValue('invoice_type', row.type),
                formatDate(row.issue_date),
                formatDate(row.due_date),
                formatCurrency(row.total_amount ?? 0),
                formatCurrency(row.paid_amount ?? 0),
                formatCurrency(row.outstanding_amount ?? 0),
                translatedDomainValue('status', row.status),
            ]
        ),
        [
            'left',
            'left',
            'left',
            'left',
            'right',
            'right',
            'right',
            'left',
        ]
    );
}

function tenantLeaseTable(rows) {
    return tableHtml(
        [
            translate('reports.building'),
            translate('reports.unit'),
            translate('reports.status'),
            translate('reports.start'),
            translate('reports.end'),
            translate('reports.rent'),
        ],
        rows.map(
            (row) => [
                row.building ?? '',
                row.unit ?? '',
                translatedDomainValue('status', row.status),
                formatDate(row.start_date),
                row.end_date
                    ? formatDate(row.end_date)
                    : '',
                formatCurrency(row.rent_amount ?? 0),
            ]
        ),
        [
            'left',
            'left',
            'left',
            'left',
            'left',
            'right',
        ]
    );
}

function tenantInvoiceTable(rows) {
    return tableHtml(
        [
            translate('reports.invoice'),
            translate('reports.type'),
            translate('reports.date'),
            translate('reports.due_date'),
            translate('reports.amount'),
            translate('reports.paid'),
            translate('reports.outstanding'),
            translate('reports.status'),
        ],
        rows.map(
            (row) => [
                row.invoice_number ?? '',
                translatedDomainValue('invoice_type', row.type),
                formatDate(row.date),
                formatDate(row.due_date),
                formatCurrency(row.amount ?? 0),
                formatCurrency(row.paid ?? 0),
                formatCurrency(row.outstanding ?? 0),
                translatedDomainValue('status', row.status),
            ]
        ),
        [
            'left',
            'left',
            'left',
            'left',
            'right',
            'right',
            'right',
            'left',
        ]
    );
}

function tenantPaymentTable(rows) {
    return tableHtml(
        [
            translate('reports.date'),
            translate('reports.amount'),
            translate('reports.method'),
            translate('reports.reference'),
            translate('reports.allocated'),
            translate('reports.unallocated'),
        ],
        rows.map(
            (row) => [
                formatDate(row.date),
                formatCurrency(row.amount ?? 0),
                translatedDomainValue('payment_method', row.method),
                row.reference ?? '',
                formatCurrency(row.allocated ?? 0),
                formatCurrency(row.unallocated ?? 0),
            ]
        ),
        [
            'left',
            'right',
            'left',
            'left',
            'right',
            'right',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Export Actions
|--------------------------------------------------------------------------
*/

function initializeExportActions() {
    document
        .getElementById('report-pdf-button')
        ?.addEventListener('click', async () => {
            await triggerExport('pdf');
        });

    document
        .getElementById('report-xlsx-button')
        ?.addEventListener('click', async () => {
            await triggerExport('xlsx');
        });

    document
        .getElementById('report-csv-button')
        ?.addEventListener('click', async () => {
            await triggerExport('csv');
        });
}

/**
 * Run one export action for the last generated report.
 *
 * Stale results refuse to export: the endpoints still describe criteria
 * the user can no longer see.
 */
async function triggerExport(format) {
    if (resultsStale) {
        return;
    }

    if (format === 'pdf' && activePdfEndpoint) {
        await openAuthenticatedDocument(activePdfEndpoint);

        return;
    }

    if (format === 'xlsx' && activeXlsxEndpoint) {
        await downloadAuthenticatedDocument(
            activeXlsxEndpoint,
            'report.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return;
    }

    if (format === 'csv' && activeCsvEndpoint) {
        await downloadAuthenticatedDocument(
            activeCsvEndpoint,
            'report.csv'
        );
    }
}

async function openAuthenticatedDocument(endpoint) {
    hideReportsError();

    try {
        await openPdfInNewTab(
            endpoint,
            translate('reports.unable_to_open')
        );
    } catch (error) {
        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_open')
        );
    }
}

async function downloadAuthenticatedDocument(
    endpoint,
    fallbackFilename,
    accept = 'text/csv'
) {
    hideReportsError();

    try {
        const response = await apiRequest(
            endpoint,
            {
                headers: {
                    Accept: accept,
                },
            }
        );

        if (! response.ok) {
            throw new Error(
                translate('reports.unable_to_download')
            );
        }

        const blob = await response.blob();

        const disposition =
            response.headers.get('Content-Disposition');

        const filename =
            filenameFromDisposition(disposition)
            || fallbackFilename;

        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');

        link.href = url;

        link.download = filename;

        document.body.appendChild(link);

        link.click();

        link.remove();

        URL.revokeObjectURL(url);
    } catch (error) {
        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_download')
        );
    }
}

function filenameFromDisposition(disposition) {
    if (! disposition) {
        return '';
    }

    const match =
        disposition.match(/filename="?([^"]+)"?/i);

    return match?.[1] ?? '';
}

function showExportActions() {
    const actions =
        document.getElementById('report-export-actions');

    if (! actions) {
        return;
    }

    actions.classList.remove('hidden');

    document
        .getElementById('report-pdf-button')
        ?.classList.toggle('hidden', ! activePdfEndpoint);

    document
        .getElementById('report-xlsx-button')
        ?.classList.toggle('hidden', ! activeXlsxEndpoint);

    document
        .getElementById('report-csv-button')
        ?.classList.toggle('hidden', ! activeCsvEndpoint);
}

function hideExportActions() {
    document
        .getElementById('report-export-actions')
        ?.classList.add('hidden');

    document
        .getElementById('report-xlsx-button')
        ?.classList.add('hidden');
}

/*
|--------------------------------------------------------------------------
| Output State
|--------------------------------------------------------------------------
*/

function renderReportHtml(html) {
    const output =
        document.getElementById('report-output');

    if (output) {
        output.innerHTML = html;
    }
}

function showReportLoading() {
    clearResultsStale();

    hasResults = false;

    renderReportHtml(`
        <div
            class="
                flex min-h-[520px]
                items-center justify-center
                text-sm text-[var(--pm-text-subtle)]
            "
        >
            ${escapeHtml(translate('reports.generating'))}
        </div>
    `);
}

/**
 * In-output error state.
 *
 * Run failures render here — inside the output panel — rather than in the
 * top page banner, which is reserved for page-boot failures.
 */
function renderReportError(message = null) {
    hasResults = false;

    clearResultsStale();

    hideExportActions();

    renderReportHtml(`
        <div
            class="
                flex min-h-[520px]
                items-center justify-center
                px-6 text-center
            "
        >
            <div class="max-w-md">
                <div
                    class="
                        text-sm font-medium
                        text-[var(--pm-danger-text)]
                    "
                >
                    ${escapeHtml(translate('reports.could_not_generate'))}
                </div>

                ${
                    message
                    && message !== translate('reports.could_not_generate')
                        ? `
                            <div
                                class="
                                    mt-2 text-sm
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(message)}
                            </div>
                        `
                        : ''
                }
            </div>
        </div>
    `);
}

function clearReportOutput() {
    activeJsonEndpoint = null;

    activePdfEndpoint = null;

    activeCsvEndpoint = null;

    activeXlsxEndpoint = null;

    hasResults = false;

    clearResultsStale();

    hideExportActions();

    renderReportHtml(`
        <div
            class="
                flex min-h-[520px]
                items-center justify-center
            "
        >
            <div
                class="
                    max-w-md text-center
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(translate('reports.initial_prompt'))}
            </div>
        </div>
    `);
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showReportsError(message) {
    const element =
        document.getElementById('reports-error');

    if (! element) {
        return;
    }

    element.textContent = message;

    element.classList.remove('hidden');
}

function hideReportsError() {
    const element =
        document.getElementById('reports-error');

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add('hidden');
}

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

function partyDisplayName(party) {
    return party?.name
        || party?.legal_name
        || translate('reports.unnamed_party');
}

function contactSummary(party) {
    return [
        party?.phone,
        party?.email,
    ]
        .filter(Boolean)
        .join(' · ');
}

/**
 * Render an API-provided percentage value (already expressed as a
 * number of percent, e.g. 92.5) for display.
 */
function percentValue(value) {
    return `${formatNumber(value ?? 0)}%`;
}

function translatedDomainValue(
    group,
    value
) {
    const normalized =
        String(value ?? '').trim();

    if (! normalized) {
        return '';
    }

    const key =
        `reports.${group}.${normalized}`;

    const translated = translate(key);

    /*
     * Unknown future API values remain readable instead of exposing a
     * translation key. Persisted/API values themselves are never modified.
     */
    if (translated === key) {
        return normalized
            .replaceAll('_', ' ')
            .split(' ')
            .filter(Boolean)
            .map(
                (word) =>
                    word.charAt(0).toUpperCase()
                    + word.slice(1)
            )
            .join(' ');
    }

    return translated;
}

/*
|--------------------------------------------------------------------------
| DOM Helpers
|--------------------------------------------------------------------------
*/

function fieldValue(id) {
    const element =
        document.getElementById(id);

    return element
        ? String(element.value ?? '').trim()
        : '';
}

function setFieldValue(
    id,
    value
) {
    const element =
        document.getElementById(id);

    if (element) {
        element.value = value ?? '';
    }
}

function setText(
    id,
    value
) {
    const element =
        document.getElementById(id);

    if (element) {
        element.textContent = value ?? '';
    }
}

/*
|--------------------------------------------------------------------------
| Escaping
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
