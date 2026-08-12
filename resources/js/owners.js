import {
    apiRequest,
    formatCurrency,
    parseJsonResponse,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Patrimoine Owners Workspace
|--------------------------------------------------------------------------
|
| The Owners screen provides an operational financial view of each property
| owner.
|
| Owner identity comes from Party.
|
| Property ownership comes from BuildingOwner.
|
| Financial activity comes from the consolidated OwnerAccount ledger.
|
| These domains remain separate even though this screen presents them as one
| coherent workspace.
|
*/

let ownerSearchTimer = null;

let ownerListPage = 1;

let selectedOwnerAccountId = null;

let selectedOwner = null;

/*
|--------------------------------------------------------------------------
| Initialization
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Owners page when its directory exists.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeOwners() {
    const list =
        document.getElementById(
            'owners-list'
        );

    if (! list) {
        return false;
    }

    initializeOwnerDirectorySearch();

    initializeOwnerWorkspaceActions();

    await loadOwnerDirectory();

    return true;
}

/*
|--------------------------------------------------------------------------
| Owner Directory
|--------------------------------------------------------------------------
*/

/**
 * Initialize the owner search field.
 */
function initializeOwnerDirectorySearch() {
    const input =
        document.getElementById(
            'owners-search'
        );

    if (! input) {
        return;
    }

    input.addEventListener(
        'input',
        () => {
            window.clearTimeout(
                ownerSearchTimer
            );

            ownerListPage = 1;

            ownerSearchTimer =
                window.setTimeout(
                    async () => {
                        await loadOwnerDirectory(
                            1
                        );
                    },
                    300
                );
        }
    );
}

/**
 * Load the searchable OwnerAccount directory.
 *
 * @param {number} page
 */
async function loadOwnerDirectory(
    page = ownerListPage
) {
    ownerListPage = page;

    hideOwnersError();

    showOwnerDirectoryLoading();

    try {
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

        const search =
            fieldValue(
                'owners-search'
            );

        if (search) {
            params.set(
                'search',
                search
            );
        }

        const response =
            await apiRequest(
                `/api/owner-accounts?${params.toString()}`
            );

        const pagination =
            await parseJsonResponse(
                response
            );

        renderOwnerDirectory(
            pagination
        );

        renderOwnerDirectoryPagination(
            pagination
        );

        /*
         * On initial page load, select the first owner automatically.
         *
         * Once the user has deliberately selected an OwnerAccount, preserve
         * that selection during searches and pagination whenever possible.
         */
        const accounts =
            Array.isArray(
                pagination?.data
            )
                ? pagination.data
                : [];

        if (
            accounts.length > 0
            && selectedOwnerAccountId === null
        ) {
            await selectOwnerAccount(
                accounts[0].id
            );
        }
    } catch (error) {
        showOwnersError(
            error instanceof Error
                ? error.message
                : 'Unable to load Property Owners.'
        );

        renderOwnerDirectoryEmpty(
            'Unable to load Property Owners.'
        );
    }
}

/**
 * Render OwnerAccount directory cards.
 *
 * @param {object} pagination
 */
function renderOwnerDirectory(
    pagination
) {
    const container =
        document.getElementById(
            'owners-list'
        );

    if (! container) {
        return;
    }

    const accounts =
        Array.isArray(
            pagination?.data
        )
            ? pagination.data
            : [];

    if (accounts.length === 0) {
        renderOwnerDirectoryEmpty(
            'No Property Owners match your search.'
        );

        return;
    }

    container.innerHTML =
        accounts
            .map(
                renderOwnerDirectoryRow
            )
            .join('');

    accounts.forEach(
        (account) => {
            container
                .querySelector(
                    `[data-owner-account-id="${account.id}"]`
                )
                ?.addEventListener(
                    'click',
                    async () => {
                        await selectOwnerAccount(
                            account.id
                        );
                    }
                );
        }
    );
}

/**
 * Render one owner in the directory.
 *
 * @param {object} account
 * @returns {string}
 */
function renderOwnerDirectoryRow(
    account
) {
    const party =
        account.party
        ?? {};

    const selected =
        String(
            selectedOwnerAccountId
            ?? ''
        ) === String(
            account.id
        );

    const balance =
        Number(
            account.balance
            ?? 0
        );

    const balanceClass =
        balance < 0
            ? 'text-red-700'
            : 'text-slate-900';

    return `
        <button
            type="button"
            data-owner-account-id="${escapeAttribute(
                account.id
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
                    flex items-start
                    justify-between gap-4
                "
            >
                <div class="min-w-0">
                    <div
                        class="
                            truncate text-sm font-semibold
                            text-slate-900
                        "
                    >
                        ${escapeHtml(
                            partyDisplayName(
                                party
                            )
                        )}
                    </div>

                    ${
                        contactSummary(
                            party
                        )
                            ? `
                                <div
                                    class="
                                        mt-1 truncate
                                        text-xs text-slate-500
                                    "
                                >
                                    ${escapeHtml(
                                        contactSummary(
                                            party
                                        )
                                    )}
                                </div>
                            `
                            : ''
                    }

                    <div
                        class="
                            mt-2 text-xs
                            text-slate-400
                        "
                    >
                        ${Number(
                            account.property_count
                            ?? 0
                        ).toLocaleString()}
                        ${
                            Number(
                                account.property_count
                                ?? 0
                            ) === 1
                                ? 'property'
                                : 'properties'
                        }
                    </div>
                </div>

                <div
                    class="
                        shrink-0 text-right
                    "
                >
                    <div
                        class="
                            text-sm font-semibold
                            ${balanceClass}
                        "
                    >
                        ${escapeHtml(
                            formatCurrency(
                                balance
                            )
                        )}
                    </div>

                    <div
                        class="
                            mt-1 text-[11px]
                            text-slate-400
                        "
                    >
                        balance
                    </div>
                </div>
            </div>
        </button>
    `;
}

/*
|--------------------------------------------------------------------------
| Directory Pagination
|--------------------------------------------------------------------------
*/

/**
 * Render Owner directory pagination.
 *
 * @param {object} pagination
 */
function renderOwnerDirectoryPagination(
    pagination
) {
    const container =
        document.getElementById(
            'owners-list-pagination'
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
                owner${total === 1 ? '' : 's'}
            </div>

            <div class="flex gap-2">
                <button
                    id="owners-list-previous"
                    type="button"
                    ${current <= 1 ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-2.5 py-1.5
                        text-xs font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    Previous
                </button>

                <button
                    id="owners-list-next"
                    type="button"
                    ${current >= last ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-2.5 py-1.5
                        text-xs font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
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
            'owners-list-previous'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (current > 1) {
                    await loadOwnerDirectory(
                        current - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'owners-list-next'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (current < last) {
                    await loadOwnerDirectory(
                        current + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Owner Selection
|--------------------------------------------------------------------------
*/

/**
 * Select and load one OwnerAccount.
 *
 * @param {number|string} accountId
 * @param {number} transactionPage
 */
async function selectOwnerAccount(
    accountId,
    transactionPage = 1
) {
    selectedOwnerAccountId =
        Number(
            accountId
        );

    hideOwnersError();

    showOwnerDetailLoading();

    /*
     * Refresh the visible directory selection immediately.
     */
    document
        .querySelectorAll(
            '[data-owner-account-id]'
        )
        .forEach(
            (button) => {
                const selected =
                    String(
                        button.dataset
                            .ownerAccountId
                    ) === String(
                        selectedOwnerAccountId
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

    try {
        const params =
            new URLSearchParams({
                transactions_page:
                    String(
                        transactionPage
                    ),

                transactions_per_page:
                    '25',
            });

        const response =
            await apiRequest(
                `/api/owner-accounts/${selectedOwnerAccountId}?${params.toString()}`
            );

        selectedOwner =
            await parseJsonResponse(
                response
            );

        renderOwnerDetail(
            selectedOwner
        );
    } catch (error) {
        selectedOwner = null;

        showOwnersError(
            error instanceof Error
                ? error.message
                : 'Unable to load Owner details.'
        );

        showOwnerDetailEmpty(
            'Unable to load this Property Owner.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Owner Detail
|--------------------------------------------------------------------------
*/

/**
 * Render the complete OwnerAccount view.
 *
 * @param {object} owner
 */
function renderOwnerDetail(
    owner
) {
    const empty =
        document.getElementById(
            'owner-detail-empty'
        );

    const content =
        document.getElementById(
            'owner-detail-content'
        );

    empty?.classList.add(
        'hidden'
    );

    content?.classList.remove(
        'hidden'
    );

    const party =
        owner.party
        ?? {};

    setText(
        'owner-detail-name',
        partyDisplayName(
            party
        )
    );

    setText(
        'owner-detail-contact',
        contactSummary(
            party
        )
        || 'No contact information available.'
    );

    renderOwnerStatus(
        owner.status
    );

    setText(
        'owner-detail-balance',
        formatCurrency(
            owner.balance
            ?? 0
        )
    );

    /*
    * Payouts are only possible while the Owner has a positive available
    * balance. Backend validation remains authoritative, but disabling the
    * button avoids presenting an impossible operation.
    */
    const payoutButton =
        document.getElementById(
            'owner-record-payout-button'
        );

    if (payoutButton) {
        const availableBalance =
            Number(
                owner.balance
                ?? 0
            );

        payoutButton.disabled =
            availableBalance <= 0;

        payoutButton.title =
            availableBalance <= 0
                ? 'This Owner has no funds available for payout.'
                : '';
    }

    setText(
        'owner-detail-credits',
        formatCurrency(
            owner.credited_amount
            ?? 0
        )
    );

    setText(
        'owner-detail-debits',
        formatCurrency(
            owner.debited_amount
            ?? 0
        )
    );

    const properties =
        Array.isArray(
            owner.properties
        )
            ? owner.properties
            : [];

    setText(
        'owner-detail-property-count',
        properties.length
            .toLocaleString()
    );

    /*
     * Existing reporting infrastructure already supports formal owner PDF
     * reports using Party rather than OwnerAccount identity.
     */
    const reportLink =
        document.getElementById(
            'owner-report-link'
        );

    if (reportLink) {
        reportLink.href =
            `/api/reports/owners/${owner.party_id}/pdf`;

        reportLink.dataset
            .ownerReportEndpoint =
            reportLink.href;
    }

    renderOwnerProperties(
        properties
    );

    renderOwnerLedger(
        owner.transactions
    );

    renderOwnerLedgerPagination(
        owner.transactions
    );

    renderOwnerPayouts(
        owner.payouts
    );
}

/**
 * Update owner status badge.
 *
 * @param {string|null} status
 */
function renderOwnerStatus(
    status
) {
    const badge =
        document.getElementById(
            'owner-detail-status'
        );

    if (! badge) {
        return;
    }

    const active =
        status === 'active';

    badge.textContent =
        active
            ? 'Active'
            : capitalize(
                status
                ?? 'Unknown'
            );

    badge.className = active
        ? `
            inline-flex items-center
            rounded-full
            bg-emerald-50
            px-2.5 py-1
            text-xs font-medium
            text-emerald-700
        `
        : `
            inline-flex items-center
            rounded-full
            bg-slate-100
            px-2.5 py-1
            text-xs font-medium
            text-slate-600
        `;
}

/*
|--------------------------------------------------------------------------
| Properties
|--------------------------------------------------------------------------
*/

/**
 * Render all Buildings owned by the selected Party.
 *
 * Lease activity is deliberately irrelevant here.
 *
 * @param {Array<object>} properties
 */
function renderOwnerProperties(
    properties
) {
    const container =
        document.getElementById(
            'owner-properties-list'
        );

    if (! container) {
        return;
    }

    if (properties.length === 0) {
        container.innerHTML = `
            <div
                class="
                    col-span-full rounded-xl
                    border border-dashed
                    border-slate-200
                    px-5 py-8
                    text-center
                    text-sm text-slate-500
                "
            >
                No Building ownership records found.
            </div>
        `;

        return;
    }

    container.innerHTML =
        properties
            .map(
                renderOwnerProperty
            )
            .join('');
}

/**
 * Render one Building ownership card.
 *
 * @param {object} property
 * @returns {string}
 */
function renderOwnerProperty(
    property
) {
    const building =
        property.building
        ?? {};

    const units =
        Array.isArray(
            building.units
        )
            ? building.units
            : [];

    const address =
        [
            building.location,
            building.address,
        ]
            .filter(Boolean)
            .join(' · ');

    return `
        <article
            class="
                rounded-xl border
                border-slate-200
                bg-white p-4
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
                            text-sm font-semibold
                            text-slate-950
                        "
                    >
                        ${escapeHtml(
                            building.name
                            ?? `Building #${building.id ?? ''}`
                        )}
                    </div>

                    ${
                        address
                            ? `
                                <div
                                    class="
                                        mt-1 text-xs
                                        text-slate-500
                                    "
                                >
                                    ${escapeHtml(
                                        address
                                    )}
                                </div>
                            `
                            : ''
                    }
                </div>

                <span
                    class="
                        shrink-0 rounded-full
                        bg-slate-100
                        px-2.5 py-1
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    ${escapeHtml(
                        property.ownership_percentage
                        ?? '0.00'
                    )}%
                </span>
            </div>

            <div
                class="
                    mt-4 border-t
                    border-slate-100 pt-3
                "
            >
                <div
                    class="
                        text-[11px] font-semibold
                        uppercase tracking-wide
                        text-slate-400
                    "
                >
                    Units
                </div>

                ${
                    units.length > 0
                        ? `
                            <div
                                class="
                                    mt-2 flex flex-wrap
                                    gap-2
                                "
                            >
                                ${units
                                    .map(
                                        (unit) => `
                                            <span
                                                class="
                                                    rounded-lg
                                                    bg-slate-50
                                                    px-2.5 py-1.5
                                                    text-xs
                                                    text-slate-600
                                                "
                                            >
                                                ${escapeHtml(
                                                    unit.name
                                                    ?? `Unit #${unit.id}`
                                                )}
                                            </span>
                                        `
                                    )
                                    .join('')}
                            </div>
                        `
                        : `
                            <div
                                class="
                                    mt-2 text-xs
                                    text-slate-400
                                "
                            >
                                No Units have been created yet.
                            </div>
                        `
                }
            </div>
        </article>
    `;
}

/*
|--------------------------------------------------------------------------
| Owner Ledger
|--------------------------------------------------------------------------
*/

/**
 * Render the selected owner's ledger page.
 *
 * @param {object} pagination
 */
function renderOwnerLedger(
    pagination
) {
    const container =
        document.getElementById(
            'owner-ledger-list'
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
        container.innerHTML = `
            <div
                class="
                    rounded-xl border
                    border-dashed border-slate-200
                    px-5 py-8 text-center
                    text-sm text-slate-500
                "
            >
                No owner financial transactions have been recorded.
            </div>
        `;

        return;
    }

    container.innerHTML =
        transactions
            .map(
                renderOwnerTransaction
            )
            .join('');
}

/**
 * Render one OwnerTransaction.
 *
 * @param {object} transaction
 * @returns {string}
 */
function renderOwnerTransaction(
    transaction
) {
    const credit =
        transaction.direction
        === 'credit';

    const amountPrefix =
        credit
            ? '+'
            : '−';

    const amountClass =
        credit
            ? 'text-emerald-700'
            : 'text-red-700';

    const property =
        transactionPropertyLabel(
            transaction
        );

    const receipt =
        transaction.receipt_endpoint
            ? `
                <button
                    type="button"
                    data-owner-receipt-endpoint="${escapeAttribute(
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
                    Receipt
                </button>
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
                        <span
                            class="
                                inline-flex items-center
                                rounded-full
                                ${
                                    credit
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-red-50 text-red-700'
                                }
                                px-2.5 py-1
                                text-xs font-medium
                            "
                        >
                            ${
                                credit
                                    ? 'Credit'
                                    : 'Debit'
                            }
                        </span>

                        <span
                            class="
                                text-sm font-semibold
                                text-slate-900
                            "
                        >
                            ${escapeHtml(
                                ownerTransactionCategoryLabel(
                                    transaction.category
                                )
                            )}
                        </span>
                    </div>

                    <div
                        class="
                            mt-2 flex flex-wrap
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
                            property
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            property
                                        )}
                                    </span>
                                `
                                : ''
                        }

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
                                        Ref:
                                        ${escapeHtml(
                                            transaction.reference
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            transaction.deposit_purpose
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            depositPurposeLabel(
                                                transaction.deposit_purpose
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            transaction.collector_name
                                ? `
                                    <span>
                                        Collector:
                                        ${escapeHtml(
                                            transaction.collector_name
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            transaction.invoice_id
                                ? `
                                    <span>
                                        Invoice #${escapeHtml(
                                            transaction.invoice_id
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
                                        text-xs leading-5
                                        text-slate-500
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
                            ${amountClass}
                        "
                    >
                        ${amountPrefix}${escapeHtml(
                            formatCurrency(
                                transaction.amount
                                ?? 0
                            )
                        )}
                    </div>

                    ${receipt}
                </div>
            </div>
        </article>
    `;
}

/*
|--------------------------------------------------------------------------
| Ledger Pagination
|--------------------------------------------------------------------------
*/

/**
 * Render transaction history pagination.
 *
 * @param {object} pagination
 */
function renderOwnerLedgerPagination(
    pagination
) {
    const container =
        document.getElementById(
            'owner-ledger-pagination'
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
                Page ${current} of ${last}
                ·
                ${total.toLocaleString()}
                transaction${total === 1 ? '' : 's'}
            </div>

            <div class="flex gap-2">
                <button
                    id="owner-ledger-previous"
                    type="button"
                    ${current <= 1 ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    Previous
                </button>

                <button
                    id="owner-ledger-next"
                    type="button"
                    ${current >= last ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        transition
                        hover:bg-slate-50
                        disabled:cursor-not-allowed
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
            'owner-ledger-previous'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (
                    current > 1
                    && selectedOwnerAccountId
                ) {
                    await selectOwnerAccount(
                        selectedOwnerAccountId,
                        current - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'owner-ledger-next'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (
                    current < last
                    && selectedOwnerAccountId
                ) {
                    await selectOwnerAccount(
                        selectedOwnerAccountId,
                        current + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Payout History
|--------------------------------------------------------------------------
*/

/**
 * Render owner payouts.
 *
 * @param {Array<object>} payouts
 */
function renderOwnerPayouts(
    payouts
) {
    const container =
        document.getElementById(
            'owner-payouts-list'
        );

    if (! container) {
        return;
    }

    const entries =
        Array.isArray(
            payouts
        )
            ? payouts
            : [];

    if (entries.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-xl border
                    border-dashed border-slate-200
                    px-5 py-8 text-center
                    text-sm text-slate-500
                "
            >
                No payouts have been recorded for this Owner.
            </div>
        `;

        return;
    }

    container.innerHTML = `
        <div
            class="
                overflow-hidden rounded-xl
                border border-slate-200
            "
        >
            ${entries
                .map(
                    (payout) => `
                        <div
                            class="
                                flex flex-col gap-3
                                border-b border-slate-100
                                px-4 py-3
                                last:border-b-0
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
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
                                        formatDate(
                                            payout.payout_date
                                        )
                                    )}
                                </div>

                                <div
                                    class="
                                        mt-1 flex flex-wrap
                                        gap-x-4 gap-y-1
                                        text-xs text-slate-500
                                    "
                                >
                                    ${
                                        payout.payment_method
                                            ? `
                                                <span>
                                                    ${escapeHtml(
                                                        paymentMethodLabel(
                                                            payout.payment_method
                                                        )
                                                    )}
                                                </span>
                                            `
                                            : ''
                                    }

                                    ${
                                        payout.reference
                                            ? `
                                                <span>
                                                    Ref:
                                                    ${escapeHtml(
                                                        payout.reference
                                                    )}
                                                </span>
                                            `
                                            : ''
                                    }
                                </div>

                                ${
                                    payout.notes
                                        ? `
                                            <div
                                                class="
                                                    mt-2 text-xs
                                                    text-slate-500
                                                "
                                            >
                                                ${escapeHtml(
                                                    payout.notes
                                                )}
                                            </div>
                                        `
                                        : ''
                                }
                            </div>

                            <div
                                class="
                                    shrink-0 text-base
                                    font-semibold
                                    text-slate-900
                                "
                            >
                                ${escapeHtml(
                                    formatCurrency(
                                        payout.amount
                                        ?? 0
                                    )
                                )}
                            </div>
                        </div>
                    `
                )
                .join('')}
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| Workspace Actions
|--------------------------------------------------------------------------
*/

/**
 * Initialize Owner financial actions and document actions.
 */
function initializeOwnerWorkspaceActions() {
    /*
     * Existing dynamically rendered Receipt and Report actions.
     */
    document.addEventListener(
        'click',
        async (event) => {
            const receiptButton =
                event.target.closest(
                    '[data-owner-receipt-endpoint]'
                );

            if (receiptButton) {
                const endpoint =
                    receiptButton.dataset
                        .ownerReceiptEndpoint;

                if (endpoint) {
                    await openAuthenticatedPdf(
                        endpoint
                    );
                }

                return;
            }

            const reportLink =
                event.target.closest(
                    '[data-owner-report-endpoint]'
                );

            if (reportLink) {
                event.preventDefault();

                const endpoint =
                    reportLink.dataset
                        .ownerReportEndpoint;

                if (endpoint) {
                    await openAuthenticatedPdf(
                        endpoint
                    );
                }
            }
        }
    );

    /*
     * Owner operation buttons.
     */
    document
        .getElementById(
            'owner-record-deposit-button'
        )
        ?.addEventListener(
            'click',
            openOwnerDepositModal
        );

    document
        .getElementById(
            'owner-record-expense-button'
        )
        ?.addEventListener(
            'click',
            openOwnerExpenseModal
        );

    document
        .getElementById(
            'owner-record-payout-button'
        )
        ?.addEventListener(
            'click',
            openOwnerPayoutModal
        );

    document
        .getElementById(
            'owner-record-adjustment-button'
        )
        ?.addEventListener(
            'click',
            openOwnerAdjustmentModal
        );

    /*
     * Generic modal close buttons.
     */
    document
        .querySelectorAll(
            '[data-close-owner-modal]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        closeOwnerModal(
                            button.dataset
                                .closeOwnerModal
                        );
                    }
                );
            }
        );

    /*
     * Clicking outside a modal closes it.
     */
    [
        'owner-deposit-modal',
        'owner-expense-modal',
        'owner-payout-modal',
        'owner-adjustment-modal',
    ].forEach(
        (id) => {
            const modal =
                document.getElementById(
                    id
                );

            modal?.addEventListener(
                'click',
                (event) => {
                    if (event.target === modal) {
                        closeOwnerModal(
                            id
                        );
                    }
                }
            );
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            [
                'owner-deposit-modal',
                'owner-expense-modal',
                'owner-payout-modal',
                'owner-adjustment-modal',
            ].forEach(
                (id) => {
                    const modal =
                        document.getElementById(
                            id
                        );

                    if (
                        modal
                        && ! modal.classList.contains(
                            'hidden'
                        )
                    ) {
                        closeOwnerModal(
                            id
                        );
                    }
                }
            );
        }
    );

    /*
     * Building → Unit dependencies.
     */
    document
        .getElementById(
            'owner-deposit-building'
        )
        ?.addEventListener(
            'change',
            () => {
                populateOwnerActionUnits(
                    'owner-deposit'
                );
            }
        );

    document
        .getElementById(
            'owner-expense-building'
        )
        ?.addEventListener(
            'change',
            () => {
                populateOwnerActionUnits(
                    'owner-expense'
                );

                updateExpenseOwnershipWarning();
            }
        );

    /*
     * Deposit cash collector.
     */
    document
        .getElementById(
            'owner-deposit-method'
        )
        ?.addEventListener(
            'change',
            updateOwnerDepositCollector
        );

    /*
     * Form submission.
     */
    document
        .getElementById(
            'owner-deposit-form'
        )
        ?.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                await submitOwnerDeposit();
            }
        );

    document
        .getElementById(
            'owner-expense-form'
        )
        ?.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                await submitOwnerExpense();
            }
        );

    document
        .getElementById(
            'owner-payout-form'
        )
        ?.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                await submitOwnerPayout();
            }
        );

    document
        .getElementById(
            'owner-adjustment-form'
        )
        ?.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                await submitOwnerAdjustment();
            }
        );
}

/*
|--------------------------------------------------------------------------
| Modal Helpers
|--------------------------------------------------------------------------
*/

function openOwnerModal(
    id
) {
    const modal =
        document.getElementById(
            id
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
}

function closeOwnerModal(
    id
) {
    if (! id) {
        return;
    }

    const modal =
        document.getElementById(
            id
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
}

/**
 * Ensure an Owner is actually selected before opening a financial action.
 *
 * @returns {boolean}
 */
function hasSelectedOwner() {
    if (
        ! selectedOwner
        || ! selectedOwnerAccountId
    ) {
        showOwnersError(
            'Select a Property Owner first.'
        );

        return false;
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| Owner Property Selectors
|--------------------------------------------------------------------------
*/

/**
 * Populate a Building selector using only properties actually owned by the
 * currently selected Owner Party.
 *
 * @param {string} prefix
 * @param {boolean} optional
 */
function populateOwnerActionBuildings(
    prefix,
    optional
) {
    const select =
        document.getElementById(
            `${prefix}-building`
        );

    if (! select) {
        return;
    }

    const properties =
        Array.isArray(
            selectedOwner?.properties
        )
            ? selectedOwner.properties
            : [];

    select.innerHTML =
        optional
            ? `
                <option value="">
                    No specific Building
                </option>
            `
            : `
                <option value="">
                    Select Building
                </option>
            `;

    properties.forEach(
        (property) => {
            const building =
                property?.building;

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
                ?? `Building #${building.id}`;

            select.appendChild(
                option
            );
        }
    );

    resetOwnerActionUnits(
        prefix
    );
}

/**
 * Populate Unit selector from the selected Owner-owned Building.
 *
 * @param {string} prefix
 */
function populateOwnerActionUnits(
    prefix
) {
    const buildingId =
        fieldValue(
            `${prefix}-building`
        );

    const unitSelect =
        document.getElementById(
            `${prefix}-unit`
        );

    if (! unitSelect) {
        return;
    }

    if (! buildingId) {
        resetOwnerActionUnits(
            prefix
        );

        return;
    }

    const properties =
        Array.isArray(
            selectedOwner?.properties
        )
            ? selectedOwner.properties
            : [];

    const property =
        properties.find(
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
            No specific Unit
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
                ?? `Unit #${unit.id}`;

            unitSelect.appendChild(
                option
            );
        }
    );

    unitSelect.disabled =
        false;
}

function resetOwnerActionUnits(
    prefix
) {
    const select =
        document.getElementById(
            `${prefix}-unit`
        );

    if (! select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            Select a Building first
        </option>
    `;

    select.disabled =
        true;
}

/*
|--------------------------------------------------------------------------
| Owner Deposit
|--------------------------------------------------------------------------
*/

function openOwnerDepositModal() {
    if (! hasSelectedOwner()) {
        return;
    }

    const form =
        document.getElementById(
            'owner-deposit-form'
        );

    form?.reset();

    hideOwnerActionError(
        'owner-deposit-error'
    );

    setFieldValue(
        'owner-deposit-date',
        localToday()
    );

    setFieldValue(
        'owner-deposit-method',
        'bank_transfer'
    );

    setFieldValue(
        'owner-deposit-purpose',
        'general_funding'
    );

    populateOwnerActionBuildings(
        'owner-deposit',
        true
    );

    updateOwnerDepositCollector();

    openOwnerModal(
        'owner-deposit-modal'
    );

    document
        .getElementById(
            'owner-deposit-amount'
        )
        ?.focus();
}

function updateOwnerDepositCollector() {
    const method =
        fieldValue(
            'owner-deposit-method'
        );

    const wrapper =
        document.getElementById(
            'owner-deposit-collector-wrapper'
        );

    const input =
        document.getElementById(
            'owner-deposit-collector'
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

async function submitOwnerDeposit() {
    if (! hasSelectedOwner()) {
        return;
    }

    hideOwnerActionError(
        'owner-deposit-error'
    );

    const amount =
        Number(
            fieldValue(
                'owner-deposit-amount'
            )
        );

    const method =
        fieldValue(
            'owner-deposit-method'
        );

    const collector =
        fieldValue(
            'owner-deposit-collector'
        );

    if (
        ! Number.isInteger(amount)
        || amount <= 0
    ) {
        showOwnerActionError(
            'owner-deposit-error',
            'Enter a valid deposit amount greater than zero.'
        );

        return;
    }

    if (
        method === 'cash'
        && ! collector
    ) {
        showOwnerActionError(
            'owner-deposit-error',
            'Collector is required for cash deposits.'
        );

        return;
    }

    const buildingId =
        fieldValue(
            'owner-deposit-building'
        );

    const unitId =
        fieldValue(
            'owner-deposit-unit'
        );

    const payload = {
        amount,

        transaction_date:
            fieldValue(
                'owner-deposit-date'
            ),

        payment_method:
            method,

        deposit_purpose:
            fieldValue(
                'owner-deposit-purpose'
            ),

        building_id:
            buildingId
                ? Number(buildingId)
                : null,

        unit_id:
            unitId
                ? Number(unitId)
                : null,

        reference:
            fieldValue(
                'owner-deposit-reference'
            )
            || null,

        collector_name:
            method === 'cash'
                ? collector
                : null,

        notes:
            fieldValue(
                'owner-deposit-notes'
            )
            || null,
    };

    setOwnerActionSubmitting(
        'owner-deposit-submit',
        true,
        'Recording…',
        'Record Deposit'
    );

    try {
        const response =
            await apiRequest(
                `/api/owner-accounts/${selectedOwnerAccountId}/deposits`,
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

        closeOwnerModal(
            'owner-deposit-modal'
        );

        await refreshSelectedOwner();

        if (transactionId) {
            await openAuthenticatedPdf(
                `/api/owner-deposits/${transactionId}/receipt`
            );
        }
    } catch (error) {
        showOwnerActionError(
            'owner-deposit-error',
            error instanceof Error
                ? error.message
                : 'Unable to record Owner deposit.'
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-deposit-submit',
            false,
            'Recording…',
            'Record Deposit'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Owner Expense
|--------------------------------------------------------------------------
*/

function openOwnerExpenseModal() {
    if (! hasSelectedOwner()) {
        return;
    }

    const properties =
        Array.isArray(
            selectedOwner?.properties
        )
            ? selectedOwner.properties
            : [];

    if (properties.length === 0) {
        showOwnersError(
            'This Owner has no Building ownership against which an expense can be recorded.'
        );

        return;
    }

    document
        .getElementById(
            'owner-expense-form'
        )
        ?.reset();

    hideOwnerActionError(
        'owner-expense-error'
    );

    hideExpenseOwnershipWarning();

    setFieldValue(
        'owner-expense-date',
        localToday()
    );

    populateOwnerActionBuildings(
        'owner-expense',
        false
    );

    openOwnerModal(
        'owner-expense-modal'
    );
}

/**
 * Warn when the selected Owner owns less than the complete Building.
 *
 * OwnerAccountingService will allocate the full expense among all Building
 * owners according to their ownership percentages.
 */
function updateExpenseOwnershipWarning() {
    const buildingId =
        fieldValue(
            'owner-expense-building'
        );

    const warning =
        document.getElementById(
            'owner-expense-sharing-warning'
        );

    if (
        ! warning
        || ! buildingId
    ) {
        hideExpenseOwnershipWarning();

        return;
    }

    const properties =
        Array.isArray(
            selectedOwner?.properties
        )
            ? selectedOwner.properties
            : [];

    const property =
        properties.find(
            (item) =>
                String(
                    item?.building?.id
                    ?? ''
                ) === buildingId
        );

    const percentage =
        Number(
            property?.ownership_percentage
            ?? 0
        );

    if (percentage >= 100) {
        hideExpenseOwnershipWarning();

        return;
    }

    warning.textContent =
        `This Owner holds ${percentage.toFixed(2)}% of this Building. `
        + 'Patrimoine will allocate the full expense across all Building '
        + 'owners according to their ownership percentages.';

    warning.classList.remove(
        'hidden'
    );
}

function hideExpenseOwnershipWarning() {
    const warning =
        document.getElementById(
            'owner-expense-sharing-warning'
        );

    if (! warning) {
        return;
    }

    warning.textContent = '';

    warning.classList.add(
        'hidden'
    );
}

async function submitOwnerExpense() {
    if (! hasSelectedOwner()) {
        return;
    }

    hideOwnerActionError(
        'owner-expense-error'
    );

    const buildingId =
        fieldValue(
            'owner-expense-building'
        );

    const unitId =
        fieldValue(
            'owner-expense-unit'
        );

    const amount =
        Number(
            fieldValue(
                'owner-expense-amount'
            )
        );

    if (! buildingId) {
        showOwnerActionError(
            'owner-expense-error',
            'Select the Building against which the expense was incurred.'
        );

        return;
    }

    if (
        ! Number.isInteger(amount)
        || amount <= 0
    ) {
        showOwnerActionError(
            'owner-expense-error',
            'Enter a valid expense amount greater than zero.'
        );

        return;
    }

    const description =
        fieldValue(
            'owner-expense-description'
        );

    if (! description) {
        showOwnerActionError(
            'owner-expense-error',
            'Expense description is required.'
        );

        return;
    }

    const payload = {
        building_id:
            Number(buildingId),

        unit_id:
            unitId
                ? Number(unitId)
                : null,

        description,

        amount,

        expense_date:
            fieldValue(
                'owner-expense-date'
            ),

        reference:
            fieldValue(
                'owner-expense-reference'
            )
            || null,

        notes:
            fieldValue(
                'owner-expense-notes'
            )
            || null,
    };

    setOwnerActionSubmitting(
        'owner-expense-submit',
        true,
        'Recording…',
        'Record Expense'
    );

    try {
        const response =
            await apiRequest(
                '/api/owner-expenses',
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closeOwnerModal(
            'owner-expense-modal'
        );

        await refreshSelectedOwner();
    } catch (error) {
        showOwnerActionError(
            'owner-expense-error',
            error instanceof Error
                ? error.message
                : 'Unable to record Owner expense.'
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-expense-submit',
            false,
            'Recording…',
            'Record Expense'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Owner Payout
|--------------------------------------------------------------------------
*/

function openOwnerPayoutModal() {
    if (! hasSelectedOwner()) {
        return;
    }

    const balance =
        Number(
            selectedOwner?.balance
            ?? 0
        );

    if (balance <= 0) {
        showOwnersError(
            'This Owner does not currently have funds available for payout.'
        );

        return;
    }

    document
        .getElementById(
            'owner-payout-form'
        )
        ?.reset();

    hideOwnerActionError(
        'owner-payout-error'
    );

    setFieldValue(
        'owner-payout-date',
        localToday()
    );

    setFieldValue(
        'owner-payout-method',
        'bank_transfer'
    );

    setText(
        'owner-payout-available-balance',
        formatCurrency(
            balance
        )
    );

    const amountInput =
        document.getElementById(
            'owner-payout-amount'
        );

    if (amountInput) {
        amountInput.max =
            String(balance);
    }

    openOwnerModal(
        'owner-payout-modal'
    );

    amountInput?.focus();
}

async function submitOwnerPayout() {
    if (! hasSelectedOwner()) {
        return;
    }

    hideOwnerActionError(
        'owner-payout-error'
    );

    const amount =
        Number(
            fieldValue(
                'owner-payout-amount'
            )
        );

    const balance =
        Number(
            selectedOwner?.balance
            ?? 0
        );

    if (
        ! Number.isInteger(amount)
        || amount <= 0
    ) {
        showOwnerActionError(
            'owner-payout-error',
            'Enter a valid payout amount greater than zero.'
        );

        return;
    }

    if (amount > balance) {
        showOwnerActionError(
            'owner-payout-error',
            `Payout cannot exceed the available Owner balance of ${formatCurrency(balance)}.`
        );

        return;
    }

    const payload = {
        amount,

        payout_date:
            fieldValue(
                'owner-payout-date'
            ),

        payment_method:
            fieldValue(
                'owner-payout-method'
            ),

        reference:
            fieldValue(
                'owner-payout-reference'
            )
            || null,

        notes:
            fieldValue(
                'owner-payout-notes'
            )
            || null,
    };

    setOwnerActionSubmitting(
        'owner-payout-submit',
        true,
        'Processing…',
        'Make Payout'
    );

    try {
        const response =
            await apiRequest(
                `/api/owner-accounts/${selectedOwnerAccountId}/payouts`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closeOwnerModal(
            'owner-payout-modal'
        );

        await refreshSelectedOwner();
    } catch (error) {
        showOwnerActionError(
            'owner-payout-error',
            error instanceof Error
                ? error.message
                : 'Unable to create Owner payout.'
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-payout-submit',
            false,
            'Processing…',
            'Make Payout'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Owner Adjustment
|--------------------------------------------------------------------------
*/

function openOwnerAdjustmentModal() {
    if (! hasSelectedOwner()) {
        return;
    }

    document
        .getElementById(
            'owner-adjustment-form'
        )
        ?.reset();

    hideOwnerActionError(
        'owner-adjustment-error'
    );

    setFieldValue(
        'owner-adjustment-direction',
        'credit'
    );

    setFieldValue(
        'owner-adjustment-date',
        localToday()
    );

    openOwnerModal(
        'owner-adjustment-modal'
    );
}

async function submitOwnerAdjustment() {
    if (! hasSelectedOwner()) {
        return;
    }

    hideOwnerActionError(
        'owner-adjustment-error'
    );

    const amount =
        Number(
            fieldValue(
                'owner-adjustment-amount'
            )
        );

    const reason =
        fieldValue(
            'owner-adjustment-reason'
        );

    if (
        ! Number.isInteger(amount)
        || amount <= 0
    ) {
        showOwnerActionError(
            'owner-adjustment-error',
            'Enter a valid adjustment amount greater than zero.'
        );

        return;
    }

    if (! reason) {
        showOwnerActionError(
            'owner-adjustment-error',
            'An audit reason is required for every manual adjustment.'
        );

        return;
    }

    const payload = {
        direction:
            fieldValue(
                'owner-adjustment-direction'
            ),

        amount,

        transaction_date:
            fieldValue(
                'owner-adjustment-date'
            ),

        reason,

        reference:
            fieldValue(
                'owner-adjustment-reference'
            )
            || null,
    };

    setOwnerActionSubmitting(
        'owner-adjustment-submit',
        true,
        'Recording…',
        'Record Adjustment'
    );

    try {
        const response =
            await apiRequest(
                `/api/owner-accounts/${selectedOwnerAccountId}/adjustments`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closeOwnerModal(
            'owner-adjustment-modal'
        );

        await refreshSelectedOwner();
    } catch (error) {
        showOwnerActionError(
            'owner-adjustment-error',
            error instanceof Error
                ? error.message
                : 'Unable to record Owner adjustment.'
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-adjustment-submit',
            false,
            'Recording…',
            'Record Adjustment'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Refresh Owner Workspace
|--------------------------------------------------------------------------
*/

/**
 * Refresh both:
 *
 * - detailed OwnerAccount financial information;
 * - Owner directory balance displayed on the left.
 *
 * The selected Owner remains selected.
 */
async function refreshSelectedOwner() {
    if (! selectedOwnerAccountId) {
        return;
    }

    await selectOwnerAccount(
        selectedOwnerAccountId,
        1
    );

    await loadOwnerDirectory(
        ownerListPage
    );
}

/*
|--------------------------------------------------------------------------
| Action Errors / Submit State
|--------------------------------------------------------------------------
*/

function showOwnerActionError(
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

    element.scrollIntoView({
        behavior:
            'smooth',

        block:
            'nearest',
    });
}

function hideOwnerActionError(
    id
) {
    const element =
        document.getElementById(
            id
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}

function setOwnerActionSubmitting(
    buttonId,
    submitting,
    submittingLabel,
    normalLabel
) {
    const button =
        document.getElementById(
            buttonId
        );

    if (! button) {
        return;
    }

    button.disabled =
        submitting;

    button.textContent =
        submitting
            ? submittingLabel
            : normalLabel;
}

/*
|--------------------------------------------------------------------------
| Authenticated Documents
|--------------------------------------------------------------------------
*/

/**
 * Fetch and open an authenticated PDF document.
 *
 * @param {string} endpoint
 */
async function openAuthenticatedPdf(
    endpoint
) {
    hideOwnersError();

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
                'Unable to open document.'
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
        showOwnersError(
            error instanceof Error
                ? error.message
                : 'Unable to open document.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Labels
|--------------------------------------------------------------------------
*/

function ownerTransactionCategoryLabel(
    category
) {
    switch (category) {
        case 'owner_deposit':
            return 'Owner Deposit';

        case 'rent_entitlement':
            return 'Rent Collected';

        case 'expense':
            return 'Property Expense';

        case 'management_fee':
            return 'Management Fee';

        case 'agent_commission':
            return 'Agent Commission';

        case 'adjustment':
            return 'Adjustment';

        case 'payout':
            return 'Owner Payout';

        default:
            return capitalizeWords(
                category
                ?? 'Transaction'
            );
    }
}

function paymentMethodLabel(
    method
) {
    switch (method) {
        case 'cash':
            return 'Cash';

        case 'bank_transfer':
            return 'Bank Transfer';

        case 'momo':
            return 'MoMo';

        default:
            return capitalizeWords(
                method
                ?? ''
            );
    }
}

function depositPurposeLabel(
    purpose
) {
    switch (purpose) {
        case 'general_funding':
            return 'General Funding';

        case 'property_expense':
            return 'Property Expense';

        case 'repair_maintenance':
            return 'Repair / Maintenance';

        case 'other':
            return 'Other';

        default:
            return capitalizeWords(
                purpose
                ?? ''
            );
    }
}

/**
 * Build Building / Unit context for one owner transaction.
 *
 * @param {object} transaction
 * @returns {string}
 */
function transactionPropertyLabel(
    transaction
) {
    const building =
        transaction?.building?.name;

    const unit =
        transaction?.unit?.name;

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

    return '';
}

/*
|--------------------------------------------------------------------------
| Party Helpers
|--------------------------------------------------------------------------
*/

function partyDisplayName(
    party
) {
    return party?.name
        || party?.legal_name
        || 'Unnamed Owner';
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

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

/**
 * Return today's local browser date as YYYY-MM-DD.
 *
 * Local date parts are used deliberately so the date does not shift because
 * of UTC conversion around midnight.
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
            today.getMonth() + 1
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


/**
 * Format a database date without UTC shifting.
 *
 * @param {string|null} value
 * @returns {string}
 */
function formatDate(
    value
) {
    if (! value) {
        return '';
    }

    const parts =
        String(value)
            .slice(
                0,
                10
            )
            .split('-');

    if (parts.length !== 3) {
        return String(value);
    }

    const date =
        new Date(
            Number(parts[0]),
            Number(parts[1]) - 1,
            Number(parts[2])
        );

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(value);
    }

    return new Intl.DateTimeFormat(
        'en-GB',
        {
            day:
                '2-digit',

            month:
                'short',

            year:
                'numeric',
        }
    ).format(
        date
    );
}

function capitalize(
    value
) {
    const text =
        String(
            value
            ?? ''
        );

    return text.length > 0
        ? (
            text.charAt(0).toUpperCase()
            + text.slice(1)
        )
        : '';
}

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
            capitalize
        )
        .join(' ');
}

/*
|--------------------------------------------------------------------------
| Loading / Empty States
|--------------------------------------------------------------------------
*/

function showOwnerDirectoryLoading() {
    const container =
        document.getElementById(
            'owners-list'
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
            Loading owners…
        </div>
    `;
}

function renderOwnerDirectoryEmpty(
    message
) {
    const container =
        document.getElementById(
            'owners-list'
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

function showOwnerDetailLoading() {
    const empty =
        document.getElementById(
            'owner-detail-empty'
        );

    const content =
        document.getElementById(
            'owner-detail-content'
        );

    content?.classList.add(
        'hidden'
    );

    if (! empty) {
        return;
    }

    empty.classList.remove(
        'hidden'
    );

    empty.innerHTML = `
        <div
            class="
                text-center text-sm
                text-slate-400
            "
        >
            Loading Owner details…
        </div>
    `;
}

function showOwnerDetailEmpty(
    message
) {
    const empty =
        document.getElementById(
            'owner-detail-empty'
        );

    const content =
        document.getElementById(
            'owner-detail-content'
        );

    content?.classList.add(
        'hidden'
    );

    if (! empty) {
        return;
    }

    empty.classList.remove(
        'hidden'
    );

    empty.innerHTML = `
        <div
            class="
                max-w-md text-center
            "
        >
            <div
                class="
                    text-sm text-slate-500
                "
            >
                ${escapeHtml(message)}
            </div>
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showOwnersError(
    message
) {
    const element =
        document.getElementById(
            'owners-error'
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

function hideOwnersError() {
    const element =
        document.getElementById(
            'owners-error'
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
| DOM Helpers
|--------------------------------------------------------------------------
*/

/**
 * Return the trimmed value of a form field.
 *
 * @param {string} id
 * @returns {string}
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

/**
 * Set the value of a form field when it exists.
 *
 * @param {string} id
 * @param {string|number|null} value
 */
function setFieldValue(
    id,
    value
) {
    const element =
        document.getElementById(
            id
        );

    if (! element) {
        return;
    }

    element.value =
        value
        ?? '';
}

/**
 * Set the text content of an element when it exists.
 *
 * @param {string} id
 * @param {string|number|null} value
 */
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
