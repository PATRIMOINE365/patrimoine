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
import { DEVICES, PROFILE, SETTINGS, AUDIT, ARCHIVE, REPORTS_INDEX } from './detail.js';

const TABS = [
    { id: 'properties', label: 'tab.properties', icon: 'building-02', path: () => endpoints.buildings },
    { id: 'parties', label: 'tab.parties', icon: 'users-03', path: () => endpoints.parties },
    { id: 'leases', label: 'tab.leases', icon: 'file-check', path: () => endpoints.leases },
    /* Accounting is "calculator" in the sidebar; Finance is the same place. */
    { id: 'finance', label: 'tab.finance', icon: 'calculator', path: () => endpoints.ownerAccounts },
    { id: 'more', label: 'tab.more', icon: 'menu-02', path: null },
];

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

    const body = el('div', { class: 'tab-body' });

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

    /* Push a screen from detail.js and draw it. */
    async function open(screen) {
        stack.push(screen);
        await renderDetail();
    }

    function back() {
        stack.pop();

        if (stack.length === 0) {
            renderHeader();
            renderMore();
        } else {
            renderDetail();
        }
    }

    async function renderDetail() {
        const screen = stack[stack.length - 1];

        renderHeader();
        mount(body, el('p', { class: 'muted centred', text: t('list.loading') }));

        try {
            const node = await screen.load(client, { open, reload: renderDetail });

            mount(body, node);
        } catch (failure) {
            mount(body, el('div', { class: 'empty' }, [
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

        await session.clear();
        onSignedOut();
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
        stack.length = 0;
        renderHeader();

        const tab = TABS.find((candidate) => candidate.id === id);

        if (tab.path === null) {
            renderMore();
        } else {
            load(tab.icon, tab.path(), () => select(id));
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
                el('button', {
                    class: 'icon-button',
                    'aria-label': t('more.notifications'),
                    onclick: () => load('bell-01', endpoints.notifications, () => select(active)),
                }, [icon('bell-01', { size: 20 })])
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

    mount(root, el('div', { class: 'shell' }, [header, body, tabBar]));

    renderHeader();
    select(active);
}
