import {
    apiRequest,
    escapeHtml,
    formatCurrency,
    parseJsonResponse,
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
            '25'
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
                'No Tenant is available to display.'
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
                : 'Unable to load Tenants.'
        );

        renderTenantDirectoryEmpty(
            'Unable to load Tenants.'
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

    const count =
        document.getElementById(
            'tenant-result-count'
        );

    if (count) {
        count.textContent =
            `${total.toLocaleString()} tenant${total === 1 ? '' : 's'}`;
    }

    if (tenants.length === 0) {
        renderTenantDirectoryEmpty(
            'No Tenants match your search.'
        );

        renderTenantDetailEmpty(
            'No Tenant is available to display.'
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

    const contact =
        [
            tenant.phone,
            tenant.email,
        ]
            .filter(Boolean)
            .join(' · ');

    return `
        <button
            type="button"
            data-tenant-id="${escapeHtml(
                tenant.id
            )}"
            class="
                block w-full
                border-b border-slate-100
                px-5 py-4 text-left
                transition
                last:border-b-0
                ${
                    selected
                        ? 'bg-patrimoine-50'
                        : 'hover:bg-slate-50'
                }
            "
        >
            <div
                class="
                    truncate text-sm font-semibold
                    text-slate-900
                "
            >
                ${escapeHtml(name)}
            </div>

            ${
                contact
                    ? `
                        <div
                            class="
                                mt-1 truncate
                                text-xs text-slate-500
                            "
                        >
                            ${escapeHtml(contact)}
                        </div>
                    `
                    : ''
            }
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
                'The selected Party is not a Tenant.'
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
                : 'Unable to load Tenant details.'
        );

        renderTenantDetailEmpty(
            'Unable to load this Tenant.'
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
                    'bg-patrimoine-50',
                    selected
                );

                button.classList.toggle(
                    'hover:bg-slate-50',
                    ! selected
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
                            || 'No contact information available.'
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
                    Tenant Statement
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
                'Total Leases',
                leases.length
            )}

            ${summaryMetric(
                'Current Leases',
                activeLeases.length
            )}

            ${summaryMetric(
                'Historical Leases',
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
                Tenant Details
            </h3>

            <div
                class="
                    mt-4 grid gap-5
                    sm:grid-cols-2
                    xl:grid-cols-3
                "
            >
                ${detailItem(
                    'Party Type',
                    capitalizeWords(
                        tenant.type
                    )
                )}

                ${detailItem(
                    'Phone',
                    tenant.phone
                )}

                ${detailItem(
                    'Alternate Phone',
                    tenant.alternate_phone
                )}

                ${detailItem(
                    'Email',
                    tenant.email
                )}

                ${detailItem(
                    'Address',
                    tenant.address
                )}

                ${detailItem(
                    'ID / Registration',
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
                    Leases
                </h3>

                <p
                    class="
                        mt-1 text-xs
                        text-slate-500
                    "
                >
                    Current and historical lease relationships for this Tenant.
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
                Financial Position
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                Outstanding receivables and tenant-held funds across all leases.
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
                'Rent Outstanding',
                summary.rent_outstanding
            )}

            ${financialMetric(
                'Security Deposit Debt',
                summary.security_deposit_debt_outstanding
            )}

            ${financialMetric(
                'Total Outstanding',
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
                Held Funds
            </h4>

            <div
                class="
                    mt-3 grid gap-3
                    sm:grid-cols-2
                    xl:grid-cols-3
                "
            >
                ${financialMetric(
                    'Rent Reserve',
                    summary.rent_reserve_balance
                )}

                ${financialMetric(
                    'Consumable Advance',
                    summary.consumable_advance_balance
                )}

                ${financialMetric(
                    'Security Deposit',
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
                Invoices
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                Billing history across this Tenant's leases.
            </p>
        </div>

        <div class="mt-4">
            ${
                rows.length === 0
                    ? financialEmptyState(
                        'No invoices have been recorded for this Tenant.'
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
                                        ${tableHeading('Invoice')}
                                        ${tableHeading('Type')}
                                        ${tableHeading('Date')}
                                        ${tableHeading('Due Date')}
                                        ${tableHeading('Amount', true)}
                                        ${tableHeading('Paid', true)}
                                        ${tableHeading('Outstanding', true)}
                                        ${tableHeading('Status')}
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
                || '—'
            )}

            ${tableCell(
                invoice?.due_date
                || '—'
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
        </tr>
    `;
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
                Payments
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                Cash received and allocation history across this Tenant's leases.
            </p>
        </div>

        <div class="mt-4">
            ${
                rows.length === 0
                    ? financialEmptyState(
                        'No payments have been recorded for this Tenant.'
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
                                        ${tableHeading('Date')}
                                        ${tableHeading('Amount', true)}
                                        ${tableHeading('Method')}
                                        ${tableHeading('Reference')}
                                        ${tableHeading('Allocated', true)}
                                        ${tableHeading('Unallocated', true)}
                                        ${tableHeading('Receipt')}
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
                payment?.date
                || '—'
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
                capitalizeWords(
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
                    Receipt
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
                    Resend
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
        'Opening…';

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
                'Unable to open receipt.'
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
                : 'Unable to open receipt.'
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
        'Sending…';

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
            'Sent';

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
                : 'Unable to resend receipt.'
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
                Fund History
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-slate-500
                "
            >
                Transaction history for Rent Reserve, Consumable Advance and Security Deposit.
            </p>
        </div>

        <div class="mt-4">
            ${
                rows.length === 0
                    ? financialEmptyState(
                        'No tenant fund transactions have been recorded for this Tenant.'
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
                                        ${tableHeading('Date')}
                                        ${tableHeading('Fund')}
                                        ${tableHeading('Direction')}
                                        ${tableHeading('Category')}
                                        ${tableHeading('Amount', true)}
                                        ${tableHeading('Reference')}
                                        ${tableHeading('Source')}
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
                String(
                    transaction?.transaction_date
                    ?? ''
                ).slice(
                    0,
                    10
                )
                || '—'
            )}

            ${tableCell(
                capitalizeWords(
                    transaction?.fund_type
                    ?? 'unknown'
                ),
                true
            )}

            ${tableCell(
                capitalizeWords(
                    transaction?.direction
                    ?? 'unknown'
                )
            )}

            ${tableCell(
                capitalizeWords(
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
        return `Payment #${transaction.payment_id}`;
    }

    if (transaction?.invoice_id) {
        return `Invoice #${transaction.invoice_id}`;
    }

    return 'Ledger';
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
                No leases have been recorded for this Tenant.
            </div>
        `;
    }

    return leases
        .map(
            (lease) => {
                const building =
                    lease?.unit?.building?.name
                    ?? 'Building';

                const unit =
                    lease?.unit?.name
                    ?? 'Unit';

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
                                        capitalizeWords(
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
        || 'Unnamed Tenant';
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
 * Lease date display.
 */
function formatLeasePeriod(
    lease
) {
    const start =
        String(
            lease?.start_date
            ?? ''
        ).slice(
            0,
            10
        );

    const end =
        String(
            lease?.end_date
            ?? ''
        ).slice(
            0,
            10
        );

    if (start && end) {
        return `${start} → ${end}`;
    }

    if (start) {
        return `${start} → ongoing`;
    }

    return 'Lease dates unavailable';
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
                ${total.toLocaleString()}
                tenant${total === 1 ? '' : 's'}
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
                    Previous
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
                    Next
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
            Loading tenants…
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
                Loading Tenant details…
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
