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
    formatLongDate,
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

    const dateElement =
        document.getElementById(
            'dashboard-date'
        );

    const errorBox =
        document.getElementById(
            'dashboard-error'
        );

    if (dateElement) {
        dateElement.textContent =
            formatLongDate(
                new Date()
            );
    }

    if (errorBox) {
        errorBox.classList.add(
            'hidden'
        );

        errorBox.textContent =
            '';
    }

    try {
        const [
            summaryResponse,
            overdueResponse,
            upcomingResponse,
        ] = await Promise.all([
            apiRequest(
                '/api/dashboard'
            ),

            apiRequest(
                '/api/dashboard/overdue'
            ),

            apiRequest(
                '/api/dashboard/upcoming'
            ),
        ]);

        const summary =
            await parseJsonResponse(
                summaryResponse
            );

        const overdue =
            await parseJsonResponse(
                overdueResponse
            );

        const upcoming =
            await parseJsonResponse(
                upcomingResponse
            );

        renderDashboardSummary(
            summary
        );

        renderCollectionsTrend(
            summary?.collections_trend
        );

        renderInvoiceList(
            'overdue-list',
            overdue
        );

        renderInvoiceList(
            'upcoming-list',
            upcoming
        );

        renderExpiringLeases(
            summary
        );

        renderUpcomingIncrements(
            summary
        );
    } catch (error) {
        if (! errorBox) {
            return;
        }

        errorBox.textContent =
            error instanceof Error
                ? error.message
                : translate(
                    'dashboard.unable_to_load'
                );

        errorBox.classList.remove(
            'hidden'
        );
    }
}

/**
 * Return the first present value from a collection of candidate keys.
 *
 * This retains compatibility with earlier dashboard response names while
 * allowing the current API contract to remain authoritative.
 *
 * @param {object} object
 * @param {string[]} keys
 * @param {*} fallback
 * @returns {*}
 */
function firstDefined(
    object,
    keys,
    fallback = 0
) {
    for (const key of keys) {
        if (
            object
            && object[key] !== undefined
            && object[key] !== null
        ) {
            return object[key];
        }
    }

    return fallback;
}

/**
 * Format a "YYYY-MM" trend month using the organisation language locale.
 *
 * @param {string} value
 * @returns {string}
 */
function formatTrendMonth(
    value
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

    return new Intl.DateTimeFormat(
        getPresentationConfiguration()
            .browser_locale
        || 'en-GB',
        {
            month:
                'short',

            year:
                '2-digit',
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
 * The fallback to the root object keeps the browser tolerant of older API
 * response structures.
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
            : summary;

    const occupied =
        Number(
            firstDefined(
                metrics,
                [
                    'occupied_units',
                    'occupied',
                ]
            )
        ) || 0;

    const totalUnits =
        Number(
            firstDefined(
                metrics,
                [
                    'total_units',
                    'units',
                ]
            )
        ) || 0;

    const providedRate =
        firstDefined(
            summary,
            [
                'occupancy_rate',
            ],
            null
        );

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
    }

    setText(
        'metric-occupied',
        occupied
    );

    setText(
        'metric-vacant',
        firstDefined(
            metrics,
            [
                'vacant_units',
                'vacant',
            ]
        )
    );

    setText(
        'metric-vacant-commercial',
        firstDefined(
            metrics,
            [
                'vacant_commercial_units',
            ]
        )
    );

    setText(
        'metric-vacant-residential',
        firstDefined(
            metrics,
            [
                'vacant_residential_units',
            ]
        )
    );

    setText(
        'metric-buildings',
        firstDefined(
            metrics,
            [
                'total_buildings',
                'buildings',
            ]
        )
    );

    setText(
        'metric-units',
        totalUnits
    );

    setText(
        'metric-rent-due',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'rent_due',
                    'total_rent_due',
                ]
            )
        )
    );

    setText(
        'metric-rent-overdue',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'rent_overdue',
                    'total_rent_overdue',
                ]
            )
        )
    );

    setText(
        'metric-collected',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'rent_collected_this_month',
                    'collected_this_month',
                    'cash_collected_this_month',
                ]
            )
        )
    );

    setText(
        'metric-owner-funds',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'owner_funds_held',
                    'owner_funds',
                ]
            )
        )
    );

    setText(
        'metric-tenant-funds',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'tenant_funds_held',
                    'tenant_funds',
                ]
            )
        )
    );
}

/**
 * Render the six-month collections trend as a hand-rolled div bar chart.
 *
 * No chart library and no canvas: a flex row of columns whose inner bar
 * heights are proportional to the highest month in the series.
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
        container.innerHTML = `
            <div
                class="
                    rounded-lg border border-dashed
                    border-[var(--pm-border)]
                    px-4 py-8 text-center
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'dashboard.no_collections'
                    )
                )}
            </div>
        `;

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
                        `${label} — ${formatCurrency(
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
                                    truncate text-xs
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
        <div
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
                firstDefined(
                    summary?.metrics,
                    [
                        'leases_expiring_90_days',
                    ],
                    leases.length
                )
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
 * Normalize possible API collection response formats.
 *
 * @param {*} payload
 * @returns {Array}
 */
function normalizeItems(payload) {
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

    if (
        Array.isArray(
            payload?.invoices
        )
    ) {
        return payload.invoices;
    }

    if (
        Array.isArray(
            payload?.items
        )
    ) {
        return payload.items;
    }

    if (
        Array.isArray(
            payload?.obligations
        )
    ) {
        return payload.obligations;
    }

    return [];
}

/**
 * Render overdue or upcoming invoice obligations.
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

    const items =
        normalizeItems(
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

    container.innerHTML =
        items
            .slice(0, 6)
            .map(
                (item) => {
                    const tenant =
                        item.tenant?.name
                        || item.tenant
                            ?.legal_name
                        || item.tenant_name
                        || translate(
                            'dashboard.tenant'
                        );

                    const property =
                        item.building?.name
                        || (
                            typeof item.building
                            === 'string'
                                ? item.building
                                : ''
                        )
                        || item.unit
                            ?.building
                            ?.name
                        || '';

                    const unit =
                        item.unit?.name
                        || (
                            typeof item.unit
                            === 'string'
                                ? item.unit
                                : ''
                        )
                        || '';

                    const amount =
                        item
                            .outstanding_amount
                        ?? item.outstanding
                        ?? item.balance
                        ?? item.amount
                        ?? 0;

                    const date =
                        item.due_date
                        || item.date
                        || '';

                    const propertyLabel =
                        [
                            property,
                            unit,
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

                                ${
                                    date
                                        ? `
                                            <div
                                                class="
                                                    mt-1 text-xs
                                                    text-[var(--pm-text-muted)]
                                                "
                                            >
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
                        </div>
                    `;
                }
            )
            .join('');
}
