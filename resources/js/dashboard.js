/*
|--------------------------------------------------------------------------
| Patrimoine Dashboard
|--------------------------------------------------------------------------
|
| Handles the Property Manager dashboard only.
|
| Responsibilities:
|
| - occupancy hero band (rate, meter, vacancy split);
| - financial metric tiles;
| - six-month collections trend chart (hand-rolled, div based);
| - owner and tenant funds held;
| - overdue rent and upcoming rent lists;
| - leases expiring within 90 days;
| - upcoming rent increments;
| - dashboard date and error handling.
|
*/

import {
    apiRequest,
    escapeHtml,
    formatCurrency,
    formatDate,
    getPresentationConfiguration,
    parseJsonResponse,
    setText,
    translate,
} from './core.js';

/**
 * Initialize the dashboard when its identifying DOM element exists.
 */
export async function initializeDashboard() {
    const buildings =
        document.getElementById(
            'metric-buildings'
        );

    /*
     * The metric is also used as a lightweight page detector so this module
     * can safely be loaded globally through Vite.
     */
    if (! buildings) {
        return;
    }

    const errorBox =
        document.getElementById(
            'dashboard-error'
        );

    if (errorBox) {
        errorBox.classList.add(
            'hidden'
        );

        errorBox.textContent =
            '';
    }

    /*
     * V1.0.9: the three regions load independently. A failing request
     * paints an error state in its own region only, so one outage never
     * blanks the whole dashboard.
     */
    const [
        summaryResult,
        overdueResult,
        upcomingResult,
    ] = await Promise.allSettled([
        fetchJson(
            '/api/dashboard'
        ),

        fetchJson(
            '/api/dashboard/overdue'
        ),

        fetchJson(
            '/api/dashboard/upcoming'
        ),
    ]);

    if (
        summaryResult.status
        === 'fulfilled'
    ) {
        const summary =
            summaryResult.value;

        renderDashboardSummary(
            summary
        );

        renderCollectionsTrend(
            summary?.collections_trend
        );

        renderExpiringLeases(
            summary
        );

        renderUpcomingIncrements(
            summary
        );
    } else {
        renderRegionError(
            'collections-chart',
            summaryResult.reason
        );

        renderRegionError(
            'expiring-list',
            summaryResult.reason
        );

        renderRegionError(
            'increments-list',
            summaryResult.reason
        );

        /*
         * The metric tiles have no region error state of their own, so a
         * failed summary — which includes total failure — also raises the
         * page-level banner.
         */
        if (errorBox) {
            errorBox.textContent =
                errorMessage(
                    summaryResult.reason,
                    'dashboard.unable_to_load'
                );

            errorBox.classList.remove(
                'hidden'
            );
        }
    }

    if (
        overdueResult.status
        === 'fulfilled'
    ) {
        renderInvoiceList(
            'overdue-list',
            overdueResult.value
        );
    } else {
        renderRegionError(
            'overdue-list',
            overdueResult.reason
        );
    }

    if (
        upcomingResult.status
        === 'fulfilled'
    ) {
        renderInvoiceList(
            'upcoming-list',
            upcomingResult.value
        );
    } else {
        renderRegionError(
            'upcoming-list',
            upcomingResult.reason
        );
    }
}

/**
 * Request one API endpoint and parse its JSON body.
 *
 * @param {string} path
 * @returns {Promise<any>}
 */
async function fetchJson(path) {
    const response =
        await apiRequest(
            path
        );

    return parseJsonResponse(
        response
    );
}

/**
 * Resolve a human-readable message from a settled rejection reason.
 *
 * @param {*} reason
 * @param {string} fallbackKey
 * @returns {string}
 */
function errorMessage(
    reason,
    fallbackKey
) {
    return reason instanceof Error
        && reason.message
        ? reason.message
        : translate(
            fallbackKey
        );
}

/**
 * Paint a scoped error state inside one dashboard region.
 *
 * @param {string} containerId
 * @param {*} reason
 */
function renderRegionError(
    containerId,
    reason
) {
    const container =
        document.getElementById(
            containerId
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            role="alert"
            class="
                rounded-lg border
                border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)]
                px-4 py-8 text-center
                text-sm text-[var(--pm-danger-text)]
            "
        >
            ${escapeHtml(
                errorMessage(
                    reason,
                    'dashboard.unable_to_load_section'
                )
            )}
        </div>
    `;
}

/**
 * Format a "YYYY-MM" trend month using the active application language.
 *
 * The same language source drives translate(), keeping the chart labels
 * aligned with the rest of the interface rather than the raw browser
 * locale.
 *
 * @param {string} value
 * @param {boolean} withYear
 * @returns {string}
 */
function formatTrendMonth(
    value,
    withYear = false
) {
    const parts =
        String(value || '')
            .split('-');

    if (parts.length < 2) {
        return String(value || '');
    }

    const date =
        new Date(
            Number(parts[0]),
            Number(parts[1]) - 1,
            1
        );

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(value);
    }

    const language =
        getPresentationConfiguration()
            .language
        || 'en';

    return new Intl.DateTimeFormat(
        language,
        withYear
            ? {
                month:
                    'short',

                year:
                    'numeric',
            }
            : {
                month:
                    'short',
            }
    ).format(
        date
    );
}

/**
 * Render dashboard summary metrics.
 *
 * Current API format:
 *
 * {
 *     "as_of": "...",
 *     "metrics": { ... },
 *     "occupancy_rate": 0-100,
 *     "collections_trend": [ ... ],
 *     "expiring_leases": [ ... ],
 *     "upcoming_increments": [ ... ]
 * }
 *
 * @param {object} summary
 */
function renderDashboardSummary(
    summary
) {
    const metrics =
        summary?.metrics
        && typeof summary.metrics
            === 'object'
            ? summary.metrics
            : {};

    const occupied =
        Number(
            metrics.occupied_units
            ?? 0
        ) || 0;

    const totalUnits =
        Number(
            metrics.total_units
            ?? 0
        ) || 0;

    const providedRate =
        summary?.occupancy_rate
        ?? null;

    const rate =
        providedRate !== null
        && Number.isFinite(
            Number(providedRate)
        )
            ? Number(providedRate)
            : (
                totalUnits > 0
                    ? Math.round(
                        (occupied / totalUnits)
                        * 100
                    )
                    : 0
            );

    const boundedRate =
        Math.max(
            0,
            Math.min(
                100,
                rate
            )
        );

    setText(
        'metric-occupancy-rate',
        `${boundedRate}%`
    );

    const meter =
        document.getElementById(
            'occupancy-meter'
        );

    if (meter) {
        meter.style.width =
            `${boundedRate}%`;

        meter.setAttribute(
            'aria-valuenow',
            String(boundedRate)
        );
    }

    setText(
        'metric-occupied',
        occupied
    );

    setText(
        'metric-vacant',
        metrics.vacant_units
        ?? 0
    );

    setText(
        'metric-vacant-commercial',
        metrics.vacant_commercial_units
        ?? 0
    );

    setText(
        'metric-vacant-residential',
        metrics.vacant_residential_units
        ?? 0
    );

    setText(
        'metric-buildings',
        metrics.total_buildings
        ?? 0
    );

    setText(
        'metric-units',
        totalUnits
    );

    setText(
        'metric-rent-due',
        formatCurrency(
            metrics.rent_due
            ?? 0
        )
    );

    setText(
        'metric-rent-overdue',
        formatCurrency(
            metrics.rent_overdue
            ?? 0
        )
    );

    setText(
        'metric-collected',
        formatCurrency(
            metrics.rent_collected_this_month
            ?? 0
        )
    );

    setText(
        'metric-management-fees',
        formatCurrency(
            metrics.management_fees_this_month
            ?? 0
        )
    );

    setText(
        'metric-owner-funds',
        formatCurrency(
            metrics.owner_funds_held
            ?? 0
        )
    );

    setText(
        'metric-tenant-funds',
        formatCurrency(
            metrics.tenant_funds_held
            ?? 0
        )
    );
}

/**
 * Render the six-month collections trend as a hand-rolled div bar chart.
 *
 * No chart library and no canvas: a flex row of columns whose inner bar
 * heights are proportional to the highest month in the series. A
 * screen-reader summary carries the exact month/amount series while the
 * visual bars stay decorative.
 *
 * @param {*} trend
 */
function renderCollectionsTrend(
    trend
) {
    const container =
        document.getElementById(
            'collections-chart'
        );

    if (! container) {
        return;
    }

    const months =
        Array.isArray(trend)
            ? trend.filter(
                (entry) =>
                    entry
                    && typeof entry
                        === 'object'
            )
            : [];

    if (months.length === 0) {
        container.innerHTML =
            emptyStateHtml(
                'dashboard.no_collections'
            );

        return;
    }

    const maximum =
        Math.max(
            ...months.map(
                (entry) =>
                    Number(entry.amount)
                    || 0
            ),
            1
        );

    const summaryText =
        months
            .map(
                (entry) =>
                    `${formatTrendMonth(
                        entry.month,
                        true
                    )}: ${formatCurrency(
                        Number(entry.amount)
                        || 0
                    )}`
            )
            .join(', ');

    const bars =
        months
            .map(
                (entry) => {
                    const amount =
                        Number(entry.amount)
                        || 0;

                    const label =
                        formatTrendMonth(
                            entry.month
                        );

                    const percent =
                        Math.round(
                            (amount / maximum)
                            * 100
                        );

                    const height =
                        amount > 0
                            ? Math.max(
                                percent,
                                2
                            )
                            : 0;

                    const tooltip =
                        `${formatTrendMonth(
                            entry.month,
                            true
                        )} — ${formatCurrency(
                            amount
                        )}`;

                    return `
                        <div
                            class="
                                flex min-w-0 flex-1
                                flex-col items-center
                                gap-2
                            "
                            title="${escapeHtml(
                                tooltip
                            ).replaceAll(
                                '"',
                                '&quot;'
                            )}"
                        >
                            <div
                                class="
                                    flex h-40 w-full
                                    items-end
                                "
                            >
                                <div
                                    class="
                                        w-full rounded-t-md
                                        bg-[var(--pm-primary)]
                                    "
                                    style="height: ${height}%; min-height: ${
                                        amount > 0
                                            ? '4px'
                                            : '2px'
                                    }; ${
                                        amount > 0
                                            ? ''
                                            : 'background: var(--pm-surface-muted);'
                                    }"
                                ></div>
                            </div>

                            <div
                                class="
                                    text-center text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${escapeHtml(
                                    label
                                )}
                            </div>
                        </div>
                    `;
                }
            )
            .join('');

    container.innerHTML = `
        <p class="sr-only">
            ${escapeHtml(
                `${translate(
                    'dashboard.collections_trend_description'
                )} ${summaryText}`
            )}
        </p>

        <div
            aria-hidden="true"
            class="
                flex items-end gap-2
                sm:gap-4
            "
        >
            ${bars}
        </div>
    `;
}

/**
 * Render the leases expiring within the next 90 days.
 *
 * @param {object} summary
 */
function renderExpiringLeases(
    summary
) {
    const container =
        document.getElementById(
            'expiring-list'
        );

    if (! container) {
        return;
    }

    const leases =
        Array.isArray(
            summary?.expiring_leases
        )
            ? summary.expiring_leases
            : [];

    const badge =
        document.getElementById(
            'expiring-count'
        );

    if (badge) {
        const count =
            Number(
                summary?.metrics
                    ?.leases_expiring_90_days
                ?? leases.length
            ) || 0;

        badge.textContent =
            String(count);

        badge.classList.toggle(
            'hidden',
            count === 0
        );
    }

    if (leases.length === 0) {
        container.innerHTML =
            emptyStateHtml(
                'dashboard.no_expiring_leases'
            );

        return;
    }

    container.innerHTML =
        leases
            .slice(0, 6)
            .map(
                (lease) => {
                    const tenant =
                        lease.tenant_name
                        || translate(
                            'dashboard.tenant'
                        );

                    const propertyLabel =
                        [
                            lease.building_name,
                            lease.unit_name,
                        ]
                            .filter(
                                Boolean
                            )
                            .join(
                                ' / '
                            );

                    return `
                        <div
                            class="
                                flex items-center gap-4
                                border-b border-[var(--pm-border)]
                                py-4 last:border-b-0
                                first:pt-0 last:pb-0
                            "
                        >
                            <div
                                class="
                                    min-w-0 flex-1
                                "
                            >
                                <div
                                    class="
                                        truncate text-sm
                                        font-medium
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(
                                        tenant
                                    )}
                                </div>

                                ${
                                    propertyLabel
                                        ? `
                                            <div
                                                class="
                                                    mt-1 truncate
                                                    text-xs
                                                    text-[var(--pm-text-secondary)]
                                                "
                                            >
                                                ${escapeHtml(
                                                    propertyLabel
                                                )}
                                            </div>
                                        `
                                        : ''
                                }
                            </div>

                            <div
                                class="
                                    shrink-0 text-right
                                "
                            >
                                <div
                                    class="
                                        text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'dashboard.ends'
                                        )
                                    )}
                                </div>

                                <div
                                    class="
                                        text-sm font-semibold
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(
                                        formatDate(
                                            lease.end_date
                                        )
                                    )}
                                </div>
                            </div>
                        </div>
                    `;
                }
            )
            .join('');
}

/**
 * Render the rent increments taking effect soon.
 *
 * @param {object} summary
 */
function renderUpcomingIncrements(
    summary
) {
    const container =
        document.getElementById(
            'increments-list'
        );

    if (! container) {
        return;
    }

    const increments =
        Array.isArray(
            summary?.upcoming_increments
        )
            ? summary.upcoming_increments
            : [];

    const badge =
        document.getElementById(
            'increments-count'
        );

    if (badge) {
        const count =
            Number(
                summary?.metrics
                    ?.increments_upcoming_60_days
                ?? increments.length
            ) || 0;

        badge.textContent =
            String(count);

        badge.classList.toggle(
            'hidden',
            count === 0
        );
    }

    if (increments.length === 0) {
        container.innerHTML =
            emptyStateHtml(
                'dashboard.no_increments'
            );

        return;
    }

    container.innerHTML =
        increments
            .slice(0, 6)
            .map(
                (increment) => {
                    const tenant =
                        increment.tenant_name
                        || translate(
                            'dashboard.tenant'
                        );

                    const oldRent =
                        formatCurrency(
                            increment
                                .old_rent_amount
                        );

                    const newRent =
                        formatCurrency(
                            increment
                                .new_rent_amount
                        );

                    return `
                        <div
                            class="
                                flex items-center gap-4
                                border-b border-[var(--pm-border)]
                                py-4 last:border-b-0
                                first:pt-0 last:pb-0
                            "
                        >
                            <div
                                class="
                                    min-w-0 flex-1
                                "
                            >
                                <div
                                    class="
                                        truncate text-sm
                                        font-medium
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(
                                        tenant
                                    )}
                                </div>

                                <div
                                    class="
                                        mt-1 truncate
                                        text-xs
                                        text-[var(--pm-text-secondary)]
                                    "
                                >
                                    ${escapeHtml(
                                        oldRent
                                    )}
                                    <span
                                        class="
                                            text-[var(--pm-text-muted)]
                                        "
                                    >→</span>
                                    <span
                                        class="
                                            font-semibold
                                            text-[var(--pm-text)]
                                        "
                                    >${escapeHtml(
                                        newRent
                                    )}</span>
                                </div>
                            </div>

                            <div
                                class="
                                    shrink-0 text-right
                                "
                            >
                                <div
                                    class="
                                        text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'dashboard.effective'
                                        )
                                    )}
                                </div>

                                <div
                                    class="
                                        text-sm font-semibold
                                        text-[var(--pm-text)]
                                    "
                                >
                                    ${escapeHtml(
                                        formatDate(
                                            increment
                                                .effective_date
                                        )
                                    )}
                                </div>
                            </div>
                        </div>
                    `;
                }
            )
            .join('');
}

/**
 * Build the shared dashed empty-state block for dashboard lists.
 *
 * @param {string} translationKey
 * @returns {string}
 */
function emptyStateHtml(
    translationKey
) {
    return `
        <div
            class="
                rounded-lg border border-dashed
                border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)]
                px-4 py-8 text-center
                text-sm text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(
                translate(
                    translationKey
                )
            )}
        </div>
    `;
}

/**
 * Normalize an obligations envelope from the dashboard API.
 *
 * Both /api/dashboard/overdue and /api/dashboard/upcoming respond with
 * { as_of, count, data: [...] }; the envelope count covers the full
 * result set, of which only a slice is rendered.
 *
 * @param {*} payload
 * @returns {{items: Array, count: number}}
 */
function normalizeCollection(
    payload
) {
    const items =
        Array.isArray(payload)
            ? payload
            : (
                Array.isArray(
                    payload?.data
                )
                    ? payload.data
                    : []
            );

    const count =
        Number.isFinite(
            Number(payload?.count)
        )
            ? Number(payload.count)
            : items.length;

    return {
        items,
        count,
    };
}

/**
 * Render overdue or upcoming invoice obligations.
 *
 * Each row deep-links into the Tenants workspace for the row's tenant,
 * and an overflow line surfaces obligations beyond the rendered slice.
 *
 * @param {string} containerId
 * @param {*} payload
 */
function renderInvoiceList(
    containerId,
    payload
) {
    const container =
        document.getElementById(
            containerId
        );

    if (! container) {
        return;
    }

    const {
        items,
        count,
    } = normalizeCollection(
        payload
    );

    if (
        items.length === 0
    ) {
        container.innerHTML =
            emptyStateHtml(
                'dashboard.no_records'
            );

        return;
    }

    const shown =
        items.slice(0, 6);

    const rows =
        shown
            .map(
                (item) => {
                    const tenant =
                        item.tenant?.name
                        || translate(
                            'dashboard.tenant'
                        );

                    const tenantId =
                        item.tenant?.id;

                    const amount =
                        item.outstanding_amount
                        ?? 0;

                    const date =
                        item.due_date
                        || '';

                    const propertyLabel =
                        [
                            item.building?.name,
                            item.unit?.name,
                        ]
                            .filter(
                                Boolean
                            )
                            .join(
                                ' / '
                            );

                    const invoiceNumber =
                        item.invoice_number
                        || '';

                    const paidContext =
                        item.status === 'partial'
                            ? translate(
                                'dashboard.paid_of_total',
                                {
                                    paid:
                                        formatCurrency(
                                            item.paid_amount
                                            ?? 0
                                        ),

                                    total:
                                        formatCurrency(
                                            item.total_amount
                                            ?? 0
                                        ),
                                }
                            )
                            : '';

                    const rowContent = `
                        <div
                            class="
                                min-w-0 flex-1
                            "
                        >
                            <div
                                class="
                                    truncate text-sm
                                    font-medium
                                    text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(
                                    tenant
                                )}
                            </div>

                            ${
                                propertyLabel
                                    ? `
                                        <div
                                            class="
                                                mt-1 truncate
                                                text-xs
                                                text-[var(--pm-text-secondary)]
                                            "
                                        >
                                            ${escapeHtml(
                                                propertyLabel
                                            )}
                                        </div>
                                    `
                                    : ''
                            }

                            <div
                                class="
                                    mt-1 flex flex-wrap
                                    items-center gap-x-2
                                    text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${
                                    invoiceNumber
                                        ? `
                                            <span>${escapeHtml(
                                                invoiceNumber
                                            )}</span>
                                        `
                                        : ''
                                }

                                ${
                                    date
                                        ? `
                                            <span>
                                                ${escapeHtml(
                                                    translate(
                                                        'dashboard.due'
                                                    )
                                                )}
                                                ${escapeHtml(
                                                    formatDate(
                                                        date
                                                    )
                                                )}
                                            </span>
                                        `
                                        : ''
                                }
                            </div>

                            ${
                                paidContext
                                    ? `
                                        <div
                                            class="
                                                mt-1 text-xs
                                                text-[var(--pm-warning-text)]
                                            "
                                        >
                                            ${escapeHtml(
                                                paidContext
                                            )}
                                        </div>
                                    `
                                    : ''
                            }
                        </div>

                        <div
                            class="
                                shrink-0 text-sm
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
                    `;

                    const rowClasses = `
                        flex items-center gap-4
                        border-b border-[var(--pm-border)]
                        py-4 last:border-b-0
                        first:pt-0 last:pb-0
                    `;

                    /*
                     * A row with a known tenant deep-links into the
                     * Tenants workspace; tenants.js resolves the
                     * tenant_id query parameter on load.
                     */
                    return tenantId
                        ? `
                            <a
                                href="/tenants?tenant_id=${encodeURIComponent(
                                    tenantId
                                )}"
                                class="${rowClasses}
                                    rounded-lg
                                    hover:bg-[var(--pm-hover)]
                                "
                            >
                                ${rowContent}
                            </a>
                        `
                        : `
                            <div
                                class="${rowClasses}"
                            >
                                ${rowContent}
                            </div>
                        `;
                }
            )
            .join('');

    const overflow =
        count > shown.length
            ? `
                <div
                    class="
                        pt-4 text-center
                    "
                >
                    <a
                        href="/tenants"
                        class="
                            text-sm font-medium
                            text-[var(--pm-accent)]
                            hover:underline
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'dashboard.more_records',
                                {
                                    count:
                                        count
                                        - shown.length,
                                }
                            )
                        )}
                    </a>
                </div>
            `
            : '';

    container.innerHTML =
        rows
        + overflow;
}
