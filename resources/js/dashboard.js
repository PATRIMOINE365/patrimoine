/*
|--------------------------------------------------------------------------
| Patrimoine Dashboard
|--------------------------------------------------------------------------
|
| Handles the Property Manager dashboard only.
|
| Responsibilities:
|
| - portfolio metrics;
| - financial metrics;
| - overdue rent;
| - upcoming rent;
| - dashboard date and error handling.
|
*/

import {
    apiRequest,
    escapeHtml,
    formatCurrency,
    formatLongDate,
    parseJsonResponse,
    setText,
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

        renderInvoiceList(
            'overdue-list',
            overdue
        );

        renderInvoiceList(
            'upcoming-list',
            upcoming
        );
    } catch (error) {
        if (! errorBox) {
            return;
        }

        errorBox.textContent =
            error instanceof Error
                ? error.message
                : 'Unable to load dashboard information.';

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
 * Render dashboard summary metrics.
 *
 * Current API format:
 *
 * {
 *     "as_of": "...",
 *     "metrics": {
 *         ...
 *     }
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
        firstDefined(
            metrics,
            [
                'total_units',
                'units',
            ]
        )
    );

    setText(
        'metric-occupied',
        firstDefined(
            metrics,
            [
                'occupied_units',
                'occupied',
            ]
        )
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
        container.innerHTML = `
            <div
                class="
                    rounded-lg border border-dashed
                    border-slate-200
                    px-4 py-8 text-center
                    text-sm text-slate-400
                "
            >
                No records to display.
            </div>
        `;

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
                        || 'Tenant';

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
                                border-b border-slate-100
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
                                        text-slate-900
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
                                                    text-slate-500
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
                                                    text-slate-400
                                                "
                                            >
                                                Due ${escapeHtml(
                                                    date
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
                                    text-slate-900
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
