/*
 * Accounting - management fee income and the VAT charged on it, for a
 * period. Administrator only (view_financial_journal), as on the web.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { money } from '../ui/money.js';
import { table, stat, loading, button } from '../ui/table.js';
import { formatDate, joinParts } from '../ui/format.js';
import { screenHead, bannerHost, showError, dateControl, query, dash } from './common.js';

const TRANSACTION_LIMIT = 200;

export function accountingScreen(client) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const body = el('div');
    let from = '';
    let to = '';

    const fromInput = dateControl(from, (value) => { from = value; });
    const toInput = dateControl(to, (value) => { to = value; });

    const period = el('div', { class: 'card' }, [
        el('div', { class: 'card-body' }, [
            el('div', { class: 'inline' }, [
                el('span', { class: 'label', text: t('ui.accounting.period') }),
                el('div', { class: 'inline grow' }, [
                    el('div', { class: 'grow' }, [fromInput]),
                    el('span', { text: '–' }),
                    el('div', { class: 'grow' }, [toInput]),
                ]),
                button(t('ui.accounting.apply'), { kind: 'primary', onClick: () => load() }),
                button(t('ui.accounting.reset'), {
                    onClick: () => {
                        from = ''; to = '';
                        fromInput.value = ''; toInput.value = '';
                        load();
                    },
                }),
            ]),
        ]),
    ]);

    fromInput.setAttribute('aria-label', t('ui.accounting.from'));
    toInput.setAttribute('aria-label', t('ui.accounting.to'));

    async function load() {
        mount(body, loading());

        try {
            const payload = await client.get(`${endpoints.accountingSummary}${query({ from, to })}`);
            const totals = payload?.totals ?? {};
            const transactions = Array.isArray(payload?.transactions) ? payload.transactions : [];

            mount(body,
                el('div', { class: 'kpis' }, [
                    stat(t('ui.accounting.fee_income'), money(totals.management_fee ?? 0), { sub: t('ui.accounting.fee_income_hint') }),
                    stat(t('ui.accounting.vat_charged'), money(totals.management_fee_vat ?? 0), { sub: t('ui.accounting.vat_charged_hint') }),
                    stat(t('ui.accounting.charged_to_owners'), money(totals.charged_to_owners ?? 0), { sub: t('ui.accounting.charged_to_owners_hint') }),
                ]),
                el('p', { class: 'muted-small', text: t('ui.accounting.vat_note') }),
                el('section', { class: 'card' }, [
                    el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('ui.accounting.transactions') })]),
                    el('div', { class: 'card-body' }, [
                        table([
                            { label: t('ui.accounting.date'), cell: (row) => formatDate(row.transaction_date) },
                            { label: t('ui.accounting.type'), cell: (row) => t(`ui.accounting.${row.category}`) },
                            { label: t('ui.accounting.owner'), cell: (row) => el('span', { class: 'cell-strong', text: dash(row.owner_name) }) },
                            { label: t('ui.accounting.property'), cell: (row) => dash(joinParts([row.building_name, row.unit_name], ' — ')) },
                            { label: t('ui.accounting.reference'), cell: (row) => dash(row.reference) },
                            { label: t('ui.accounting.amount'), align: 'right', cell: (row) => money(row.amount) },
                        ], transactions, { empty: t('ui.accounting.empty') }),
                        transactions.length === TRANSACTION_LIMIT ? el('p', { class: 'muted-small', text: t('ui.accounting.capped') }) : null,
                    ]),
                ])
            );
        } catch (failure) {
            mount(body);
            showError(errors, failure);
        }
    }

    mount(host,
        screenHead({ eyebrow: t('ui.navigation.finance'), title: t('ui.accounting.title'), sub: t('ui.accounting.subtitle') }),
        errors,
        period,
        body
    );

    load();

    return { node: host, reload: load };
}
