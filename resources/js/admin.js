/*
|--------------------------------------------------------------------------
| Patrimoine Platform Administration Console (V1.0.11)
|--------------------------------------------------------------------------
|
| Kality Ltd staff only. The console runs in its own shell (no customer
| navigation): Dashboard, Organizations, Licenses, Activity and
| Settings, plus the organisation drill-down, all driven from
| /api/admin/*.
|
| The page performs its own authentication bootstrap: it is not part of
| the customer application shell. Internal tool: deliberately
| English-only.
|
*/

import {
    apiRequest,
    clearToken,
    closeDrawer,
    escapeHtml,
    formatNumber,
    initials,
    openDrawer,
    parseJsonResponse,
    token,
    wireDrawer,
} from './core.js';

import {
    getThemePreference,
    setThemePreference,
} from './theme.js';

let currentOrganisation = null;

let organisationOptions = [];

let searchDebounce = null;

let currentSection = 'dashboard';

const SECTIONS = [
    'dashboard',
    'organizations',
    'licenses',
    'activity',
    'settings',
    'organisation',
];

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
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
        <span class="pm-admin-status ${active ? 'pm-admin-status-active' : 'pm-admin-status-suspended'}">
            ${active ? 'Active' : 'Suspended'}
        </span>
    `;
}

function planBadge(plan, onTrial) {
    const label = plan === 'professional'
        ? 'Pro'
        : plan.charAt(0).toUpperCase() + plan.slice(1);

    return `
        <span class="pm-admin-plan-badge pm-admin-plan-${escapeHtml(plan)}">${escapeHtml(label)}</span>
        ${onTrial ? '<span class="ml-1 text-xs text-[var(--pm-text-muted)]">trial</span>' : ''}
    `;
}

function orgCell(org) {
    return `
        <span class="flex items-center gap-3">
            <span class="pm-admin-org-avatar">${escapeHtml(initials(org.name))}</span>
            <span class="min-w-0">
                <span class="block font-medium text-[var(--pm-text)]">${escapeHtml(org.name)}</span>
                <span class="block text-xs text-[var(--pm-text-muted)]">${escapeHtml(accountNumber(org.id))}</span>
            </span>
        </span>
    `;
}

function accountNumber(id) {
    return 'ORG-' + String(id).padStart(6, '0');
}

function metricCard(label, value, hint) {
    return `
        <div class="pm-admin-card">
            <div class="text-sm font-medium text-[var(--pm-text-muted)]">
                ${escapeHtml(label)}
            </div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-[var(--pm-text)]">
                ${escapeHtml(String(value))}
            </div>
            ${hint ? `<div class="mt-2 text-sm text-[var(--pm-text-muted)]">${escapeHtml(hint)}</div>` : ''}
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| Section router
|--------------------------------------------------------------------------
*/

function showSection(name) {
    currentSection = name;

    for (const section of SECTIONS) {
        const element = document.getElementById('admin-section-' + section);

        if (element) {
            element.hidden = section !== name;
        }
    }

    document.querySelectorAll('[data-admin-nav]').forEach((item) => {
        item.classList.toggle(
            'pm-admin-nav-active',
            item.dataset.adminNav === name
            || (name === 'organisation' && item.dataset.adminNav === 'organizations')
        );
    });

    if (name !== 'organisation') {
        window.location.hash = name;
    }

    clearError();
}

async function navigate(name) {
    showSection(name);

    try {
        if (name === 'dashboard') {
            await loadDashboard();
        } else if (name === 'organizations') {
            await loadOrganisations();
        } else if (name === 'licenses') {
            await Promise.all([loadLicenseMetrics(), loadSubscriptions()]);
        } else if (name === 'activity') {
            await loadActivity();
        } else if (name === 'settings') {
            await loadStaff();
        }
    } catch (error) {
        showError(error instanceof Error ? error.message : 'Unable to load this page.');
    }
}

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

async function loadDashboard() {
    const data = await adminRequest('/api/admin/dashboard', 'GET');

    const metrics = document.getElementById('admin-metrics');

    if (metrics) {
        metrics.innerHTML = [
            metricCard('Organizations', data.totals.organisations, `${data.totals.suspended} suspended`),
            metricCard('On trial', data.totals.on_trial, 'Professional trial running'),
            metricCard('Licensed', data.totals.licensed, `${data.totals.plans.standard} Standard · ${data.totals.plans.professional} Pro`),
            metricCard('Signups this month', data.signups_this_month, ''),
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
| Organisations
|--------------------------------------------------------------------------
*/

async function fetchOrganisations(page, search, status) {
    const params = new URLSearchParams({ page: String(page) });

    if (search) {
        params.set('search', search);
    }

    if (status) {
        params.set('status', status);
    }

    const data = await adminRequest(
        '/api/admin/organisations?' + params.toString(),
        'GET'
    );

    /*
     * Keep the assign-licence organisation picker warm with the most
     * recently listed customers.
     */
    for (const org of data.data) {
        if (! organisationOptions.some((option) => option.id === org.id)) {
            organisationOptions.push({ id: org.id, name: org.name });
        }
    }

    return data;
}

async function loadOrganisations(page = 1) {
    const search = document.getElementById('admin-search')?.value.trim() ?? '';

    const status = document.getElementById('admin-status-filter')?.value ?? '';

    const data = await fetchOrganisations(page, search, status);

    const body = document.getElementById('admin-orgs-body');

    if (body) {
        body.innerHTML = data.data.length === 0
            ? '<tr><td colspan="6" class="px-3 py-6 text-sm text-[var(--pm-text-muted)]">No organizations found.</td></tr>'
            : data.data.map(
                (org) => `
                    <tr class="pm-admin-row-click" data-admin-open="${org.id}">
                        <td>${orgCell(org)}</td>
                        <td class="text-[var(--pm-text-muted)]">${escapeHtml(accountNumber(org.id))}</td>
                        <td>${formatNumber(org.usage.users)}</td>
                        <td>${formatNumber(org.usage.active_leases)}</td>
                        <td>${planBadge(org.plan, org.on_trial)}</td>
                        <td>${statusBadge(org.status)}</td>
                    </tr>
                `
            ).join('');
    }

    document.getElementById('admin-orgs-count').textContent =
        `${formatNumber(data.meta.total)} result(s)`;

    renderPagination(
        document.getElementById('admin-pagination'),
        data.meta,
        'data-admin-page'
    );
}

function renderPagination(container, meta, attribute) {
    if (! container) {
        return;
    }

    const { current_page: current, last_page: last, total } = meta;

    container.innerHTML = `
        <span class="text-[var(--pm-text-muted)]">Showing page ${current} of ${last} · ${formatNumber(total)} total</span>
        <span class="flex items-center gap-2">
            <button type="button" class="pm-button-secondary ${current <= 1 ? 'invisible' : ''}" ${attribute}="${current - 1}">←</button>
            <button type="button" class="pm-button-secondary ${current >= last ? 'invisible' : ''}" ${attribute}="${current + 1}">→</button>
        </span>
    `;
}

/*
|--------------------------------------------------------------------------
| Licenses (subscription overview)
|--------------------------------------------------------------------------
*/

async function loadLicenseMetrics() {
    const data = await adminRequest('/api/admin/dashboard', 'GET');

    const metrics = document.getElementById('admin-license-metrics');

    if (metrics) {
        metrics.innerHTML = [
            metricCard('Licensed organizations', data.totals.licensed, 'Covered by an assigned license'),
            metricCard('On trial', data.totals.on_trial, 'Will fall to Free unless licensed'),
            metricCard('Expiring in 14 days', data.expiring_soon.length, 'Trials and licenses'),
        ].join('');
    }
}

async function loadSubscriptions(page = 1) {
    const search =
        document.getElementById('admin-license-search')?.value.trim() ?? '';

    const data = await fetchOrganisations(page, search, '');

    const body = document.getElementById('admin-licenses-body');

    if (body) {
        body.innerHTML = data.data.length === 0
            ? '<tr><td colspan="6" class="px-3 py-6 text-sm text-[var(--pm-text-muted)]">No organizations found.</td></tr>'
            : data.data.map(
                (org) => {
                    const period = org.current_license
                        ? `${org.current_license.starts_on} → ${org.current_license.expires_on ?? 'never'}`
                        : org.on_trial
                            ? `trial → ${org.trial_ends_on}`
                            : '—';

                    const consumption = `
                        ${formatNumber(org.usage.active_leases)} / ${org.limits.active_leases ?? '∞'} leases
                        · ${formatNumber(org.usage.users)} / ${org.limits.users ?? '∞'} users
                        · ${formatNumber(org.usage.emails_this_month)} / ${org.limits.emails_per_month ?? '∞'} emails
                    `;

                    return `
                        <tr class="pm-admin-row-click" data-admin-open="${org.id}">
                            <td>${orgCell(org)}</td>
                            <td>${planBadge(org.plan, org.on_trial)}</td>
                            <td class="whitespace-nowrap text-[var(--pm-text-muted)]">${escapeHtml(period)}</td>
                            <td class="text-xs text-[var(--pm-text-muted)]">${consumption}</td>
                            <td>${statusBadge(org.status)}</td>
                            <td class="text-right">
                                <button type="button" class="text-sm font-medium text-[var(--pm-accent)]" data-admin-assign-org="${org.id}">
                                    Assign
                                </button>
                            </td>
                        </tr>
                    `;
                }
            ).join('');
    }

    renderPagination(
        document.getElementById('admin-licenses-pagination'),
        data.meta,
        'data-admin-licpage'
    );
}

/*
|--------------------------------------------------------------------------
| Activity
|--------------------------------------------------------------------------
*/

const ACTION_LABELS = {
    'platform.license_issued': 'License assigned',
    'platform.license_revoked': 'License revoked',
    'platform.organisation_suspended': 'Organization suspended',
    'platform.organisation_reactivated': 'Organization reactivated',
    'platform.organisation_deleted': 'Organization deleted',
    'platform.verification_resent': 'Verification email resent',
    'platform.user_deactivated': 'Customer user deactivated',
    'platform.user_reactivated': 'Customer user reactivated',
    'platform.password_reset_sent': 'Password reset sent',
    'auth.login': 'Signed in',
    'auth.logout': 'Signed out',
    'auth.login_failed': 'Failed sign-in',
    'user.created': 'Staff user created',
    'user.updated': 'Staff user updated',
};

async function loadActivity(page = 1) {
    const data = await adminRequest(
        '/api/admin/activity?page=' + page,
        'GET'
    );

    const body = document.getElementById('admin-activity-body');

    if (body) {
        body.innerHTML = data.data.length === 0
            ? '<tr><td colspan="5" class="px-3 py-6 text-sm text-[var(--pm-text-muted)]">No activity yet.</td></tr>'
            : data.data.map(
                (event) => `
                    <tr>
                        <td class="whitespace-nowrap text-[var(--pm-text-muted)]">${escapeHtml(event.created_at ?? '')}</td>
                        <td class="font-medium text-[var(--pm-text)]">${escapeHtml(event.actor ?? '—')}</td>
                        <td>${escapeHtml(ACTION_LABELS[event.action] ?? event.action)}</td>
                        <td>${escapeHtml(event.customer_organisation ?? '—')}</td>
                        <td class="text-[var(--pm-text-muted)]">${escapeHtml(event.entity_label ?? '')}</td>
                    </tr>
                `
            ).join('');
    }

    renderPagination(
        document.getElementById('admin-activity-pagination'),
        data.meta,
        'data-admin-actpage'
    );
}

/*
|--------------------------------------------------------------------------
| Settings (team access)
|--------------------------------------------------------------------------
*/

async function loadStaff() {
    const data = await adminRequest('/api/users', 'GET');

    const staff = data.data ?? data;

    const body = document.getElementById('admin-staff-body');

    if (body) {
        body.innerHTML = staff.map(
            (user) => `
                <tr>
                    <td class="font-medium text-[var(--pm-text)]">${escapeHtml(user.name)}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td class="text-[var(--pm-text-muted)]">${escapeHtml(String(user.role))}</td>
                    <td class="text-[var(--pm-text-muted)]">${user.is_active ? 'active' : 'inactive'}</td>
                </tr>
            `
        ).join('');
    }
}

async function submitStaffInvite(event) {
    event.preventDefault();

    clearError();

    try {
        await adminRequest('/api/users', 'POST', {
            name: document.getElementById('admin-staff-name').value.trim(),
            email: document.getElementById('admin-staff-email').value.trim(),
            role: document.getElementById('admin-staff-role').value,
        });

        closeDrawer('admin-staff-modal');

        await loadStaff();
    } catch (error) {
        closeDrawer('admin-staff-modal');

        showError(error instanceof Error ? error.message : 'Unable to send the invitation.');
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

    showSection('organisation');

    document.getElementById('admin-detail-name').textContent =
        data.organisation.name;

    document.getElementById('admin-detail-status').innerHTML =
        statusBadge(data.organisation.status);

    document.getElementById('admin-detail-meta').textContent =
        `${accountNumber(data.organisation.id)} · ${data.organisation.plan}`
        + `${data.organisation.on_trial ? ' (trial until ' + data.organisation.trial_ends_on + ')' : ''}`
        + ` · signed up ${String(data.organisation.created_at ?? '').slice(0, 10)}`;

    document.getElementById('admin-suspend')?.classList.toggle(
        'hidden',
        data.organisation.status !== 'active'
    );

    document.getElementById('admin-reactivate')?.classList.toggle(
        'hidden',
        data.organisation.status !== 'suspended'
    );

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
            ? '<tr><td colspan="6" class="px-3 py-5 text-sm text-[var(--pm-text-muted)]">No licenses assigned — the organization runs on trial/Free rules.</td></tr>'
            : data.licenses.map(
                (license) => `
                    <tr>
                        <td>${planBadge(license.plan, false)}</td>
                        <td>${escapeHtml(license.starts_on ?? '')}</td>
                        <td>${escapeHtml(license.expires_on ?? 'never')}</td>
                        <td class="text-[var(--pm-text-muted)]">
                            ${license.amount !== null ? escapeHtml(`${license.amount} ${license.currency ?? ''} · ${license.payment_method ?? ''} ${license.payment_reference ?? ''}`) : '—'}
                        </td>
                        <td>
                            ${
                                license.revoked_at
                                    ? '<span class="text-[var(--pm-danger,#b3261e)]">revoked</span>'
                                    : license.covers_today
                                        ? '<span class="font-semibold text-[var(--pm-accent)]">current</span>'
                                        : '<span class="text-[var(--pm-text-muted)]">inactive</span>'
                            }
                        </td>
                        <td class="text-right">
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
                    <td class="font-medium text-[var(--pm-text)]">${escapeHtml(user.name)}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td class="text-[var(--pm-text-muted)]">${escapeHtml(String(user.role))}</td>
                    <td class="text-[var(--pm-text-muted)]">
                        ${user.is_active ? 'active' : 'inactive'}${user.email_verified ? '' : ' · unverified'}
                    </td>
                    <td>
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

/*
|--------------------------------------------------------------------------
| Assign licence
|--------------------------------------------------------------------------
*/

async function openAssignDrawer(preselectedOrganisationId = null) {
    /*
     * Make sure the picker has choices even before the organisation
     * list was ever visited.
     */
    if (organisationOptions.length === 0) {
        try {
            await fetchOrganisations(1, '', '');
        } catch {
            // The drawer still opens; the picker will just be empty.
        }
    }

    const select = document.getElementById('admin-license-organisation');

    if (select) {
        select.innerHTML =
            '<option value="">Select an organization</option>'
            + organisationOptions
                .map(
                    (option) =>
                        `<option value="${option.id}">${escapeHtml(option.name)}</option>`
                )
                .join('');

        if (preselectedOrganisationId !== null) {
            select.value = String(preselectedOrganisationId);
        }
    }

    document.getElementById('admin-license-starts').value =
        new Date().toISOString().slice(0, 10);

    openDrawer('admin-license-modal');
}

async function submitLicense(event) {
    event.preventDefault();

    clearError();

    const organisationId = Number(
        document.getElementById('admin-license-organisation').value
    );

    if (! organisationId) {
        return;
    }

    try {
        await adminRequest('/api/admin/licenses', 'POST', {
            organisation_id: organisationId,
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

        if (currentSection === 'organisation' && currentOrganisation) {
            await openOrganisation(currentOrganisation.id);
        } else {
            await navigate(currentSection);
        }
    } catch (error) {
        closeDrawer('admin-license-modal');

        showError(error instanceof Error ? error.message : 'Unable to assign the license.');
    }
}

/*
|--------------------------------------------------------------------------
| Suspend / delete
|--------------------------------------------------------------------------
*/

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
        closeDrawer('admin-suspend-modal');

        showError(error instanceof Error ? error.message : 'Unable to suspend.');
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

        await navigate('organizations');
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
 * Performs its own authentication: the admin shell is separate from
 * the customer application shell.
 *
 * @returns {Promise<boolean>}
 */
export async function initializeAdmin() {
    const workspace = document.getElementById('admin-workspace');

    if (! workspace) {
        return false;
    }

    if (! token()) {
        window.location.replace('/login');

        return true;
    }

    let user = null;

    try {
        const response = await apiRequest('/api/auth/me');

        user = await parseJsonResponse(response);
    } catch {
        clearToken();

        window.location.replace('/login');

        return true;
    }

    if (! user?.is_platform_admin) {
        window.location.replace('/dashboard');

        return true;
    }

    /*
     * Sidebar identity + sign-out.
     */
    document.getElementById('admin-user-name').textContent = user.name ?? '';

    document.getElementById('admin-user-avatar').textContent =
        initials(user.name ?? '');

    document.getElementById('admin-logout')
        ?.addEventListener('click', async () => {
            try {
                await apiRequest('/api/auth/logout', { method: 'POST' });
            } catch {
                // Local sign-out proceeds regardless.
            }

            clearToken();

            window.location.replace('/login');
        });

    /*
     * Theme toggle: light ↔ dark.
     */
    document.getElementById('admin-theme-toggle')
        ?.addEventListener('click', () => {
            const current = getThemePreference();

            setThemePreference(current === 'dark' ? 'light' : 'dark');
        });

    /*
     * Global search: routes to the Organizations page and filters it.
     */
    const globalSearch = document.getElementById('admin-global-search');

    globalSearch?.addEventListener('input', () => {
        clearTimeout(searchDebounce);

        searchDebounce = setTimeout(async () => {
            const query = globalSearch.value.trim();

            const localSearch = document.getElementById('admin-search');

            if (localSearch) {
                localSearch.value = query;
            }

            if (currentSection !== 'organizations') {
                showSection('organizations');
            }

            try {
                await loadOrganisations(1);
            } catch (error) {
                showError(error instanceof Error ? error.message : 'Search failed.');
            }
        }, 300);
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();

            globalSearch?.focus();
        }
    });

    /*
     * Sidebar navigation.
     */
    document.querySelectorAll('[data-admin-nav]').forEach((item) => {
        item.addEventListener('click', () => navigate(item.dataset.adminNav));
    });

    /*
     * Drawers.
     */
    wireDrawer('admin-license-modal', {
        closers: ['admin-license-close', 'admin-license-cancel', 'admin-license-backdrop'],
    });

    wireDrawer('admin-suspend-modal', {
        closers: ['admin-suspend-close', 'admin-suspend-cancel', 'admin-suspend-backdrop'],
    });

    wireDrawer('admin-delete-modal', {
        closers: ['admin-delete-close', 'admin-delete-cancel', 'admin-delete-backdrop'],
    });

    wireDrawer('admin-staff-modal', {
        closers: ['admin-staff-close', 'admin-staff-cancel', 'admin-staff-backdrop'],
    });

    document.getElementById('admin-license-form')
        ?.addEventListener('submit', submitLicense);

    document.getElementById('admin-suspend-form')
        ?.addEventListener('submit', submitSuspend);

    document.getElementById('admin-delete-form')
        ?.addEventListener('submit', submitDelete);

    document.getElementById('admin-staff-form')
        ?.addEventListener('submit', submitStaffInvite);

    /*
     * Top-bar Assign License (no organisation preselected).
     */
    document.getElementById('admin-assign-license')
        ?.addEventListener('click', () => openAssignDrawer());

    document.getElementById('admin-invite-staff')
        ?.addEventListener('click', () => openDrawer('admin-staff-modal'));

    /*
     * Detail-page actions.
     */
    document.getElementById('admin-back')
        ?.addEventListener('click', () => navigate('organizations'));

    document.getElementById('admin-issue-license')
        ?.addEventListener('click', () => {
            if (currentOrganisation) {
                openAssignDrawer(currentOrganisation.id);
            }
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

    /*
     * Section-local filters.
     */
    document.getElementById('admin-search')
        ?.addEventListener('input', () => {
            clearTimeout(searchDebounce);

            searchDebounce = setTimeout(() => loadOrganisations(1), 300);
        });

    document.getElementById('admin-status-filter')
        ?.addEventListener('change', () => loadOrganisations(1));

    document.getElementById('admin-license-search')
        ?.addEventListener('input', () => {
            clearTimeout(searchDebounce);

            searchDebounce = setTimeout(() => loadSubscriptions(1), 300);
        });

    /*
     * One delegated click handler for every dynamic control.
     */
    workspace.addEventListener('click', async (event) => {
        const target = event.target.closest(
            '[data-admin-open], [data-admin-page], [data-admin-licpage], '
            + '[data-admin-actpage], [data-admin-revoke], [data-admin-assign-org], '
            + '[data-admin-assign], [data-admin-reverify], [data-admin-toggle], '
            + '[data-admin-pwreset]'
        );

        if (! target) {
            return;
        }

        clearError();

        try {
            if (target.dataset.adminAssignOrg !== undefined) {
                event.stopPropagation();

                await openAssignDrawer(Number(target.dataset.adminAssignOrg));
            } else if (target.dataset.adminAssign !== undefined) {
                await openAssignDrawer();
            } else if (target.dataset.adminOpen) {
                await openOrganisation(Number(target.dataset.adminOpen));
            } else if (target.dataset.adminPage) {
                await loadOrganisations(Number(target.dataset.adminPage));
            } else if (target.dataset.adminLicpage) {
                await loadSubscriptions(Number(target.dataset.adminLicpage));
            } else if (target.dataset.adminActpage) {
                await loadActivity(Number(target.dataset.adminActpage));
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

    /*
     * Land on the hash-requested page, defaulting to the dashboard.
     */
    const requested = window.location.hash.replace('#', '');

    await navigate(
        SECTIONS.includes(requested) && requested !== 'organisation'
            ? requested
            : 'dashboard'
    );

    return true;
}
