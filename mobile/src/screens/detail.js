/*
 * The screens behind More.
 *
 * Each one is backed by a real endpoint. None of them invents a field: what
 * the API returns is what is shown, because a screen that displays a
 * plausible-looking number the server never sent is worse than a screen
 * that shows nothing.
 *
 * They are read-only, with one exception - revoking a device, which the
 * mobile contract puts here deliberately: reading and revoking your own
 * sessions is not an administrative act, and asking an administrator to do
 * it is the wrong answer to "I left my phone in a taxi".
 */

import { el, mount, errorLine } from '../ui/dom.js';
import { titleOf, subtitleOf } from '../data/record.js';
import { icon } from '../ui/icon.js';
import { t, language } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import * as store from '../data/store.js';

/*
 * Fetched on first visit, then held. These are not in the working set -
 * prefetching them would trade one spinner for a slow launch - but a second
 * visit should be instant like everything else.
 */
async function cached(client, key, path) {
    const held = await store.ensure(client, key, path);

    if (held.data === null && held.error !== null) {
        throw held.error;
    }

    return held.data;
}

/* Dates arrive as ISO strings; the handset knows how to write them. */
function when(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat(language(), {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function rows(payload) {
    return Array.isArray(payload) ? payload : (payload?.data ?? []);
}

/* A label above a value. Used by the screens that are facts, not lists. */
function facts(pairs) {
    return el('dl', { class: 'facts' }, pairs.flatMap(([label, value]) => (
        value === null || value === undefined || value === ''
            ? []
            : [
                el('dt', { class: 'fact-label', text: label }),
                el('dd', { class: 'fact-value', text: String(value) }),
            ]
    )));
}

function listOf(items) {
    return el('ul', { class: 'list' }, items);
}

function emptyState(iconName) {
    return el('div', { class: 'empty' }, [
        el('div', { class: 'empty-icon' }, [icon(iconName, { size: 24 })]),
        el('p', { class: 'empty-text', text: t('list.empty') }),
    ]);
}

/*
 * Every screen below is { title, load(client) -> node }. The shell renders
 * the chrome; these only say what goes inside.
 */

export const DEVICES = {
    label: 'nav.devices',
    icon: 'smartphone',
    async load(client, { reload }) {
        const devices = rows(await cached(client, 'devices', endpoints.auth.devices));

        if (devices.length === 0) {
            return emptyState('smartphone');
        }

        return listOf(devices.map((device) => {
            const subtitle = [
                device.platform,
                device.app_version,
                when(device.last_used_at),
            ].filter(Boolean).join(' · ');

            return el('li', { class: 'row' }, [
                el('div', { class: 'row-main' }, [
                    el('span', { class: 'row-title', text: device.name ?? `#${device.id}` }),
                    subtitle === '' ? null : el('span', { class: 'row-subtitle', text: subtitle }),
                ]),
                device.is_current === true
                    ? el('span', { class: 'chip', text: t('devices.current') })
                    : el('button', {
                        class: 'link danger',
                        text: t('devices.revoke'),
                        onclick: async () => {
                            await client.delete(endpoints.auth.device(device.id));
                            /* The held list is now wrong; re-fetch it. */
                            await store.fetchKey(client, 'devices', endpoints.auth.devices);
                            reload();
                        },
                    }),
            ]);
        }));
    },
};

export const PROFILE = {
    label: 'nav.profile',
    icon: 'user-01',
    async load(client) {
        const payload = await cached(client, 'me', endpoints.auth.me);
        const user = payload?.user ?? payload ?? {};

        return facts([
            [t('profile.name'), user.name],
            [t('profile.email'), user.email],
            [t('profile.role'), user.role_label ?? user.role],
            [t('profile.organisation'), user.organisation?.name],
        ]);
    },
};

export const SETTINGS = {
    label: 'nav.settings',
    icon: 'settings-01',
    async load(client) {
        /*
         * Two calls because the two facts live apart: the organisation is
         * the customer's own record, the licence is what they are entitled
         * to. Settled together they are the account summary the web app
         * shows on its Settings page.
         */
        const [organisation, licence] = await Promise.all([
            cached(client, 'organisation', endpoints.managingOrganisation).catch(() => null),
            cached(client, 'license', endpoints.license).catch(() => null),
        ]);

        const org = organisation?.data ?? organisation ?? {};
        const lic = licence?.data ?? licence ?? {};

        return facts([
            [t('settings.organisation'), org.name],
            [t('settings.email'), org.email],
            [t('settings.phone'), org.phone],
            [t('settings.plan'), lic.plan_label ?? lic.plan],
            [t('settings.expires'), when(lic.expires_at)],
        ]);
    },
};

export const AUDIT = {
    label: 'nav.audit',
    icon: 'clock-rewind',
    async load(client) {
        const entries = rows(await cached(client, 'audit', endpoints.activityLog));

        if (entries.length === 0) {
            return emptyState('clock-rewind');
        }

        return listOf(entries.map((entry) => {
            const subtitle = [
                entry.actor_name ?? entry.actor?.name,
                when(entry.created_at),
            ].filter(Boolean).join(' · ');

            return el('li', { class: 'row' }, [
                el('div', { class: 'row-main' }, [
                    el('span', {
                        class: 'row-title',
                        text: entry.description ?? entry.action_label ?? entry.action ?? `#${entry.id}`,
                    }),
                    subtitle === '' ? null : el('span', { class: 'row-subtitle', text: subtitle }),
                ]),
            ]);
        }));
    },
};

export const ARCHIVE = {
    label: 'nav.archive',
    icon: 'archive',
    async load(client) {
        const entries = rows(await cached(client, 'archive', endpoints.archive));

        if (entries.length === 0) {
            return emptyState('archive');
        }

        return listOf(entries.map((entry) => {
            const subtitle = [
                entry.kind_label ?? entry.kind,
                when(entry.archived_at),
            ].filter(Boolean).join(' · ');

            return el('li', { class: 'row' }, [
                el('div', { class: 'row-main' }, [
                    el('span', {
                        class: 'row-title',
                        text: entry.label ?? entry.name ?? `#${entry.id}`,
                    }),
                    subtitle === '' ? null : el('span', { class: 'row-subtitle', text: subtitle }),
                ]),
            ]);
        }));
    },
};

/*
 * Reports is an index, not a report. The four the API exposes without a
 * record to hang off are listed; each opens its own screen. Anything that
 * needs a building, a unit or a party is reached from that record instead.
 */
const REPORTS = [
    { key: 'arrears', path: '/reports/arrears' },
    { key: 'occupancy', path: '/reports/occupancy' },
    { key: 'payments', path: '/reports/payments' },
    { key: 'funds', path: '/reports/funds' },
];

export const REPORTS_INDEX = {
    label: 'nav.reports',
    icon: 'bar-chart-square',
    async load(client, { open }) {
        return listOf(REPORTS.map((report) => el('li', {
            class: 'row row-tappable',
            onclick: () => open({
                label: `reports.${report.key}`,
                icon: 'bar-chart-square',
                async load(inner) {
                    const payload = await cached(inner, `report:${report.key}`, report.path);
                    const found = rows(payload);

                    if (found.length === 0) {
                        return emptyState('bar-chart-square');
                    }

                    /*
                     * Report payloads differ per report, so nothing is
                     * assumed beyond a label and a figure. Whatever else a
                     * report carries belongs on a screen built for it.
                     */
                    return listOf(found.map((line) => el('li', { class: 'row' }, [
                        el('div', { class: 'row-main' }, [
                            el('span', {
                                class: 'row-title',
                                text: line.label ?? line.name ?? line.party?.name ?? `#${line.id ?? ''}`,
                            }),
                        ]),
                        line.total_formatted ?? line.amount_formatted
                            ? el('span', {
                                class: 'row-figure',
                                text: line.total_formatted ?? line.amount_formatted,
                            })
                            : null,
                    ])));
                },
            }),
        }, [
            el('div', { class: 'row-lead' }, [
                icon('bar-chart-square', { size: 20 }),
                el('span', { class: 'row-title', text: t(`reports.${report.key}`) }),
            ]),
            icon('chevron-right', { size: 20, class: 'row-chevron' }),
        ])));
    },
};

export { errorLine, mount };


/*
 * Tenants, Owners and Accounting - the three destinations the phone's tab
 * bar no longer carries. Same names and same information as the web
 * application: tenants and owners are parties filtered by role, exactly as
 * the sidebar does it, and Accounting is the owner accounts.
 */
function partyList(role, label, iconName) {
    return {
        label,
        icon: iconName,
        async load(client, { open }) {
            const found = rows(await cached(client, `parties:${role}`, `${endpoints.parties}?role=${role}`));

            if (found.length === 0) {
                return emptyState(iconName);
            }

            return listOf(found.map((party) => tappableRow(party, () => open(
                recordScreen({ label, icon: iconName, detail: (id) => endpoints.party(id) }, party)
            ))));
        },
    };
}

export const TENANTS = partyList('tenant', 'nav.tenants', 'users-01');
export const OWNERS = partyList('owner', 'nav.owners', 'user-check');

export const ACCOUNTING = {
    label: 'nav.accounting',
    icon: 'calculator',
    async load(client, { open }) {
        const found = rows(await cached(client, 'accounting', endpoints.ownerAccounts));

        if (found.length === 0) {
            return emptyState('calculator');
        }

        return listOf(found.map((account) => tappableRow(account, () => open(
            recordScreen(
                { label: 'nav.accounting', icon: 'calculator', detail: (id) => endpoints.ownerAccount(id) },
                account
            )
        ))));
    },
};

function tappableRow(record, onOpen) {
    const subtitle = subtitleOf(record);

    return el('li', { class: 'row row-tappable', onclick: onOpen }, [
        el('div', { class: 'row-main' }, [
            el('span', { class: 'row-title', text: titleOf(record) }),
            subtitle === '' ? null : el('span', { class: 'row-subtitle', text: subtitle }),
        ]),
        record.status
            ? el('span', { class: `chip chip-${record.status}`, text: String(record.status) })
            : null,
        icon('chevron-right', { size: 20, class: 'row-chevron' }),
    ]);
}

/**
 * One record, read only.
 *
 * The phone shows information and never changes it - Komla's decision, and
 * the reason there is no action on this screen. Fields come from whatever
 * the API returned; nothing here invents a shape.
 */
export function recordScreen(section, record) {
    return {
        label: section.label,
        icon: section.icon,
        async load(client) {
            const full = section.detail === undefined
                ? record
                : (await client.get(section.detail(record.id)))?.data ?? record;

            const subtitle = subtitleOf(full);

            return el('div', { class: 'record' }, [
                el('header', { class: 'record-head' }, [
                    el('h2', { class: 'record-title', text: titleOf(full) }),
                    subtitle === '' ? null : el('p', { class: 'record-subtitle', text: subtitle }),
                ]),
                factsOf(full),
            ]);
        },
    };
}

/* Whatever fields came back, as label and value. */
function factsOf(record) {
    const skip = new Set(['id', 'created_at', 'updated_at', 'deleted_at', 'archived_at']);

    const pairs = Object.entries(record).filter(([key, value]) => (
        ! skip.has(key) && value !== null && value !== '' && typeof value !== 'object'
    ));

    if (pairs.length === 0) {
        return el('p', { class: 'muted', text: t('list.empty') });
    }

    return el('dl', { class: 'facts' }, pairs.flatMap(([key, value]) => [
        el('dt', { class: 'fact-label', text: key.replace(/_/g, ' ') }),
        el('dd', { class: 'fact-value', text: String(value) }),
    ]));
}
