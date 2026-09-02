/*
 * Leases - the register and every lifecycle drawer the browser has:
 * the whole letting, financial history, rent increments, extend,
 * terminate, the termination settlement, delete and archive. Plus the
 * unfinished assistants panel, which continues a lease draft.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { openSheet, confirmSheet, informSheet, today } from '../ui/sheet.js';
import { archiveRecord } from '../ui/confirm.js';
import { table, pagedTable, pagination, pageSize, loading, emptyState, badge, button, stat, section } from '../ui/table.js';
import { money } from '../ui/money.js';
import { formatDate, formatLongDate, formatNumber, joinParts, domain } from '../ui/format.js';
import { openDocument } from '../data/exports.js';
import * as store from '../data/store.js';
import {
    screenHead, bannerHost, showError, showSuccess, rows, pageMeta, filterField, selectControl, dateControl,
    query, partyName, leaseStatusChip, leaseStatusLabel, frequencyLabel, paymentMethodLabel, fundTypeLabel,
    pdfButton, fileButton, dash, dl,
} from './common.js';
import { leaseWizard } from './lease-wizard.js';

const STATUSES = ['draft', 'active', 'notice', 'terminated'];
const FREQUENCIES = ['monthly', 'quarterly', 'bi_yearly', 'yearly'];

function leaseTitle(lease) {
    return joinParts([lease.unit?.building?.name, lease.unit?.name], ' / ');
}

function kindValue(type, value) {
    if (type === 'percentage') {
        return `${formatNumber(value)}%`;
    }

    if (type === 'fixed') {
        return money(value);
    }

    return t('lease.kind.none');
}

function kindLabel(type) {
    const map = { none: t('lease.kind.none'), percentage: t('lease.kind.percentage'), fixed: t('lease.kind.fixed') };

    return map[type] ?? dash(type);
}

export function leasesScreen(client, { onOpenTenant } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const drafts = el('div');
    const kpis = el('div', { class: 'kpis' });
    const list = el('div');
    const size = pageSize('leases');
    const filters = { status: '', tenant_id: '', building_id: '', payment_frequency: '', ending_before: '' };
    let page = 1;
    let payload = null;
    let tenants = [];
    let buildings = [];

    const controls = el('div', { class: 'filters' });

    function paintControls() {
        mount(controls,
            filterField(t('ui.leases.status'), selectControl([{ value: '', label: t('ui.leases.all_statuses') }, ...STATUSES.map((s) => ({ value: s, label: t(`ui.leases.status_${s}`) }))], filters.status, (v) => { filters.status = v; reload(1); })),
            filterField(t('ui.leases.tenant'), selectControl([{ value: '', label: t('ui.leases.all_tenants') }, ...tenants.map((p) => ({ value: p.id, label: partyName(p) }))], filters.tenant_id, (v) => { filters.tenant_id = v; reload(1); })),
            filterField(t('ui.leases.property'), selectControl([{ value: '', label: t('ui.leases.all_buildings') }, ...buildings.map((b) => ({ value: b.id, label: b.name }))], filters.building_id, (v) => { filters.building_id = v; reload(1); })),
            filterField(t('ui.leases.payment_frequency'), selectControl([{ value: '', label: t('ui.leases.all_frequencies') }, ...FREQUENCIES.map((f) => ({ value: f, label: t(`ui.leases.${f}`) }))], filters.payment_frequency, (v) => { filters.payment_frequency = v; reload(1); })),
            filterField(t('ui.leases.expiring_before'), dateControl(filters.ending_before, (v) => { filters.ending_before = v; reload(1); }))
        );
    }

    /* ------------------------------------------------------- drafts */

    async function loadDrafts() {
        try {
            const found = rows(await client.get('/lease-wizard/drafts'));

            mount(drafts, found.length === 0 ? null : section(t('ui.wizard.drafts_title'), [
                el('p', { class: 'muted-small', text: t('ui.wizard.drafts_note') }),
                ...found.map((draft) => {
                    const discard = button(t('ui.wizard.drafts_discard'), { kind: 'danger-outline', iconName: 'trash-01', onClick: async (node) => {
                        if (node.dataset.armed !== '1') {
                            node.dataset.armed = '1';
                            node.querySelector('span').textContent = t('ui.wizard.drafts_discard_confirm');
                            node.classList.replace('button-danger-outline', 'button-danger');
                            setTimeout(() => {
                                if (node.isConnected) {
                                    node.dataset.armed = '0';
                                    node.querySelector('span').textContent = t('ui.wizard.drafts_discard');
                                    node.classList.replace('button-danger', 'button-danger-outline');
                                }
                            }, 5000);

                            return;
                        }

                        try {
                            await client.delete(`/lease-wizard/drafts/${draft.id}`);
                            loadDrafts();
                        } catch (failure) {
                            showError(errors, failure, t('ui.wizard.drafts_discard_failed'));
                        }
                    } });

                    return el('div', { class: 'inline' }, [
                        el('span', { class: 'grow', text: joinParts([draft.author, formatLongDate(draft.started_at)]) }),
                        button(t('ui.wizard.drafts_continue'), { kind: 'primary', iconName: 'arrow-right', onClick: () => openWizard(draft.id) }),
                        discard,
                    ]);
                }),
            ]));
        } catch {
            mount(drafts);
        }
    }

    async function openWizard(draftId = null) {
        const created = await leaseWizard(client, { draftId });

        if (created) {
            await store.refreshAll(client);
            reload(1);
        }

        loadDrafts();
    }

    /* ------------------------------------------------------ drawers */

    async function viewComposition(lease) {
        let ownerships = null;

        try {
            const building = await client.get(endpoints.building(lease.unit?.building_id ?? lease.unit?.building?.id));

            ownerships = building?.ownerships ?? [];
        } catch {
            ownerships = null;
        }

        const kindRow = (type, value) => (type && type !== 'none' ? kindValue(type, value) : kindLabel('none'));

        await openSheet({
            title: t('ui.leases.composition'),
            description: t('ui.leases.composition_description'),
            width: 'lg',
            submitLabel: t('ui.actions.close'),
            fields: [{ name: 'body', type: 'note', label: '' }],
            onSubmit: async () => true,
            onChange: (values, api) => {
                mount(api.get('body').node,
                    section(t('ui.leases.composition_property'), dl([
                        [t('ui.leases.property'), lease.unit?.building?.name],
                        [t('ui.leases.unit'), lease.unit?.name],
                        [t('ui.leases.reference'), lease.reference],
                        [t('ui.leases.status'), leaseStatusLabel(lease.status)],
                    ])),
                    section(t('ui.leases.composition_owners'), ownerships === null
                        ? el('p', { class: 'muted-small', text: t('ui.leases.composition_owners_unavailable') })
                        : dl(ownerships.map((o) => [partyName(o.party), `${formatNumber(o.ownership_percentage)}%`]))),
                    section(t('ui.leases.composition_parties'), dl([
                        [t('ui.leases.tenant'), partyName(lease.tenant)],
                        [t('ui.leases.agent'), lease.agent ? partyName(lease.agent) : t('ui.leases.composition_no_agent')],
                        lease.agent ? [t('ui.leases.agent_commission'), money(lease.agent_commission_amount ?? 0)] : null,
                    ])),
                    section(t('ui.leases.composition_dates'), dl([
                        [t('ui.leases.start_date'), formatDate(lease.start_date)],
                        [t('ui.leases.end_date'), lease.end_date ? formatDate(lease.end_date) : t('ui.leases.composition_open_ended')],
                        lease.termination_notice_date ? [t('ui.leases.notice_date'), formatDate(lease.termination_notice_date)] : null,
                        lease.termination_date ? [t('ui.leases.termination_date'), formatDate(lease.termination_date)] : null,
                    ])),
                    section(t('ui.leases.composition_rent'), dl([
                        [t('ui.leases.monthly_rent'), money(lease.rent_amount)],
                        [t('ui.leases.payment_frequency'), frequencyLabel(lease.payment_frequency)],
                        [t('ui.leases.due_day'), lease.due_day],
                        lease.proration_amount !== null && lease.proration_amount !== undefined ? [t('ui.leases.proration'), money(lease.proration_amount)] : null,
                    ])),
                    section(t('ui.leases.composition_held'), dl([
                        [t('ui.leases.security_deposit'), money(lease.security_deposit_amount ?? 0)],
                        [t('ui.leases.rent_reserve'), money(lease.rent_reserve_amount ?? 0)],
                        [t('ui.leases.advance_payment'), money(lease.advance_payment_amount ?? 0)],
                    ])),
                    section(t('ui.leases.composition_increases'), dl([
                        [t('ui.leases.increment_type'), kindLabel(lease.rent_increment_type)],
                        [t('ui.leases.increment_value'), kindRow(lease.rent_increment_type, lease.rent_increment_value)],
                        lease.next_rent_increment_date ? [t('ui.leases.next_increment_date'), formatDate(lease.next_rent_increment_date)] : null,
                    ])),
                    section(t('ui.leases.composition_fees'), dl([
                        [t('ui.leases.management_fee'), kindLabel(lease.management_fee_type)],
                        [t('ui.leases.fee_value'), kindRow(lease.management_fee_type, lease.management_fee_value)],
                        [t('ui.leases.vat_rate'), lease.vat_rate],
                    ])),
                    lease.notes ? section(t('ui.leases.composition_notes'), dl([[t('ui.leases.notes'), lease.notes]])) : null
                );
            },
        });
    }

    async function financialHistory(lease) {
        let history = null;

        try {
            history = await client.get(endpoints.leaseFinancialHistory(lease.id));
        } catch (failure) {
            showError(errors, failure, t('ui.leases.financial_history_unable_load'));

            return;
        }

        const events = Array.isArray(history?.events) ? history.events : [];
        const base = endpoints.leaseFinancialHistory(lease.id);

        await openSheet({
            title: t('ui.leases.financial_history'),
            description: joinParts([lease.unit?.building?.name, lease.unit?.name, partyName(lease.tenant)]) || t('ui.leases.financial_history_description'),
            width: 'lg',
            submitLabel: t('ui.actions.close'),
            fields: [{ name: 'body', type: 'note', label: '' }],
            onSubmit: async () => true,
            onChange: (values, api) => {
                mount(api.get('body').node,
                    el('div', { class: 'inline' }, [
                        pdfButton(client, `${base}/pdf`, t('ui.leases.financial_history_export_pdf'), { onFail: (f) => showError(errors, f) }),
                        fileButton(client, `${base}/xlsx`, t('ui.leases.financial_history_export_excel'), `lease-financial-history-${lease.id}.xlsx`, { onFail: (f) => showError(errors, f) }),
                        fileButton(client, `${base}/csv`, t('ui.leases.financial_history_export_csv'), `lease-financial-history-${lease.id}.csv`, { onFail: (f) => showError(errors, f) }),
                    ]),
                    events.length === 0
                        ? emptyState('coins-stacked', t('ui.leases.financial_history_empty'), t('ui.leases.financial_history_empty_description'))
                        : pagedTable('lease-financial-history', [
                            { label: t('ui.leases.financial_history_export_date'), cell: (e) => formatDate(e.occurred_on) },
                            { label: t('ui.leases.financial_history_export_type'), cell: (e) => domain('leases', `financial_history_event_${e.event_type}`) },
                            { label: t('ui.leases.financial_history_export_reference'), cell: (e) => dash(e.reference) },
                            { label: t('ui.leases.financial_history_export_fund'), cell: (e) => (e.fund_type ? fundTypeLabel(e.fund_type) : '—') },
                            { label: t('ui.leases.financial_history_export_payment_method'), cell: (e) => (e.payment_method ? paymentMethodLabel(e.payment_method) : '—') },
                            { label: t('ui.leases.financial_history_export_amount'), align: 'right', cell: (e) => money(e.amount ?? 0) },
                            { label: t('ui.leases.financial_history_export_document'), cell: (e) => (e.document?.endpoint
                                ? button(t('ui.leases.financial_history_open'), { iconName: 'file-05', onClick: () => openDocument(client, String(e.document.endpoint).replace(/^\/api(\/v\d+)?/, '')).catch((f) => showError(errors, f, t('ui.leases.financial_history_unable_open_document'))) })
                                : '—') },
                        ], events, { panel: false })
                );
            },
        });
    }

    async function rentIncrements(lease) {
        const repaint = async (api) => {
            let increments = [];

            try {
                increments = rows((await client.get(`${endpoints.lease(lease.id)}/rent-increments`))?.rent_increments);
            } catch (failure) {
                showError(errors, failure, t('ui.leases.rent_increments_loading'));
            }

            mount(api.get('list').node, increments.length === 0
                ? el('p', { class: 'muted-small', text: t('ui.leases.no_rent_increments') })
                : el('div', { class: 'stack' }, increments.map((inc) => el('div', { class: 'record-card' }, [
                    el('div', { class: 'record-card-head' }, [
                        el('span', { class: 'cell-strong', text: `${money(inc.old_rent_amount)} → ${money(inc.new_rent_amount)}` }),
                        badge(t(`ui.leases.increment_status_${inc.status}`), inc.status),
                    ]),
                    el('p', { class: 'record-card-sub', text: `${inc.increment_type === 'percentage' ? `+${formatNumber(inc.increment_value)}%` : `+${money(inc.increment_value)}`} · ${t('ui.leases.effective_date')}: ${formatDate(inc.effective_date)}` }),
                    el('p', { class: 'muted-small', text: joinParts([
                        inc.notification_sent_at || inc.notified_at ? `${t('ui.leases.notification_sent')} ${formatDate(String(inc.notification_sent_at ?? inc.notified_at).slice(0, 10))}` : null,
                        inc.applied_at ? `${t('ui.leases.applied_on')} ${formatDate(String(inc.applied_at).slice(0, 10))}` : null,
                        inc.cancelled_at ? `${t('ui.leases.cancelled_on')} ${formatDate(String(inc.cancelled_at).slice(0, 10))}` : null,
                    ]) }),
                    inc.status === 'scheduled' && can('manage_operations') ? el('div', { class: 'record-card-actions' }, [
                        button(t('ui.leases.cancel_increment'), { kind: 'danger-outline', onClick: async () => {
                            if (await confirmSheet({ title: t('ui.leases.cancel_increment'), body: t('ui.leases.confirm_cancel_increment'), confirmLabel: t('ui.leases.cancel_increment'), danger: true })) {
                                try {
                                    await client.post(`/rent-increments/${inc.id}/cancel`, {});
                                    repaint(api);
                                } catch (failure) {
                                    showError(errors, failure, t('ui.leases.increment_cancel_failed'));
                                }
                            }
                        } }),
                    ]) : null,
                ]))));
        };

        await openSheet({
            title: t('ui.leases.rent_increments'),
            description: t('ui.leases.rent_increments_description'),
            width: 'lg',
            submitLabel: can('manage_operations') ? t('ui.leases.schedule_increment') : t('ui.actions.close'),
            fields: [
                { name: 'list', type: 'note', label: '' },
                ...(can('manage_operations') ? [
                    { name: 'h', type: 'heading', label: t('ui.leases.schedule_increment') },
                    { name: 'increment_type', type: 'select', label: t('ui.leases.increment_type'), value: 'percentage', options: [{ value: 'percentage', label: kindLabel('percentage') }, { value: 'fixed', label: kindLabel('fixed') }], required: true },
                    { name: 'increment_value', type: 'number', label: t('ui.leases.increment_value'), min: 0.01, step: 0.01, required: true },
                    { name: 'effective_date', type: 'date', label: t('ui.leases.effective_date'), required: true },
                ] : []),
            ],
            onChange: (values, api, changed) => {
                if (changed === null) {
                    mount(api.get('list').node, loading(t('ui.leases.rent_increments_loading')));
                    repaint(api);
                }
            },
            validate: (values) => {
                if (! can('manage_operations')) {
                    return null;
                }

                if (! values.effective_date) {
                    return { effective_date: t('ui.leases.increment_invalid_date') };
                }

                return null;
            },
            onSubmit: async (values) => {
                if (! can('manage_operations')) {
                    return true;
                }

                await client.post(`${endpoints.lease(lease.id)}/rent-increments`, {
                    increment_type: values.increment_type,
                    increment_value: Number(values.increment_value),
                    effective_date: values.effective_date,
                });
            },
        });
    }

    async function extend(lease) {
        const saved = await openSheet({
            title: t('ui.leases.extend_lease'),
            description: t('ui.leases.extend_description'),
            width: 'lg',
            submitLabel: t('ui.leases.extend_lease'),
            fields: [
                { name: 'h0', type: 'heading', label: t('ui.leases.current_terms') },
                { name: 'cur', type: 'note', label: joinParts([
                    `${t('ui.leases.monthly_rent')}: ${money(lease.rent_amount)}`,
                    `${t('ui.leases.payment_frequency')}: ${frequencyLabel(lease.payment_frequency)}`,
                    `${t('ui.leases.end_date')}: ${lease.end_date ? formatDate(lease.end_date) : t('ui.leases.composition_open_ended')}`,
                    `${t('ui.leases.due_day')}: ${lease.due_day ?? String(lease.start_date ?? '').slice(8, 10)}`,
                ]) },
                { name: 'h1', type: 'heading', label: t('ui.leases.new_terms') },
                { name: 'effective_from', type: 'date', label: t('ui.leases.effective_from'), required: true },
                { name: 'end_date', type: 'date', label: t('ui.leases.end_date'), value: lease.end_date ?? '' },
                { name: 'rent_amount', type: 'money', label: t('ui.leases.monthly_rent'), value: String(lease.rent_amount ?? ''), required: true },
                { name: 'payment_frequency', type: 'select', label: t('ui.leases.payment_frequency'), value: lease.payment_frequency, options: FREQUENCIES.map((f) => ({ value: f, label: frequencyLabel(f) })), required: true },
                { name: 'due_day', type: 'number', label: t('ui.leases.due_day'), value: lease.due_day ?? '', min: 1, max: 31, step: 1 },
                { name: 'vat_rate', type: 'number', label: t('ui.leases.vat_rate'), value: String(lease.vat_rate ?? 0), min: 0, max: 100, step: 0.01, suffix: '%', required: true },
                { name: 'h2', type: 'heading', label: t('ui.leases.rent_increment') },
                { name: 'rent_increment_type', type: 'select', label: t('ui.leases.increment_type'), value: lease.rent_increment_type ?? 'none', options: ['none', 'percentage', 'fixed'].map((k) => ({ value: k, label: kindLabel(k) })) },
                { name: 'rent_increment_value', type: 'number', label: t('ui.leases.increment_value'), value: String(lease.rent_increment_value ?? 0), min: 0, step: 0.01, when: (v) => v.rent_increment_type !== 'none' },
                { name: 'next_rent_increment_date', type: 'date', label: t('ui.leases.next_increment_date'), value: lease.next_rent_increment_date ?? '', when: (v) => v.rent_increment_type !== 'none' },
                { name: 'notes', type: 'textarea', rows: 4, label: t('ui.leases.notes'), value: lease.notes ?? '' },
            ],
            onSubmit: async (values) => {
                const none = values.rent_increment_type === 'none';

                await client.post(`${endpoints.lease(lease.id)}/extend`, {
                    effective_from: values.effective_from,
                    end_date: values.end_date || null,
                    rent_amount: Number(values.rent_amount || 0),
                    payment_frequency: values.payment_frequency,
                    due_day: values.due_day ? Number(values.due_day) : null,
                    vat_rate: Number(values.vat_rate || 0),
                    rent_increment_type: values.rent_increment_type,
                    rent_increment_value: none ? 0 : Number(values.rent_increment_value || 0),
                    next_rent_increment_date: none ? null : (values.next_rent_increment_date || null),
                    notes: values.notes || null,
                });
            },
        });

        if (saved) {
            await store.refreshAll(client);
            reload(1);
        }
    }

    async function terminate(lease) {
        const done = await openSheet({
            title: t('ui.leases.terminate_lease'),
            description: t('ui.leases.termination_description'),
            width: 'lg',
            submitLabel: t('ui.leases.initiate_termination'),
            submitKind: 'danger',
            fields: [
                { name: 'h0', type: 'heading', label: t('ui.leases.lease_context') },
                { name: 'ctx', type: 'note', label: joinParts([
                    `${t('ui.leases.lease')}: ${lease.reference ?? `#${lease.id}`}`,
                    `${t('ui.leases.tenant')}: ${partyName(lease.tenant)}`,
                    `${t('ui.leases.property')}: ${lease.unit?.building?.name ?? ''}`,
                    `${t('ui.leases.unit')}: ${lease.unit?.name ?? ''}`,
                    `${t('ui.leases.status')}: ${leaseStatusLabel(lease.status)}`,
                ]) },
                { name: 'h1', type: 'heading', label: t('ui.leases.termination_details') },
                { name: 'notice_date', type: 'date', label: t('ui.leases.notice_date'), required: true },
                { name: 'termination_date', type: 'date', label: t('ui.leases.termination_date'), required: true },
                { name: 'h2', type: 'heading', label: t('ui.leases.final_rent_treatment') },
                { name: 'final_rent_mode', type: 'radio', label: t('ui.leases.final_rent_treatment'), value: 'prorate', options: [
                    { value: 'prorate', label: t('ui.leases.final_rent_prorate'), hint: t('ui.leases.final_rent_prorate_help') },
                    { value: 'full', label: t('ui.leases.final_rent_full'), hint: t('ui.leases.final_rent_full_help') },
                    { value: 'none', label: t('ui.leases.final_rent_none'), hint: t('ui.leases.final_rent_none_help') },
                ] },
            ],
            validate: (values) => (! values.notice_date || ! values.termination_date || ! values.final_rent_mode ? { _: t('ui.leases.termination_required_fields') } : null),
            onSubmit: async (values) => {
                await client.post(`${endpoints.lease(lease.id)}/termination`, {
                    notice_date: values.notice_date,
                    termination_date: values.termination_date,
                    final_rent_mode: values.final_rent_mode,
                });
            },
        });

        if (done) {
            await store.refreshAll(client);
            reload(1);
            showSuccess(errors, t('ui.leases.termination_notice_ready'), [
                { label: t('ui.leases.open_termination_notice'), onClick: () => openDocument(client, `${endpoints.lease(lease.id)}/termination-notice/pdf`).catch((f) => showError(errors, f, t('ui.leases.termination_notice_unable_open'))) },
            ]);
        }
    }

    async function settlement(lease) {
        let payload = null;

        try {
            payload = await client.get(`${endpoints.lease(lease.id)}/termination-settlement`);
        } catch (failure) {
            showError(errors, failure, t('ui.leases.termination_settlement_load_failed'));

            return;
        }

        const settle = payload?.settlement ?? {};
        const debt = payload?.debt ?? {};
        const funds = payload?.funds ?? {};
        const security = payload?.security_deposit ?? {};
        const info = payload?.lease ?? {};
        const blockers = Array.isArray(settle.blockers) ? settle.blockers : [];
        const deductionsAllowed = can('manage_finance') && (info.status === 'terminated' || info.status === 'notice');

        const outcome = await openSheet({
            title: t('ui.leases.termination_settlement'),
            description: t('ui.leases.termination_settlement_description'),
            width: 'lg',
            submitLabel: t('ui.leases.complete_termination'),
            submitDisabled: settle.can_complete !== true || ! can('manage_operations'),
            fields: [
                { name: 'ctx', type: 'note', label: joinParts([
                    `${t('ui.leases.lease')}: #${info.id ?? lease.id}`,
                    `${t('ui.leases.tenant')}: ${info.tenant ?? partyName(lease.tenant)}`,
                    `${t('ui.leases.property')}: ${info.building ?? ''}`,
                    `${t('ui.leases.unit')}: ${info.unit ?? ''}`,
                    `${t('ui.leases.notice_date')}: ${formatDate(info.termination_notice_date) || '—'}`,
                    `${t('ui.leases.termination_date')}: ${formatDate(info.termination_date) || '—'}`,
                ]) },
                { name: 'h1', type: 'heading', label: t('ui.leases.termination_financial_position') },
                { name: 'position', type: 'note', label: '' },
                ...(deductionsAllowed ? [
                    { name: 'h2', type: 'heading', label: t('ui.leases.record_deduction'), hint: t('ui.leases.record_deduction_description') },
                    { name: 'deduction_description', type: 'text', label: t('ui.leases.deduction_description'), placeholder: t('ui.leases.deduction_description_placeholder'), maxlength: 255 },
                    { name: 'deduction_amount', type: 'money', label: t('ui.leases.deduction_amount') },
                    { name: 'deduction_date', type: 'date', label: t('ui.leases.deduction_date'), value: today() },
                    { name: 'deduction_add', type: 'note', label: '' },
                ] : []),
                { name: 'h3', type: 'heading', label: t('ui.leases.termination_unresolved_items') },
                { name: 'blockers', type: 'note', label: '' },
                { name: 'resolve', type: 'note', tone: 'info', label: t('ui.leases.termination_resolve_from_tenant') },
                { name: 'links', type: 'note', label: '' },
            ],
            onChange: (values, api, changed) => {
                if (changed !== null) {
                    return;
                }

                mount(api.get('position').node, dl([
                    [t('ui.leases.outstanding_debt'), money(debt.total_outstanding ?? 0)],
                    [t('ui.leases.rent_reserve'), money(funds.rent_reserve_remaining ?? 0)],
                    [t('ui.leases.consumable_advance'), money(funds.consumable_advance_remaining ?? 0)],
                    [t('ui.leases.security_deposit'), money(funds.security_deposit_held ?? 0)],
                    [t('ui.leases.security_deposit_deductions'), money(security.deduction_total ?? 0)],
                    [t('ui.leases.other_tenant_funds'), money(funds.other_tenant_funds_balance ?? 0)],
                    [t('ui.leases.amount_still_owed'), money(settle.amount_still_owed_by_tenant ?? 0)],
                    [t('ui.leases.final_refundable_amount'), money(settle.potential_refundable_amount ?? 0)],
                ]));

                mount(api.get('blockers').node, blockers.length === 0
                    ? el('p', { class: 'sheet-note is-success', text: t('ui.leases.termination_no_blockers') })
                    : el('ul', { class: 'stack' }, blockers.map((b) => el('li', { class: 'sheet-note is-warning', text: typeof b === 'string' ? b : (b.message ?? b.reason ?? b.label ?? t('ui.leases.termination_unresolved_item')) }))));

                if (deductionsAllowed) {
                    mount(api.get('deduction_add').node, button(t('ui.leases.add_deduction'), { iconName: 'plus', onClick: async () => {
                        const v = api.values();
                        const amount = Number(v.deduction_amount);

                        if (! v.deduction_description || ! (amount > 0) || ! v.deduction_date) {
                            api.error('deduction_description', t('ui.leases.deduction_fields_required'));

                            return;
                        }

                        try {
                            await client.post(`${endpoints.lease(lease.id)}/security-deposit/deductions`, { description: v.deduction_description, amount, deduction_date: v.deduction_date });
                            api.set('deduction_description', '');
                            api.set('deduction_amount', '');
                            /* Re-read the position: a deduction moves the refund. */
                            const fresh = await client.get(`${endpoints.lease(lease.id)}/termination-settlement`);

                            Object.assign(settle, fresh?.settlement ?? {});
                            Object.assign(security, fresh?.security_deposit ?? {});
                            Object.assign(funds, fresh?.funds ?? {});
                            Object.assign(debt, fresh?.debt ?? {});
                            blockers.splice(0, blockers.length, ...(Array.isArray(settle.blockers) ? settle.blockers : []));
                            api.setSubmitDisabled(settle.can_complete !== true || ! can('manage_operations'));
                            api.error('deduction_description', null);
                            onChangeAgain(api);
                        } catch (failure) {
                            api.error('deduction_description', `${failure?.message ?? t('ui.leases.deduction_record_failed')}${failure?.code ? ` (${failure.code})` : ''}`);
                        }
                    } }));
                }

                mount(api.get('links').node, el('div', { class: 'inline' }, [
                    onOpenTenant ? button(t('ui.leases.go_to_tenant'), { iconName: 'users-01', disabled: ! (info.tenant_id ?? lease.tenant_id), onClick: () => { onOpenTenant(info.tenant_id ?? lease.tenant_id); } }) : null,
                    pdfButton(client, `${endpoints.lease(lease.id)}/termination-notice/pdf`, t('ui.leases.open_termination_notice'), { onFail: (f) => showError(errors, f, t('ui.leases.termination_notice_unable_open')) }),
                    can('manage_operations') ? button(t('ui.leases.cancel_termination'), { kind: 'danger-outline', onClick: async () => {
                        if (await confirmSheet({ title: t('ui.leases.cancel_termination'), body: t('ui.leases.confirm_cancel_termination'), confirmLabel: t('ui.leases.cancel_termination'), danger: true })) {
                            try {
                                await client.post(`${endpoints.lease(lease.id)}/termination/cancel`, {});
                                api.setSubmitDisabled(true);
                                document.querySelectorAll('.sheet-backdrop')[document.querySelectorAll('.sheet-backdrop').length - 1]?.remove();
                                await store.refreshAll(client);
                                reload(1);
                            } catch (failure) {
                                showError(errors, failure, t('ui.leases.termination_cancel_failed'));
                            }
                        }
                    } }) : null,
                ]));

                function onChangeAgain(inner) {
                    inner.get('position') && mount(inner.get('position').node, dl([
                        [t('ui.leases.outstanding_debt'), money(debt.total_outstanding ?? 0)],
                        [t('ui.leases.rent_reserve'), money(funds.rent_reserve_remaining ?? 0)],
                        [t('ui.leases.consumable_advance'), money(funds.consumable_advance_remaining ?? 0)],
                        [t('ui.leases.security_deposit'), money(funds.security_deposit_held ?? 0)],
                        [t('ui.leases.security_deposit_deductions'), money(security.deduction_total ?? 0)],
                        [t('ui.leases.other_tenant_funds'), money(funds.other_tenant_funds_balance ?? 0)],
                        [t('ui.leases.amount_still_owed'), money(settle.amount_still_owed_by_tenant ?? 0)],
                        [t('ui.leases.final_refundable_amount'), money(settle.potential_refundable_amount ?? 0)],
                    ]));
                    mount(inner.get('blockers').node, blockers.length === 0
                        ? el('p', { class: 'sheet-note is-success', text: t('ui.leases.termination_no_blockers') })
                        : el('ul', { class: 'stack' }, blockers.map((b) => el('li', { class: 'sheet-note is-warning', text: typeof b === 'string' ? b : (b.message ?? b.reason ?? b.label ?? t('ui.leases.termination_unresolved_item')) }))));
                }
            },
            onSubmit: async () => {
                if (! await confirmSheet({ title: t('ui.leases.complete_termination'), body: t('ui.leases.confirm_complete_termination'), confirmLabel: t('ui.leases.complete_termination') })) {
                    return false;
                }

                await client.post(`${endpoints.lease(lease.id)}/termination/complete`, {});

                return true;
            },
        });

        if (outcome === true) {
            await store.refreshAll(client);
            reload(1);
        }
    }

    async function remove(lease) {
        let impact = null;

        try {
            impact = await client.get(`${endpoints.lease(lease.id)}/deletion-impact`);
        } catch (failure) {
            showError(errors, failure, t('ui.leases.delete_impact_failed'));
        }

        const eligibility = impact?.eligibility ?? {};
        const restoration = impact?.operational?.monetary_restoration ?? {};
        const accounts = restoration?.tenant_funds?.accounts ?? [];
        const balanceOf = (type) => accounts.find((a) => a.type === type)?.balance ?? 0;
        const deleteInOrder = impact?.operational?.delete_in_order ?? [];
        const countOf = (tableName) => (deleteInOrder.find((d) => d.table === tableName)?.ids ?? []).length;
        const safe = eligibility.safe_to_execute === true && (eligibility.blocking_reasons ?? []).length === 0;

        const done = await openSheet({
            title: t('ui.leases.delete_lease'),
            description: t('ui.leases.delete_destructive_action'),
            width: 'lg',
            submitLabel: t('ui.leases.delete_permanently'),
            submitKind: 'danger',
            submitDisabled: ! safe,
            fields: [
                { name: 'h0', type: 'heading', label: t('ui.leases.delete_context') },
                { name: 'ctx', type: 'note', label: joinParts([
                    `${t('ui.leases.lease')}: #${lease.id}`,
                    `${t('ui.leases.tenant')}: ${partyName(lease.tenant)}`,
                    `${t('ui.leases.property')}: ${lease.unit?.building?.name ?? ''}`,
                    `${t('ui.leases.unit')}: ${lease.unit?.name ?? ''}`,
                ]) },
                { name: 'h1', type: 'heading', label: t('ui.leases.delete_impact_title'), hint: t('ui.leases.delete_impact_description') },
                { name: 'impact', type: 'note', label: '' },
                { name: 'blocked', type: 'note', tone: safe ? 'success' : 'danger', label: safe ? t('ui.leases.delete_impact_safe') : `${t('ui.leases.delete_blocked')} ${(eligibility.blocking_reasons ?? []).join(' · ')}` },
                { name: 'reason', type: 'textarea', rows: 4, maxlength: 2000, label: t('ui.leases.delete_reason'), required: true },
                { name: 'confirmation', type: 'text', label: t('ui.leases.delete_confirmation_label'), required: true },
                { name: 'current_password', type: 'password', label: t('ui.leases.delete_password'), autocomplete: 'current-password', required: true },
            ],
            onChange: (values, api, changed) => {
                if (changed === null) {
                    mount(api.get('impact').node, el('div', { class: 'pair-grid' }, [
                        [t('ui.leases.delete_impact_invoices'), formatNumber(restoration.invoices?.count ?? 0)],
                        [t('ui.leases.delete_impact_payments'), formatNumber(restoration.payments?.count ?? 0)],
                        [t('ui.leases.delete_impact_allocations'), formatNumber(countOf('payment_allocations'))],
                        [t('ui.leases.delete_impact_receipts'), formatNumber(countOf('withdrawal_receipts'))],
                        [t('ui.leases.delete_impact_security'), money(balanceOf('security_deposit'))],
                        [t('ui.leases.delete_impact_reserve'), money(balanceOf('rent_reserve'))],
                        [t('ui.leases.delete_impact_consumable'), money(balanceOf('consumable_advance'))],
                        [t('ui.leases.delete_impact_outstanding'), money(restoration.invoices?.outstanding ?? 0)],
                        [t('ui.leases.delete_impact_reversals'), formatNumber((impact?.accounting?.reversal_candidates ?? []).length)],
                        [t('ui.leases.delete_impact_owner'), money(restoration.owner?.net_lease_effect ?? 0)],
                    ].map(([label, value]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: value })]))));
                }
            },
            validate: (values) => {
                if (values.reason === '') {
                    return { reason: t('ui.leases.delete_reason_required') };
                }

                if (values.confirmation !== 'DELETE') {
                    return { confirmation: t('ui.leases.delete_confirmation_invalid') };
                }

                if (values.current_password === '') {
                    return { current_password: t('ui.leases.delete_password_required') };
                }

                return null;
            },
            onSubmit: async (values) => {
                if (! await confirmSheet({ title: t('ui.leases.delete_lease'), body: t('ui.leases.delete_final_confirmation'), confirmLabel: t('ui.leases.delete_permanently'), danger: true })) {
                    return false;
                }

                await client.delete(endpoints.lease(lease.id), { reason: values.reason, confirmation: values.confirmation, current_password: values.current_password });

                return true;
            },
        });

        if (done === true) {
            await store.refreshAll(client);
            reload(1);
        }
    }

    async function archive(lease) {
        try {
            if (await archiveRecord(client, { kind: 'lease', id: lease.id, label: `${leaseTitle(lease)} — ${partyName(lease.tenant)}` })) {
                await store.refreshAll(client);
                reload(1);
            }
        } catch (failure) {
            showError(errors, failure, t('ui.archive.archive_failed'));
        }
    }

    /* --------------------------------------------------------- cards */

    function card(lease) {
        const manage = can('manage_operations');

        return el('article', { class: 'record-card' }, [
            el('div', { class: 'record-card-head' }, [
                el('h3', { class: 'record-card-title', text: leaseTitle(lease) || `#${lease.id}` }),
                leaseStatusChip(lease.status),
            ]),
            el('p', { class: 'record-card-sub', text: `${t('ui.leases.tenant')}: ${partyName(lease.tenant)}` }),
            el('div', { class: 'record-card-actions' }, [
                button(t('ui.leases.view'), { iconName: 'eye', onClick: () => viewComposition(lease) }),
                button(t('ui.leases.financial_history'), { iconName: 'coins-stacked', onClick: () => financialHistory(lease) }),
                button(t('ui.leases.rent_increments'), { iconName: 'trend-up', onClick: () => rentIncrements(lease) }),
                manage && lease.status !== 'notice' ? button(t('ui.leases.extend'), { iconName: 'calendar', onClick: () => extend(lease) }) : null,
                lease.status === 'notice' ? button(t('ui.leases.termination_settlement'), { kind: 'warning', iconName: 'scale-balanced', onClick: () => settlement(lease) }) : null,
                manage && lease.status !== 'notice' && lease.status !== 'terminated' ? button(t('ui.leases.terminate'), { kind: 'danger-outline', iconName: 'x-circle', onClick: () => terminate(lease) }) : null,
                manage ? (lease.is_deletable === false
                    ? button(t('ui.archive.archive'), { kind: 'danger-outline', iconName: 'archive', onClick: () => archive(lease) })
                    : button(t('ui.leases.delete'), { kind: 'danger-outline', iconName: 'trash-01', onClick: () => remove(lease) })) : null,
            ].filter(Boolean)),
        ]);
    }

    function paint() {
        const counts = payload?.status_counts ?? {};
        const found = rows(payload);

        mount(kpis,
            stat(t('ui.leases.total_leases'), formatNumber(counts.total ?? payload?.total ?? found.length)),
            stat(t('ui.leases.status_active'), formatNumber(counts.active ?? 0), { tone: 'success' }),
            stat(t('ui.leases.in_notice'), formatNumber(counts.notice ?? 0), { tone: 'warning' }),
            stat(t('ui.leases.status_draft'), formatNumber(counts.draft ?? 0))
        );

        mount(list,
            found.length === 0
                ? emptyState('file-check', t('ui.leases.none_found'), t('ui.leases.none_found_description'))
                : el('div', {}, found.map(card)),
            pagination(pageMeta(payload, size.get()), size, reload)
        );
    }

    async function reload(next = page) {
        page = next;
        mount(list, loading(t('ui.leases.loading')));

        try {
            payload = await client.get(`${endpoints.leases}${query({ ...filters, page, per_page: size.get() })}`);
            paint();
        } catch (failure) {
            mount(list);
            showError(errors, failure, t('ui.leases.unable_load'));
        }
    }

    mount(host,
        screenHead({
            eyebrow: t('ui.leases.tenancy'), title: t('ui.leases.heading'), sub: t('ui.leases.page_description'),
            actions: [can('manage_operations') ? button(t('ui.leases.add_lease'), { kind: 'primary', iconName: 'plus', compact: false, onClick: () => openWizard(null) }) : null],
        }),
        errors,
        drafts,
        kpis,
        controls,
        list
    );

    paintControls();

    Promise.all([
        client.get(`${endpoints.parties}?role=tenant&per_page=100`).catch(() => null),
        client.get(`${endpoints.buildings}?per_page=100`).catch(() => null),
    ]).then(([tenantPayload, buildingPayload]) => {
        tenants = rows(tenantPayload);
        buildings = rows(buildingPayload);
        paintControls();
    });

    if (can('manage_operations')) {
        loadDrafts();
    }

    reload(1);

    return { node: host, reload: () => reload(page) };
}
