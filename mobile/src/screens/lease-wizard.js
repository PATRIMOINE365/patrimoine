/*
 * The lease assistant - the browser application's eight-page wizard, the
 * one way a lease is created. One POST /lease-wizard at the end creates
 * the property, its owners, the tenant, the agent and the lease in a single
 * transaction, or nothing at all.
 *
 * Nothing is validated on the way through, as on the web: the server is
 * authoritative, and a 422 jumps back to the page that owns the rejected
 * field. Save as draft keeps the answers on the server so another device
 * can continue them; the assistant always reopens on page one.
 */

import { el, mount, clear } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { openSheet, informSheet, today } from '../ui/sheet.js';
import { money, currencyCode } from '../ui/money.js';
import { formatDate, endDateAfterMonths, formatNumber } from '../ui/format.js';
import * as store from '../data/store.js';
import { rows, partyName, partyContact, frequencyLabel } from './common.js';
import { session } from '../auth/session.js';

const TOTAL = 8;

function partyBlock(prefix) {
    return [
        { name: `${prefix}_type`, type: 'select', label: t('ui.wizard.party_type'), value: 'person', options: [{ value: 'person', label: t('ui.wizard.person') }, { value: 'organisation', label: t('ui.wizard.organisation') }] },
        { name: `${prefix}_given_names`, type: 'text', label: t('ui.wizard.given_names'), maxlength: 255, when: (v) => v[`${prefix}_type`] === 'person' },
        { name: `${prefix}_surname`, type: 'text', label: t('ui.wizard.surname'), maxlength: 255, required: true, when: (v) => v[`${prefix}_type`] === 'person' },
        { name: `${prefix}_legal_name`, type: 'text', label: t('ui.wizard.legal_name'), maxlength: 255, required: true, when: (v) => v[`${prefix}_type`] !== 'person' },
        { name: `${prefix}_contact_name`, type: 'text', label: t('ui.wizard.contact_name'), maxlength: 255, required: true, when: (v) => v[`${prefix}_type`] !== 'person' },
        { name: `${prefix}_phone`, type: 'phone', label: t('ui.wizard.phone'), required: true },
        { name: `${prefix}_email`, type: 'email', label: t('ui.wizard.email'), maxlength: 255, required: true },
        { name: `${prefix}_email_policy`, type: 'select', label: t('ui.wizard.email_policy'), value: 'inherit', hint: t('ui.wizard.email_policy_help'), options: [
            { value: 'inherit', label: t('ui.wizard.email_policy_inherit') },
            { value: 'always', label: t('ui.wizard.email_policy_always') },
            { value: 'never', label: t('ui.wizard.email_policy_never') },
        ] },
    ];
}

function partyAttributes(values, prefix) {
    const person = values[`${prefix}_type`] === 'person';
    const phone = values[`${prefix}_phone`] ?? {};

    return {
        type: person ? 'person' : 'organisation',
        email: values[`${prefix}_email`] || null,
        email_policy: values[`${prefix}_email_policy`] ?? 'inherit',
        ...(person ? {
            given_names: values[`${prefix}_given_names`] || null,
            surname: values[`${prefix}_surname`] || null,
            phone: phone.number || null,
            phone_country: phone.country ?? null,
        } : {
            legal_name: values[`${prefix}_legal_name`] || null,
            contact_person_name: values[`${prefix}_contact_name`] || null,
            contact_person_phone: phone.number || null,
            contact_person_phone_country: phone.country ?? null,
            contact_person_email: values[`${prefix}_email`] || null,
        }),
    };
}

/* Which page owns a rejected field, so a 422 lands where it can be fixed. */
function stepFor(key) {
    if (/^(building|unit|owners|tenant)/.test(key)) return 2;
    if (/^agent/.test(key)) return 7;
    if (/^lease\.(start_date|end_date|termination_notice_date)/.test(key)) return 3;
    if (/^lease\.(rent_amount|payment_frequency|due_day|vat_rate|proration_amount|security_deposit_amount|security_deposit_received)/.test(key)) return 4;
    if (/^lease\.(advance_payment_amount|rent_reserve_amount|advance_received)/.test(key)) return 5;
    if (/^lease\.(rent_increment|next_rent_increment_date)/.test(key)) return 6;
    if (/^lease\.(management_fee|agent_commission_amount|notes)/.test(key)) return 7;

    return 8;
}

/**
 * @returns {Promise<boolean>} whether a lease was created
 */
export async function leaseWizard(client, { draftId = null } = {}) {
    /* Reference data, asked for up front so the pickers are never empty. */
    const [buildingsPayload, ownersPayload, tenantsPayload, agentsPayload, presentation] = await Promise.all([
        client.get(`${endpoints.buildings}?per_page=100`).catch(() => null),
        client.get(`${endpoints.parties}?role=owner&per_page=100`).catch(() => null),
        client.get(`${endpoints.parties}?role=tenant&per_page=100`).catch(() => null),
        client.get(`${endpoints.parties}?role=agent&per_page=100`).catch(() => null),
        client.get('/presentation-config').catch(() => null),
    ]);

    const buildings = rows(buildingsPayload);
    const owners = rows(ownersPayload);
    const tenants = rows(tenantsPayload);
    const agents = rows(agentsPayload);
    const me = store.read('me').data ?? {};
    const userName = (me.user ?? me).name ?? '';
    const currency = currencyCode() ?? '';

    const buildingOption = (b) => ({ value: String(b.id), label: b.name, sub: b.location || b.address || '', keywords: `${b.location ?? ''} ${b.address ?? ''}` });
    const partyOption = (p) => ({ value: String(p.id), label: partyName(p), sub: partyContact(p), keywords: partyContact(p) });

    const searchBuildings = async (term) => rows(await client.get(`${endpoints.buildings}?search=${encodeURIComponent(term)}&per_page=15`)).map(buildingOption);
    const searchParties = (role) => async (term) => rows(await client.get(`${endpoints.parties}?role=${role}&search=${encodeURIComponent(term)}&per_page=15`)).map(partyOption);

    /* The answers, kept across pages. */
    let answers = {
        building_mode: buildings.length === 0 ? 'new' : 'existing',
        unit_mode: 'existing',
        tenant_mode: tenants.length === 0 ? 'new' : 'existing',
        agent_mode: 'none',
        owner_rows: [{ mode: owners.length === 0 ? 'new' : 'existing', share: '100' }],
        duration: '12',
        frequency: 'monthly',
        fee_vat: String(presentation?.default_vat_rate ?? ''),
        security_deposit: '0',
        deposit_method: 'bank_transfer',
        advance_amount: '0',
        rent_reserve: '0',
        advance_received: true,
        advance_method: 'cash',
        increment_type: 'none',
        agent_commission: '0',
        fee_type: 'percentage',
        fee_value: '',
        start_date: today(),
    };

    if (draftId !== null) {
        try {
            const draft = await client.get(`/lease-wizard/drafts/${draftId}`);

            answers = { ...answers, ...(draft?.payload?.answers ?? draft?.payload?.fields ?? {}) };
        } catch (failure) {
            await informSheet({ title: t('ui.wizard.heading'), body: t('ui.wizard.draft_missing'), tone: 'warning' });

            return false;
        }
    }

    /* Units of the chosen building, vacant only. */
    function vacantUnits(buildingId) {
        const building = buildings.find((b) => String(b.id) === String(buildingId));

        return (building?.units ?? []).filter((u) => u.is_occupied !== true);
    }

    function chosenBuilding() {
        return buildings.find((b) => String(b.id) === String(answers.building_id));
    }

    function ownershipAsked() {
        if (answers.building_mode === 'new') {
            return true;
        }

        const building = chosenBuilding();

        return building === undefined || (building.ownerships ?? []).length === 0;
    }

    /* --------------------------------------------------------- pages */

    function page1() {
        return [
            { name: 'glossary', type: 'note', label: '' },
            { name: 'note1', type: 'note', label: t('ui.wizard.step1_note') },
        ];
    }

    function page2() {
        const vacant = vacantUnits(answers.building_id);
        const ownerRows = answers.owner_rows ?? [];

        return [
            { name: 'note2', type: 'note', label: t('ui.wizard.step2_note') },
            { name: 'h_property', type: 'heading', label: t('ui.wizard.property') },
            { name: 'building_mode', type: 'select', label: t('ui.wizard.property'), value: answers.building_mode, options: [
                { value: 'existing', label: t('ui.wizard.use_existing_property'), disabled: buildings.length === 0 },
                { value: 'new', label: t('ui.wizard.add_new_property') },
            ] },
            { name: 'building_id', type: 'picker', label: t('ui.wizard.choose_property'), placeholder: t('ui.wizard.search_property'), empty: t('ui.wizard.no_property_found'), value: answers.building_id ?? '', options: buildings.map(buildingOption), search: searchBuildings, when: (v) => v.building_mode === 'existing' },
            { name: 'building_name', type: 'text', label: t('ui.wizard.property_name'), value: answers.building_name ?? '', maxlength: 255, required: true, when: (v) => v.building_mode === 'new' },
            { name: 'building_address', type: 'text', label: t('ui.wizard.property_address'), value: answers.building_address ?? '', when: (v) => v.building_mode === 'new' },

            { name: 'h_unit', type: 'heading', label: t('ui.wizard.unit') },
            { name: 'unit_mode', type: 'select', label: t('ui.wizard.unit'), value: answers.building_mode === 'new' || vacant.length === 0 ? 'new' : answers.unit_mode, options: [
                { value: 'existing', label: t('ui.wizard.use_existing_unit'), disabled: answers.building_mode === 'new' || vacant.length === 0 },
                { value: 'new', label: t('ui.wizard.add_new_unit') },
            ] },
            { name: 'unit_id', type: 'picker', label: t('ui.wizard.choose_unit'), placeholder: t('ui.wizard.search_unit'), empty: t('ui.wizard.no_unit_found'), hint: t('ui.wizard.vacant_units_only'), value: answers.unit_id ?? '', options: vacant.map((u) => ({ value: String(u.id), label: u.name, sub: u.description ?? '' })), when: (v) => v.unit_mode === 'existing' },
            { name: 'unit_name', type: 'text', label: t('ui.wizard.unit_name'), value: answers.unit_name ?? '', maxlength: 255, required: true, when: (v) => v.unit_mode === 'new' },
            { name: 'unit_commercial', type: 'toggle', label: t('ui.wizard.unit_commercial'), value: answers.unit_commercial === true, when: (v) => v.unit_mode === 'new' },

            ...(ownershipAsked() ? [
                { name: 'h_owners', type: 'heading', label: t('ui.wizard.ownership'), hint: t('ui.wizard.ownership_note') },
                {
                    name: 'owner_rows', type: 'lines', min: 1, addLabel: t('ui.wizard.add_owner'), removeLabel: t('ui.wizard.remove'),
                    value: ownerRows,
                    columns: [
                        { name: 'mode', type: 'select', label: t('ui.wizard.owner'), value: owners.length === 0 ? 'new' : 'existing', options: [
                            { value: 'existing', label: t('ui.wizard.use_existing_party'), disabled: owners.length === 0 },
                            { value: 'new', label: t('ui.wizard.add_new_party') },
                        ] },
                        { name: 'share', type: 'number', label: t('ui.wizard.share'), value: '100', min: 0.01, max: 100, step: 0.01, suffix: '%' },
                        { name: 'party_id', type: 'picker', label: t('ui.wizard.choose_owner'), placeholder: t('ui.wizard.search_owner'), options: owners.map(partyOption), search: searchParties('owner'), when: (r) => r.mode === 'existing' },
                        { name: 'type', type: 'select', label: t('ui.wizard.party_type'), value: 'person', options: [{ value: 'person', label: t('ui.wizard.person') }, { value: 'organisation', label: t('ui.wizard.organisation') }], when: (r) => r.mode === 'new' },
                        { name: 'given_names', type: 'text', label: t('ui.wizard.given_names'), maxlength: 255, when: (r) => r.mode === 'new' && r.type === 'person' },
                        { name: 'surname', type: 'text', label: t('ui.wizard.surname'), maxlength: 255, when: (r) => r.mode === 'new' && r.type === 'person' },
                        { name: 'legal_name', type: 'text', label: t('ui.wizard.legal_name'), maxlength: 255, when: (r) => r.mode === 'new' && r.type !== 'person' },
                        { name: 'contact_name', type: 'text', label: t('ui.wizard.contact_name'), maxlength: 255, when: (r) => r.mode === 'new' && r.type !== 'person' },
                        { name: 'phone', type: 'phone', label: t('ui.wizard.phone'), when: (r) => r.mode === 'new' },
                        { name: 'email', type: 'email', label: t('ui.wizard.email'), maxlength: 255, when: (r) => r.mode === 'new' },
                        { name: 'email_policy', type: 'select', label: t('ui.wizard.email_policy'), value: 'inherit', when: (r) => r.mode === 'new', options: [
                            { value: 'inherit', label: t('ui.wizard.email_policy_inherit') },
                            { value: 'always', label: t('ui.wizard.email_policy_always') },
                            { value: 'never', label: t('ui.wizard.email_policy_never') },
                        ] },
                    ],
                    total: (values) => t('ui.wizard.owner_total', { total: formatNumber(values.reduce((sum, row) => sum + (Number(row.share) || 0), 0)) }),
                },
            ] : []),

            { name: 'h_tenant', type: 'heading', label: t('ui.wizard.tenant') },
            { name: 'tenant_mode', type: 'select', label: t('ui.wizard.tenant'), value: answers.tenant_mode, options: [
                { value: 'existing', label: t('ui.wizard.use_existing_party'), disabled: tenants.length === 0 },
                { value: 'new', label: t('ui.wizard.add_new_party') },
            ] },
            { name: 'tenant_id', type: 'picker', label: t('ui.wizard.choose_tenant'), placeholder: t('ui.wizard.search_tenant'), value: answers.tenant_id ?? '', options: tenants.map(partyOption), search: searchParties('tenant'), when: (v) => v.tenant_mode === 'existing' },
            ...partyBlock('tenant').map((f) => ({ ...f, value: answers[f.name] ?? f.value, when: (v) => v.tenant_mode === 'new' && (f.when ? f.when(v) : true) })),
        ];
    }

    function page3() {
        return [
            { name: 'start_date', type: 'date', label: t('ui.wizard.start_date'), value: answers.start_date ?? today(), required: true, hint: t('ui.wizard.start_date_help') },
            { name: 'duration', type: 'select', label: t('ui.wizard.duration'), value: answers.duration ?? '12', options: [
                { value: '12', label: t('ui.wizard.duration_12') === 'ui.wizard.duration_12' ? '12 months' : t('ui.wizard.duration_12') },
                { value: '6', label: t('ui.wizard.duration_6') === 'ui.wizard.duration_6' ? '6 months' : t('ui.wizard.duration_6') },
                { value: '24', label: t('ui.wizard.duration_24') === 'ui.wizard.duration_24' ? '24 months' : t('ui.wizard.duration_24') },
                { value: 'custom', label: t('ui.wizard.duration_custom') },
                { value: 'open', label: t('ui.wizard.duration_open') },
            ] },
            { name: 'end_date', type: 'date', label: t('ui.wizard.end_date'), value: answers.end_date ?? '', hint: t('ui.wizard.end_date_help'), when: (v) => v.duration !== 'open' },
            { name: 'notice_date', type: 'date', label: t('ui.wizard.notice_date'), value: answers.notice_date ?? '', hint: t('ui.wizard.notice_date_help') },
        ];
    }

    function page4() {
        return [
            { name: 'rent_amount', type: 'money', label: t('ui.wizard.rent_amount'), value: answers.rent_amount ?? '', required: true, hint: t('ui.wizard.rent_amount_help'), suffix: currency },
            { name: 'frequency', type: 'select', label: t('ui.wizard.frequency'), value: answers.frequency ?? 'monthly', hint: t('ui.wizard.frequency_help'), options: ['monthly', 'quarterly', 'bi_yearly', 'yearly'].map((f) => ({ value: f, label: frequencyLabel(f) })) },
            { name: 'due_day', type: 'number', label: t('ui.wizard.due_day'), value: answers.due_day ?? '', min: 1, max: 31, step: 1, hint: t('ui.wizard.due_day_help') },
            { name: 'fee_vat', type: 'number', label: t('ui.wizard.fee_vat'), value: answers.fee_vat ?? '', min: 0, max: 100, step: 0.01, suffix: '%', hint: t('ui.wizard.fee_vat_help') },
            { name: 'proration', type: 'money', label: t('ui.wizard.proration'), value: answers.proration ?? '', hint: t('ui.wizard.proration_help'), suffix: currency },
            { name: 'security_deposit', type: 'money', label: t('ui.wizard.security_deposit'), value: answers.security_deposit ?? '0', suffix: currency },
            { name: 'h_deposit', type: 'heading', label: t('ui.wizard.security_deposit'), hint: t('ui.leases.security_deposit_received_help'), when: (v) => Number(v.security_deposit) > 0 },
            { name: 'deposit_date', type: 'date', label: t('ui.wizard.advance_date'), value: answers.deposit_date ?? '', when: (v) => Number(v.security_deposit) > 0 },
            { name: 'deposit_method', type: 'select', label: t('ui.wizard.advance_method'), value: answers.deposit_method ?? 'bank_transfer', when: (v) => Number(v.security_deposit) > 0, options: [
                { value: 'bank_transfer', label: t('ui.wizard.method_bank_transfer') },
                { value: 'momo', label: t('ui.wizard.method_mobile_money') },
                { value: 'cash', label: t('ui.wizard.method_cash') },
                { value: 'cheque', label: t('ui.wizard.method_cheque') },
            ] },
            { name: 'deposit_reference', type: 'text', label: t('ui.wizard.advance_reference'), value: answers.deposit_reference ?? '', maxlength: 255, when: (v) => Number(v.security_deposit) > 0 },
        ];
    }

    function page5() {
        return [
            { name: 'note5', type: 'note', label: t('ui.wizard.step5_note') },
            { name: 'advance_amount', type: 'money', label: t('ui.wizard.advance_amount'), value: answers.advance_amount ?? '0', suffix: currency },
            { name: 'rent_reserve', type: 'money', label: t('ui.wizard.rent_reserve'), value: answers.rent_reserve ?? '0', hint: t('ui.wizard.rent_reserve_help'), suffix: currency },
            { name: 'consumable', type: 'readonly', label: t('ui.wizard.consumable_advance'), hint: t('ui.wizard.consumable_advance_help'), value: money(Math.max(0, Number(answers.advance_amount || 0) - Number(answers.rent_reserve || 0))) },
            { name: 'advance_received', type: 'toggle', label: t('ui.wizard.advance_received'), value: answers.advance_received !== false },
            { name: 'advance_date', type: 'date', label: t('ui.wizard.advance_date'), value: answers.advance_date ?? '', when: (v) => v.advance_received === true },
            { name: 'advance_method', type: 'select', label: t('ui.wizard.advance_method'), value: answers.advance_method ?? 'cash', when: (v) => v.advance_received === true, options: [
                { value: 'cash', label: t('ui.wizard.method_cash') },
                { value: 'bank_transfer', label: t('ui.wizard.method_bank_transfer') },
                { value: 'cheque', label: t('ui.wizard.method_cheque') },
                { value: 'momo', label: t('ui.wizard.method_mobile_money') },
            ] },
            { name: 'cashier', type: 'readonly', label: t('ui.wizard.cashier'), value: userName || t('ui.wizard.cashier_placeholder'), when: (v) => v.advance_received === true && v.advance_method === 'cash' },
            { name: 'advance_reference', type: 'text', label: t('ui.wizard.advance_reference'), value: answers.advance_reference ?? '', maxlength: 255, when: (v) => v.advance_received === true },
        ];
    }

    function page6() {
        return [
            { name: 'increment_type', type: 'select', label: t('ui.wizard.increment_type'), value: answers.increment_type ?? 'none', options: [
                { value: 'none', label: t('ui.wizard.increment_none') },
                { value: 'percentage', label: t('ui.wizard.increment_percentage') === 'ui.wizard.increment_percentage' ? t('lease.kind.percentage') : t('ui.wizard.increment_percentage') },
                { value: 'fixed', label: t('ui.wizard.increment_fixed') === 'ui.wizard.increment_fixed' ? t('lease.kind.fixed') : t('ui.wizard.increment_fixed') },
            ] },
            { name: 'increment_value', type: 'number', label: t('ui.wizard.increment_value'), value: answers.increment_value ?? '', min: 0, step: 0.01, when: (v) => v.increment_type !== 'none' },
            { name: 'increment_date', type: 'date', label: t('ui.wizard.increment_date'), value: answers.increment_date ?? '', hint: t('ui.wizard.increment_date_help'), when: (v) => v.increment_type !== 'none' },
        ];
    }

    function page7() {
        return [
            { name: 'agent_mode', type: 'select', label: t('ui.wizard.agent'), value: answers.agent_mode ?? 'none', options: [
                { value: 'none', label: t('ui.wizard.no_agent') },
                { value: 'existing', label: t('ui.wizard.use_existing_party'), disabled: agents.length === 0 },
                { value: 'new', label: t('ui.wizard.add_new_party') },
            ] },
            { name: 'agent_id', type: 'picker', label: t('ui.wizard.choose_agent'), placeholder: t('ui.wizard.search_agent'), value: answers.agent_id ?? '', options: agents.map(partyOption), search: searchParties('agent'), when: (v) => v.agent_mode === 'existing' },
            ...partyBlock('agent').map((f) => ({ ...f, value: answers[f.name] ?? f.value, when: (v) => v.agent_mode === 'new' && (f.when ? f.when(v) : true) })),
            { name: 'agent_commission', type: 'money', label: t('ui.wizard.agent_commission'), value: answers.agent_commission ?? '0', hint: t('ui.wizard.agent_commission_help'), suffix: currency, when: (v) => v.agent_mode !== 'none' },
            { name: 'fee_type', type: 'select', label: t('ui.wizard.fee_type'), value: answers.fee_type ?? 'percentage', options: [
                { value: 'percentage', label: t('ui.wizard.fee_percentage') },
                { value: 'fixed', label: t('ui.wizard.fee_fixed') },
                { value: 'none', label: t('ui.wizard.fee_none') },
            ] },
            { name: 'fee_value', type: 'number', label: t('ui.wizard.fee_value'), value: answers.fee_value ?? '', min: 0, step: 0.01, when: (v) => v.fee_type !== 'none' },
            { name: 'notes', type: 'textarea', rows: 3, label: t('ui.wizard.notes'), value: answers.notes ?? '', hint: t('ui.wizard.notes_help') },
        ];
    }

    function summaryRows() {
        const a = answers;
        const building = a.building_mode === 'new' ? a.building_name : chosenBuilding()?.name;
        const unit = a.unit_mode === 'new' ? a.unit_name : vacantUnits(a.building_id).find((u) => String(u.id) === String(a.unit_id))?.name;
        const tenant = a.tenant_mode === 'new' ? (a.tenant_type === 'person' ? `${a.tenant_given_names ?? ''} ${a.tenant_surname ?? ''}`.trim() : a.tenant_legal_name) : partyName(tenants.find((p) => String(p.id) === String(a.tenant_id)) ?? {});
        const agent = a.agent_mode === 'none' ? t('ui.wizard.no_agent') : a.agent_mode === 'new' ? (a.agent_type === 'person' ? `${a.agent_given_names ?? ''} ${a.agent_surname ?? ''}`.trim() : a.agent_legal_name) : partyName(agents.find((p) => String(p.id) === String(a.agent_id)) ?? {});
        const fee = a.fee_type === 'none' ? t('ui.wizard.fee_none') : `${a.fee_type === 'percentage' ? t('ui.wizard.fee_percentage') : t('ui.wizard.fee_fixed')} · ${a.fee_type === 'percentage' ? `${formatNumber(a.fee_value || 0)}%` : money(a.fee_value || 0)}`;
        const dash = (v) => (v === null || v === undefined || v === '' ? '—' : String(v));

        return [
            [t('ui.wizard.property'), dash(building)],
            [t('ui.wizard.unit'), dash(unit)],
            [t('ui.wizard.tenant'), dash(tenant)],
            [t('ui.wizard.start_date'), formatDate(a.start_date) || '—'],
            [t('ui.wizard.end_date'), a.duration === 'open' ? t('ui.wizard.duration_open') : (formatDate(a.end_date) || '—')],
            [t('ui.wizard.rent_amount'), `${money(a.rent_amount || 0)} · ${frequencyLabel(a.frequency)}`],
            [t('ui.wizard.security_deposit'), money(a.security_deposit || 0)],
            [t('ui.wizard.fee_vat'), `${formatNumber(a.fee_vat || 0)}%`],
            [t('ui.wizard.rent_reserve'), money(a.rent_reserve || 0)],
            [t('ui.wizard.advance_amount'), `${money(a.advance_amount || 0)}${a.advance_received !== false ? ` · ${t('ui.wizard.advance_received')}` : ''}`],
            [t('ui.wizard.consumable_advance'), money(Math.max(0, Number(a.advance_amount || 0) - Number(a.rent_reserve || 0)))],
            [t('ui.wizard.fee_type'), fee],
            [t('ui.wizard.agent'), a.agent_mode === 'none' ? t('ui.wizard.no_agent') : `${dash(agent)} · ${money(a.agent_commission || 0)}`],
            [t('ui.wizard.agent_commission'), a.agent_mode === 'none' ? t('ui.wizard.no_agent') : money(a.agent_commission || 0)],
            [t('ui.wizard.notes'), dash(a.notes)],
        ];
    }

    function page8() {
        return [
            { name: 'summary', type: 'note', label: '' },
            { name: 'note8', type: 'note', label: t('ui.wizard.step8_note') },
        ];
    }

    const PAGES = [page1, page2, page3, page4, page5, page6, page7, page8];

    /* ------------------------------------------------------- payload */

    function payload() {
        const a = answers;
        const body = {
            building: a.building_mode === 'new' ? { attributes: { name: a.building_name || null, address: a.building_address || null } } : { id: Number(a.building_id) },
            unit: a.unit_mode === 'new' ? { attributes: { name: a.unit_name || null, is_commercial: a.unit_commercial === true } } : { id: Number(a.unit_id) },
            tenant: a.tenant_mode === 'new' ? { attributes: partyAttributes(a, 'tenant') } : { id: Number(a.tenant_id) },
            lease: {
                status: 'active',
                start_date: a.start_date || null,
                end_date: a.duration === 'open' ? null : (a.end_date || null),
                termination_notice_date: a.notice_date || null,
                rent_amount: Number(a.rent_amount || 0),
                payment_frequency: a.frequency,
                due_day: a.due_day ? Number(a.due_day) : null,
                proration_amount: a.proration === '' || a.proration === undefined ? null : Number(a.proration),
                security_deposit_amount: Number(a.security_deposit || 0),
                rent_reserve_amount: Number(a.rent_reserve || 0),
                notes: a.notes || null,
                advance_payment_amount: Number(a.advance_amount || 0),
                advance_received: a.advance_received !== false,
                rent_increment_type: a.increment_type ?? 'none',
                rent_increment_value: a.increment_type === 'none' ? 0 : Number(a.increment_value || 0),
                next_rent_increment_date: a.increment_type === 'none' ? null : (a.increment_date || null),
                vat_rate: Number(a.fee_vat || 0),
                management_fee_type: a.fee_type ?? 'percentage',
                management_fee_value: a.fee_type === 'none' ? 0 : Number(a.fee_value || 0),
                agent_commission_amount: a.agent_mode === 'none' ? 0 : Number(a.agent_commission || 0),
            },
        };

        if (Number(a.security_deposit) > 0) {
            body.lease.security_deposit_received_date = a.deposit_date || null;
            body.lease.security_deposit_received_method = a.deposit_method || null;
            body.lease.security_deposit_received_reference = a.deposit_reference || null;
        }

        if (a.advance_received !== false) {
            body.lease.advance_received_date = a.advance_date || null;
            body.lease.advance_received_method = a.advance_method || null;
            body.lease.advance_received_reference = a.advance_reference || null;

            if (a.advance_method === 'cash') {
                body.lease.advance_received_collector = userName || null;
            }
        }

        if (ownershipAsked()) {
            body.owners = (a.owner_rows ?? []).map((row) => ({
                ...(row.mode === 'new' ? { attributes: partyAttributes({
                    owner_type: row.type, owner_given_names: row.given_names, owner_surname: row.surname, owner_legal_name: row.legal_name,
                    owner_contact_name: row.contact_name, owner_phone: row.phone, owner_email: row.email, owner_email_policy: row.email_policy,
                }, 'owner') } : { id: Number(row.party_id) }),
                ownership_percentage: Number(row.share || 0),
            }));
        }

        if (a.agent_mode === 'existing') {
            body.agent = { id: Number(a.agent_id) };
        } else if (a.agent_mode === 'new') {
            body.agent = { attributes: partyAttributes(a, 'agent') };
        }

        return body;
    }

    /* ---------------------------------------------------------- run */

    let step = 1;
    let serverDraftId = draftId;
    let serverErrors = null;

    while (true) {
        const fields = PAGES[step - 1]();
        const first = step === 1;
        const last = step === TOTAL;
        const errorsForPage = serverErrors;

        serverErrors = null;

        const outcome = await openSheet({
            title: `${t('ui.wizard.heading')} — ${t(`ui.wizard.step${step}_title`)}`,
            description: `${t('ui.wizard.step_counter', { current: step, total: TOTAL })} · ${t('ui.wizard.subtitle')}`,
            width: 'lg',
            submitLabel: last ? t('ui.wizard.create_activate') : t('ui.wizard.next'),
            cancelLabel: first ? t('ui.wizard.cancel') : t('ui.wizard.back'),
            fields: [
                { name: 'progress', type: 'note', label: '' },
                ...fields,
                { name: 'draft_row', type: 'note', label: '' },
            ],
            onChange: (values, api, changed) => {
                if (changed === null) {
                    mount(api.get('progress').node, el('div', { class: 'wizard-progress' }, [el('div', { class: 'wizard-progress-fill', style: `width:${(step / TOTAL) * 100}%` })]));

                    mount(api.get('draft_row').node, el('div', { class: 'inline' }, [
                        el('button', { class: 'button button-secondary button-compact', type: 'button', onclick: async (event) => {
                            const node = event.currentTarget;

                            Object.assign(answers, api.values());
                            node.disabled = true;

                            try {
                                const saved = await client.post('/lease-wizard/drafts', { id: serverDraftId, payload: { answers } });

                                serverDraftId = saved?.draft?.id ?? serverDraftId;
                                api.setSubmitDisabled(true);
                                document.querySelectorAll('.sheet-backdrop')[document.querySelectorAll('.sheet-backdrop').length - 1]?.remove();
                                resolveDraftSaved();
                            } catch (failure) {
                                api.error('draft_row', null);
                                node.disabled = false;
                                await informSheet({ title: t('ui.wizard.save_draft'), body: `${failure?.message ?? t('ui.wizard.save_failed')}${failure?.code ? ` (${failure.code})` : ''}`, tone: 'danger' });
                            }
                        } }, [icon('download-01', { size: 16 }), el('span', { text: t('ui.wizard.save_draft') })]),
                    ]));

                    if (step === 1) {
                        mount(api.get('glossary').node, el('dl', { class: 'wizard-glossary' }, ['organisation', 'party', 'owner', 'tenant', 'agent', 'property', 'unit', 'lease'].flatMap((term) => [
                            el('dt', { text: t(`ui.wizard.glossary_${term}_term`) }),
                            el('dd', { text: t(`ui.wizard.glossary_${term}_text`) }),
                        ])));
                    }

                    if (step === 8) {
                        mount(api.get('summary').node, el('dl', { class: 'dl' }, summaryRows().flatMap(([label, value]) => [el('dt', { text: label }), el('dd', { text: value })])));
                    }

                    if (errorsForPage) {
                        for (const [key, text] of Object.entries(errorsForPage)) {
                            api.error(key, text);
                        }
                    }
                }

                if (step === 3 && (changed === 'duration' || changed === 'start_date')) {
                    const months = Number(values.duration);

                    if (Number.isFinite(months) && months > 0 && values.start_date) {
                        api.set('end_date', endDateAfterMonths(values.start_date, months));
                    }
                }

                if (step === 5 && (changed === 'advance_amount' || changed === 'rent_reserve')) {
                    api.set('consumable', money(Math.max(0, Number(values.advance_amount || 0) - Number(values.rent_reserve || 0))));
                }

                if (step === 2 && changed === 'building_id') {
                    const vacant = vacantUnits(values.building_id);

                    api.options('unit_mode', [
                        { value: 'existing', label: t('ui.wizard.use_existing_unit'), disabled: vacant.length === 0 },
                        { value: 'new', label: t('ui.wizard.add_new_unit') },
                    ], vacant.length > 0);
                    api.get('unit_id')?.setOptions(vacant.map((u) => ({ value: String(u.id), label: u.name, sub: u.description ?? '' })));

                    if (vacant.length === 0) {
                        api.set('unit_mode', 'new');
                    }

                    api.get('unit_mode')?.node.querySelector('select')?.dispatchEvent(new Event('change'));
                }
            },
            onSubmit: async (values) => {
                Object.assign(answers, values);

                if (! last) {
                    return 'next';
                }

                try {
                    await client.post('/lease-wizard', payload());
                } catch (failure) {
                    if (failure?.isValidation && failure.errors) {
                        const mapped = {};
                        let lowest = TOTAL;

                        for (const [key, list] of Object.entries(failure.errors)) {
                            const target = stepFor(key);

                            lowest = Math.min(lowest, target);
                            mapped[key] = Array.isArray(list) ? list[0] : String(list);
                        }

                        serverErrors = mapped;
                        step = lowest;

                        return 'jump';
                    }

                    throw failure;
                }

                if (serverDraftId !== null) {
                    await client.delete(`/lease-wizard/drafts/${serverDraftId}`).catch(() => {});
                }

                return 'created';
            },
        });

        if (draftSaved) {
            draftSaved = false;

            return false;
        }

        if (outcome === 'created') {
            return true;
        }

        if (outcome === 'jump') {
            continue;
        }

        if (outcome === 'next') {
            step += 1;
            continue;
        }

        /* Cancel on page one leaves; Back on any other page steps back. */
        if (first) {
            return false;
        }

        step -= 1;
    }

    /* Draft saving closes the sheet from inside; this is how the loop learns. */
    function resolveDraftSaved() {
        draftSaved = true;
    }
}

let draftSaved = false;
