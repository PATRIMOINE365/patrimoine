/*
 * Tenants - the finance workspace for one tenant: a directory on the left,
 * the six panels on the right, and every money drawer the browser has:
 * Accounts (with Transfer), Deposit, Withdrawal, Expense, Adjustment, Pay
 * invoice and Cancel payment. Documents and their e-mails too.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { openSheet, today } from '../ui/sheet.js';
import { pagedTable, pagination, pageSize, loading, emptyState, badge, button, section, stat } from '../ui/table.js';
import { money } from '../ui/money.js';
import { formatDate, joinParts } from '../ui/format.js';
import { openDocument } from '../data/exports.js';
import * as store from '../data/store.js';
import {
    screenHead, bannerHost, showError, showSuccess, rows, pageMeta, query, partyName, partyContact, initials,
    leaseStatusLabel, paymentMethodLabel, fundTypeLabel, pdfButton, resendButton, dash, capitalise,
} from './common.js';
import { domain } from '../ui/format.js';
import { searchField } from '../ui/search.js';
import { hasRole } from '../data/record.js';

const METHODS = ['cash', 'bank_transfer', 'momo', 'cheque'];

function methodOptions(order = METHODS) {
    return order.map((value) => ({ value, label: paymentMethodLabel(value) }));
}

function leaseLabel(lease) {
    return joinParts([lease.reference, lease.unit?.building?.name, lease.unit?.name]);
}

export function tenantsScreen(client, { initialTenantId = null } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const directory = el('div', { class: 'directory' });
    const detail = el('div');
    const size = pageSize('tenants');
    let page = 1;
    let search = '';
    let listPayload = null;
    let selectedId = initialTenantId;
    let current = null;
    const me = store.read('me').data ?? {};
    const userName = (me.user ?? me).name ?? '';

    const searchBox = searchField((value) => { search = value; reloadDirectory(1); });

    searchBox.input.placeholder = t('ui.tenants.search_placeholder');

    /* ---------------------------------------------------- directory */

    function directoryRow(party) {
        return el('button', {
            class: `directory-row${String(party.id) === String(selectedId) ? ' is-selected' : ''}`, type: 'button',
            'aria-current': String(party.id) === String(selectedId) ? 'true' : undefined,
            onclick: () => select(party.id),
        }, [
            el('span', { class: 'avatar-initials', text: initials(partyName(party)) }),
            el('span', { class: 'grow' }, [
                el('span', { class: 'cell-strong', text: partyName(party) || t('ui.tenants.unnamed_tenant') }),
                el('span', { class: 'muted-small', text: party.phone || party.email || t('ui.tenants.no_contact_information') }),
                party.phone && party.email ? el('span', { class: 'muted-small', text: party.email }) : null,
            ].map((n) => (n ? el('span', { style: 'display:block' }, [n]) : null))),
            el('span', { class: 'muted-small', text: domain('parties', party.type) }),
        ]);
    }

    async function reloadDirectory(next = page) {
        page = next;
        mount(directory, loading(t('ui.tenants.loading')));

        try {
            listPayload = await client.get(`${endpoints.parties}${query({ role: 'tenant', page, per_page: size.get(), search })}`);
            const found = rows(listPayload);

            mount(directory,
                found.length === 0 ? emptyState('users-01', t('ui.tenants.no_search_results')) : el('div', { class: 'directory' }, found.map(directoryRow)),
                pagination(pageMeta(listPayload, size.get()), size, reloadDirectory)
            );

            if (selectedId === null && found.length > 0) {
                select(found[0].id);
            } else if (selectedId !== null && current === null) {
                select(selectedId);
            }
        } catch (failure) {
            mount(directory);
            showError(errors, failure, t('ui.tenants.unable_to_load'));
        }
    }

    /* ------------------------------------------------------- loading */

    async function select(id) {
        selectedId = id;
        current = null;

        for (const node of directory.querySelectorAll('.directory-row')) {
            node.classList.toggle('is-selected', false);
        }

        mount(detail, loading(t('ui.tenants.loading_details')));

        try {
            const party = await client.get(endpoints.party(id));

            if (! hasRole(party, 'tenant')) {
                throw new Error(t('ui.tenants.not_tenant'));
            }

            const leasesPayload = await client.get(`${endpoints.leases}${query({ tenant_id: id, per_page: 100 })}`);
            const leases = rows(leasesPayload);
            const statement = await client.get(`/reports/tenants/${id}`).catch(() => null);

            const details = await Promise.all(leases.map(async (lease) => {
                const full = lease.tenant_fund_accounts ? lease : await client.get(endpoints.lease(lease.id)).catch(() => lease);
                const payments = rows(await client.get(`/payments${query({ lease_id: lease.id, per_page: 100 })}`).catch(() => null));

                return { ...full, payments };
            }));

            current = { party, leases: details, statement };
            paintDetail();
            reloadDirectory(page);
        } catch (failure) {
            mount(detail, emptyState('users-01', t('ui.tenants.unable_to_load_tenant')));
            showError(errors, failure, t('ui.tenants.unable_to_load_details'));
        }
    }

    async function refresh() {
        await store.refreshAll(client);

        if (selectedId !== null) {
            await select(selectedId);
        }
    }

    /* --------------------------------------------------- accounts */

    function fundAccounts({ includeDraft = false, activeOnly = false } = {}) {
        return (current?.leases ?? [])
            .filter((lease) => includeDraft || lease.status !== 'draft')
            .flatMap((lease) => (lease.tenant_fund_accounts ?? []).map((account) => ({ ...account, lease })))
            .filter((account) => ! activeOnly || account.status === 'active');
    }

    function accountLabel(account) {
        return `${fundTypeLabel(account.type)} — ${money(account.balance ?? 0)} · ${leaseLabel(account.lease)}`;
    }

    function fundTransactions() {
        return fundAccounts({ includeDraft: true })
            .flatMap((account) => (account.transactions ?? []).map((tx) => ({ ...tx, fund_type: account.type, lease_id: account.lease.id })))
            .sort((a, b) => String(b.transaction_date).localeCompare(String(a.transaction_date)) || (b.id - a.id));
    }

    /* ---------------------------------------------------- documents */

    const fail = (message) => (failure) => showError(errors, failure, message);

    function receiptActions(endpoint, resendEndpoint, { label } = {}) {
        return el('span', { class: 'cell-actions' }, [
            pdfButton(client, endpoint, label ?? t('ui.tenants.receipt'), { onFail: fail(t('ui.tenants.unable_to_open_receipt')) }),
            can('manage_operations') ? resendButton(client, resendEndpoint, { label: t('ui.tenants.resend'), sending: t('ui.tenants.sending') === 'ui.tenants.sending' ? t('ui.owners.sending') : t('ui.tenants.sending'), sent: t('ui.tenants.sent'), onFail: fail(t('ui.tenants.unable_to_resend_receipt')) }) : null,
        ].filter(Boolean));
    }

    /* ------------------------------------------------------ drawers */

    function successWithDownload(message, endpoint, label) {
        showSuccess(errors, message, endpoint ? [{ label: label ?? t('ui.tenants.download_receipt'), onClick: () => openDocument(client, endpoint).catch(fail(t('ui.tenants.unable_to_open_receipt'))) }] : []);
    }

    async function accountsDrawer() {
        const accounts = fundAccounts();
        const summary = current?.statement?.summary ?? {};

        await openSheet({
            title: t('ui.tenants.accounts'),
            description: t('ui.tenants.accounts_description'),
            width: 'lg',
            submitLabel: t('ui.actions.close'),
            fields: [{ name: 'body', type: 'note', label: '' }],
            onSubmit: async () => true,
            onChange: (values, api, changed) => {
                if (changed !== null) return;

                const paint = () => mount(api.get('body').node,
                    el('div', { class: 'inline' }, [
                        el('span', { class: 'grow cell-strong', text: partyName(current.party) }),
                        can('manage_finance') ? button(t('ui.tenants.transfer'), { kind: 'primary', iconName: 'refresh-cw', onClick: async () => {
                            const done = await transferDrawer();

                            if (done) {
                                paint();
                            }
                        } }) : null,
                    ]),
                    section(t('ui.tenants.accounts'), pagedTable('tenant-accounts', [
                        { label: t('ui.tenants.lease'), cell: (a) => leaseLabel(a.lease) },
                        { label: t('ui.tenants.fund'), cell: (a) => el('span', { class: 'cell-strong', text: fundTypeLabel(a.type) }) },
                        { label: t('ui.tenants.current_balance'), align: 'right', cell: (a) => el('span', { class: 'cell-strong', text: money(a.balance ?? 0) }) },
                    ], fundAccounts(), {
                        empty: t('ui.tenants.no_accounts'),
                        footer: el('tr', {}, [el('td', { text: t('ui.tenants.total_held_funds') }), el('td'), el('td', { class: 'is-numeric', text: money(fundAccounts().reduce((sum, a) => sum + Number(a.balance ?? 0), 0)) })]),
                    })),
                    section(t('ui.tenants.financial_position'), el('div', { class: 'kpis' }, [
                        stat(t('ui.tenants.rent_outstanding'), money(summary.rent_outstanding ?? 0)),
                        stat(t('ui.tenants.security_deposit_debt'), money(summary.security_deposit_debt_outstanding ?? 0)),
                        stat(t('ui.tenants.total_outstanding'), money(summary.total_outstanding ?? 0), { tone: 'danger' }),
                    ]))
                );

                paint();
            },
        });
    }

    async function transferDrawer() {
        const accounts = fundAccounts({ activeOnly: true });

        if (accounts.length < 2) {
            showError(errors, null, t('ui.tenants.no_transferable_accounts'));

            return false;
        }

        const option = (a) => ({ value: String(a.id), label: accountLabel(a) });
        const byId = (id) => accounts.find((a) => String(a.id) === String(id));

        const result = await openSheet({
            title: t('ui.tenants.transfer'),
            description: t('ui.tenants.transfer_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'source_account_id', type: 'select', label: t('ui.tenants.source_account'), value: String(accounts[0].id), options: accounts.map(option), required: true },
                { name: 'destination_account_id', type: 'select', label: t('ui.tenants.destination_account'), value: String(accounts[1].id), options: accounts.slice(1).map(option), required: true },
                { name: 'preview', type: 'note', label: '' },
                { name: 'amount', type: 'money', label: t('ui.tenants.amount'), required: true },
                { name: 'reason', type: 'textarea', rows: 4, maxlength: 500, label: t('ui.tenants.reason'), placeholder: t('ui.tenants.transfer_reason_placeholder'), required: true },
                { name: 'reference', type: 'text', label: t('ui.tenants.reference'), maxlength: 255 },
            ],
            onChange: (values, api, changed) => {
                if (changed === 'source_account_id' || changed === null) {
                    api.options('destination_account_id', accounts.filter((a) => String(a.id) !== values.source_account_id).map(option), true);
                }

                const fresh = api.values();
                const source = byId(fresh.source_account_id);
                const destination = byId(fresh.destination_account_id);
                const amount = Number(fresh.amount || 0);

                mount(api.get('preview').node, el('div', { class: 'pair-grid pair-grid-4' }, [
                    [t('ui.tenants.source_balance'), money(source?.balance ?? 0)],
                    [t('ui.tenants.destination_balance'), money(destination?.balance ?? 0)],
                    [t('ui.tenants.resulting_source_balance'), money((source?.balance ?? 0) - amount)],
                    [t('ui.tenants.resulting_destination_balance'), money((destination?.balance ?? 0) + amount)],
                ].map(([label, value]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: value })]))));
            },
            validate: (values) => {
                if (! values.source_account_id || ! values.destination_account_id || ! (Number(values.amount) > 0) || values.reason === '') {
                    return { _: t('ui.tenants.transfer_required_fields') };
                }

                if (values.source_account_id === values.destination_account_id) {
                    return { destination_account_id: t('ui.tenants.transfer_same_account') };
                }

                if (Number(values.amount) > Number(byId(values.source_account_id)?.balance ?? 0)) {
                    return { amount: t('ui.tenants.transfer_exceeds_balance') };
                }

                return null;
            },
            onSubmit: async (values) => client.post('/tenant-funds/transfers', {
                source_account_id: Number(values.source_account_id),
                destination_account_id: Number(values.destination_account_id),
                amount: Number(values.amount),
                reason: values.reason,
                reference: values.reference || null,
            }),
        });

        if (result) {
            const endpoint = result?.transfer?.voucher?.pdf_endpoint ?? (result?.transfer?.debit_transaction?.id ? `/tenant-fund-transfers/${result.transfer.debit_transaction.id}/voucher` : null);

            await refresh();
            successWithDownload(t('ui.tenants.transfer_recorded'), endpoint ? String(endpoint).replace(/^\/api(\/v\d+)?/, '') : null);

            return true;
        }

        return false;
    }

    async function depositDrawer() {
        const leases = (current?.leases ?? []).filter((lease) => lease.status !== 'draft');

        function destinations(leaseId) {
            const list = [];

            for (const lease of leases.filter((l) => ! leaseId || String(l.id) === String(leaseId))) {
                const invoices = lease.invoices ?? [];
                const owes = invoices.some((inv) => Number(inv.outstanding_amount ?? 0) > 0 || ['issued', 'partial', 'overdue'].includes(inv.status));

                if (owes) {
                    list.push({ value: `rent:${lease.id}`, label: `${t('ui.tenants.rent_payment')} · ${leaseLabel(lease)}`, kind: 'rent', lease });
                }

                for (const account of (lease.tenant_fund_accounts ?? []).filter((a) => a.status === 'active')) {
                    list.push({ value: `fund:${account.id}`, label: accountLabel({ ...account, lease }), kind: 'fund', lease, account });
                }
            }

            return list;
        }

        const initial = destinations('');

        const result = await openSheet({
            title: t('ui.tenants.deposit_title'),
            description: t('ui.tenants.deposit_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'context', type: 'readonly', label: t('ui.tenants.transaction_context'), value: partyName(current.party) },
                { name: 'lease_id', type: 'select', label: `${t('ui.tenants.lease')} ${t('ui.tenants.optional')}`, value: '', hint: t('ui.tenants.any_account_help'), options: [{ value: '', label: t('ui.tenants.all_leases') }, ...leases.map((l) => ({ value: String(l.id), label: leaseLabel(l) }))] },
                { name: 'destination', type: 'select', label: t('ui.tenants.destination'), value: initial[0]?.value ?? '', options: initial.length === 0 ? [{ value: '', label: t('ui.tenants.no_eligible_accounts') }] : initial, required: true },
                { name: 'amount', type: 'money', label: t('ui.tenants.amount'), required: true },
                { name: 'preview', type: 'note', label: '' },
                { name: 'payment_method', type: 'select', label: t('ui.tenants.payment_method_label'), value: 'cash', options: methodOptions(), required: true },
                { name: 'cashier', type: 'readonly', label: t('ui.tenants.cash_receiver'), hint: t('ui.tenants.cash_receiver_help'), value: userName || t('ui.tenants.cash_receiver_automatic'), when: (v) => v.payment_method === 'cash' },
                { name: 'transaction_date', type: 'date', label: t('ui.tenants.transaction_date'), value: today(), required: true },
                { name: 'reference', type: 'text', label: `${t('ui.tenants.reference')} ${t('ui.tenants.optional')}`, maxlength: 255 },
                { name: 'notes', type: 'textarea', rows: 3, maxlength: 2000, label: `${t('ui.tenants.notes')} ${t('ui.tenants.optional')}` },
            ],
            onChange: (values, api, changed) => {
                if (changed === 'lease_id') {
                    const list = destinations(values.lease_id);

                    api.options('destination', list.length === 0 ? [{ value: '', label: t('ui.tenants.no_eligible_accounts') }] : list, true);
                }

                const fresh = api.values();
                const chosen = destinations(fresh.lease_id).find((d) => d.value === fresh.destination);
                const amount = Number(fresh.amount || 0);
                const balance = chosen?.kind === 'fund' ? Number(chosen.account.balance ?? 0) : null;

                mount(api.get('preview').node, el('div', { class: 'pair-grid pair-grid-3' }, [
                    [t('ui.tenants.current_balance'), balance === null ? '—' : money(balance)],
                    [t('ui.tenants.transaction_amount'), money(amount)],
                    [t('ui.tenants.resulting_balance'), balance === null ? '—' : money(balance + amount)],
                ].map(([label, value]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: value })]))));
            },
            validate: (values) => (! values.destination || ! (Number(values.amount) > 0) || ! values.transaction_date || ! values.payment_method ? { _: t('ui.tenants.transaction_required_fields') } : null),
            onSubmit: async (values) => {
                const chosen = destinations(values.lease_id).find((d) => d.value === values.destination);

                if (! chosen) {
                    throw new Error(t('ui.tenants.invalid_account'));
                }

                if (chosen.kind === 'rent') {
                    const payment = await client.post('/payments', {
                        lease_id: chosen.lease.id,
                        amount: Number(values.amount),
                        payment_date: values.transaction_date,
                        payment_method: values.payment_method,
                        reference: values.reference || null,
                        notes: values.notes || null,
                    });

                    return { kind: 'rent', receipt: payment?.id ? `/payments/${payment.id}/receipt` : null };
                }

                const saved = await client.post('/tenant-fund-deposits', {
                    lease_id: chosen.lease.id,
                    fund_type: chosen.account.type,
                    amount: Number(values.amount),
                    transaction_date: values.transaction_date,
                    payment_method: values.payment_method,
                    reference: values.reference || null,
                    notes: values.notes || null,
                });

                return { kind: 'fund', receipt: saved?.payment?.id ? `/payments/${saved.payment.id}/receipt` : null };
            },
        });

        if (result) {
            await refresh();
            successWithDownload(result.kind === 'rent' ? t('ui.tenants.rent_payment_recorded') : t('ui.tenants.deposit_recorded'), result.receipt);
        }
    }

    async function withdrawalDrawer() {
        const leases = (current?.leases ?? []).filter((lease) => lease.status !== 'draft');
        const eligible = (leaseId) => fundAccounts({ activeOnly: true })
            .filter((a) => ['rent_reserve', 'consumable_advance'].includes(a.type) && Number(a.balance ?? 0) > 0)
            .filter((a) => ! leaseId || String(a.lease.id) === String(leaseId));
        const option = (a) => ({ value: String(a.id), label: accountLabel(a) });
        const initial = eligible('');

        const result = await openSheet({
            title: t('ui.tenants.withdrawal_title'),
            description: t('ui.tenants.withdrawal_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'context', type: 'readonly', label: t('ui.tenants.transaction_context'), value: partyName(current.party) },
                { name: 'lease_id', type: 'select', label: `${t('ui.tenants.lease')} ${t('ui.tenants.optional')}`, value: '', options: [{ value: '', label: t('ui.tenants.all_leases') }, ...leases.map((l) => ({ value: String(l.id), label: leaseLabel(l) }))] },
                { name: 'account_id', type: 'select', label: t('ui.tenants.account'), value: initial[0] ? String(initial[0].id) : '', options: initial.length === 0 ? [{ value: '', label: t('ui.tenants.no_withdrawable_funds') }] : initial.map(option), required: true },
                { name: 'amount', type: 'money', label: t('ui.tenants.amount'), required: true },
                { name: 'preview', type: 'note', label: '' },
                { name: 'payment_method', type: 'select', label: t('ui.tenants.payment_method_label'), value: 'cash', options: methodOptions(), required: true },
                { name: 'cashier', type: 'readonly', label: t('ui.tenants.cash_receiver'), value: userName || t('ui.tenants.cash_receiver_automatic'), when: (v) => v.payment_method === 'cash' },
                { name: 'transaction_date', type: 'date', label: t('ui.tenants.transaction_date'), value: today(), required: true },
                { name: 'reference', type: 'text', label: `${t('ui.tenants.reference')} ${t('ui.tenants.optional')}`, maxlength: 255 },
                { name: 'notes', type: 'textarea', rows: 3, maxlength: 2000, label: `${t('ui.tenants.notes')} ${t('ui.tenants.optional')}` },
            ],
            onChange: (values, api, changed) => {
                if (changed === 'lease_id') {
                    const list = eligible(values.lease_id);

                    api.options('account_id', list.length === 0 ? [{ value: '', label: t('ui.tenants.no_withdrawable_funds') }] : list.map(option), true);
                }

                const fresh = api.values();
                const account = eligible('').find((a) => String(a.id) === fresh.account_id);
                const amount = Number(fresh.amount || 0);

                mount(api.get('preview').node, el('div', { class: 'pair-grid pair-grid-3' }, [
                    [t('ui.tenants.current_balance'), money(account?.balance ?? 0)],
                    [t('ui.tenants.transaction_amount'), money(amount)],
                    [t('ui.tenants.resulting_balance'), money((account?.balance ?? 0) - amount)],
                ].map(([label, value]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: value })]))));
            },
            validate: (values) => {
                if (! values.account_id || ! (Number(values.amount) > 0) || ! values.transaction_date) {
                    return { _: t('ui.tenants.transaction_required_fields') };
                }

                const account = eligible('').find((a) => String(a.id) === values.account_id);

                if (Number(values.amount) > Number(account?.balance ?? 0)) {
                    return { amount: t('ui.tenants.withdrawal_exceeds_balance') };
                }

                return null;
            },
            onSubmit: async (values) => client.post('/tenant-fund-withdrawals', {
                tenant_fund_account_id: Number(values.account_id),
                amount: Number(values.amount),
                transaction_date: values.transaction_date,
                payment_method: values.payment_method,
                reference: values.reference || null,
                notes: values.notes || null,
            }),
        });

        if (result) {
            await refresh();
            successWithDownload(t('ui.tenants.withdrawal_recorded'), result?.withdrawal_receipt?.pdf_endpoint ? String(result.withdrawal_receipt.pdf_endpoint).replace(/^\/api(\/v\d+)?/, '') : null);
        }
    }

    async function expenseDrawer() {
        const leases = (current?.leases ?? []).filter((lease) => lease.status !== 'draft');

        const result = await openSheet({
            title: t('ui.tenants.expense_title'),
            description: t('ui.tenants.expense_description_text'),
            width: 'lg',
            submitLabel: t('ui.tenants.review'),
            fields: [
                { name: 'lease_id', type: 'select', label: t('ui.tenants.lease'), value: '', hint: t('ui.tenants.expense_invoice_help'), options: [{ value: '', label: t('ui.tenants.select_lease') }, ...leases.map((l) => ({ value: String(l.id), label: leaseLabel(l) }))], required: true },
                { name: 'transaction_date', type: 'date', label: t('ui.tenants.transaction_date'), value: today(), required: true },
                { name: 'reference', type: 'text', label: `${t('ui.tenants.reference')} ${t('ui.tenants.optional')}`, maxlength: 255 },
                {
                    name: 'lines', type: 'lines', label: t('ui.tenants.expense_lines'), required: true, min: 1, addLabel: t('ui.tenants.add_line'), removeLabel: t('ui.tenants.remove_line'),
                    value: [{ description: '', amount: '' }],
                    columns: [
                        { name: 'description', type: 'text', label: t('ui.tenants.description') === 'ui.tenants.description' ? t('ui.owners.line_description_placeholder') : t('ui.tenants.description'), placeholder: t('ui.tenants.expense_line_description_placeholder'), maxlength: 255, required: true },
                        { name: 'amount', type: 'money', label: t('ui.tenants.amount'), required: true },
                    ],
                    total: (values) => `${t('ui.tenants.expense_total')}: ${money(values.reduce((sum, row) => sum + Number(row.amount || 0), 0))}`,
                },
            ],
            validate: (values) => {
                if (! values.lease_id || ! values.transaction_date) {
                    return { _: t('ui.tenants.expense_fields_required') };
                }

                if ((values.lines ?? []).length === 0 || values.lines.some((row) => row.description === '' || ! (Number(row.amount) > 0))) {
                    return { lines: t('ui.tenants.expense_line_invalid') };
                }

                return null;
            },
            review: (values) => {
                const lease = leases.find((l) => String(l.id) === values.lease_id);

                return el('div', { class: 'stack' }, [
                    el('h3', { class: 'sheet-heading-title', text: t('ui.tenants.expense_review_title') }),
                    el('p', { class: 'muted-small', text: t('ui.tenants.expense_review_description') }),
                    el('dl', { class: 'dl' }, [
                        el('dt', { text: t('ui.tenants.lease') }), el('dd', { text: leaseLabel(lease ?? {}) }),
                        el('dt', { text: t('ui.tenants.transaction_date') }), el('dd', { text: formatDate(values.transaction_date) }),
                        ...values.lines.flatMap((row) => [el('dt', { text: row.description }), el('dd', { text: money(row.amount) })]),
                        el('dt', { class: 'cell-strong', text: t('ui.tenants.expense_total') }), el('dd', { class: 'cell-strong', text: money(values.lines.reduce((sum, row) => sum + Number(row.amount || 0), 0)) }),
                    ]),
                ]);
            },
            onSubmit: async (values) => client.post('/tenant-expense-invoices', {
                lease_id: Number(values.lease_id),
                transaction_date: values.transaction_date,
                reference: values.reference || null,
                lines: values.lines.map((row) => ({ description: row.description, amount: Number(row.amount) })),
            }),
        });

        if (result) {
            await refresh();
            successWithDownload(t('ui.tenants.expense_invoice_created'), result?.invoice?.id ? `/invoices/${result.invoice.id}/pdf` : null, t('ui.tenants.download_invoice'));
        }
    }

    async function adjustmentDrawer() {
        const accounts = fundAccounts({ includeDraft: true });
        const option = (a) => ({ value: String(a.id), label: accountLabel(a) });
        const byId = (id) => accounts.find((a) => String(a.id) === String(id));

        const result = await openSheet({
            title: t('ui.tenants.adjustment_title'),
            description: t('ui.tenants.adjustment_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'warning', type: 'note', tone: 'warning', label: t('ui.tenants.adjustment_warning') },
                { name: 'account_id', type: 'select', label: t('ui.tenants.account'), value: accounts[0] ? String(accounts[0].id) : '', options: accounts.length === 0 ? [{ value: '', label: t('ui.tenants.no_accounts') }] : accounts.map(option), required: true },
                { name: 'preview', type: 'note', label: '' },
                { name: 'corrected_balance', type: 'money', label: t('ui.tenants.correct_balance'), value: accounts[0] ? String(accounts[0].balance ?? 0) : '', required: true },
                { name: 'reason', type: 'textarea', rows: 4, maxlength: 1000, label: t('ui.tenants.reason'), placeholder: t('ui.tenants.adjustment_reason_placeholder'), required: true },
                { name: 'reference', type: 'text', label: `${t('ui.tenants.reference')} ${t('ui.tenants.optional')}`, maxlength: 255 },
            ],
            onChange: (values, api, changed) => {
                if (changed === 'account_id') {
                    api.set('corrected_balance', String(byId(values.account_id)?.balance ?? 0));
                }

                const fresh = api.values();
                const account = byId(fresh.account_id);
                const corrected = Number(fresh.corrected_balance || 0);

                mount(api.get('preview').node, el('div', { class: 'pair-grid pair-grid-3' }, [
                    [t('ui.tenants.current_balance'), money(account?.balance ?? 0)],
                    [t('ui.tenants.correct_balance'), money(corrected)],
                    [t('ui.tenants.calculated_adjustment'), money(corrected - Number(account?.balance ?? 0))],
                ].map(([label, value]) => el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: label }), el('span', { class: 'pair-value', text: value })]))));
            },
            validate: (values) => {
                if (! values.account_id || values.corrected_balance === '' || values.reason === '') {
                    return { _: t('ui.tenants.adjustment_required_fields') };
                }

                if (Number(values.corrected_balance) < 0) {
                    return { corrected_balance: t('ui.tenants.adjustment_negative_balance') };
                }

                if (Number(values.corrected_balance) === Number(byId(values.account_id)?.balance ?? 0)) {
                    return { corrected_balance: t('ui.tenants.adjustment_no_change') };
                }

                return null;
            },
            onSubmit: async (values) => client.post(`/tenant-funds/${values.account_id}/adjustments`, {
                corrected_balance: Number(values.corrected_balance),
                reason: values.reason,
                reference: values.reference || null,
            }),
        });

        if (result) {
            await refresh();
            successWithDownload(t('ui.tenants.adjustment_recorded'), result?.adjustment_voucher?.pdf_endpoint ? String(result.adjustment_voucher.pdf_endpoint).replace(/^\/api(\/v\d+)?/, '') : null);
        }
    }

    async function payInvoiceDrawer(invoice, lease) {
        const accounts = (lease?.tenant_fund_accounts ?? []).filter((a) => a.status === 'active' && Number(a.balance ?? 0) > 0).map((a) => ({ ...a, lease }));
        const allowed = invoice.type === 'expense'
            ? ['rent_reserve', 'consumable_advance', 'security_deposit']
            : (lease?.status === 'notice' ? ['consumable_advance', 'rent_reserve'] : ['consumable_advance']);
        const eligible = accounts.filter((a) => allowed.includes(a.type));
        const outstanding = Number(invoice.outstanding ?? invoice.outstanding_amount ?? 0);
        const byId = (id) => eligible.find((a) => String(a.id) === String(id));

        const result = await openSheet({
            title: t('ui.tenants.pay_invoice_title'),
            description: t('ui.tenants.pay_invoice_description'),
            submitLabel: t('ui.tenants.review'),
            fields: [
                { name: 'ctx', type: 'note', label: joinParts([`${t('ui.tenants.invoice')}: ${invoice.invoice_number ?? `#${invoice.id}`}`, leaseLabel(lease ?? {}), `${t('ui.tenants.outstanding')}: ${money(outstanding)}`]) },
                { name: 'account_id', type: 'select', label: t('ui.tenants.account'), value: eligible[0] ? String(eligible[0].id) : '', options: eligible.length === 0 ? [{ value: '', label: t('ui.tenants.no_withdrawable_funds') }] : eligible.map((a) => ({ value: String(a.id), label: accountLabel(a) })), required: true },
                { name: 'amount', type: 'money', label: t('ui.tenants.amount'), value: String(outstanding), required: true },
                { name: 'transaction_date', type: 'date', label: t('ui.tenants.transaction_date'), value: today(), required: true },
            ],
            validate: (values) => {
                if (! values.account_id || ! (Number(values.amount) > 0) || ! values.transaction_date) {
                    return { _: t('ui.tenants.pay_fields_required') };
                }

                if (Number(values.amount) > Number(byId(values.account_id)?.balance ?? 0)) {
                    return { amount: t('ui.tenants.pay_exceeds_balance') };
                }

                if (Number(values.amount) > outstanding) {
                    return { amount: t('ui.tenants.pay_exceeds_invoice') };
                }

                return null;
            },
            review: (values) => el('div', { class: 'stack' }, [
                el('h3', { class: 'sheet-heading-title', text: t('ui.tenants.pay_review_title') }),
                el('p', { class: 'muted-small', text: t('ui.tenants.pay_review_description') }),
                el('dl', { class: 'dl' }, [
                    el('dt', { text: t('ui.tenants.invoice') }), el('dd', { text: invoice.invoice_number ?? `#${invoice.id}` }),
                    el('dt', { text: t('ui.tenants.account') }), el('dd', { text: accountLabel(byId(values.account_id) ?? { lease }) }),
                    el('dt', { text: t('ui.tenants.amount') }), el('dd', { text: money(values.amount) }),
                    el('dt', { text: t('ui.tenants.transaction_date') }), el('dd', { text: formatDate(values.transaction_date) }),
                ]),
            ]),
            onSubmit: async (values) => client.post(`/invoices/${invoice.id}/account-payments`, {
                tenant_fund_account_id: Number(values.account_id),
                amount: Number(values.amount),
                transaction_date: values.transaction_date,
            }),
        });

        if (result) {
            await refresh();
            successWithDownload(t('ui.tenants.payment_recorded'), result?.invoice?.status === 'paid' ? `/invoices/${invoice.id}/payment-receipt` : null);
        }
    }

    async function cancelPaymentDrawer(invoice) {
        const payment = (invoice.account_payments ?? []).filter((p) => p.cancellable === true).pop();

        if (! payment) {
            return;
        }

        const done = await openSheet({
            title: t('ui.tenants.cancel_payment_title'),
            description: t('ui.tenants.cancel_payment_description'),
            submitLabel: t('ui.tenants.cancel_payment'),
            submitKind: 'danger',
            fields: [
                { name: 'ctx', type: 'note', label: joinParts([invoice.invoice_number ?? `#${invoice.id}`, `${money(payment.amount)} · ${formatDate(payment.transaction_date)}`]) },
                { name: 'reason', type: 'textarea', rows: 3, maxlength: 500, label: t('ui.tenants.cancellation_reason'), required: true },
            ],
            validate: (values) => (values.reason === '' ? { reason: t('ui.tenants.cancellation_reason_required') } : null),
            onSubmit: async (values) => client.post(`/invoice-account-payments/${payment.id}/cancel`, { reason: values.reason }),
        });

        if (done) {
            await refresh();
            showSuccess(errors, t('ui.tenants.payment_cancelled'));
        }
    }

    /* -------------------------------------------------------- panels */

    function invoiceTable(key, invoices) {
        const leaseOf = (invoice) => (current?.leases ?? []).find((l) => String(l.id) === String(invoice.lease_id)) ?? (current?.leases ?? []).find((l) => (l.invoices ?? []).some((i) => i.id === invoice.id));

        return pagedTable(key, [
            { label: t('ui.tenants.invoice'), cell: (inv) => el('span', { class: 'cell-strong', text: inv.invoice_number ?? `#${inv.id}` }) },
            { label: t('ui.tenants.type'), cell: (inv) => domain('tenants.invoice_type', inv.type) },
            { label: t('ui.tenants.due_date'), cell: (inv) => formatDate(inv.due_date) },
            { label: t('ui.tenants.amount'), align: 'right', cell: (inv) => money(inv.amount ?? inv.total_amount ?? 0) },
            { label: t('ui.tenants.paid'), align: 'right', cell: (inv) => money(inv.paid ?? inv.paid_amount ?? 0) },
            { label: t('ui.tenants.outstanding'), align: 'right', cell: (inv) => money(inv.outstanding ?? inv.outstanding_amount ?? 0) },
            { label: t('ui.tenants.status'), cell: (inv) => badge(capitalise(inv.status), inv.status) },
            { label: t('ui.tenants.actions'), align: 'right', cell: (inv) => {
                const lease = leaseOf(inv);
                const outstanding = Number(inv.outstanding ?? inv.outstanding_amount ?? 0);

                return el('span', { class: 'cell-actions' }, [
                    can('manage_finance') && ['rent', 'expense'].includes(inv.type) && inv.status !== 'cancelled' && outstanding > 0 ? button(t('ui.tenants.pay'), { kind: 'primary', onClick: () => payInvoiceDrawer(inv, lease) }) : null,
                    can('manage_finance') && (inv.account_payments ?? []).some((p) => p.cancellable === true) ? button(t('ui.tenants.cancel_payment'), { kind: 'danger-outline', onClick: () => cancelPaymentDrawer(inv) }) : null,
                    pdfButton(client, `/invoices/${inv.id}/pdf`, t('ui.tenants.invoice'), { onFail: fail(t('ui.tenants.unable_to_open_invoice')) }),
                    inv.status === 'paid' && (inv.account_payments ?? []).length > 0 ? pdfButton(client, `/invoices/${inv.id}/payment-receipt`, t('ui.tenants.receipt'), { onFail: fail(t('ui.tenants.unable_to_open_receipt')) }) : null,
                    can('manage_operations') ? resendButton(client, `/invoices/${inv.id}/send-email`, { label: t('ui.tenants.resend'), sent: t('ui.tenants.sent'), onFail: fail(t('ui.tenants.unable_to_resend_invoice')) }) : null,
                ].filter(Boolean));
            } },
        ], invoices, { empty: key === 'tenant-expenses' ? t('ui.tenants.no_expense_invoices') : t('ui.tenants.no_invoices') === 'ui.tenants.no_invoices' ? t('list.empty') : t('ui.tenants.no_invoices') });
    }

    function paymentsTable() {
        const statementPayments = current?.statement?.payments ?? [];
        const persisted = (current?.leases ?? []).flatMap((l) => l.payments ?? []);
        const keyOf = (date, amount, method, reference) => `${String(date ?? '').slice(0, 10)}|${Number(amount ?? 0)}|${method ?? ''}|${reference ?? ''}`;
        const buckets = new Map();

        for (const p of persisted) {
            const k = keyOf(p.payment_date, p.amount, p.payment_method, p.reference);

            buckets.set(k, [...(buckets.get(k) ?? []), p]);
        }

        return pagedTable('tenant-payments', [
            { label: t('ui.tenants.date'), cell: (p) => formatDate(p.date) },
            { label: t('ui.tenants.amount'), align: 'right', cell: (p) => el('span', { class: 'cell-strong', text: money(p.amount ?? 0) }) },
            { label: t('ui.tenants.method'), cell: (p) => paymentMethodLabel(p.method) },
            { label: t('ui.tenants.reference'), cell: (p) => dash(p.reference) },
            { label: t('ui.tenants.allocated'), align: 'right', cell: (p) => money(p.allocated ?? 0) },
            { label: t('ui.tenants.unallocated'), align: 'right', cell: (p) => money(p.unallocated ?? 0) },
            { label: t('ui.tenants.receipt'), align: 'right', cell: (p) => {
                const matches = buckets.get(keyOf(p.date, p.amount, p.method, p.reference)) ?? [];

                if (matches.length !== 1) {
                    return '—';
                }

                return receiptActions(`/payments/${matches[0].id}/receipt`, `/payments/${matches[0].id}/send-receipt`);
            } },
        ], statementPayments, { empty: t('ui.tenants.no_payments') });
    }

    function transfersTable() {
        const ledger = fundTransactions();
        const debits = ledger.filter((tx) => tx.category === 'transfer' && tx.direction === 'debit');

        return pagedTable('tenant-transfers', [
            { label: t('ui.tenants.date'), cell: (d) => formatDate(d.transaction_date) },
            { label: t('ui.tenants.voucher'), cell: (d) => dash(d.reference) },
            { label: t('ui.tenants.from_fund'), cell: (d) => fundTypeLabel(d.fund_type) },
            { label: t('ui.tenants.to_fund'), cell: (d) => {
                const credit = ledger.find((tx) => tx.category === 'transfer' && tx.direction === 'credit' && tx.reference === d.reference && tx.id !== d.id);

                return credit ? fundTypeLabel(credit.fund_type) : '—';
            } },
            { label: t('ui.tenants.amount'), align: 'right', cell: (d) => el('span', { class: 'cell-strong', text: money(d.amount ?? 0) }) },
            { label: t('ui.tenants.actions'), align: 'right', cell: (d) => el('span', { class: 'cell-actions' }, [
                pdfButton(client, `/tenant-fund-transfers/${d.id}/voucher`, t('ui.tenants.voucher'), { onFail: fail(t('ui.tenants.unable_to_open_voucher')) }),
                can('manage_operations') ? resendButton(client, `/tenant-fund-transfers/${d.id}/send-email`, { label: t('ui.tenants.resend'), sent: t('ui.tenants.sent'), onFail: fail(t('ui.tenants.unable_to_resend_voucher')) }) : null,
            ].filter(Boolean)) },
        ], debits, { empty: t('ui.tenants.no_transfers') });
    }

    function fundHistoryTable() {
        return pagedTable('tenant-funds', [
            { label: t('ui.tenants.date'), cell: (tx) => formatDate(tx.transaction_date) },
            { label: t('ui.tenants.fund'), cell: (tx) => el('span', { class: 'cell-strong', text: fundTypeLabel(tx.fund_type) }) },
            { label: t('ui.tenants.direction'), cell: (tx) => domain('tenants.direction', tx.direction) },
            { label: t('ui.tenants.category'), cell: (tx) => domain('tenants.category', tx.category) },
            { label: t('ui.tenants.amount'), align: 'right', cell: (tx) => el('span', { class: 'cell-strong', text: money(tx.amount ?? 0) }) },
            { label: t('ui.tenants.reference'), cell: (tx) => dash(tx.reference) },
            { label: t('ui.tenants.source'), cell: (tx) => (tx.payment_id ? t('ui.tenants.payment_number', { number: tx.payment_id }) : tx.invoice_id ? t('ui.tenants.invoice_number', { number: tx.invoice_id }) : t('ui.tenants.ledger')) },
        ], fundTransactions(), { empty: t('ui.tenants.no_fund_transactions') });
    }

    function paintDetail() {
        const party = current.party;
        const invoices = current.statement?.invoices ?? [];

        mount(detail,
            el('div', { class: 'screen-head' }, [
                el('div', {}, [
                    el('h2', { class: 'card-title', text: partyName(party) }),
                    el('p', { class: 'screen-sub', text: partyContact(party) || t('ui.tenants.no_contact_information') }),
                ]),
                can('manage_finance') ? el('div', { class: 'screen-actions' }, [
                    button(t('ui.tenants.accounts'), { iconName: 'wallet', onClick: accountsDrawer }),
                    button(t('ui.tenants.deposit'), { kind: 'primary', iconName: 'coins-stacked', onClick: depositDrawer }),
                    button(t('ui.tenants.withdrawal'), { iconName: 'trend-down', onClick: withdrawalDrawer }),
                    button(t('ui.tenants.expense'), { iconName: 'file-05', onClick: expenseDrawer }),
                    button(t('ui.tenants.adjustment'), { iconName: 'scale-balanced', onClick: adjustmentDrawer }),
                ]) : el('div', { class: 'screen-actions' }, [button(t('ui.tenants.accounts'), { iconName: 'wallet', onClick: accountsDrawer })]),
            ]),
            section(t('ui.tenants.leases'), pagedTable('tenant-leases', [
                { label: t('ui.tenants.property'), cell: (l) => l.unit?.building?.name ?? t('ui.tenants.building') },
                { label: t('ui.tenants.unit'), cell: (l) => dash(l.unit?.name) },
                { label: t('ui.tenants.period'), cell: (l) => (l.start_date && l.end_date ? `${formatDate(l.start_date)} → ${formatDate(l.end_date)}` : l.start_date ? t('ui.tenants.lease_ongoing', { start: formatDate(l.start_date) }) : t('ui.tenants.lease_dates_unavailable')) },
                { label: t('ui.tenants.rent'), align: 'right', cell: (l) => money(l.rent_amount ?? 0) },
                { label: t('ui.tenants.status'), cell: (l) => badge(leaseStatusLabel(l.status), l.status) },
            ], current.leases, { empty: t('ui.tenants.no_leases') }), { sub: t('ui.tenants.leases_description') }),
            section(t('ui.tenants.invoices'), invoiceTable('tenant-invoices', invoices.filter((inv) => inv.type !== 'expense')), { sub: t('ui.tenants.invoices_description') }),
            section(t('ui.tenants.payments'), paymentsTable(), { sub: t('ui.tenants.payments_description') }),
            section(t('ui.tenants.transfers'), transfersTable(), { sub: t('ui.tenants.transfers_description') }),
            section(t('ui.tenants.expenses'), invoiceTable('tenant-expenses', invoices.filter((inv) => inv.type === 'expense')), { sub: t('ui.tenants.expense_invoices_description') }),
            section(t('ui.tenants.fund_history'), fundHistoryTable(), { sub: t('ui.tenants.fund_history_description') })
        );
    }

    mount(host,
        screenHead({ eyebrow: t('ui.tenants.finance'), title: t('ui.tenants.heading'), sub: t('ui.tenants.page_description') }),
        errors,
        el('div', { class: 'split' }, [
            el('div', { class: 'card' }, [
                el('header', { class: 'card-head' }, [el('div', { class: 'card-words' }, [el('h2', { class: 'card-title', text: t('ui.tenants.directory') }), el('p', { class: 'card-sub', text: t('ui.tenants.search_description') })])]),
                el('div', { class: 'card-body' }, [searchBox.node, directory]),
            ]),
            el('div', {}, [detail]),
        ])
    );

    mount(detail, emptyState('users-01', t('detail.none')));
    reloadDirectory(1);

    return { node: host, reload: () => (selectedId !== null ? select(selectedId) : reloadDirectory(1)), open: (id) => select(id) };
}
