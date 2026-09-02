/*
 * Reports - the nine the browser offers, with the same criteria: a subject
 * where one is needed, a period or a reference date, the payment filters,
 * and the three exports. Results go stale the moment a criterion changes
 * and the exports refuse until the report is run again - otherwise
 * somebody would be handed a file that does not match the screen.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { table, pairs, loading, emptyState, button, section, stat } from '../ui/table.js';
import { money } from '../ui/money.js';
import { formatDate, formatNumber, percent, joinParts, domain, prettify } from '../ui/format.js';
import { openDocument } from '../data/exports.js';
import {
    screenHead, bannerHost, showError, rows, query, partyName, partyContact, filterField, selectControl, textControl,
    dateControl, pdfButton, fileButton, dash,
} from './common.js';

const REPORTS = [
    { id: 'managing-organisation', title: 'managing_organisation', output: 'managing_organisation_report', description: 'managing_organisation_description', summary: 'managing_organisation_summary', subject: null, dateMode: 'period', base: '/reports/managing-organisation', count: null },
    { id: 'owner', title: 'owner_report', output: 'owner_report', description: 'owner_report_description', summary: 'owner_report_summary', subject: 'owner', dateMode: 'period', base: '/reports/owners', count: (r) => (r.transactions ?? []).length },
    { id: 'building', title: 'building_report', output: 'building_report', description: 'building_report_description', summary: 'building_report_summary', subject: 'building', dateMode: 'period', base: '/reports/buildings', count: null },
    { id: 'unit', title: 'unit_report', output: 'unit_report', description: 'unit_report_description', summary: 'unit_report_summary', subject: 'unit', dateMode: 'period', base: '/reports/units', count: null },
    { id: 'tenant', title: 'tenant_statement', output: 'tenant_statement', description: 'tenant_statement_description', summary: 'tenant_statement_summary', subject: 'tenant', dateMode: 'period', base: '/reports/tenants', count: null },
    { id: 'payments', title: 'payments', output: 'payments_report', description: 'payments_report_description', summary: 'payments_report_summary', subject: null, dateMode: 'period', paymentFilters: true, base: '/reports/payments', count: (r) => (r.payments ?? []).length },
    { id: 'occupancy', title: 'occupancy_report', output: 'occupancy_report', description: 'occupancy_report_description', summary: 'occupancy_report_summary', subject: null, dateMode: 'asof', base: '/reports/occupancy', count: (r) => (r.buildings ?? []).length },
    { id: 'arrears', title: 'arrears_report', output: 'arrears_report', description: 'arrears_report_description', summary: 'arrears_report_summary', subject: null, dateMode: 'asof', base: '/reports/arrears', count: (r) => (r.tenants ?? []).length },
    { id: 'funds', title: 'funds_report', output: 'funds_report', description: 'funds_report_description', summary: 'funds_report_summary', subject: null, dateMode: 'none', base: '/reports/funds', count: (r) => (r.tenant_funds?.tenants ?? []).length + (r.owner_funds?.owners ?? []).length },
];

const SUBJECTS = {
    owner: { label: 'property_owner', placeholder: 'search_owner_placeholder', path: (term) => `/owner-accounts${query({ search: term, per_page: 10 })}`, id: (r) => r.party_id, name: (r) => partyName(r.party), meta: (r) => partyContact(r.party), secondary: (r) => money(r.balance ?? 0) },
    tenant: { label: 'tenant', placeholder: 'search_tenant_placeholder', path: (term) => `/parties${query({ role: 'tenant', search: term, per_page: 10 })}`, id: (r) => r.id, name: (r) => partyName(r), meta: (r) => partyContact(r), secondary: () => '' },
    building: { label: 'building', placeholder: 'search_building_placeholder', path: (term) => `/buildings${query({ search: term, per_page: 10 })}`, id: (r) => r.id, name: (r) => r.name ?? t('ui.reports.building_number', { number: r.id }), meta: (r) => joinParts([r.location, r.address]), secondary: () => '' },
    unit: { label: 'unit', placeholder: 'search_unit_placeholder', path: (term) => `/units${query({ search: term, per_page: 10 })}`, id: (r) => r.id, name: (r) => r.name ?? t('ui.reports.unit_number', { number: r.id }), meta: (r) => r.building?.name ?? '', secondary: () => '' },
};

const value = (group, v) => (v === null || v === undefined || v === '' ? '' : domain(`reports.${group}`, v));

function metric(label, v, options = {}) {
    return [label, v, options];
}

export function reportsScreen(client) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const criteria = el('div', { class: 'stack' });
    const output = el('div');
    let type = REPORTS[0];
    let subject = null;
    let from = '';
    let to = '';
    let asOf = '';
    let paymentFilters = { tenant_id: '', lease_id: '', building_id: '', unit_id: '', payment_method: '', cash_receiver: '', reference: '' };
    let report = null;
    let stale = false;
    let filterOptions = null;

    function endpointsFor() {
        let q;

        if (type.paymentFilters) {
            q = query({ from, to, ...paymentFilters });
        } else if (type.dateMode === 'asof') {
            q = query({ as_of: asOf });
        } else if (type.dateMode === 'period') {
            q = query({ from, to });
        } else {
            q = '';
        }

        const base = type.subject ? `${type.base}/${subject?.id}` : type.base;

        return { json: `${base}${q}`, pdf: `${base}/pdf${q}`, csv: `${base}/csv${q}`, xlsx: `${base}/xlsx${q}` };
    }

    function markStale() {
        if (report !== null && ! stale) {
            stale = true;
            paintOutput();
        }
    }

    function selectType(id) {
        type = REPORTS.find((r) => r.id === id) ?? REPORTS[0];
        subject = null;
        from = ''; to = ''; asOf = '';
        paymentFilters = { tenant_id: '', lease_id: '', building_id: '', unit_id: '', payment_method: '', cash_receiver: '', reference: '' };
        report = null;
        stale = false;
        paintCriteria();
        paintOutput();

        if (type.paymentFilters && filterOptions === null) {
            loadPaymentFilters();
        }
    }

    async function loadPaymentFilters() {
        try {
            const [tenants, leases, buildings, units] = await Promise.all([
                client.get('/parties?role=tenant&per_page=500'), client.get('/leases?per_page=500'), client.get('/buildings?per_page=500'), client.get('/units?per_page=500'),
            ]);

            filterOptions = { tenants: rows(tenants), leases: rows(leases), buildings: rows(buildings), units: rows(units) };
            paintCriteria();
        } catch (failure) {
            showError(errors, failure, t('ui.reports.unable_to_load_payment_filters'));
        }
    }

    /* ---------------------------------------------------- criteria */

    function subjectPicker() {
        const spec = SUBJECTS[type.subject];
        const results = el('ul', { class: 'picker-results', hidden: true });
        const input = el('input', { class: 'input', type: 'search', placeholder: t(`ui.reports.${spec.placeholder}`), autocapitalize: 'none', autocorrect: 'off', autocomplete: 'off' });
        let timer = null;
        let controller = null;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const term = input.value.trim();

            if (term.length < 2) {
                results.hidden = true;

                return;
            }

            timer = setTimeout(async () => {
                controller?.abort?.();
                controller = { abort() { this.stale = true; }, stale: false };
                const mine = controller;

                mount(results, el('li', { class: 'picker-empty', text: t('ui.reports.searching') }));
                results.hidden = false;

                try {
                    const found = rows(await client.get(spec.path(term)));

                    if (mine.stale) return;

                    mount(results, ...(found.length === 0
                        ? [el('li', { class: 'picker-empty', text: t('ui.reports.no_matching_records') })]
                        : found.map((row) => el('li', { class: 'picker-option', onmousedown: (e) => e.preventDefault(), onclick: () => {
                            subject = { id: spec.id(row), name: spec.name(row), meta: spec.meta(row) };
                            markStale();
                            paintCriteria();
                        } }, [
                            el('span', { class: 'picker-option-label', text: spec.name(row) }),
                            el('span', { class: 'picker-option-sub', text: joinParts([spec.meta(row), spec.secondary(row)]) }),
                        ]))));
                } catch {
                    if (! mine.stale) mount(results, el('li', { class: 'picker-empty', text: t('ui.reports.unable_to_search') }));
                }
            }, 300);
        });

        input.addEventListener('blur', () => setTimeout(() => { results.hidden = true; }, 150));

        return el('div', { class: 'field' }, [
            el('label', { class: 'label', text: t(`ui.reports.${spec.label}`) }),
            subject
                ? el('div', { class: 'inline' }, [
                    el('span', { class: 'grow' }, [el('span', { class: 'cell-strong', style: 'display:block', text: subject.name }), el('span', { class: 'muted-small', text: subject.meta })]),
                    button(t('ui.reports.change'), { onClick: () => { subject = null; markStale(); paintCriteria(); } }),
                ])
                : el('div', { class: 'picker' }, [input, results]),
        ]);
    }

    function paintCriteria() {
        const dates = type.dateMode === 'period'
            ? el('div', { class: 'stack' }, [
                el('h3', { class: 'sheet-heading-title', text: t('ui.reports.reporting_period') }),
                el('p', { class: 'muted-small', text: t('ui.reports.period_description') }),
                el('div', { class: 'filters' }, [
                    filterField(t('ui.reports.from'), dateControl(from, (v) => { from = v; markStale(); })),
                    filterField(t('ui.reports.to'), dateControl(to, (v) => { to = v; markStale(); })),
                ]),
            ])
            : type.dateMode === 'asof'
                ? el('div', { class: 'stack' }, [
                    el('h3', { class: 'sheet-heading-title', text: t('ui.reports.as_of_heading') }),
                    el('p', { class: 'muted-small', text: t('ui.reports.as_of_description') }),
                    filterField(t('ui.reports.as_of'), dateControl(asOf, (v) => { asOf = v; markStale(); })),
                ])
                : null;

        const payments = type.paymentFilters ? el('div', { class: 'stack' }, [
            el('h3', { class: 'sheet-heading-title', text: t('ui.reports.payment_filters') }),
            el('p', { class: 'muted-small', text: t('ui.reports.payment_filters_description') }),
            el('div', { class: 'filters' }, [
                filterField(t('ui.reports.tenant'), selectControl([{ value: '', label: t('ui.reports.all_tenants') }, ...(filterOptions?.tenants ?? []).map((p) => ({ value: p.id, label: partyName(p) }))], paymentFilters.tenant_id, (v) => { paymentFilters.tenant_id = v; markStale(); })),
                filterField(t('ui.reports.lease'), selectControl([{ value: '', label: t('ui.reports.all_leases') }, ...(filterOptions?.leases ?? []).map((l) => ({ value: l.id, label: joinParts([l.reference ?? `#${l.id}`, l.unit?.building?.name, l.unit?.name, partyName(l.tenant)]) }))], paymentFilters.lease_id, (v) => { paymentFilters.lease_id = v; markStale(); })),
                filterField(t('ui.reports.building'), selectControl([{ value: '', label: t('ui.reports.all_buildings') }, ...(filterOptions?.buildings ?? []).map((b) => ({ value: b.id, label: b.name }))], paymentFilters.building_id, (v) => { paymentFilters.building_id = v; markStale(); })),
                filterField(t('ui.reports.unit'), selectControl([{ value: '', label: t('ui.reports.all_units') }, ...(filterOptions?.units ?? []).map((u) => ({ value: u.id, label: joinParts([u.name, u.building?.name]) }))], paymentFilters.unit_id, (v) => { paymentFilters.unit_id = v; markStale(); })),
                filterField(t('ui.reports.payment_method_label'), selectControl([
                    { value: '', label: t('ui.reports.all_payment_methods') },
                    { value: 'cash', label: t('ui.reports.payment_method_cash') },
                    { value: 'bank_transfer', label: t('ui.reports.payment_method_bank') },
                    { value: 'momo', label: t('ui.reports.payment_method_mobile') },
                    { value: 'cheque', label: t('ui.reports.payment_method_cheque') },
                ], paymentFilters.payment_method, (v) => { paymentFilters.payment_method = v; markStale(); })),
                filterField(t('ui.reports.cash_receiver'), textControl({ value: paymentFilters.cash_receiver, placeholder: t('ui.reports.cash_receiver_placeholder'), maxlength: 255, onInput: (v) => { paymentFilters.cash_receiver = v; markStale(); } })),
                filterField(t('ui.reports.payment_reference'), textControl({ value: paymentFilters.reference, placeholder: t('ui.reports.payment_reference_placeholder'), maxlength: 255, onInput: (v) => { paymentFilters.reference = v; markStale(); } })),
            ]),
        ]) : null;

        mount(criteria,
            el('div', { class: 'card' }, [
                el('header', { class: 'card-head' }, [el('div', { class: 'card-words' }, [el('h2', { class: 'card-title', text: t('ui.reports.report_type') }), el('p', { class: 'card-sub', text: t('ui.reports.report_type_description') })])]),
                el('div', { class: 'card-body' }, [
                    el('div', { class: 'guide-grid' }, REPORTS.map((r) => el('button', { class: `guide-card${r.id === type.id ? ' is-selected record-card is-selected' : ''}`, type: 'button', onclick: () => selectType(r.id) }, [
                        el('span', { class: 'cell-strong', style: 'display:block', text: t(`ui.reports.${r.title}`) }),
                        el('span', { class: 'muted-small', text: t(`ui.reports.${r.summary}`) }),
                    ]))),
                ]),
            ]),
            el('div', { class: 'card' }, [
                el('div', { class: 'card-body' }, [
                    type.subject ? subjectPicker() : null,
                    payments,
                    dates,
                    el('div', { class: 'inline' }, [
                        button(t('ui.reports.run_report'), { kind: 'primary', iconName: 'bar-chart-square', compact: false, onClick: run }),
                        button(t('ui.reports.reset_filters'), { compact: false, onClick: () => selectType(type.id) }),
                    ]),
                ]),
            ])
        );
    }

    /* ------------------------------------------------------- output */

    async function run() {
        if (type.subject && subject === null) {
            showError(errors, null, t('ui.reports.select_subject_first'));

            return;
        }

        if (type.dateMode === 'period' && from && to && from > to) {
            showError(errors, null, t('ui.reports.invalid_period'));

            return;
        }

        mount(output, loading());

        try {
            report = await client.get(endpointsFor().json);
            stale = false;
            paintOutput();
        } catch (failure) {
            mount(output);
            showError(errors, failure, t('ui.reports.unable_to_generate'));
        }
    }

    function resultsBar() {
        const parts = [];

        if (report?.as_of) {
            parts.push(`${t('ui.reports.as_of')}: ${formatDate(report.as_of)}`);
        } else if (type.dateMode === 'period') {
            const p = report?.period ?? {};

            parts.push(! p.from && ! p.to ? t('ui.reports.reporting_period_all_history') : `${t('ui.reports.reporting_period')}: ${p.from ? formatDate(p.from) : t('ui.reports.beginning')} — ${p.to ? formatDate(p.to) : t('ui.reports.present')}`);
        }

        const count = type.count?.(report);

        if (count !== null && count !== undefined) {
            parts.push(t('ui.reports.result_rows', { count: formatNumber(count) }));
        }

        return parts.length === 0 ? null : el('p', { class: 'sheet-note is-info', text: parts.join(' · ') });
    }

    const grid = (metrics) => el('div', { class: 'kpis' }, metrics.map(([label, v, options = {}]) => stat(label, v, { tone: options.emphasis === 'danger' ? 'danger' : undefined, sub: options.meta })));
    const identity = (title, sub) => el('div', { class: 'dl-block' }, [el('span', { class: 'cell-strong', style: 'display:block', text: title }), sub ? el('span', { class: 'muted-small', text: sub }) : null]);
    const simpleTable = (headers, data, aligns = []) => (data.length === 0
        ? el('div', { class: 'table-empty', text: t('ui.reports.no_records_section') })
        : table(headers.map((label, i) => ({ label, align: aligns[i] === 'right' ? 'right' : undefined, cell: (row) => row[i] })), data));

    const renderers = {
        payments(r) {
            const summary = r.summary ?? {};
            const payments = r.payments ?? [];

            return [
                grid([metric(t('ui.reports.payment_count'), formatNumber(summary.payment_count ?? 0)), metric(t('ui.reports.total_received'), money(summary.total_received ?? 0))]),
                section(t('ui.reports.payments'), payments.length === 0 ? el('div', { class: 'table-empty', text: t('ui.reports.no_payments_found') }) : table([
                    { label: t('ui.reports.date'), cell: (p) => formatDate(p.payment_date) },
                    { label: t('ui.reports.payment_number'), cell: (p) => p.payment_number ?? `PAY-${p.id}` },
                    { label: t('ui.reports.tenant'), cell: (p) => dash(p.tenant?.name) },
                    { label: t('ui.reports.property'), cell: (p) => dash(joinParts([p.building?.name, p.unit?.name])) },
                    { label: t('ui.reports.payment_method_label'), cell: (p) => value('payment_method', p.payment_method) },
                    { label: t('ui.reports.cash_receiver'), cell: (p) => dash(p.cash_receiver_name) },
                    { label: t('ui.reports.reference'), cell: (p) => dash(p.reference) },
                    { label: t('ui.reports.amount'), align: 'right', cell: (p) => money(p.amount ?? 0) },
                    { label: t('ui.reports.receipt'), cell: (p) => (p.receipt_endpoint ? pdfButton(client, String(p.receipt_endpoint).replace(/^\/api(\/v\d+)?/, ''), t('ui.reports.receipt'), { onFail: (f) => showError(errors, f, t('ui.reports.unable_to_open')) }) : '') },
                ], payments)),
            ];
        },
        'managing-organisation'(r) {
            const portfolio = r.portfolio ?? {}; const billing = r.billing ?? {}; const owner = r.owner_accounting ?? {}; const funds = r.tenant_funds ?? {};

            return [
                grid([metric(t('ui.reports.buildings'), formatNumber(portfolio.buildings)), metric(t('ui.reports.units'), formatNumber(portfolio.units)), metric(t('ui.reports.owner_accounts'), formatNumber(portfolio.owner_accounts)), metric(t('ui.reports.cash_received'), money(billing.cash_received ?? 0))]),
                section(t('ui.reports.billing'), pairs([
                    [t('ui.reports.total_invoiced'), money(billing.invoiced ?? 0)], [t('ui.reports.rent_invoiced'), money(billing.rent_invoiced ?? 0)], [t('ui.reports.security_deposit_debt_invoiced'), money(billing.security_deposit_debt_invoiced ?? 0)], [t('ui.reports.settled'), money(billing.settled ?? 0)],
                    [t('ui.reports.rent_outstanding'), money(billing.rent_outstanding ?? 0)], [t('ui.reports.security_deposit_debt_outstanding'), money(billing.security_deposit_debt_outstanding ?? 0)], [t('ui.reports.total_outstanding'), money(billing.total_outstanding ?? 0)], [t('ui.reports.cash_received'), money(billing.cash_received ?? 0)],
                ])),
                section(t('ui.reports.owner_accounting'), pairs([
                    [t('ui.reports.rent_entitlement'), money(owner.rent_entitlement ?? 0)], [t('ui.reports.management_fees'), money(owner.management_fees ?? 0)], [t('ui.reports.agent_commissions'), money(owner.agent_commissions ?? 0)],
                    [t('ui.reports.owner_expenses'), money(owner.owner_expenses ?? 0)], [t('ui.reports.owner_payouts'), money(owner.owner_payouts ?? 0)], [t('ui.reports.owner_funds_held'), money(owner.owner_funds_held ?? 0)],
                ])),
                section(t('ui.reports.tenant_funds'), pairs([[t('ui.reports.rent_reserve'), money(funds.rent_reserve ?? 0)], [t('ui.reports.consumable_advance'), money(funds.consumable_advance ?? 0)], [t('ui.reports.security_deposit'), money(funds.security_deposit ?? 0)]])),
            ];
        },
        owner(r) {
            const o = r.owner ?? {}; const s = r.summary ?? {}; const tx = r.transactions ?? [];

            return [
                identity(o.name ?? t('ui.reports.property_owner'), joinParts([o.phone, o.email])),
                grid([metric(t('ui.reports.opening_balance'), money(s.opening_balance ?? 0)), metric(t('ui.reports.credits'), money(s.credits ?? 0)), metric(t('ui.reports.debits'), money(s.debits ?? 0)), metric(t('ui.reports.closing_balance'), money(s.closing_balance ?? 0))]),
                section(t('ui.reports.financial_summary'), pairs([
                    [t('ui.reports.rent_entitlement'), money(s.rent_entitlement ?? 0)], [t('ui.reports.owner_deposits'), money(s.owner_deposits ?? 0)], [t('ui.reports.management_fees'), money(s.management_fees ?? 0)], [t('ui.reports.agent_commissions'), money(s.agent_commissions ?? 0)],
                    [t('ui.reports.property_expenses'), money(s.expenses ?? 0)], [t('ui.reports.payouts'), money(s.payouts ?? 0)], [t('ui.reports.adjustments_credit'), money(s.adjustments_credit ?? 0)], [t('ui.reports.adjustments_debit'), money(s.adjustments_debit ?? 0)],
                ])),
                section(t('ui.reports.transactions'), simpleTable(
                    [t('ui.reports.date'), t('ui.reports.direction'), t('ui.reports.category'), t('ui.reports.amount'), t('ui.reports.building'), t('ui.reports.unit'), t('ui.reports.invoice'), t('ui.reports.reference')],
                    tx.map((row) => [formatDate(row.date), value('direction', row.direction), value('category', row.category), money(row.amount ?? 0), row.building ?? '', row.unit ?? '', row.invoice ?? '', row.reference ?? '']),
                    ['left', 'left', 'left', 'right'])),
            ];
        },
        building(r) {
            const b = r.building ?? {}; const s = r.summary ?? {};

            return [
                identity(b.name ?? t('ui.reports.building'), joinParts([b.location, b.address])),
                grid([metric(t('ui.reports.units'), formatNumber(s.units)), metric(t('ui.reports.leases'), formatNumber(s.leases)), metric(t('ui.reports.rent_outstanding'), money(s.rent_outstanding ?? 0)), metric(t('ui.reports.security_deposit_debt'), money(s.security_deposit_debt_outstanding ?? 0))]),
                section(t('ui.reports.financial_summary'), pairs([
                    [t('ui.reports.total_invoiced'), money(s.invoiced ?? 0)], [t('ui.reports.rent_invoiced'), money(s.rent_invoiced ?? 0)], [t('ui.reports.security_deposit_debt_invoiced'), money(s.security_deposit_debt_invoiced ?? 0)], [t('ui.reports.invoice_settled'), money(s.invoice_settled ?? 0)],
                    [t('ui.reports.rent_outstanding'), money(s.rent_outstanding ?? 0)], [t('ui.reports.security_deposit_debt_outstanding'), money(s.security_deposit_debt_outstanding ?? 0)], [t('ui.reports.total_outstanding'), money(s.total_outstanding ?? 0)], [t('ui.reports.cash_received'), money(s.cash_received ?? 0)],
                    [t('ui.reports.property_expenses'), money(s.property_expenses ?? 0)], [t('ui.reports.owner_rent_entitlement'), money(s.owner_rent_entitlement ?? 0)], [t('ui.reports.management_fees'), money(s.management_fees ?? 0)], [t('ui.reports.agent_commissions'), money(s.agent_commissions ?? 0)],
                ])),
                section(t('ui.reports.ownership'), simpleTable([t('ui.reports.owner'), t('ui.reports.ownership')], (r.ownership ?? []).map((row) => [row.owner ?? '', `${row.percentage ?? 0}%`]), ['left', 'right'])),
                section(t('ui.reports.property_expenses'), simpleTable([t('ui.reports.date'), t('ui.reports.description'), t('ui.reports.amount'), t('ui.reports.unit'), t('ui.reports.reference')], (r.expenses ?? []).map((row) => [formatDate(row.date), row.description ?? '', money(row.amount ?? 0), row.unit_id ? t('ui.reports.unit_number', { number: row.unit_id }) : '', row.reference ?? '']), ['left', 'left', 'right'])),
            ];
        },
        unit(r) {
            const u = r.unit ?? {}; const s = r.summary ?? {};

            return [
                identity(u.name ?? t('ui.reports.unit'), u.building?.name ?? ''),
                grid([metric(t('ui.reports.leases'), formatNumber(s.leases)), metric(t('ui.reports.rent_outstanding'), money(s.rent_outstanding ?? 0)), metric(t('ui.reports.security_deposit_debt'), money(s.security_deposit_debt_outstanding ?? 0)), metric(t('ui.reports.total_outstanding'), money(s.total_outstanding ?? 0))]),
                section(t('ui.reports.financial_summary'), pairs([
                    [t('ui.reports.total_invoiced'), money(s.invoiced ?? 0)], [t('ui.reports.rent_invoiced'), money(s.rent_invoiced ?? 0)], [t('ui.reports.security_deposit_debt_invoiced'), money(s.security_deposit_debt_invoiced ?? 0)], [t('ui.reports.settled'), money(s.settled ?? 0)],
                    [t('ui.reports.rent_outstanding'), money(s.rent_outstanding ?? 0)], [t('ui.reports.security_deposit_debt_outstanding'), money(s.security_deposit_debt_outstanding ?? 0)], [t('ui.reports.total_outstanding'), money(s.total_outstanding ?? 0)], [t('ui.reports.cash_received'), money(s.cash_received ?? 0)], [t('ui.reports.expenses'), money(s.expenses ?? 0)],
                ])),
                section(t('ui.reports.lease_history'), simpleTable([t('ui.reports.tenant'), t('ui.reports.start'), t('ui.reports.end'), t('ui.reports.status'), t('ui.reports.rent'), t('ui.reports.frequency')], (r.leases ?? []).map((row) => [row.tenant ?? '', formatDate(row.start_date), row.end_date ? formatDate(row.end_date) : '', value('status', row.status), money(row.rent_amount ?? 0), value('frequency', row.payment_frequency)]), ['left', 'left', 'left', 'left', 'right'])),
                section(t('ui.reports.invoices'), simpleTable([t('ui.reports.invoice'), t('ui.reports.type'), t('ui.reports.issue_date'), t('ui.reports.due_date'), t('ui.reports.amount'), t('ui.reports.paid'), t('ui.reports.outstanding'), t('ui.reports.status')], (r.invoices ?? []).map((row) => [row.invoice_number ?? '', value('invoice_type', row.type), formatDate(row.issue_date), formatDate(row.due_date), money(row.total_amount ?? 0), money(row.paid_amount ?? 0), money(row.outstanding_amount ?? 0), value('status', row.status)]), ['left', 'left', 'left', 'left', 'right', 'right', 'right'])),
            ];
        },
        tenant(r) {
            const tn = r.tenant ?? {}; const s = r.summary ?? {};

            return [
                identity(tn.name ?? t('ui.reports.tenant'), joinParts([tn.phone, tn.email])),
                grid([metric(t('ui.reports.rent_outstanding'), money(s.rent_outstanding ?? 0)), metric(t('ui.reports.security_deposit_debt'), money(s.security_deposit_debt_outstanding ?? 0)), metric(t('ui.reports.total_outstanding'), money(s.total_outstanding ?? 0)), metric(t('ui.reports.cash_received'), money(s.cash_received ?? 0))]),
                section(t('ui.reports.receivables'), pairs([[t('ui.reports.total_invoiced'), money(s.invoiced ?? 0)], [t('ui.reports.settled'), money(s.settled ?? 0)], [t('ui.reports.rent_outstanding'), money(s.rent_outstanding ?? 0)], [t('ui.reports.security_deposit_debt_outstanding'), money(s.security_deposit_debt_outstanding ?? 0)], [t('ui.reports.total_outstanding'), money(s.total_outstanding ?? 0)]])),
                section(t('ui.reports.held_funds'), pairs([[t('ui.reports.rent_reserve'), money(s.rent_reserve_balance ?? 0)], [t('ui.reports.consumable_advance'), money(s.consumable_advance_balance ?? 0)], [t('ui.reports.security_deposit'), money(s.security_deposit_balance ?? 0)]])),
                section(t('ui.reports.leases'), simpleTable([t('ui.reports.building'), t('ui.reports.unit'), t('ui.reports.status'), t('ui.reports.start'), t('ui.reports.end'), t('ui.reports.rent')], (r.leases ?? []).map((row) => [row.building ?? '', row.unit ?? '', value('status', row.status), formatDate(row.start_date), row.end_date ? formatDate(row.end_date) : '', money(row.rent_amount ?? 0)]), ['left', 'left', 'left', 'left', 'left', 'right'])),
                section(t('ui.reports.invoices'), simpleTable([t('ui.reports.invoice'), t('ui.reports.type'), t('ui.reports.date'), t('ui.reports.due_date'), t('ui.reports.amount'), t('ui.reports.paid'), t('ui.reports.outstanding'), t('ui.reports.status')], (r.invoices ?? []).map((row) => [row.invoice_number ?? '', value('invoice_type', row.type), formatDate(row.date), formatDate(row.due_date), money(row.amount ?? 0), money(row.paid ?? 0), money(row.outstanding ?? 0), value('status', row.status)]), ['left', 'left', 'left', 'left', 'right', 'right', 'right'])),
                section(t('ui.reports.payments'), simpleTable([t('ui.reports.date'), t('ui.reports.amount'), t('ui.reports.method'), t('ui.reports.reference'), t('ui.reports.allocated'), t('ui.reports.unallocated')], (r.payments ?? []).map((row) => [formatDate(row.date), money(row.amount ?? 0), value('payment_method', row.method), row.reference ?? '', money(row.allocated ?? 0), money(row.unallocated ?? 0)]), ['left', 'right', 'left', 'left', 'right', 'right'])),
            ];
        },
        occupancy(r) {
            const totals = r.totals ?? {}; const c = r.classification ?? {};
            const card = (title, d) => el('div', { class: 'card' }, [el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: title })]), el('div', { class: 'card-body' }, [pairs([[t('ui.reports.units'), formatNumber(d.units ?? 0)], [t('ui.reports.occupied'), formatNumber(d.occupied ?? 0)], [t('ui.reports.vacant'), formatNumber(d.vacant ?? 0)], [t('ui.reports.occupancy_rate'), percent(d.occupancy_rate)]], { columns: 2 })])]);

            return [
                grid([metric(t('ui.reports.units'), formatNumber(totals.units ?? 0)), metric(t('ui.reports.occupied'), formatNumber(totals.occupied ?? 0)), metric(t('ui.reports.vacant'), formatNumber(totals.vacant ?? 0)), metric(t('ui.reports.occupancy_rate'), percent(totals.occupancy_rate))]),
                section(t('ui.reports.occupancy_by_classification'), el('div', { class: 'grid-2' }, [card(t('ui.reports.commercial'), c.commercial ?? {}), card(t('ui.reports.residential'), c.residential ?? {})])),
                section(t('ui.reports.buildings'), simpleTable([t('ui.reports.building'), t('ui.reports.units'), t('ui.reports.occupied'), t('ui.reports.vacant'), t('ui.reports.occupancy_rate'), t('ui.reports.commercial_units')], (r.buildings ?? []).map((row) => [row.name ?? t('ui.reports.building_number', { number: row.id }), formatNumber(row.units ?? 0), formatNumber(row.occupied ?? 0), formatNumber(row.vacant ?? 0), percent(row.occupancy_rate), formatNumber(row.commercial_units ?? 0)]), ['left', 'right', 'right', 'right', 'right', 'right'])),
            ];
        },
        arrears(r) {
            const totals = r.totals ?? {};

            return [
                grid([metric(t('ui.reports.aging_current'), money(totals.current ?? 0)), metric(t('ui.reports.aging_31_60'), money(totals.days_31_60 ?? 0)), metric(t('ui.reports.aging_61_90'), money(totals.days_61_90 ?? 0)), metric(t('ui.reports.aging_over_90'), money(totals.over_90 ?? 0), { emphasis: 'danger' }), metric(t('ui.reports.total_arrears'), money(totals.total ?? 0)), metric(t('ui.reports.open_invoices'), formatNumber(totals.invoice_count ?? 0))]),
                section(t('ui.reports.tenants_in_arrears'), simpleTable([t('ui.reports.tenant'), t('ui.reports.lease'), t('ui.reports.building'), t('ui.reports.unit'), t('ui.reports.open_invoices'), t('ui.reports.aging_current'), t('ui.reports.aging_31_60'), t('ui.reports.aging_61_90'), t('ui.reports.aging_over_90'), t('ui.reports.total_arrears')], (r.tenants ?? []).map((row) => [row.tenant?.name ?? t('ui.reports.unnamed_party'), row.lease?.id ? `#${row.lease.id}` : '', row.building?.name ?? '', row.unit?.name ?? '', formatNumber(row.invoice_count ?? 0), money(row.current ?? 0), money(row.days_31_60 ?? 0), money(row.days_61_90 ?? 0), money(row.over_90 ?? 0), money(row.total ?? 0)]), ['left', 'left', 'left', 'left', 'right', 'right', 'right', 'right', 'right', 'right'])),
            ];
        },
        funds(r) {
            const tf = r.tenant_funds ?? {}; const ts = tf.summary ?? {}; const of = r.owner_funds ?? {}; const os = of.summary ?? {};
            const count = (n) => t('ui.reports.account_count', { count: formatNumber(n ?? 0) });

            return [
                section(t('ui.reports.tenant_funds'), [
                    grid([metric(t('ui.reports.rent_reserve'), money(ts.rent_reserve?.total_held ?? 0), { meta: count(ts.rent_reserve?.account_count) }), metric(t('ui.reports.consumable_advance'), money(ts.consumable_advance?.total_held ?? 0), { meta: count(ts.consumable_advance?.account_count) }), metric(t('ui.reports.security_deposit'), money(ts.security_deposit?.total_held ?? 0), { meta: count(ts.security_deposit?.account_count) }), metric(t('ui.reports.total_held'), money(ts.total_held ?? 0))]),
                    simpleTable([t('ui.reports.tenant'), t('ui.reports.lease'), t('ui.reports.building'), t('ui.reports.unit'), t('ui.reports.rent_reserve'), t('ui.reports.consumable_advance'), t('ui.reports.security_deposit'), t('ui.reports.total_held')], (tf.tenants ?? []).map((row) => [row.tenant?.name ?? t('ui.reports.unnamed_party'), row.lease?.id ? `#${row.lease.id}` : '', row.building?.name ?? '', row.unit?.name ?? '', money(row.rent_reserve ?? 0), money(row.consumable_advance ?? 0), money(row.security_deposit ?? 0), money(row.total ?? 0)]), ['left', 'left', 'left', 'left', 'right', 'right', 'right', 'right']),
                ]),
                section(t('ui.reports.owner_funds'), [
                    grid([metric(t('ui.reports.owner_accounts'), formatNumber(os.account_count ?? 0)), metric(t('ui.reports.total_held'), money(os.total_held ?? 0))]),
                    simpleTable([t('ui.reports.owner'), t('ui.reports.balance')], (of.owners ?? []).map((row) => [row.owner?.name ?? t('ui.reports.unnamed_party'), money(row.balance ?? 0)]), ['left', 'right']),
                ]),
            ];
        },
    };

    function paintOutput() {
        const links = endpointsFor();
        const exportsBar = can('export_reports') && report !== null ? el('div', { class: 'inline' }, [
            pdfButton(client, links.pdf, t('ui.reports.pdf'), { onFail: (f) => showError(errors, f, t('ui.reports.unable_to_open')) }),
            fileButton(client, links.xlsx, t('ui.reports.xlsx'), `${type.id}-report.xlsx`, { onFail: (f) => showError(errors, f, t('ui.reports.unable_to_open')) }),
            fileButton(client, links.csv, t('ui.reports.csv'), `${type.id}-report.csv`, { onFail: (f) => showError(errors, f, t('ui.reports.unable_to_open')) }),
        ]) : null;

        if (exportsBar && stale) {
            for (const b of exportsBar.querySelectorAll('button')) { b.disabled = true; }
        }

        mount(output,
            el('div', { class: 'card' }, [
                el('header', { class: 'card-head' }, [
                    el('div', { class: 'card-words' }, [
                        el('h2', { class: 'card-title', text: t(`ui.reports.${type.output}`) }),
                        el('p', { class: 'card-sub', text: t(`ui.reports.${type.description}`) }),
                    ]),
                    exportsBar,
                ]),
                el('div', { class: `card-body${stale ? ' is-stale' : ''}`, style: stale ? 'opacity:0.5' : undefined }, [
                    stale ? el('p', { class: 'sheet-note is-warning', text: t('ui.reports.stale_results') }) : null,
                    report === null
                        ? el('p', { class: 'muted-small', text: t('ui.reports.initial_prompt') })
                        : el('div', { class: 'stack' }, [resultsBar(), ...(renderers[type.id] ?? (() => []))(report)]),
                ]),
            ])
        );
    }

    mount(host,
        screenHead({ eyebrow: t('ui.reports.finance'), title: t('ui.reports.heading'), sub: t('ui.reports.page_description') }),
        errors,
        criteria,
        output
    );

    selectType(REPORTS[0].id);

    return { node: host, reload: () => (report ? run() : null) };
}
