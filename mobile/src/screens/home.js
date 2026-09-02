/*
 * Home — the phone's opening screen, built to Komla's design.
 *
 * Greeting, three figures, what is due next, what just happened. Everything
 * on it comes from the API; nothing is illustrative:
 *
 *   Total Revenue    metrics.rent_collected_this_month
 *   Outstanding      metrics.rent_overdue, across the leases that owe it
 *   Occupancy Rate   occupancy_rate
 *   Upcoming         GET /dashboard/upcoming - real invoices, real due dates
 *   Recent activity  GET /activity-log
 *
 * Where a figure the design asks for does not exist, the card is left out
 * rather than filled with something plausible. This is a money screen.
 */

import { el } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { money, relativeDays, shortDate, whenLabel } from '../ui/money.js';
import { titleOf } from '../data/record.js';
import * as store from '../data/store.js';

/* Morning until noon, afternoon until 18:00, evening after. */
function greetingKey() {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'home.morning';
    }

    return hour < 18 ? 'home.afternoon' : 'home.evening';
}

function firstName(user) {
    return (user?.given_names ?? user?.name ?? '').trim().split(/\s+/)[0] ?? '';
}

/*
 * A figure with its own coloured badge. `tone` picks the ramp - brand for
 * money in, blue for money owed, warning for occupancy - matching the
 * design's three cards.
 */
function statCard({ tone, iconName, label, value, sub, footer }) {
    return el('article', { class: `stat stat-${tone}` }, [
        el('div', { class: 'stat-badge' }, [icon(iconName, { size: 20 })]),
        el('span', { class: 'stat-label', text: label }),
        el('span', { class: 'stat-value', text: value }),
        sub ? el('span', { class: 'stat-sub', text: sub }) : null,
        footer ?? null,
    ]);
}

function panel(title, rows, onViewAll) {
    if (rows.length === 0) {
        return null;
    }

    return el('section', { class: 'card' }, [
        el('header', { class: 'card-head' }, [
            el('h2', { class: 'card-title', text: title }),
            onViewAll === undefined
                ? null
                : el('button', { class: 'card-link', text: t('home.view_all'), onclick: onViewAll }),
        ]),
        el('ul', { class: 'list list-flush' }, rows),
    ]);
}

/* A row with a circular badge, two lines, and a figure with its date. */
function mediaRow({ tone, iconName, over, title, amount, when, overFirst = false }) {
    /*
     * The two panels lead with different things, as the design does: an
     * upcoming payment is a property that owes money, so the property comes
     * first; an activity entry is something that happened, so the action
     * does.
     */
    const lines = [
        el('span', { class: 'row-title', text: title }),
        over ? el('span', { class: 'row-over', text: over }) : null,
    ];

    return el('li', { class: 'row row-media' }, [
        el('div', { class: `media-badge media-${tone}` }, [icon(iconName, { size: 20 })]),
        el('div', { class: 'row-main' }, overFirst ? lines.reverse() : lines),
        el('div', { class: 'row-trail' }, [
            amount ? el('span', { class: 'row-figure', text: amount }) : null,
            when ? el('span', { class: 'row-when', text: when }) : null,
        ]),
    ]);
}

/**
 * @param {object} options
 * @param {(tabId: string) => void} options.onOpenTab
 */
export function homeScreen({ onOpenTab }) {
    const me = store.read('me').data;
    const user = me?.user ?? me ?? {};

    const summary = store.read('dashboard').data ?? {};
    const metrics = summary.metrics ?? {};

    const upcoming = store.read('upcoming').data;
    const upcomingRows = (Array.isArray(upcoming) ? upcoming : upcoming?.data ?? []).slice(0, 3);

    const activity = store.read('activity').data;
    const activityRows = (Array.isArray(activity) ? activity : activity?.data ?? []).slice(0, 3);

    /*
     * Only the cards whose figure actually came back. A dashboard that
     * silently shows zero where the server sent nothing is worse than one
     * with two cards on it.
     */
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
            label: t('home.outstanding'),
            value: money(metrics.rent_overdue),
            sub: metrics.total_leases === undefined
                ? null
                : t('home.across_leases', { count: metrics.total_leases }),
            footer: el('button', {
                class: 'stat-link',
                onclick: () => onOpenTab('accounting'),
            }, [
                el('span', { text: t('home.view_dues') }),
                icon('arrow-right', { size: 16 }),
            ]),
        }),
        summary.occupancy_rate === undefined ? null : statCard({
            tone: 'warning',
            iconName: 'bar-chart-square',
            label: t('home.occupancy'),
            value: `${summary.occupancy_rate}%`,
            sub: t('home.all_properties'),
        }),
    ].filter(Boolean);

    return el('div', { class: 'home' }, [
        el('header', { class: 'greeting' }, [
            el('h1', { class: 'greeting-title', text: t(greetingKey(), { name: firstName(user) }).trim() }),
            el('p', { class: 'greeting-sub', text: t('home.subtitle') }),
        ]),

        cards.length === 0 ? null : el('div', { class: 'stats' }, cards),

        panel(
            t('home.upcoming'),
            upcomingRows.map((invoice) => mediaRow({
                tone: 'brand',
                iconName: 'building-02',
                title: [invoice.unit?.name, invoice.building?.name].filter(Boolean).join(' – ')
                    || invoice.tenant?.name
                    || invoice.invoice_number,
                over: relativeDays(invoice.due_date),
                amount: money(invoice.outstanding_amount ?? invoice.total_amount),
                when: shortDate(invoice.due_date),
            })),
            () => onOpenTab('accounting')
        ),

        panel(
            t('home.recent'),
            activityRows.map((entry) => mediaRow({
                tone: 'muted',
                iconName: 'clock-rewind',
                overFirst: true,
                over: entry.action_label ?? entry.action,
                title: entry.description ?? titleOf(entry),
                when: whenLabel(entry.created_at),
            }))
        ),
    ]);
}
