/*
 * The iPad dashboard: Komla's design, carrying every figure the browser
 * dashboard carries.
 *
 * The web page has four rows - occupancy and the money tiles, the
 * collections trend beside the funds held, overdue beside upcoming rent,
 * expiring leases beside upcoming increments - and a card inviting the
 * first lease when there is none. All of it is here, in the tile-and-panel
 * language of the tablet design, from the same three endpoints.
 */

import { el, mount } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { money } from '../ui/money.js';
import { formatDate, formatNumber } from '../ui/format.js';
import { openDocument } from '../data/exports.js';
import * as store from '../data/store.js';
import { stat, button, loading, section } from '../ui/table.js';
import { rows, showError, bannerHost, partyName } from './common.js';

const SHOWN = 6;

function panel(title, body, { sub, link, onLink, badge } = {}) {
    return el('section', { class: 'card' }, [
        el('header', { class: 'card-head' }, [
            el('div', { class: 'card-words' }, [
                el('div', { class: 'inline' }, [el('h2', { class: 'card-title', text: title }), badge ?? null]),
                sub ? el('p', { class: 'card-sub', text: sub }) : null,
            ]),
            link ? el('button', { class: 'card-link', text: link, onclick: onLink }) : null,
        ]),
        el('div', { class: 'card-body' }, [].concat(body).filter(Boolean)),
    ]);
}

function trendChart(trend) {
    const peak = trend.reduce((high, point) => Math.max(high, Number(point.amount ?? 0)), 0);

    if (trend.length === 0) {
        return el('p', { class: 'muted-small', text: t('ui.dashboard.no_collections') });
    }

    return el('div', { class: 'bars' }, trend.map((point) => {
        const amount = Number(point.amount ?? 0);
        const height = peak > 0 ? Math.max(amount > 0 ? 2 : 0, Math.round((amount / peak) * 100)) : 0;
        const month = new Date(`${point.month}-01T00:00:00`);

        return el('div', { class: 'bar', title: `${point.month} — ${money(amount)}` }, [
            el('div', { class: `bar-fill${amount === 0 ? ' is-zero' : ''}`, style: `height:${amount === 0 ? 2 : height}%` }),
            el('span', { class: 'bar-label', text: Number.isNaN(month.getTime()) ? String(point.month ?? '').slice(5) : new Intl.DateTimeFormat(undefined, { month: 'short' }).format(month) }),
        ]);
    }));
}

function rentList(client, items, count, { onOpenTenant, onOpenTenants }) {
    if (items.length === 0) {
        return el('p', { class: 'muted-small', text: t('ui.dashboard.no_records') });
    }

    return el('div', { class: 'stack' }, [
        ...items.slice(0, SHOWN).map((item) => el('div', {
            class: `inline${item.tenant?.id ? ' is-tappable' : ''}`,
            onclick: item.tenant?.id && onOpenTenant ? () => onOpenTenant(item.tenant.id) : undefined,
        }, [
            el('span', { class: 'grow' }, [
                el('span', { class: 'cell-strong', style: 'display:block', text: item.tenant?.name ?? t('ui.dashboard.tenant') }),
                el('span', { class: 'muted-small', style: 'display:block', text: `${item.building?.name ?? ''} / ${item.unit?.name ?? ''}` }),
                el('span', { class: 'muted-small', style: 'display:block', text: `${item.invoice_number ?? ''} · ${t('ui.dashboard.due')} ${formatDate(item.due_date)}` }),
                item.status === 'partial' ? el('span', { class: 'muted-small', style: 'display:block;color:var(--pm-warning-text)', text: t('ui.dashboard.paid_of_total', { paid: money(item.paid_amount ?? 0), total: money(item.total_amount ?? 0) }) }) : null,
            ]),
            el('span', { class: 'cell-strong', text: money(item.outstanding_amount ?? 0) }),
            el('button', { class: 'icon-button', 'aria-label': t('export.pdf'), onclick: (e) => { e.stopPropagation(); openDocument(client, `/invoices/${item.id}/pdf`).catch(() => {}); } }, [icon('file-05', { size: 18 })]),
        ])),
        count > SHOWN ? el('button', { class: 'link', type: 'button', text: t('ui.dashboard.more_records', { count: count - SHOWN }), onclick: onOpenTenants }) : null,
    ]);
}

export function dashboardScreen(client, { onOpenSection, onOpenTenant, onNewLease } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const body = el('div', { class: 'stack' });

    async function load() {
        mount(body, loading(t('ui.dashboard.loading')));

        const [summaryResult, overdueResult, upcomingResult] = await Promise.allSettled([
            client.get(endpoints.dashboard.summary),
            client.get(endpoints.dashboard.overdue),
            client.get(endpoints.dashboard.upcoming),
        ]);

        const summary = summaryResult.status === 'fulfilled' ? summaryResult.value : null;
        const overdue = overdueResult.status === 'fulfilled' ? overdueResult.value : null;
        const upcoming = upcomingResult.status === 'fulfilled' ? upcomingResult.value : null;

        if (summary === null) {
            showError(errors, summaryResult.reason, t('ui.dashboard.unable_to_load'));
        }

        const metrics = summary?.metrics ?? {};
        const me = store.read('me').data ?? {};
        const user = me.user ?? me;
        const rate = summary?.occupancy_rate ?? (metrics.total_units ? Math.round((metrics.occupied_units / metrics.total_units) * 100) : 0);
        const clamped = Math.min(100, Math.max(0, Number(rate) || 0));
        const failed = (result) => el('p', { class: 'muted-small', text: t('ui.dashboard.unable_to_load_section') });
        const expiring = summary?.expiring_leases ?? [];
        const increments = summary?.upcoming_increments ?? [];

        mount(body,
            el('header', { class: 'greeting' }, [
                el('h1', { class: 'greeting-title', text: t('dash.greeting', { name: (user.given_names ?? user.name ?? '').split(/\s+/)[0] ?? '' }).trim() }),
                el('p', { class: 'greeting-sub', text: t('ui.dashboard.description') }),
            ]),

            (metrics.total_leases ?? 0) === 0 && can('manage_operations') ? section(t('ui.wizard.invite_title'), [
                el('p', { class: 'record-card-sub', text: t('ui.wizard.invite_text') }),
                button(t('ui.wizard.launch'), { kind: 'primary', iconName: 'plus', onClick: onNewLease }),
            ], { tone: 'info' }) : null,

            summary === null ? null : el('div', { class: 'grid-2' }, [
                panel(t('ui.dashboard.occupancy_rate'), [
                    el('span', { class: 'kpi-value', text: `${clamped}%` }),
                    el('div', { class: 'meter' }, [el('div', { class: 'meter-fill', style: `width:${clamped}%` })]),
                    el('div', { class: 'pair-grid pair-grid-4' }, [
                        [t('ui.dashboard.occupied'), metrics.occupied_units], [t('ui.dashboard.vacant'), metrics.vacant_units],
                        [t('ui.dashboard.vacant_commercial'), metrics.vacant_commercial_units], [t('ui.dashboard.vacant_residential'), metrics.vacant_residential_units],
                    ].map(([label, v]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: formatNumber(v ?? 0) })]))),
                ]),
                el('div', { class: 'kpis' }, [
                    stat(t('ui.dashboard.buildings'), formatNumber(metrics.total_buildings ?? 0)),
                    stat(t('ui.dashboard.total_units'), formatNumber(metrics.total_units ?? 0)),
                    stat(t('ui.dashboard.rent_overdue'), money(metrics.rent_overdue ?? 0), { tone: 'danger' }),
                    stat(t('ui.dashboard.rent_due'), money(metrics.rent_due ?? 0)),
                    stat(t('ui.dashboard.collected_this_month'), money(metrics.rent_collected_this_month ?? 0), { tone: 'success' }),
                    stat(t('ui.dashboard.management_fees_this_month'), money(metrics.management_fees_this_month ?? 0)),
                ]),
            ]),

            summary === null ? null : el('div', { class: 'grid-2' }, [
                panel(t('ui.dashboard.collections_trend'), trendChart(summary.collections_trend ?? []), { sub: t('ui.dashboard.collections_trend_description') }),
                panel(t('ui.dashboard.funds_held'), el('div', { class: 'kpis' }, [
                    stat(t('ui.dashboard.owner_funds_held'), money(metrics.owner_funds_held ?? 0)),
                    stat(t('ui.dashboard.tenant_funds_held'), money(metrics.tenant_funds_held ?? 0)),
                ]), { sub: t('ui.dashboard.funds_held_description') }),
            ]),

            el('div', { class: 'grid-2' }, [
                panel(t('ui.dashboard.overdue_rent'), overdue === null ? failed() : rentList(client, rows(overdue), overdue.count ?? rows(overdue).length, { onOpenTenant, onOpenTenants: () => onOpenSection?.('tenants') }), { sub: t('ui.dashboard.overdue_description') }),
                panel(t('ui.dashboard.upcoming_rent'), upcoming === null ? failed() : rentList(client, rows(upcoming), upcoming.count ?? rows(upcoming).length, { onOpenTenant, onOpenTenants: () => onOpenSection?.('tenants') }), { sub: t('ui.dashboard.upcoming_description') }),
            ]),

            summary === null ? null : el('div', { class: 'grid-2' }, [
                panel(t('ui.dashboard.expiring_leases'), expiring.length === 0
                    ? el('p', { class: 'muted-small', text: t('ui.dashboard.no_expiring_leases') })
                    : el('div', { class: 'stack' }, expiring.slice(0, SHOWN).map((lease) => el('div', { class: 'inline' }, [
                        el('span', { class: 'grow' }, [
                            el('span', { class: 'cell-strong', style: 'display:block', text: lease.tenant_name ?? '' }),
                            el('span', { class: 'muted-small', text: `${lease.building_name ?? ''} / ${lease.unit_name ?? ''}` }),
                        ]),
                        el('span', { class: 'muted-small', text: `${t('ui.dashboard.ends')} ${formatDate(lease.end_date)}` }),
                    ]))),
                { sub: t('ui.dashboard.expiring_leases_description'), badge: (metrics.leases_expiring_90_days ?? 0) > 0 ? el('span', { class: 'chip chip-warning', text: String(metrics.leases_expiring_90_days) }) : null, link: t('home.view_all'), onLink: () => onOpenSection?.('leases') }),
                panel(t('ui.dashboard.upcoming_increments'), increments.length === 0
                    ? el('p', { class: 'muted-small', text: t('ui.dashboard.no_increments') })
                    : el('div', { class: 'stack' }, increments.slice(0, SHOWN).map((inc) => el('div', { class: 'inline' }, [
                        el('span', { class: 'grow' }, [
                            el('span', { class: 'cell-strong', style: 'display:block', text: inc.tenant_name ?? '' }),
                            el('span', { class: 'muted-small', text: `${money(inc.old_rent_amount ?? 0)} → ${money(inc.new_rent_amount ?? 0)}` }),
                        ]),
                        el('span', { class: 'muted-small', text: `${t('ui.dashboard.effective')} ${formatDate(inc.effective_date)}` }),
                    ]))),
                { sub: t('ui.dashboard.upcoming_increments_description'), badge: (metrics.increments_upcoming_60_days ?? 0) > 0 ? el('span', { class: 'chip chip-info', text: String(metrics.increments_upcoming_60_days) }) : null, link: t('home.view_all'), onLink: () => onOpenSection?.('leases') }),
            ])
        );
    }

    mount(host,
        el('header', { class: 'screen-head' }, [el('div', {}, [el('p', { class: 'screen-eyebrow', text: t('ui.dashboard.overview') }), el('h1', { class: 'screen-title', text: t('ui.dashboard.heading') })])]),
        errors,
        body
    );

    load();

    return { node: host, reload: load };
}
