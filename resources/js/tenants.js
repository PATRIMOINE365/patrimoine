import {
    apiRequest,
    escapeHtml,
    formatCurrency,
    formatDate,
    formatNumber,
    parseJsonResponse,
    translate,
} from './core.js';

import {
    browserCan,
} from './permissions.js';

import {
    dateForApi,
    dateForDisplay,
    initializeDateInputs,
} from './date-input.js';

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


/*
|--------------------------------------------------------------------------
| V1.0.5 Apply Security Deposit — Contextual Eligibility
|--------------------------------------------------------------------------
|
| Apply Security Deposit is NOT a fourth primary Tenant action.
| It is revealed contextually on a Lease only when the server-derived
| Lease detail shows:
|
| - a positive Security Deposit balance; and
| - at least one outstanding Rent or Security Deposit Debt invoice.
|
| The backend remains authoritative and revalidates everything when the
| actual application is eventually submitted.
|
*/


/**
 * Reveal contextual Apply Security Deposit buttons for eligible Leases.
 */
async function initializeSecurityDepositApplicationActions() {
    if (
        ! browserCan(
            'manage_finance'
        )
    ) {
        return;
    }

    const button =
        document.getElementById(
            'tenant-apply-security-deposit'
        );

    if (! button) {
        return;
    }

    /*
     * Begin hidden. Eligibility must be proven from authoritative
     * Lease detail before this financial action becomes available.
     */
    button.classList.add(
        'hidden'
    );

    delete button.dataset.leaseId;
    delete button.dataset.securityDepositBalance;
    delete button.dataset.eligibleInvoiceCount;

    const eligibleLeases = [];

    for (
        const summary
        of selectedTenantLeases
    ) {
        const leaseId =
            Number(
                summary?.id
                ?? 0
            );

        if (
            ! Number.isInteger(
                leaseId
            )
            || leaseId <= 0
            || summary?.status === 'draft'
        ) {
            continue;
        }

        try {
            const lease =
                await tenantLeaseDetail(
                    leaseId
                );

            const eligibility =
                securityDepositApplicationEligibility(
                    lease
                );

            if (! eligibility.eligible) {
                continue;
            }

            eligibleLeases.push({
                lease,
                eligibility,
            });
        } catch {
            /*
             * Eligibility discovery is presentation-only.
             * Backend validation remains authoritative.
             */
        }
    }

    /*
     * The current drawer operates against one specific Lease.
     *
     * Do not guess when more than one Lease is eligible. We expose the
     * header action only when its Lease context is unambiguous.
     */
    if (
        eligibleLeases.length !== 1
    ) {
        return;
    }

    const {
        lease,
        eligibility,
    } = eligibleLeases[0];

    button.dataset.leaseId =
        String(
            lease.id
        );

    button.dataset.securityDepositBalance =
        String(
            eligibility.heldBalance
        );

    button.dataset.eligibleInvoiceCount =
        String(
            eligibility.invoices.length
        );

    button.classList.remove(
        'hidden'
    );
}


/**
 * Evaluate Security Deposit application eligibility from authoritative
 * Lease-detail data.
 *
 * @param {object|null} lease
 * @returns {{
 *     eligible: boolean,
 *     heldBalance: number,
 *     invoices: Array<object>
 * }}
 */
function securityDepositApplicationEligibility(
    lease
) {
    if (
        ! lease
        || lease?.status === 'draft'
    ) {
        return {
            eligible: false,
            heldBalance: 0,
            invoices: [],
        };
    }

    const securityDepositAccount =
        tenantFundAccounts(
            lease
        ).find(
            (account) =>
                account?.type
                === 'security_deposit'
        );

    const heldBalance =
        securityDepositAccount
            ? tenantFundBalance(
                securityDepositAccount
            )
            : 0;

    const invoices =
        (
            Array.isArray(
                lease?.invoices
            )
                ? lease.invoices
                : []
        ).filter(
            (invoice) => {
                const type =
                    String(
                        invoice?.type
                        ?? ''
                    );

                const outstanding =
                    Number(
                        invoice?.outstanding_amount
                        ?? invoice?.outstanding
                        ?? 0
                    );

                return [
                    'rent',
                    'security_deposit_debt',
                ].includes(type)
                    && outstanding > 0;
            }
        );

    return {
        eligible:
            heldBalance > 0
            && invoices.length > 0,

        heldBalance,

        invoices,
    };
}



/**
 * Open Apply Security Deposit for one eligible Lease.
 *
 * @param {number} leaseId
 */
async function openSecurityDepositApplicationDrawer(
    leaseId
) {
    hideTenantTransactionError(
        'tenant-security-application-error'
    );

    const form =
        document.getElementById(
            'tenant-security-application-form'
        );

    form?.reset();

    const leaseField =
        document.getElementById(
            'tenant-security-application-lease-id'
        );

    if (leaseField) {
        leaseField.value =
            String(
                leaseId
            );
    }

    setElementText(
        'tenant-security-application-tenant-context',
        tenantDisplayName(
            selectedTenant
        )
    );

    setElementText(
        'tenant-security-application-property-context',
        translate(
            'tenants.loading_details'
        )
    );

    const heldBalance =
        document.getElementById(
            'tenant-security-application-held-balance'
        );

    if (heldBalance) {
        heldBalance.textContent = '—';

        delete heldBalance.dataset
            .rawBalance;
    }

    setElementText(
        'tenant-security-application-invoice-balance',
        '—'
    );

    setElementText(
        'tenant-security-application-resulting-deposit',
        '—'
    );

    setElementText(
        'tenant-security-application-resulting-receivable',
        '—'
    );

    const invoiceSelect =
        document.getElementById(
            'tenant-security-application-invoice'
        );

    if (invoiceSelect) {
        invoiceSelect.innerHTML =
            `<option value="">${escapeHtml(
                translate(
                    'tenants.select_receivable'
                )
            )}</option>`;

        invoiceSelect.disabled =
            true;
    }

    setTenantTransactionToday(
        'tenant-security-application-date'
    );

    openTenantTransactionDrawer(
        'tenant-security-application-drawer'
    );

    try {
        const lease =
            await tenantLeaseDetail(
                leaseId
            );

        const eligibility =
            securityDepositApplicationEligibility(
                lease
            );

        if (! eligibility.eligible) {
            throw new Error(
                translate(
                    'tenants.security_application_not_available'
                )
            );
        }

        setElementText(
            'tenant-security-application-property-context',
            tenantLeaseLabel(
                lease
            )
        );

        if (heldBalance) {
            heldBalance.dataset.rawBalance =
                String(
                    eligibility.heldBalance
                );

            heldBalance.textContent =
                formatCurrency(
                    eligibility.heldBalance
                );
        }

        if (invoiceSelect) {
            invoiceSelect.disabled =
                false;

            eligibility.invoices.forEach(
                (invoice) => {
                    const option =
                        document.createElement(
                            'option'
                        );

                    const outstanding =
                        Number(
                            invoice?.outstanding_amount
                            ?? invoice?.outstanding
                            ?? 0
                        );

                    option.value =
                        String(
                            invoice.id
                        );

                    option.dataset.outstanding =
                        String(
                            outstanding
                        );

                    option.textContent =
                        `${
                            invoice?.invoice_number
                            ?? `#${invoice.id}`
                        } · ${
                            securityDepositReceivableLabel(
                                invoice?.type
                            )
                        } · ${
                            formatCurrency(
                                outstanding
                            )
                        }`;

                    invoiceSelect.appendChild(
                        option
                    );
                }
            );
        }

        updateSecurityDepositApplicationPreview();
    } catch (error) {
        showTenantTransactionError(
            'tenant-security-application-error',
            error instanceof Error
                ? error.message
                : translate(
                    'tenants.security_application_not_available'
                )
        );
    }
}


/**
 * Localized receivable type.
 *
 * @param {string|null|undefined} type
 * @returns {string}
 */
function securityDepositReceivableLabel(
    type
) {
    const normalized =
        String(
            type
            ?? ''
        );

    if (normalized === 'rent') {
        return translate(
            'tenants.invoice_type.rent'
        );
    }

    if (
        normalized
        === 'security_deposit_debt'
    ) {
        return translate(
            'tenants.invoice_type.security_deposit_debt'
        );
    }

    return capitalizeWords(
        normalized
    );
}


/**
 * Update Apply Security Deposit preview.
 */
function updateSecurityDepositApplicationPreview() {
    const invoice =
        selectedTransactionOption(
            'tenant-security-application-invoice'
        );

    const heldBalance =
        Number(
            document
                .getElementById(
                    'tenant-security-application-held-balance'
                )
                ?.dataset
                .rawBalance
            ?? NaN
        );

    const outstanding =
        invoice
            ? Number(
                invoice.dataset.outstanding
                ?? 0
            )
            : null;

    const amount =
        integerInputValue(
            'tenant-security-application-amount'
        );

    setElementText(
        'tenant-security-application-invoice-balance',
        outstanding !== null
            ? formatCurrency(
                outstanding
            )
            : '—'
    );

    if (
        ! Number.isFinite(
            heldBalance
        )
        || outstanding === null
        || amount === null
        || amount <= 0
    ) {
        setElementText(
            'tenant-security-application-resulting-deposit',
            '—'
        );

        setElementText(
            'tenant-security-application-resulting-receivable',
            '—'
        );

        return;
    }

    setElementText(
        'tenant-security-application-resulting-deposit',
        formatCurrency(
            Math.max(
                0,
                heldBalance - amount
            )
        )
    );

    setElementText(
        'tenant-security-application-resulting-receivable',
        formatCurrency(
            Math.max(
                0,
                outstanding - amount
            )
        )
    );
}



/**
 * Submit held Security Deposit against the selected Lease receivable.
 *
 * @param {SubmitEvent} event
 */
async function submitSecurityDepositApplication(
    event
) {
    event.preventDefault();

    hideTenantTransactionError(
        'tenant-security-application-error'
    );

    const leaseId =
        requiredPositiveIntegerValue(
            'tenant-security-application-lease-id'
        );

    const invoice =
        selectedTransactionOption(
            'tenant-security-application-invoice'
        );

    const invoiceId =
        Number(
            invoice?.value
            ?? 0
        );

    const amount =
        requiredPositiveIntegerValue(
            'tenant-security-application-amount'
        );

    const transactionDate =
        transactionDateForApi(
            'tenant-security-application-date'
        );

    const notes =
        nullableTrimmedValue(
            'tenant-security-application-notes'
        );

    const outstanding =
        Number(
            invoice?.dataset.outstanding
            ?? 0
        );

    const heldBalance =
        Number(
            document
                .getElementById(
                    'tenant-security-application-held-balance'
                )
                ?.dataset.rawBalance
            ?? 0
        );

    if (
        ! leaseId
        || ! Number.isInteger(
            invoiceId
        )
        || invoiceId <= 0
        || ! amount
        || ! transactionDate
    ) {
        showTenantTransactionError(
            'tenant-security-application-error',
            translate(
                'tenants.transaction_required_fields'
            )
        );

        return;
    }

    /*
     * Browser guards improve UX only.
     * Backend locking/validation remains authoritative.
     */
    if (
        heldBalance > 0
        && amount > heldBalance
    ) {
        showTenantTransactionError(
            'tenant-security-application-error',
            translate(
                'tenants.security_application_exceeds_deposit'
            )
        );

        return;
    }

    if (
        outstanding > 0
        && amount > outstanding
    ) {
        showTenantTransactionError(
            'tenant-security-application-error',
            translate(
                'tenants.security_application_exceeds_receivable'
            )
        );

        return;
    }

    const submitButton =
        document.getElementById(
            'tenant-security-application-submit'
        );

    await withTenantTransactionSubmitLock(
        submitButton,
        async () => {
            try {
                await postTenantTransaction(
                    `/api/leases/${encodeURIComponent(
                        leaseId
                    )}/security-deposit/apply`,
                    {
                        invoice_id:
                            invoiceId,

                        amount,

                        transaction_date:
                            transactionDate,

                        notes,
                    }
                );

                closeTenantTransactionDrawer(
                    'tenant-security-application-drawer'
                );

                await refreshSelectedTenantAfterTransaction();

                showTenantTransactionSuccess(
                    translate(
                        'tenants.security_application_recorded'
                    )
                );
            } catch (error) {
                showTenantTransactionError(
                    'tenant-security-application-error',
                    tenantTransactionErrorMessage(
                        error
                    )
                );
            }
        }
    );
}


/**
 * Register Apply Security Deposit static drawer controls once.
 */
function initializeSecurityDepositApplicationControls() {
    document
        .getElementById(
            'tenant-detail'
        )
        ?.addEventListener(
            'click',
            async (event) => {
                const button =
                    event.target.closest(
                        '[data-apply-security-deposit-header]'
                    );

                if (
                    ! button
                    || ! browserCan(
                        'manage_finance'
                    )
                ) {
                    return;
                }

                event.preventDefault();

                const leaseId =
                    Number(
                        button.dataset.leaseId
                        ?? 0
                    );

                if (
                    ! Number.isInteger(
                        leaseId
                    )
                    || leaseId <= 0
                ) {
                    return;
                }

                await openSecurityDepositApplicationDrawer(
                    leaseId
                );
            }
        );

    document
        .getElementById(
            'tenant-security-application-form'
        )
        ?.addEventListener(
            'submit',
            submitSecurityDepositApplication
        );

    document
        .getElementById(
            'tenant-security-application-invoice'
        )
        ?.addEventListener(
            'change',
            updateSecurityDepositApplicationPreview
        );

    document
        .getElementById(
            'tenant-security-application-amount'
        )
        ?.addEventListener(
            'input',
            updateSecurityDepositApplicationPreview
        );
}


/*
|--------------------------------------------------------------------------
| V1.0.5 Tenant Transaction State
|--------------------------------------------------------------------------
|
| The browser stores presentation context only. Backend services remain
| authoritative for balances, eligibility, FIFO allocation and accounting.
|
*/

let selectedTenant = null;
let selectedTenantLeases = [];
let selectedTenantLeaseDetails = new Map();

const tenantTransactionDrawerTimers =
    new Map();

let tenantTransactionControlsInitialized =
    false;


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

    initializeTenantTransactionControls();

    initializeSecurityDepositApplicationControls();

    initializeDateInputs(
        '[data-pm-date-input]'
    );

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

        const requestedTenantId =
            new URLSearchParams(
                window.location.search
            ).get(
                'tenant_id'
            );

        const selectedTenantStillVisible =
            tenants.some(
                (tenant) =>
                    String(
                        tenant.id
                    ) === String(
                        selectedTenantId
                    )
            );

        /*
         * An explicit Tenant deep-link wins even when that Tenant is not
         * present in the current directory page. selectTenant() loads the
         * Party directly by ID, so the hand-off remains deterministic for
         * large Tenant directories.
         */
        const tenantToSelect =
            requestedTenantId
                ? requestedTenantId
                : (
                    selectedTenantStillVisible
                        ? selectedTenantId
                        : tenants[0].id
                );

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

        selectedTenant =
            tenant;

        selectedTenantLeases =
            leases;

        selectedTenantLeaseDetails =
            new Map();

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

                <div
                    class="
                        flex flex-wrap items-center
                        justify-end gap-2
                    "
                >
                    ${
                        browserCan(
                            'manage_finance'
                        )
                            ? renderTenantTransactionActions()
                            : ''
                    }

                    ${
                        browserCan(
                            'manage_finance'
                        )
                            ? `
                                <button
                                    type="button"
                                    id="tenant-apply-security-deposit"
                                    data-apply-security-deposit-header
                                    class="
                                        hidden
                                        pm-button-secondary
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'tenants.apply_security_deposit'
                                        )
                                    )}
                                </button>
                            `
                            : ''
                    }
                </div>
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
    initializeTenantTransactionActionButtons();
    initializeSecurityDepositApplicationActions();
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

/*
|--------------------------------------------------------------------------
| V1.0.5 Tenant Transaction UI
|--------------------------------------------------------------------------
|
| Tenant is now the contextual operational location for Tenant money.
|
| Primary actions:
| - Deposit
| - Withdrawal
| - Adjustment
|
| This Step 5C layer intentionally performs no POST mutations yet. It owns
| selection, eligibility, authoritative balance display and confirmation
| previews. Step 5D will wire the already-tested backend endpoints.
|
*/

/**
 * Render exactly the three frozen V1.0.5 Tenant transaction actions.
 *
 * @returns {string}
 */
function renderTenantTransactionActions() {
    return `
        <div
            id="tenant-transaction-actions"
            data-requires-capability="manage_finance"
            class="flex flex-wrap items-center gap-2"
        >
            <button
                type="button"
                data-tenant-transaction-action="deposit"
                class="pm-button-primary"
            >
                ${escapeHtml(
                    translate(
                        'tenants.deposit'
                    )
                )}
            </button>

            <button
                type="button"
                data-tenant-transaction-action="withdrawal"
                class="pm-button-secondary"
            >
                ${escapeHtml(
                    translate(
                        'tenants.withdrawal'
                    )
                )}
            </button>

            <button
                type="button"
                data-tenant-transaction-action="adjustment"
                class="pm-button-secondary"
            >
                ${escapeHtml(
                    translate(
                        'tenants.adjustment'
                    )
                )}
            </button>
        </div>
    `;
}


/**
 * Register the dynamically rendered selected-Tenant action buttons.
 */
function initializeTenantTransactionActionButtons() {
    if (
        ! browserCan(
            'manage_finance'
        )
    ) {
        return;
    }

    document
        .querySelectorAll(
            '[data-tenant-transaction-action]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        const action =
                            button.dataset
                                .tenantTransactionAction;

                        if (
                            action
                            === 'deposit'
                        ) {
                            await openTenantDepositDrawer();

                            return;
                        }

                        if (
                            action
                            === 'withdrawal'
                        ) {
                            await openTenantWithdrawalDrawer();

                            return;
                        }

                        if (
                            action
                            === 'adjustment'
                        ) {
                            await openTenantAdjustmentDrawer();
                        }
                    }
                );
            }
        );
}


/**
 * Register static controls belonging to the Blade drawers.
 */
function initializeTenantTransactionControls() {
    if (
        tenantTransactionControlsInitialized
    ) {
        return;
    }

    tenantTransactionControlsInitialized =
        true;

    [
        'deposit',
        'withdrawal',
        'adjustment',
        'security-application',
    ].forEach(
        (action) => {
            document
                .getElementById(
                    `tenant-${action}-drawer-close`
                )
                ?.addEventListener(
                    'click',
                    () => {
                        closeTenantTransactionDrawer(
                            `tenant-${action}-drawer`
                        );
                    }
                );

            document
                .getElementById(
                    `tenant-${action}-drawer-backdrop`
                )
                ?.addEventListener(
                    'click',
                    () => {
                        closeTenantTransactionDrawer(
                            `tenant-${action}-drawer`
                        );
                    }
                );

            document
                .querySelectorAll(
                    `[data-close-tenant-transaction="tenant-${action}-drawer"]`
                )
                .forEach(
                    (button) => {
                        button.addEventListener(
                            'click',
                            () => {
                                closeTenantTransactionDrawer(
                                    `tenant-${action}-drawer`
                                );
                            }
                        );
                    }
                );
        }
    );

    document
        .getElementById(
            'tenant-deposit-lease'
        )
        ?.addEventListener(
            'change',
            async (event) => {
                await populateDepositDestinations(
                    event.target.value
                );

                setTenantTransactionContext(
                    'tenant-deposit'
                );
            }
        );

    document
        .getElementById(
            'tenant-withdrawal-lease'
        )
        ?.addEventListener(
            'change',
            async (event) => {
                await populateWithdrawalAccounts(
                    event.target.value
                );

                setTenantTransactionContext(
                    'tenant-withdrawal'
                );
            }
        );

    document
        .getElementById(
            'tenant-deposit-account'
        )
        ?.addEventListener(
            'change',
            () => {
                updateTenantDepositPreview();

                setTenantTransactionContext(
                    'tenant-deposit'
                );
            }
        );

    document
        .getElementById(
            'tenant-deposit-amount'
        )
        ?.addEventListener(
            'input',
            updateTenantDepositPreview
        );

    document
        .getElementById(
            'tenant-deposit-method'
        )
        ?.addEventListener(
            'change',
            updateTenantDepositCashReceiver
        );

    document
        .getElementById(
            'tenant-withdrawal-method'
        )
        ?.addEventListener(
            'change',
            updateTenantWithdrawalCashReceiver
        );

    document
        .getElementById(
            'tenant-withdrawal-account'
        )
        ?.addEventListener(
            'change',
            () => {
                updateTenantWithdrawalPreview();

                setTenantTransactionContext(
                    'tenant-withdrawal'
                );
            }
        );

    document
        .getElementById(
            'tenant-withdrawal-amount'
        )
        ?.addEventListener(
            'input',
            updateTenantWithdrawalPreview
        );

    document
        .getElementById(
            'tenant-adjustment-account'
        )
        ?.addEventListener(
            'change',
            () => {
                updateTenantAdjustmentPreview();

                setTenantTransactionContext(
                    'tenant-adjustment'
                );
            }
        );

    document
        .getElementById(
            'tenant-adjustment-corrected-balance'
        )
        ?.addEventListener(
            'input',
            updateTenantAdjustmentPreview
        );

    document
        .getElementById(
            'tenant-deposit-form'
        )
        ?.addEventListener(
            'submit',
            submitTenantDeposit
        );

    document
        .getElementById(
            'tenant-withdrawal-form'
        )
        ?.addEventListener(
            'submit',
            submitTenantWithdrawal
        );

    document
        .getElementById(
            'tenant-adjustment-form'
        )
        ?.addEventListener(
            'submit',
            submitTenantAdjustment
        );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key
                !== 'Escape'
            ) {
                return;
            }

            document
                .querySelectorAll(
                    '#tenant-deposit-drawer.pm-drawer-active, '
                    + '#tenant-withdrawal-drawer.pm-drawer-active, '
                    + '#tenant-adjustment-drawer.pm-drawer-active, '
                    + '#tenant-security-application-drawer.pm-drawer-active'
                )
                .forEach(
                    (drawer) => {
                        closeTenantTransactionDrawer(
                            drawer.id
                        );
                    }
                );
        }
    );
}


/**
 * Require an operationally selected Tenant before opening a mutation drawer.
 *
 * @returns {boolean}
 */
function hasSelectedTenantTransactionContext() {
    if (
        ! selectedTenant
        || ! selectedTenantId
    ) {
        showTenantError(
            translate(
                'tenants.no_tenant_available'
            )
        );

        return false;
    }

    return true;
}


/**
 * Open the Deposit drawer.
 */
async function openTenantDepositDrawer() {
    if (
        ! hasSelectedTenantTransactionContext()
    ) {
        return;
    }

    resetTenantDepositDrawer();

    populateTenantLeaseSelect(
        'tenant-deposit-lease'
    );

    setTenantTransactionContext(
        'tenant-deposit'
    );

    setTenantTransactionToday(
        'tenant-deposit-date'
    );

    updateTenantDepositCashReceiver();

    openTenantTransactionDrawer(
        'tenant-deposit-drawer'
    );
}


/**
 * Open the Withdrawal drawer.
 */
async function openTenantWithdrawalDrawer() {
    if (
        ! hasSelectedTenantTransactionContext()
    ) {
        return;
    }

    resetTenantWithdrawalDrawer();

    populateTenantLeaseSelect(
        'tenant-withdrawal-lease'
    );

    setTenantTransactionContext(
        'tenant-withdrawal'
    );

    setTenantTransactionToday(
        'tenant-withdrawal-date'
    );

    updateTenantWithdrawalCashReceiver();

    openTenantTransactionDrawer(
        'tenant-withdrawal-drawer'
    );
}


/**
 * Open the Adjustment drawer.
 *
 * Adjustment itself is not presented as Lease-first. The account selector
 * contains every actual Tenant fund account belonging to this Tenant, with
 * Lease / property context included in each label.
 */
async function openTenantAdjustmentDrawer() {
    if (
        ! hasSelectedTenantTransactionContext()
    ) {
        return;
    }

    resetTenantAdjustmentDrawer();

    setTenantTransactionContext(
        'tenant-adjustment'
    );

    openTenantTransactionDrawer(
        'tenant-adjustment-drawer'
    );

    await populateTenantAdjustmentAccounts();
}


/**
 * Populate one Deposit/Withdrawal Lease selector.
 *
 * Draft Leases are excluded because new money operations cannot be posted
 * against them under the existing validation contract.
 */
function populateTenantLeaseSelect(
    selectId
) {
    const select =
        document.getElementById(
            selectId
        );

    if (! select) {
        return;
    }

    select.innerHTML =
        `<option value="">${escapeHtml(
            translate(
                'tenants.select_lease'
            )
        )}</option>`;

    selectedTenantLeases
        .filter(
            (lease) =>
                lease?.status
                !== 'draft'
        )
        .forEach(
            (lease) => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(
                        lease.id
                    );

                option.textContent =
                    tenantLeaseLabel(
                        lease
                    );

                select.appendChild(
                    option
                );
            }
        );
}


/**
 * Fetch and cache authoritative Lease detail.
 *
 * @param {number|string} leaseId
 * @returns {Promise<object|null>}
 */
async function tenantLeaseDetail(
    leaseId
) {
    const numericId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericId
        )
        || numericId <= 0
    ) {
        return null;
    }

    if (
        selectedTenantLeaseDetails.has(
            numericId
        )
    ) {
        return selectedTenantLeaseDetails.get(
            numericId
        );
    }

    const response =
        await apiRequest(
            `/api/leases/${encodeURIComponent(
                numericId
            )}`
        );

    const lease =
        await parseJsonResponse(
            response
        );

    if (
        Number(
            lease?.tenant_id
            ?? lease?.tenant?.id
        )
        !== Number(
            selectedTenantId
        )
    ) {
        throw new Error(
            translate(
                'tenants.unable_to_load_tenant'
            )
        );
    }

    selectedTenantLeaseDetails.set(
        numericId,
        lease
    );

    return lease;
}


/**
 * Populate Deposit destination choices for one Lease.
 *
 * Rent Payment uses /api/payments and FIFO.
 * The other three destinations use the Tenant Fund Deposit endpoint.
 */
async function populateDepositDestinations(
    leaseId
) {
    const select =
        document.getElementById(
            'tenant-deposit-account'
        );

    if (! select) {
        return;
    }

    resetTransactionSelect(
        select,
        translate(
            'tenants.select_lease_first'
        )
    );

    if (! leaseId) {
        updateTenantDepositPreview();

        return;
    }

    try {
        const lease =
            await tenantLeaseDetail(
                leaseId
            );

        select.disabled =
            false;

        select.innerHTML = '';

        /*
         * Rent Payment is displayed only when this Lease actually has an
         * outstanding Invoice. The backend Payment allocation remains FIFO.
         */
        const invoices =
            Array.isArray(
                lease?.invoices
            )
                ? lease.invoices
                : [];

        const hasOutstandingInvoice =
            invoices.some(
                (invoice) =>
                    Number(
                        invoice?.outstanding_amount
                        ?? invoice?.outstanding
                        ?? 0
                    ) > 0
                    || [
                        'issued',
                        'partial',
                        'overdue',
                    ].includes(
                        String(
                            invoice?.status
                            ?? ''
                        )
                    )
            );

        if (hasOutstandingInvoice) {
            appendTransactionOption(
                select,
                'rent_payment',
                translate(
                    'tenants.rent_payment'
                ),
                0,
                {
                    kind:
                        'rent_payment',
                }
            );
        }

        const accounts =
            tenantFundAccounts(
                lease
            );

        accounts
            .filter(
                (account) =>
                    [
                        'rent_reserve',
                        'consumable_advance',
                        'security_deposit',
                    ].includes(
                        account.type
                    )
            )
            .forEach(
                (account) => {
                    appendTransactionOption(
                        select,
                        account.type,
                        tenantFundAccountLabel(
                            account
                        ),
                        tenantFundBalance(
                            account
                        ),
                        {
                            kind:
                                'fund',
                            accountId:
                                account.id,
                        }
                    );
                }
            );

        if (
            select.options.length
            === 0
        ) {
            resetTransactionSelect(
                select,
                translate(
                    'tenants.no_eligible_accounts'
                )
            );
        }

        updateTenantDepositPreview();
    } catch (error) {
        showTenantTransactionError(
            'tenant-deposit-error',
            error instanceof Error
                ? error.message
                : translate(
                    'tenants.unable_to_load_accounts'
                )
        );
    }
}


/**
 * Populate eligible Withdrawal accounts for a Lease.
 *
 * Normal withdrawal must never create a negative fund balance, therefore
 * only positive-balance accounts are offered.
 */
async function populateWithdrawalAccounts(
    leaseId
) {
    const select =
        document.getElementById(
            'tenant-withdrawal-account'
        );

    if (! select) {
        return;
    }

    resetTransactionSelect(
        select,
        translate(
            'tenants.select_lease_first'
        )
    );

    if (! leaseId) {
        updateTenantWithdrawalPreview();

        return;
    }

    try {
        const lease =
            await tenantLeaseDetail(
                leaseId
            );

        const accounts =
            tenantFundAccounts(
                lease
            )
                .filter(
                    (account) =>
                        [
                            'rent_reserve',
                            'consumable_advance',
                        ].includes(
                            account.type
                        )
                        && tenantFundBalance(
                            account
                        ) > 0
                );

        select.innerHTML = '';

        accounts.forEach(
            (account) => {
                appendTransactionOption(
                    select,
                    account.id,
                    tenantFundAccountLabel(
                        account
                    ),
                    tenantFundBalance(
                        account
                    ),
                    {
                        kind:
                            'fund',
                        accountId:
                            account.id,
                    }
                );
            }
        );

        if (accounts.length === 0) {
            resetTransactionSelect(
                select,
                translate(
                    'tenants.no_withdrawable_funds'
                )
            );

            return;
        }

        select.disabled =
            false;

        updateTenantWithdrawalPreview();
    } catch (error) {
        showTenantTransactionError(
            'tenant-withdrawal-error',
            error instanceof Error
                ? error.message
                : translate(
                    'tenants.unable_to_load_accounts'
                )
        );
    }
}


/**
 * Load all actual fund accounts belonging to the selected Tenant for
 * Adjustment. Each account retains its Lease context.
 */
async function populateTenantAdjustmentAccounts() {
    const select =
        document.getElementById(
            'tenant-adjustment-account'
        );

    if (! select) {
        return;
    }

    select.disabled =
        true;

    select.innerHTML =
        `<option value="">${escapeHtml(
            translate(
                'tenants.select_account'
            )
        )}</option>`;

    try {
        for (
            const summaryLease
            of selectedTenantLeases
        ) {
            const lease =
                await tenantLeaseDetail(
                    summaryLease.id
                );

            tenantFundAccounts(
                lease
            ).forEach(
                (account) => {
                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        String(
                            account.id
                        );

                    option.dataset.balance =
                        String(
                            tenantFundBalance(
                                account
                            )
                        );

                    option.dataset.leaseId =
                        String(
                            lease.id
                        );

                    option.dataset.fundType =
                        String(
                            account.type
                            ?? ''
                        );

                    option.textContent =
                        `${tenantFundAccountLabel(
                            account
                        )} · ${tenantLeaseLabel(
                            lease
                        )}`;

                    select.appendChild(
                        option
                    );
                }
            );
        }

        select.disabled =
            select.options.length
            <= 1;

        updateTenantAdjustmentPreview();
    } catch (error) {
        showTenantTransactionError(
            'tenant-adjustment-error',
            error instanceof Error
                ? error.message
                : translate(
                    'tenants.unable_to_load_accounts'
                )
        );
    }
}


/**
 * Return actual Tenant fund accounts from Lease detail.
 *
 * @param {object} lease
 * @returns {Array<object>}
 */
function tenantFundAccounts(
    lease
) {
    return Array.isArray(
        lease?.tenant_fund_accounts
    )
        ? lease.tenant_fund_accounts
        : [];
}


/**
 * Resolve an authoritative account balance already serialized by the API.
 *
 * @param {object} account
 * @returns {number}
 */
function tenantFundBalance(
    account
) {
    return Number(
        account?.balance
        ?? 0
    );
}


/**
 * Human-readable account option including current balance.
 *
 * @param {object} account
 * @returns {string}
 */
function tenantFundAccountLabel(
    account
) {
    return `${tenantFundTypeLabel(
        account?.type
    )} — ${formatCurrency(
        tenantFundBalance(
            account
        )
    )}`;
}


/**
 * Human-readable fund type.
 *
 * @param {string} type
 * @returns {string}
 */
function tenantFundTypeLabel(
    type
) {
    const key = {
        rent_reserve:
            'tenants.rent_reserve',

        consumable_advance:
            'tenants.consumable_advance',

        security_deposit:
            'tenants.security_deposit',
    }[
        String(
            type
            ?? ''
        )
    ];

    return key
        ? translate(key)
        : capitalizeWords(
            type
        );
}


/**
 * Lease option label containing Building / Unit and status where available.
 *
 * @param {object} lease
 * @returns {string}
 */
function tenantLeaseLabel(
    lease
) {
    const building =
        lease?.unit?.building?.name
        ?? lease?.building?.name
        ?? '';

    const unit =
        lease?.unit?.name
        ?? '';

    const reference =
        lease?.reference
        ?? lease?.lease_reference
        ?? `#${lease?.id ?? ''}`;

    return [
        reference,
        building,
        unit,
    ]
        .filter(Boolean)
        .join(' · ');
}


/**
 * Append one selectable transaction account/destination.
 */
function appendTransactionOption(
    select,
    value,
    label,
    balance,
    metadata = {}
) {
    const option =
        document.createElement(
            'option'
        );

    option.value =
        String(
            value
        );

    option.textContent =
        label;

    option.dataset.balance =
        String(
            Number(
                balance
                ?? 0
            )
        );

    Object.entries(
        metadata
    ).forEach(
        ([key, metadataValue]) => {
            if (
                metadataValue
                === null
                || metadataValue
                === undefined
            ) {
                return;
            }

            option.dataset[key] =
                String(
                    metadataValue
                );
        }
    );

    select.appendChild(
        option
    );
}


/**
 * Restore a transaction selector to a disabled placeholder.
 */
function resetTransactionSelect(
    select,
    label
) {
    select.innerHTML =
        `<option value="">${escapeHtml(
            label
        )}</option>`;

    select.disabled =
        true;
}


/**
 * Update Deposit Current → Amount → Resulting preview.
 */
function updateTenantDepositPreview() {
    const destination =
        selectedTransactionOption(
            'tenant-deposit-account'
        );

    const amount =
        positiveIntegerInput(
            'tenant-deposit-amount'
        );

    /*
     * Rent Payment is not a persistent fund balance. For that destination,
     * the preview therefore presents transaction amount rather than inventing
     * a browser-side receivable balance.
     */
    const rentPayment =
        destination?.dataset.kind
        === 'rent_payment';

    const current =
        rentPayment
            ? null
            : selectedOptionBalance(
                destination
            );

    setCurrencyPreview(
        'tenant-deposit-current-balance',
        current
    );

    setCurrencyPreview(
        'tenant-deposit-preview-amount',
        amount
    );

    setCurrencyPreview(
        'tenant-deposit-resulting-balance',
        current === null
            ? null
            : current + amount
    );
}


/**
 * Update Withdrawal Current → Amount → Resulting preview.
 */
function updateTenantWithdrawalPreview() {
    const account =
        selectedTransactionOption(
            'tenant-withdrawal-account'
        );

    const current =
        selectedOptionBalance(
            account
        );

    const amount =
        positiveIntegerInput(
            'tenant-withdrawal-amount'
        );

    setCurrencyPreview(
        'tenant-withdrawal-current-balance',
        current
    );

    setCurrencyPreview(
        'tenant-withdrawal-preview-amount',
        amount
    );

    setCurrencyPreview(
        'tenant-withdrawal-resulting-balance',
        current === null
            ? null
            : current - amount
    );

    const input =
        document.getElementById(
            'tenant-withdrawal-amount'
        );

    if (
        input
        && current !== null
    ) {
        input.max =
            String(
                Math.max(
                    0,
                    current
                )
            );
    }
}


/**
 * Update Adjustment Current → Correct → Difference preview.
 */
function updateTenantAdjustmentPreview() {
    const account =
        selectedTransactionOption(
            'tenant-adjustment-account'
        );

    const current =
        selectedOptionBalance(
            account
        );

    const correctedInput =
        document.getElementById(
            'tenant-adjustment-corrected-balance'
        );

    const corrected =
        correctedInput?.value === ''
            ? null
            : Number(
                correctedInput?.value
            );

    const difference =
        (
            current === null
            || corrected === null
            || ! Number.isFinite(
                corrected
            )
        )
            ? null
            : corrected - current;

    setCurrencyPreview(
        'tenant-adjustment-current-balance',
        current
    );

    setCurrencyPreview(
        'tenant-adjustment-preview-correct',
        corrected
    );

    setCurrencyPreview(
        'tenant-adjustment-difference',
        difference
    );
}


/**
 * Cash Receiver is displayed only for Cash Deposit.
 */
function updateTenantTransactionCashReceiver(action) {
    const wrapper =
        document.getElementById(
            'tenant-deposit-cash-receiver-wrapper'
        );

    const input =
        document.getElementById(
            `tenant-${action}-cash-receiver`
        );

    const method =
        document.getElementById(
            `tenant-${action}-method`
        )?.value;

    if (
        ! wrapper
        || ! input
    ) {
        return;
    }

    const cash =
        method === 'cash';

    wrapper.classList.toggle(
        'hidden',
        ! cash
    );

    input.value =
        cash
            ? String(
                document.body.dataset
                    .currentUserName
                ?? ''
            )
            : '';
}


/**
 * Refresh Deposit Cash Receiver visibility.
 */
function updateTenantDepositCashReceiver() {
    updateTenantTransactionCashReceiver(
        'deposit'
    );
}


/**
 * Refresh Withdrawal Cash Receiver visibility.
 */
function updateTenantWithdrawalCashReceiver() {
    updateTenantTransactionCashReceiver(
        'withdrawal'
    );
}


/**
 * Selected option or null when still on placeholder.
 */
function selectedTransactionOption(
    selectId
) {
    const select =
        document.getElementById(
            selectId
        );

    if (
        ! select
        || ! select.value
    ) {
        return null;
    }

    return select.options[
        select.selectedIndex
    ]
    ?? null;
}


/**
 * Balance serialized into a transaction option.
 */
function selectedOptionBalance(
    option
) {
    if (! option) {
        return null;
    }

    const balance =
        Number(
            option.dataset.balance
        );

    return Number.isFinite(
        balance
    )
        ? balance
        : null;
}


/**
 * Read positive integer transaction amount for preview.
 */
function positiveIntegerInput(
    inputId
) {
    const value =
        Number(
            document
                .getElementById(
                    inputId
                )
                ?.value
            ?? 0
        );

    return (
        Number.isInteger(
            value
        )
        && value > 0
    )
        ? value
        : 0;
}


/**
 * Currency preview helper.
 */
function setCurrencyPreview(
    elementId,
    amount
) {
    const element =
        document.getElementById(
            elementId
        );

    if (! element) {
        return;
    }

    element.textContent =
        (
            amount === null
            || amount === undefined
            || ! Number.isFinite(
                Number(
                    amount
                )
            )
        )
            ? '—'
            : formatCurrency(
                Number(
                    amount
                )
            );
}


/**
 * Fill drawer identifying context.
 */
function setTenantTransactionContext(
    prefix
) {
    const tenantName =
        tenantDisplayName(
            selectedTenant
        );

    setElementText(
        `${prefix}-tenant-context`,
        tenantName
    );

    setElementText(
        `${prefix}-property-context`,
        translate(
            'tenants.select_lease_context'
        )
    );
}


/**
 * Today in Patrimoine DD-MM-YYYY display form.
 */
function setTenantTransactionToday(
    inputId
) {
    const input =
        document.getElementById(
            inputId
        );

    if (! input) {
        return;
    }

    const now =
        new Date();

    const iso =
        [
            now.getFullYear(),
            String(
                now.getMonth() + 1
            ).padStart(
                2,
                '0'
            ),
            String(
                now.getDate()
            ).padStart(
                2,
                '0'
            ),
        ].join('-');

    input.value =
        dateForDisplay(
            iso
        );
}


/**
 * Reset Deposit controls.
 */
function resetTenantDepositDrawer() {
    document
        .getElementById(
            'tenant-deposit-form'
        )
        ?.reset();

    hideTenantTransactionError(
        'tenant-deposit-error'
    );

    resetTransactionSelect(
        document.getElementById(
            'tenant-deposit-account'
        ),
        translate(
            'tenants.select_lease_first'
        )
    );

    updateTenantDepositPreview();
}


/**
 * Reset Withdrawal controls.
 */
function resetTenantWithdrawalDrawer() {
    document
        .getElementById(
            'tenant-withdrawal-form'
        )
        ?.reset();

    hideTenantTransactionError(
        'tenant-withdrawal-error'
    );

    resetTransactionSelect(
        document.getElementById(
            'tenant-withdrawal-account'
        ),
        translate(
            'tenants.select_lease_first'
        )
    );

    updateTenantWithdrawalPreview();
}


/**
 * Reset Adjustment controls.
 */
function resetTenantAdjustmentDrawer() {
    document
        .getElementById(
            'tenant-adjustment-form'
        )
        ?.reset();

    hideTenantTransactionError(
        'tenant-adjustment-error'
    );

    const select =
        document.getElementById(
            'tenant-adjustment-account'
        );

    if (select) {
        select.innerHTML =
            `<option value="">${escapeHtml(
                translate(
                    'tenants.select_account'
                )
            )}</option>`;

        select.disabled =
            true;
    }

    updateTenantAdjustmentPreview();
}


/**
 * Open a standard V1.0.4 drawer.
 */
function openTenantTransactionDrawer(
    id
) {
    const drawer =
        document.getElementById(
            id
        );

    if (! drawer) {
        return;
    }

    const oldTimer =
        tenantTransactionDrawerTimers.get(
            id
        );

    if (oldTimer) {
        window.clearTimeout(
            oldTimer
        );

        tenantTransactionDrawerTimers.delete(
            id
        );
    }

    drawer.classList.remove(
        'pm-drawer-open',
        'pm-drawer-closing'
    );

    drawer.removeAttribute(
        'hidden'
    );

    drawer.classList.add(
        'pm-drawer-active'
    );

    drawer.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    const panel =
        drawer.querySelector(
            '.pm-drawer-panel'
        );

    if (panel) {
        void panel.getBoundingClientRect();
    }

    window.requestAnimationFrame(
        () => {
            window.requestAnimationFrame(
                () => {
                    drawer.classList.add(
                        'pm-drawer-open'
                    );
                }
            );
        }
    );
}


/**
 * Close a Tenant transaction drawer.
 */
function closeTenantTransactionDrawer(
    id
) {
    const drawer =
        document.getElementById(
            id
        );

    if (
        ! drawer
        || ! drawer.classList.contains(
            'pm-drawer-active'
        )
    ) {
        return;
    }

    drawer.classList.remove(
        'pm-drawer-open'
    );

    drawer.classList.add(
        'pm-drawer-closing'
    );

    drawer.setAttribute(
        'aria-hidden',
        'true'
    );

    const oldTimer =
        tenantTransactionDrawerTimers.get(
            id
        );

    if (oldTimer) {
        window.clearTimeout(
            oldTimer
        );
    }

    const timer =
        window.setTimeout(
            () => {
                drawer.classList.remove(
                    'pm-drawer-active',
                    'pm-drawer-closing'
                );

                drawer.setAttribute(
                    'hidden',
                    ''
                );

                tenantTransactionDrawerTimers.delete(
                    id
                );

                const anotherDrawerOpen =
                    document.querySelector(
                        '.pm-drawer.pm-drawer-active'
                    );

                if (! anotherDrawerOpen) {
                    document.body.classList.remove(
                        'overflow-hidden'
                    );
                }
            },
            850
        );

    tenantTransactionDrawerTimers.set(
        id,
        timer
    );
}


/**
 * Drawer-local financial error.
 */
function showTenantTransactionError(
    id,
    message
) {
    const element =
        document.getElementById(
            id
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
 * Clear drawer-local financial error.
 */
function hideTenantTransactionError(
    id
) {
    const element =
        document.getElementById(
            id
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


/**
 * Safe text helper.
 */
function setElementText(
    id,
    value
) {
    const element =
        document.getElementById(
            id
        );

    if (element) {
        element.textContent =
            value ?? '';
    }
}

/*
|--------------------------------------------------------------------------
| V1.0.5 Tenant Transaction Submission
|--------------------------------------------------------------------------
|
| This layer maps the frozen Tenant transaction drawers onto already-tested
| backend financial boundaries.
|
| No accounting calculations are duplicated here:
|
| Rent Payment
|     POST /api/payments
|     Existing PaymentAllocationService owns FIFO.
|
| Held-fund Deposit
|     POST /api/tenant-fund-deposits
|     Entire amount is classified directly into the selected fund.
|
| Withdrawal
|     POST /api/tenant-fund-withdrawals
|     Backend enforces availability and creates Withdrawal Receipt.
|
| Adjustment
|     POST /api/tenant-funds/{account}/adjustments
|     Backend calculates difference, journals, logs and creates Voucher.
|
*/


/**
 * Submit Deposit.
 *
 * Deposit has two intentionally different backend boundaries:
 *
 * - Rent Payment -> ordinary Payment / FIFO;
 * - Lease fund -> direct Tenant Fund Deposit.
 *
 * @param {SubmitEvent} event
 */
async function submitTenantDeposit(
    event
) {
    event.preventDefault();

    hideTenantTransactionError(
        'tenant-deposit-error'
    );

    const leaseId =
        requiredPositiveIntegerValue(
            'tenant-deposit-lease'
        );

    const destination =
        selectedTransactionOption(
            'tenant-deposit-account'
        );

    const amount =
        requiredPositiveIntegerValue(
            'tenant-deposit-amount'
        );

    const transactionDate =
        transactionDateForApi(
            'tenant-deposit-date'
        );

    const paymentMethod =
        String(
            document
                .getElementById(
                    'tenant-deposit-method'
                )
                ?.value
            ?? ''
        );

    const reference =
        nullableTrimmedValue(
            'tenant-deposit-reference'
        );

    const notes =
        nullableTrimmedValue(
            'tenant-deposit-notes'
        );

    if (
        ! leaseId
        || ! destination
        || ! amount
        || ! transactionDate
        || ! [
            'cash',
            'bank_transfer',
            'momo',
        ].includes(
            paymentMethod
        )
    ) {
        showTenantTransactionError(
            'tenant-deposit-error',
            translate(
                'tenants.transaction_required_fields'
            )
        );

        return;
    }

    const submitButton =
        document.getElementById(
            'tenant-deposit-submit'
        );

    await withTenantTransactionSubmitLock(
        submitButton,
        async () => {
            try {
                let result;

                if (
                    destination.dataset.kind
                    === 'rent_payment'
                ) {
                    result =
                        await postTenantTransaction(
                            '/api/payments',
                            {
                                lease_id:
                                    leaseId,

                                amount,

                                payment_date:
                                    transactionDate,

                                payment_method:
                                    paymentMethod,

                                reference,

                                notes,
                            }
                        );
                } else {
                    const fundType =
                        String(
                            destination.value
                            ?? ''
                        );

                    if (
                        ! [
                            'rent_reserve',
                            'consumable_advance',
                            'security_deposit',
                        ].includes(
                            fundType
                        )
                    ) {
                        throw new Error(
                            translate(
                                'tenants.invalid_account'
                            )
                        );
                    }

                    result =
                        await postTenantTransaction(
                            '/api/tenant-fund-deposits',
                            {
                                lease_id:
                                    leaseId,

                                fund_type:
                                    fundType,

                                amount,

                                transaction_date:
                                    transactionDate,

                                payment_method:
                                    paymentMethod,

                                reference,

                                notes,
                            }
                        );
                }

                closeTenantTransactionDrawer(
                    'tenant-deposit-drawer'
                );

                await refreshSelectedTenantAfterTransaction();

                showTenantTransactionSuccess(
                    translate(
                        destination.dataset.kind
                        === 'rent_payment'
                            ? 'tenants.rent_payment_recorded'
                            : 'tenants.deposit_recorded'
                    ),
                    tenantPaymentDocumentEndpoint(
                        result
                    )
                );
            } catch (error) {
                showTenantTransactionError(
                    'tenant-deposit-error',
                    tenantTransactionErrorMessage(
                        error
                    )
                );
            }
        }
    );
}


/**
 * Submit Tenant fund Withdrawal.
 *
 * @param {SubmitEvent} event
 */
async function submitTenantWithdrawal(
    event
) {
    event.preventDefault();

    hideTenantTransactionError(
        'tenant-withdrawal-error'
    );

    const account =
        selectedTransactionOption(
            'tenant-withdrawal-account'
        );

    const accountId =
        Number(
            account?.dataset.accountId
            ?? account?.value
            ?? 0
        );

    const amount =
        requiredPositiveIntegerValue(
            'tenant-withdrawal-amount'
        );

    const transactionDate =
        transactionDateForApi(
            'tenant-withdrawal-date'
        );

    const paymentMethod =
        String(
            document
                .getElementById(
                    'tenant-withdrawal-method'
                )
                ?.value
            ?? ''
        );

    const reference =
        nullableTrimmedValue(
            'tenant-withdrawal-reference'
        );

    const notes =
        nullableTrimmedValue(
            'tenant-withdrawal-notes'
        );

    const available =
        selectedOptionBalance(
            account
        );

    if (
        ! Number.isInteger(
            accountId
        )
        || accountId <= 0
        || ! amount
        || ! transactionDate
        || ! [
            'cash',
            'bank_transfer',
            'momo',
        ].includes(
            paymentMethod
        )
    ) {
        showTenantTransactionError(
            'tenant-withdrawal-error',
            translate(
                'tenants.transaction_required_fields'
            )
        );

        return;
    }

    /*
     * Client-side preview guard only.
     *
     * The backend remains authoritative and locks/revalidates the account.
     */
    if (
        available !== null
        && amount > available
    ) {
        showTenantTransactionError(
            'tenant-withdrawal-error',
            translate(
                'tenants.withdrawal_exceeds_balance'
            )
        );

        return;
    }

    const submitButton =
        document.getElementById(
            'tenant-withdrawal-submit'
        );

    await withTenantTransactionSubmitLock(
        submitButton,
        async () => {
            try {
                const result =
                    await postTenantTransaction(
                        '/api/tenant-fund-withdrawals',
                        {
                            tenant_fund_account_id:
                                accountId,

                            amount,

                            transaction_date:
                                transactionDate,

                            payment_method:
                                paymentMethod,

                            reference,

                            notes,
                        }
                    );

                closeTenantTransactionDrawer(
                    'tenant-withdrawal-drawer'
                );

                await refreshSelectedTenantAfterTransaction();

                showTenantTransactionSuccess(
                    translate(
                        'tenants.withdrawal_recorded'
                    ),
                    result
                        ?.withdrawal_receipt
                        ?.pdf_endpoint
                    ?? null
                );
            } catch (error) {
                showTenantTransactionError(
                    'tenant-withdrawal-error',
                    tenantTransactionErrorMessage(
                        error
                    )
                );
            }
        }
    );
}


/**
 * Submit standardized Tenant Adjustment.
 *
 * Adjustment has no user-supplied date. The server always uses today.
 *
 * @param {SubmitEvent} event
 */
async function submitTenantAdjustment(
    event
) {
    event.preventDefault();

    hideTenantTransactionError(
        'tenant-adjustment-error'
    );

    const account =
        selectedTransactionOption(
            'tenant-adjustment-account'
        );

    const accountId =
        Number(
            account?.value
            ?? 0
        );

    const correctedBalance =
        integerInputValue(
            'tenant-adjustment-corrected-balance'
        );

    const reason =
        nullableTrimmedValue(
            'tenant-adjustment-reason'
        );

    const reference =
        nullableTrimmedValue(
            'tenant-adjustment-reference'
        );

    if (
        ! Number.isInteger(
            accountId
        )
        || accountId <= 0
        || correctedBalance === null
        || ! reason
    ) {
        showTenantTransactionError(
            'tenant-adjustment-error',
            translate(
                'tenants.adjustment_required_fields'
            )
        );

        return;
    }

    const submitButton =
        document.getElementById(
            'tenant-adjustment-submit'
        );

    await withTenantTransactionSubmitLock(
        submitButton,
        async () => {
            try {
                const result =
                    await postTenantTransaction(
                        `/api/tenant-funds/${encodeURIComponent(
                            accountId
                        )}/adjustments`,
                        {
                            corrected_balance:
                                correctedBalance,

                            reason,

                            reference,
                        }
                    );

                closeTenantTransactionDrawer(
                    'tenant-adjustment-drawer'
                );

                await refreshSelectedTenantAfterTransaction();

                showTenantTransactionSuccess(
                    translate(
                        'tenants.adjustment_recorded'
                    ),
                    result
                        ?.adjustment_voucher
                        ?.pdf_endpoint
                    ?? null
                );
            } catch (error) {
                showTenantTransactionError(
                    'tenant-adjustment-error',
                    tenantTransactionErrorMessage(
                        error
                    )
                );
            }
        }
    );
}


/**
 * Perform authenticated JSON POST using Patrimoine's central API helper.
 *
 * @param {string} endpoint
 * @param {object} payload
 * @returns {Promise<any>}
 */
async function postTenantTransaction(
    endpoint,
    payload
) {
    const response =
        await apiRequest(
            endpoint,
            {
                method:
                    'POST',

                body:
                    JSON.stringify(
                        payload
                    ),
            }
        );

    return parseJsonResponse(
        response
    );
}


/**
 * Refresh all selected Tenant context from authoritative APIs after mutation.
 */
async function refreshSelectedTenantAfterTransaction() {
    const tenantId =
        selectedTenantId;

    if (! tenantId) {
        return;
    }

    /*
     * Financial account balances and invoices may have changed.
     * Never retain pre-transaction Lease cache.
     */
    selectedTenantLeaseDetails =
        new Map();

    await selectTenant(
        tenantId
    );
}


/**
 * Prevent accidental duplicate financial submission.
 *
 * @param {HTMLButtonElement|null} button
 * @param {Function} callback
 */
async function withTenantTransactionSubmitLock(
    button,
    callback
) {
    if (
        button?.disabled
    ) {
        return;
    }

    const previousDisabled =
        Boolean(
            button?.disabled
        );

    if (button) {
        button.disabled =
            true;

        button.setAttribute(
            'aria-busy',
            'true'
        );
    }

    try {
        await callback();
    } finally {
        if (button) {
            button.disabled =
                previousDisabled;

            button.removeAttribute(
                'aria-busy'
            );
        }
    }
}


/**
 * Convert Patrimoine DD-MM-YYYY input into API YYYY-MM-DD.
 *
 * @param {string} inputId
 * @returns {string|null}
 */
function transactionDateForApi(
    inputId
) {
    const value =
        String(
            document
                .getElementById(
                    inputId
                )
                ?.value
            ?? ''
        ).trim();

    if (! value) {
        return null;
    }

    try {
        return dateForApi(
            value
        );
    } catch {
        return null;
    }
}


/**
 * Read a positive integer field.
 *
 * @param {string} inputId
 * @returns {number|null}
 */
function requiredPositiveIntegerValue(
    inputId
) {
    const value =
        Number(
            document
                .getElementById(
                    inputId
                )
                ?.value
            ?? ''
        );

    return (
        Number.isInteger(
            value
        )
        && value > 0
    )
        ? value
        : null;
}


/**
 * Read any integer value, including zero and negatives.
 *
 * @param {string} inputId
 * @returns {number|null}
 */
function integerInputValue(
    inputId
) {
    const raw =
        String(
            document
                .getElementById(
                    inputId
                )
                ?.value
            ?? ''
        ).trim();

    if (raw === '') {
        return null;
    }

    const value =
        Number(
            raw
        );

    return Number.isInteger(
        value
    )
        ? value
        : null;
}


/**
 * Trim optional form value.
 *
 * @param {string} inputId
 * @returns {string|null}
 */
function nullableTrimmedValue(
    inputId
) {
    const value =
        String(
            document
                .getElementById(
                    inputId
                )
                ?.value
            ?? ''
        ).trim();

    return value === ''
        ? null
        : value;
}


/**
 * Normalize backend/browser exception for drawer presentation.
 *
 * parseJsonResponse already extracts Laravel validation messages where
 * possible, therefore preserving the server message is preferred.
 *
 * @param {unknown} error
 * @returns {string}
 */
function tenantTransactionErrorMessage(
    error
) {
    return error instanceof Error
        ? error.message
        : translate(
            'tenants.transaction_failed'
        );
}


/**
 * Resolve Deposit/Payment Receipt endpoint from returned Payment.
 *
 * Both ordinary Rent Payment and direct fund Deposit create a Payment.
 *
 * @param {any} result
 * @returns {string|null}
 */
function tenantPaymentDocumentEndpoint(
    result
) {
    const paymentId =
        Number(
            result?.payment?.id
            ?? result?.id
            ?? 0
        );

    return (
        Number.isInteger(
            paymentId
        )
        && paymentId > 0
    )
        ? `/api/payments/${paymentId}/receipt`
        : null;
}


/**
 * Show a short success state at Tenant workspace level.
 *
 * Document endpoint is retained as metadata for later history/drill-down
 * integration. We deliberately do not open a new browser window here because
 * authenticated document endpoints use Patrimoine's API authentication flow.
 *
 * @param {string} message
 * @param {string|null} documentEndpoint
 */
function showTenantTransactionSuccess(
    message,
    documentEndpoint = null
) {
    let element =
        document.getElementById(
            'tenant-transaction-success'
        );

    if (! element) {
        const tenantError =
            document.getElementById(
                'tenant-error'
            );

        element =
            document.createElement(
                'div'
            );

        element.id =
            'tenant-transaction-success';

        element.className = [
            'mb-4',
            'rounded-xl',
            'border',
            'border-emerald-200',
            'bg-emerald-50',
            'px-4',
            'py-3',
            'text-sm',
            'text-emerald-800',
        ].join(' ');

        if (
            tenantError?.parentNode
        ) {
            tenantError.parentNode.insertBefore(
                element,
                tenantError.nextSibling
            );
        }
    }

    if (! element) {
        return;
    }

    element.textContent =
        message;

    if (documentEndpoint) {
        element.dataset.documentEndpoint =
            documentEndpoint;
    } else {
        delete element.dataset
            .documentEndpoint;
    }

    element.classList.remove(
        'hidden'
    );

    window.setTimeout(
        () => {
            element?.classList.add(
                'hidden'
            );
        },
        5000
    );
}
