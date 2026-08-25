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
    openDatePicker,
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
 * Report types that cover the whole portfolio and therefore skip the
 * subject-picker step entirely.
 */
const SUBJECTLESS_REPORT_TYPES = [
    'managing-organisation',
    'payments',
    'occupancy',
    'arrears',
    'funds',
];

/*
 * Portfolio snapshot reports replace the From/To period pair with a
 * single optional as-of reference date.
 */
const AS_OF_REPORT_TYPES = [
    'occupancy',
    'arrears',
];

/*
 * Report types that never use the From/To reporting period fields.
 */
const PERIODLESS_REPORT_TYPES = [
    'occupancy',
    'arrears',
    'funds',
];

let selectedReportType =
    'managing-organisation';

let selectedSubject =
    null;

let searchTimer =
    null;

let activeJsonEndpoint =
    null;

let activePdfEndpoint =
    null;

let activeCsvEndpoint =
    null;

let activeXlsxEndpoint =
    null;

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
    const output =
        document.getElementById(
            'report-output'
        );

    if (! output) {
        return false;
    }

    initializeReportTypeButtons();

    initializeSubjectSearch();

    initializePeriodControls();

    initializeDateInputs(
        '[data-report-date-input]'
    );

    initializeReportDatePickers();

    initializeExportActions();

    initializePaymentReportFilters();

    updateReportTypeUi();

    /*
     * Internal workspaces may link directly to a specific report subject.
     * Resolve that context only after all Report controls are initialized.
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
 * V1.0.1 Tenant Management uses this to open a selected Tenant's statement
 * directly rather than forcing the Property Manager to search for the same
 * Tenant again in Reports.
 */
async function initializeReportDeepLink() {
    const parameters =
        new URLSearchParams(
            window.location.search
        );

    const type =
        parameters.get(
            'type'
        );

    const tenantId =
        Number(
            parameters.get(
                'tenant_id'
            )
        );

    if (
        type !== 'tenant'
        || ! Number.isInteger(
            tenantId
        )
        || tenantId <= 0
    ) {
        return;
    }

    selectedReportType =
        'tenant';

    selectedSubject =
        null;

    clearSubjectSelection();

    clearReportOutput();

    updateReportTypeUi();

    try {
        const response =
            await apiRequest(
                `/api/parties/${tenantId}`
            );

        const tenant =
            await parseJsonResponse(
                response
            );

        const isTenant =
            Array.isArray(
                tenant.roles
            )
            && tenant.roles.some(
                (role) =>
                    role.role === 'tenant'
            );

        if (! isTenant) {
            throw new Error(
                translate('reports.not_tenant')
            );
        }

        selectSubject({
            id:
                tenant.id,

            apiId:
                tenant.id,

            name:
                partyDisplayName(
                    tenant
                ),

            meta:
                contactSummary(
                    tenant
                ),
        });

        await runReport();
    } catch (error) {
        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_open_tenant_statement')
        );

        renderReportError();
    }
}

/*
|--------------------------------------------------------------------------
| Report Type Selection
|--------------------------------------------------------------------------
*/

function initializeReportTypeButtons() {
    document
        .querySelectorAll(
            '[data-report-type]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        selectedReportType =
                            button.dataset
                                .reportType;

                        selectedSubject =
                            null;

                        clearSubjectSelection();

                        clearReportOutput();

                        updateReportTypeUi();
                    }
                );
            }
        );
}

function updateReportTypeUi() {
    document
        .querySelectorAll(
            '[data-report-type]'
        )
        .forEach(
            (button) => {
                const active =
                    button.dataset
                        .reportType
                    === selectedReportType;

                button.classList.toggle(
                    'is-active',
                    active
                );
            }
        );

    const subjectSection =
        document.getElementById(
            'report-subject-section'
        );

    if (
        SUBJECTLESS_REPORT_TYPES.includes(
            selectedReportType
        )
    ) {
        subjectSection
            ?.classList
            .add(
                'hidden'
            );
    } else {
        subjectSection
            ?.classList
            .remove(
                'hidden'
            );
    }

    document
        .getElementById(
            'payment-report-filters'
        )
        ?.classList.toggle(
            'hidden',
            selectedReportType
                !== 'payments'
        );

    document
        .getElementById(
            'report-period-fields'
        )
        ?.classList.toggle(
            'hidden',
            PERIODLESS_REPORT_TYPES.includes(
                selectedReportType
            )
        );

    document
        .getElementById(
            'report-asof-field'
        )
        ?.classList.toggle(
            'hidden',
            ! AS_OF_REPORT_TYPES.includes(
                selectedReportType
            )
        );

    if (
        selectedReportType
        === 'payments'
    ) {
        loadPaymentReportFilterOptions();
    }

    updateSubjectLabels();

    updateReportHeader();

    updateRunButton();
}

function updateSubjectLabels() {
    const label =
        document.getElementById(
            'report-subject-label'
        );

    const input =
        document.getElementById(
            'report-subject-search'
        );

    if (
        ! label
        || ! input
    ) {
        return;
    }

    switch (
        selectedReportType
    ) {
        case 'owner':
            label.textContent =
                translate('reports.property_owner');

            input.placeholder =
                translate('reports.search_owner_placeholder');

            break;

        case 'tenant':
            label.textContent =
                translate('reports.tenant');

            input.placeholder =
                translate('reports.search_tenant_placeholder');

            break;

        case 'building':
            label.textContent =
                translate('reports.building');

            input.placeholder =
                translate('reports.search_building_placeholder');

            break;

        case 'unit':
            label.textContent =
                translate('reports.unit');

            input.placeholder =
                translate('reports.search_unit_placeholder');

            break;

        default:
            label.textContent =
                translate('reports.search');

            input.placeholder =
                translate('reports.search_placeholder');
    }
}

function updateReportHeader() {
    const title =
        document.getElementById(
            'report-output-title'
        );

    const subtitle =
        document.getElementById(
            'report-output-subtitle'
        );

    if (
        ! title
        || ! subtitle
    ) {
        return;
    }

    const definitions = {
        'managing-organisation': {
            title:
                translate('reports.managing_organisation_report'),

            subtitle:
                translate('reports.managing_organisation_description'),
        },

        owner: {
            title:
                translate('reports.owner_report'),

            subtitle:
                translate('reports.owner_report_description'),
        },

        building: {
            title:
                translate('reports.building_report'),

            subtitle:
                translate('reports.building_report_description'),
        },

        unit: {
            title:
                translate('reports.unit_report'),

            subtitle:
                translate('reports.unit_report_description'),
        },

        tenant: {
            title:
                translate('reports.tenant_statement'),

            subtitle:
                translate('reports.tenant_statement_description'),
        },

        payments: {
            title:
                translate('reports.payments_report'),

            subtitle:
                translate('reports.payments_report_description'),
        },

        occupancy: {
            title:
                translate('reports.occupancy_report'),

            subtitle:
                translate('reports.occupancy_report_description'),
        },

        arrears: {
            title:
                translate('reports.arrears_report'),

            subtitle:
                translate('reports.arrears_report_description'),
        },

        funds: {
            title:
                translate('reports.funds_report'),

            subtitle:
                translate('reports.funds_report_description'),
        },
    };

    const definition =
        definitions[
            selectedReportType
        ];

    title.textContent =
        definition?.title
        ?? translate('reports.report');

    subtitle.textContent =
        definition?.subtitle
        ?? '';
}

/*
|--------------------------------------------------------------------------
| Subject Search
|--------------------------------------------------------------------------
*/

function initializeSubjectSearch() {
    const input =
        document.getElementById(
            'report-subject-search'
        );

    if (! input) {
        return;
    }

    input.addEventListener(
        'input',
        () => {
            if (
                selectedSubject
            ) {
                clearSubjectSelection(
                    false
                );
            }

            window.clearTimeout(
                searchTimer
            );

            const search =
                input.value.trim();

            if (
                search.length
                < 2
            ) {
                hideSubjectResults();

                return;
            }

            searchTimer =
                window.setTimeout(
                    async () => {
                        await searchSubjects(
                            search
                        );
                    },
                    300
                );
        }
    );

    document
        .getElementById(
            'report-clear-subject'
        )
        ?.addEventListener(
            'click',
            () => {
                clearSubjectSelection();

                input.focus();
            }
        );
}

async function searchSubjects(
    search
) {
    const container =
        document.getElementById(
            'report-subject-results'
        );

    if (! container) {
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

    container.classList.remove(
        'hidden'
    );

    try {
        const endpoint =
            subjectSearchEndpoint(
                search
            );

        const response =
            await apiRequest(
                endpoint
            );

        const data =
            await parseJsonResponse(
                response
            );

        const subjects =
            normalizeSearchResults(
                data
            );

        renderSubjectResults(
            subjects
        );
    } catch (error) {
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

function subjectSearchEndpoint(
    search
) {
    const params =
        new URLSearchParams();

    params.set(
        'search',
        search
    );

    params.set(
        'per_page',
        '10'
    );

    switch (
        selectedReportType
    ) {
        case 'owner':
            return `/api/owner-accounts?${params.toString()}`;

        case 'tenant':
            params.set(
                'role',
                'tenant'
            );

            return `/api/parties?${params.toString()}`;

        case 'building':
            return `/api/buildings?${params.toString()}`;

        case 'unit':
            return `/api/units?${params.toString()}`;

        default:
            throw new Error(
                translate('reports.subject_not_required')
            );
    }
}

function normalizeSearchResults(
    data
) {
    const rows =
        Array.isArray(
            data?.data
        )
            ? data.data
            : [];

    switch (
        selectedReportType
    ) {
        case 'owner':
            return rows.map(
                (account) => ({
                    id:
                        account.party_id,

                    apiId:
                        account.party_id,

                    name:
                        partyDisplayName(
                            account.party
                            ?? {}
                        ),

                    meta:
                        contactSummary(
                            account.party
                            ?? {}
                        ),

                    secondary:
                        formatCurrency(
                            account.balance
                            ?? 0
                        ),
                })
            );

        case 'tenant':
            return rows.map(
                (party) => ({
                    id:
                        party.id,

                    apiId:
                        party.id,

                    name:
                        partyDisplayName(
                            party
                        ),

                    meta:
                        contactSummary(
                            party
                        ),
                })
            );

        case 'building':
            return rows.map(
                (building) => ({
                    id:
                        building.id,

                    apiId:
                        building.id,

                    name:
                        building.name
                        ?? translate(
                            'reports.building_number',
                            {
                                number:
                                    building.id,
                            }
                        ),

                    meta:
                        [
                            building.location,
                            building.address,
                        ]
                            .filter(Boolean)
                            .join(' · '),
                })
            );

        case 'unit':
            return rows.map(
                (unit) => ({
                    id:
                        unit.id,

                    apiId:
                        unit.id,

                    name:
                        unit.name
                        ?? translate(
                            'reports.unit_number',
                            {
                                number:
                                    unit.id,
                            }
                        ),

                    meta:
                        unit?.building?.name
                        ?? '',
                })
            );

        default:
            return [];
    }
}

function renderSubjectResults(
    subjects
) {
    const container =
        document.getElementById(
            'report-subject-results'
        );

    if (! container) {
        return;
    }

    if (
        subjects.length
        === 0
    ) {
        container.innerHTML = `
            <div
                class="
                    px-4 py-4
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'reports.no_matching_records'
                    )
                )}
            </div>
        `;

        container.classList.remove(
            'hidden'
        );

        return;
    }

    container.innerHTML =
        subjects
            .map(
                (subject) => `
                    <button
                        type="button"
                        data-report-subject-id="${escapeAttribute(
                            subject.id
                        )}"
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
                                    ${escapeHtml(
                                        subject.name
                                    )}
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
                                                ${escapeHtml(
                                                    subject.meta
                                                )}
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
                                            ${escapeHtml(
                                                subject.secondary
                                            )}
                                        </div>
                                    `
                                    : ''
                            }

                        </div>
                    </button>
                `
            )
            .join('');

    subjects.forEach(
        (subject) => {
            container
                .querySelector(
                    `[data-report-subject-id="${subject.id}"]`
                )
                ?.addEventListener(
                    'click',
                    () => {
                        selectSubject(
                            subject
                        );
                    }
                );
        }
    );
}

function selectSubject(
    subject
) {
    selectedSubject =
        subject;

    setFieldValue(
        'report-subject-id',
        subject.apiId
    );

    setFieldValue(
        'report-subject-search',
        subject.name
    );

    setText(
        'report-selected-subject-name',
        subject.name
    );

    setText(
        'report-selected-subject-meta',
        subject.meta
        ?? ''
    );

    document
        .getElementById(
            'report-selected-subject'
        )
        ?.classList.remove(
            'hidden'
        );

    hideSubjectResults();

    updateRunButton();
}

function clearSubjectSelection(
    clearSearch = true
) {
    selectedSubject =
        null;

    setFieldValue(
        'report-subject-id',
        ''
    );

    if (
        clearSearch
    ) {
        setFieldValue(
            'report-subject-search',
            ''
        );
    }

    document
        .getElementById(
            'report-selected-subject'
        )
        ?.classList.add(
            'hidden'
        );

    updateRunButton();
}

function hideSubjectResults() {
    const container =
        document.getElementById(
            'report-subject-results'
        );

    if (! container) {
        return;
    }

    container.innerHTML = '';

    container.classList.add(
        'hidden'
    );
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
        .querySelectorAll(
            '[data-report-date-picker]'
        )
        .forEach(
            (button) => {
                const fieldId =
                    button.dataset
                        .reportDatePicker;

                const visibleInput =
                    document.getElementById(
                        fieldId
                    );

                const picker =
                    document.getElementById(
                        `${fieldId}-picker`
                    );

                if (
                    ! visibleInput
                    || ! picker
                ) {
                    return;
                }

                button.addEventListener(
                    'click',
                    () => {
                        const iso =
                            dateForApi(
                                visibleInput.value
                            );

                        if (
                            /^\d{4}-\d{2}-\d{2}$/.test(
                                iso
                            )
                        ) {
                            picker.value =
                                iso;
                        }

                        if (
                            typeof picker.showPicker
                            === 'function'
                        ) {
                            picker.showPicker();
                        } else {
                            picker.click();
                        }
                    }
                );

                picker.addEventListener(
                    'change',
                    () => {
                        visibleInput.value =
                            dateForDisplay(
                                picker.value
                            );

                        visibleInput.dispatchEvent(
                            new Event(
                                'change',
                                {
                                    bubbles: true,
                                }
                            )
                        );
                    }
                );
            }
        );
}



/*
|--------------------------------------------------------------------------
| Payments Report Filters
|--------------------------------------------------------------------------
*/

let paymentReportOptionsLoaded =
    false;


function initializePaymentReportFilters() {
    [
        'payment-report-tenant',
        'payment-report-lease',
        'payment-report-building',
        'payment-report-unit',
        'payment-report-method',
        'payment-report-cash-receiver',
        'payment-report-reference',
    ].forEach(
        (id) => {
            const field =
                document.getElementById(
                    id
                );

            field?.addEventListener(
                'change',
                updateRunButton
            );
        }
    );
}


async function loadPaymentReportFilterOptions() {
    if (paymentReportOptionsLoaded) {
        return;
    }

    paymentReportOptionsLoaded =
        true;

    try {
        const [
            tenantsResponse,
            leasesResponse,
            buildingsResponse,
            unitsResponse,
        ] = await Promise.all([
            apiRequest(
                '/api/parties?role=tenant&per_page=500'
            ),
            apiRequest(
                '/api/leases?per_page=500'
            ),
            apiRequest(
                '/api/buildings?per_page=500'
            ),
            apiRequest(
                '/api/units?per_page=500'
            ),
        ]);

        const [
            tenantsPayload,
            leasesPayload,
            buildingsPayload,
            unitsPayload,
        ] = await Promise.all([
            parseJsonResponse(
                tenantsResponse
            ),
            parseJsonResponse(
                leasesResponse
            ),
            parseJsonResponse(
                buildingsResponse
            ),
            parseJsonResponse(
                unitsResponse
            ),
        ]);

        populatePaymentReportSelect(
            'payment-report-tenant',
            collectionRows(
                tenantsPayload
            ),
            (tenant) =>
                partyDisplayName(
                    tenant
                )
        );

        populatePaymentReportSelect(
            'payment-report-lease',
            collectionRows(
                leasesPayload
            ),
            (lease) =>
                [
                    `#${lease.id}`,
                    partyDisplayName(
                        lease?.tenant
                        ?? {}
                    ),
                    lease?.unit?.building?.name,
                    lease?.unit?.name,
                ]
                    .filter(Boolean)
                    .join(' · ')
        );

        populatePaymentReportSelect(
            'payment-report-building',
            collectionRows(
                buildingsPayload
            ),
            (building) =>
                building?.name
                ?? `#${building.id}`
        );

        populatePaymentReportSelect(
            'payment-report-unit',
            collectionRows(
                unitsPayload
            ),
            (unit) =>
                [
                    unit?.building?.name,
                    unit?.name
                    ?? `#${unit.id}`,
                ]
                    .filter(Boolean)
                    .join(' · ')
        );
    } catch (error) {
        paymentReportOptionsLoaded =
            false;

        showReportsError(
            error instanceof Error
                ? error.message
                : translate(
                    'reports.unable_to_load_payment_filters'
                )
        );
    }
}


function collectionRows(
    payload
) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (
        Array.isArray(
            payload?.data
        )
    ) {
        return payload.data;
    }

    return [];
}


function populatePaymentReportSelect(
    id,
    rows,
    labelBuilder
) {
    const select =
        document.getElementById(
            id
        );

    if (! select) {
        return;
    }

    const firstOption =
        select.options[0]
            ?.cloneNode(
                true
            );

    select.innerHTML = '';

    if (firstOption) {
        select.appendChild(
            firstOption
        );
    }

    rows.forEach(
        (row) => {
            const option =
                document.createElement(
                    'option'
                );

            option.value =
                String(
                    row.id
                );

            option.textContent =
                labelBuilder(
                    row
                );

            select.appendChild(
                option
            );
        }
    );
}


function paymentReportQuery(
    from,
    to
) {
    const query =
        new URLSearchParams();

    const values = {
        from,
        to,

        tenant_id:
            fieldValue(
                'payment-report-tenant'
            ),

        lease_id:
            fieldValue(
                'payment-report-lease'
            ),

        building_id:
            fieldValue(
                'payment-report-building'
            ),

        unit_id:
            fieldValue(
                'payment-report-unit'
            ),

        payment_method:
            fieldValue(
                'payment-report-method'
            ),

        cash_receiver:
            fieldValue(
                'payment-report-cash-receiver'
            ),

        reference:
            fieldValue(
                'payment-report-reference'
            ),
    };

    Object.entries(
        values
    ).forEach(
        ([key, value]) => {
            if (
                value !== null
                && value !== undefined
                && String(value).trim() !== ''
            ) {
                query.set(
                    key,
                    value
                );
            }
        }
    );

    return query;
}


/*
|--------------------------------------------------------------------------
| Period and Report Execution
|--------------------------------------------------------------------------
*/

function initializePeriodControls() {
    document
        .getElementById(
            'run-report-button'
        )
        ?.addEventListener(
            'click',
            async () => {
                await runReport();
            }
        );
}

function updateRunButton() {
    const button =
        document.getElementById(
            'run-report-button'
        );

    if (! button) {
        return;
    }

    const needsSubject =
        ! SUBJECTLESS_REPORT_TYPES.includes(
            selectedReportType
        );

    button.disabled =
        needsSubject
        && ! selectedSubject;
}

async function runReport() {
    hideReportsError();

    const from =
        dateForApi(
            fieldValue(
                'report-from'
            )
        );

    const to =
        dateForApi(
            fieldValue(
                'report-to'
            )
        );

    if (
        ! PERIODLESS_REPORT_TYPES.includes(
            selectedReportType
        )
        && from
        && to
        && from > to
    ) {
        showReportsError(
            translate('reports.invalid_period')
        );

        return;
    }

    if (
        ! SUBJECTLESS_REPORT_TYPES.includes(
            selectedReportType
        )
        && ! selectedSubject
    ) {
        showReportsError(
            translate('reports.select_subject_first')
        );

        return;
    }

    const endpoints =
        buildReportEndpoints(
            from,
            to
        );

    activeJsonEndpoint =
        endpoints.json;

    activePdfEndpoint =
        endpoints.pdf;

    activeCsvEndpoint =
        endpoints.csv;

    activeXlsxEndpoint =
        endpoints.xlsx;

    showReportLoading();

    try {
        const response =
            await apiRequest(
                activeJsonEndpoint
            );

        const report =
            await parseJsonResponse(
                response
            );

        renderReport(
            report
        );

        showExportActions();
    } catch (error) {
        hideExportActions();

        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_generate')
        );

        renderReportError();
    }
}

function buildReportEndpoints(
    from,
    to
) {
    let query;

    if (
        selectedReportType
        === 'payments'
    ) {
        query =
            paymentReportQuery(
                from,
                to
            );
    } else if (
        PERIODLESS_REPORT_TYPES.includes(
            selectedReportType
        )
    ) {
        query =
            new URLSearchParams();

        if (
            AS_OF_REPORT_TYPES.includes(
                selectedReportType
            )
        ) {
            const asOf =
                dateForApi(
                    fieldValue(
                        'report-as-of'
                    )
                );

            if (asOf) {
                query.set(
                    'as_of',
                    asOf
                );
            }
        }
    } else {
        query =
            new URLSearchParams();

        if (from) {
            query.set(
                'from',
                from
            );
        }

        if (to) {
            query.set(
                'to',
                to
            );
        }
    }

    const suffix =
        query.toString()
            ? `?${query.toString()}`
            : '';

    let base;

    switch (
        selectedReportType
    ) {
        case 'owner':
            base =
                `/api/reports/owners/${selectedSubject.apiId}`;

            break;

        case 'building':
            base =
                `/api/reports/buildings/${selectedSubject.apiId}`;

            break;

        case 'unit':
            base =
                `/api/reports/units/${selectedSubject.apiId}`;

            break;

        case 'tenant':
            base =
                `/api/reports/tenants/${selectedSubject.apiId}`;

            break;

        case 'payments':
            base =
                '/api/reports/payments';

            break;

        case 'occupancy':
            base =
                '/api/reports/occupancy';

            break;

        case 'arrears':
            base =
                '/api/reports/arrears';

            break;

        case 'funds':
            base =
                '/api/reports/funds';

            break;

        default:
            base =
                '/api/reports/managing-organisation';
    }

    return {
        json:
            `${base}${suffix}`,

        pdf:
            `${base}/pdf${suffix}`,

        csv:
            `${base}/csv${suffix}`,

        xlsx:
            `${base}/xlsx${suffix}`,
    };
}

/*
|--------------------------------------------------------------------------
| Report Rendering
|--------------------------------------------------------------------------
*/

function renderReport(
    report
) {
    switch (
        selectedReportType
    ) {
        case 'owner':
            renderOwnerReport(
                report
            );

            break;

        case 'building':
            renderBuildingReport(
                report
            );

            break;

        case 'unit':
            renderUnitReport(
                report
            );

            break;

        case 'tenant':
            renderTenantReport(
                report
            );

            break;

        case 'payments':
            renderPaymentReport(
                report
            );

            break;

        case 'occupancy':
            renderOccupancyReport(
                report
            );

            break;

        case 'arrears':
            renderArrearsReport(
                report
            );

            break;

        case 'funds':
            renderFundsReport(
                report
            );

            break;

        default:
            renderManagingOrganisationReport(
                report
            );
    }
}


/*
|--------------------------------------------------------------------------
| Payments Report
|--------------------------------------------------------------------------
*/

function renderPaymentReport(
    report
) {
    const summary =
        report?.summary
        ?? {};

    const payments =
        Array.isArray(
            report?.payments
        )
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
                                    formatDate(
                                        payment.payment_date
                                    )
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
                                    formatCurrency(
                                        payment.amount
                                        ?? 0
                                    )
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
                                        translate(
                                            'reports.receipt'
                                        )
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
                            translate(
                                'reports.no_payments_found'
                            )
                        )}
                    </td>
                </tr>
            `;

    renderReportHtml(`
        ${metricGrid([
            [
                translate(
                    'reports.payment_count'
                ),
                numberFormat(
                    summary.payment_count
                    ?? 0
                ),
            ],
            [
                translate(
                    'reports.total_received'
                ),
                formatCurrency(
                    summary.total_received
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate(
                'reports.payments'
            ),
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
                                    translate(
                                        'reports.payment_number'
                                    )
                                )}

                                ${paymentReportHeading(
                                    translate('reports.tenant')
                                )}

                                ${paymentReportHeading(
                                    translate('reports.property')
                                )}

                                ${paymentReportHeading(
                                    translate(
                                        'reports.payment_method_label'
                                    )
                                )}

                                ${paymentReportHeading(
                                    translate(
                                        'reports.cash_receiver'
                                    )
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
        .querySelectorAll(
            '[data-payment-report-receipt]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        const endpoint =
                            button.dataset
                                .paymentReportReceipt;

                        if (! endpoint) {
                            return;
                        }

                        await openAuthenticatedDocument(
                            endpoint,
                            'application/pdf'
                        );
                    }
                );
            }
        );
}


/*
|--------------------------------------------------------------------------
| Managing Organisation Report
|--------------------------------------------------------------------------
*/

function renderManagingOrganisationReport(
    report
) {
    const portfolio =
        report.portfolio
        ?? {};

    const billing =
        report.billing
        ?? {};

    const owner =
        report.owner_accounting
        ?? {};

    const funds =
        report.tenant_funds
        ?? {};

    renderReportHtml(`
        ${periodHtml(report.period)}

        ${metricGrid([
            [
                translate('reports.buildings'),
                numberFormat(
                    portfolio.buildings
                ),
            ],
            [
                translate('reports.units'),
                numberFormat(
                    portfolio.units
                ),
            ],
            [
                translate('reports.owner_accounts'),
                numberFormat(
                    portfolio.owner_accounts
                ),
            ],
            [
                translate('reports.cash_received'),
                formatCurrency(
                    billing.cash_received
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.billing'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(
                        billing.invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_invoiced'),
                    formatCurrency(
                        billing.rent_invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_invoiced'),
                    formatCurrency(
                        billing.security_deposit_debt_invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.settled'),
                    formatCurrency(
                        billing.settled
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(
                        billing.rent_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        billing.security_deposit_debt_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(
                        billing.total_outstanding
                        ?? billing.outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.cash_received'),
                    formatCurrency(
                        billing.cash_received
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.owner_accounting'),
            pairGrid([
                [
                    translate('reports.rent_entitlement'),
                    formatCurrency(
                        owner.rent_entitlement
                        ?? 0
                    ),
                ],
                [
                    translate('reports.management_fees'),
                    formatCurrency(
                        owner.management_fees
                        ?? 0
                    ),
                ],
                [
                    translate('reports.agent_commissions'),
                    formatCurrency(
                        owner.agent_commissions
                        ?? 0
                    ),
                ],
                [
                    translate('reports.owner_expenses'),
                    formatCurrency(
                        owner.owner_expenses
                        ?? 0
                    ),
                ],
                [
                    translate('reports.owner_payouts'),
                    formatCurrency(
                        owner.owner_payouts
                        ?? 0
                    ),
                ],
                [
                    translate('reports.owner_funds_held'),
                    formatCurrency(
                        owner.owner_funds_held
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.tenant_funds'),
            pairGrid([
                [
                    translate('reports.rent_reserve'),
                    formatCurrency(
                        funds.rent_reserve
                        ?? 0
                    ),
                ],
                [
                    translate('reports.consumable_advance'),
                    formatCurrency(
                        funds.consumable_advance
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit'),
                    formatCurrency(
                        funds.security_deposit
                        ?? 0
                    ),
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

function renderOwnerReport(
    report
) {
    const owner =
        report.owner
        ?? {};

    const summary =
        report.summary
        ?? {};

    const transactions =
        Array.isArray(
            report.transactions
        )
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
                formatCurrency(
                    summary.opening_balance
                    ?? 0
                ),
            ],
            [
                translate('reports.credits'),
                formatCurrency(
                    summary.credits
                    ?? 0
                ),
            ],
            [
                translate('reports.debits'),
                formatCurrency(
                    summary.debits
                    ?? 0
                ),
            ],
            [
                translate('reports.closing_balance'),
                formatCurrency(
                    summary.closing_balance
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.financial_summary'),
            pairGrid([
                [
                    translate('reports.rent_collected'),
                    formatCurrency(
                        summary.rent_entitlement
                        ?? 0
                    ),
                ],
                [
                    translate('reports.owner_deposits'),
                    formatCurrency(
                        summary.owner_deposits
                        ?? 0
                    ),
                ],
                [
                    translate('reports.management_fees'),
                    formatCurrency(
                        summary.management_fees
                        ?? 0
                    ),
                ],
                [
                    translate('reports.agent_commissions'),
                    formatCurrency(
                        summary.agent_commissions
                        ?? 0
                    ),
                ],
                [
                    translate('reports.property_expenses'),
                    formatCurrency(
                        summary.expenses
                        ?? 0
                    ),
                ],
                [
                    translate('reports.payouts'),
                    formatCurrency(
                        summary.payouts
                        ?? 0
                    ),
                ],
                [
                    translate('reports.adjustments_credit'),
                    formatCurrency(
                        summary.adjustments_credit
                        ?? 0
                    ),
                ],
                [
                    translate('reports.adjustments_debit'),
                    formatCurrency(
                        summary.adjustments_debit
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.transactions'),
            ownerTransactionsTable(
                transactions
            )
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Building Report
|--------------------------------------------------------------------------
*/

function renderBuildingReport(
    report
) {
    const building =
        report.building
        ?? {};

    const summary =
        report.summary
        ?? {};

    const ownership =
        Array.isArray(
            report.ownership
        )
            ? report.ownership
            : [];

    const expenses =
        Array.isArray(
            report.expenses
        )
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
                numberFormat(
                    summary.units
                ),
            ],
            [
                translate('reports.leases'),
                numberFormat(
                    summary.leases
                ),
            ],
            [
                translate('reports.rent_outstanding'),
                formatCurrency(
                    summary.rent_outstanding
                    ?? 0
                ),
            ],
            [
                translate('reports.security_deposit_debt'),
                formatCurrency(
                    summary.security_deposit_debt_outstanding
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.financial_summary'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(
                        summary.invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_invoiced'),
                    formatCurrency(
                        summary.rent_invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_invoiced'),
                    formatCurrency(
                        summary.security_deposit_debt_invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.invoice_settled'),
                    formatCurrency(
                        summary.invoice_settled
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(
                        summary.rent_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        summary.security_deposit_debt_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(
                        summary.total_outstanding
                        ?? summary.outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.cash_received'),
                    formatCurrency(
                        summary.cash_received
                        ?? 0
                    ),
                ],
                [
                    translate('reports.property_expenses'),
                    formatCurrency(
                        summary.property_expenses
                        ?? 0
                    ),
                ],
                [
                    translate('reports.owner_rent_entitlement'),
                    formatCurrency(
                        summary.owner_rent_entitlement
                        ?? 0
                    ),
                ],
                [
                    translate('reports.management_fees'),
                    formatCurrency(
                        summary.management_fees
                        ?? 0
                    ),
                ],
                [
                    translate('reports.agent_commissions'),
                    formatCurrency(
                        summary.agent_commissions
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.ownership'),
            ownershipTable(
                ownership
            )
        )}

        ${reportSection(
            translate('reports.property_expenses'),
            expenseTable(
                expenses
            )
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Unit Report
|--------------------------------------------------------------------------
*/

function renderUnitReport(
    report
) {
    const unit =
        report.unit
        ?? {};

    const summary =
        report.summary
        ?? {};

    const leases =
        Array.isArray(
            report.leases
        )
            ? report.leases
            : [];

    const invoices =
        Array.isArray(
            report.invoices
        )
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
                numberFormat(
                    summary.leases
                ),
            ],
            [
                translate('reports.rent_outstanding'),
                formatCurrency(
                    summary.rent_outstanding
                    ?? 0
                ),
            ],
            [
                translate('reports.security_deposit_debt'),
                formatCurrency(
                    summary.security_deposit_debt_outstanding
                    ?? 0
                ),
            ],
            [
                translate('reports.total_outstanding'),
                formatCurrency(
                    summary.total_outstanding
                    ?? summary.outstanding
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.financial_summary'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(
                        summary.invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_invoiced'),
                    formatCurrency(
                        summary.rent_invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_invoiced'),
                    formatCurrency(
                        summary.security_deposit_debt_invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.settled'),
                    formatCurrency(
                        summary.settled
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(
                        summary.rent_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        summary.security_deposit_debt_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(
                        summary.total_outstanding
                        ?? summary.outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.cash_received'),
                    formatCurrency(
                        summary.cash_received
                        ?? 0
                    ),
                ],
                [
                    translate('reports.expenses'),
                    formatCurrency(
                        summary.expenses
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.lease_history'),
            leaseTable(
                leases
            )
        )}

        ${reportSection(
            translate('reports.invoices'),
            invoiceTable(
                invoices
            )
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Tenant Statement
|--------------------------------------------------------------------------
*/

function renderTenantReport(
    report
) {
    const tenant =
        report.tenant
        ?? {};

    const summary =
        report.summary
        ?? {};

    const leases =
        Array.isArray(
            report.leases
        )
            ? report.leases
            : [];

    const invoices =
        Array.isArray(
            report.invoices
        )
            ? report.invoices
            : [];

    const payments =
        Array.isArray(
            report.payments
        )
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
                formatCurrency(
                    summary.rent_outstanding
                    ?? 0
                ),
            ],
            [
                translate('reports.security_deposit_debt'),
                formatCurrency(
                    summary.security_deposit_debt_outstanding
                    ?? 0
                ),
            ],
            [
                translate('reports.total_outstanding'),
                formatCurrency(
                    summary.total_outstanding
                    ?? summary.outstanding
                    ?? 0
                ),
            ],
            [
                translate('reports.cash_received'),
                formatCurrency(
                    summary.cash_received
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.receivables'),
            pairGrid([
                [
                    translate('reports.total_invoiced'),
                    formatCurrency(
                        summary.invoiced
                        ?? 0
                    ),
                ],
                [
                    translate('reports.settled'),
                    formatCurrency(
                        summary.settled
                        ?? 0
                    ),
                ],
                [
                    translate('reports.rent_outstanding'),
                    formatCurrency(
                        summary.rent_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit_debt_outstanding'),
                    formatCurrency(
                        summary.security_deposit_debt_outstanding
                        ?? 0
                    ),
                ],
                [
                    translate('reports.total_outstanding'),
                    formatCurrency(
                        summary.total_outstanding
                        ?? summary.outstanding
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.held_funds'),
            pairGrid([
                [
                    translate('reports.rent_reserve'),
                    formatCurrency(
                        summary.rent_reserve_balance
                        ?? 0
                    ),
                ],
                [
                    translate('reports.consumable_advance'),
                    formatCurrency(
                        summary.consumable_advance_balance
                        ?? 0
                    ),
                ],
                [
                    translate('reports.security_deposit'),
                    formatCurrency(
                        summary.security_deposit_balance
                        ?? 0
                    ),
                ],
            ])
        )}

        ${reportSection(
            translate('reports.leases'),
            tenantLeaseTable(
                leases
            )
        )}

        ${reportSection(
            translate('reports.invoices'),
            tenantInvoiceTable(
                invoices
            )
        )}

        ${reportSection(
            translate('reports.payments'),
            tenantPaymentTable(
                payments
            )
        )}
    `);
}

/*
|--------------------------------------------------------------------------
| Occupancy Report
|--------------------------------------------------------------------------
*/

function renderOccupancyReport(
    report
) {
    const totals =
        report?.totals
        ?? {};

    const classification =
        report?.classification
        ?? {};

    const buildings =
        Array.isArray(
            report?.buildings
        )
            ? report.buildings
            : [];

    renderReportHtml(`
        ${asOfHtml(report?.as_of)}

        ${metricGrid([
            [
                translate('reports.units'),
                numberFormat(
                    totals.units
                    ?? 0
                ),
            ],
            [
                translate('reports.occupied'),
                numberFormat(
                    totals.occupied
                    ?? 0
                ),
            ],
            [
                translate('reports.vacant'),
                numberFormat(
                    totals.vacant
                    ?? 0
                ),
            ],
            [
                translate('reports.occupancy_rate'),
                percentValue(
                    totals.occupancy_rate
                ),
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
                        classification.commercial
                        ?? {}
                    )}

                    ${occupancyClassificationCard(
                        translate('reports.residential'),
                        classification.residential
                        ?? {}
                    )}
                </div>
            `
        )}

        ${reportSection(
            translate('reports.buildings'),
            occupancyBuildingsTable(
                buildings
            )
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
            numberFormat(
                data.units
                ?? 0
            ),
        ],
        [
            translate('reports.occupied'),
            numberFormat(
                data.occupied
                ?? 0
            ),
        ],
        [
            translate('reports.vacant'),
            numberFormat(
                data.vacant
                ?? 0
            ),
        ],
        [
            translate('reports.occupancy_rate'),
            percentValue(
                data.occupancy_rate
            ),
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
                ${escapeHtml(
                    title
                )}
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
                                    ${escapeHtml(
                                        label
                                    )}
                                </div>

                                <div
                                    class="
                                        mt-1 text-sm
                                        font-semibold
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(
                                        value
                                    )}
                                </div>
                            </div>
                        `
                    )
                    .join('')}
            </div>
        </div>
    `;
}

function occupancyBuildingsTable(
    rows
) {
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
                ?? translate(
                    'reports.building_number',
                    {
                        number:
                            row.id,
                    }
                ),
                numberFormat(
                    row.units
                    ?? 0
                ),
                numberFormat(
                    row.occupied
                    ?? 0
                ),
                numberFormat(
                    row.vacant
                    ?? 0
                ),
                percentValue(
                    row.occupancy_rate
                ),
                numberFormat(
                    row.commercial_units
                    ?? 0
                ),
            ]
        )
    );
}

/*
|--------------------------------------------------------------------------
| Arrears Aging Report
|--------------------------------------------------------------------------
*/

function renderArrearsReport(
    report
) {
    const totals =
        report?.totals
        ?? {};

    const tenants =
        Array.isArray(
            report?.tenants
        )
            ? report.tenants
            : [];

    renderReportHtml(`
        ${asOfHtml(report?.as_of)}

        ${metricGrid([
            [
                translate('reports.aging_current'),
                formatCurrency(
                    totals.current
                    ?? 0
                ),
            ],
            [
                translate('reports.aging_31_60'),
                formatCurrency(
                    totals.days_31_60
                    ?? 0
                ),
            ],
            [
                translate('reports.aging_61_90'),
                formatCurrency(
                    totals.days_61_90
                    ?? 0
                ),
            ],
            [
                translate('reports.aging_over_90'),
                formatCurrency(
                    totals.over_90
                    ?? 0
                ),
                {
                    emphasis:
                        'danger',
                },
            ],
            [
                translate('reports.total_arrears'),
                formatCurrency(
                    totals.total
                    ?? 0
                ),
            ],
            [
                translate('reports.open_invoices'),
                numberFormat(
                    totals.invoice_count
                    ?? 0
                ),
            ],
        ])}

        ${reportSection(
            translate('reports.tenants_in_arrears'),
            arrearsTenantsTable(
                tenants
            )
        )}
    `);
}

function arrearsTenantsTable(
    rows
) {
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
                row?.building?.name
                    ?? '',
                row?.unit?.name
                    ?? '',
                numberFormat(
                    row.invoice_count
                    ?? 0
                ),
                formatCurrency(
                    row.current
                    ?? 0
                ),
                formatCurrency(
                    row.days_31_60
                    ?? 0
                ),
                formatCurrency(
                    row.days_61_90
                    ?? 0
                ),
                formatCurrency(
                    row.over_90
                    ?? 0
                ),
                formatCurrency(
                    row.total
                    ?? 0
                ),
            ]
        )
    );
}

/*
|--------------------------------------------------------------------------
| Funds Held Report
|--------------------------------------------------------------------------
*/

function renderFundsReport(
    report
) {
    const tenantFunds =
        report?.tenant_funds
        ?? {};

    const tenantSummary =
        tenantFunds.summary
        ?? {};

    const tenants =
        Array.isArray(
            tenantFunds.tenants
        )
            ? tenantFunds.tenants
            : [];

    const ownerFunds =
        report?.owner_funds
        ?? {};

    const ownerSummary =
        ownerFunds.summary
        ?? {};

    const owners =
        Array.isArray(
            ownerFunds.owners
        )
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
                            meta:
                                accountCountLabel(
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
                            meta:
                                accountCountLabel(
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
                            meta:
                                accountCountLabel(
                                    tenantSummary?.security_deposit?.account_count
                                ),
                        },
                    ],
                    [
                        translate('reports.total_held'),
                        formatCurrency(
                            tenantSummary.total_held
                            ?? 0
                        ),
                    ],
                ])}

                ${fundsTenantsTable(
                    tenants
                )}
            `
        )}

        ${reportSection(
            translate('reports.owner_funds'),
            `
                ${metricGrid([
                    [
                        translate('reports.owner_accounts'),
                        numberFormat(
                            ownerSummary.account_count
                            ?? 0
                        ),
                    ],
                    [
                        translate('reports.total_held'),
                        formatCurrency(
                            ownerSummary.total_held
                            ?? 0
                        ),
                    ],
                ])}

                ${fundsOwnersTable(
                    owners
                )}
            `
        )}
    `);
}

function fundsTenantsTable(
    rows
) {
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
                row?.building?.name
                    ?? '',
                row?.unit?.name
                    ?? '',
                formatCurrency(
                    row.rent_reserve
                    ?? 0
                ),
                formatCurrency(
                    row.consumable_advance
                    ?? 0
                ),
                formatCurrency(
                    row.security_deposit
                    ?? 0
                ),
                formatCurrency(
                    row.total
                    ?? 0
                ),
            ]
        )
    );
}

function fundsOwnersTable(
    rows
) {
    return tableHtml(
        [
            translate('reports.owner'),
            translate('reports.balance'),
        ],
        rows.map(
            (row) => [
                row?.owner?.name
                    ?? translate('reports.unnamed_party'),
                formatCurrency(
                    row.balance
                    ?? 0
                ),
            ]
        )
    );
}

function accountCountLabel(
    count
) {
    return translate(
        'reports.account_count',
        {
            count:
                numberFormat(
                    count
                    ?? 0
                ),
        }
    );
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
                ${escapeHtml(
                    title
                )}
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
                            ${escapeHtml(
                                subtitle
                            )}
                        </div>
                    `
                    : ''
            }
        </div>
    `;
}

function periodHtml(
    period
) {
    const from =
        period?.from;

    const to =
        period?.to;

    if (
        ! from
        && ! to
    ) {
        return `
            <div
                class="
                    mb-6 text-xs
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'reports.reporting_period_all_history'
                    )
                )}
            </div>
        `;
    }

    return `
        <div
            class="
                mb-6 text-xs
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(
                translate(
                    'reports.reporting_period'
                )
            )}:
            ${escapeHtml(
                from
                    ? formatDate(from)
                    : translate(
                        'reports.beginning'
                    )
            )}
            —
            ${escapeHtml(
                to
                    ? formatDate(to)
                    : translate(
                        'reports.present'
                    )
            )}
        </div>
    `;
}

/**
 * Reference-date line for snapshot reports.
 */
function asOfHtml(
    asOf
) {
    return `
        <div
            class="
                mb-6 text-xs
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(
                translate(
                    'reports.as_of'
                )
            )}:
            ${escapeHtml(
                asOf
                    ? formatDate(asOf)
                    : translate(
                        'reports.present'
                    )
            )}
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
function metricGrid(
    metrics
) {
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
                            options.emphasis
                            === 'danger';

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
                                ${escapeHtml(
                                    label
                                )}
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
                                ${escapeHtml(
                                    value
                                )}
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
                                            ${escapeHtml(
                                                options.meta
                                            )}
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

function pairGrid(
    rows
) {
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
                                ${escapeHtml(
                                    label
                                )}
                            </div>

                            <div
                                class="
                                    mt-1 text-sm
                                    font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(
                                    value
                                )}
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
                ${escapeHtml(
                    title
                )}
            </h3>

            ${body}
        </section>
    `;
}

function tableHtml(
    headers,
    rows
) {
    if (
        rows.length
        === 0
    ) {
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
                                (header) => `
                                    <th
                                        class="
                                            whitespace-nowrap
                                            px-4 py-3
                                            text-left
                                            text-xs font-semibold
                                            uppercase tracking-wide
                                            text-[var(--pm-text-muted)]
                                        "
                                    >
                                        ${escapeHtml(
                                            header
                                        )}
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
                                            (value) => `
                                                <td
                                                    class="
                                                        whitespace-nowrap
                                                        px-4 py-3
                                                        text-[var(--pm-text-secondary)]
                                                    "
                                                >
                                                    ${escapeHtml(
                                                        value
                                                    )}
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

function ownerTransactionsTable(
    rows
) {
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
                formatDate(
                    row.date
                ),
                translatedDomainValue(
                    'direction',
                    row.direction
                ),
                translatedDomainValue(
                    'category',
                    row.category
                ),
                formatCurrency(
                    row.amount
                    ?? 0
                ),
                row.building
                    ?? '',
                row.unit
                    ?? '',
                row.invoice
                    ?? '',
                row.reference
                    ?? '',
            ]
        )
    );
}

function ownershipTable(
    rows
) {
    return tableHtml(
        [
            translate('reports.owner'),
            translate('reports.ownership'),
        ],
        rows.map(
            (row) => [
                row.owner
                    ?? '',
                `${row.percentage ?? 0}%`,
            ]
        )
    );
}

function expenseTable(
    rows
) {
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
                formatDate(
                    row.date
                ),
                row.description
                    ?? '',
                formatCurrency(
                    row.amount
                    ?? 0
                ),
                row.unit_id
                    ? translate(
                        'reports.unit_number',
                        {
                            number:
                                row.unit_id,
                        }
                    )
                    : '',
                row.reference
                    ?? '',
            ]
        )
    );
}

function leaseTable(
    rows
) {
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
                row.tenant
                    ?? '',
                formatDate(
                    row.start_date
                ),
                row.end_date
                    ? formatDate(
                        row.end_date
                    )
                    : '',
                translatedDomainValue(
                    'status',
                    row.status
                ),
                formatCurrency(
                    row.rent_amount
                    ?? 0
                ),
                translatedDomainValue(
                    'frequency',
                    row.payment_frequency
                ),
            ]
        )
    );
}

function invoiceTable(
    rows
) {
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
                row.invoice_number
                    ?? '',
                translatedDomainValue(
                    'invoice_type',
                    row.type
                ),
                formatDate(
                    row.issue_date
                ),
                formatDate(
                    row.due_date
                ),
                formatCurrency(
                    row.total_amount
                    ?? 0
                ),
                formatCurrency(
                    row.paid_amount
                    ?? 0
                ),
                formatCurrency(
                    row.outstanding_amount
                    ?? 0
                ),
                translatedDomainValue(
                    'status',
                    row.status
                ),
            ]
        )
    );
}

function tenantLeaseTable(
    rows
) {
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
                row.building
                    ?? '',
                row.unit
                    ?? '',
                translatedDomainValue(
                    'status',
                    row.status
                ),
                formatDate(
                    row.start_date
                ),
                row.end_date
                    ? formatDate(
                        row.end_date
                    )
                    : '',
                formatCurrency(
                    row.rent_amount
                    ?? 0
                ),
            ]
        )
    );
}

function tenantInvoiceTable(
    rows
) {
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
                row.invoice_number
                    ?? '',
                translatedDomainValue(
                    'invoice_type',
                    row.type
                ),
                formatDate(
                    row.date
                ),
                formatDate(
                    row.due_date
                ),
                formatCurrency(
                    row.amount
                    ?? 0
                ),
                formatCurrency(
                    row.paid
                    ?? 0
                ),
                formatCurrency(
                    row.outstanding
                    ?? 0
                ),
                translatedDomainValue(
                    'status',
                    row.status
                ),
            ]
        )
    );
}

function tenantPaymentTable(
    rows
) {
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
                formatDate(
                    row.date
                ),
                formatCurrency(
                    row.amount
                    ?? 0
                ),
                translatedDomainValue(
                    'payment_method',
                    row.method
                ),
                row.reference
                    ?? '',
                formatCurrency(
                    row.allocated
                    ?? 0
                ),
                formatCurrency(
                    row.unallocated
                    ?? 0
                ),
            ]
        )
    );
}

/*
|--------------------------------------------------------------------------
| Export Actions
|--------------------------------------------------------------------------
*/

function initializeExportActions() {
    document
        .getElementById(
            'report-pdf-button'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (
                    activePdfEndpoint
                ) {
                    await openAuthenticatedDocument(
                        activePdfEndpoint,
                        'application/pdf'
                    );
                }
            }
        );

    document
        .getElementById(
            'report-xlsx-button'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (
                    activeXlsxEndpoint
                ) {
                    await downloadAuthenticatedDocument(
                        activeXlsxEndpoint,
                        'report.xlsx',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    );
                }
            }
        );

    document
        .getElementById(
            'report-csv-button'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (
                    activeCsvEndpoint
                ) {
                    await downloadAuthenticatedDocument(
                        activeCsvEndpoint,
                        'report.csv'
                    );
                }
            }
        );
}

async function openAuthenticatedDocument(
    endpoint,
    accept
) {
    hideReportsError();

    try {
        await openPdfInNewTab(
            endpoint,
            translate('reports.unable_to_open'),
            accept
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
        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            accept,
                    },
                }
            );

        if (! response.ok) {
            throw new Error(
                translate('reports.unable_to_download')
            );
        }

        const blob =
            await response.blob();

        const disposition =
            response.headers.get(
                'Content-Disposition'
            );

        const filename =
            filenameFromDisposition(
                disposition
            )
            || fallbackFilename;

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
            filename;

        document.body.appendChild(
            link
        );

        link.click();

        link.remove();

        URL.revokeObjectURL(
            url
        );
    } catch (error) {
        showReportsError(
            error instanceof Error
                ? error.message
                : translate('reports.unable_to_download')
        );
    }
}

function filenameFromDisposition(
    disposition
) {
    if (! disposition) {
        return '';
    }

    const match =
        disposition.match(
            /filename="?([^"]+)"?/i
        );

    return match?.[1]
        ?? '';
}

function showExportActions() {
    const actions =
        document.getElementById(
            'report-export-actions'
        );

    if (! actions) {
        return;
    }

    actions.classList.remove(
        'hidden'
    );

    document
        .getElementById(
            'report-pdf-button'
        )
        ?.classList.toggle(
            'hidden',
            ! activePdfEndpoint
        );

    document
        .getElementById(
            'report-xlsx-button'
        )
        ?.classList.toggle(
            'hidden',
            ! activeXlsxEndpoint
        );

    document
        .getElementById(
            'report-csv-button'
        )
        ?.classList.toggle(
            'hidden',
            ! activeCsvEndpoint
        );
}

function hideExportActions() {
    document
        .getElementById(
            'report-export-actions'
        )
        ?.classList.add(
            'hidden'
        );

    document
        .getElementById(
            'report-xlsx-button'
        )
        ?.classList.add(
            'hidden'
        );
}

/*
|--------------------------------------------------------------------------
| Output State
|--------------------------------------------------------------------------
*/

function renderReportHtml(
    html
) {
    const output =
        document.getElementById(
            'report-output'
        );

    if (output) {
        output.innerHTML =
            html;
    }
}

function showReportLoading() {
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

function renderReportError() {
    renderReportHtml(`
        <div
            class="
                flex min-h-[520px]
                items-center justify-center
                text-sm text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(translate('reports.could_not_generate'))}
        </div>
    `);
}

function clearReportOutput() {
    activeJsonEndpoint =
        null;

    activePdfEndpoint =
        null;

    activeCsvEndpoint =
        null;

    activeXlsxEndpoint =
        null;

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
                ${escapeHtml(translate('reports.select_criteria'))}
            </div>
        </div>
    `);
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showReportsError(
    message
) {
    const element =
        document.getElementById(
            'reports-error'
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

function hideReportsError() {
    const element =
        document.getElementById(
            'reports-error'
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

function partyDisplayName(
    party
) {
    return party?.name
        || party?.legal_name
        || translate('reports.unnamed_party');
}

function contactSummary(
    party
) {
    return [
        party?.phone,
        party?.email,
    ]
        .filter(Boolean)
        .join(' · ');
}

function numberFormat(
    value
) {
    return formatNumber(
        value
    );
}

/**
 * Render an API-provided percentage value (already expressed as a
 * number of percent, e.g. 92.5) for display.
 */
function percentValue(
    value
) {
    return `${numberFormat(value ?? 0)}%`;
}

function translatedDomainValue(
    group,
    value
) {
    const normalized =
        String(
            value
            ?? ''
        ).trim();

    if (! normalized) {
        return '';
    }

    const key =
        `reports.${group}.${normalized}`;

    const translated =
        translate(
            key
        );

    /*
     * Unknown future API values remain readable instead of exposing a
     * translation key. Persisted/API values themselves are never modified.
     */
    if (
        translated
        === key
    ) {
        return normalized
            .replaceAll(
                '_',
                ' '
            )
            .split(' ')
            .filter(Boolean)
            .map(
                (word) =>
                    word
                        .charAt(0)
                        .toUpperCase()
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

function fieldValue(
    id
) {
    const element =
        document.getElementById(
            id
        );

    return element
        ? String(
            element.value
            ?? ''
        ).trim()
        : '';
}

function setFieldValue(
    id,
    value
) {
    const element =
        document.getElementById(
            id
        );

    if (element) {
        element.value =
            value
            ?? '';
    }
}

function setText(
    id,
    value
) {
    const element =
        document.getElementById(
            id
        );

    if (element) {
        element.textContent =
            value
            ?? '';
    }
}

/*
|--------------------------------------------------------------------------
| Escaping
|--------------------------------------------------------------------------
*/

function escapeHtml(
    value
) {
    return String(
        value
        ?? ''
    )
        .replaceAll(
            '&',
            '&amp;'
        )
        .replaceAll(
            '<',
            '&lt;'
        )
        .replaceAll(
            '>',
            '&gt;'
        )
        .replaceAll(
            '"',
            '&quot;'
        )
        .replaceAll(
            "'",
            '&#039;'
        );
}

function escapeAttribute(
    value
) {
    return escapeHtml(
        value
    );
}
