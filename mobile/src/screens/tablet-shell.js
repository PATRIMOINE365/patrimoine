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
    { id: 'settings', label: 'nav.settings', icon: 'settings-01', kind: 'settings' },
    { id: 'audit', label: 'nav.audit', icon: 'clock-rewind', kind: 'list', path: endpoints.activityLog },
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

export function tabletShell(root, { client, config, onSignedOut }) {
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

    const sidebar = el('aside', { class: 'sidebar' }, [
        el('div', { class: 'sidebar-head' }, [
            el('span', { class: 'sidebar-title', text: t('app.name') }),
            refreshButton,
        ]),
        nav,
        el('div', { class: 'sidebar-foot' }, [
            el('button', {
                class: 'nav-item',
                onclick: () => window.open(config?.links?.support ?? '#', '_blank'),
            }, [icon('life-buoy', { size: 20 }), el('span', { class: 'nav-label', text: t('more.support') })]),
            el('button', {
                class: 'nav-item is-danger',
                onclick: signOut,
            }, [icon('log-out-01', { size: 20 }), el('span', { class: 'nav-label', text: t('more.signout') })]),
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
            const payload = section.detail === undefined
                ? record
                : await client.get(section.detail(record.id));

            const full = payload?.data ?? payload ?? record;

            const action = ACTIONS[section.id];

            mount(detailPane, el('div', { class: 'detail' }, [
                el('header', { class: 'detail-head' }, [
                    el('button', {
                        class: 'detail-back',
                        onclick: clearRecord,
                    }, [icon('arrow-left', { size: 20 }), el('span', { text: t(current.label) })]),
                    el('h2', { class: 'detail-title', text: titleOf(full) }),
                    subtitleOf(full) === ''
                        ? null
                        : el('p', { class: 'detail-subtitle', text: subtitleOf(full) }),
                    /*
                     * The action is offered ON the record, so the id it
                     * needs is already known and nobody has to pick the
                     * lease or the account from a second list.
                     */
                    action === undefined
                        ? null
                        : el('div', { class: 'detail-actions' }, [
                            el('button', {
                                class: 'button button-compact',
                                onclick: () => action.run(client, full),
                            }, [
                                icon(action.icon, { size: 20 }),
                                el('span', { text: t(action.label) }),
                            ]),
                        ]),
                ]),
                factsOf(full),
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
                ]);
            }));

        mount(
            listPane,
            el('div', { class: 'pane-head' }, [
                el('h1', { class: 'pane-title', text: t(section.label) }),
                el('span', { class: 'pane-count', text: String(found.length) }),
            ]),
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
    async function renderSection(section) {
        if (section.kind !== 'dashboard') {
            mount(listPane, el('div', { class: 'empty' }, [
                el('div', { class: 'empty-icon' }, [icon(section.icon, { size: 24 })]),
                el('p', { class: 'empty-text', text: t('detail.not_built') }),
            ]));

            return;
        }

        mount(listPane, el('p', { class: 'muted centred', text: t('list.loading') }));

        try {
            const summary = await client.get(endpoints.dashboard.summary);
            const metrics = summary?.metrics ?? {};

            const tiles = [
                ['dashboard.occupancy', `${summary.occupancy_rate ?? 0}%`],
                ['dashboard.units', metrics.total_units],
                ['dashboard.occupied', metrics.occupied_units],
                ['dashboard.active_leases', metrics.active_leases],
            ].filter(([, value]) => value !== undefined && value !== null);

            /*
             * The trend arrives as [{ month: 'YYYY-MM', amount }]. Drawn as
             * bars against the largest month rather than a chart library:
             * twelve values do not justify a dependency, and a library
             * would bring its own colours into a system that has some.
             */
            const trend = Array.isArray(summary.collections_trend)
                ? summary.collections_trend
                : [];

            const peak = trend.reduce((high, point) => Math.max(high, point.amount ?? 0), 0);

            const expiring = Array.isArray(summary.expiring_leases)
                ? summary.expiring_leases
                : [];

            mount(listPane, el('div', { class: 'dashboard' }, [
                el('div', { class: 'tiles' }, tiles.map(([label, value]) => el('div', { class: 'tile' }, [
                    el('span', { class: 'tile-value', text: String(value) }),
                    el('span', { class: 'tile-label', text: t(label) }),
                ]))),

                trend.length === 0 ? null : el('section', { class: 'panel' }, [
                    el('h2', { class: 'panel-title', text: t('dashboard.collections') }),
                    el('div', { class: 'trend' }, trend.map((point) => el('div', { class: 'trend-bar' }, [
                        el('div', {
                            class: 'trend-fill',
                            /* Zero months still show a hairline, not nothing. */
                            style: `height: ${peak > 0 ? Math.max(2, Math.round((point.amount ?? 0) * 100 / peak)) : 2}%`,
                            title: `${point.month}: ${point.amount ?? 0}`,
                        }),
                        el('span', { class: 'trend-label', text: String(point.month ?? '').slice(5) }),
                    ]))),
                ]),

                expiring.length === 0 ? null : el('section', { class: 'panel' }, [
                    el('h2', { class: 'panel-title', text: t('dashboard.expiring') }),
                    el('ul', { class: 'list' }, expiring.map((lease) => {
                        const where = [lease.unit_name, lease.building_name]
                            .filter(Boolean).join(' · ');

                        return el('li', { class: 'row' }, [
                            el('div', { class: 'row-main' }, [
                                el('span', { class: 'row-title', text: lease.tenant_name ?? `#${lease.id}` }),
                                where === '' ? null : el('span', { class: 'row-subtitle', text: where }),
                            ]),
                            lease.end_date
                                ? el('span', { class: 'row-figure', text: lease.end_date })
                                : null,
                        ]);
                    })),
                ]),
            ]));
        } catch (failure) {
            mount(listPane, errorLine(failure, t('signin.offline')));
        }
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

    mount(root, el('div', { class: 'tablet-shell' }, [sidebar, workspace]));

    reflectLoading();
    startRefreshing();
    select(current.id);
}
