/*
 * Owners - the directory, the owner record with its figures, properties,
 * ledger, withdrawals, transfers and expense bills, and every drawer the
 * browser has: Deposit, Withdrawal, Adjustment, Expense bill (with the
 * split preview), Pay bill, Cancel payment, Transfer, Accounts, Statement.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { openSheet, today } from '../ui/sheet.js';
import { table, pagedTable, pagination, pageSize, loading, emptyState, badge, button, section, stat } from '../ui/table.js';
import { money } from '../ui/money.js';
import { formatDate, formatNumber, joinParts, addDays, domain } from '../ui/format.js';
import { openDocument } from '../data/exports.js';
import * as store from '../data/store.js';
import {
    screenHead, bannerHost, showError, showSuccess, rows, pageMeta, query, partyName, partyContact,
    paymentMethodLabel, pdfButton, resendButton, dash,
} from './common.js';
import { searchField } from '../ui/search.js';
import { splitShares } from '../data/split.js';

const CATEGORY = {
    owner_deposit: 'owner_deposit', rent_entitlement: 'rent_collected', expense: 'property_expense', management_fee: 'management_fee',
    management_fee_vat: 'management_fee_vat', agent_commission: 'agent_commission', adjustment: 'adjustment', payout: 'owner_payout', reserve_transfer: 'reserve_transfer',
};

function categoryLabel(category) {
    return CATEGORY[category] ? t(`ui.owners.${CATEGORY[category]}`) : domain('owners', category);
}

function purposeLabel(purpose) {
    return purpose ? domain('owners', purpose) : null;
}

function methodOptions() {
    return ['bank_transfer', 'momo', 'cash', 'cheque'].map((value) => ({ value, label: paymentMethodLabel(value) }));
}

export function ownersScreen(client) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const directory = el('div');
    const detail = el('div');
    const size = pageSize('owners');
    const ledgerSize = pageSize('owner-ledger', { panel: true });
    let page = 1;
    let search = '';
    let selectedId = null;
    let owner = null;
    let transfers = [];
    let bills = null;
    const me = store.read('me').data ?? {};
    const userName = (me.user ?? me).name ?? '';
    const fail = (message) => (failure) => showError(errors, failure, message);

    const searchBox = searchField((value) => { search = value; reloadDirectory(1); });

    searchBox.input.placeholder = t('ui.owners.search_placeholder');

    /* ---------------------------------------------------- directory */

    async function reloadDirectory(next = page) {
        page = next;
        mount(directory, loading(t('ui.owners.loading')));

        try {
            const payload = await client.get(`${endpoints.ownerAccounts}${query({ page, per_page: size.get(), search })}`);
            const found = rows(payload);

            mount(directory,
                found.length === 0 ? emptyState('user-check', t('ui.owners.no_search_results')) : el('div', { class: 'directory' }, found.map((account) => el('button', {
                    class: `directory-row${String(account.id) === String(selectedId) ? ' is-selected' : ''}`, type: 'button', onclick: () => select(account.id),
                }, [
                    el('span', { class: 'grow' }, [
                        el('span', { class: 'cell-strong', style: 'display:block', text: partyName(account.party) || t('ui.owners.unnamed_owner') }),
                        el('span', { class: 'muted-small', style: 'display:block', text: partyContact(account.party) }),
                        el('span', { class: 'muted-small', style: 'display:block', text: `${formatNumber(account.property_count ?? 0)} ${Number(account.property_count) === 1 ? t('ui.owners.property') : t('ui.owners.properties_lower')}` }),
                    ]),
                    el('span', { class: `directory-figure${Number(account.balance) < 0 ? ' is-negative' : ''}` }, [
                        el('span', { text: money(account.balance ?? 0) }),
                        el('span', { class: 'muted-small', text: t('ui.owners.balance') }),
                    ]),
                ]))),
                pagination(pageMeta(payload, size.get()), size, reloadDirectory)
            );

            if (selectedId === null && found.length > 0) {
                select(found[0].id);
            }
        } catch (failure) {
            mount(directory);
            showError(errors, failure, t('ui.owners.unable_to_load'));
        }
    }

    async function select(id, ledgerPage = 1) {
        selectedId = id;

        for (const node of directory.querySelectorAll('.directory-row')) {
            node.classList.remove('is-selected');
        }

        mount(detail, loading());

        try {
            [owner, transfers, bills] = await Promise.all([
                client.get(`${endpoints.ownerAccount(id)}${query({ transactions_page: ledgerPage, transactions_per_page: ledgerSize.get() })}`),
                client.get(`${endpoints.ownerAccount(id)}/reserve-transfers`).then(rows).catch(() => []),
                client.get(`${endpoints.ownerAccount(id)}/expense-bills`).catch(() => null),
            ]);

            paintDetail();
            reloadDirectory(page);
        } catch (failure) {
            mount(detail, emptyState('user-check', t('ui.owners.unable_to_load')));
            showError(errors, failure, t('ui.owners.unable_to_load'));
        }
    }

    async function refresh() {
        await store.refreshAll(client);

        if (selectedId !== null) {
            await select(selectedId);
        }
    }

    function ownerBuildings() {
        return (owner?.properties ?? []).map((p) => p.building).filter(Boolean);
    }

    /* ------------------------------------------------------ drawers */

    async function depositDrawer() {
        const buildings = ownerBuildings();

        const result = await openSheet({
            title: t('ui.owners.record_owner_deposit'),
            description: t('ui.owners.deposit_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'amount', type: 'money', label: t('ui.owners.amount'), required: true },
                { name: 'transaction_date', type: 'date', label: t('ui.owners.deposit_date'), value: today(), required: true },
                { name: 'payment_method', type: 'select', label: t('ui.owners.payment_method'), value: 'bank_transfer', options: methodOptions(), required: true },
                { name: 'deposit_purpose', type: 'select', label: t('ui.owners.deposit_purpose'), value: 'general_funding', required: true, options: [
                    { value: 'general_funding', label: t('ui.owners.general_funding') },
                    { value: 'property_expense', label: t('ui.owners.property_expense') },
                    { value: 'repair_maintenance', label: t('ui.owners.repair_maintenance_static') },
                    { value: 'other', label: t('ui.owners.other') },
                ] },
                { name: 'building_id', type: 'select', label: `${t('ui.owners.building')} ${t('ui.owners.optional')}`, value: '', options: [{ value: '', label: t('ui.owners.no_specific_building') }, ...buildings.map((b) => ({ value: String(b.id), label: b.name }))] },
                { name: 'unit_id', type: 'select', label: `${t('ui.owners.unit')} ${t('ui.owners.optional')}`, value: '', options: [{ value: '', label: t('ui.owners.select_building_first') }], disabled: true },
                { name: 'reference', type: 'text', label: `${t('ui.owners.reference')} ${t('ui.owners.optional')}`, maxlength: 255 },
                { name: 'collector', type: 'readonly', label: t('ui.owners.collector'), value: userName || t('ui.owners.collector_placeholder'), when: (v) => v.payment_method === 'cash' },
                { name: 'notes', type: 'textarea', rows: 3, label: `${t('ui.owners.notes')} ${t('ui.owners.optional')}` },
            ],
            onChange: (values, api, changed) => {
                if (changed === 'building_id') {
                    const building = buildings.find((b) => String(b.id) === values.building_id);

                    api.options('unit_id', building ? [{ value: '', label: t('ui.owners.no_specific_unit') }, ...(building.units ?? []).map((u) => ({ value: String(u.id), label: u.name }))] : [{ value: '', label: t('ui.owners.select_building_first') }], false);
                    api.disable('unit_id', ! building);
                }
            },
            validate: (values) => (! (Number(values.amount) > 0) ? { amount: t('ui.owners.invalid_deposit_amount') } : null),
            onSubmit: async (values) => client.post(`${endpoints.ownerAccount(owner.id)}/deposits`, {
                amount: Number(values.amount),
                transaction_date: values.transaction_date,
                payment_method: values.payment_method,
                deposit_purpose: values.deposit_purpose,
                building_id: values.building_id ? Number(values.building_id) : null,
                unit_id: values.unit_id ? Number(values.unit_id) : null,
                reference: values.reference || null,
                collector_name: values.payment_method === 'cash' ? (userName || null) : null,
                notes: values.notes || null,
            }),
        });

        if (result) {
            await refresh();

            const id = result?.transaction?.id;

            if (id) {
                openDocument(client, `/owner-deposits/${id}/receipt`).catch(fail(t('ui.owners.unable_to_open_document')));
            }
        }
    }

    async function payoutDrawer() {
        const balance = Number(owner?.balance ?? 0);

        if (balance <= 0) {
            showError(errors, null, t('ui.owners.no_payout_funds'));

            return;
        }

        const result = await openSheet({
            title: t('ui.owners.make_owner_payout'),
            description: t('ui.owners.payout_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'available', type: 'readonly', label: t('ui.owners.available_owner_balance'), value: money(balance) },
                { name: 'amount', type: 'money', label: t('ui.owners.amount'), required: true },
                { name: 'all', type: 'note', label: '' },
                { name: 'payout_date', type: 'date', label: t('ui.owners.payout_date'), value: today(), required: true },
                { name: 'payment_method', type: 'select', label: t('ui.owners.payment_method'), value: 'bank_transfer', options: methodOptions(), required: true },
                { name: 'reference', type: 'text', label: `${t('ui.owners.reference')} ${t('ui.owners.optional')}`, maxlength: 255 },
                { name: 'notes', type: 'textarea', rows: 3, label: `${t('ui.owners.notes')} ${t('ui.owners.optional')}` },
            ],
            onChange: (values, api, changed) => {
                if (changed === null) {
                    mount(api.get('all').node, button(t('ui.owners.withdraw_all'), { onClick: () => { api.set('amount', String(Math.max(0, Math.trunc(balance)))); api.get('amount').focus(); } }));
                }
            },
            validate: (values) => {
                if (! (Number(values.amount) > 0)) {
                    return { amount: t('ui.owners.invalid_payout_amount') };
                }

                if (Number(values.amount) > balance) {
                    return { amount: t('ui.owners.payout_exceeds_balance', { balance: money(balance) }) };
                }

                return null;
            },
            onSubmit: async (values) => client.post(`${endpoints.ownerAccount(owner.id)}/payouts`, {
                amount: Number(values.amount),
                payout_date: values.payout_date,
                payment_method: values.payment_method,
                reference: values.reference || null,
                notes: values.notes || null,
            }),
        });

        if (result) {
            await refresh();
        }
    }

    async function adjustmentDrawer() {
        const balance = Number(owner?.balance ?? 0);

        const result = await openSheet({
            title: t('ui.owners.owner_account_adjustment'),
            description: t('ui.owners.adjustment_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'warning', type: 'note', tone: 'warning', label: t('ui.owners.adjustment_warning') },
                { name: 'direction', type: 'select', label: t('ui.owners.direction'), value: 'credit', options: [
                    { value: 'credit', label: t('ui.owners.credit_increase_balance') },
                    { value: 'debit', label: t('ui.owners.debit_reduce_balance') },
                ] },
                { name: 'amount', type: 'money', label: t('ui.owners.amount'), required: true },
                { name: 'preview', type: 'note', label: '' },
                { name: 'transaction_date', type: 'date', label: t('ui.owners.adjustment_date'), value: today() },
                { name: 'reference', type: 'text', label: `${t('ui.owners.reference')} ${t('ui.owners.optional')}`, maxlength: 255 },
                { name: 'reason', type: 'textarea', rows: 4, maxlength: 1000, label: t('ui.owners.reason'), placeholder: t('ui.owners.adjustment_reason_placeholder'), required: true },
            ],
            onChange: (values, api) => {
                const amount = Number(api.values().amount || 0);
                const corrected = balance + (api.values().direction === 'debit' ? -amount : amount);

                mount(api.get('preview').node, el('div', { class: 'pair-grid pair-grid-2' }, [
                    [t('ui.owners.current_balance'), money(balance)],
                    [t('ui.tenants.correct_balance'), money(corrected)],
                ].map(([label, value]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: value })]))));
            },
            validate: (values) => {
                if (! (Number(values.amount) > 0)) {
                    return { amount: t('ui.owners.invalid_adjustment_amount') };
                }

                if (values.reason === '') {
                    return { reason: t('ui.owners.adjustment_reason_required') };
                }

                return null;
            },
            /*
             * The API takes the TARGET balance, not a delta: it is computed
             * here from the direction and amount the drawer collects.
             */
            onSubmit: async (values) => client.post(`${endpoints.ownerAccount(owner.id)}/adjustments`, {
                corrected_balance: balance + (values.direction === 'debit' ? -Number(values.amount) : Number(values.amount)),
                reason: values.reason,
                reference: values.reference || null,
            }),
        });

        if (result) {
            await refresh();

            const endpoint = result?.adjustment_voucher?.pdf_endpoint;

            if (endpoint) {
                openDocument(client, String(endpoint).replace(/^\/api(\/v\d+)?/, '')).catch(fail(t('ui.owners.unable_to_open_document')));
            }
        }
    }

    async function expenseBillDrawer() {
        const buildings = ownerBuildings();

        const result = await openSheet({
            title: t('ui.owners.expense_bill_title'),
            description: t('ui.owners.expense_bill_description'),
            width: 'lg',
            submitLabel: t('ui.owners.review'),
            fields: [
                { name: 'building_id', type: 'select', label: t('ui.owners.building'), value: '', required: true, options: [{ value: '', label: t('ui.owners.select_building') }, ...buildings.map((b) => ({ value: String(b.id), label: b.name }))] },
                { name: 'split', type: 'select', label: t('ui.owners.billing_mode'), value: 'single', required: true, options: [
                    { value: 'single', label: t('ui.owners.billing_mode_single') },
                    { value: 'split', label: t('ui.owners.billing_mode_split') },
                ] },
                { name: 'bill_date', type: 'date', label: t('ui.owners.bill_date'), value: today(), required: true },
                {
                    name: 'lines', type: 'lines', label: t('ui.owners.expense_lines'), min: 1, addLabel: t('ui.owners.add_line'), removeLabel: t('ui.owners.remove_line'),
                    value: [{ description: '', amount: '' }],
                    columns: [
                        { name: 'description', type: 'text', label: t('ui.owners.details'), placeholder: t('ui.owners.line_description_placeholder'), maxlength: 255, required: true },
                        { name: 'amount', type: 'money', label: t('ui.owners.amount'), required: true },
                    ],
                    total: (values) => `${t('ui.owners.bill_total')}: ${money(values.reduce((sum, row) => sum + Number(row.amount || 0), 0))}`,
                },
                { name: 'notes', type: 'textarea', rows: 3, label: `${t('ui.owners.notes')} ${t('ui.owners.optional')}` },
            ],
            validate: (values) => {
                if (! values.building_id) {
                    return { building_id: t('ui.owners.building_required') };
                }

                if ((values.lines ?? []).length === 0) {
                    return { lines: t('ui.owners.expense_bill_lines_required') };
                }

                if (values.lines.some((row) => row.description === '' || ! (Number(row.amount) > 0))) {
                    return { lines: t('ui.owners.expense_bill_line_invalid') };
                }

                return null;
            },
            review: (values) => {
                const building = buildings.find((b) => String(b.id) === values.building_id);
                const total = values.lines.reduce((sum, row) => sum + Number(row.amount || 0), 0);
                const coOwners = building?.ownerships ?? [];
                const shares = values.split === 'split' ? splitShares(values.lines, coOwners) : [];

                return el('div', { class: 'stack' }, [
                    el('h3', { class: 'sheet-heading-title', text: t('ui.owners.expense_review_title') }),
                    el('p', { class: 'muted-small', text: t('ui.owners.expense_review_description') }),
                    el('dl', { class: 'dl' }, [
                        el('dt', { text: t('ui.owners.building') }), el('dd', { text: building?.name ?? '' }),
                        el('dt', { text: t('ui.owners.billing_mode') }), el('dd', { text: values.split === 'split' ? t('ui.owners.billing_mode_split') : t('ui.owners.billing_mode_single') }),
                        ...values.lines.flatMap((row) => [el('dt', { text: row.description }), el('dd', { text: money(row.amount) })]),
                        el('dt', { class: 'cell-strong', text: t('ui.owners.bill_total') }), el('dd', { class: 'cell-strong', text: money(total) }),
                    ]),
                    values.split === 'split' ? el('div', { class: 'stack' }, [
                        el('h3', { class: 'sheet-heading-title', text: t('ui.owners.split_preview_title') }),
                        el('dl', { class: 'dl' }, coOwners.flatMap((o, i) => [el('dt', { text: `${o.name ?? partyName(o.party)} (${formatNumber(o.ownership_percentage)}%)` }), el('dd', { text: money(shares[i] ?? 0) })])),
                    ]) : null,
                ]);
            },
            onSubmit: async (values) => client.post(`${endpoints.ownerAccount(owner.id)}/expense-bills`, {
                building_id: Number(values.building_id),
                split: values.split,
                bill_date: values.bill_date,
                notes: values.notes || null,
                lines: values.lines.map((row) => ({ description: row.description, amount: Number(row.amount) })),
            }),
        });

        if (result) {
            await refresh();

            const bill = result?.expense_bill ?? result?.bills?.[0];

            if (bill?.id) {
                showSuccess(errors, t('ui.owners.expense_bill_recorded', { number: bill.bill_number ?? `#${bill.id}` }), [
                    { label: t('ui.owners.download_bill'), onClick: () => openDocument(client, `/owner-expense-bills/${bill.id}/pdf`).catch(fail(t('ui.owners.unable_to_open_document'))) },
                    { label: t('ui.owners.email_to_owner'), onClick: async () => {
                        try {
                            await client.post(`/owner-expense-bills/${bill.id}/send-email`, {});
                            showSuccess(errors, t('ui.owners.email_sent'));
                        } catch (failure) {
                            showError(errors, failure, t('ui.owners.email_failed'));
                        }
                    } },
                ]);
            }
        }
    }

    async function payBillDrawer(bill) {
        const balances = bills?.owner_account ?? {};
        const outstanding = Number(bill.outstanding ?? 0);

        const result = await openSheet({
            title: t('ui.owners.pay_bill_title'),
            description: t('ui.owners.pay_bill_description'),
            submitLabel: t('ui.owners.review'),
            fields: [
                { name: 'ctx', type: 'note', label: joinParts([`${t('ui.owners.expense_bill')}: ${bill.bill_number ?? `#${bill.id}`}`, `${t('ui.owners.outstanding')}: ${money(outstanding)}`]) },
                { name: 'funding_source', type: 'select', label: t('ui.owners.pay_source_account'), value: 'deposit_account', required: true, options: [
                    { value: 'deposit_account', label: `${t('ui.owners.deposit_account')} · ${money(balances.deposit_account_balance ?? owner?.deposit_account_balance ?? 0)}` },
                    { value: 'payout_account', label: `${t('ui.owners.payout_account')} · ${money(balances.payout_account_balance ?? owner?.payout_account_balance ?? 0)}` },
                ] },
                { name: 'amount', type: 'money', label: t('ui.owners.amount'), value: String(outstanding), required: true },
                { name: 'transaction_date', type: 'date', label: t('ui.owners.date'), value: today(), required: true },
            ],
            validate: (values) => {
                if (! values.funding_source || ! (Number(values.amount) > 0) || ! values.transaction_date) {
                    return { _: t('ui.owners.pay_fields_required') };
                }

                if (Number(values.amount) > outstanding) {
                    return { amount: t('ui.owners.pay_exceeds_bill') };
                }

                if (values.funding_source === 'payout_account' && Number(values.amount) > Number(balances.payout_account_balance ?? owner?.payout_account_balance ?? 0)) {
                    return { amount: t('ui.owners.pay_exceeds_payout') };
                }

                return null;
            },
            review: (values) => el('div', { class: 'stack' }, [
                el('h3', { class: 'sheet-heading-title', text: t('ui.owners.pay_review_title') }),
                el('p', { class: 'muted-small', text: t('ui.owners.pay_review_description') }),
                el('dl', { class: 'dl' }, [
                    el('dt', { text: t('ui.owners.expense_bill') }), el('dd', { text: bill.bill_number ?? `#${bill.id}` }),
                    el('dt', { text: t('ui.owners.pay_source_account') }), el('dd', { text: values.funding_source === 'payout_account' ? t('ui.owners.payout_account') : t('ui.owners.deposit_account') }),
                    el('dt', { text: t('ui.owners.amount') }), el('dd', { text: money(values.amount) }),
                    el('dt', { text: t('ui.owners.date') }), el('dd', { text: formatDate(values.transaction_date) }),
                ]),
            ]),
            onSubmit: async (values) => client.post(`/owner-expense-bills/${bill.id}/payments`, {
                funding_source: values.funding_source,
                amount: Number(values.amount),
                transaction_date: values.transaction_date,
            }),
        });

        if (result) {
            await refresh();
        }
    }

    async function cancelBillPaymentDrawer(bill) {
        const payment = (bill.payments ?? []).filter((p) => p.cancellable === true).pop();

        if (! payment) {
            return;
        }

        const done = await openSheet({
            title: t('ui.owners.cancel_payment_title'),
            description: t('ui.owners.cancel_payment_description'),
            submitLabel: t('ui.owners.cancel_payment'),
            submitKind: 'danger',
            fields: [
                { name: 'ctx', type: 'note', label: joinParts([bill.bill_number ?? `#${bill.id}`, `${money(payment.amount)} · ${formatDate(payment.transaction_date)}`]) },
                { name: 'reason', type: 'textarea', rows: 3, maxlength: 500, label: t('ui.owners.cancellation_reason'), required: true },
            ],
            validate: (values) => (values.reason === '' ? { reason: t('ui.owners.cancellation_reason_required') } : null),
            onSubmit: async (values) => client.post(`/owner-expense-bill-payments/${payment.id}/cancel`, { reason: values.reason }),
        });

        if (done) {
            await refresh();
        }
    }

    async function transferDrawer() {
        const result = await openSheet({
            title: t('ui.owners.transfer_title'),
            description: t('ui.owners.transfer_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'direction', type: 'select', label: t('ui.owners.transfer_direction'), value: 'to_expense', required: true, options: [
                    { value: 'to_expense', label: t('ui.owners.transfer_to_expense') },
                    { value: 'to_payout', label: t('ui.owners.transfer_to_payout') },
                ] },
                { name: 'available', type: 'readonly', label: t('ui.owners.transfer_available'), value: money(owner?.payout_account_balance ?? 0) },
                { name: 'amount', type: 'money', label: t('ui.owners.amount'), required: true },
                { name: 'transaction_date', type: 'date', label: t('ui.owners.transaction_date'), value: today(), required: true },
                { name: 'reason', type: 'textarea', rows: 3, maxlength: 1000, label: t('ui.owners.transfer_reason'), required: true },
            ],
            onChange: (values, api, changed) => {
                if (changed === 'direction') {
                    api.set('available', money(values.direction === 'to_payout' ? (owner?.deposit_account_balance ?? 0) : (owner?.payout_account_balance ?? 0)));
                }
            },
            validate: (values) => (! (Number(values.amount) > 0) ? { amount: t('ui.owners.invalid_transfer_amount') } : values.reason === '' ? { reason: t('ui.owners.transfer_reason') } : null),
            onSubmit: async (values) => client.post(`${endpoints.ownerAccount(owner.id)}/reserve-transfers`, {
                direction: values.direction,
                amount: Number(values.amount),
                transaction_date: values.transaction_date,
                reason: values.reason,
            }),
        });

        if (result) {
            await refresh();

            return true;
        }

        return false;
    }

    async function statementDrawer() {
        const last = owner?.last_payout_date ?? null;

        await openSheet({
            title: t('ui.owners.statement_title'),
            description: t('ui.owners.statement_description'),
            submitLabel: t('ui.owners.statement_generate'),
            cancelLabel: t('ui.owners.cancel'),
            fields: [
                { name: 'hint', type: 'note', label: last ? t('ui.owners.statement_since_payout', { date: formatDate(last) }) : t('ui.owners.statement_no_payout') },
                { name: 'from', type: 'date', label: t('ui.owners.statement_from'), value: last ? addDays(String(last).slice(0, 10), 1) : '' },
                { name: 'to', type: 'date', label: t('ui.owners.statement_to'), value: today() },
            ],
            onSubmit: async (values) => {
                await openDocument(client, `/reports/owners/${owner.party_id}/pdf${query({ from: values.from, to: values.to })}`);
            },
        });
    }

    async function accountsDrawer() {
        const totals = owner?.category_totals ?? {};
        const order = ['rent_entitlement', 'owner_deposit', 'management_fee', 'management_fee_vat', 'agent_commission', 'expense', 'payout', 'adjustment'];

        await openSheet({
            title: t('ui.owners.owner_accounts_title'),
            description: t('ui.owners.owner_accounts_description'),
            width: 'lg',
            submitLabel: t('ui.actions.close'),
            fields: [{ name: 'body', type: 'note', label: '' }],
            onSubmit: async () => true,
            onChange: (values, api, changed) => {
                if (changed !== null) return;

                const paint = () => mount(api.get('body').node,
                    el('p', { class: 'sheet-note is-info', text: t('ui.owners.consolidated_account_note') }),
                    el('div', { class: 'inline' }, [
                        button(t('ui.owners.statement'), { iconName: 'file-05', onClick: statementDrawer }),
                        can('manage_finance') ? button(t('ui.owners.transfer'), { kind: 'primary', iconName: 'refresh-cw', onClick: async () => { if (await transferDrawer()) { paint(); } } }) : null,
                    ].filter(Boolean)),
                    el('div', { class: 'kpis' }, [
                        stat(t('ui.owners.payout_account_balance'), money(owner.payout_account_balance ?? 0), { tone: Number(owner.payout_account_balance) < 0 ? 'danger' : undefined }),
                        stat(t('ui.owners.deposit_account_balance'), money(owner.deposit_account_balance ?? 0), { tone: Number(owner.deposit_account_balance) < 0 ? 'danger' : undefined }),
                        stat(t('ui.owners.current_balance'), money(owner.balance ?? 0)),
                        stat(t('ui.owners.properties'), formatNumber((owner.properties ?? []).length)),
                        stat(t('ui.owners.total_credits'), money(owner.credited_amount ?? 0), { tone: 'success' }),
                        stat(t('ui.owners.total_debits'), money(owner.debited_amount ?? 0), { tone: 'danger' }),
                    ]),
                    section(t('ui.owners.accounts_breakdown'), table([
                        { label: t('ui.owners.account'), cell: (row) => categoryLabel(row.category) },
                        { label: t('ui.owners.current_balance'), align: 'right', cell: (row) => el('span', { class: Number(row.amount) < 0 ? 'is-negative' : undefined, text: money(row.amount) }) },
                    ], order.map((category) => ({ category, amount: Number(owner.category_totals?.[category] ?? totals[category] ?? 0) })), {
                        footer: el('tr', {}, [el('td', { text: t('ui.owners.current_balance') }), el('td', { class: 'is-numeric', text: money(owner.balance ?? 0) })]),
                    }), { sub: t('ui.owners.accounts_breakdown_description') }),
                    section(t('ui.owners.recent_activity'), table([
                        { label: t('ui.owners.date'), cell: (tx) => formatDate(tx.transaction_date) },
                        { label: t('ui.owners.type'), cell: (tx) => categoryLabel(tx.category) },
                        { label: t('ui.owners.amount'), align: 'right', cell: (tx) => el('span', { class: tx.direction === 'credit' ? 'is-credit' : 'is-debit', text: `${tx.direction === 'credit' ? '+' : '−'} ${money(tx.amount)}` }) },
                    ], rows(owner.transactions), { empty: t('ui.owners.no_transactions') }), { sub: t('ui.owners.recent_activity_description') })
                );

                paint();
            },
        });
    }

    /* ---------------------------------------------------------- detail */

    function ledgerDetails(tx) {
        return joinParts([
            tx.payment_method ? paymentMethodLabel(tx.payment_method) : null,
            purposeLabel(tx.deposit_purpose),
            tx.reference ? `${t('ui.owners.reference_short')} ${tx.reference}` : null,
            tx.collector_name ? `${t('ui.owners.collector_short')} ${tx.collector_name}` : null,
            tx.invoice_id ? `${t('ui.owners.invoice')} #${tx.invoice_id}` : null,
            tx.notes,
        ]);
    }

    function paintDetail() {
        const party = owner.party ?? {};
        const properties = owner.properties ?? [];
        const unitRows = properties.flatMap((p) => ((p.building?.units ?? []).length === 0 ? [{ building: p.building, unit: null, share: p.ownership_percentage }] : (p.building.units ?? []).map((unit) => ({ building: p.building, unit, share: p.ownership_percentage }))));
        const ledger = owner.transactions ?? {};
        const billList = bills?.expense_bills ?? [];
        const balance = Number(owner.balance ?? 0);

        mount(detail,
            el('div', { class: 'screen-head' }, [
                el('div', {}, [
                    el('div', { class: 'inline' }, [
                        el('h2', { class: 'card-title', text: partyName(party) }),
                        badge(owner.status === 'active' ? t('ui.owners.active') : (owner.status ? String(owner.status) : t('ui.owners.unknown')), owner.status === 'active' ? 'success' : 'neutral'),
                    ]),
                    el('p', { class: 'screen-sub', text: partyContact(party) || t('ui.owners.no_contact_information') }),
                ]),
                el('div', { class: 'screen-actions' }, [
                    button(t('ui.owners.accounts'), { iconName: 'wallet', onClick: accountsDrawer }),
                    can('manage_finance') ? button(t('ui.owners.deposit'), { kind: 'primary', iconName: 'coins-stacked', onClick: depositDrawer }) : null,
                    can('manage_finance') ? button(t('ui.owners.payout'), { iconName: 'trend-down', disabled: balance <= 0, title: balance <= 0 ? t('ui.owners.no_funds_available') : undefined, onClick: payoutDrawer }) : null,
                    can('manage_finance') ? button(t('ui.owners.expense'), { iconName: 'file-05', onClick: expenseBillDrawer }) : null,
                    can('manage_finance') ? button(t('ui.owners.adjustment'), { iconName: 'scale-balanced', onClick: adjustmentDrawer }) : null,
                ].filter(Boolean)),
            ]),
            el('div', { class: 'kpis' }, [
                stat(t('ui.owners.current_balance'), money(balance), { tone: balance < 0 ? 'danger' : undefined }),
                stat(t('ui.owners.total_credits'), money(owner.credited_amount ?? 0), { tone: 'success' }),
                stat(t('ui.owners.total_debits'), money(owner.debited_amount ?? 0)),
                stat(t('ui.owners.properties'), formatNumber(properties.length)),
            ]),
            section(t('ui.owners.properties'), pagedTable('owner-properties', [
                { label: t('ui.owners.column_property'), cell: (r) => r.building?.name ?? '' },
                { label: t('ui.owners.column_location'), cell: (r) => dash(joinParts([r.building?.location, r.building?.address])) },
                { label: t('ui.owners.column_unit'), cell: (r) => dash(r.unit?.name) },
                { label: t('ui.owners.details'), cell: (r) => dash(r.unit?.description) },
                { label: t('ui.owners.column_share'), align: 'right', cell: (r) => `${formatNumber(r.share)}%` },
            ], unitRows, { empty: t('ui.owners.no_building_ownership') }), { sub: t('ui.owners.properties_description') }),
            section(t('ui.owners.owner_ledger'), el('div', { class: 'paged' }, [
                table([
                    { label: t('ui.owners.transaction_date'), cell: (tx) => formatDate(tx.transaction_date) },
                    { label: t('ui.owners.category'), cell: (tx) => categoryLabel(tx.category) },
                    { label: t('ui.owners.column_property'), cell: (tx) => dash(joinParts([tx.building?.name ?? tx.building, tx.unit?.name ?? tx.unit], ' / ')) },
                    { label: t('ui.owners.details'), cell: (tx) => dash(ledgerDetails(tx)) },
                    { label: t('ui.owners.amount'), align: 'right', cell: (tx) => el('span', { class: tx.direction === 'credit' ? 'is-credit' : 'is-debit', text: `${tx.direction === 'credit' ? '+' : '−'} ${money(tx.amount)}` }) },
                    { label: t('ui.owners.actions'), align: 'right', cell: (tx) => (tx.receipt_endpoint ? pdfButton(client, String(tx.receipt_endpoint).replace(/^\/api(\/v\d+)?/, ''), t('ui.owners.receipt'), { onFail: fail(t('ui.owners.unable_to_open_document')) }) : '') },
                ], rows(ledger), { empty: t('ui.owners.no_transactions') }),
                pagination(pageMeta(ledger, ledgerSize.get()), ledgerSize, (next) => select(owner.id, next)),
            ]), { sub: t('ui.owners.ledger_description') }),
            section(t('ui.owners.payout_history'), (owner.payouts ?? []).length === 0
                ? el('p', { class: 'table-empty', text: t('ui.owners.no_payouts') })
                : el('div', { class: 'stack' }, owner.payouts.map((payout) => el('div', { class: 'inline' }, [
                    el('span', { class: 'grow' }, [
                        el('span', { class: 'cell-strong', style: 'display:block', text: formatDate(payout.payout_date) }),
                        el('span', { class: 'muted-small', style: 'display:block', text: joinParts([paymentMethodLabel(payout.payment_method), payout.reference ? `${t('ui.owners.reference_short')} ${payout.reference}` : null, payout.notes]) }),
                    ]),
                    el('span', { class: 'cell-strong', text: money(payout.amount) }),
                    pdfButton(client, `/owner-payouts/${payout.id}/receipt`, t('ui.owners.receipt'), { onFail: fail(t('ui.owners.unable_to_open_document')) }),
                ]))), { sub: t('ui.owners.payout_history_description') }),
            section(t('ui.owners.transfers'), transfers.length === 0
                ? el('p', { class: 'table-empty', text: t('ui.owners.no_transfers') })
                : el('div', { class: 'stack' }, transfers.map((tr) => el('div', { class: 'inline' }, [
                    el('span', { class: 'grow' }, [
                        el('span', { class: 'cell-strong', style: 'display:block', text: joinParts([tr.reference, tr.direction === 'credit' ? t('ui.owners.transfer_to_expense') : t('ui.owners.transfer_to_payout')]) }),
                        el('span', { class: 'muted-small', style: 'display:block', text: joinParts([formatDate(tr.transaction_date), tr.notes]) }),
                    ]),
                    el('span', { class: 'cell-strong', text: money(tr.amount) }),
                    pdfButton(client, `/owner-reserve-transfers/${tr.id}/voucher`, t('ui.owners.voucher'), { onFail: fail(t('ui.owners.unable_to_open_voucher')) }),
                    can('manage_operations') ? resendButton(client, `/owner-reserve-transfers/${tr.id}/send-email`, { onFail: fail(t('ui.owners.unable_to_resend_voucher')) }) : null,
                ].filter(Boolean)))), { sub: t('ui.owners.transfers_description') }),
            section(t('ui.owners.expenses'), table([
                { label: t('ui.owners.expense_bill'), cell: (b) => el('span', { class: 'cell-strong', text: b.bill_number ?? `#${b.id}` }) },
                { label: t('ui.owners.date'), cell: (b) => formatDate(b.bill_date) },
                { label: t('ui.owners.amount'), align: 'right', cell: (b) => money(b.total_amount ?? 0) },
                { label: t('ui.owners.paid'), align: 'right', cell: (b) => money(b.paid ?? 0) },
                { label: t('ui.owners.outstanding'), align: 'right', cell: (b) => el('span', { class: 'cell-strong', text: money(b.outstanding ?? 0) }) },
                { label: t('ui.owners.status'), cell: (b) => badge(t(`ui.owners.bill_status_${b.payment_status}`), b.payment_status) },
                { label: t('ui.owners.actions'), align: 'right', cell: (b) => el('span', { class: 'cell-actions' }, [
                    can('manage_finance') && Number(b.outstanding ?? 0) > 0 ? button(t('ui.owners.pay'), { kind: 'primary', onClick: () => payBillDrawer(b) }) : null,
                    can('manage_finance') && (b.payments ?? []).some((p) => p.cancellable === true) ? button(t('ui.owners.cancel_payment'), { kind: 'danger-outline', onClick: () => cancelBillPaymentDrawer(b) }) : null,
                    pdfButton(client, `/owner-expense-bills/${b.id}/pdf`, t('ui.owners.invoice'), { onFail: fail(t('ui.owners.unable_to_open_document')) }),
                    b.payment_status === 'paid' && (b.payments ?? []).length > 0 ? pdfButton(client, `/owner-expense-bills/${b.id}/payment-receipt`, t('ui.owners.receipt'), { onFail: fail(t('ui.owners.unable_to_open_document')) }) : null,
                    can('manage_operations') ? resendButton(client, `/owner-expense-bills/${b.id}/send-email`, { onFail: fail(t('ui.owners.unable_to_resend_bill')) }) : null,
                ].filter(Boolean)) },
            ], billList, { empty: t('ui.owners.no_expense_bills') }), { sub: t('ui.owners.expense_bills_description') })
        );
    }

    mount(host,
        screenHead({ eyebrow: t('ui.owners.finance'), title: t('ui.owners.heading'), sub: t('ui.owners.page_description') }),
        errors,
        el('div', { class: 'split' }, [
            el('div', { class: 'card' }, [
                el('header', { class: 'card-head' }, [el('div', { class: 'card-words' }, [el('h2', { class: 'card-title', text: t('ui.owners.property_owners') }), el('p', { class: 'card-sub', text: t('ui.owners.search_description') })])]),
                el('div', { class: 'card-body' }, [searchBox.node, directory]),
            ]),
            el('div', {}, [detail]),
        ])
    );

    mount(detail, emptyState('user-check', t('ui.owners.select_property_owner'), t('ui.owners.select_owner_description')));
    reloadDirectory(1);

    return { node: host, reload: () => (selectedId !== null ? select(selectedId) : reloadDirectory(1)) };
}
