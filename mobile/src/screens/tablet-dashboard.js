/*
 * The iPad dashboard, built to Komla's second design.
 *
 * Five figures, then Upcoming Payments beside a revenue chart, then Recent
 * Activity beside Lease Expirations. Everything is drawn from the API.
 *
 * TWO PANELS FROM THE DESIGN ARE DELIBERATELY ABSENT, because nothing in
 * the API can fill them honestly:
 *
 *   - "Top Properties by Revenue" needs revenue ranked per building. There
 *     is no such endpoint; /reports/buildings/{id} is one building at a
 *     time, so the panel would mean a request per property and a ranking
 *     computed on the handset from partial data.
 *
 *   - The "TYPE" column on Upcoming Payments ("Rent", "Service Charge")
 *     has no field behind it: an invoice returns number, dates, status and
 *     amounts, and nothing that says what it is for.
 *
 * A panel that looks finished and is quietly invented is worse than one
 * that is not there, particularly on a screen of figures.
 */

import { el } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { money, relativeDays, shortDate, whenLabel } from '../ui/money.js';
import * as store from '../data/store.js';
import { openDocument } from '../data/exports.js';

const SVG_NS = 'http://www.w3.org/2000/svg';

function rows(payload) {
    return Array.isArray(payload) ? payload : (payload?.data ?? []);
}

/* Laravel paginates, so the count on screen is the total, not the page. */
function totalOf(payload) {
    return payload?.meta?.total ?? payload?.total ?? rows(payload).length;
}

function statCard({ tone, iconName, label, value, sub, link, onLink }) {
    return el('article', { class: `stat stat-${tone}` }, [
        el('div', { class: 'stat-badge' }, [icon(iconName, { size: 20 })]),
        el('span', { class: 'stat-label', text: label }),
        el('span', { class: 'stat-value', text: value }),
        sub ? el('span', { class: 'stat-sub', text: sub }) : null,
        link ? el('button', { class: 'stat-link', onclick: onLink }, [
            el('span', { text: link }),
            icon('arrow-right', { size: 16 }),
        ]) : null,
    ]);
}

function panel(title, body, { link, onLink } = {}) {
    return el('section', { class: 'card' }, [
        el('header', { class: 'card-head' }, [
            el('h2', { class: 'card-title', text: title }),
            link ? el('button', { class: 'card-link', text: link, onclick: onLink }) : null,
        ]),
        body,
    ]);
}

/*
 * The revenue chart, drawn as an area from the monthly collections trend.
 *
 * The design shows a daily line across one month; the API returns twelve
 * MONTHLY totals, so this plots what exists rather than inventing daily
 * points. No charting library: a dozen values do not justify a dependency,
 * and one would bring its own palette into a system that has one.
 */
function revenueChart(trend) {
    const width = 640;
    const height = 180;
    const padding = { top: 12, right: 8, bottom: 22, left: 8 };

    const peak = trend.reduce((high, point) => Math.max(high, point.amount ?? 0), 0) || 1;
    const step = trend.length > 1
        ? (width - padding.left - padding.right) / (trend.length - 1)
        : 0;

    const points = trend.map((point, index) => {
        const x = padding.left + index * step;
        const usable = height - padding.top - padding.bottom;
        const y = padding.top + usable - ((point.amount ?? 0) / peak) * usable;

        return [x, y];
    });

    const line = points.map(([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`).join(' ');
    const area = `${padding.left},${height - padding.bottom} ${line} ${(padding.left + (trend.length - 1) * step).toFixed(1)},${height - padding.bottom}`;

    const svg = document.createElementNS(SVG_NS, 'svg');

    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('class', 'chart');
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.setAttribute('aria-hidden', 'true');

    const fill = document.createElementNS(SVG_NS, 'polygon');

    fill.setAttribute('points', area);
    fill.setAttribute('class', 'chart-area');
    svg.append(fill);

    const stroke = document.createElementNS(SVG_NS, 'polyline');

    stroke.setAttribute('points', line);
    stroke.setAttribute('class', 'chart-line');
    svg.append(stroke);

    return el('div', { class: 'chart-wrap' }, [
        svg,
        el('div', { class: 'chart-axis' }, trend.map((point) => el('span', {
            class: 'chart-tick',
            text: String(point.month ?? '').slice(5),
        }))),
    ]);
}

/**
 * @param {object} options
 * @param {(id: string) => void} options.onOpenSection
 */
export function tabletDashboard({ onOpenSection, client }) {
    const me = store.read('me').data;
    const user = me?.user ?? me ?? {};

    const summary = store.read('dashboard').data ?? {};
    const metrics = summary.metrics ?? {};

    const leases = rows(store.read('leases').data);
    const activeLeases = leases.filter((lease) => lease.status === 'active').length;
    const parties = store.read('parties').data;

    const upcoming = rows(store.read('upcoming').data).slice(0, 5);
    const activity = rows(store.read('activity').data).slice(0, 4);
    const expiring = (Array.isArray(summary.expiring_leases) ? summary.expiring_leases : []).slice(0, 4);
    const trend = Array.isArray(summary.collections_trend) ? summary.collections_trend : [];

    const cards = [
        metrics.rent_collected_this_month === undefined ? null : statCard({
            tone: 'brand',
            iconName: 'coins-stacked',
            label: t('home.revenue'),
            value: money(metrics.rent_collected_this_month),
            sub: t('home.this_month'),
        }),
        metrics.rent_overdue === undefined ? null : statCard({
            tone: 'info',
            iconName: 'file-05',
            label: t('dash.outstanding_balances'),
            value: money(metrics.rent_overdue),
            sub: metrics.total_leases === undefined
                ? null
                : t('home.across_leases', { count: metrics.total_leases }),
            link: t('dash.view_details'),
            onLink: () => onOpenSection('accounting'),
        }),
        summary.occupancy_rate === undefined ? null : statCard({
            tone: 'warning',
            iconName: 'bar-chart-square',
            label: t('home.occupancy'),
            value: `${summary.occupancy_rate}%`,
            sub: t('home.all_properties'),
        }),
        /* Counted from the leases already held, not from a metric that does
           not exist: the API reports total_leases, not active ones. */
        leases.length === 0 ? null : statCard({
            tone: 'accent',
            iconName: 'file-check',
            label: t('dash.active_leases'),
            value: String(activeLeases),
            sub: metrics.total_buildings === undefined
                ? null
                : t('dash.across_properties', { count: metrics.total_buildings }),
            link: t('dash.view_leases'),
            onLink: () => onOpenSection('leases'),
        }),
        parties === null ? null : statCard({
            tone: 'teal',
            iconName: 'users-03',
            label: t('nav.parties'),
            value: String(totalOf(parties)),
            sub: t('dash.tenants_owners'),
            link: t('dash.view_parties'),
            onLink: () => onOpenSection('parties'),
        }),
    ].filter(Boolean);

    return el('div', { class: 'dashboard' }, [
        el('header', { class: 'greeting' }, [
            el('h1', { class: 'greeting-title', text: t('dash.greeting', { name: (user.given_names ?? user.name ?? '').split(/\s+/)[0] ?? '' }).trim() }),
            el('p', { class: 'greeting-sub', text: t('dash.subtitle') }),
        ]),

        cards.length === 0 ? null : el('div', { class: 'stats stats-grid' }, cards),

        el('div', { class: 'grid-2' }, [
            panel(
                t('home.upcoming'),
                upcoming.length === 0
                    ? el('p', { class: 'muted', text: t('list.empty') })
                    : el('table', { class: 'table' }, [
                        el('thead', {}, [el('tr', {}, [
                            el('th', { text: t('dash.tenant_property') }),
                            el('th', { text: t('dash.due_date') }),
                            el('th', { class: 'is-numeric', text: t('write.amount') }),
                            el('th', { class: 'is-numeric', text: t('export.download') }),
                        ])]),
                        el('tbody', {}, upcoming.map((invoice) => el('tr', {}, [
                            el('td', {}, [
                                el('div', { class: 'cell-stack' }, [
                                    el('span', { class: 'cell-title', text: invoice.tenant?.name ?? invoice.invoice_number ?? '' }),
                                    el('span', { class: 'cell-sub', text: [invoice.unit?.name, invoice.building?.name].filter(Boolean).join(' · ') }),
                                ]),
                            ]),
                            el('td', {}, [
                                el('div', { class: 'cell-stack' }, [
                                    el('span', { class: 'cell-title', text: relativeDays(invoice.due_date) ?? '' }),
                                    el('span', { class: 'cell-sub is-due', text: shortDate(invoice.due_date) }),
                                ]),
                            ]),
                            el('td', { class: 'is-numeric' }, [
                                el('span', { class: 'row-figure', text: money(invoice.outstanding_amount ?? invoice.total_amount) }),
                            ]),
                            /*
                             * The invoice itself, and its receipt once
                             * something has been paid against it. There is
                             * no invoice list endpoint, so this row is the
                             * only place these documents are reachable.
                             */
                            el('td', { class: 'is-numeric' }, [
                                el('button', {
                                    class: 'icon-button',
                                    'aria-label': t('export.pdf'),
                                    onclick: () => openDocument(client, `/invoices/${invoice.id}/pdf`),
                                }, [icon('file-05', { size: 20 })]),
                                (invoice.paid_amount ?? 0) > 0
                                    ? el('button', {
                                        class: 'icon-button',
                                        'aria-label': t('record.receipt'),
                                        onclick: () => openDocument(client, `/invoices/${invoice.id}/payment-receipt`),
                                    }, [icon('check-circle', { size: 20 })])
                                    : null,
                            ]),
                        ]))),
                    ]),
                { link: t('home.view_all'), onLink: () => onOpenSection('accounting') }
            ),

            trend.length === 0 ? null : panel(t('dash.revenue_overview'), el('div', { class: 'chart-body' }, [
                el('span', { class: 'chart-value', text: money(metrics.rent_collected_this_month) }),
                el('span', { class: 'chart-caption', text: t('home.revenue') }),
                revenueChart(trend),
            ])),
        ]),

        el('div', { class: 'grid-2' }, [
            panel(
                t('home.recent'),
                activity.length === 0
                    ? el('p', { class: 'muted', text: t('list.empty') })
                    : el('ul', { class: 'list list-flush' }, activity.map((entry) => el('li', { class: 'row' }, [
                        el('div', { class: 'row-main' }, [
                            el('span', { class: 'row-over', text: entry.action_label ?? entry.action ?? '' }),
                            el('span', { class: 'row-title', text: entry.description ?? '' }),
                        ]),
                        el('span', { class: 'row-when', text: whenLabel(entry.created_at) ?? '' }),
                    ]))),
                { link: t('home.view_all'), onLink: () => onOpenSection('audit') }
            ),

            panel(
                t('dash.expirations'),
                expiring.length === 0
                    ? el('p', { class: 'muted', text: t('list.empty') })
                    : el('ul', { class: 'list list-flush' }, expiring.map((lease) => {
                        const days = relativeDays(lease.end_date);

                        return el('li', { class: 'row' }, [
                            days === null ? null : el('span', { class: 'days-badge', text: days }),
                            el('div', { class: 'row-main' }, [
                                el('span', { class: 'row-title', text: lease.tenant_name ?? `#${lease.id}` }),
                                el('span', { class: 'row-over', text: t('dash.lease_ends', { date: shortDate(lease.end_date) }) }),
                            ]),
                            lease.status
                                ? el('span', { class: `chip chip-${lease.status}`, text: String(lease.status) })
                                : null,
                        ]);
                    })),
                { link: t('home.view_all'), onLink: () => onOpenSection('leases') }
            ),
        ]),
    ]);
}
