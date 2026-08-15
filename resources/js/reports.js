import {
    apiRequest,
    formatCurrency,
    formatDate,
    formatNumber,
    parseJsonResponse,
    translate,
} from './core.js';

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

    initializeExportActions();

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
                    'bg-patrimoine-50',
                    active
                );

                button.classList.toggle(
                    'text-patrimoine-950',
                    active
                );

                button.classList.toggle(
                    'text-slate-700',
                    ! active
                );

                button.classList.toggle(
                    'hover:bg-slate-50',
                    ! active
                );
            }
        );

    const subjectSection =
        document.getElementById(
            'report-subject-section'
        );

    if (
        selectedReportType
        === 'managing-organisation'
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
                text-sm text-slate-400
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
                    text-sm text-red-600
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
                    text-sm text-slate-500
                "
            >
                No matching records found.
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
                            border-b border-slate-100
                            px-4 py-3 text-left
                            transition
                            last:border-b-0
                            hover:bg-slate-50
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
                                        text-slate-900
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
                                                    text-slate-500
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
                                                text-slate-600
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
        selectedReportType
        !== 'managing-organisation';

    button.disabled =
        needsSubject
        && ! selectedSubject;
}

async function runReport() {
    hideReportsError();

    const from =
        fieldValue(
            'report-from'
        );

    const to =
        fieldValue(
            'report-to'
        );

    if (
        from
        && to
        && from > to
    ) {
        showReportsError(
            translate('reports.invalid_period')
        );

        return;
    }

    if (
        selectedReportType
        !== 'managing-organisation'
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
    const query =
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

        default:
            renderManagingOrganisationReport(
                report
            );
    }
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
                border border-slate-200
                bg-slate-50/60 p-5
            "
        >
            <div
                class="
                    text-lg font-semibold
                    text-slate-950
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
                                text-slate-500
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
                    text-slate-500
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
                text-slate-500
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
                    ([label, value]) => `
                        <div
                            class="
                                rounded-xl
                                border border-slate-200
                                bg-white p-4
                            "
                        >
                            <div
                                class="
                                    text-xs font-medium
                                    uppercase tracking-wide
                                    text-slate-500
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
                                    text-slate-950
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
                                border border-slate-200
                                px-4 py-3
                            "
                        >
                            <div
                                class="
                                    text-xs
                                    text-slate-500
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
                                    text-slate-900
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
                border-slate-100 pt-6
            "
        >
            <h3
                class="
                    mb-4 text-base
                    font-semibold
                    text-slate-950
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
                    border-slate-200
                    px-5 py-8
                    text-center
                    text-sm text-slate-500
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
                border border-slate-200
            "
        >
            <table
                class="
                    min-w-full
                    divide-y divide-slate-200
                    text-sm
                "
            >
                <thead
                    class="
                        bg-slate-50
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
                                            text-slate-500
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
                        divide-y divide-slate-100
                        bg-white
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
                                                        text-slate-700
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
                translate('reports.unable_to_open')
            );
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        window.open(
            url,
            '_blank',
            'noopener,noreferrer'
        );

        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
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
    fallbackFilename
) {
    hideReportsError();

    try {
        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            'text/csv',
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
    document
        .getElementById(
            'report-export-actions'
        )
        ?.classList
        .remove(
            'hidden'
        );

    document
        .getElementById(
            'report-export-actions'
        )
        ?.classList
        .add(
            'flex'
        );
}

function hideExportActions() {
    const actions =
        document.getElementById(
            'report-export-actions'
        );

    actions
        ?.classList
        .add(
            'hidden'
        );

    actions
        ?.classList
        .remove(
            'flex'
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
                text-sm text-slate-400
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
                text-sm text-slate-500
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
                    text-sm text-slate-500
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
