/*
 * The iPad client.
 *
 * NOT a stretched phone. Komla's instruction, and the right one: the tablet
 * is the full client and the phone stays simple. So this is a different
 * application sharing the same store, tokens and icons - Apple's sidebar
 * pattern, a content list in the middle where one makes sense, and a detail
 * pane on the right.
 *
 * The sidebar MIRRORS THE WEB APPLICATION entry for entry, in the web
 * application's order, with the web application's icons - read from the same
 * resources/icons/untitled-ui.json. Somebody who knows the web product must
 * not have to learn a second information architecture to use the tablet.
 * Only the platform console is left out; it is staff-only.
 */

import { el, mount, errorLine } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { session } from '../auth/session.js';
import * as store from '../data/store.js';
import { App as CapacitorApp } from '@capacitor/app';
import { ACTIONS } from './write-flows.js';
import { searchField } from '../ui/search.js';
import { titleOf, subtitleOf, filterRecords } from '../data/record.js';
import { brandMark } from '../ui/brand.js';
import { tabletDashboard } from './tablet-dashboard.js';
import { openDocument } from '../data/exports.js';
import { recordView } from './tablet-records.js';
import { attachPullToRefresh } from '../ui/pull-refresh.js';
import { newLease } from './new-lease.js';
import { PROFILE, signOutScreen } from './detail.js';
import { shortDate } from '../ui/money.js';

const REFRESH_INTERVAL = 5 * 60 * 1000;

/*
 * The web sidebar, in its own order. `key` names a store entry where one
 * exists; `path` is used for the entries fetched on demand. `detail` marks
 * the sections that are a list with a record beside it rather than one
 * full-width surface.
 */
const SECTIONS = [
    { id: 'dashboard', label: 'nav.dashboard', icon: 'grid-01', kind: 'dashboard' },
    { id: 'properties', label: 'nav.properties', icon: 'building-02', kind: 'list', key: 'buildings', detail: (id) => endpoints.building(id) },
    { id: 'parties', label: 'nav.parties', icon: 'users-03', kind: 'list', key: 'parties', detail: (id) => endpoints.party(id) },
    { id: 'leases', label: 'nav.leases', icon: 'file-check', kind: 'list', key: 'leases', detail: (id) => endpoints.lease(id) },
    { id: 'tenants', label: 'nav.tenants', icon: 'users-01', kind: 'list', path: '/parties?role=tenant', detail: (id) => endpoints.party(id) },
    { id: 'owners', label: 'nav.owners', icon: 'user-check', kind: 'list', path: '/parties?role=owner', detail: (id) => endpoints.party(id) },
    { id: 'accounting', label: 'nav.accounting', icon: 'calculator', kind: 'list', key: 'ownerAccounts', detail: (id) => endpoints.ownerAccount(id) },
    { id: 'reports', label: 'nav.reports', icon: 'bar-chart-square', kind: 'reports' },
    { id: 'users', label: 'nav.users', icon: 'user-01', kind: 'list', path: endpoints.users, detail: (id) => endpoints.user(id) },
    { id: 'settings', label: 'nav.settings', icon: 'settings-01', kind: 'settings' },
    { id: 'audit', label: 'nav.audit', icon: 'clock-rewind', kind: 'list', path: endpoints.activityLog, exports: { base: endpoints.activityLog, formats: ['csv', 'xlsx'] } },
    { id: 'journal', label: 'nav.financial_journal', icon: 'scale-balanced', kind: 'list', path: endpoints.financialJournal, exports: { base: endpoints.financialJournal, formats: ['csv', 'xlsx'] } },
    { id: 'archive', label: 'nav.archive', icon: 'archive', kind: 'list', path: endpoints.archive },
];

function rows(payload) {
    return Array.isArray(payload) ? payload : (payload?.data ?? []);
}

/*
 * A record rendered as its own fields. Deliberately generic: the API returns
 * a different shape per type and nothing here invents one. Per-type detail
 * screens replace this as they are built.
 */
function factsOf(record) {
    const skip = new Set(['id', 'created_at', 'updated_at', 'deleted_at', 'archived_at']);

    const pairs = Object.entries(record)
        .filter(([key, value]) => (
            ! skip.has(key)
            && value !== null
            && value !== ''
            && typeof value !== 'object'
        ));

    if (pairs.length === 0) {
        return el('p', { class: 'muted', text: t('list.empty') });
    }

    return el('dl', { class: 'facts' }, pairs.flatMap(([key, value]) => [
        el('dt', { class: 'fact-label', text: key.replace(/_/g, ' ') }),
        el('dd', { class: 'fact-value', text: String(value) }),
    ]));
}

export function tabletShell(root, { client, config, version = '1.0.0', onSignedOut }) {
    let current = SECTIONS[0];
    let selectedId = null;
    let unsubscribe = () => {};
    let timer = null;
    let query = '';

    const search = searchField((value) => {
        query = value;
        paintList();
    });

    const listPane = el('div', { class: 'pane pane-list' });
    const detailPane = el('div', { class: 'pane pane-detail' });
    const workspace = el('div', { class: 'workspace' }, [listPane, detailPane]);

    /* ---------------------------------------------------------- sidebar */

    const navItems = new Map();

    const nav = el('nav', { class: 'sidebar-nav' }, SECTIONS.map((section) => {
        const item = el('button', {
            class: 'nav-item',
            onclick: () => select(section.id),
        }, [
            icon(section.icon, { size: 20 }),
            el('span', { class: 'nav-label', text: t(section.label) }),
        ]);

        navItems.set(section.id, item);

        return item;
    }));

    const refreshButton = el('button', {
        class: 'icon-button on-band',
        'aria-label': t('common.refresh'),
        onclick: () => store.refreshAll(client),
    }, [icon('refresh-cw', { size: 20 })]);

    function reflectLoading() {
        const loading = store.isLoading();

        refreshButton.classList.toggle('is-spinning', loading);
        refreshButton.disabled = loading;
    }

    const unsubscribeLoading = store.subscribeAny(reflectLoading);

    /*
     * The user chip that closes the sidebar in the design. Initials rather
     * than an avatar: the API serves a photograph only for those who have
     * uploaded one, and a broken image is worse than two letters.
     */
    function userChip() {
        const me = store.read('me').data;
        const user = me?.user ?? me ?? {};
        const name = user.name ?? '';
        const initials = name.split(/\s+/).filter(Boolean).slice(0, 2)
            .map((part) => part[0].toUpperCase()).join('');

        return el('button', {
            class: 'user-chip',
            /* The name opens the person, not the organisation's settings. */
            onclick: () => open(PROFILE),
        }, [
            el('span', { class: 'user-avatar', text: initials }),
            el('span', { class: 'user-words' }, [
                el('span', { class: 'user-name', text: name }),
                el('span', { class: 'user-role', text: user.role_label ?? user.role ?? '' }),
            ]),
            icon('chevron-down', { size: 16, class: 'user-chevron' }),
        ]);
    }

    /*
     * NO "MORE" ENTRY, on Komla's instruction: every destination is listed,
     * in the order the application has them. A tablet has the room, and an
     * operator should not have to open a drawer to find Archive.
     */
    const sidebar = el('aside', { class: 'sidebar' }, [
        el('div', { class: 'sidebar-head' }, [
            el('div', { class: 'brand' }, [
                el('div', { class: 'brand-tile' }, [brandMark(22)]),
                el('div', { class: 'brand-words' }, [
                    el('span', { class: 'brand-name' }, [
                        el('strong', { text: 'Patrimoine' }),
                        el('span', { class: 'brand-365', text: ' 365' }),
                    ]),
                ]),
            ]),
        ]),
        nav,
        el('div', { class: 'sidebar-foot' }, [
            el('button', {
                class: 'nav-item',
                onclick: () => window.open(config?.links?.support ?? '#', '_blank'),
            }, [icon('life-buoy', { size: 20 }), el('span', { class: 'nav-label', text: t('more.support') })]),
            el('button', {
                class: 'nav-item is-danger',
                /*
                 * A screen, not an instant action - as the web application
                 * does it. Signing out of a tablet somebody shares should
                 * take a deliberate second tap.
                 */
                onclick: () => open(signOutScreen(signOut)),
            }, [icon('log-out-01', { size: 20 }), el('span', { class: 'nav-label', text: t('nav.sign_out') })]),
            userChip(),
        ]),
    ]);

    /* ------------------------------------------------------------ panes */

    function showDetailPlaceholder(iconName) {
        mount(detailPane, el('div', { class: 'empty' }, [
            el('div', { class: 'empty-icon' }, [icon(iconName, { size: 24 })]),
            el('p', { class: 'empty-text', text: t('detail.none') }),
        ]));
    }

    function clearRecord() {
        selectedId = null;
        workspace.classList.remove('has-detail');
        paintList();
        showDetailPlaceholder(current.icon);
    }

    async function openRecord(section, record) {
        selectedId = record.id ?? null;
        /* At compact width this is what swaps the list for the record. */
        workspace.classList.add('has-detail');
        paintList();

        mount(detailPane, el('p', { class: 'muted centred', text: t('list.loading') }));

        try {
            const render = recordView(section.id);
            const body = await render(client, record, {
                reload: () => openRecord(section, record),
                open,
            });

            mount(detailPane, el('div', {}, [
                el('header', { class: 'detail-head' }, [
                    el('button', {
                        class: 'detail-back',
                        onclick: clearRecord,
                    }, [icon('arrow-left', { size: 20 }), el('span', { text: t(current.label) })]),
                    el('h2', { class: 'detail-title', text: titleOf(record) }),
                    subtitleOf(record) === ''
                        ? null
                        : el('p', { class: 'detail-subtitle', text: subtitleOf(record) }),
                    ACTIONS[section.id] === undefined ? null : el('div', { class: 'detail-actions' }, [
                        el('button', {
                            class: 'button button-compact',
                            onclick: () => ACTIONS[section.id].run(client, record),
                        }, [
                            icon(ACTIONS[section.id].icon, { size: 20 }),
                            el('span', { text: t(ACTIONS[section.id].label) }),
                        ]),
                    ]),
                ]),
                body,
            ]));
        } catch (failure) {
            mount(detailPane, errorLine(failure, t('signin.offline')));
        }
    }

    function paintList() {
        const section = current;
        const held = section.key ? store.read(section.key) : store.read(`section:${section.id}`);

        if (held.data === null) {
            mount(listPane, el('p', { class: 'muted centred', text: t('list.loading') }));

            return;
        }

        const all = rows(held.data);

        if (all.length === 0) {
            mount(listPane, el('div', { class: 'empty' }, [
                el('div', { class: 'empty-icon' }, [icon(section.icon, { size: 24 })]),
                el('p', { class: 'empty-text', text: t('list.empty') }),
            ]));

            return;
        }

        const found = filterRecords(all, query);

        const listNode = found.length === 0
            ? el('div', { class: 'empty' }, [
                el('div', { class: 'empty-icon' }, [icon('search-lg', { size: 24 })]),
                el('p', { class: 'empty-text', text: t('search.none', { query }) }),
            ])
            : el('ul', { class: 'list' }, found.map((record) => {
                const subtitle = subtitleOf(record);

                return el('li', {
                    class: `row row-tappable${record.id === selectedId ? ' is-selected' : ''}`,
                    onclick: () => openRecord(section, record),
                }, [
                    el('div', { class: 'row-main' }, [
                        el('span', { class: 'row-title', text: titleOf(record) }),
                        subtitle === '' ? null : el('span', { class: 'row-subtitle', text: subtitle }),
                    ]),
                    record.status
                        ? el('span', { class: `chip chip-${record.status}`, text: String(record.status) })
                        : null,
                    /* The sign that this row opens into something. */
                    icon('chevron-right', { size: 20, class: 'row-chevron' }),
                ]);
            }));

        mount(
            listPane,
            el('div', { class: 'pane-head' }, [
                el('h1', { class: 'pane-title', text: t(section.label) }),
                el('span', { class: 'pane-count', text: String(found.length) }),
                /* Creating a lease is a tablet act; the phone reads only. */
                section.id !== 'leases' ? null : el('button', {
                    class: 'button button-compact button-primary pane-action',
                    onclick: async () => {
                        if (await newLease(client)) {
                            paintList();
                        }
                    },
                }, [icon('plus', { size: 18 }), el('span', { text: t('lease.new') })]),
            ]),
            section.exports === undefined
                ? null
                : exportBar(section.exports.base, section.exports.formats),
            all.length >= 8 ? search.node : null,
            listNode
        );
    }

    async function select(id) {
        const changed = current.id !== id;

        current = SECTIONS.find((section) => section.id === id) ?? SECTIONS[0];
        selectedId = null;

        /* A query from one section means nothing in the next. */
        if (changed) {
            query = '';
            search.input.value = '';
        }

        for (const [key, item] of navItems) {
            const active = key === current.id;

            item.classList.toggle('is-active', active);
            item.setAttribute('aria-current', active ? 'page' : 'false');
        }

        workspace.classList.toggle('is-single', current.kind !== 'list');
        workspace.classList.remove('has-detail');

        unsubscribe();

        if (current.kind !== 'list') {
            mount(detailPane, el('div'));
            await renderSection(current);

            return;
        }

        showDetailPlaceholder(current.icon);
        paintList();

        if (current.key) {
            unsubscribe = store.subscribe(current.key, () => {
                if (current.id === id) {
                    paintList();
                }
            });
        } else {
            /* Sections outside the working set are held after first use. */
            await store.ensure(client, `section:${current.id}`, current.path);
            paintList();
        }
    }

    /*
     * Sections that are one full-width surface rather than a list. Only the
     * dashboard is built here; the rest state plainly that they are not,
     * which is better than a screen pretending to be finished.
     */
    /*
     * Download buttons for a screen that has exports. Everything the browser
     * application can produce is available on the tablet - Komla's
     * instruction that the iPad has everything.
     *
     * Nothing is streamed here: the API signs a link and the system browser
     * fetches it. See data/exports.js for why that is the only way this can
     * work inside a WebView.
     */
    function exportBar(base, formats) {
        return el('div', { class: 'export-bar' }, [
            el('span', { class: 'export-label', text: t('export.download') }),
            ...formats.map((format) => el('button', {
                class: 'button button-secondary button-compact',
                text: t(`export.${format}`),
                onclick: async (event) => {
                    const button = event.currentTarget;
                    const label = button.textContent;

                    button.disabled = true;

                    try {
                        await openDocument(client, `${base}/${format}`);
                        button.textContent = label;
                    } catch {
                        button.textContent = t('export.failed');
                    } finally {
                        button.disabled = false;
                    }
                },
            })),
        ]);
    }

    async function renderSection(section) {
        if (section.kind === 'dashboard') {
            mount(
                listPane,
                el('div', { class: 'workspace-head' }, [refreshButton]),
                tabletDashboard({ onOpenSection: select, client }),
                el('footer', { class: 'app-footer' }, [
                    el('span', { text: t('app.copyright', { year: new Date().getFullYear() }) }),
                    el('span', { text: `v${version}` }),
                ])
            );

            return;
        }

        if (section.kind === 'reports') {
            mount(listPane, el('div', { class: 'dashboard' }, [
                el('h1', { class: 'pane-title', text: t('nav.reports') }),
                el('div', { class: 'report-grid' }, endpoints.reports.map((report) => el('section', { class: 'card' }, [
                    el('header', { class: 'card-head' }, [
                        el('h2', { class: 'card-title', text: t(`reports.${report.key}`) }),
                        el('button', {
                            class: 'card-link',
                            text: t('dash.view_details'),
                            onclick: () => open({
                                label: `reports.${report.key}`,
                                icon: 'bar-chart-square',
                                async load(inner) {
                                    const found = rows(await inner.get(report.path));

                                    return el('div', { class: 'record' }, [
                                        exportBar(report.path, report.formats),
                                        found.length === 0
                                            ? el('p', { class: 'muted', text: t('list.empty') })
                                            : el('ul', { class: 'list' }, found.map((line) => el('li', { class: 'row' }, [
                                                el('span', { class: 'row-title', text: titleOf(line) }),
                                                (line.total_formatted ?? line.amount_formatted)
                                                    ? el('span', { class: 'row-figure', text: line.total_formatted ?? line.amount_formatted })
                                                    : null,
                                            ]))),
                                    ]);
                                },
                            }),
                        }),
                    ]),
                    el('div', { class: 'card-body' }, [
                        exportBar(report.path, report.formats),
                    ]),
                ]))),
            ]));

            return;
        }

        if (section.kind === 'settings') {
            mount(listPane, el('p', { class: 'muted centred', text: t('list.loading') }));

            const [organisation, licence, devices] = await Promise.all([
                client.get(endpoints.managingOrganisation).catch(() => null),
                client.get(endpoints.license).catch(() => null),
                client.get(endpoints.auth.devices).catch(() => null),
            ]);

            const org = organisation?.data ?? organisation ?? {};
            const lic = licence?.data ?? licence ?? {};
            const me = store.read('me').data;
            const user = me?.user ?? me ?? {};

            const pairs = (entries) => el('dl', { class: 'facts' }, entries
                .filter(([, v]) => v !== null && v !== undefined && v !== '')
                .flatMap(([label, v]) => [
                    el('dt', { class: 'fact-label', text: label }),
                    el('dd', { class: 'fact-value', text: String(v) }),
                ]));

            mount(listPane, el('div', { class: 'dashboard' }, [
                el('h1', { class: 'pane-title', text: t('nav.settings') }),
                el('div', { class: 'report-grid' }, [
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('settings.organisation') })]),
                        pairs([
                            [t('field.name'), org.name],
                            [t('signin.email'), org.email],
                            [t('settings.phone'), org.phone],
                            [t('field.address'), org.address],
                            [t('settings.currency'), org.currency],
                        ]),
                    ]),
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('settings.licence') })]),
                        pairs([
                            [t('settings.plan'), lic.plan_label ?? lic.plan],
                            [t('settings.expires'), lic.expires_at ? shortDate(lic.expires_at) : null],
                            [t('settings.status'), lic.status],
                        ]),
                    ]),
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('nav.profile') })]),
                        pairs([
                            [t('field.name'), user.name],
                            [t('signin.email'), user.email],
                            [t('profile.role'), user.role_label ?? user.role],
                        ]),
                    ]),
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('nav.devices') })]),
                        el('ul', { class: 'list list-flush' }, (devices?.data ?? []).map((device) => el('li', { class: 'row' }, [
                            el('div', { class: 'row-main' }, [
                                el('span', { class: 'row-title', text: device.name ?? `#${device.id}` }),
                                el('span', { class: 'row-subtitle', text: [device.platform, device.app_version].filter(Boolean).join(' · ') }),
                            ]),
                            device.is_current
                                ? el('span', { class: 'chip', text: t('devices.current') })
                                : el('button', {
                                    class: 'link danger',
                                    text: t('devices.revoke'),
                                    onclick: async () => {
                                        await client.delete(endpoints.auth.device(device.id));
                                        renderSection(section);
                                    },
                                }),
                        ]))),
                    ]),
                ]),
            ]));

            return;
        }

        mount(listPane, el('div', { class: 'empty' }, [
            el('div', { class: 'empty-icon' }, [icon(section.icon, { size: 24 })]),
            el('p', { class: 'empty-text', text: t('detail.not_built') }),
        ]));
    }

    /* ------------------------------------------------------- lifecycle */

    async function signOut() {
        stopRefreshing();
        unsubscribe();
        unsubscribeLoading();
        store.clear();

        try {
            await client.post(endpoints.auth.logout);
        } catch {
            /* Local sign-out happens regardless; see the phone shell. */
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

    /*
     * Both panes pull to refresh. The list because that is where somebody
     * looks for new data, and the record because a figure on it can have
     * moved since it was opened.
     */
    attachPullToRefresh(listPane, async () => {
        await store.refreshAll(client);

        if (current.kind === 'list') {
            paintList();
        } else {
            await renderSection(current);
        }
    });

    attachPullToRefresh(detailPane, async () => {
        await store.refreshAll(client);
    });

    mount(root, el('div', { class: 'tablet-shell' }, [sidebar, workspace]));

    reflectLoading();
    startRefreshing();
    select(current.id);
}
