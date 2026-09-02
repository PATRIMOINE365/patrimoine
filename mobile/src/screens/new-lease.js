/*
 * Creating a lease.
 *
 * StoreLeaseRequest requires sixteen fields, and Laravel reads `required`
 * as "absent is invalid" - so a short form is not an option: every one of
 * them has to be answered before the lease can exist. That is precisely why
 * the web application uses a guided assistant rather than a single page.
 *
 * This is that assistant, in one sheet: the unit and the tenant, then the
 * money, then the policies. The defaults below are the ones that make a
 * plain monthly tenancy, so the common case is mostly confirmation - but
 * every one of them is SHOWN, never assumed silently, because each one
 * decides what somebody is charged.
 *
 * Tablet only. Money does not start on a phone.
 */

import { openSheet, today } from '../ui/sheet.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { titleOf } from '../data/record.js';
import * as store from '../data/store.js';

function options(values, prefix) {
    return values.map((value) => ({ value, label: t(`${prefix}.${value}`) }));
}

/* Every unit of every building already held, as one pickable list. */
function unitOptions() {
    const buildings = store.read('buildings').data;
    const list = Array.isArray(buildings) ? buildings : (buildings?.data ?? []);

    return list.flatMap((building) => (building.units ?? []).map((unit) => ({
        value: String(unit.id),
        label: `${unit.name ?? unit.label ?? `#${unit.id}`} — ${building.name}`,
    })));
}

function tenantOptions() {
    const parties = store.read('parties').data;
    const list = Array.isArray(parties) ? parties : (parties?.data ?? []);

    return list
        .filter((party) => String(party.roles ?? party.role ?? '').includes('tenant'))
        .map((party) => ({ value: String(party.id), label: titleOf(party) }));
}

/**
 * @returns {Promise<boolean>} whether a lease was created
 */
export async function newLease(client) {
    const units = unitOptions();
    const tenants = tenantOptions();

    /*
     * Without a unit or a tenant there is nothing to lease. Saying so is
     * better than a form whose first two fields are empty lists.
     */
    if (units.length === 0 || tenants.length === 0) {
        await openSheet({
            title: t('lease.new'),
            submitLabel: t('common.close'),
            fields: [{
                name: 'note',
                type: 'note',
                label: units.length === 0 ? t('lease.no_units') : t('lease.no_tenants'),
            }],
            onSubmit: async () => {},
        });

        return false;
    }

    const created = await openSheet({
        title: t('lease.new'),
        submitLabel: t('lease.create'),
        fields: [
            { name: 'unit_id', label: t('lease.unit'), type: 'select', options: units },
            { name: 'tenant_id', label: t('nav.tenants'), type: 'select', options: tenants },
            { name: 'start_date', label: t('lease.start_date'), type: 'date', value: today() },
            { name: 'status', label: t('lease.status'), type: 'select', options: options(['draft', 'active', 'notice', 'terminated'], 'lease.status'), value: 'draft' },

            { name: 'rent_amount', label: t('lease.rent'), type: 'money', hint: t('write.amount_hint') },
            { name: 'payment_frequency', label: t('lease.frequency'), type: 'select', options: options(['monthly', 'quarterly', 'bi_yearly', 'yearly'], 'lease.frequency'), value: 'monthly' },
            { name: 'vat_rate', label: t('lease.vat_rate'), type: 'text', value: '0' },

            { name: 'security_deposit_amount', label: t('lease.deposit'), type: 'money', value: '0' },
            { name: 'advance_payment_amount', label: t('lease.advance'), type: 'money', value: '0' },
            { name: 'rent_reserve_amount', label: t('lease.reserve'), type: 'money', value: '0' },
            { name: 'advance_received', label: t('lease.advance_received'), type: 'select', options: [
                { value: 'no', label: t('common.no') },
                { value: 'yes', label: t('common.yes') },
            ], value: 'no' },

            { name: 'rent_increment_type', label: t('lease.increment_type'), type: 'select', options: options(['none', 'percentage', 'fixed'], 'lease.kind'), value: 'none' },
            { name: 'rent_increment_value', label: t('lease.increment_value'), type: 'text', value: '0' },
            { name: 'management_fee_type', label: t('lease.fee_type'), type: 'select', options: options(['none', 'percentage', 'fixed'], 'lease.kind'), value: 'none' },
            { name: 'management_fee_value', label: t('lease.fee_value'), type: 'text', value: '0' },
            { name: 'agent_commission_amount', label: t('lease.commission'), type: 'money', value: '0' },
        ],
        onSubmit: async (values) => {
            await client.post(endpoints.leases, {
                unit_id: Number(values.unit_id),
                tenant_id: Number(values.tenant_id),
                start_date: values.start_date,
                status: values.status,

                rent_amount: Number(values.rent_amount || 0),
                payment_frequency: values.payment_frequency,
                /* numeric, not integer: a VAT rate is a percentage. */
                vat_rate: Number(values.vat_rate || 0),

                security_deposit_amount: Number(values.security_deposit_amount || 0),
                advance_payment_amount: Number(values.advance_payment_amount || 0),
                rent_reserve_amount: Number(values.rent_reserve_amount || 0),
                advance_received: values.advance_received === 'yes',

                rent_increment_type: values.rent_increment_type,
                rent_increment_value: Number(values.rent_increment_value || 0),
                management_fee_type: values.management_fee_type,
                management_fee_value: Number(values.management_fee_value || 0),
                agent_commission_amount: Number(values.agent_commission_amount || 0),
            });
        },
    });

    if (created) {
        await store.refreshAll(client);
    }

    return created;
}
