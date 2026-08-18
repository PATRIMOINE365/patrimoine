import {
    apiRequest,
    escapeHtml,
    formatCurrency,
    formatDate,
    formatNumber,
    parseJsonResponse,
    translate,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Patrimoine Tenant Workspace
|--------------------------------------------------------------------------
|
| V1.0.1 provides a dedicated read-only tenant directory with tenant
| identity, lease history and financial information. Financial values are
| sourced from the existing Tenant Statement report so accounting logic
| remains centralized in the reporting service.
|
*/

let tenantSearchTimer = null;
let tenantPage = 1;
let selectedTenantId = null;


/**
 * Initialize the Tenants page when present.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeTenants() {
    const list =
        document.getElementById(
            'tenant-list'
        );

    if (! list) {
        return false;
    }

    initializeTenantSearch();

    await loadTenants();

    return true;
}


/**
 * Initialize tenant directory search.
 */
function initializeTenantSearch() {
    const input =
        document.getElementById(
            'tenant-search'
        );

    if (! input) {
        return;
    }

    input.addEventListener(
        'input',
        () => {
            window.clearTimeout(
                tenantSearchTimer
            );

            tenantPage = 1;

            tenantSearchTimer =
                window.setTimeout(
                    async () => {
                        await loadTenants(
                            1
                        );
                    },
                    300
                );
        }
    );
}


/**
 * Load Parties with the Tenant role.
 *
 * @param {number} page
 */
async function loadTenants(
    page = tenantPage
) {
    tenantPage = page;

    hideTenantError();

    showTenantLoading();

    try {
        const params =
            new URLSearchParams();

        params.set(
            'role',
            'tenant'
        );

        params.set(
            'page',
            String(page)
        );

        params.set(
            'per_page',
            '50'
        );

        const search =
            String(
                document
                    .getElementById(
                        'tenant-search'
                    )
                    ?.value
                ?? ''
            ).trim();

        if (search) {
            params.set(
                'search',
                search
            );
        }

        const response =
            await apiRequest(
                `/api/parties?${params.toString()}`
            );

        const pagination =
            await parseJsonResponse(
                response
            );

        renderTenantDirectory(
            pagination
        );

        renderTenantPagination(
            pagination
        );

        const tenants =
            Array.isArray(
                pagination?.data
            )
                ? pagination.data
                : [];

        /*
         * Keep the directory and detail pane synchronized.
         *
         * Rendering the directory replaces its DOM, so selection styling
         * alone is not enough to guarantee that the detail pane represents
         * the currently visible result set. Always resolve the effective
         * selection after each directory load.
         */
        if (tenants.length === 0) {
            selectedTenantId = null;

            renderTenantDetailEmpty(
                translate('tenants.no_tenant_available')
            );

            return;
        }

        const selectedTenantStillVisible =
            tenants.some(
                (tenant) =>
                    String(
                        tenant.id
                    ) === String(
                        selectedTenantId
                    )
            );

        const tenantToSelect =
            selectedTenantStillVisible
                ? selectedTenantId
                : tenants[0].id;

        await selectTenant(
            tenantToSelect
        );
    } catch (error) {
        showTenantError(
            error instanceof Error
                ? error.message
                : translate('tenants.unable_to_load')
        );

        renderTenantDirectoryEmpty(
            translate('tenants.unable_to_load')
        );
    }
}


/**
 * Render the tenant directory.
 *
 * @param {object} pagination
 */
function renderTenantDirectory(
    pagination
) {
    const container =
        document.getElementById(
            'tenant-list'
        );

    if (! container) {
        return;
    }

    const tenants =
        Array.isArray(
            pagination?.data
        )
            ? pagination.data
            : [];

    const total =
        Number(
            pagination?.total
            ?? tenants.length
        );

    if (tenants.length === 0) {
        renderTenantDirectoryEmpty(
            translate('tenants.no_search_results')
        );

        renderTenantDetailEmpty(
            translate('tenants.no_tenant_available')
        );

        return;
    }

    container.innerHTML =
        tenants
            .map(
                renderTenantDirectoryRow
            )
            .join('');

    container
        .querySelectorAll(
            '[data-tenant-id]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        await selectTenant(
                            button.dataset.tenantId
                        );
                    }
                );
            }
        );
}


/**
 * Render one tenant directory row.
 *
 * @param {object} tenant
 * @returns {string}
 */
function renderTenantDirectoryRow(
    tenant
) {
    const selected =
        String(
            selectedTenantId
            ?? ''
        ) === String(
            tenant.id
        );

    const name =
        tenantDisplayName(
            tenant
        );

    const initials =
        String(name)
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(
                (part) =>
                    part.charAt(0)
                        .toUpperCase()
            )
            .join('')
        || 'T';

    const primaryContact =
        tenant.phone
        || tenant.email
        || translate(
            'tenants.no_contact_information'
        );

    const secondaryContact =
        tenant.phone
        && tenant.email
            ? tenant.email
            : '';

    const type =
        tenantDynamicLabel(
            'party_type',
            tenant.type
            ?? 'person'
        );

    return `
        <button
            type="button"
            data-tenant-id="${escapeHtml(
                tenant.id
            )}"
            class="
                pm-tenant-directory-row
                ${
                    selected
                        ? 'pm-tenant-directory-row-selected'
                        : ''
                }
            "
            aria-current="${selected ? 'true' : 'false'}"
        >
            <span
                class="pm-tenant-directory-avatar"
                aria-hidden="true"
            >
                ${escapeHtml(initials)}
            </span>

            <span class="pm-tenant-directory-content">
                <span class="pm-tenant-directory-name">
                    ${escapeHtml(name)}
                </span>

                <span class="pm-tenant-directory-contact">
                    ${escapeHtml(primaryContact)}
                </span>

                ${
                    secondaryContact
                        ? `
                            <span
                                class="
                                    pm-tenant-directory-secondary
                                "
                            >
                                ${escapeHtml(
                                    secondaryContact
                                )}
                            </span>
                        `
                        : ''
                }
            </span>

            <span class="pm-tenant-directory-meta">
                <span class="pm-tenant-directory-type">
                    ${escapeHtml(type)}
                </span>

                <svg
                    class="pm-tenant-directory-chevron"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path
                        d="m7.5 5 5 5-5 5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </span>
        </button>
    `;
}


/**
 * Select and load one tenant Party.
 *
 * @param {number|string} tenantId
 */
async function selectTenant(
    tenantId
) {
    selectedTenantId =
        Number(
            tenantId
        );

    hideTenantError();

    updateTenantDirectorySelection();

    renderTenantDetailLoading();

    try {
        const response =
            await apiRequest(
                `/api/parties/${selectedTenantId}`
            );

        const tenant =
            await parseJsonResponse(
                response
            );

        if (
            ! Array.isArray(
                tenant.roles
            )
            || ! tenant.roles.some(
                (role) =>
                    role.role === 'tenant'
            )
        ) {
            throw new Error(
                translate('tenants.not_tenant')
            );
        }

        /*
         * Party show does not currently expose leases. Fetch them through
         * the existing Lease API so C1 remains read-only and avoids adding
         * duplicate tenant-domain endpoints.
         */
        const leasesResponse =
            await apiRequest(
                `/api/leases?tenant_id=${encodeURIComponent(
                    tenant.id
                )}&per_page=100`
            );

        const leasePayload =
            await parseJsonResponse(
                leasesResponse
            );

        const leases =
            Array.isArray(
                leasePayload?.data
            )
                ? leasePayload.data
                : [];

        /*
         * Reuse the existing Tenant Statement report as the authoritative
         * source for tenant financial information.
         *
         * This deliberately avoids reproducing invoice settlement, held-fund
         * or receivable calculations in the browser.
         */
        const statementResponse =
            await apiRequest(
                `/api/reports/tenants/${encodeURIComponent(
                    tenant.id
                )}`
            );

        const statement =
            await parseJsonResponse(
                statementResponse
            );

        /*
         * C3 operational history.
         *
         * The Tenant Statement remains authoritative for financial totals.
         * Existing Lease/Payment APIs provide ledger and receipt identifiers
         * without duplicating accounting calculations in the browser.
         */
        const operationalHistory =
            await loadTenantOperationalHistory(
                leases
            );

        renderTenantDetail(
            tenant,
            leases,
            statement,
            operationalHistory
        );
    } catch (error) {
        showTenantError(
            error instanceof Error
                ? error.message
                : translate('tenants.unable_to_load_details')
        );

        renderTenantDetailEmpty(
            translate('tenants.unable_to_load_tenant')
        );
    }
}


/**
 * Update directory selection styling.
 */
function updateTenantDirectorySelection() {
    document
        .querySelectorAll(
            '[data-tenant-id]'
        )
        .forEach(
            (button) => {
                const selected =
                    String(
                        button.dataset.tenantId
                    ) === String(
                        selectedTenantId
                    );

                button.classList.toggle(
                    'pm-tenant-directory-row-selected',
                    selected
                );

                button.setAttribute(
                    'aria-current',
                    selected
                        ? 'true'
                        : 'false'
                );
            }
        );
}


/**
 * Render selected tenant identity and lease history.
 *
 * @param {object} tenant
 * @param {Array<object>} leases
 * @param {object} statement
 */
function renderTenantDetail(
    tenant,
    leases,
    statement,
    operationalHistory = {}
) {
    const container =
        document.getElementById(
            'tenant-detail'
        );

    if (! container) {
        return;
    }

    const activeLeases =
        leases.filter(
            (lease) =>
                [
                    'active',
                    'notice',
                ].includes(
                    lease.status
                )
        );

    container.innerHTML = `
        <div
            class="
                border-b border-slate-100
                px-6 py-5
            "
        >
            <div
                class="
                    flex flex-col gap-5
                    lg:flex-row
                    lg:items-start
                    lg:justify-between
                "
            >
                <div>
                    <h2
                        class="
                            text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        ${escapeHtml(
                            tenantDisplayName(
                                tenant
                            )
                        )}
                    </h2>

                    <div
                        class="
                            mt-2 text-sm
                            text-slate-500
                        "
                    >
                        ${escapeHtml(
                            contactSummary(
                                tenant
                            )
                            || translate('tenants.no_contact_information')
                        )}
                    </div>
                </div>

                <a
                    href="/reports?type=tenant&tenant_id=${encodeURIComponent(
                        tenant.id
                    )}"
                    class="
                        inline-flex items-center
                        rounded-lg border
                        border-slate-200
                        bg-white px-3.5 py-2.5
                        text-sm font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.tenant_statement'
                        )
                    )}
                </a>
            </div>
        </div>

        <div
            class="
                grid gap-4
                border-b border-slate-100
                bg-slate-50/50
                px-6 py-5
                sm:grid-cols-3
            "
        >
            ${summaryMetric(
                translate('tenants.total_leases'),
                leases.length
            )}

            ${summaryMetric(
                translate('tenants.current_leases'),
                activeLeases.length
            )}

            ${summaryMetric(
                translate('tenants.historical_leases'),
                Math.max(
                    0,
                    leases.length
                    - activeLeases.length
                )
            )}
        </div>

        <div
            class="
                border-b border-slate-100
                px-6 py-6
            "
        >
            <h3
                class="
                    text-base font-semibold
                    text-slate-950
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.tenant_details'
                    )
                )}
            </h3>

            <div
                class="
                    mt-4 grid gap-5
                    sm:grid-cols-2
                    xl:grid-cols-3
                "
            >
                ${detailItem(
                    translate('tenants.party_type'),
                    tenantDynamicLabel(
                        'party_type',
                        tenant.type
                    )
                )}

                ${detailItem(
                    translate('tenants.phone'),
                    tenant.phone
                )}

                ${detailItem(
                    translate('tenants.alternate_phone'),
                    tenant.alternate_phone
                )}

                ${detailItem(
                    translate('tenants.email'),
                    tenant.email
                )}

                ${detailItem(
                    translate('tenants.address'),
                    tenant.address
                )}

                ${detailItem(
                    translate('tenants.id_registration'),
                    tenant.id_number
                    || tenant.registration_number
                )}
            </div>
        </div>

        <div class="px-6 py-6">
            <div>
                <h3
                    class="
                        text-base font-semibold
                        text-slate-950
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.leases'
                        )
                    )}
                </h3>

                <p
                    class="
                        mt-1 text-xs
                        text-slate-500
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.leases_description'
                        )
                    )}
                </p>
            </div>

            <div class="mt-4">
                ${renderTenantLeases(
                    leases
                )}
            </div>
        </div>

        <div
            class="
                border-t border-slate-100
                px-6 py-6
            "
        >
            ${renderTenantFinancialPosition(
                statement
            )}
        </div>

        <div
            class="
                border-t border-slate-100
                px-6 py-6
            "
        >
            ${renderTenantInvoices(
                statement?.invoices
            )}
        </div>

        <div
            class="
                border-t border-slate-100
                px-6 py-6
            "
        >
            ${renderTenantPayments(
                statement?.payments,
                operationalHistory?.payments
            )}
        </div>

        <div
            class="
                border-t border-slate-100
                px-6 py-6
            "
        >
            ${renderTenantFundHistory(
                operationalHistory?.fundTransactions
            )}
        </div>
    `;

    initializeTenantInvoiceActions();
    initializeTenantReceiptActions();
}


/**
 * Render the Tenant's current financial position.
 *
 * Values come directly from TenantStatementService. The browser performs
 * formatting only and does not recalculate accounting balances.
 *
 * @param {object} statement
 * @returns {string}
 */
function renderTenantFinancialPosition(
    statement
) {
    const summary =
        statement?.summary
        ?? {};

    return `
        <div>
            <h3
                class="
                    text-base font-semibold
                    text-slate-950
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.financial_position'
                    )
                )}
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.financial_position_description'
                    )
                )}
            </p>
        </div>

        <div
            class="
                mt-4 grid gap-3
                sm:grid-cols-2
                xl:grid-cols-3
            "
        >
            ${financialMetric(
                translate('tenants.rent_outstanding'),
                summary.rent_outstanding
            )}

            ${financialMetric(
                translate('tenants.security_deposit_debt'),
                summary.security_deposit_debt_outstanding
            )}

            ${financialMetric(
                translate('tenants.total_outstanding'),
                summary.total_outstanding
            )}
        </div>

        <div
            class="
                mt-6
                border-t border-slate-100
                pt-5
            "
        >
            <h4
                class="
                    text-sm font-semibold
                    text-slate-900
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.held_funds'
                    )
                )}
            </h4>

            <div
                class="
                    mt-3 grid gap-3
                    sm:grid-cols-2
                    xl:grid-cols-3
                "
            >
                ${financialMetric(
                    translate('tenants.rent_reserve'),
                    summary.rent_reserve_balance
                )}

                ${financialMetric(
                    translate('tenants.consumable_advance'),
                    summary.consumable_advance_balance
                )}

                ${financialMetric(
                    translate('tenants.security_deposit'),
                    summary.security_deposit_balance
                )}
            </div>
        </div>
    `;
}


/**
 * Render Tenant invoices from the Tenant Statement.
 *
 * @param {Array<object>} invoices
 * @returns {string}
 */
function renderTenantInvoices(
    invoices
) {
    const rows =
        Array.isArray(
            invoices
        )
            ? invoices
            : [];

    return `
        <div>
            <h3
                class="
                    text-base font-semibold
                    text-slate-950
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.invoices'
                    )
                )}
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.invoices_description'
                    )
                )}
            </p>
        </div>

        <div class="mt-4">
            ${
                rows.length === 0
                    ? financialEmptyState(
                        translate('tenants.no_invoices')
                    )
                    : `
                        <div
                            class="
                                overflow-x-auto
                                rounded-xl border
                                border-slate-200
                            "
                        >
                            <table
                                class="
                                    min-w-full
                                    divide-y divide-slate-200
                                    text-sm
                                "
                            >
                                <thead class="bg-slate-50">
                                    <tr>
                                        ${tableHeading(translate('tenants.invoice'))}
                                        ${tableHeading(translate('tenants.type'))}
                                        ${tableHeading(translate('tenants.date'))}
                                        ${tableHeading(translate('tenants.due_date'))}
                                        ${tableHeading(translate('tenants.amount'), true)}
                                        ${tableHeading(translate('tenants.paid'), true)}
                                        ${tableHeading(translate('tenants.outstanding'), true)}
                                        ${tableHeading(translate('tenants.status'))}
                                        ${tableHeading(translate('tenants.actions'))}
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
                                            renderTenantInvoiceRow
                                        )
                                        .join('')}
                                </tbody>
                            </table>
                        </div>
                    `
            }
        </div>
    `;
}


/**
 * Render one Tenant invoice row.
 *
 * @param {object} invoice
 * @returns {string}
 */
function renderTenantInvoiceRow(
    invoice
) {
    return `
        <tr>
            ${tableCell(
                invoice?.invoice_number
                || `#${invoice?.id ?? '—'}`,
                true
            )}

            ${tableCell(
                capitalizeWords(
                    invoice?.type
                    ?? 'unknown'
                )
            )}

            ${tableCell(
                invoice?.date
                    ? formatDate(
                        invoice.date
                    )
                    : '—'
            )}

            ${tableCell(
                invoice?.due_date
                    ? formatDate(
                        invoice.due_date
                    )
                    : '—'
            )}

            ${tableCell(
                formatCurrency(
                    invoice?.amount
                    ?? 0
                ),
                false,
                true
            )}

            ${tableCell(
                formatCurrency(
                    invoice?.paid
                    ?? 0
                ),
                false,
                true
            )}

            ${tableCell(
                formatCurrency(
                    invoice?.outstanding
                    ?? 0
                ),
                true,
                true
            )}

            ${tableCell(
                capitalizeWords(
                    invoice?.status
                    ?? 'unknown'
                )
            )}

            ${invoiceActionCell(
                invoice?.id
            )}
        </tr>
    `;
}


/**
 * Render document actions for one persisted Tenant Invoice.
 *
 * Existing authenticated Invoice PDF and email endpoints are reused.
 * The Tenant workspace remains read-oriented and introduces no duplicate
 * billing workflow.
 *
 * @param {number|string|null} invoiceId
 * @returns {string}
 */
function invoiceActionCell(
    invoiceId
) {
    if (! invoiceId) {
        return tableCell(
            '—'
        );
    }

    const safeId =
        escapeHtml(
            invoiceId
        );

    return `
        <td
            class="
                whitespace-nowrap
                px-4 py-3 text-left
            "
        >
            <div
                class="
                    flex items-center gap-2
                "
            >
                <button
                    type="button"
                    data-open-invoice="${safeId}"
                    class="
                        inline-flex items-center
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        shadow-sm transition
                        hover:border-slate-300
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.invoice'
                        )
                    )}
                </button>

                <button
                    type="button"
                    data-resend-invoice="${safeId}"
                    class="
                        inline-flex items-center
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        shadow-sm transition
                        hover:border-slate-300
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.resend'
                        )
                    )}
                </button>
            </div>
        </td>
    `;
}


/**
 * Wire Invoice document actions after Tenant detail rendering.
 */
function initializeTenantInvoiceActions() {
    document
        .querySelectorAll(
            '[data-open-invoice]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        await openTenantInvoice(
                            button
                        );
                    }
                );
            }
        );

    document
        .querySelectorAll(
            '[data-resend-invoice]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        await resendTenantInvoice(
                            button
                        );
                    }
                );
            }
        );
}


/**
 * Fetch and open an authenticated Tenant Invoice PDF.
 *
 * Direct browser navigation cannot carry the API Bearer token, so the PDF
 * is retrieved through apiRequest() and opened through a temporary blob URL.
 *
 * @param {HTMLButtonElement} button
 */
async function openTenantInvoice(
    button
) {
    const invoiceId =
        button.dataset.openInvoice;

    if (! invoiceId) {
        return;
    }

    const originalLabel =
        button.textContent;

    button.disabled = true;
    button.textContent =
        translate('tenants.opening');

    hideTenantError();

    try {
        const response =
            await apiRequest(
                `/api/invoices/${encodeURIComponent(
                    invoiceId
                )}/pdf`
            );

        if (! response.ok) {
            throw new Error(
                translate('tenants.unable_to_open_invoice')
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
        showTenantError(
            error instanceof Error
                ? error.message
                : translate('tenants.unable_to_open_invoice')
        );
    } finally {
        if (
            document.body.contains(
                button
            )
        ) {
            button.textContent =
                originalLabel;

            button.disabled =
                false;
        }
    }
}


/**
 * Resend an existing Tenant Invoice using the established email workflow.
 *
 * @param {HTMLButtonElement} button
 */
async function resendTenantInvoice(
    button
) {
    const invoiceId =
        button.dataset.resendInvoice;

    if (! invoiceId) {
        return;
    }

    const originalLabel =
        button.textContent;

    button.disabled = true;
    button.textContent =
        translate('tenants.sending');

    hideTenantError();

    try {
        const response =
            await apiRequest(
                `/api/invoices/${encodeURIComponent(
                    invoiceId
                )}/send-email`,
                {
                    method:
                        'POST',
                }
            );

        await parseJsonResponse(
            response
        );

        button.textContent =
            translate('tenants.sent');

        window.setTimeout(
            () => {
                if (
                    document.body.contains(
                        button
                    )
                ) {
                    button.textContent =
                        originalLabel;

                    button.disabled =
                        false;
                }
            },
            1800
        );
    } catch (error) {
        button.textContent =
            originalLabel;

        button.disabled =
            false;

        showTenantError(
            error instanceof Error
                ? error.message
                : translate('tenants.unable_to_resend_invoice')
        );
    }
}


/**
 * Render Tenant payment history from the Tenant Statement.
 *
 * @param {Array<object>} payments
 * @returns {string}
 */
function renderTenantPayments(
    payments,
    operationalPayments = []
) {
    const rows =
        Array.isArray(
            payments
        )
            ? payments
            : [];

    const paymentLookup =
        buildPaymentLookup(
            operationalPayments
        );

    return `
        <div>
            <h3
                class="
                    text-base font-semibold
                    text-slate-950
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.payments'
                    )
                )}
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.payments_description'
                    )
                )}
            </p>
        </div>

        <div class="mt-4">
            ${
                rows.length === 0
                    ? financialEmptyState(
                        translate('tenants.no_payments')
                    )
                    : `
                        <div
                            class="
                                overflow-x-auto
                                rounded-xl border
                                border-slate-200
                            "
                        >
                            <table
                                class="
                                    min-w-full
                                    divide-y divide-slate-200
                                    text-sm
                                "
                            >
                                <thead class="bg-slate-50">
                                    <tr>
                                        ${tableHeading(translate('tenants.date'))}
                                        ${tableHeading(translate('tenants.amount'), true)}
                                        ${tableHeading(translate('tenants.method'))}
                                        ${tableHeading(translate('tenants.reference'))}
                                        ${tableHeading(translate('tenants.allocated'), true)}
                                        ${tableHeading(translate('tenants.unallocated'), true)}
                                        ${tableHeading(translate('tenants.receipt'))}
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
                                            (payment) =>
                                                renderTenantPaymentRow(
                                                    payment,
                                                    paymentLookup
                                                )
                                        )
                                        .join('')}
                                </tbody>
                            </table>
                        </div>
                    `
            }
        </div>
    `;
}


/**
 * Render one Tenant payment row.
 *
 * @param {object} payment
 * @returns {string}
 */
function renderTenantPaymentRow(
    payment,
    paymentLookup
) {
    const operationalPayment =
        findOperationalPayment(
            payment,
            paymentLookup
        );

    return `
        <tr>
            ${tableCell(
                formatTenantDisplayDate(
                    payment?.date
                )
            )}

            ${tableCell(
                formatCurrency(
                    payment?.amount
                    ?? 0
                ),
                true,
                true
            )}

            ${tableCell(
                tenantDynamicLabel(
                    'payment_method',
                    payment?.method
                    ?? 'unknown'
                )
            )}

            ${tableCell(
                payment?.reference
                || '—'
            )}

            ${tableCell(
                formatCurrency(
                    payment?.allocated
                    ?? 0
                ),
                false,
                true
            )}

            ${tableCell(
                formatCurrency(
                    payment?.unallocated
                    ?? 0
                ),
                false,
                true
            )}

            ${receiptActionCell(
                operationalPayment?.id
            )}
        </tr>
    `;
}


/**
 * Load existing operational records required by the Tenant workspace.
 *
 * Financial totals remain sourced from TenantStatementService. These records
 * provide only receipt identifiers and the underlying tenant-fund ledger.
 *
 * @param {Array<object>} leases
 * @returns {Promise<object>}
 */
async function loadTenantOperationalHistory(
    leases
) {
    const payments = [];
    const fundTransactions = [];

    for (const lease of leases) {
        /*
         * Lease index responses may already include tenant-fund accounts.
         * When they do, consume that ledger directly.
         */
        collectFundTransactions(
            lease,
            fundTransactions
        );

        /*
         * Payment IDs are required for receipt download/resend. The existing
         * Payment API supports Lease filtering, so no new receipt endpoint or
         * financial read model is introduced for C3.
         */
        const paymentResponse =
            await apiRequest(
                `/api/payments?lease_id=${encodeURIComponent(
                    lease.id
                )}&per_page=100`
            );

        const paymentPayload =
            await parseJsonResponse(
                paymentResponse
            );

        const leasePayments =
            Array.isArray(
                paymentPayload?.data
            )
                ? paymentPayload.data
                : [];

        payments.push(
            ...leasePayments
        );

        /*
         * If the Lease index did not contain fund accounts, retrieve the
         * existing Lease detail representation. LeaseController already
         * exposes tenantFundAccounts.transactions.
         */
        if (
            ! Array.isArray(
                lease?.tenant_fund_accounts
            )
        ) {
            const leaseResponse =
                await apiRequest(
                    `/api/leases/${encodeURIComponent(
                        lease.id
                    )}`
                );

            const leaseDetail =
                await parseJsonResponse(
                    leaseResponse
                );

            collectFundTransactions(
                leaseDetail,
                fundTransactions
            );
        }
    }

    fundTransactions.sort(
        (left, right) => {
            const dateCompare =
                String(
                    right?.transaction_date
                    ?? ''
                ).localeCompare(
                    String(
                        left?.transaction_date
                        ?? ''
                    )
                );

            if (dateCompare !== 0) {
                return dateCompare;
            }

            return Number(
                right?.id
                ?? 0
            ) - Number(
                left?.id
                ?? 0
            );
        }
    );

    return {
        payments,
        fundTransactions,
    };
}


/**
 * Collect tenant-fund ledger entries from one Lease representation.
 *
 * @param {object} lease
 * @param {Array<object>} target
 */
function collectFundTransactions(
    lease,
    target
) {
    const accounts =
        Array.isArray(
            lease?.tenant_fund_accounts
        )
            ? lease.tenant_fund_accounts
            : [];

    accounts.forEach(
        (account) => {
            const transactions =
                Array.isArray(
                    account?.transactions
                )
                    ? account.transactions
                    : [];

            transactions.forEach(
                (transaction) => {
                    target.push({
                        ...transaction,
                        fund_type:
                            account.type,
                        lease_id:
                            lease.id,
                    });
                }
            );
        }
    );
}


/**
 * Build candidate Payment lookup keys for matching Tenant Statement rows.
 *
 * The Statement and Payment APIs represent the same persisted Payment but
 * expose slightly different field names.
 *
 * @param {Array<object>} payments
 * @returns {Map<string, Array<object>>}
 */
function buildPaymentLookup(
    payments
) {
    const lookup =
        new Map();

    payments.forEach(
        (payment) => {
            const key =
                paymentMatchKey({
                    date:
                        payment?.payment_date,
                    amount:
                        payment?.amount,
                    method:
                        payment?.payment_method,
                    reference:
                        payment?.reference,
                });

            if (! lookup.has(key)) {
                lookup.set(
                    key,
                    []
                );
            }

            lookup
                .get(key)
                .push(
                    payment
                );
        }
    );

    return lookup;
}


/**
 * Match one Tenant Statement payment to its persisted Payment record.
 *
 * @param {object} payment
 * @param {Map<string, Array<object>>} lookup
 * @returns {object|null}
 */
function findOperationalPayment(
    payment,
    lookup
) {
    const key =
        paymentMatchKey(
            payment
        );

    const candidates =
        lookup.get(
            key
        )
        ?? [];

    return candidates.length === 1
        ? candidates[0]
        : null;
}


/**
 * Stable matching key shared by Statement and Payment API representations.
 *
 * @param {object} payment
 * @returns {string}
 */
function paymentMatchKey(
    payment
) {
    return [
        String(
            payment?.date
            ?? ''
        ).slice(
            0,
            10
        ),
        Number(
            payment?.amount
            ?? 0
        ),
        String(
            payment?.method
            ?? ''
        ),
        String(
            payment?.reference
            ?? ''
        ),
    ].join('|');
}


/**
 * Render receipt actions for one persisted tenant Payment.
 *
 * @param {number|string|null} paymentId
 * @returns {string}
 */
function receiptActionCell(
    paymentId
) {
    if (! paymentId) {
        return tableCell(
            '—'
        );
    }

    const safeId =
        escapeHtml(
            paymentId
        );

    return `
        <td
            class="
                whitespace-nowrap
                px-4 py-3 text-left
            "
        >
            <div
                class="
                    flex items-center gap-2
                "
            >
                <button
                    type="button"
                    data-open-receipt="${safeId}"
                    class="
                        inline-flex items-center
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        shadow-sm transition
                        hover:border-slate-300
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.receipt'
                        )
                    )}
                </button>

                <button
                    type="button"
                    data-resend-receipt="${safeId}"
                    class="
                        inline-flex items-center
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        shadow-sm transition
                        hover:border-slate-300
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.resend'
                        )
                    )}
                </button>
            </div>
        </td>
    `;
}


/**
 * Wire receipt resend buttons after Tenant detail rendering.
 */
function initializeTenantReceiptActions() {
    document
        .querySelectorAll(
            '[data-open-receipt]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        await openTenantReceipt(
                            button
                        );
                    }
                );
            }
        );

    document
        .querySelectorAll(
            '[data-resend-receipt]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        await resendTenantReceipt(
                            button
                        );
                    }
                );
            }
        );
}


/**
 * Fetch and open an authenticated Tenant Payment receipt.
 *
 * Receipt document endpoints require the Sanctum Bearer token maintained
 * by apiRequest(), so they must not be opened through direct browser
 * navigation.
 *
 * @param {HTMLButtonElement} button
 */
async function openTenantReceipt(
    button
) {
    const paymentId =
        button.dataset.openReceipt;

    if (! paymentId) {
        return;
    }

    const originalLabel =
        button.textContent;

    button.disabled = true;
    button.textContent =
        translate('tenants.opening');

    hideTenantError();

    try {
        const response =
            await apiRequest(
                `/api/payments/${encodeURIComponent(
                    paymentId
                )}/receipt`
            );

        if (! response.ok) {
            throw new Error(
                translate('tenants.unable_to_open_receipt')
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
        showTenantError(
            error instanceof Error
                ? error.message
                : translate('tenants.unable_to_open_receipt')
        );
    } finally {
        if (
            document.body.contains(
                button
            )
        ) {
            button.textContent =
                originalLabel;

            button.disabled =
                false;
        }
    }
}


/**
 * Resend an existing tenant Payment receipt.
 *
 * @param {HTMLButtonElement} button
 */
async function resendTenantReceipt(
    button
) {
    const paymentId =
        button.dataset.resendReceipt;

    if (! paymentId) {
        return;
    }

    const originalLabel =
        button.textContent;

    button.disabled = true;
    button.textContent =
        translate('tenants.sending');

    hideTenantError();

    try {
        const response =
            await apiRequest(
                `/api/payments/${encodeURIComponent(
                    paymentId
                )}/send-receipt`,
                {
                    method:
                        'POST',
                }
            );

        await parseJsonResponse(
            response
        );

        button.textContent =
            translate('tenants.sent');

        window.setTimeout(
            () => {
                if (
                    document.body.contains(
                        button
                    )
                ) {
                    button.textContent =
                        originalLabel;
                    button.disabled =
                        false;
                }
            },
            1800
        );
    } catch (error) {
        button.textContent =
            originalLabel;
        button.disabled =
            false;

        showTenantError(
            error instanceof Error
                ? error.message
                : translate('tenants.unable_to_resend_receipt')
        );
    }
}


/**
 * Render the complete tenant-held fund ledger.
 *
 * @param {Array<object>} transactions
 * @returns {string}
 */
function renderTenantFundHistory(
    transactions
) {
    const rows =
        Array.isArray(
            transactions
        )
            ? transactions
            : [];

    return `
        <div>
            <h3
                class="
                    text-base font-semibold
                    text-slate-950
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.fund_history'
                    )
                )}
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.fund_history_description'
                    )
                )}
            </p>
        </div>

        <div class="mt-4">
            ${
                rows.length === 0
                    ? financialEmptyState(
                        translate('tenants.no_fund_transactions')
                    )
                    : `
                        <div
                            class="
                                overflow-x-auto
                                rounded-xl border
                                border-slate-200
                            "
                        >
                            <table
                                class="
                                    min-w-full
                                    divide-y divide-slate-200
                                    text-sm
                                "
                            >
                                <thead class="bg-slate-50">
                                    <tr>
                                        ${tableHeading(translate('tenants.date'))}
                                        ${tableHeading(translate('tenants.fund'))}
                                        ${tableHeading(translate('tenants.direction'))}
                                        ${tableHeading(translate('tenants.category'))}
                                        ${tableHeading(translate('tenants.amount'), true)}
                                        ${tableHeading(translate('tenants.reference'))}
                                        ${tableHeading(translate('tenants.source'))}
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
                                            renderTenantFundTransactionRow
                                        )
                                        .join('')}
                                </tbody>
                            </table>
                        </div>
                    `
            }
        </div>
    `;
}


/**
 * Render one tenant-held fund transaction.
 *
 * @param {object} transaction
 * @returns {string}
 */
function renderTenantFundTransactionRow(
    transaction
) {
    return `
        <tr>
            ${tableCell(
                formatTenantDisplayDate(
                    transaction?.transaction_date
                )
            )}

            ${tableCell(
                tenantDynamicLabel(
                    'fund_type',
                    transaction?.fund_type
                    ?? 'unknown'
                ),
                true
            )}

            ${tableCell(
                tenantDynamicLabel(
                    'direction',
                    transaction?.direction
                    ?? 'unknown'
                )
            )}

            ${tableCell(
                tenantDynamicLabel(
                    'category',
                    transaction?.category
                    ?? 'unknown'
                )
            )}

            ${tableCell(
                formatCurrency(
                    transaction?.amount
                    ?? 0
                ),
                true,
                true
            )}

            ${tableCell(
                transaction?.reference
                || '—'
            )}

            ${tableCell(
                tenantFundSource(
                    transaction
                )
            )}
        </tr>
    `;
}


/**
 * Human-readable source for a tenant-fund ledger movement.
 *
 * @param {object} transaction
 * @returns {string}
 */
function tenantFundSource(
    transaction
) {
    if (transaction?.payment_id) {
        return translate(
            'tenants.payment_number',
            {
                number:
                    transaction.payment_id,
            }
        );
    }

    if (transaction?.invoice_id) {
        return translate(
            'tenants.invoice_number',
            {
                number:
                    transaction.invoice_id,
            }
        );
    }

    return translate(
        'tenants.ledger'
    );
}


/**
 * Render one financial summary card.
 *
 * @param {string} label
 * @param {number|string|null} value
 * @returns {string}
 */
function financialMetric(
    label,
    value
) {
    return `
        <div
            class="
                rounded-xl border
                border-slate-200
                bg-slate-50/50
                px-4 py-4
            "
        >
            <div
                class="
                    text-xs font-medium
                    text-slate-500
                "
            >
                ${escapeHtml(label)}
            </div>

            <div
                class="
                    mt-1 text-lg font-semibold
                    text-slate-950
                "
            >
                ${escapeHtml(
                    formatCurrency(
                        value
                        ?? 0
                    )
                )}
            </div>
        </div>
    `;
}


/**
 * Render a table heading.
 *
 * @param {string} label
 * @param {boolean} numeric
 * @returns {string}
 */
function tableHeading(
    label,
    numeric = false
) {
    return `
        <th
            scope="col"
            class="
                whitespace-nowrap
                px-4 py-3
                text-xs font-medium
                uppercase tracking-wide
                text-slate-500
                ${numeric ? 'text-right' : 'text-left'}
            "
        >
            ${escapeHtml(label)}
        </th>
    `;
}


/**
 * Render a table cell.
 *
 * @param {string|number} value
 * @param {boolean} strong
 * @param {boolean} numeric
 * @returns {string}
 */
function tableCell(
    value,
    strong = false,
    numeric = false
) {
    return `
        <td
            class="
                whitespace-nowrap
                px-4 py-3
                ${numeric ? 'text-right' : 'text-left'}
                ${
                    strong
                        ? 'font-semibold text-slate-900'
                        : 'text-slate-600'
                }
            "
        >
            ${escapeHtml(
                value
                ?? '—'
            )}
        </td>
    `;
}


/**
 * Render an empty financial-history state.
 *
 * @param {string} message
 * @returns {string}
 */
function financialEmptyState(
    message
) {
    return `
        <div
            class="
                rounded-xl border
                border-dashed border-slate-200
                px-5 py-8 text-center
                text-sm text-slate-500
            "
        >
            ${escapeHtml(message)}
        </div>
    `;
}


/**
 * Render tenant leases.
 *
 * @param {Array<object>} leases
 * @returns {string}
 */
function renderTenantLeases(
    leases
) {
    if (leases.length === 0) {
        return `
            <div
                class="
                    rounded-xl border
                    border-dashed border-slate-200
                    px-5 py-8 text-center
                    text-sm text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.no_leases'
                    )
                )}
            </div>
        `;
    }

    return leases
        .map(
            (lease) => {
                const building =
                    lease?.unit?.building?.name
                    ?? translate('tenants.building');

                const unit =
                    lease?.unit?.name
                    ?? translate('tenants.unit');

                return `
                    <article
                        class="
                            mb-3 rounded-xl
                            border border-slate-200
                            bg-white p-4
                            last:mb-0
                        "
                    >
                        <div
                            class="
                                flex flex-col gap-3
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            "
                        >
                            <div>
                                <div
                                    class="
                                        text-sm font-semibold
                                        text-slate-900
                                    "
                                >
                                    ${escapeHtml(
                                        `${building} / ${unit}`
                                    )}
                                </div>

                                <div
                                    class="
                                        mt-1 text-xs
                                        text-slate-500
                                    "
                                >
                                    ${escapeHtml(
                                        formatLeasePeriod(
                                            lease
                                        )
                                    )}
                                </div>
                            </div>

                            <div
                                class="
                                    flex items-center gap-3
                                "
                            >
                                <div
                                    class="
                                        text-sm font-semibold
                                        text-slate-900
                                    "
                                >
                                    ${escapeHtml(
                                        formatCurrency(
                                            lease.rent_amount
                                            ?? 0
                                        )
                                    )}
                                </div>

                                <span
                                    class="
                                        rounded-full bg-slate-100
                                        px-2.5 py-1
                                        text-xs font-medium
                                        text-slate-600
                                    "
                                >
                                    ${escapeHtml(
                                        tenantDynamicLabel(
                                            'lease_status',
                                            lease.status
                                            ?? 'unknown'
                                        )
                                    )}
                                </span>
                            </div>
                        </div>
                    </article>
                `;
            }
        )
        .join('');
}


/**
 * Summary metric.
 */
function summaryMetric(
    label,
    value
) {
    return `
        <div>
            <div
                class="
                    text-xs font-medium
                    uppercase tracking-wide
                    text-slate-500
                "
            >
                ${escapeHtml(label)}
            </div>

            <div
                class="
                    mt-2 text-xl font-semibold
                    text-slate-900
                "
            >
                ${escapeHtml(value)}
            </div>
        </div>
    `;
}


/**
 * Detail item.
 */
function detailItem(
    label,
    value
) {
    return `
        <div>
            <div
                class="
                    text-xs font-medium
                    text-slate-500
                "
            >
                ${escapeHtml(label)}
            </div>

            <div
                class="
                    mt-1 text-sm
                    text-slate-900
                "
            >
                ${escapeHtml(
                    value || '—'
                )}
            </div>
        </div>
    `;
}


/**
 * Tenant display name.
 */
function tenantDisplayName(
    tenant
) {
    return tenant?.name
        || tenant?.legal_name
        || translate('tenants.unnamed_tenant');
}


/**
 * Tenant contact summary.
 */
function contactSummary(
    tenant
) {
    return [
        tenant?.phone,
        tenant?.email,
    ]
        .filter(Boolean)
        .join(' · ');
}


/**
 * Translate backend enum values used by the Tenant workspace.
 *
 * Falls back to the existing human-readable capitalization when an
 * unexpected value is returned by the API.
 */
function tenantDynamicLabel(
    group,
    value
) {
    const normalized =
        String(
            value
            ?? ''
        )
            .trim()
            .toLowerCase()
            .replace(
                /[\s-]+/g,
                '_'
            );

    if (!normalized) {
        return '—';
    }

    const key =
        `tenants.${group}.${normalized}`;

    const translated =
        translate(
            key
        );

    /*
     * translate() returns the key when no translation exists.
     */
    if (
        translated
        && translated !== key
    ) {
        return translated;
    }

    return capitalizeWords(
        normalized
    );
}


/**
 * Display a Tenant business date using the central Patrimoine
 * presentation standard.
 *
 * French:  DD-MM-YYYY
 * English: DD/MM/YYYY
 */
function formatTenantDisplayDate(
    value
) {
    if (! value) {
        return '—';
    }

    return formatDate(
        String(value).slice(
            0,
            10
        )
    ) || '—';
}


/**
 * Lease date display.
 */
function formatLeasePeriod(
    lease
) {
    const rawStart =
        String(
            lease?.start_date
            ?? ''
        ).slice(
            0,
            10
        );

    const rawEnd =
        String(
            lease?.end_date
            ?? ''
        ).slice(
            0,
            10
        );

    const start =
        rawStart
            ? formatTenantDisplayDate(
                rawStart
            )
            : '';

    const end =
        rawEnd
            ? formatTenantDisplayDate(
                rawEnd
            )
            : '';

    if (start && end) {
        return `${start} → ${end}`;
    }

    if (start) {
        return translate(
            'tenants.lease_ongoing',
            {
                start,
            }
        );
    }

    return translate(
        'tenants.lease_dates_unavailable'
    );
}


/**
 * Render Tenant directory pagination.
 */
function renderTenantPagination(
    pagination
) {
    const container =
        document.getElementById(
            'tenant-pagination'
        );

    if (! container) {
        return;
    }

    const current =
        Number(
            pagination?.current_page
            ?? 1
        );

    const last =
        Number(
            pagination?.last_page
            ?? 1
        );

    const total =
        Number(
            pagination?.total
            ?? 0
        );

    if (last <= 1) {
        container.innerHTML = '';

        container.classList.add(
            'hidden'
        );

        return;
    }

    container.classList.remove(
        'hidden'
    );

    container.innerHTML = `
        <div
            class="
                flex items-center
                justify-between gap-3
            "
        >
            <div
                class="
                    text-xs text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        total === 1
                            ? 'tenants.pagination_tenant'
                            : 'tenants.pagination_tenants',
                        {
                            total:
                                formatNumber(
                                    total
                                ),
                        }
                    )
                )}
            </div>

            <div class="flex gap-2">
                <button
                    id="tenant-list-previous"
                    type="button"
                    ${current <= 1 ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-2.5 py-1.5
                        text-xs font-medium
                        text-slate-700
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.previous'
                        )
                    )}
                </button>

                <button
                    id="tenant-list-next"
                    type="button"
                    ${current >= last ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-2.5 py-1.5
                        text-xs font-medium
                        text-slate-700
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'tenants.next'
                        )
                    )}
                </button>
            </div>
        </div>
    `;

    document
        .getElementById(
            'tenant-list-previous'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (current > 1) {
                    selectedTenantId = null;

                    await loadTenants(
                        current - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'tenant-list-next'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (current < last) {
                    selectedTenantId = null;

                    await loadTenants(
                        current + 1
                    );
                }
            }
        );
}


/**
 * Directory loading state.
 */
function showTenantLoading() {
    const container =
        document.getElementById(
            'tenant-list'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                px-5 py-8 text-center
                text-sm text-slate-400
            "
        >
            ${escapeHtml(
                translate(
                    'tenants.loading'
                )
            )}
        </div>
    `;
}


/**
 * Directory empty state.
 */
function renderTenantDirectoryEmpty(
    message
) {
    const container =
        document.getElementById(
            'tenant-list'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                px-5 py-10 text-center
                text-sm text-slate-500
            "
        >
            ${escapeHtml(message)}
        </div>
    `;
}


/**
 * Detail loading state.
 */
function renderTenantDetailLoading() {
    const container =
        document.getElementById(
            'tenant-detail'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                flex min-h-[620px]
                items-center justify-center
            "
        >
            <div
                class="
                    text-sm text-slate-400
                "
            >
                ${escapeHtml(
                    translate(
                        'tenants.loading_details'
                    )
                )}
            </div>
        </div>
    `;
}


/**
 * Detail empty/error state.
 */
function renderTenantDetailEmpty(
    message
) {
    const container =
        document.getElementById(
            'tenant-detail'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                flex min-h-[620px]
                items-center justify-center
                px-6
            "
        >
            <div
                class="
                    max-w-md text-center
                    text-sm text-slate-500
                "
            >
                ${escapeHtml(message)}
            </div>
        </div>
    `;
}


/**
 * Tenant page error.
 */
function showTenantError(
    message
) {
    const element =
        document.getElementById(
            'tenant-error'
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


/**
 * Clear Tenant page error.
 */
function hideTenantError() {
    const element =
        document.getElementById(
            'tenant-error'
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}


/**
 * Human-readable enum label.
 */
function capitalizeWords(
    value
) {
    return String(
        value
        ?? ''
    )
        .replaceAll(
            '_',
            ' '
        )
        .split(' ')
        .filter(Boolean)
        .map(
            (word) =>
                word.charAt(0).toUpperCase()
                + word.slice(1)
        )
        .join(' ');
}
