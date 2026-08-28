/*
|--------------------------------------------------------------------------
| Patrimoine Financial Journal
|--------------------------------------------------------------------------
|
| Administrator-only, read-only browser workspace for V1.0.5.
|
| The API remains the authoritative authorization boundary. This UI
| performs no Journal writes and exposes no edit/delete/reversal controls.
|
*/

import {
    apiRequest,
    escapeHtml,
    formValue,
    formatCurrency,
    formatDate,
    getPresentationConfiguration,
    parseJsonResponse,
    translate,
} from './core.js';

import {
    dateForApi,
    initializeDateInputs,
} from './date-input.js';

let financialJournalSearchTimer =
    null;

let financialJournalDrawerCloseTimer =
    null;

export async function initializeFinancialJournal() {
    const workspace =
        document.getElementById(
            'financial-journal-workspace'
        );

    if (! workspace) {
        return;
    }

    if (
        document.body.dataset
            .currentUserRole
        !== 'administrator'
    ) {
        window.location.replace(
            '/dashboard'
        );

        return;
    }

    initializeDateInputs();

    initializeFinancialJournalFilters();
    initializeFinancialJournalExports();
    initializeFinancialJournalDrawer();

    await Promise.all([
        loadFinancialJournalFilterOptions(),
        loadFinancialJournal(),
    ]);
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

function initializeFinancialJournalFilters() {
    document
        .getElementById(
            'financial-journal-search'
        )
        ?.addEventListener(
            'input',
            () => {
                clearTimeout(
                    financialJournalSearchTimer
                );

                financialJournalSearchTimer =
                    setTimeout(
                        () =>
                            loadFinancialJournal(
                                1
                            ),
                        300
                    );
            }
        );

    [
        'financial-journal-from',
        'financial-journal-to',
        'financial-journal-entry-kind',
        'financial-journal-transaction-type',
        'financial-journal-account',
    ].forEach(
        (id) => {
            document
                .getElementById(id)
                ?.addEventListener(
                    'change',
                    () =>
                        loadFinancialJournal(
                            1
                        )
                );
        }
    );

    document
        .getElementById(
            'financial-journal-clear-filters'
        )
        ?.addEventListener(
            'click',
            clearFinancialJournalFilters
        );
}

function financialJournalFilterParameters() {
    const parameters =
        new URLSearchParams();

    const values = {
        search:
            formValue(
                'financial-journal-search'
            ),

        from:
            dateForApi(
                formValue(
                    'financial-journal-from'
                )
            ),

        to:
            dateForApi(
                formValue(
                    'financial-journal-to'
                )
            ),

        entry_kind:
            formValue(
                'financial-journal-entry-kind'
            ),

        transaction_type:
            formValue(
                'financial-journal-transaction-type'
            ),

        account_id:
            formValue(
                'financial-journal-account'
            ),
    };

    Object.entries(
        values
    ).forEach(
        ([name, value]) => {
            if (value !== '') {
                parameters.set(
                    name,
                    value
                );
            }
        }
    );

    return parameters;
}

function financialJournalQueryParameters(
    page = 1
) {
    const parameters =
        financialJournalFilterParameters();

    parameters.set(
        'page',
        String(page)
    );

    parameters.set(
        'per_page',
        '25'
    );

    return parameters;
}

function clearFinancialJournalFilters() {
    [
        'financial-journal-search',
        'financial-journal-from',
        'financial-journal-to',
        'financial-journal-entry-kind',
        'financial-journal-transaction-type',
        'financial-journal-account',
    ].forEach(
        (id) => {
            const element =
                document.getElementById(
                    id
                );

            if (element) {
                element.value = '';
            }
        }
    );

    loadFinancialJournal(1);
}

/*
|--------------------------------------------------------------------------
| Filter Options
|--------------------------------------------------------------------------
*/

async function loadFinancialJournalFilterOptions() {
    try {
        const response =
            await apiRequest(
                '/api/financial-journal/filter-options'
            );

        const payload =
            await parseJsonResponse(
                response
            );

        const transactionType =
            document.getElementById(
                'financial-journal-transaction-type'
            );

        if (
            transactionType
            && Array.isArray(
                payload?.transaction_types
            )
        ) {
            payload.transaction_types
                .forEach(
                    (type) => {
                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            type;

                        option.textContent =
                            transactionTypeLabel(
                                type
                            );

                        transactionType
                            .appendChild(
                                option
                            );
                    }
                );
        }

        const account =
            document.getElementById(
                'financial-journal-account'
            );

        if (
            account
            && Array.isArray(
                payload?.accounts
            )
        ) {
            payload.accounts
                .forEach(
                    (item) => {
                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            String(
                                item.id
                            );

                        option.textContent =
                            [
                                item.code,
                                item.name,
                            ]
                                .filter(Boolean)
                                .join(' — ');

                        account.appendChild(
                            option
                        );
                    }
                );
        }
    } catch {
        /*
         * Filter option failure must not prevent Journal browsing.
         */
    }
}

/*
|--------------------------------------------------------------------------
| Exports
|--------------------------------------------------------------------------
*/

function initializeFinancialJournalExports() {
    [
        'pdf',
        'csv',
        'xlsx',
    ].forEach(
        (format) => {
            document
                .getElementById(
                    `financial-journal-export-${format}`
                )
                ?.addEventListener(
                    'click',
                    () =>
                        exportFinancialJournal(
                            format
                        )
                );
        }
    );
}

async function exportFinancialJournal(
    format
) {
    const button =
        document.getElementById(
            `financial-journal-export-${format}`
        );

    const originalLabel =
        button?.textContent
            ?.trim()
        || '';

    hideFinancialJournalError();

    try {
        if (button) {
            button.disabled =
                true;

            button.textContent =
                translate(
                    'financial_journal.exporting'
                );
        }

        const parameters =
            financialJournalFilterParameters();

        const query =
            parameters.toString();

        const endpoint =
            `/api/financial-journal/${format}`
            + (
                query !== ''
                    ? `?${query}`
                    : ''
            );

        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            format === 'pdf'
                                ? 'application/pdf'
                                : (
                                    format === 'csv'
                                        ? 'text/csv'
                                        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                ),
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
            financialJournalExportFilename(
                response,
                format
            );

        document.body.appendChild(
            link
        );

        link.click();
        link.remove();

        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            1000
        );
    } catch (error) {
        showFinancialJournalError(
            error instanceof Error
                ? error.message
                : translate(
                    'financial_journal.unable_export'
                )
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                originalLabel
                || translate(
                    `financial_journal.export_${format}`
                );
        }
    }
}

function financialJournalExportFilename(
    response,
    format
) {
    const disposition =
        response.headers.get(
            'Content-Disposition'
        )
        || '';

    const encoded =
        disposition.match(
            /filename\*=UTF-8''([^;]+)/i
        );

    if (encoded?.[1]) {
        try {
            return decodeURIComponent(
                encoded[1]
                    .trim()
                    .replace(
                        /^["']|["']$/g,
                        ''
                    )
            );
        } catch {
            // Fall through.
        }
    }

    const standard =
        disposition.match(
            /filename\s*=\s*"?([^";]+)"?/i
        );

    if (standard?.[1]) {
        return standard[1]
            .trim();
    }

    return `financial-journal.${format}`;
}

/*
|--------------------------------------------------------------------------
| Register
|--------------------------------------------------------------------------
*/

async function loadFinancialJournal(
    page = 1
) {
    const container =
        document.getElementById(
            'financial-journal-list'
        );

    if (! container) {
        return;
    }

    hideFinancialJournalError();

    container.innerHTML = `
        <div
            class="
                px-5 py-12
                text-center text-sm
            "
        >
            ${escapeHtml(
                translate(
                    'financial_journal.loading'
                )
            )}
        </div>
    `;

    try {
        const parameters =
            financialJournalQueryParameters(
                page
            );

        const response =
            await apiRequest(
                `/api/financial-journal?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderFinancialJournal(
            payload
        );

        renderFinancialJournalPagination(
            payload
        );
    } catch (error) {
        container.innerHTML =
            '';

        showFinancialJournalError(
            error instanceof Error
                ? error.message
                : translate(
                    'financial_journal.unable_load'
                )
        );
    }
}

function renderFinancialJournal(
    payload
) {
    const container =
        document.getElementById(
            'financial-journal-list'
        );

    if (! container) {
        return;
    }

    const entries =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    if (entries.length === 0) {
        container.innerHTML = `
            <div
                class="
                    px-6 py-14
                    text-center
                "
            >
                <div
                    class="
                        text-sm font-medium
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'financial_journal.none_found'
                        )
                    )}
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-[var(--pm-text-muted)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'financial_journal.none_found_description'
                        )
                    )}
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        entries
            .map(
                financialJournalRow
            )
            .join('');

    container
        .querySelectorAll(
            '[data-financial-journal-id]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () =>
                        openFinancialJournalDetail(
                            button.dataset
                                .financialJournalId
                        )
                );
            }
        );
}

function financialJournalRow(
    entry
) {
    return `
        <article
            class="
                px-5 py-4
                transition
                hover:bg-[var(--pm-hover)]
            "
        >
            <div
                class="
                    flex flex-col gap-4
                    xl:flex-row
                    xl:items-center
                    xl:justify-between
                "
            >
                <div class="min-w-0 flex-1">
                    <div
                        class="
                            flex flex-wrap
                            items-center gap-2
                        "
                    >
                        <span
                            class="
                                pm-activity-action-badge
                                rounded-full
                                px-2.5 py-1
                                font-mono
                                text-xs font-medium
                            "
                        >
                            ${escapeHtml(
                                entry.journal_number
                            )}
                        </span>

                        <span
                            class="
                                pm-activity-role-badge
                                rounded-full
                                px-2.5 py-1
                                text-xs font-medium
                            "
                        >
                            ${escapeHtml(
                                financialJournalKindLabel(
                                    entry.entry_kind
                                )
                            )}
                        </span>

                        ${
                            entry.is_reversed
                                ? `
                                    <span
                                        class="
                                            rounded-full
                                            border px-2.5 py-1
                                            text-xs font-medium
                                            border-[var(--pm-danger-border)]
                                            text-[var(--pm-danger-text)]
                                        "
                                    >
                                        ${escapeHtml(
                                            translate(
                                                'financial_journal.reversed'
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>

                    <div
                        class="
                            mt-3 text-sm
                            font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        ${escapeHtml(
                            entry.description
                            || transactionTypeLabel(
                                entry.transaction_type
                            )
                        )}
                    </div>

                    <div
                        class="
                            mt-1 flex flex-wrap
                            gap-x-5 gap-y-1
                            text-xs
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span>
                            ${escapeHtml(
                                formatJournalDate(
                                    entry.journal_date
                                )
                            )}
                        </span>

                        <span>
                            ${escapeHtml(
                                transactionTypeLabel(
                                    entry.transaction_type
                                )
                            )}
                        </span>

                        ${
                            entry.actor_name_snapshot
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            entry.actor_name_snapshot
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>
                </div>

                <div
                    class="
                        flex flex-wrap
                        items-center gap-5
                    "
                >
                    <div>
                        <div
                            class="
                                text-[11px]
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'financial_journal.debit'
                                )
                            )}
                        </div>

                        <div
                            class="
                                mt-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(
                                formatCurrency(
                                    entry.debit_total
                                )
                            )}
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-[11px]
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'financial_journal.credit'
                                )
                            )}
                        </div>

                        <div
                            class="
                                mt-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(
                                formatCurrency(
                                    entry.credit_total
                                )
                            )}
                        </div>
                    </div>

                    <button
                        type="button"
                        data-financial-journal-id="${escapeHtml(entry.id)}"
                        class="
                            pm-activity-detail-button
                            shrink-0 rounded-lg
                            border px-3 py-2
                            text-xs font-medium
                            transition
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'financial_journal.view_details'
                            )
                        )}
                    </button>
                </div>
            </div>
        </article>
    `;
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

function renderFinancialJournalPagination(
    payload
) {
    const container =
        document.getElementById(
            'financial-journal-pagination'
        );

    if (! container) {
        return;
    }

    const current =
        Number(
            payload?.current_page
            ?? 1
        );

    const last =
        Number(
            payload?.last_page
            ?? 1
        );

    if (last <= 1) {
        container.innerHTML =
            '';

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
                justify-between gap-4
            "
        >
            <div
                class="
                    text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'financial_journal.page_of',
                        {
                            current,
                            last,
                        }
                    )
                )}
            </div>

            <div class="flex gap-2">
                <button
                    id="financial-journal-previous"
                    type="button"
                    ${current <= 1 ? 'disabled' : ''}
                    class="
                        pm-button-secondary
                        px-3 py-2
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'financial_journal.previous'
                        )
                    )}
                </button>

                <button
                    id="financial-journal-next"
                    type="button"
                    ${current >= last ? 'disabled' : ''}
                    class="
                        pm-button-secondary
                        px-3 py-2
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'financial_journal.next'
                        )
                    )}
                </button>
            </div>
        </div>
    `;

    document
        .getElementById(
            'financial-journal-previous'
        )
        ?.addEventListener(
            'click',
            () => {
                if (current > 1) {
                    loadFinancialJournal(
                        current - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'financial-journal-next'
        )
        ?.addEventListener(
            'click',
            () => {
                if (current < last) {
                    loadFinancialJournal(
                        current + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Detail Drawer
|--------------------------------------------------------------------------
*/

function initializeFinancialJournalDrawer() {
    document
        .getElementById(
            'financial-journal-modal-close'
        )
        ?.addEventListener(
            'click',
            closeFinancialJournalDetail
        );

    document
        .getElementById(
            'financial-journal-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeFinancialJournalDetail
        );

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key === 'Escape') {
                closeFinancialJournalDetail();
            }
        }
    );
}

async function openFinancialJournalDetail(
    id
) {
    const container =
        document.getElementById(
            'financial-journal-detail'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                py-12 text-center
                text-sm
                text-[var(--pm-text-subtle)]
            "
        >
            ${escapeHtml(
                translate(
                    'financial_journal.loading_detail'
                )
            )}
        </div>
    `;

    showFinancialJournalDrawer();

    try {
        const response =
            await apiRequest(
                `/api/financial-journal/${id}`
            );

        const entry =
            await parseJsonResponse(
                response
            );

        container.innerHTML =
            financialJournalDetailMarkup(
                entry
            );
    } catch (error) {
        container.innerHTML = `
            <div
                class="
                    rounded-lg border
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    px-4 py-3
                    text-sm
                    text-[var(--pm-danger-text)]
                "
            >
                ${escapeHtml(
                    error instanceof Error
                        ? error.message
                        : translate(
                            'financial_journal.unable_load_detail'
                        )
                )}
            </div>
        `;
    }
}

function financialJournalDetailMarkup(
    entry
) {
    const lines =
        Array.isArray(
            entry?.lines
        )
            ? entry.lines
            : [];

    return `
        <div class="space-y-6">
            <section>
                <div
                    class="
                        flex flex-wrap
                        items-center
                        justify-between gap-3
                    "
                >
                    <div>
                        <div
                            class="
                                font-mono text-base
                                font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(
                                entry.journal_number
                            )}
                        </div>

                        <div
                            class="
                                mt-1 text-sm
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(
                                financialJournalKindLabel(
                                    entry.entry_kind
                                )
                            )}
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(
                                formatJournalDate(
                                    entry.journal_date
                                )
                            )}
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(
                                formatJournalTimestamp(
                                    entry.posted_at
                                )
                            )}
                        </div>
                    </div>
                </div>

                <dl
                    class="
                        mt-4 grid
                        grid-cols-1 gap-3
                    "
                >
                    ${financialJournalDetailItem(
                        translate(
                            'financial_journal.transaction_type'
                        ),
                        transactionTypeLabel(
                            entry.transaction_type
                        )
                    )}

                    ${financialJournalDetailItem(
                        translate(
                            'financial_journal.actor'
                        ),
                        entry.actor_name_snapshot
                    )}

                    ${financialJournalDetailItem(
                        translate(
                            'financial_journal.source'
                        ),
                        financialJournalSource(
                            entry
                        )
                    )}

                    ${financialJournalDetailItem(
                        translate(
                            'financial_journal.balance_status'
                        ),
                        entry.is_balanced
                            ? translate(
                                'financial_journal.balanced'
                            )
                            : translate(
                                'financial_journal.unbalanced'
                            )
                    )}
                </dl>
            </section>

            <section>
                <h3
                    class="
                        text-xs font-semibold
                        uppercase tracking-[0.12em]
                        text-[var(--pm-text-muted)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'financial_journal.description_label'
                        )
                    )}
                </h3>

                <div
                    class="
                        mt-3 rounded-lg border
                        border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)]
                        px-4 py-3
                        text-sm
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        entry.description
                        || translate(
                            'financial_journal.not_available'
                        )
                    )}
                </div>
            </section>

            ${financialJournalReversalMarkup(entry)}

            <section>
                <div
                    class="
                        flex items-center
                        justify-between gap-4
                    "
                >
                    <h3
                        class="
                            text-xs font-semibold
                            uppercase tracking-[0.12em]
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'financial_journal.accounting_lines'
                            )
                        )}
                    </h3>

                    <div
                        class="
                            text-xs
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'financial_journal.line_count',
                                {
                                    count:
                                        lines.length,
                                }
                            )
                        )}
                    </div>
                </div>

                ${financialJournalLinesMarkup(
                    lines
                )}

                <div
                    class="
                        mt-3 grid
                        grid-cols-1 gap-3
                    "
                >
                    ${financialJournalTotalCard(
                        translate(
                            'financial_journal.total_debit'
                        ),
                        entry.debit_total
                    )}

                    ${financialJournalTotalCard(
                        translate(
                            'financial_journal.total_credit'
                        ),
                        entry.credit_total
                    )}
                </div>
            </section>
        </div>
    `;
}

function financialJournalLinesMarkup(
    lines
) {
    if (lines.length === 0) {
        return `
            <div
                class="
                    mt-3 rounded-lg border
                    border-[var(--pm-border)]
                    px-4 py-6 text-center
                    text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'financial_journal.no_lines'
                    )
                )}
            </div>
        `;
    }

    return `
        <div
            class="
                mt-3 overflow-hidden
                rounded-lg border
                border-[var(--pm-border)]
            "
        >
            ${lines.map(
                (line) => `
                    <div
                        class="
                            grid grid-cols-1 gap-3
                            border-b
                            border-[var(--pm-border-subtle)]
                            px-4 py-4
                            last:border-b-0
                        "
                    >
                        <div>
                            <div
                                class="
                                    font-mono text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(
                                    line.account_code_snapshot
                                    || ''
                                )}
                            </div>

                            <div
                                class="
                                    mt-1 text-sm
                                    font-medium
                                    text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(
                                    line.account_name_snapshot
                                    || translate(
                                        'financial_journal.not_available'
                                    )
                                )}
                            </div>

                            ${
                                line.memo
                                    ? `
                                        <div
                                            class="
                                                mt-1 text-xs
                                                text-[var(--pm-text-muted)]
                                            "
                                        >
                                            ${escapeHtml(
                                                line.memo
                                            )}
                                        </div>
                                    `
                                    : ''
                            }
                        </div>

                        <div>
                            <div
                                class="
                                    text-[11px]
                                    uppercase tracking-wide
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(
                                    translate(
                                        'financial_journal.debit'
                                    )
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
                                    formatCurrency(
                                        line.debit_amount
                                    )
                                )}
                            </div>
                        </div>

                        <div>
                            <div
                                class="
                                    text-[11px]
                                    uppercase tracking-wide
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(
                                    translate(
                                        'financial_journal.credit'
                                    )
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
                                    formatCurrency(
                                        line.credit_amount
                                    )
                                )}
                            </div>
                        </div>
                    </div>
                `
            ).join('')}
        </div>
    `;
}

function financialJournalReversalMarkup(
    entry
) {
    const cards = [];

    if (entry.reversal_of) {
        cards.push(
            financialJournalDetailItem(
                translate(
                    'financial_journal.reversal_of'
                ),
                entry.reversal_of
                    .journal_number
            )
        );
    }

    if (entry.reversed_by) {
        cards.push(
            financialJournalDetailItem(
                translate(
                    'financial_journal.reversed_by'
                ),
                entry.reversed_by
                    .journal_number
            )
        );

        if (
            entry.reversed_by
                .reversal_reason
        ) {
            cards.push(
                financialJournalDetailItem(
                    translate(
                        'financial_journal.reversal_reason'
                    ),
                    entry.reversed_by
                        .reversal_reason
                )
            );
        }
    }

    if (
        entry.reversal_reason
        && entry.entry_kind === 'reversal'
    ) {
        cards.push(
            financialJournalDetailItem(
                translate(
                    'financial_journal.reversal_reason'
                ),
                entry.reversal_reason
            )
        );
    }

    if (cards.length === 0) {
        return '';
    }

    return `
        <section>
            <h3
                class="
                    text-xs font-semibold
                    uppercase tracking-[0.12em]
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'financial_journal.reversal_context'
                    )
                )}
            </h3>

            <dl
                class="
                    mt-3 grid
                    grid-cols-1 gap-3
                "
            >
                ${cards.join('')}
            </dl>
        </section>
    `;
}

function financialJournalDetailItem(
    label,
    value
) {
    const displayed =
        value === null
        || value === undefined
        || value === ''
            ? translate(
                'financial_journal.not_available'
            )
            : String(value);

    return `
        <div
            class="
                rounded-lg border
                border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)]
                px-4 py-3
            "
        >
            <dt
                class="
                    text-xs font-medium
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(label)}
            </dt>

            <dd
                class="
                    mt-1 break-words
                    text-sm
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(displayed)}
            </dd>
        </div>
    `;
}

function financialJournalTotalCard(
    label,
    amount
) {
    return `
        <div
            class="
                rounded-lg border
                border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)]
                px-4 py-3
                text-right
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
                ${escapeHtml(
                    formatCurrency(
                        amount
                    )
                )}
            </div>
        </div>
    `;
}

function showFinancialJournalDrawer() {
    const modal =
        document.getElementById(
            'financial-journal-modal'
        );

    if (! modal) {
        return;
    }

    if (
        financialJournalDrawerCloseTimer
        !== null
    ) {
        window.clearTimeout(
            financialJournalDrawerCloseTimer
        );

        financialJournalDrawerCloseTimer =
            null;
    }

    modal.classList.remove(
        'pm-drawer-open',
        'pm-drawer-closing'
    );

    modal.removeAttribute(
        'hidden'
    );

    modal.classList.add(
        'pm-drawer-active'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    const panel =
        modal.querySelector(
            '.pm-drawer-panel'
        );

    if (panel) {
        void panel.getBoundingClientRect();
    }

    window.requestAnimationFrame(
        () => {
            window.requestAnimationFrame(
                () => {
                    modal.classList.add(
                        'pm-drawer-open'
                    );
                }
            );
        }
    );
}

function closeFinancialJournalDetail() {
    const modal =
        document.getElementById(
            'financial-journal-modal'
        );

    if (
        ! modal
        || ! modal.classList.contains(
            'pm-drawer-active'
        )
    ) {
        return;
    }

    modal.classList.remove(
        'pm-drawer-open'
    );

    modal.classList.add(
        'pm-drawer-closing'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    if (
        financialJournalDrawerCloseTimer
        !== null
    ) {
        window.clearTimeout(
            financialJournalDrawerCloseTimer
        );
    }

    financialJournalDrawerCloseTimer =
        window.setTimeout(
            () => {
                modal.classList.remove(
                    'pm-drawer-active',
                    'pm-drawer-closing'
                );

                document.body
                    .classList.remove(
                        'overflow-hidden'
                    );

                financialJournalDrawerCloseTimer =
                    null;
            },
            850
        );
}

/*
|--------------------------------------------------------------------------
| Presentation
|--------------------------------------------------------------------------
*/

function financialJournalKindLabel(
    kind
) {
    if (! kind) {
        return '';
    }

    const key =
        `financial_journal.kind_${kind}`;

    const translated =
        translate(
            key
        );

    return translated === key
        ? humanizeIdentifier(kind)
        : translated;
}


/**
 * Human label for a journal transaction type.
 *
 * Types are a closed set with translations; anything new falls back to
 * the prettified identifier so an unmapped type still reads sensibly.
 */
function transactionTypeLabel(type) {
    if (! type) {
        return '';
    }

    const key =
        'financial_journal.transaction_types.'
        + type;

    const label =
        translate(key);

    return label === key
        ? humanizeIdentifier(type)
        : label;
}

function humanizeIdentifier(
    value
) {
    if (! value) {
        return '';
    }

    return String(value)
        .replaceAll('.', ' ')
        .replaceAll('_', ' ')
        .replace(
            /\b\w/g,
            (character) =>
                character.toUpperCase()
        );
}

function financialJournalSource(
    entry
) {
    if (! entry?.source_type) {
        return '';
    }

    const type =
        humanizeIdentifier(
            String(
                entry.source_type
            )
                .split('\\')
                .pop()
        );

    if (! entry.source_id) {
        return type;
    }

    return `${type} #${entry.source_id}`;
}

function formatJournalDate(
    value
) {
    /*
     * V1.0.9: business dates follow the application-wide presentation
     * standard (DD/MM/YYYY in English, DD-MM-YYYY in French) instead of
     * a Journal-only hardcoded dash format.
     */
    return formatDate(value);
}

function formatJournalTimestamp(
    value
) {
    if (! value) {
        return '';
    }

    const date =
        new Date(value);

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(value);
    }

    const configuration =
        getPresentationConfiguration();

    return new Intl.DateTimeFormat(
        configuration.browser_locale
        || 'en-GB',
        {
            dateStyle: 'medium',
            timeStyle: 'medium',
        }
    ).format(date);
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showFinancialJournalError(
    message
) {
    const element =
        document.getElementById(
            'financial-journal-error'
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

function hideFinancialJournalError() {
    const element =
        document.getElementById(
            'financial-journal-error'
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}
