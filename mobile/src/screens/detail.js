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
import { icon } from '../ui/icon.js';
import { t, language } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';

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
    label: 'more.devices',
    icon: 'smartphone',
    async load(client, { reload }) {
        const devices = rows(await client.get(endpoints.auth.devices));

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
                            reload();
                        },
                    }),
            ]);
        }));
    },
};

export const PROFILE = {
    label: 'more.profile',
    icon: 'user-01',
    async load(client) {
        const payload = await client.get(endpoints.auth.me);
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
    label: 'more.settings',
    icon: 'settings-01',
    async load(client) {
        /*
         * Two calls because the two facts live apart: the organisation is
         * the customer's own record, the licence is what they are entitled
         * to. Settled together they are the account summary the web app
         * shows on its Settings page.
         */
        const [organisation, licence] = await Promise.all([
            client.get(endpoints.managingOrganisation).catch(() => null),
            client.get(endpoints.license).catch(() => null),
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
    label: 'more.audit',
    icon: 'clock-rewind',
    async load(client) {
        const entries = rows(await client.get(endpoints.activityLog));

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
    label: 'more.archive',
    icon: 'archive',
    async load(client) {
        const entries = rows(await client.get(endpoints.archive));

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
    label: 'more.reports',
    icon: 'bar-chart-square',
    async load(client, { open }) {
        return listOf(REPORTS.map((report) => el('li', {
            class: 'row row-tappable',
            onclick: () => open({
                label: `reports.${report.key}`,
                icon: 'bar-chart-square',
                async load(inner) {
                    const payload = await inner.get(report.path);
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
