/*
|--------------------------------------------------------------------------
| Patrimoine Platform Administration Console (V1.0.11)
|--------------------------------------------------------------------------
|
| Kality Ltd staff only. Renders the platform dashboard, the customer
| organisation list/detail, and drives licence issuance, suspension,
| support tools and permanent deletion against /api/admin/*.
|
| Internal tool: deliberately English-only.
|
*/

import {
    apiRequest,
    closeDrawer,
    escapeHtml,
    formatNumber,
    openDrawer,
    parseJsonResponse,
    wireDrawer,
} from './core.js';

let currentOrganisation = null;

let searchDebounce = null;

/**
 * POST/PATCH/DELETE JSON against the admin API.
 */
async function adminRequest(url, method, payload) {
    const response = await apiRequest(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: payload === undefined
            ? undefined
            : JSON.stringify(payload),
    });

    return parseJsonResponse(response);
}

function showError(message) {
    const box = document.getElementById('admin-error');

    if (! box) {
        return;
    }

    box.textContent = message;

    box.classList.remove('hidden');

    box.scrollIntoView({ block: 'nearest' });
}

function clearError() {
    document.getElementById('admin-error')?.classList.add('hidden');
}

function statusBadge(status) {
    const active = status === 'active';

    return `
        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ${
            active
                ? 'bg-[var(--pm-accent)]/10 text-[var(--pm-accent)]'
                : 'bg-[var(--pm-danger,#b3261e)]/10 text-[var(--pm-danger,#b3261e)]'
        }">${active ? 'Active' : 'Suspended'}</span>
    `;
}

function metricCard(label, value, hint) {
    return `
        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-[var(--pm-text-muted)]">
                ${escapeHtml(label)}
            </div>
            <div class="mt-2 text-2xl font-semibold text-[var(--pm-text)]">
                ${escapeHtml(String(value))}
            </div>
            ${hint ? `<div class="mt-1 text-xs text-[var(--pm-text-muted)]">${escapeHtml(hint)}</div>` : ''}
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| Overview
|--------------------------------------------------------------------------
*/

async function loadDashboard() {
    const data = await adminRequest('/api/admin/dashboard', 'GET');

    const metrics = document.getElementById('admin-metrics');

    if (metrics) {
        metrics.innerHTML = [
            metricCard(
                'Organisations',
                data.totals.organisations,
                `${data.totals.suspended} suspended`
            ),
            metricCard(
                'On trial',
                data.totals.on_trial,
                'Professional trial running'
            ),
            metricCard(
                'Licensed',
                data.totals.licensed,
                `${data.totals.plans.standard} Standard · ${data.totals.plans.professional} Professional`
            ),
            metricCard(
                'Signups this month',
                data.signups_this_month,
                ''
            ),
        ].join('');
    }

    const expiring = document.getElementById('admin-expiring');

    if (expiring) {
        expiring.innerHTML = data.expiring_soon.length === 0
            ? '<p class="text-sm text-[var(--pm-text-muted)]">Nothing expires within 14 days.</p>'
            : data.expiring_soon.map(
                (row) => `
                    <div class="flex items-center justify-between border-b border-[var(--pm-border)] py-2 text-sm last:border-b-0">
                        <button type="button" class="font-medium text-[var(--pm-accent)]" data-admin-open="${row.organisation_id}">
                            ${escapeHtml(row.organisation)}
                        </button>
                        <span class="text-[var(--pm-text-muted)]">
                            ${escapeHtml(row.kind)} · ${escapeHtml(row.plan)} · ends ${escapeHtml(row.ends_on)}
                        </span>
                    </div>
                `
            ).join('');
    }

    const usage = document.getElementById('admin-email-usage');

    if (usage) {
        usage.innerHTML = data.top_email_usage.length === 0
            ? '<p class="text-sm text-[var(--pm-text-muted)]">No product email sent this month.</p>'
            : data.top_email_usage.map(
                (row) => `
                    <div class="flex items-center justify-between border-b border-[var(--pm-border)] py-2 text-sm last:border-b-0">
                        <span class="font-medium text-[var(--pm-text)]">${escapeHtml(row.organisation)}</span>
                        <span class="text-[var(--pm-text-muted)]">${formatNumber(row.sent)} sent</span>
                    </div>
                `
            ).join('');
    }
}

/*
|--------------------------------------------------------------------------
| Organisation list
|--------------------------------------------------------------------------
*/

async function loadOrganisations(page = 1) {
    const search = document.getElementById('admin-search')?.value.trim() ?? '';

    const status = document.getElementById('admin-status-filter')?.value ?? '';

    const params = new URLSearchParams({ page: String(page) });

    if (search !== '') {
        params.set('search', search);
    }

    if (status !== '') {
        params.set('status', status);
    }

    const data = await adminRequest(
        '/api/admin/organisations?' + params.toString(),
        'GET'
    );

    const body = document.getElementById('admin-orgs-body');

    if (body) {
        body.innerHTML = data.data.length === 0
            ? '<tr><td colspan="6" class="px-3 py-6 text-sm text-[var(--pm-text-muted)]">No organisations found.</td></tr>'
            : data.data.map(
                (org) => `
                    <tr class="cursor-pointer hover:bg-[var(--pm-hover)]" data-admin-open="${org.id}">
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 font-medium text-[var(--pm-text)]">
                            ${escapeHtml(org.name)}
                        </td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3">${statusBadge(org.status)}</td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">
                            ${escapeHtml(org.plan)}${org.on_trial ? ' (trial)' : ''}
                        </td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">${formatNumber(org.usage.users)}</td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">${formatNumber(org.usage.active_leases)}</td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-muted)]">${escapeHtml(org.created_at ?? '')}</td>
                    </tr>
                `
            ).join('');
    }

    const pagination = document.getElementById('admin-pagination');

    if (pagination) {
        const { current_page: current, last_page: last, total } = data.meta;

        pagination.innerHTML = `
            <span class="text-[var(--pm-text-muted)]">${formatNumber(total)} organisation(s)</span>
            <span class="flex items-center gap-2">
                <button type="button" class="pm-button-secondary ${current <= 1 ? 'invisible' : ''}" data-admin-page="${current - 1}">Previous</button>
                <span class="text-[var(--pm-text-muted)]">Page ${current} / ${last}</span>
                <button type="button" class="pm-button-secondary ${current >= last ? 'invisible' : ''}" data-admin-page="${current + 1}">Next</button>
            </span>
        `;
    }
}

/*
|--------------------------------------------------------------------------
| Organisation detail
|--------------------------------------------------------------------------
*/

async function openOrganisation(id) {
    clearError();

    const data = await adminRequest(
        '/api/admin/organisations/' + id,
        'GET'
    );

    currentOrganisation = data.organisation;

    document.getElementById('admin-overview')?.classList.add('hidden');
    document.getElementById('admin-detail')?.classList.remove('hidden');

    document.getElementById('admin-detail-name').textContent =
        data.organisation.name;

    document.getElementById('admin-detail-status').innerHTML =
        statusBadge(data.organisation.status);

    document.getElementById('admin-detail-meta').textContent =
        `${data.organisation.plan}${data.organisation.on_trial ? ' (trial until ' + data.organisation.trial_ends_on + ')' : ''}`
        + ` · signed up ${String(data.organisation.created_at ?? '').slice(0, 10)}`;

    document.getElementById('admin-suspend')?.classList.toggle(
        'hidden',
        data.organisation.status !== 'active'
    );

    document.getElementById('admin-reactivate')?.classList.toggle(
        'hidden',
        data.organisation.status !== 'suspended'
    );

    /*
     * Usage vs the plan's limits.
     */
    const usage = document.getElementById('admin-detail-usage');

    if (usage) {
        const limits = data.limits ?? {};

        usage.innerHTML = [
            metricCard('Users', `${data.usage.users} / ${limits.users ?? '∞'}`, ''),
            metricCard('Active leases', `${data.usage.active_leases} / ${limits.active_leases ?? '∞'}`, ''),
            metricCard('Parties', `${data.usage.parties} / ${limits.parties ?? '∞'}`, ''),
            metricCard('Emails this month', `${data.usage.emails_this_month} / ${limits.emails_per_month ?? '∞'}`, ''),
        ].join('');
    }

    const licenses = document.getElementById('admin-detail-licenses');

    if (licenses) {
        licenses.innerHTML = data.licenses.length === 0
            ? '<tr><td colspan="6" class="px-3 py-5 text-sm text-[var(--pm-text-muted)]">No licences issued — the organisation runs on trial/Free rules.</td></tr>'
            : data.licenses.map(
                (license) => `
                    <tr>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 font-medium text-[var(--pm-text)]">${escapeHtml(license.plan)}</td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">${escapeHtml(license.starts_on ?? '')}</td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">${escapeHtml(license.expires_on ?? 'never')}</td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-muted)]">
                            ${license.amount !== null ? escapeHtml(`${license.amount} ${license.currency ?? ''} · ${license.payment_method ?? ''} ${license.payment_reference ?? ''}`) : '—'}
                        </td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3">
                            ${
                                license.revoked_at
                                    ? '<span class="text-[var(--pm-danger,#b3261e)]">revoked</span>'
                                    : license.covers_today
                                        ? '<span class="font-semibold text-[var(--pm-accent)]">current</span>'
                                        : '<span class="text-[var(--pm-text-muted)]">inactive</span>'
                            }
                        </td>
                        <td class="border-b border-[var(--pm-border)] px-3 py-3 text-right">
                            ${
                                license.revoked_at || ! license.covers_today
                                    ? ''
                                    : `<button type="button" class="text-sm font-medium text-[var(--pm-danger,#b3261e)]" data-admin-revoke="${license.id}">Revoke</button>`
                            }
                        </td>
                    </tr>
                `
            ).join('');
    }

    const users = document.getElementById('admin-detail-users');

    if (users) {
        users.innerHTML = data.users.map(
            (user) => `
                <tr>
                    <td class="border-b border-[var(--pm-border)] px-3 py-3 font-medium text-[var(--pm-text)]">${escapeHtml(user.name)}</td>
                    <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">${escapeHtml(user.email)}</td>
                    <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-secondary)]">${escapeHtml(user.role)}</td>
                    <td class="border-b border-[var(--pm-border)] px-3 py-3 text-[var(--pm-text-muted)]">
                        ${user.is_active ? 'active' : 'inactive'}${user.email_verified ? '' : ' · unverified'}
                    </td>
                    <td class="border-b border-[var(--pm-border)] px-3 py-3">
                        <span class="flex flex-wrap gap-3 text-sm font-medium">
                            ${user.email_verified ? '' : `<button type="button" class="text-[var(--pm-accent)]" data-admin-reverify="${user.id}">Resend verification</button>`}
                            <button type="button" class="text-[var(--pm-accent)]" data-admin-toggle="${user.id}" data-admin-toggle-to="${user.is_active ? '0' : '1'}">
                                ${user.is_active ? 'Deactivate' : 'Reactivate'}
                            </button>
                            <button type="button" class="text-[var(--pm-accent)]" data-admin-pwreset="${user.id}">Password reset</button>
                        </span>
                    </td>
                </tr>
            `
        ).join('');
    }
}

function backToOverview() {
    currentOrganisation = null;

    document.getElementById('admin-detail')?.classList.add('hidden');
    document.getElementById('admin-overview')?.classList.remove('hidden');
}

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

async function submitLicense(event) {
    event.preventDefault();

    if (! currentOrganisation) {
        return;
    }

    clearError();

    try {
        await adminRequest('/api/admin/licenses', 'POST', {
            organisation_id: currentOrganisation.id,
            plan: document.getElementById('admin-license-plan').value,
            starts_on: document.getElementById('admin-license-starts').value,
            expires_on: document.getElementById('admin-license-expires').value || null,
            amount: document.getElementById('admin-license-amount').value === ''
                ? null
                : Number(document.getElementById('admin-license-amount').value),
            currency: document.getElementById('admin-license-currency').value.trim() || null,
            payment_method: document.getElementById('admin-license-method').value || null,
            payment_reference: document.getElementById('admin-license-reference').value.trim() || null,
            notes: document.getElementById('admin-license-notes').value.trim() || null,
        });

        closeDrawer('admin-license-modal');

        await openOrganisation(currentOrganisation.id);
    } catch (error) {
        showError(error instanceof Error ? error.message : 'Unable to issue the licence.');

        closeDrawer('admin-license-modal');
    }
}

async function submitSuspend(event) {
    event.preventDefault();

    if (! currentOrganisation) {
        return;
    }

    clearError();

    try {
        await adminRequest(
            `/api/admin/organisations/${currentOrganisation.id}/suspend`,
            'POST',
            {
                reason: document.getElementById('admin-suspend-reason').value.trim() || null,
            }
        );

        closeDrawer('admin-suspend-modal');

        await openOrganisation(currentOrganisation.id);
    } catch (error) {
        showError(error instanceof Error ? error.message : 'Unable to suspend.');

        closeDrawer('admin-suspend-modal');
    }
}

async function submitDelete(event) {
    event.preventDefault();

    if (! currentOrganisation) {
        return;
    }

    clearError();

    try {
        await adminRequest(
            `/api/admin/organisations/${currentOrganisation.id}`,
            'DELETE',
            {
                name_confirmation: document.getElementById('admin-delete-name').value,
                password: document.getElementById('admin-delete-password').value,
            }
        );

        closeDrawer('admin-delete-modal');

        backToOverview();

        await Promise.all([loadDashboard(), loadOrganisations()]);
    } catch (error) {
        showError(error instanceof Error ? error.message : 'Unable to delete.');
    } finally {
        document.getElementById('admin-delete-password').value = '';
    }
}

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

/**
 * Initialize the platform console when its page is present.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeAdmin() {
    const workspace = document.getElementById('admin-workspace');

    if (! workspace) {
        return false;
    }

    if (document.body.dataset.platformAdmin !== 'true') {
        window.location.replace('/dashboard');

        return true;
    }

    wireDrawer('admin-license-modal', {
        closers: ['admin-license-close', 'admin-license-cancel', 'admin-license-backdrop'],
    });

    wireDrawer('admin-suspend-modal', {
        closers: ['admin-suspend-close', 'admin-suspend-cancel', 'admin-suspend-backdrop'],
    });

    wireDrawer('admin-delete-modal', {
        closers: ['admin-delete-close', 'admin-delete-cancel', 'admin-delete-backdrop'],
    });

    document.getElementById('admin-license-form')
        ?.addEventListener('submit', submitLicense);

    document.getElementById('admin-suspend-form')
        ?.addEventListener('submit', submitSuspend);

    document.getElementById('admin-delete-form')
        ?.addEventListener('submit', submitDelete);

    document.getElementById('admin-back')
        ?.addEventListener('click', backToOverview);

    document.getElementById('admin-issue-license')
        ?.addEventListener('click', () => {
            if (! currentOrganisation) {
                return;
            }

            document.getElementById('admin-license-org').textContent =
                currentOrganisation.name;

            document.getElementById('admin-license-starts').value =
                new Date().toISOString().slice(0, 10);

            openDrawer('admin-license-modal');
        });

    document.getElementById('admin-suspend')
        ?.addEventListener('click', () => {
            if (! currentOrganisation) {
                return;
            }

            document.getElementById('admin-suspend-org').textContent =
                currentOrganisation.name;

            openDrawer('admin-suspend-modal');
        });

    document.getElementById('admin-reactivate')
        ?.addEventListener('click', async () => {
            if (! currentOrganisation) {
                return;
            }

            clearError();

            try {
                await adminRequest(
                    `/api/admin/organisations/${currentOrganisation.id}/reactivate`,
                    'POST',
                    {}
                );

                await openOrganisation(currentOrganisation.id);
            } catch (error) {
                showError(error instanceof Error ? error.message : 'Unable to reactivate.');
            }
        });

    document.getElementById('admin-delete')
        ?.addEventListener('click', () => {
            if (! currentOrganisation) {
                return;
            }

            document.getElementById('admin-delete-org').textContent =
                currentOrganisation.name;

            document.getElementById('admin-delete-name').value = '';
            document.getElementById('admin-delete-password').value = '';

            openDrawer('admin-delete-modal');
        });

    document.getElementById('admin-search')
        ?.addEventListener('input', () => {
            clearTimeout(searchDebounce);

            searchDebounce = setTimeout(
                () => loadOrganisations(1),
                300
            );
        });

    document.getElementById('admin-status-filter')
        ?.addEventListener('change', () => loadOrganisations(1));

    /*
     * One delegated click handler for every dynamic control.
     */
    workspace.addEventListener('click', async (event) => {
        const target = event.target.closest(
            '[data-admin-open], [data-admin-page], [data-admin-revoke], '
            + '[data-admin-reverify], [data-admin-toggle], [data-admin-pwreset]'
        );

        if (! target) {
            return;
        }

        clearError();

        try {
            if (target.dataset.adminOpen) {
                await openOrganisation(Number(target.dataset.adminOpen));
            } else if (target.dataset.adminPage) {
                await loadOrganisations(Number(target.dataset.adminPage));
            } else if (target.dataset.adminRevoke) {
                await adminRequest(
                    `/api/admin/licenses/${target.dataset.adminRevoke}/revoke`,
                    'POST',
                    {}
                );

                await openOrganisation(currentOrganisation.id);
            } else if (target.dataset.adminReverify) {
                await adminRequest(
                    `/api/admin/users/${target.dataset.adminReverify}/resend-verification`,
                    'POST',
                    {}
                );

                target.textContent = 'Sent ✓';
            } else if (target.dataset.adminToggle) {
                await adminRequest(
                    `/api/admin/users/${target.dataset.adminToggle}/active`,
                    'PATCH',
                    {
                        is_active: target.dataset.adminToggleTo === '1',
                    }
                );

                await openOrganisation(currentOrganisation.id);
            } else if (target.dataset.adminPwreset) {
                await adminRequest(
                    `/api/admin/users/${target.dataset.adminPwreset}/password-reset`,
                    'POST',
                    {}
                );

                target.textContent = 'Sent ✓';
            }
        } catch (error) {
            showError(error instanceof Error ? error.message : 'The action failed.');
        }
    });

    try {
        await Promise.all([loadDashboard(), loadOrganisations()]);
    } catch (error) {
        showError(error instanceof Error ? error.message : 'Unable to load the console.');
    }

    return true;
}
