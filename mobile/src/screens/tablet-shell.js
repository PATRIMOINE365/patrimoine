/*
 * The iPad client - the full product.
 *
 * The sidebar MIRRORS THE WEB APPLICATION entry for entry, group for
 * group: Workspace (Dashboard, Properties, Parties, Leases), Finance
 * (Tenants, Owners, Accounting, Reports), Administration (Settings,
 * Audit, Archive) - with the web's own icons, from the same icon file, and
 * the same capability gates. The top bar carries what the web's carries:
 * the organisation, the date, Refresh, the bell and the avatar menu with
 * My Profile, Appearance, Support and Sign out.
 *
 * Every screen is the browser page rebuilt for touch; nothing here is a
 * WebView of it. Only the platform console is left out: it is staff-only.
 */

import { el, mount } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { session } from '../auth/session.js';
import * as store from '../data/store.js';
import { App as CapacitorApp } from '@capacitor/app';
import { brandMark } from '../ui/brand.js';
import { attachPullToRefresh } from '../ui/pull-refresh.js';
import { setUser, can, isPlatformAdmin } from '../auth/capabilities.js';
import { setThemePreference, themePreference } from '../ui/theme.js';
import { formatLongDate, isoToday } from '../ui/format.js';
import { setCurrency } from '../ui/money.js';
import { confirmSheet } from '../ui/sheet.js';
import { avatar, roleLabel, partyName } from './common.js';
import { dashboardScreen } from './dashboard.js';
import { propertiesScreen } from './properties.js';
import { partiesScreen } from './parties.js';
import { leasesScreen } from './leases.js';
import { tenantsScreen } from './tenants.js';
import { ownersScreen } from './owners.js';
import { accountingScreen } from './accounting.js';
import { reportsScreen } from './reports.js';
import { settingsScreen } from './settings.js';
import { auditScreen } from './audit.js';
import { archiveScreen } from './archive.js';
import { helpScreen } from './help.js';
import { profileSheet } from './profile.js';
import { notificationsPanel } from './notifications.js';
import { leaseWizard } from './lease-wizard.js';

const REFRESH_INTERVAL = 5 * 60 * 1000;

const GROUPS = [
    { label: 'ui.navigation.workspace', items: [
        { id: 'dashboard', label: 'ui.navigation.dashboard', icon: 'grid-01' },
        { id: 'properties', label: 'ui.navigation.properties', icon: 'building-02' },
        { id: 'parties', label: 'ui.navigation.parties', icon: 'users-03' },
        { id: 'leases', label: 'ui.navigation.leases', icon: 'file-check' },
    ] },
    { label: 'ui.navigation.finance', items: [
        { id: 'tenants', label: 'ui.navigation.tenants', icon: 'users-01' },
        { id: 'owners', label: 'ui.navigation.owners', icon: 'user-check' },
        { id: 'accounting', label: 'ui.navigation.accounting', icon: 'calculator', capability: 'view_financial_journal' },
        { id: 'reports', label: 'ui.navigation.reports', icon: 'bar-chart-square' },
    ] },
    { label: 'ui.navigation.manage', items: [
        { id: 'settings', label: 'ui.navigation.settings', icon: 'settings-01', capability: 'manage_settings' },
        { id: 'audit', label: 'ui.navigation.audit', icon: 'clock-rewind', capability: 'view_activity_log' },
        { id: 'archive', label: 'ui.navigation.archive', icon: 'archive' },
    ] },
];

export function tabletShell(root, { client, config, version = '1.0.0', apiBase = '', onSignedOut }) {
    let currentId = 'dashboard';
    let currentScreen = null;
    let timer = null;
    let popover = null;

    const me = store.read('me').data ?? {};
    let user = me.user ?? me;

    setUser(user);

    const content = el('div', { class: 'workspace-scroll' });
    const navItems = new Map();

    /* ---------------------------------------------------------- nav */

    const nav = el('nav', { class: 'sidebar-nav' }, GROUPS.flatMap((group) => {
        const items = group.items.filter((item) => ! item.capability || can(item.capability));

        if (items.length === 0) {
            return [];
        }

        return [
            el('div', { class: 'sidebar-group', text: t(group.label) }),
            ...items.map((item) => {
                const node = el('button', { class: 'nav-item', onclick: () => open(item.id) }, [
                    icon(item.icon, { size: 20 }),
                    el('span', { class: 'nav-label', text: t(item.label) }),
                ]);

                navItems.set(item.id, node);

                return node;
            }),
        ];
    }));

    if (isPlatformAdmin()) {
        nav.append(el('button', { class: 'nav-item', onclick: () => window.open(`${apiBase.replace(/\/api(\/v\d+)?\/?$/, '')}/admin`, '_blank') }, [
            icon('shield-tick', { size: 20 }),
            el('span', { class: 'nav-label', text: t('ui.navigation.platform_console') }),
        ]));
    }

    function userChip() {
        return el('button', { class: 'user-chip', onclick: (event) => toggleMenu(event.currentTarget) }, [
            avatar(user),
            el('span', { class: 'user-words' }, [
                el('span', { class: 'user-name', text: user.name ?? '' }),
                el('span', { class: 'user-role', text: roleLabel(user.role) }),
            ]),
            icon('chevron-down', { size: 16, class: 'user-chevron' }),
        ]);
    }

    const chipHost = el('div');

    const sidebar = el('aside', { class: 'sidebar' }, [
        el('div', { class: 'sidebar-head' }, [
            el('div', { class: 'brand' }, [
                el('div', { class: 'brand-tile' }, [brandMark(22)]),
                el('div', { class: 'brand-words' }, [
                    el('span', { class: 'brand-name' }, [el('strong', { text: 'Patrimoine' }), el('span', { class: 'brand-365', text: ' 365' })]),
                ]),
            ]),
        ]),
        nav,
        el('div', { class: 'sidebar-foot' }, [
            el('button', { class: 'nav-item', onclick: () => open('help') }, [icon('life-buoy', { size: 20 }), el('span', { class: 'nav-label', text: t('ui.shell.support') })]),
            el('button', { class: 'nav-item is-danger', onclick: signOutAsk }, [icon('log-out-01', { size: 20 }), el('span', { class: 'nav-label', text: t('ui.navigation.sign_out') })]),
            chipHost,
        ]),
    ]);

    mount(chipHost, userChip());

    /* ------------------------------------------------------- top bar */

    const orgName = el('span', { class: 'topbar-org', text: '' });
    const bell = el('button', { class: 'icon-button', 'aria-label': t('ui.shell.notifications'), onclick: (event) => toggleBell(event.currentTarget) }, [icon('bell-01', { size: 20 })]);
    const refreshButton = el('button', { class: 'icon-button', 'aria-label': t('ui.shell.refresh'), onclick: () => refresh() }, [icon('refresh-cw', { size: 20 })]);
    const avatarButton = el('button', { class: 'icon-button', 'aria-label': t('ui.shell.my_profile'), onclick: (event) => toggleMenu(event.currentTarget) }, [avatar(user)]);

    const topbar = el('div', { class: 'topbar' }, [
        el('div', {}, [
            orgName,
            el('span', { class: 'topbar-date', style: 'display:block', text: formatLongDate(isoToday()) }),
        ]),
        el('div', { class: 'topbar-spacer' }),
        refreshButton,
        bell,
        avatarButton,
    ]);

    function paintOrganisation() {
        const organisation = store.read('organisation').data;
        const org = organisation?.data ?? organisation ?? {};

        orgName.textContent = org.legal_name ?? org.name ?? t('app.name');
        setCurrency(org.currency);
    }

    function closePopover() {
        popover?.remove();
        popover = null;
        document.removeEventListener('click', outside, true);
    }

    function outside(event) {
        if (popover && ! popover.contains(event.target)) {
            closePopover();
        }
    }

    function place(node, anchor) {
        const rect = anchor.getBoundingClientRect();

        node.style.top = `${rect.bottom + 8}px`;
        node.style.right = `${Math.max(8, window.innerWidth - rect.right)}px`;
        document.body.append(node);
        popover = node;
        setTimeout(() => document.addEventListener('click', outside, true), 0);
    }

    function toggleBell(anchor) {
        if (popover?.dataset.kind === 'bell') {
            closePopover();

            return;
        }

        closePopover();

        const panel = notificationsPanel(client, {
            onOpenSection: (id) => open(id),
            onOpenUpdates: () => open('help', { tab: 'updates' }),
            onUnread: (unread) => bell.classList.toggle('badge-dot', unread),
            onClose: closePopover,
        });

        panel.node.dataset.kind = 'bell';
        place(panel.node, anchor);
    }

    function toggleMenu(anchor) {
        if (popover?.dataset.kind === 'menu') {
            closePopover();

            return;
        }

        closePopover();

        const segmented = el('div', { class: 'segmented' }, ['light', 'dark', 'system'].map((choice) => el('button', {
            class: themePreference() === choice ? 'is-active' : '', type: 'button', text: t(`ui.shell.theme_${choice}`),
            onclick: (event) => {
                setThemePreference(choice);

                for (const b of event.currentTarget.parentNode.querySelectorAll('button')) {
                    b.classList.toggle('is-active', b === event.currentTarget);
                }
            },
        })));

        const menu = el('div', { class: 'popover', dataset: { kind: 'menu' } }, [
            el('div', { class: 'menu-section' }, [
                el('div', { class: 'inline' }, [
                    avatar(user, { size: 'lg' }),
                    el('span', { class: 'grow' }, [
                        el('span', { class: 'cell-strong', style: 'display:block', text: user.name ?? '' }),
                        el('span', { class: 'muted-small', style: 'display:block', text: user.email ?? '' }),
                        el('span', { class: 'chip chip-info', text: roleLabel(user.role) }),
                    ]),
                ]),
            ]),
            el('button', { class: 'menu-item', type: 'button', onclick: async () => {
                closePopover();
                await profileSheet(client, { onUpdated: (fresh) => { user = fresh; setUser(user); mount(chipHost, userChip()); mount(avatarButton, avatar(user)); }, onSignedOut: () => onSignedOut() });
            } }, [icon('user-01', { size: 18 }), el('span', { class: 'grow' }, [el('span', { class: 'cell-strong', style: 'display:block', text: t('ui.shell.my_profile') }), el('span', { class: 'muted-small', text: t('ui.shell.my_profile_description') })]), icon('chevron-right', { size: 16 })]),
            el('div', { class: 'menu-section' }, [el('span', { class: 'muted-small', style: 'display:block;margin-bottom:0.375rem', text: t('ui.shell.appearance') }), segmented]),
            el('button', { class: 'menu-item', type: 'button', onclick: () => { closePopover(); open('help'); } }, [icon('help-circle', { size: 18 }), el('span', { text: t('ui.shell.support') })]),
            el('button', { class: 'menu-item is-danger', type: 'button', onclick: () => { closePopover(); signOutAsk(); } }, [icon('log-out-01', { size: 18 }), el('span', { class: 'grow' }, [el('span', { class: 'cell-strong', style: 'display:block', text: t('ui.navigation.sign_out') }), el('span', { class: 'muted-small', text: t('ui.navigation.sign_out_description') })])]),
        ]);

        place(menu, anchor);
    }

    /* ------------------------------------------------------ screens */

    const factories = {
        dashboard: () => dashboardScreen(client, { onOpenSection: open, onOpenTenant: (id) => open('tenants', { tenantId: id }), onNewLease: async () => { if (await leaseWizard(client)) { await refresh(); } } }),
        properties: () => propertiesScreen(client),
        parties: () => partiesScreen(client),
        leases: () => leasesScreen(client, { onOpenTenant: (id) => open('tenants', { tenantId: id }) }),
        tenants: (options) => tenantsScreen(client, { initialTenantId: options?.tenantId ?? null }),
        owners: () => ownersScreen(client),
        accounting: () => accountingScreen(client),
        reports: () => reportsScreen(client),
        settings: (options) => settingsScreen(client, { config, version, initialTab: options?.tab, onOrganisationSaved: (saved, changedLocale) => { paintOrganisation(); if (changedLocale) { refresh(); } }, onSignedOut: () => signOut(true) }),
        audit: () => auditScreen(client),
        archive: () => archiveScreen(client, { onChanged: () => store.refreshAll(client) }),
        help: (options) => helpScreen(client, { apiBase, initialTab: options?.tab }),
    };

    function open(id, options = {}) {
        closePopover();

        const factory = factories[id] ?? factories.dashboard;

        currentId = factories[id] ? id : 'dashboard';

        for (const [key, item] of navItems) {
            const active = key === currentId;

            item.classList.toggle('is-active', active);
            item.setAttribute('aria-current', active ? 'page' : 'false');
        }

        currentScreen = factory(options);
        mount(content, currentScreen.node);
        content.scrollTop = 0;
    }

    async function refresh() {
        refreshButton.classList.add('is-spinning');

        try {
            await store.refreshAll(client);
            paintOrganisation();
            const fresh = store.read('me').data;

            if (fresh) {
                user = fresh.user ?? fresh;
                setUser(user);
            }

            await currentScreen?.reload?.();
        } finally {
            refreshButton.classList.remove('is-spinning');
        }
    }

    /* ----------------------------------------------------- lifecycle */

    async function signOutAsk() {
        if (await confirmSheet({ title: t('signout.title'), body: t('signout.body'), confirmLabel: t('signout.confirm'), danger: true })) {
            await signOut(false);
        }
    }

    async function signOut(alreadyGone) {
        stopRefreshing();
        closePopover();
        store.clear();

        if (! alreadyGone) {
            try {
                await client.post(endpoints.auth.logout);
            } catch {
                /* Local sign-out happens regardless. */
            }
        }

        await session.clear();
        onSignedOut();
    }

    function startRefreshing() {
        timer = setInterval(() => store.refreshAll(client), REFRESH_INTERVAL);

        CapacitorApp.addListener('appStateChange', ({ isActive }) => {
            if (isActive) {
                store.refreshAll(client);
            }
        }).catch(() => {});
    }

    function stopRefreshing() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    attachPullToRefresh(content, () => refresh());

    mount(root, el('div', { class: 'tablet-shell' }, [
        sidebar,
        el('div', { class: 'workspace-frame' }, [topbar, content]),
    ]));

    paintOrganisation();

    client.get(endpoints.notifications).then((payload) => {
        const list = payload?.notifications ?? [];

        bell.classList.toggle('badge-dot', list.some((n) => (n.kind === 'release_notes' && n.unread === true) || n.severity === 'danger'));
    }).catch(() => {});

    startRefreshing();
    open('dashboard');
}
