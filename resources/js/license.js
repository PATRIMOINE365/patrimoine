/*
|--------------------------------------------------------------------------
| Patrimoine Licence & Plan Page (V1.0.10)
|--------------------------------------------------------------------------
|
| Renders the organisation's current plan and its usage meters from
| GET /api/license. Plan comparison lives on patrimoine365.com/pricing.
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
