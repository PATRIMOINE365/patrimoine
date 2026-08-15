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
| Patrimoine Payments Workspace
|--------------------------------------------------------------------------
|
| The Payments screen is the operational workspace for actual incoming money.
|
| Patrimoine currently receives money from two separate accounting domains:
|
| - Tenant Payments attached to Leases;
| - Owner deposits attached to OwnerAccounts.
|
| These remain separate financial records internally. The browser combines
| them only for operational convenience through /api/payment-register.
|
*/

let currentPage = 1;

let tenantSearchTimer = null;

let ownerSearchTimer = null;

let selectedTenant = null;

let selectedOwnerAccount = null;

let selectedOwnerProperties = [];

/*
|--------------------------------------------------------------------------
| Initialization
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Payments workspace when the Payments register exists.
 *
 * @returns {Promise<boolean>}
 */
export async function initializePayments() {
    const list =
        document.getElementById(
            'payments-list'
        );

    if (! list) {
        return false;
    }

    initializePaymentFilters();

    initializeReceiptActions();

    initializeTenantFundActions();

    initializePaymentModal();

    initializeTenantFundModal();

    await loadPaymentRegister();

    return true;
}

/*
|--------------------------------------------------------------------------
| Payment Register Loading
|--------------------------------------------------------------------------
*/

/**
 * Load the current Payments register page.
 *
 * @param {number} page
 */
async function loadPaymentRegister(
    page = currentPage
) {
    currentPage = page;

    hidePaymentError();

    showLoadingState();

    try {
        const query =
            buildPaymentRegisterQuery(
                page
            );

        const response =
            await apiRequest(
                `/api/payment-register?${query}`
            );

        const data =
            await parseJsonResponse(
                response
            );

        renderPaymentSummary(
            data.summary
        );

        renderPaymentRegister(
            data.transactions
        );

        renderPagination(
            data.transactions
        );
    } catch (error) {
        showPaymentError(
            error instanceof Error
                ? error.message
                : translate('payments.unable_to_load')
        );

        renderEmptyRegister(
            translate('payments.unable_to_load')
        );
    }
}

/**
 * Build query parameters from the current Payment Register filters.
 *
 * @param {number} page
 * @returns {string}
 */
function buildPaymentRegisterQuery(
    page
) {
    const params =
        new URLSearchParams();

    params.set(
        'page',
        String(page)
    );

    params.set(
        'per_page',
        '25'
    );

    const source =
        fieldValue(
            'payment-source-filter'
        );

    const method =
        fieldValue(
            'payment-method-filter'
        );

    const from =
        fieldValue(
            'payment-from-filter'
        );

    const to =
        fieldValue(
            'payment-to-filter'
        );

    if (source) {
        params.set(
            'source',
            source
        );
    }

    if (method) {
        params.set(
            'payment_method',
            method
        );
    }

    if (from) {
        params.set(
            'from',
            from
        );
    }

    if (to) {
        params.set(
            'to',
            to
        );
    }

    return params.toString();
}

/*
|--------------------------------------------------------------------------
| Register Filters
|--------------------------------------------------------------------------
*/

/**
 * Reload the Payment Register whenever one of its filters changes.
 */
function initializePaymentFilters() {
    [
        'payment-source-filter',
        'payment-method-filter',
        'payment-from-filter',
        'payment-to-filter',
    ].forEach(
        (id) => {
            document
                .getElementById(id)
                ?.addEventListener(
                    'change',
                    async () => {
                        currentPage = 1;

                        await loadPaymentRegister();
                    }
                );
        }
    );
}

/*
|--------------------------------------------------------------------------
| Summary Cards
|--------------------------------------------------------------------------
*/

/**
 * Populate the current-month Payments summary.
 *
 * @param {{
 *     received_this_month: number,
 *     tenant_payments: number,
 *     owner_deposits: number,
 *     transactions: number
 * }} summary
 */
function renderPaymentSummary(
    summary
) {
    setText(
        'payments-received-month',
        formatCurrency(
            summary?.received_this_month
            ?? 0
        )
    );

    setText(
        'payments-tenant-total',
        formatCurrency(
            summary?.tenant_payments
            ?? 0
        )
    );

    setText(
        'payments-owner-total',
        formatCurrency(
            summary?.owner_deposits
            ?? 0
        )
    );

    setText(
        'payments-transaction-count',
        formatNumber(
            summary?.transactions
            ?? 0
        )
    );
}

/*
|--------------------------------------------------------------------------
| Payment Register
|--------------------------------------------------------------------------
*/

/**
 * Render the paginated Payment Register.
 *
 * @param {{
 *     data: Array<object>
 * }} pagination
 */
function renderPaymentRegister(
    pagination
) {
    const container =
        document.getElementById(
            'payments-list'
        );

    if (! container) {
        return;
    }

    const transactions =
        Array.isArray(
            pagination?.data
        )
            ? pagination.data
            : [];

    if (transactions.length === 0) {
        renderEmptyRegister(
            translate(
                'payments.no_matching_payments'
            )
        );

        return;
    }

    container.innerHTML =
        transactions
            .map(
                renderPaymentRow
            )
            .join('');
}

/**
 * Render one normalized Tenant Payment or Owner Deposit.
 *
 * Both payment sources may now have PDF receipts.
 *
 * @param {object} transaction
 * @returns {string}
 */
function renderPaymentRow(
    transaction
) {
    const tenantPayment =
        transaction.source
        === 'tenant';

    const sourceBadge =
        tenantPayment
            ? `
                <span
                    class="
                        inline-flex items-center
                        rounded-full
                        bg-blue-50 px-2.5 py-1
                        text-xs font-medium
                        text-blue-700
                    "
                >
                    ${escapeHtml(
                        translate(
                            'payments.tenant_payment'
                        )
                    )}
                </span>
            `
            : `
                <span
                    class="
                        inline-flex items-center
                        rounded-full
                        bg-emerald-50 px-2.5 py-1
                        text-xs font-medium
                        text-emerald-700
                    "
                >
                    ${escapeHtml(
                        translate(
                            'payments.owner_deposit'
                        )
                    )}
                </span>
            `;

    const property =
        buildPropertyLabel(
            transaction
        );

    /*
     * Receipt availability is determined by the API endpoint rather than
     * payment source. Tenant Payments and Owner Deposits may both therefore
     * display the same Receipt action.
     */
    /*
     * Tenant Payments may contain unapplied money that must be explicitly
     * classified into Rent Reserve, Consumable Advance or Security Deposit.
     *
     * We deliberately show the action on every Tenant Payment and fetch its
     * current server-side position only when opened. This avoids relying on
     * potentially stale values in the unified Payment Register.
     */
    const manageFundsAction =
        tenantPayment
            ? `
                <button
                    type="button"
                    data-manage-tenant-funds
                    data-payment-id="${escapeAttribute(
                        transaction.id
                    )}"
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        transition
                        hover:border-slate-300
                        hover:bg-slate-50
                    "
                >
                    ${escapeHtml(
                        translate(
                            'payments.manage_funds'
                        )
                    )}
                </button>
            `
            : '';

    const receiptAction =
        transaction.receipt_endpoint
            ? `
                <button
                    type="button"
                    data-receipt-endpoint="${escapeAttribute(
                        transaction.receipt_endpoint
                    )}"
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        transition
                        hover:border-slate-300
                        hover:bg-slate-50
                    "
                >
                    ${escapeHtml(
                        translate(
                            'payments.receipt'
                        )
                    )}
                </button>
            `
            : '';

    const depositPurpose =
        transaction.source === 'owner'
        && transaction.deposit_purpose
            ? `
                <span>
                    ${escapeHtml(
                        depositPurposeLabel(
                            transaction.deposit_purpose
                        )
                    )}
                </span>
            `
            : '';

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
                    flex flex-col gap-4
                    lg:flex-row
                    lg:items-start
                    lg:justify-between
                "
            >
                <div class="min-w-0">
                    <div
                        class="
                            flex flex-wrap
                            items-center gap-2
                        "
                    >
                        ${sourceBadge}

                        <span
                            class="
                                text-sm font-semibold
                                text-slate-950
                            "
                        >
                            ${escapeHtml(
                                transaction.payer_name
                                ?? (
                                    tenantPayment
                                        ? translate(
                                            'payments.tenant'
                                        )
                                        : translate(
                                            'payments.owner'
                                        )
                                )
                            )}
                        </span>
                    </div>

                    ${
                        property
                            ? `
                                <div
                                    class="
                                        mt-2 text-sm
                                        text-slate-600
                                    "
                                >
                                    ${escapeHtml(
                                        property
                                    )}
                                </div>
                            `
                            : ''
                    }

                    <div
                        class="
                            mt-3 flex flex-wrap
                            gap-x-5 gap-y-1
                            text-xs text-slate-500
                        "
                    >
                        <span>
                            ${escapeHtml(
                                formatDate(
                                    transaction.transaction_date
                                )
                            )}
                        </span>

                        ${
                            transaction.payment_method
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            paymentMethodLabel(
                                                transaction.payment_method
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            transaction.reference
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            translate(
                                                'payments.reference'
                                            )
                                        )}:
                                        ${escapeHtml(
                                            transaction.reference
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${depositPurpose}

                        ${
                            transaction.collector_name
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            translate(
                                                'payments.collector'
                                            )
                                        )}:
                                        ${escapeHtml(
                                            transaction.collector_name
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>

                    ${
                        transaction.notes
                            ? `
                                <div
                                    class="
                                        mt-3 max-w-3xl
                                        text-xs text-slate-500
                                    "
                                >
                                    ${escapeHtml(
                                        transaction.notes
                                    )}
                                </div>
                            `
                            : ''
                    }
                </div>

                <div
                    class="
                        flex shrink-0
                        items-center gap-3
                        lg:pl-6
                    "
                >
                    <div
                        class="
                            text-lg font-semibold
                            text-slate-950
                        "
                    >
                        ${escapeHtml(
                            formatCurrency(
                                transaction.amount
                            )
                        )}
                    </div>

                    ${manageFundsAction}

                    ${receiptAction}
                </div>
            </div>
        </article>
    `;
}

/**
 * Build the Building / Unit display value from normalized register fields.
 *
 * @param {object} transaction
 * @returns {string}
 */
function buildPropertyLabel(
    transaction
) {
    const building =
        transaction.building_name;

    const unit =
        transaction.unit_name;

    if (
        building
        && unit
    ) {
        return `${building} / ${unit}`;
    }

    if (building) {
        return building;
    }

    if (unit) {
        return unit;
    }

    if (
        transaction.source === 'tenant'
        && transaction.lease_id
    ) {
        return translate(
            'payments.lease_number',
            {
                id:
                    transaction.lease_id,
            }
        );
    }

    /*
     * Owner deposits are allowed without a specific Building.
     *
     * An OwnerAccount is consolidated across all properties owned by the
     * Party, so general owner funding has no mandatory property reference.
     */
    if (
        transaction.source === 'owner'
    ) {
        return translate(
            'payments.general_owner_account'
        );
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

/**
 * Render Payment Register pagination.
 *
 * @param {object} pagination
 */
function renderPagination(
    pagination
) {
    const container =
        document.getElementById(
            'payments-pagination'
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
                flex flex-col gap-3
                sm:flex-row
                sm:items-center
                sm:justify-between
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
                            ? 'payments.pagination_single'
                            : 'payments.pagination_plural',
                        {
                            current,
                            last,
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
                    id="payments-previous-page"
                    type="button"
                    ${current <= 1 ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'payments.previous'
                        )
                    )}
                </button>

                <button
                    id="payments-next-page"
                    type="button"
                    ${current >= last ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'payments.next'
                        )
                    )}
                </button>
            </div>
        </div>
    `;

    document
        .getElementById(
            'payments-previous-page'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (current > 1) {
                    await loadPaymentRegister(
                        current - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'payments-next-page'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (current < last) {
                    await loadPaymentRegister(
                        current + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Receipt Actions
|--------------------------------------------------------------------------
*/

/**
 * Handle Receipt buttons for both Tenant Payments and Owner Deposits.
 *
 * Direct browser navigation cannot be used because document endpoints
 * require the Sanctum Bearer token stored in sessionStorage.
 */
function initializeReceiptActions() {
    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '[data-receipt-endpoint]'
                );

            if (! button) {
                return;
            }

            const endpoint =
                button.dataset
                    .receiptEndpoint;

            if (! endpoint) {
                return;
            }

            await openAuthenticatedPdf(
                endpoint
            );
        }
    );
}

/**
 * Fetch and open an authenticated PDF.
 *
 * @param {string} endpoint
 */
async function openAuthenticatedPdf(
    endpoint
) {
    hidePaymentError();

    try {
        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            'application/pdf',
                    },
                }
            );

        if (! response.ok) {
            throw new Error(
                translate('payments.unable_to_open_receipt')
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
        showPaymentError(
            error instanceof Error
                ? error.message
                : translate('payments.unable_to_open_receipt')
        );
    }
}

/*
|--------------------------------------------------------------------------
| Record Payment Modal
|--------------------------------------------------------------------------
*/

/**
 * Initialize the complete Record Payment modal.
 */
function initializePaymentModal() {
    const modal =
        document.getElementById(
            'payment-modal'
        );

    const form =
        document.getElementById(
            'payment-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    document
        .getElementById(
            'record-payment-button'
        )
        ?.addEventListener(
            'click',
            () => {
                openPaymentModal();
            }
        );

    document
        .getElementById(
            'close-payment-modal-button'
        )
        ?.addEventListener(
            'click',
            () => {
                closePaymentModal();
            }
        );

    document
        .getElementById(
            'cancel-payment-button'
        )
        ?.addEventListener(
            'click',
            () => {
                closePaymentModal();
            }
        );

    modal.addEventListener(
        'click',
        (event) => {
            if (
                event.target === modal
            ) {
                closePaymentModal();
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closePaymentModal();
            }
        }
    );

    document
        .querySelectorAll(
            'input[name="payment_source"]'
        )
        .forEach(
            (input) => {
                input.addEventListener(
                    'change',
                    () => {
                        updatePaymentSource();
                    }
                );
            }
        );

    document
        .getElementById(
            'payment-method'
        )
        ?.addEventListener(
            'change',
            () => {
                updateCollectorVisibility();
            }
        );

    initializeTenantSearch();

    initializeOwnerSearch();

    initializeOwnerPropertySelectors();

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            await submitPayment();
        }
    );
}

/**
 * Open the modal with a clean form.
 */
function openPaymentModal() {
    resetPaymentForm();

    const modal =
        document.getElementById(
            'payment-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.remove(
        'hidden'
    );

    modal.classList.add(
        'flex'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    document
        .getElementById(
            'tenant-payment-search'
        )
        ?.focus();
}

/**
 * Close the modal.
 */
function closePaymentModal() {
    const modal =
        document.getElementById(
            'payment-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.classList.remove(
        'flex'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    hidePaymentFormError();

    hideSearchResults(
        'tenant-payment-search-results'
    );

    hideSearchResults(
        'owner-payment-search-results'
    );
}

/**
 * Reset all modal state.
 */
function resetPaymentForm() {
    const form =
        document.getElementById(
            'payment-form'
        );

    form?.reset();

    selectedTenant = null;

    selectedOwnerAccount = null;

    selectedOwnerProperties = [];

    setFieldValue(
        'tenant-payment-party-id',
        ''
    );

    setFieldValue(
        'owner-payment-account-id',
        ''
    );

    const tenantRadio =
        document.getElementById(
            'payment-source-tenant'
        );

    if (tenantRadio) {
        tenantRadio.checked = true;
    }

    setFieldValue(
        'payment-date',
        localToday()
    );

    setFieldValue(
        'payment-method',
        'bank_transfer'
    );

    setFieldValue(
        'owner-payment-purpose',
        'general_funding'
    );

    clearTenantSelection();

    clearOwnerSelection();

    resetOwnerBuildingSelector();

    resetOwnerUnitSelector();

    hidePaymentFormError();

    setSubmitting(
        false
    );

    updatePaymentSource();

    updateCollectorVisibility();
}

/*
|--------------------------------------------------------------------------
| Payment Source Switching
|--------------------------------------------------------------------------
*/

/**
 * Return the currently selected incoming-payment source.
 *
 * @returns {'tenant'|'owner'}
 */
function paymentSource() {
    const selected =
        document.querySelector(
            'input[name="payment_source"]:checked'
        );

    return selected?.value === 'owner'
        ? 'owner'
        : 'tenant';
}

/**
 * Show the applicable payment-source section.
 */
function updatePaymentSource() {
    const source =
        paymentSource();

    const tenantSection =
        document.getElementById(
            'tenant-payment-section'
        );

    const ownerSection =
        document.getElementById(
            'owner-payment-section'
        );

    if (source === 'owner') {
        tenantSection?.classList.add(
            'hidden'
        );

        ownerSection?.classList.remove(
            'hidden'
        );

        window.setTimeout(
            () => {
                document
                    .getElementById(
                        'owner-payment-search'
                    )
                    ?.focus();
            },
            0
        );

        return;
    }

    ownerSection?.classList.add(
        'hidden'
    );

    tenantSection?.classList.remove(
        'hidden'
    );

    window.setTimeout(
        () => {
            document
                .getElementById(
                    'tenant-payment-search'
                )
                ?.focus();
        },
        0
    );
}

/*
|--------------------------------------------------------------------------
| Tenant Search
|--------------------------------------------------------------------------
*/

/**
 * Initialize asynchronous Tenant search.
 */
function initializeTenantSearch() {
    const input =
        document.getElementById(
            'tenant-payment-search'
        );

    if (! input) {
        return;
    }

    input.addEventListener(
        'input',
        () => {
            /*
             * Typing after a Tenant has been selected means the user intends
             * to choose another Tenant.
             */
            if (selectedTenant) {
                clearTenantSelection(
                    false
                );
            }

            window.clearTimeout(
                tenantSearchTimer
            );

            const search =
                input.value.trim();

            if (search.length < 2) {
                hideSearchResults(
                    'tenant-payment-search-results'
                );

                return;
            }

            tenantSearchTimer =
                window.setTimeout(
                    async () => {
                        await searchTenants(
                            search
                        );
                    },
                    300
                );
        }
    );

    document
        .getElementById(
            'clear-tenant-payment-button'
        )
        ?.addEventListener(
            'click',
            () => {
                clearTenantSelection();

                input.focus();
            }
        );
}

/**
 * Search Parties having the Tenant role.
 *
 * @param {string} search
 */
async function searchTenants(
    search
) {
    const container =
        document.getElementById(
            'tenant-payment-search-results'
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
            ${escapeHtml(
                translate(
                    'payments.searching'
                )
            )}
        </div>
    `;

    container.classList.remove(
        'hidden'
    );

    try {
        const params =
            new URLSearchParams({
                role: 'tenant',
                search,
                per_page: '10',
            });

        const response =
            await apiRequest(
                `/api/parties?${params.toString()}`
            );

        const data =
            await parseJsonResponse(
                response
            );

        const parties =
            Array.isArray(
                data?.data
            )
                ? data.data
                : [];

        renderTenantSearchResults(
            parties
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
                        : translate(
                            'payments.unable_to_search_tenants'
                        )
                )}
            </div>
        `;
    }
}

/**
 * Render Tenant search results.
 *
 * @param {Array<object>} parties
 */
function renderTenantSearchResults(
    parties
) {
    const container =
        document.getElementById(
            'tenant-payment-search-results'
        );

    if (! container) {
        return;
    }

    if (parties.length === 0) {
        container.innerHTML = `
            <div
                class="
                    px-4 py-4
                    text-sm text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'payments.no_matching_tenants'
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
        parties
            .map(
                (party) => {
                    const name =
                        partyDisplayName(
                            party
                        );

                    const meta =
                        contactSummary(
                            party
                        );

                    return `
                        <button
                            type="button"
                            data-tenant-result-id="${escapeAttribute(
                                party.id
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
                                    text-sm font-medium
                                    text-slate-900
                                "
                            >
                                ${escapeHtml(name)}
                            </div>

                            ${
                                meta
                                    ? `
                                        <div
                                            class="
                                                mt-1 text-xs
                                                text-slate-500
                                            "
                                        >
                                            ${escapeHtml(meta)}
                                        </div>
                                    `
                                    : ''
                            }
                        </button>
                    `;
                }
            )
            .join('');

    container.classList.remove(
        'hidden'
    );

    parties.forEach(
        (party) => {
            container
                .querySelector(
                    `[data-tenant-result-id="${party.id}"]`
                )
                ?.addEventListener(
                    'click',
                    async () => {
                        await selectTenant(
                            party
                        );
                    }
                );
        }
    );
}

/**
 * Select one Tenant and load that Tenant's Lease history.
 *
 * Draft Leases are excluded because backend payment validation does not
 * permit payments against drafts.
 *
 * Terminated Leases remain available because legitimate arrears may still
 * be settled after termination.
 *
 * @param {object} party
 */
async function selectTenant(
    party
) {
    selectedTenant =
        party;

    setFieldValue(
        'tenant-payment-party-id',
        party.id
    );

    setFieldValue(
        'tenant-payment-search',
        partyDisplayName(
            party
        )
    );

    setText(
        'tenant-payment-selected-name',
        partyDisplayName(
            party
        )
    );

    setText(
        'tenant-payment-selected-meta',
        contactSummary(
            party
        )
    );

    document
        .getElementById(
            'tenant-payment-selected'
        )
        ?.classList.remove(
            'hidden'
        );

    hideSearchResults(
        'tenant-payment-search-results'
    );

    await loadTenantLeases(
        party.id
    );
}

/**
 * Load all non-draft Leases belonging to the selected Tenant.
 *
 * @param {number|string} tenantId
 */
async function loadTenantLeases(
    tenantId
) {
    const select =
        document.getElementById(
            'tenant-payment-lease'
        );

    if (! select) {
        return;
    }

    select.disabled = true;

    select.innerHTML = `
        <option value="">
            ${escapeHtml(
                translate(
                    'payments.loading_leases'
                )
            )}
        </option>
    `;

    try {
        const params =
            new URLSearchParams({
                tenant_id:
                    String(tenantId),

                per_page:
                    '100',
            });

        const response =
            await apiRequest(
                `/api/leases?${params.toString()}`
            );

        const data =
            await parseJsonResponse(
                response
            );

        const leases =
            (
                Array.isArray(
                    data?.data
                )
                    ? data.data
                    : []
            ).filter(
                (lease) =>
                    lease.status
                    !== 'draft'
            );

        if (leases.length === 0) {
            select.innerHTML = `
                <option value="">
                    ${escapeHtml(
                        translate(
                            'payments.no_payable_lease'
                        )
                    )}
                </option>
            `;

            select.disabled = true;

            setText(
                'tenant-payment-lease-help',
                translate(
                    'payments.no_payable_lease_help'
                )
            );

            return;
        }

        select.innerHTML = `
            <option value="">
                ${escapeHtml(
                    translate(
                        'payments.select_lease_property'
                    )
                )}
            </option>

            ${leases
                .map(
                    (lease) => `
                        <option
                            value="${escapeAttribute(
                                lease.id
                            )}"
                        >
                            ${escapeHtml(
                                leaseOptionLabel(
                                    lease
                                )
                            )}
                        </option>
                    `
                )
                .join('')}
        `;

        select.disabled = false;

        setText(
            'tenant-payment-lease-help',
            translate(
                'payments.lease_fifo_outstanding_help'
            )
        );

        /*
         * When only one Lease is applicable, selecting it automatically
         * avoids an unnecessary second user decision.
         */
        if (leases.length === 1) {
            select.value =
                String(
                    leases[0].id
                );
        }
    } catch (error) {
        select.innerHTML = `
            <option value="">
                ${escapeHtml(
                    translate(
                        'payments.unable_to_load_leases'
                    )
                )}
            </option>
        `;

        select.disabled = true;

        showPaymentFormError(
            error instanceof Error
                ? error.message
                : translate(
                    'payments.unable_to_load_tenant_leases'
                )
        );
    }
}

/**
 * Clear the selected Tenant.
 *
 * @param {boolean} clearSearch
 */
function clearTenantSelection(
    clearSearch = true
) {
    selectedTenant = null;

    setFieldValue(
        'tenant-payment-party-id',
        ''
    );

    if (clearSearch) {
        setFieldValue(
            'tenant-payment-search',
            ''
        );
    }

    document
        .getElementById(
            'tenant-payment-selected'
        )
        ?.classList.add(
            'hidden'
        );

    const leaseSelect =
        document.getElementById(
            'tenant-payment-lease'
        );

    if (leaseSelect) {
        leaseSelect.disabled = true;

        leaseSelect.innerHTML = `
            <option value="">
                ${escapeHtml(
                    translate(
                        'payments.search_select_tenant_first'
                    )
                )}
            </option>
        `;
    }

    setText(
        'tenant-payment-lease-help',
        translate(
            'payments.lease_fifo_help'
        )
    );
}

/*
|--------------------------------------------------------------------------
| Owner Search
|--------------------------------------------------------------------------
*/

/**
 * Initialize asynchronous OwnerAccount search.
 *
 * The user searches by Owner identity rather than choosing from a fixed
 * dropdown. OwnerAccounts have already been provisioned automatically when
 * Building ownership is created.
 */
function initializeOwnerSearch() {
    const input =
        document.getElementById(
            'owner-payment-search'
        );

    if (! input) {
        return;
    }

    input.addEventListener(
        'input',
        () => {
            if (selectedOwnerAccount) {
                clearOwnerSelection(
                    false
                );
            }

            window.clearTimeout(
                ownerSearchTimer
            );

            const search =
                input.value.trim();

            if (search.length < 2) {
                hideSearchResults(
                    'owner-payment-search-results'
                );

                return;
            }

            ownerSearchTimer =
                window.setTimeout(
                    async () => {
                        await searchOwners(
                            search
                        );
                    },
                    300
                );
        }
    );

    document
        .getElementById(
            'clear-owner-payment-button'
        )
        ?.addEventListener(
            'click',
            () => {
                clearOwnerSelection();

                input.focus();
            }
        );
}

/**
 * Search owner financial accounts by the associated Party.
 *
 * @param {string} search
 */
async function searchOwners(
    search
) {
    const container =
        document.getElementById(
            'owner-payment-search-results'
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
            ${escapeHtml(
                translate(
                    'payments.searching'
                )
            )}
        </div>
    `;

    container.classList.remove(
        'hidden'
    );

    try {
        const params =
            new URLSearchParams({
                search,
                per_page:
                    '10',
            });

        const response =
            await apiRequest(
                `/api/owner-accounts?${params.toString()}`
            );

        const data =
            await parseJsonResponse(
                response
            );

        const accounts =
            Array.isArray(
                data?.data
            )
                ? data.data
                : [];

        renderOwnerSearchResults(
            accounts
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
                        : translate(
                            'payments.unable_to_search_owners'
                        )
                )}
            </div>
        `;
    }
}

/**
 * Render searchable Owner results.
 *
 * @param {Array<object>} accounts
 */
function renderOwnerSearchResults(
    accounts
) {
    const container =
        document.getElementById(
            'owner-payment-search-results'
        );

    if (! container) {
        return;
    }

    if (accounts.length === 0) {
        container.innerHTML = `
            <div
                class="
                    px-4 py-4
                    text-sm text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'payments.no_matching_owners'
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
        accounts
            .map(
                (account) => {
                    const party =
                        account.party
                        ?? {};

                    return `
                        <button
                            type="button"
                            data-owner-account-result-id="${escapeAttribute(
                                account.id
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
                                <div>
                                    <div
                                        class="
                                            text-sm font-medium
                                            text-slate-900
                                        "
                                    >
                                        ${escapeHtml(
                                            partyDisplayName(
                                                party
                                            )
                                        )}
                                    </div>

                                    <div
                                        class="
                                            mt-1 text-xs
                                            text-slate-500
                                        "
                                    >
                                        ${escapeHtml(
                                            contactSummary(
                                                party
                                            )
                                        )}
                                    </div>
                                </div>

                                <div
                                    class="
                                        shrink-0 text-xs
                                        font-medium text-slate-600
                                    "
                                >
                                    ${escapeHtml(
                                        formatCurrency(
                                            account.balance
                                            ?? 0
                                        )
                                    )}
                                </div>
                            </div>
                        </button>
                    `;
                }
            )
            .join('');

    container.classList.remove(
        'hidden'
    );

    accounts.forEach(
        (account) => {
            container
                .querySelector(
                    `[data-owner-account-result-id="${account.id}"]`
                )
                ?.addEventListener(
                    'click',
                    async () => {
                        await selectOwner(
                            account
                        );
                    }
                );
        }
    );
}

/**
 * Select an OwnerAccount and retrieve the Buildings actually owned by
 * the associated Party.
 *
 * No Lease lookup occurs here.
 *
 * @param {object} account
 */
async function selectOwner(
    account
) {
    hidePaymentFormError();

    try {
        const response =
            await apiRequest(
                `/api/owner-accounts/${account.id}`
            );

        const fullAccount =
            await parseJsonResponse(
                response
            );

        selectedOwnerAccount =
            fullAccount;

        selectedOwnerProperties =
            Array.isArray(
                fullAccount.properties
            )
                ? fullAccount.properties
                : [];

        setFieldValue(
            'owner-payment-account-id',
            fullAccount.id
        );

        setFieldValue(
            'owner-payment-search',
            partyDisplayName(
                fullAccount.party
                ?? {}
            )
        );

        setText(
            'owner-payment-selected-name',
            partyDisplayName(
                fullAccount.party
                ?? {}
            )
        );

        setText(
            'owner-payment-selected-meta',
            contactSummary(
                fullAccount.party
                ?? {}
            )
        );

        setText(
            'owner-payment-selected-balance',
            formatCurrency(
                fullAccount.balance
                ?? 0
            )
        );

        document
            .getElementById(
                'owner-payment-selected'
            )
            ?.classList.remove(
                'hidden'
            );

        hideSearchResults(
            'owner-payment-search-results'
        );

        populateOwnerBuildings();
    } catch (error) {
        showPaymentFormError(
            error instanceof Error
                ? error.message
                : translate(
                    'payments.unable_to_load_owner'
                )
        );
    }
}

/**
 * Clear current Owner selection.
 *
 * @param {boolean} clearSearch
 */
function clearOwnerSelection(
    clearSearch = true
) {
    selectedOwnerAccount = null;

    selectedOwnerProperties = [];

    setFieldValue(
        'owner-payment-account-id',
        ''
    );

    if (clearSearch) {
        setFieldValue(
            'owner-payment-search',
            ''
        );
    }

    document
        .getElementById(
            'owner-payment-selected'
        )
        ?.classList.add(
            'hidden'
        );

    resetOwnerBuildingSelector();

    resetOwnerUnitSelector();
}

/*
|--------------------------------------------------------------------------
| Owner Property Context
|--------------------------------------------------------------------------
*/

/**
 * Initialize Building → Unit dependency.
 */
function initializeOwnerPropertySelectors() {
    document
        .getElementById(
            'owner-payment-building'
        )
        ?.addEventListener(
            'change',
            () => {
                populateOwnerUnits();
            }
        );
}

/**
 * Populate the Building selector from actual ownership records.
 *
 * This list has no relationship to active Leases.
 */
function populateOwnerBuildings() {
    const select =
        document.getElementById(
            'owner-payment-building'
        );

    if (! select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            ${escapeHtml(
                translate(
                    'payments.no_specific_building'
                )
            )}
        </option>
    `;

    selectedOwnerProperties.forEach(
        (property) => {
            const building =
                property.building;

            if (! building) {
                return;
            }

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                String(
                    building.id
                );

            option.textContent =
                building.name
                ?? translate(
                    'payments.building_number',
                    {
                        id:
                            building.id,
                    }
                );

            select.appendChild(
                option
            );
        }
    );

    resetOwnerUnitSelector();
}

/**
 * Populate Units belonging to the selected Owner Building.
 */
function populateOwnerUnits() {
    const buildingId =
        fieldValue(
            'owner-payment-building'
        );

    const unitSelect =
        document.getElementById(
            'owner-payment-unit'
        );

    if (! unitSelect) {
        return;
    }

    if (! buildingId) {
        resetOwnerUnitSelector();

        return;
    }

    const property =
        selectedOwnerProperties
            .find(
                (item) =>
                    String(
                        item?.building?.id
                        ?? ''
                    ) === buildingId
            );

    const units =
        Array.isArray(
            property?.building?.units
        )
            ? property.building.units
            : [];

    unitSelect.innerHTML = `
        <option value="">
            ${escapeHtml(
                translate(
                    'payments.no_specific_unit'
                )
            )}
        </option>
    `;

    units.forEach(
        (unit) => {
            const option =
                document.createElement(
                    'option'
                );

            option.value =
                String(
                    unit.id
                );

            option.textContent =
                unit.name
                ?? translate(
                    'payments.unit_number',
                    {
                        id:
                            unit.id,
                    }
                );

            unitSelect.appendChild(
                option
            );
        }
    );

    /*
     * A Building may legitimately have no Units yet. Building-level owner
     * funding must still remain possible.
     */
    unitSelect.disabled =
        false;
}

/**
 * Reset Building selector.
 */
function resetOwnerBuildingSelector() {
    const select =
        document.getElementById(
            'owner-payment-building'
        );

    if (! select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            ${escapeHtml(
                translate(
                    'payments.no_specific_building'
                )
            )}
        </option>
    `;
}

/**
 * Reset Unit selector.
 */
function resetOwnerUnitSelector() {
    const select =
        document.getElementById(
            'owner-payment-unit'
        );

    if (! select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            ${escapeHtml(
                translate(
                    'payments.select_building_first'
                )
            )}
        </option>
    `;

    select.disabled =
        true;
}

/*
|--------------------------------------------------------------------------
| Cash Collector
|--------------------------------------------------------------------------
*/

/**
 * Show the collector field only for Cash payments.
 */
function updateCollectorVisibility() {
    const method =
        fieldValue(
            'payment-method'
        );

    const wrapper =
        document.getElementById(
            'payment-collector-wrapper'
        );

    const input =
        document.getElementById(
            'payment-collector'
        );

    if (! wrapper) {
        return;
    }

    if (method === 'cash') {
        wrapper.classList.remove(
            'hidden'
        );

        if (input) {
            input.required = true;
        }

        return;
    }

    wrapper.classList.add(
        'hidden'
    );

    if (input) {
        input.required = false;

        input.value = '';
    }
}

/*
|--------------------------------------------------------------------------
| Payment Submission
|--------------------------------------------------------------------------
*/

/**
 * Submit either:
 *
 * - POST /api/payments
 * - POST /api/owner-accounts/{id}/deposits
 *
 * depending on the selected payment source.
 */
async function submitPayment() {
    hidePaymentFormError();

    const source =
        paymentSource();

    const amount =
        Number(
            fieldValue(
                'payment-amount'
            )
        );

    const paymentDate =
        fieldValue(
            'payment-date'
        );

    const method =
        fieldValue(
            'payment-method'
        );

    const reference =
        fieldValue(
            'payment-reference'
        );

    const collector =
        fieldValue(
            'payment-collector'
        );

    const notes =
        fieldValue(
            'payment-notes'
        );

    if (
        ! Number.isInteger(amount)
        || amount <= 0
    ) {
        showPaymentFormError(
            translate('payments.validation_amount')
        );

        return;
    }

    if (! paymentDate) {
        showPaymentFormError(
            translate('payments.validation_date')
        );

        return;
    }

    if (
        ! [
            'cash',
            'bank_transfer',
            'momo',
        ].includes(
            method
        )
    ) {
        showPaymentFormError(
            translate('payments.validation_method')
        );

        return;
    }

    if (
        method === 'cash'
        && collector === ''
    ) {
        showPaymentFormError(
            translate('payments.validation_collector')
        );

        return;
    }

    setSubmitting(
        true
    );

    try {
        let receiptEndpoint = null;

        if (source === 'tenant') {
            receiptEndpoint =
                await submitTenantPayment({
                    amount,
                    paymentDate,
                    method,
                    reference,
                    collector,
                    notes,
                });
        } else {
            receiptEndpoint =
                await submitOwnerDeposit({
                    amount,
                    paymentDate,
                    method,
                    reference,
                    collector,
                    notes,
                });
        }

        closePaymentModal();

        currentPage = 1;

        await loadPaymentRegister(
            1
        );

        /*
         * A receipt is produced for every successful incoming payment.
         *
         * Open it immediately after recording. It remains available from
         * the Payment Register afterwards through the Receipt button.
         */
        if (receiptEndpoint) {
            await openAuthenticatedPdf(
                receiptEndpoint
            );
        }
    } catch (error) {
        showPaymentFormError(
            error instanceof Error
                ? error.message
                : translate(
                    'payments.unable_to_record'
                )
        );
    } finally {
        setSubmitting(
            false
        );
    }
}

/**
 * Record money received from a Tenant.
 *
 * @param {object} details
 * @returns {Promise<string>}
 */
async function submitTenantPayment(
    details
) {
    const leaseId =
        fieldValue(
            'tenant-payment-lease'
        );

    if (! selectedTenant) {
        throw new Error(
            translate(
                'payments.select_tenant_required'
            )
        );
    }

    if (! leaseId) {
        throw new Error(
            translate(
                'payments.select_lease_required'
            )
        );
    }

    const payload = {
        lease_id:
            Number(leaseId),

        amount:
            details.amount,

        payment_date:
            details.paymentDate,

        payment_method:
            details.method,

        reference:
            details.reference
            || null,

        collector_name:
            details.method === 'cash'
                ? details.collector
                : null,

        notes:
            details.notes
            || null,
    };

    const response =
        await apiRequest(
            '/api/payments',
            {
                method:
                    'POST',

                body:
                    JSON.stringify(
                        payload
                    ),
            }
        );

    const payment =
        await parseJsonResponse(
            response
        );

    if (! payment?.id) {
        throw new Error(
            translate(
                'payments.payment_receipt_unresolved'
            )
        );
    }

    return `/api/payments/${payment.id}/receipt`;
}

/**
 * Record actual money supplied by an Owner.
 *
 * Owner deposits do not require:
 *
 * - a Tenant;
 * - an active Lease;
 * - historical rent collections;
 * - a positive OwnerAccount balance.
 *
 * A Building and Unit are optional contextual references.
 *
 * @param {object} details
 * @returns {Promise<string>}
 */
async function submitOwnerDeposit(
    details
) {
    const accountId =
        fieldValue(
            'owner-payment-account-id'
        );

    if (
        ! selectedOwnerAccount
        || ! accountId
    ) {
        throw new Error(
            translate(
                'payments.select_owner_required'
            )
        );
    }

    const purpose =
        fieldValue(
            'owner-payment-purpose'
        );

    const buildingId =
        fieldValue(
            'owner-payment-building'
        );

    const unitId =
        fieldValue(
            'owner-payment-unit'
        );

    const payload = {
        amount:
            details.amount,

        transaction_date:
            details.paymentDate,

        payment_method:
            details.method,

        deposit_purpose:
            purpose,

        building_id:
            buildingId
                ? Number(buildingId)
                : null,

        unit_id:
            unitId
                ? Number(unitId)
                : null,

        reference:
            details.reference
            || null,

        collector_name:
            details.method === 'cash'
                ? details.collector
                : null,

        notes:
            details.notes
            || null,
    };

    const response =
        await apiRequest(
            `/api/owner-accounts/${accountId}/deposits`,
            {
                method:
                    'POST',

                body:
                    JSON.stringify(
                        payload
                    ),
            }
        );

    const data =
        await parseJsonResponse(
            response
        );

    const transactionId =
        data?.transaction?.id;

    if (! transactionId) {
        throw new Error(
            translate(
                'payments.owner_receipt_unresolved'
            )
        );
    }

    return `/api/owner-deposits/${transactionId}/receipt`;
}

/**
 * Enable / disable the submit action while the request is running.
 *
 * @param {boolean} submitting
 */
function setSubmitting(
    submitting
) {
    const button =
        document.getElementById(
            'submit-payment-button'
        );

    if (! button) {
        return;
    }

    button.disabled =
        submitting;

    button.textContent =
        submitting
            ? translate(
                'payments.recording'
            )
            : translate(
                'payments.record_payment'
            );
}

/*
|--------------------------------------------------------------------------
| Labels and Formatting
|--------------------------------------------------------------------------
*/

function paymentMethodLabel(
    method
) {
    switch (method) {
        case 'cash':
            return translate(
                'payments.cash'
            );

        case 'bank_transfer':
            return translate(
                'payments.bank_transfer'
            );

        case 'momo':
            return translate(
                'payments.momo'
            );

        default:
            return String(
                method
            ).replaceAll(
                '_',
                ' '
            );
    }
}

function depositPurposeLabel(
    purpose
) {
    switch (purpose) {
        case 'general_funding':
            return translate(
                'payments.general_funding'
            );

        case 'property_expense':
            return translate(
                'payments.property_expense'
            );

        case 'repair_maintenance':
            return translate(
                'payments.repair_maintenance'
            );

        case 'other':
            return translate(
                'payments.other'
            );

        default:
            return String(
                purpose
            ).replaceAll(
                '_',
                ' '
            );
    }
}

/**
 * Human-readable Party name.
 *
 * @param {object} party
 * @returns {string}
 */
function partyDisplayName(
    party
) {
    return party?.name
        || party?.legal_name
        || translate(
            'payments.unnamed_party'
        );
}

/**
 * Build concise Party contact information.
 *
 * @param {object} party
 * @returns {string}
 */
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

/**
 * Human-readable Lease option.
 *
 * @param {object} lease
 * @returns {string}
 */
function leaseOptionLabel(
    lease
) {
    const building =
        lease?.unit?.building?.name
        ?? translate(
            'payments.property'
        );

    const unit =
        lease?.unit?.name
        ?? translate(
            'payments.unit_number',
            {
                id:
                    lease?.unit_id
                    ?? '',
            }
        );

    const status =
        String(
            lease?.status
            ?? ''
        );

    const statusLabel =
        status
            ? leaseStatusLabel(
                status
            )
            : '';

    const startDate =
        formatDate(
            lease?.start_date
        );

    return [
        `${building} / ${unit}`,
        statusLabel,
        startDate
            ? translate(
                'payments.from_date',
                {
                    date:
                        startDate,
                }
            )
            : '',
    ]
        .filter(Boolean)
        .join(' — ');
}




/**
 * Human-readable localized Lease status.
 *
 * @param {string} status
 * @returns {string}
 */
function leaseStatusLabel(
    status
) {
    switch (status) {
        case 'draft':
            return translate(
                'payments.status_draft'
            );

        case 'active':
            return translate(
                'payments.status_active'
            );

        case 'notice':
            return translate(
                'payments.status_notice'
            );

        case 'terminated':
            return translate(
                'payments.status_terminated'
            );

        default:
            return String(
                status
            ).replaceAll(
                '_',
                ' '
            );
    }
}

/**
 * Return today's date in local browser time as YYYY-MM-DD.
 *
 * Using local date parts avoids UTC offset changes around midnight.
 *
 * @returns {string}
 */
function localToday() {
    const today =
        new Date();

    const year =
        today.getFullYear();

    const month =
        String(
            today.getMonth()
            + 1
        ).padStart(
            2,
            '0'
        );

    const day =
        String(
            today.getDate()
        ).padStart(
            2,
            '0'
        );

    return `${year}-${month}-${day}`;
}

/*
|--------------------------------------------------------------------------
| Form Error
|--------------------------------------------------------------------------
*/

function showPaymentFormError(
    message
) {
    const element =
        document.getElementById(
            'payment-form-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        message;

    element.classList.remove(
        'hidden'
    );

    element.scrollIntoView({
        behavior:
            'smooth',

        block:
            'nearest',
    });
}

function hidePaymentFormError() {
    const element =
        document.getElementById(
            'payment-form-error'
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
| Search Helpers
|--------------------------------------------------------------------------
*/

function hideSearchResults(
    id
) {
    const element =
        document.getElementById(
            id
        );

    if (! element) {
        return;
    }

    element.innerHTML = '';

    element.classList.add(
        'hidden'
    );
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

function showLoadingState() {
    const container =
        document.getElementById(
            'payments-list'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                py-8 text-center
                text-sm text-slate-400
            "
        >
            ${escapeHtml(
                translate(
                    'payments.loading'
                )
            )}
        </div>
    `;
}

function renderEmptyRegister(
    message
) {
    const container =
        document.getElementById(
            'payments-list'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                py-10 text-center
                text-sm text-slate-500
            "
        >
            ${escapeHtml(message)}
        </div>
    `;
}

function showPaymentError(
    message
) {
    const element =
        document.getElementById(
            'payments-error'
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

function hidePaymentError() {
    const element =
        document.getElementById(
            'payments-error'
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

/*
|--------------------------------------------------------------------------
| Tenant Fund Classification
|--------------------------------------------------------------------------
|
| Unapplied tenant Payment money is not automatically treated as Advance,
| Rent Reserve or Security Deposit.
|
| The Property Manager must explicitly classify it. All availability
| calculations are reloaded from the API before and after each operation so
| browser state can never become the accounting authority.
|
*/

let tenantFundPaymentId =
    null;

/**
 * Listen for Manage Funds actions rendered in the unified Payment Register.
 *
 * Event delegation is used because register rows are replaced after filters,
 * pagination and successful financial operations.
 */
function initializeTenantFundActions() {
    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '[data-manage-tenant-funds]'
                );

            if (! button) {
                return;
            }

            await openTenantFundModal(
                button.dataset.paymentId
            );
        }
    );
}

/**
 * Initialize modal lifecycle and allocation submission.
 */
function initializeTenantFundModal() {
    document
        .getElementById(
            'close-tenant-fund-modal-button'
        )
        ?.addEventListener(
            'click',
            closeTenantFundModal
        );

    document
        .getElementById(
            'tenant-fund-form'
        )
        ?.addEventListener(
            'submit',
            submitTenantFundAllocation
        );

    document.addEventListener(
        'keydown',
        (event) => {
            const modal =
                document.getElementById(
                    'tenant-fund-modal'
                );

            if (
                event.key === 'Escape'
                && modal
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closeTenantFundModal();
            }
        }
    );
}

/**
 * Open Manage Funds for one Tenant Payment.
 */
async function openTenantFundModal(
    paymentId
) {
    const numericPaymentId =
        Number(
            paymentId
        );

    if (
        ! Number.isInteger(
            numericPaymentId
        )
        || numericPaymentId <= 0
    ) {
        return;
    }

    tenantFundPaymentId =
        numericPaymentId;

    resetTenantFundModal();

    const modal =
        document.getElementById(
            'tenant-fund-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.remove(
        'hidden'
    );

    modal.classList.add(
        'flex'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    await loadTenantFundPayment();
}

/**
 * Close the Manage Funds modal.
 */
function closeTenantFundModal() {
    const modal =
        document.getElementById(
            'tenant-fund-modal'
        );

    modal?.classList.add(
        'hidden'
    );

    modal?.classList.remove(
        'flex'
    );

    modal?.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    tenantFundPaymentId =
        null;

    resetTenantFundModal();
}

/**
 * Restore modal loading and form state.
 */
function resetTenantFundModal() {
    hideTenantFundError();

    document
        .getElementById(
            'tenant-fund-loading'
        )
        ?.classList.remove(
            'hidden'
        );

    document
        .getElementById(
            'tenant-fund-content'
        )
        ?.classList.add(
            'hidden'
        );

    document
        .getElementById(
            'tenant-fund-complete-message'
        )
        ?.classList.add(
            'hidden'
        );

    const form =
        document.getElementById(
            'tenant-fund-form'
        );

    form?.reset();

    form?.classList.add(
        'hidden'
    );
}

/**
 * Reload the authoritative Payment classification position.
 */
async function loadTenantFundPayment() {
    if (! tenantFundPaymentId) {
        return;
    }

    try {
        hideTenantFundError();

        const response =
            await apiRequest(
                `/api/payments/${tenantFundPaymentId}`
            );

        const payment =
            await parseJsonResponse(
                response
            );

        renderTenantFundPayment(
            payment
        );
    } catch (error) {
        showTenantFundError(
            error instanceof Error
                ? error.message
                : translate(
                    'payments.unable_to_load_funds'
                )
        );
    } finally {
        document
            .getElementById(
                'tenant-fund-loading'
            )
            ?.classList.add(
                'hidden'
            );

        document
            .getElementById(
                'tenant-fund-content'
            )
            ?.classList.remove(
                'hidden'
            );
    }
}

/**
 * Render Payment amounts and available classification capacity.
 */
function renderTenantFundPayment(
    payment
) {
    const amount =
        Number(
            payment?.amount
            ?? 0
        );

    const allocated =
        Number(
            payment?.allocated_amount
            ?? 0
        );

    const unallocated =
        Number(
            payment?.unallocated_amount
            ?? 0
        );

    const classified =
        Number(
            payment?.classified_fund_amount
            ?? 0
        );

    const remaining =
        Number(
            payment?.remaining_unclassified_amount
            ?? 0
        );

    setText(
        'tenant-fund-payment-amount',
        formatCurrency(
            amount
        )
    );

    setText(
        'tenant-fund-allocated',
        formatCurrency(
            allocated
        )
    );

    setText(
        'tenant-fund-unallocated',
        formatCurrency(
            unallocated
        )
    );

    setText(
        'tenant-fund-classified',
        formatCurrency(
            classified
        )
    );

    setText(
        'tenant-fund-remaining',
        formatCurrency(
            remaining
        )
    );

    const property =
        [
            payment?.lease?.unit?.building?.name,
            payment?.lease?.unit?.name,
        ]
            .filter(Boolean)
            .join(' / ');

    const tenant =
        payment?.lease?.tenant?.name
        || payment?.lease?.tenant?.legal_name
        || translate(
            'payments.tenant'
        );

    setText(
        'tenant-fund-modal-description',
        property
            ? `${tenant} · ${property}`
            : tenant
    );

    const form =
        document.getElementById(
            'tenant-fund-form'
        );

    const complete =
        document.getElementById(
            'tenant-fund-complete-message'
        );

    form?.classList.add(
        'hidden'
    );

    complete?.classList.add(
        'hidden'
    );

    if (remaining <= 0) {
        complete?.classList.remove(
            'hidden'
        );

        return;
    }

    form?.classList.remove(
        'hidden'
    );

    const amountInput =
        document.getElementById(
            'tenant-fund-amount'
        );

    if (amountInput) {
        amountInput.max =
            String(
                remaining
            );
    }

    setText(
        'tenant-fund-amount-help',
        translate(
            'payments.maximum_available',
            {
                amount:
                    formatCurrency(
                        remaining
                    ),
            }
        )
    );

    /*
     * Payment classification normally occurs on the original Payment date.
     *
     * The operator may still deliberately choose another valid transaction
     * date if needed for historical reconstruction.
     */
    const dateInput =
        document.getElementById(
            'tenant-fund-date'
        );

    if (
        dateInput
        && ! dateInput.value
    ) {
        dateInput.value =
            String(
                payment?.payment_date
                ?? ''
            ).slice(
                0,
                10
            );
    }

    const reference =
        document.getElementById(
            'tenant-fund-reference'
        );

    if (
        reference
        && ! reference.value
        && payment?.reference
    ) {
        reference.value =
            payment.reference;
    }
}

/**
 * Classify unapplied Payment money into one tenant-held fund.
 */
async function submitTenantFundAllocation(
    event
) {
    event.preventDefault();

    if (! tenantFundPaymentId) {
        return;
    }

    const fundType =
        fieldValue(
            'tenant-fund-type'
        );

    const amount =
        Number(
            fieldValue(
                'tenant-fund-amount'
            )
        );

    const transactionDate =
        fieldValue(
            'tenant-fund-date'
        );

    const reference =
        fieldValue(
            'tenant-fund-reference'
        );

    const notes =
        fieldValue(
            'tenant-fund-notes'
        );

    const button =
        document.getElementById(
            'tenant-fund-submit-button'
        );

    try {
        hideTenantFundError();

        if (button) {
            button.disabled =
                true;

            button.textContent =
                'Allocating…';
        }

        const response =
            await apiRequest(
                `/api/payments/${tenantFundPaymentId}/tenant-funds`,
                {
                    method:
                        'POST',

                    headers: {
                        'Content-Type':
                            'application/json',
                    },

                    body:
                        JSON.stringify({
                            fund_type:
                                fundType,

                            amount,

                            transaction_date:
                                transactionDate,

                            reference:
                                reference || null,

                            notes:
                                notes || null,
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        /*
         * Reset only allocation-specific fields. Keep the Payment modal open
         * and reload its authoritative financial position from the backend.
         */
        document
            .getElementById(
                'tenant-fund-form'
            )
            ?.reset();

        await loadTenantFundPayment();

        /*
         * Refresh the unified register so the workspace reflects any new
         * operational activity immediately.
         */
        await loadPaymentRegister(
            currentPage
        );
    } catch (error) {
        showTenantFundError(
            error instanceof Error
                ? error.message
                : translate(
                    'payments.unable_to_classify_funds'
                )
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                translate(
                    'payments.allocate_funds'
                );
        }
    }
}

/**
 * Display Manage Funds workflow errors.
 */
function showTenantFundError(
    message
) {
    const box =
        document.getElementById(
            'tenant-fund-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );
}

/**
 * Clear Manage Funds workflow errors.
 */
function hideTenantFundError() {
    const box =
        document.getElementById(
            'tenant-fund-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}
