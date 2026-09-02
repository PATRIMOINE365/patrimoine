/*
 * The signed-in shell.
 *
 * Navigation is settled and is not a placeholder: the phone tab bar is
 * exactly Properties, Parties, Leases, Finance, More. Transactions and
 * receipts live inside Finance, never at top level.
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

import { el, mount, clear, errorLine } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { session } from '../auth/session.js';

const TABS = [
    { id: 'properties', label: 'tab.properties', path: () => endpoints.buildings },
    { id: 'parties', label: 'tab.parties', path: () => endpoints.parties },
    { id: 'leases', label: 'tab.leases', path: () => endpoints.leases },
    { id: 'finance', label: 'tab.finance', path: () => endpoints.ownerAccounts },
    { id: 'more', label: 'tab.more', path: null },
];

/* Lease CREATE is tablet-only, and lease EDIT does not exist as a concept. */
const MORE_ITEMS = [
    'more.reports',
    'more.audit',
    'more.archive',
    'more.settings',
    'more.profile',
    'more.devices',
    'more.support',
];

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

    const subtitleParts = [
        record.unit?.label ?? record.unit?.name,
        record.unit?.building?.name ?? record.building?.name,
        record.address,
    ].filter((part) => part !== undefined && part !== null && part !== '');

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

export function appShell(root, { client, onSignedOut }) {
    let active = 'leases';

    const body = el('div', { class: 'tab-body' });

    async function renderList(tab) {
        mount(body, el('p', { class: 'muted', text: t('list.loading') }));

        try {
            const payload = await client.get(tab.path());

            /*
             * Laravel paginates as { data: [ … ], meta: { … } } and returns
             * a bare array when it does not. Both are handled rather than
             * assumed, because the two shapes differ per endpoint.
             */
            const records = Array.isArray(payload) ? payload : (payload?.data ?? []);

            if (records.length === 0) {
                mount(body, el('p', { class: 'muted', text: t('list.empty') }));

                return;
            }

            mount(body, el('ul', { class: 'list' }, records.map(row)));
        } catch (failure) {
            mount(body, el('div', {}, [
                errorLine(failure, t('signin.offline')),
                el('button', {
                    class: 'button',
                    text: t('common.retry'),
                    onclick: () => renderList(tab),
                }),
            ]));
        }
    }

    function renderMore() {
        mount(body, el('ul', { class: 'list' }, [
            ...MORE_ITEMS.map((key) => el('li', { class: 'row' }, [
                el('span', { class: 'row-title', text: t(key) }),
            ])),
            el('li', { class: 'row' }, [
                el('button', {
                    class: 'link danger',
                    text: t('more.signout'),
                    onclick: signOut,
                }),
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

    const tabBar = el('nav', { class: 'tab-bar' }, TABS.map((tab) => el('button', {
        class: 'tab',
        dataset: { tab: tab.id },
        text: t(tab.label),
        onclick: () => select(tab.id),
    })));

    function select(id) {
        active = id;

        for (const button of tabBar.children) {
            const selected = button.dataset.tab === id;

            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-current', selected ? 'page' : 'false');
        }

        const tab = TABS.find((candidate) => candidate.id === id);

        if (tab.path === null) {
            renderMore();
        } else {
            renderList(tab);
        }
    }

    const header = el('header', { class: 'header' }, [
        el('span', { class: 'header-title', text: t('app.name') }),
        /*
         * The bell is a header icon on every screen, because on a phone it
         * and the dashboard are the same surface.
         */
        el('button', {
            class: 'bell',
            'aria-label': 'Notifications',
            text: '•',
            onclick: async () => {
                mount(body, el('p', { class: 'muted', text: t('list.loading') }));

                try {
                    const payload = await client.get(endpoints.notifications);
                    const records = Array.isArray(payload) ? payload : (payload?.data ?? []);

                    mount(body, records.length === 0
                        ? el('p', { class: 'muted', text: t('list.empty') })
                        : el('ul', { class: 'list' }, records.map(row)));
                } catch (failure) {
                    mount(body, errorLine(failure, t('signin.offline')));
                }
            },
        }),
    ]);

    mount(root, el('div', { class: 'shell' }, [header, body, tabBar]));

    select(active);
}
