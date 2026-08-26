/*
|--------------------------------------------------------------------------
| Patrimoine Licence & Plan Page (V1.1.0)
|--------------------------------------------------------------------------
|
| Renders the organisation's current plan, usage meters and the plan
| comparison matrix from GET /api/license.
|
*/

import {
    apiRequest,
    escapeHtml,
    formatNumber,
    parseJsonResponse,
    translate,
} from './core.js';

/**
 * The plan keys in presentation order.
 */
const PLAN_ORDER = ['free', 'standard', 'professional'];

/**
 * Rows of the comparison table: [translation key, renderer].
 */
function comparisonRows() {
    return [
        {
            key: 'license.row_price',
            render: (plan) =>
                plan.price_monthly_usd > 0
                    ? '$'
                        + plan.price_monthly_usd
                        + translate('license.per_month')
                    : translate('license.price_free'),
        },
        {
            key: 'license.row_users',
            render: (plan) =>
                formatNumber(plan.limits.users),
        },
        {
            key: 'license.row_active_leases',
            render: (plan) =>
                formatNumber(plan.limits.active_leases),
        },
        {
            key: 'license.row_parties',
            render: (plan) =>
                formatNumber(plan.limits.parties),
        },
        {
            key: 'license.row_emails',
            render: (plan) =>
                formatNumber(plan.limits.emails_per_month)
                + translate('license.per_month'),
        },
        {
            key: 'license.row_reports',
            render: (plan) =>
                booleanMark(plan.features.reports),
        },
        {
            key: 'license.row_exports',
            render: (plan) =>
                booleanMark(plan.features.exports),
        },
        {
            key: 'license.row_automated',
            render: (plan) =>
                booleanMark(plan.features.automated_reminders),
        },
        {
            key: 'license.row_sms',
            render: (plan) =>
                plan.features.sms
                    ? formatNumber(plan.limits.sms_per_month)
                        + translate('license.per_month')
                    : booleanMark(false),
        },
        {
            key: 'license.row_portal',
            render: (plan) =>
                booleanMark(plan.features.party_portal),
        },
        {
            key: 'license.row_api',
            render: (plan) =>
                booleanMark(plan.features.api_access),
        },
    ];
}

function booleanMark(included) {
    return included
        ? '<span class="text-[var(--pm-accent)] font-semibold">✓</span>'
        : '<span class="text-[var(--pm-text-muted)]">—</span>';
}

/**
 * One usage meter card.
 */
function usageCard(labelKey, used, limit) {
    const unlimited =
        limit === null
        || limit === undefined;

    const percent = unlimited
        ? 0
        : Math.min(
            100,
            limit === 0
                ? 100
                : Math.round((used / limit) * 100)
        );

    const nearLimit =
        ! unlimited
        && percent >= 90;

    return `
        <div class="rounded-xl border border-[var(--pm-border)] p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-[var(--pm-text-muted)]">
                ${escapeHtml(translate(labelKey))}
            </div>

            <div class="mt-2 text-xl font-semibold text-[var(--pm-text)]">
                ${formatNumber(used)}
                <span class="text-sm font-normal text-[var(--pm-text-muted)]">
                    /
                    ${
                        unlimited
                            ? escapeHtml(translate('license.unlimited'))
                            : formatNumber(limit)
                    }
                </span>
            </div>

            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[var(--pm-border)]">
                <div
                    class="h-full rounded-full ${
                        nearLimit
                            ? 'bg-[var(--pm-danger,#b3261e)]'
                            : 'bg-[var(--pm-accent)]'
                    }"
                    style="width: ${percent}%"
                ></div>
            </div>
        </div>
    `;
}

/**
 * Initialize the licence page when present.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeLicense() {
    const workspace =
        document.getElementById('license-workspace');

    if (! workspace) {
        return false;
    }

    const errorBox =
        document.getElementById('license-error');

    try {
        const response =
            await apiRequest('/api/license');

        const data =
            await parseJsonResponse(response);

        renderCurrentPlan(data);

        renderUsage(data);

        renderComparison(data);
    } catch (error) {
        if (errorBox) {
            errorBox.textContent =
                error instanceof Error
                    ? error.message
                    : translate('license.unable');

            errorBox.classList.remove('hidden');
        }
    }

    return true;
}

function renderCurrentPlan(data) {
    const planName =
        document.getElementById('license-plan-name');

    if (planName) {
        planName.textContent =
            translate('license.plan_' + data.plan)
            || data.plan_label
            || data.plan;
    }

    const badge =
        document.getElementById('license-trial-badge');

    if (badge && data.on_trial) {
        badge.textContent =
            translate('license.trial_until')
            + ' '
            + (data.trial_ends_on || '');

        badge.classList.remove('hidden');
    }
}

function renderUsage(data) {
    const container =
        document.getElementById('license-usage');

    if (! container) {
        return;
    }

    const usage =
        data.usage
        || {};

    const limits =
        data.limits
        || {};

    container.innerHTML = [
        usageCard(
            'license.usage_users',
            usage.users || 0,
            limits.users
        ),
        usageCard(
            'license.usage_active_leases',
            usage.active_leases || 0,
            limits.active_leases
        ),
        usageCard(
            'license.usage_parties',
            usage.parties || 0,
            limits.parties
        ),
        usageCard(
            'license.usage_emails',
            usage.emails_this_month || 0,
            limits.emails_per_month
        ),
    ].join('');
}

function renderComparison(data) {
    const table =
        document.getElementById('license-plans-table');

    if (! table) {
        return;
    }

    const plans =
        data.plans
        || {};

    const headCells =
        PLAN_ORDER
            .map((key) => {
                const isCurrent =
                    key === data.plan;

                const highlight =
                    key === 'professional';

                return `
                    <th class="border-b border-[var(--pm-border)] px-4 py-3 text-left align-bottom">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-[var(--pm-text)]">
                                ${escapeHtml(translate('license.plan_' + key))}
                            </span>
                            ${
                                highlight
                                    ? `<span class="rounded-full bg-[var(--pm-accent)]/10 px-2 py-0.5 text-[11px] font-semibold text-[var(--pm-accent)]">${escapeHtml(translate('license.most_popular'))}</span>`
                                    : ''
                            }
                        </div>
                        ${
                            isCurrent
                                ? `<div class="mt-1 text-[11px] font-medium uppercase tracking-wide text-[var(--pm-text-muted)]">${escapeHtml(translate('license.your_plan'))}</div>`
                                : ''
                        }
                    </th>
                `;
            })
            .join('');

    const bodyRows =
        comparisonRows()
            .map((row) => {
                const cells =
                    PLAN_ORDER
                        .map((key) => {
                            const plan =
                                plans[key];

                            return `
                                <td class="border-b border-[var(--pm-border)] px-4 py-3 text-[var(--pm-text-secondary)]">
                                    ${plan ? row.render(plan) : '—'}
                                </td>
                            `;
                        })
                        .join('');

                return `
                    <tr>
                        <td class="border-b border-[var(--pm-border)] px-4 py-3 font-medium text-[var(--pm-text)]">
                            ${escapeHtml(translate(row.key))}
                        </td>
                        ${cells}
                    </tr>
                `;
            })
            .join('');

    table.innerHTML = `
        <thead>
            <tr>
                <th class="w-[240px] border-b border-[var(--pm-border)] px-4 py-3"></th>
                ${headCells}
            </tr>
        </thead>
        <tbody>${bodyRows}</tbody>
    `;
}
