/*
 * The signed-in shell.
 *
 * Navigation is settled and is not a placeholder: the phone tab bar is
 * exactly Properties, Parties, Leases, Finance, More. Transactions and
 * receipts live inside Finance, never at top level.
 *
 * THE ICONS ARE THE PRODUCT'S OWN. Every one is the same Untitled UI icon
 * the web sidebar uses for that entry, read from the same
 * resources/icons/untitled-ui.json - properties is building-02 there and
 * building-02 here. An entry that looked like one thing on the web and
 * another on a phone would be a worse failure than having no icon at all.
 *
 * There is deliberately no Dashboard tab on the phone. The dashboard's
 * content and the bell's content are the same eight derived kinds from
 * GET /notifications, so on a phone they are one surface: the bell stays a
 * header icon on every screen and the application opens on Leases. The
 * tablet keeps a real Dashboard and mirrors the web sidebar entry for
 * entry - that layout is not built here.
 *
 * Also deliberately absent everywhere: a Documents entry (there is no
 * global documents screen; documents hang off the record that generates
 * them), Plans/Subscription, Add-lease on the phone, and the admin console.
 */

import { el, mount, errorLine } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { session } from '../auth/session.js';
import { App as CapacitorApp } from '@capacitor/app';
import { DEVICES, PROFILE, SETTINGS, AUDIT, ARCHIVE, REPORTS_INDEX } from './detail.js';
import * as store from '../data/store.js';
import { attachSwipeBack } from '../ui/swipe-back.js';

/*
 * Each tab names a key in the store rather than a path. The data is already
 * held by the time a tab is opened, so switching tabs paints immediately.
 */
const TABS = [
    { id: 'properties', label: 'tab.properties', icon: 'building-02', key: 'buildings' },
    { id: 'parties', label: 'tab.parties', icon: 'users-03', key: 'parties' },
    { id: 'leases', label: 'tab.leases', icon: 'file-check', key: 'leases' },
    /* Accounting is "calculator" in the sidebar; Finance is the same place. */
    { id: 'finance', label: 'tab.finance', icon: 'calculator', key: 'ownerAccounts' },
    { id: 'more', label: 'tab.more', icon: 'menu-02', key: null },
];

/* On resume, and every five minutes while open. */
const REFRESH_INTERVAL = 5 * 60 * 1000;

/*
 * Lease CREATE is tablet-only, and lease EDIT does not exist as a concept.
 *
 * Every entry here opens a real screen backed by a real endpoint. Support
 * is the exception and leaves for the browser, because the support form is
 * a web journey like sign-up and plan changes.
 */
const MORE_ITEMS = [REPORTS_INDEX, AUDIT, ARCHIVE, SETTINGS, PROFILE, DEVICES];

function chip(status) {
    if (status === undefined || status === null) {
        return null;
    }

    return el('span', { class: `chip chip-${String(status)}`, text: String(status) });
}

/*
 * One row shape for every list. The real screens differ per record type;
 * this reads the fields the API already returns so the slice is live data
 * rather than a mock.
 */
function row(record) {
    const title = record.name
        ?? record.tenant?.name
        ?? record.label
        ?? record.reference
        ?? `#${record.id}`;

    /*
     * De-duplicated, because a single-unit property is commonly named after
     * the building - and "6 Osekere Rd House · 6 Osekere Rd House" reads
     * like a bug even though both fields are correct.
     */
    const subtitleParts = [...new Set([
        record.unit?.label ?? record.unit?.name,
        record.unit?.building?.name ?? record.building?.name,
        record.address,
    ].filter((part) => part !== undefined && part !== null && part !== ''))];

    return el('li', { class: 'row' }, [
        el('div', { class: 'row-main' }, [
            el('span', { class: 'row-title', text: String(title) }),
            subtitleParts.length === 0
                ? null
                : el('span', { class: 'row-subtitle', text: subtitleParts.join(' · ') }),
        ]),
        chip(record.status),
    ]);
}

export function appShell(root, { client, config, onSignedOut }) {
    let active = 'leases';

    /*
     * A stack rather than a flag: Reports opens an index, and a report
     * opens from inside it, so Back has to mean "the screen before this"
     * and not "the tab bar".
     */
    const stack = [];

    /* The live subscription for whichever tab is showing. */
    let unsubscribe = () => {};
    let timer = null;

    /*
     * One element per pushed screen, parallel to `stack`. Screens are
     * layers rather than replaced content so that the one underneath is
     * still there to slide back to - which is what the edge-swipe needs,
     * and also what stops a Back tap re-fetching a list that never went
     * away.
     */
    const layers = [];

    const body = el('div', { class: 'tab-body' });

    /* The stage: the tab body, plus any pushed screens layered over it. */
    const screens = el('div', { class: 'screens' }, [body]);

    function records(payload) {
        /*
         * Laravel paginates as { data: [ … ], meta: { … } } and returns a
         * bare array when it does not. Both are handled rather than assumed,
         * because the two shapes differ per endpoint.
         */
        return Array.isArray(payload) ? payload : (payload?.data ?? []);
    }

    function showEmpty(iconName, message) {
        mount(body, el('div', { class: 'empty' }, [
            el('div', { class: 'empty-icon' }, [icon(iconName, { size: 24 })]),
            el('p', { class: 'empty-text', text: message }),
        ]));
    }

    /*
     * Render a tab from the store. No await and no spinner: the working set
     * was fetched at sign-in, so this paints synchronously. A background
     * refresh re-renders in place when it lands.
     */
    function paint(tab) {
        const held = store.read(tab.key);

        /* Only ever seen if a key failed at sign-in and has nothing yet. */
        if (held.data === null) {
            if (held.loading) {
                mount(body, el('p', { class: 'muted centred', text: t('list.loading') }));

                return;
            }

            mount(body, el('div', { class: 'empty' }, [
                el('div', { class: 'empty-icon' }, [icon('alert-circle', { size: 24 })]),
                errorLine(held.error, t('signin.offline')),
                el('button', {
                    class: 'button button-secondary',
                    text: t('common.retry'),
                    onclick: () => store.fetchKey(client, tab.key),
                }),
            ]));

            return;
        }

        const found = records(held.data);

        if (found.length === 0) {
            showEmpty(tab.icon, t('list.empty'));

            return;
        }

        mount(body, el('ul', { class: 'list' }, found.map(row)));
    }

    /* The bell and any one-off list still fetch on demand. */
    async function load(iconName, path, onRetry) {
        mount(body, el('p', { class: 'muted centred', text: t('list.loading') }));

        try {
            const found = records(await client.get(path));

            if (found.length === 0) {
                showEmpty(iconName, t('list.empty'));

                return;
            }

            mount(body, el('ul', { class: 'list' }, found.map(row)));
        } catch (failure) {
            mount(body, el('div', { class: 'empty' }, [
                el('div', { class: 'empty-icon' }, [icon('alert-circle', { size: 24 })]),
                errorLine(failure, t('signin.offline')),
                el('button', {
                    class: 'button button-secondary',
                    text: t('common.retry'),
                    onclick: onRetry,
                }),
            ]));
        }
    }

    /* The screen a pushed layer sits on top of. */
    function beneath() {
        return layers.length > 1 ? layers[layers.length - 2] : body;
    }

    /* Push a screen from detail.js as a new layer over the current one. */
    async function open(screen) {
        const layer = el('div', { class: 'screen-layer' });

        screens.append(layer);
        stack.push(screen);
        layers.push(layer);

        renderHeader();

        /*
         * The screen underneath is pushed back while it is covered, so that
         * a drag continues that movement instead of starting it. Without
         * this the covered screen sits at zero and jumps a third of the
         * width the instant a finger touches the edge.
         */
        beneath().classList.add('is-covered');

        /*
         * Flush layout so the off-screen position is committed before the
         * open class lands - otherwise the browser collapses both styles
         * into one and there is nothing to animate. A forced reflow rather
         * than requestAnimationFrame: rAF is throttled when the view is not
         * visible, and a screen that opened while backgrounded would still
         * be off-screen when the person came back to it.
         */
        void layer.offsetWidth;

        layer.classList.add('is-open');

        attachSwipeBack(layer, { beneath, onComplete: back });

        await renderDetail();
    }

    function back() {
        const layer = layers.pop();

        stack.pop();
        renderHeader();

        if (layer === undefined) {
            return;
        }

        beneath().classList.remove('is-covered');

        /*
         * Remove on transitionend rather than on a timer, so a layer is
         * never torn out from under a gesture still finishing.
         */
        layer.classList.remove('is-open');
        layer.addEventListener('transitionend', () => layer.remove(), { once: true });
        window.setTimeout(() => layer.remove(), 400);
    }

    /* Everything pushed is dropped at once, without animating each one. */
    function clearLayers() {
        for (const layer of layers) {
            layer.remove();
        }

        layers.length = 0;
        stack.length = 0;
        body.classList.remove('is-covered');
    }

    async function renderDetail() {
        const screen = stack[stack.length - 1];
        const layer = layers[layers.length - 1];

        if (screen === undefined || layer === undefined) {
            return;
        }

        mount(layer, el('p', { class: 'muted centred', text: t('list.loading') }));

        try {
            const node = await screen.load(client, { open, reload: renderDetail });

            /* The person may have swiped back while this was in flight. */
            if (layers.includes(layer)) {
                mount(layer, node);
            }
        } catch (failure) {
            if (! layers.includes(layer)) {
                return;
            }

            mount(layer, el('div', { class: 'empty' }, [
                el('div', { class: 'empty-icon' }, [icon('alert-circle', { size: 24 })]),
                errorLine(failure, t('signin.offline')),
                el('button', {
                    class: 'button button-secondary',
                    text: t('common.retry'),
                    onclick: renderDetail,
                }),
            ]));
        }
    }

    function renderMore() {
        mount(body, el('ul', { class: 'list' }, [
            ...MORE_ITEMS.map((item) => el('li', {
                class: 'row row-tappable',
                onclick: () => open(item),
            }, [
                el('div', { class: 'row-lead' }, [
                    icon(item.icon, { size: 20 }),
                    el('span', { class: 'row-title', text: t(item.label) }),
                ]),
                icon('chevron-right', { size: 20, class: 'row-chevron' }),
            ])),
            /*
             * Support leaves for the browser: the support form is a web
             * journey, like sign-up and anything to do with a plan.
             */
            el('li', {
                class: 'row row-tappable',
                onclick: () => {
                    const url = config?.links?.support;

                    if (url) {
                        window.open(url, '_blank');
                    }
                },
            }, [
                el('div', { class: 'row-lead' }, [
                    icon('life-buoy', { size: 20 }),
                    el('span', { class: 'row-title', text: t('more.support') }),
                ]),
                icon('link-external', { size: 20, class: 'row-chevron' }),
            ]),
            el('li', { class: 'row row-tappable', onclick: signOut }, [
                el('div', { class: 'row-lead danger' }, [
                    icon('log-out-01', { size: 20 }),
                    el('span', { class: 'row-title', text: t('more.signout') }),
                ]),
            ]),
        ]));
    }

    /*
     * Revoke server-side first, while the token is still valid, then clear
     * locally. If the network is gone the local clear still happens - the
     * person asked to be signed out of this handset and must be, even
     * though the token stays live until it is revoked or expires.
     */
    async function signOut() {
        try {
            await client.post(endpoints.auth.logout);
        } catch {
            /* Deliberately ignored: see above. */
        }

        stopRefreshing();
        unsubscribe();
        unsubscribeFreshness();
        /* Nothing of the previous person may survive a sign-out. */
        store.clear();

        await session.clear();
        onSignedOut();
    }

    /*
     * Refresh sits in the header beside the bell. Its only state is whether
     * a refresh is running, shown by spinning the glyph: the screen is
     * already showing data, so there is nothing else for it to say.
     */
    const refreshButton = el('button', {
        class: 'icon-button',
        'aria-label': t('common.refresh'),
        onclick: () => store.refreshAll(client),
    }, [icon('refresh-cw', { size: 20 })]);

    function refreshFreshness() {
        const loading = store.isLoading();

        refreshButton.classList.toggle('is-spinning', loading);
        /* Disabled while running, so a second tap cannot stack refreshes. */
        refreshButton.disabled = loading;
    }

    /*
     * Subscribed to the whole store rather than to one key: hung off the
     * active tab it kept spinning after everything had finished, whenever
     * that key happened to complete before the others.
     */
    const unsubscribeFreshness = store.subscribeAny(refreshFreshness);

    /*
     * Two triggers, as agreed: whenever the application returns to the
     * foreground, and every five minutes while it is open. A phone in a
     * pocket must not be refreshing on a cellular plan.
     */
    function startRefreshing() {
        timer = setInterval(() => store.refreshAll(client), REFRESH_INTERVAL);

        CapacitorApp.addListener('appStateChange', ({ isActive }) => {
            if (isActive) {
                store.refreshAll(client);
            }
        }).catch(() => {
            /* Web has no lifecycle events; the interval is enough there. */
        });
    }

    function stopRefreshing() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    /*
     * Untitled UI's mobile bottom navigation, icon only. The name reaches
     * assistive technology through aria-label rather than a visible label
     * repeating what the glyph already says.
     */
    const tabBar = el('nav', { class: 'tab-bar' }, TABS.map((tab) => el('button', {
        class: 'tab',
        dataset: { tab: tab.id },
        'aria-label': t(tab.label),
        title: t(tab.label),
        onclick: () => select(tab.id),
    }, [icon(tab.icon, { size: 24 })])));

    function select(id) {
        active = id;

        for (const button of tabBar.children) {
            const selected = button.dataset.tab === id;

            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-current', selected ? 'page' : 'false');
        }

        /* Changing tab abandons whatever was pushed on the old one. */
        clearLayers();
        renderHeader();

        const tab = TABS.find((candidate) => candidate.id === id);

        unsubscribe();

        if (tab.key === null) {
            renderMore();
        } else {
            paint(tab);

            /* Re-paint in place when a background refresh lands. */
            unsubscribe = store.subscribe(tab.key, () => {
                if (active === id && stack.length === 0) {
                    paint(tab);
                }
            });
        }
    }

    const header = el('header', { class: 'header' });

    /*
     * One header, two states. At the top of a tab it names the product and
     * offers the bell; inside a pushed screen it becomes Back and the
     * screen's own title. The bell is dropped there deliberately - it would
     * navigate away from a screen the person has just opened.
     */
    function renderHeader() {
        const screen = stack[stack.length - 1];

        if (screen === undefined) {
            mount(
                header,
                el('span', { class: 'header-title', text: t('app.name') }),
                el('div', { class: 'header-actions' }, [
                    refreshButton,
                    el('button', {
                        class: 'icon-button',
                        'aria-label': t('more.notifications'),
                        onclick: () => load('bell-01', endpoints.notifications, () => select(active)),
                    }, [icon('bell-01', { size: 20 })]),
                ])
            );

            return;
        }

        mount(
            header,
            el('div', { class: 'header-lead' }, [
                el('button', {
                    class: 'icon-button',
                    'aria-label': t('common.back'),
                    onclick: back,
                }, [icon('arrow-left', { size: 20 })]),
                el('span', { class: 'header-title', text: t(screen.label) }),
            ])
        );
    }

    mount(root, el('div', { class: 'shell' }, [header, screens, tabBar]));

    renderHeader();
    refreshFreshness();
    startRefreshing();
    select(active);
}
