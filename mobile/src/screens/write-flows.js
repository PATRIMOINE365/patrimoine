/*
 * The three flows that move money: Record payment, Record payout, Record
 * expense. Tablet only, by Komla's decision - the phone stays a reading
 * tool and nothing moves money from a handset.
 *
 * Each one is defined against the server's OWN validation rules, read from
 * StorePaymentRequest, StoreOwnerPayoutRequest and StoreOwnerExpenseRequest
 * rather than guessed. Two things those rules make non-negotiable:
 *
 *   1. `amount` is validated as INTEGER, in whole currency units. A decimal
 *      is not a smaller payment, it is a 422. The money field only accepts
 *      digits.
 *   2. `payment_method` is one of exactly four values. They are listed here
 *      as a closed set, not a free-text field, so a typo cannot become a
 *      rejected payment after somebody has counted the cash.
 *
 * Each flow is opened FROM A RECORD, so the identifier it needs is already
 * known and never has to be picked from a second list.
 */

import { openSheet, today } from '../ui/sheet.js';
import { t } from '../i18n/index.js';
import * as store from '../data/store.js';
import { endpoints } from '../api/endpoints.js';

/* The four the API accepts. Nothing else validates. */
const PAYMENT_METHODS = [
    { value: 'cash', label: 'method.cash' },
    { value: 'bank_transfer', label: 'method.bank_transfer' },
    { value: 'momo', label: 'method.momo' },
    { value: 'cheque', label: 'method.cheque' },
];

function methodOptions() {
    return PAYMENT_METHODS.map((method) => ({
        value: method.value,
        label: t(method.label),
    }));
}

function amountField() {
    return {
        name: 'amount',
        label: t('write.amount'),
        type: 'money',
        hint: t('write.amount_hint'),
    };
}

function referenceAndNotes() {
    return [
        { name: 'reference', label: t('write.reference'), type: 'text' },
        { name: 'notes', label: t('write.notes'), type: 'textarea' },
    ];
}

/*
 * After a write, what was held is wrong. Refreshing the whole working set
 * is cheap and cannot miss a knock-on: a payment changes the lease, the
 * tenant account and the dashboard, not just the row that was edited.
 */
async function settle(client) {
    await store.refreshAll(client);
}

/** Record a payment against a lease. POST /payments */
export async function recordPayment(client, lease) {
    const submitted = await openSheet({
        title: t('write.record_payment'),
        submitLabel: t('write.record'),
        fields: [
            amountField(),
            { name: 'payment_date', label: t('write.payment_date'), type: 'date', value: today() },
            { name: 'payment_method', label: t('write.method'), type: 'select', options: methodOptions(), value: 'cash' },
            ...referenceAndNotes(),
        ],
        onSubmit: async (values) => {
            await client.post('/payments', {
                lease_id: lease.id,
                amount: Number(values.amount),
                payment_date: values.payment_date,
                payment_method: values.payment_method,
                /* Omitted rather than sent empty: both are nullable. */
                ...(values.reference ? { reference: values.reference } : {}),
                ...(values.notes ? { notes: values.notes } : {}),
            });
        },
    });

    if (submitted) {
        await settle(client);
    }

    return submitted;
}

/** Pay an owner out. POST /owner-accounts/{id}/payouts */
export async function recordPayout(client, ownerAccount) {
    const submitted = await openSheet({
        title: t('write.record_payout'),
        submitLabel: t('write.record'),
        fields: [
            amountField(),
            { name: 'payout_date', label: t('write.payout_date'), type: 'date', value: today() },
            { name: 'payment_method', label: t('write.method'), type: 'select', options: methodOptions(), value: 'bank_transfer' },
            ...referenceAndNotes(),
        ],
        onSubmit: async (values) => {
            await client.post(`${endpoints.ownerAccount(ownerAccount.id)}/payouts`, {
                amount: Number(values.amount),
                payout_date: values.payout_date,
                payment_method: values.payment_method,
                ...(values.reference ? { reference: values.reference } : {}),
                ...(values.notes ? { notes: values.notes } : {}),
            });
        },
    });

    if (submitted) {
        await settle(client);
    }

    return submitted;
}

/**
 * Record an expense against a property. POST /owner-expenses
 *
 * unit_id is nullable, so an expense may belong to the building as a whole.
 * It is not offered here: choosing a unit needs a picker fed by that
 * building's units, and an expense filed against the wrong unit is worse
 * than one filed against the building.
 */
export async function recordExpense(client, building) {
    const submitted = await openSheet({
        title: t('write.record_expense'),
        submitLabel: t('write.record'),
        fields: [
            { name: 'description', label: t('write.description'), type: 'text' },
            amountField(),
            { name: 'expense_date', label: t('write.expense_date'), type: 'date', value: today() },
            ...referenceAndNotes(),
        ],
        onSubmit: async (values) => {
            await client.post('/owner-expenses', {
                building_id: building.id,
                description: values.description,
                amount: Number(values.amount),
                expense_date: values.expense_date,
                ...(values.reference ? { reference: values.reference } : {}),
                ...(values.notes ? { notes: values.notes } : {}),
            });
        },
    });

    if (submitted) {
        await settle(client);
    }

    return submitted;
}

/* Which action a section's record offers, if any. */
export const ACTIONS = {
    leases: { label: 'write.record_payment', icon: 'coins-stacked', run: recordPayment },
    accounting: { label: 'write.record_payout', icon: 'wallet', run: recordPayout },
    properties: { label: 'write.record_expense', icon: 'file-05', run: recordExpense },
};
