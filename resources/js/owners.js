import {
    apiRequest,
    formatCurrency,
    formatDate,
    formatNumber,
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

    synchronizeOwnerCurrencyDisplays();
    initializeOwnerDateInputs();

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
            '10'
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
                : translate('owners.unable_to_load')
        );

        renderOwnerDirectoryEmpty(
            translate('owners.unable_to_load')
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
            translate('owners.no_search_results')
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
                        ${formatNumber(
                            Number(
                                account.property_count
                                ?? 0
                            )
                        )}
                        ${
                            Number(
                                account.property_count
                                ?? 0
                            ) === 1
                                ? translate(
                                    'owners.property'
                                )
                                : translate(
                                    'owners.properties_lower'
                                )
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
                        ${translate(
                            'owners.balance'
                        )}
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
                ${translate(
                    total === 1
                        ? 'owners.pagination_owner'
                        : 'owners.pagination_owners',
                    {
                        total:
                            formatNumber(
                                total
                            ),
                    }
                )}
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
                    ${translate(
                        'owners.previous'
                    )}
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
                    ${translate(
                        'owners.next'
                    )}
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
                    '8',
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
                : translate('owners.unable_to_load_details')
        );

        showOwnerDetailEmpty(
            translate('owners.unable_to_load_owner')
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
        || translate('owners.no_contact_information')
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
                ? translate(
                    'owners.no_funds_available'
                )
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
        formatNumber(
            properties.length
        )
    );

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
            ? translate(
                'owners.active'
            )
            : (
                status
                    ? capitalize(
                        status
                    )
                    : translate(
                        'owners.unknown'
                    )
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
                ${translate(
                    'owners.no_building_ownership'
                )}
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
                            ?? `${translate(
                                'owners.building'
                            )} #${building.id ?? ''}`
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
                    ${translate(
                        'owners.units'
                    )}
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
                                                    ?? `${translate(
                                                        'owners.unit'
                                                    )} #${unit.id}`
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
                                ${translate(
                                    'owners.no_units_created'
                                )}
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
                ${translate(
                    'owners.no_transactions'
                )}
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
                    ${translate(
                        'owners.receipt'
                    )}
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
                                    ? translate(
                                        'owners.credit'
                                    )
                                    : translate(
                                        'owners.debit'
                                    )
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
                                        ${translate(
                                            'owners.reference_short'
                                        )}
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
                                        ${translate(
                                            'owners.collector_short'
                                        )}
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
                                        ${translate(
                                            'owners.invoice'
                                        )} #${escapeHtml(
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
                ${translate(
                    'owners.page_of',
                    {
                        current:
                            formatNumber(
                                current
                            ),

                        last:
                            formatNumber(
                                last
                            ),
                    }
                )}
                ·
                ${translate(
                    total === 1
                        ? 'owners.pagination_transaction'
                        : 'owners.pagination_transactions',
                    {
                        total:
                            formatNumber(
                                total
                            ),
                    }
                )}
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
                    ${translate(
                        'owners.previous'
                    )}
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
                    ${translate(
                        'owners.next'
                    )}
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
                ${translate(
                    'owners.no_payouts'
                )}
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
                                                    ${translate(
                                            'owners.reference_short'
                                        )}
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
     * Shared drawer headers generate close buttons by ID.
     */
    [
        'owner-deposit-modal',
        'owner-expense-modal',
        'owner-payout-modal',
        'owner-adjustment-modal',
    ].forEach(
        (id) => {
            document
                .getElementById(
                    `${id}-close`
                )
                ?.addEventListener(
                    'click',
                    () => {
                        closeOwnerModal(
                            id
                        );
                    }
                );
        }
    );


    /*
     * Clicking the drawer backdrop closes it.
     */
    [
        'owner-deposit-modal',
        'owner-expense-modal',
        'owner-payout-modal',
        'owner-adjustment-modal',
    ].forEach(
        (id) => {
            document
                .getElementById(
                    `${id}-backdrop`
                )
                ?.addEventListener(
                    'click',
                    () => {
                        closeOwnerModal(
                            id
                        );
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
| Owner Financial Drawer Presentation
|--------------------------------------------------------------------------
|
| Drawer dates are displayed using Patrimoine's DD-MM-YYYY convention while
| API payloads continue to use ISO YYYY-MM-DD dates.
|
| Currency labels are derived from formatCurrency(), ensuring these forms use
| the same organisation-wide presentation configuration as the rest of the
| application.
|
*/

/**
 * Return the organisation currency marker using the same presentation
 * configuration as formatCurrency().
 *
 * @returns {string}
 */
function ownerCurrencyDisplay() {
    const formatted =
        formatCurrency(0);

    return formatted
        .replace(/[0-9\s.,+-]/g, '')
        .trim();
}

/**
 * Synchronise currency markers in Owner financial drawers.
 */
function synchronizeOwnerCurrencyDisplays() {
    const currency =
        ownerCurrencyDisplay();

    document
        .querySelectorAll(
            '[data-currency-display]'
        )
        .forEach(
            (element) => {
                element.textContent =
                    currency;
            }
        );
}

/**
 * Convert ISO YYYY-MM-DD into DD-MM-YYYY for display.
 *
 * @param {string|null} value
 * @returns {string}
 */
function ownerDateForDisplay(
    value
) {
    return dateForDisplay(
        value
    );
}

/**
 * Convert DD-MM-YYYY into ISO YYYY-MM-DD for API submission.
 *
 * Existing ISO values are accepted as well.
 *
 * @param {string|null} value
 * @returns {string}
 */
function ownerDateForApi(
    value
) {
    return dateForApi(
        value
    );
}

/**
 * Put an ISO date into an Owner drawer using application display format.
 *
 * @param {string} id
 * @param {string} value
 */
function setOwnerDateValue(
    id,
    value
) {
    setFieldValue(
        id,
        ownerDateForDisplay(
            value
        )
    );
}

/**
 * Read an Owner drawer date in API format.
 *
 * @param {string} id
 * @returns {string}
 */
function ownerDateValue(
    id
) {
    return ownerDateForApi(
        fieldValue(
            id
        )
    );
}

/**
 * Normalise typed Owner drawer dates.
 *
 * Digits entered as DDMMYYYY are progressively displayed as DD-MM-YYYY.
 */
function initializeOwnerDateInputs() {
    initializeDateInputs(
        '[data-owner-date-input]'
    );
}

/*
|--------------------------------------------------------------------------
| Owner Drawer Helpers
|--------------------------------------------------------------------------
*/

const ownerDrawerCloseTimers = new Map();

function openOwnerModal(
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
        ownerDrawerCloseTimers.get(
            id
        );

    if (oldTimer) {
        window.clearTimeout(
            oldTimer
        );

        ownerDrawerCloseTimers.delete(
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

function closeOwnerModal(
    id
) {
    if (! id) {
        return;
    }

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
        ownerDrawerCloseTimers.get(
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

                ownerDrawerCloseTimers.delete(
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

    ownerDrawerCloseTimers.set(
        id,
        timer
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
            translate('owners.select_owner_first')
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
                    ${translate(
                        'owners.no_specific_building'
                    )}
                </option>
            `
            : `
                <option value="">
                    ${translate(
                        'owners.select_building'
                    )}
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
                ?? `${translate(
                    'owners.building'
                )} #${building.id}`;

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
            ${translate(
                'owners.no_specific_unit'
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
                ?? `${translate(
                    'owners.unit'
                )} #${unit.id}`;

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
            ${translate(
                'owners.select_building_first'
            )}
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

    setOwnerDateValue(
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
            translate('owners.invalid_deposit_amount')
        );

        return;
    }

    if (
        method === 'cash'
        && ! collector
    ) {
        showOwnerActionError(
            'owner-deposit-error',
            translate('owners.collector_required')
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
            ownerDateValue(
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
        translate('owners.recording'),
        translate('actions.save')
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
                : translate('owners.unable_to_record_deposit')
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-deposit-submit',
            false,
            translate('owners.recording'),
            translate('owners.record_deposit')
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
            translate('owners.no_property_for_expense')
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

    setOwnerDateValue(
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
        translate(
            'owners.expense_sharing_warning',
            {
                percentage:
                    percentage.toFixed(2),
            }
        );

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
            translate('owners.select_expense_building')
        );

        return;
    }

    if (
        ! Number.isInteger(amount)
        || amount <= 0
    ) {
        showOwnerActionError(
            'owner-expense-error',
            translate('owners.invalid_expense_amount')
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
            translate('owners.expense_description_required')
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
            ownerDateValue(
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
        translate('owners.recording'),
        translate('actions.save')
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
                : translate('owners.unable_to_record_expense')
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-expense-submit',
            false,
            translate('owners.recording'),
            translate('owners.record_expense')
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
            translate('owners.no_payout_funds')
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

    setOwnerDateValue(
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
            translate('owners.invalid_payout_amount')
        );

        return;
    }

    if (amount > balance) {
        showOwnerActionError(
            'owner-payout-error',
            translate(
                'owners.payout_exceeds_balance',
                {
                    balance:
                        formatCurrency(
                            balance
                        ),
                }
            )
        );

        return;
    }

    const payload = {
        amount,

        payout_date:
            ownerDateValue(
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
        translate('owners.processing'),
        translate('actions.save')
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
                : translate('owners.unable_to_create_payout')
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-payout-submit',
            false,
            translate('owners.processing'),
            translate('owners.make_payout')
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

    setOwnerDateValue(
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
            translate('owners.invalid_adjustment_amount')
        );

        return;
    }

    if (! reason) {
        showOwnerActionError(
            'owner-adjustment-error',
            translate('owners.adjustment_reason_required')
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
            ownerDateValue(
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
        translate('owners.recording'),
        translate('actions.save')
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
                : translate('owners.unable_to_record_adjustment')
        );
    } finally {
        setOwnerActionSubmitting(
            'owner-adjustment-submit',
            false,
            translate('owners.recording'),
            translate('owners.record_adjustment')
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
                translate('owners.unable_to_open_document')
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
                : translate('owners.unable_to_open_document')
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
            return translate('owners.owner_deposit');

        case 'rent_entitlement':
            return translate('owners.rent_collected');

        case 'expense':
            return translate('owners.property_expense');

        case 'management_fee':
            return translate('owners.management_fee');

        case 'agent_commission':
            return translate('owners.agent_commission');

        case 'adjustment':
            return translate('owners.adjustment');

        case 'payout':
            return translate('owners.owner_payout');

        default:
            return capitalizeWords(
                category
                ?? translate(
                    'owners.transaction'
                )
            );
    }
}

function paymentMethodLabel(
    method
) {
    switch (method) {
        case 'cash':
            return translate('owners.cash');

        case 'bank_transfer':
            return translate('owners.bank_transfer');

        case 'momo':
            return translate('owners.momo');

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
            return translate('owners.general_funding');

        case 'property_expense':
            return translate('owners.property_expense');

        case 'repair_maintenance':
            return translate('owners.repair_maintenance');

        case 'other':
            return translate('owners.other');

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
        || translate('owners.unnamed_owner');
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
            ${translate(
                'owners.loading'
            )}
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
            ${translate(
                'owners.loading_details'
            )}
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
