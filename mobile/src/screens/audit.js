/*
 * Audit - the Activity monitor and the Financial journal, two tabs on one
 * screen as the browser application has them since v1.0.38.
 *
 * Both are read-only and administrator-only. Neither offers a PDF: the
 * server has no such route (dompdf could not hold either document), so
 * XLSX and CSV are the only exports, and they carry the filters without
 * the pagination - every matching record, not the visible page.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { money } from '../ui/money.js';
import { pagination, pageSize, loading, emptyState, badge, button } from '../ui/table.js';
import { formatDate, formatDateTime, domain, prettify, joinParts } from '../ui/format.js';
import { openSheet } from '../ui/sheet.js';
import {
    screenHead, bannerHost, showError, rows, pageMeta, tabs, filterField, selectControl,
    textControl, dateControl, query, fileButton, roleLabel, dl,
} from './common.js';

function actionLabel(action) {
    const key = `activity_log.actions.${action}`;
    const found = t(key);

    return found === key ? prettify(action) : found;
}

function entityLabel(type) {
    const key = `activity_log.entities.${type}`;
    const found = t(key);

    return found === key ? prettify(type) : found;
}

function metadataKey(key) {
    const known = t(`activity_log.metadata_labels.${key}`);

    return known === `activity_log.metadata_labels.${key}` ? prettify(key) : known;
}

function structured(title, value) {
    if (value === null || value === undefined || (typeof value === 'object' && Object.keys(value).length === 0)) {
        return null;
    }

    const entries = typeof value === 'object' && ! Array.isArray(value)
        ? Object.entries(value)
        : [['', value]];

    return el('section', { class: 'card' }, [
        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: title })]),
        el('div', { class: 'card-body' }, [
            el('dl', { class: 'dl' }, entries.flatMap(([key, v]) => [
                el('dt', { text: key === '' ? '' : metadataKey(key) }),
                el('dd', {}, [typeof v === 'object' && v !== null
                    ? el('pre', { class: 'json', text: JSON.stringify(v, null, 2) })
                    : el('span', { text: v === null || v === '' ? t('ui.activity_log.not_available') : String(v) })]),
            ])),
        ]),
    ]);
}

/* -------------------------------------------------------------- activity */

function activityTab(client, errors) {
    const host = el('div');
    const list = el('div');
    const size = pageSize('activity-log');
    const filters = { search: '', from: '', to: '', user_id: '', role: '', action: '', entity_type: '' };
    let page = 1;
    let users = [];

    const controls = el('div', { class: 'filters' });

    function paintControls() {
        mount(controls,
            filterField(t('ui.activity_log.search'), textControl({ value: filters.search, placeholder: t('ui.activity_log.search_placeholder'), type: 'search', maxlength: 255, onInput: (v) => { filters.search = v; reload(1); } }), { span: true }),
            filterField(t('ui.activity_log.from'), dateControl(filters.from, (v) => { filters.from = v; reload(1); })),
            filterField(t('ui.activity_log.to'), dateControl(filters.to, (v) => { filters.to = v; reload(1); })),
            filterField(t('ui.activity_log.user'), selectControl([
                { value: '', label: t('ui.activity_log.all_users') },
                ...users.map((user) => ({ value: user.id, label: user.name ?? user.email })),
            ], filters.user_id, (v) => { filters.user_id = v; reload(1); })),
            filterField(t('ui.activity_log.role'), selectControl([
                { value: '', label: t('ui.activity_log.all_roles') },
                { value: 'administrator', label: roleLabel('administrator') },
                { value: 'property_manager', label: roleLabel('property_manager') },
                { value: 'viewer', label: roleLabel('viewer') },
            ], filters.role, (v) => { filters.role = v; reload(1); })),
            filterField(t('ui.activity_log.action'), textControl({ value: filters.action, placeholder: t('ui.activity_log.action_placeholder'), maxlength: 100, onInput: (v) => { filters.action = v; reload(1); } })),
            filterField(t('ui.activity_log.entity_type'), textControl({ value: filters.entity_type, placeholder: t('ui.activity_log.entity_type_placeholder'), maxlength: 100, onInput: (v) => { filters.entity_type = v; reload(1); } })),
            el('div', { class: 'field' }, [
                el('span', { class: 'label', text: ' ' }),
                button(t('ui.activity_log.clear_filters'), {
                    onClick: () => {
                        for (const key of Object.keys(filters)) { filters[key] = ''; }
                        paintControls();
                        reload(1);
                    },
                }),
            ])
        );
    }

    async function openDetail(id) {
        let entry = null;

        try {
            entry = await client.get(`${endpoints.activityLog}/${id}`);
        } catch (failure) {
            showError(errors, failure, t('ui.activity_log.unable_load_detail'));

            return;
        }

        const na = t('ui.activity_log.not_available');
        const fact = (v) => (v === null || v === undefined || v === '' ? na : String(v));

        await openSheet({
            title: t('ui.activity_log.detail_heading'),
            description: t('ui.activity_log.detail_description'),
            width: 'lg',
            submitLabel: t('ui.activity_log.close'),
            fields: [{ name: 'body', type: 'note', label: '' }],
            onSubmit: async () => true,
            onChange: (values, api) => {
                const note = api.get('body').node;

                mount(note,
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('ui.activity_log.event') })]),
                        el('div', { class: 'card-body' }, [dl([
                            [t('ui.activity_log.timestamp'), formatDateTime(entry.created_at)],
                            [t('ui.activity_log.action'), actionLabel(entry.action)],
                            [t('ui.activity_log.actor'), fact(entry.actor_name)],
                            [t('ui.activity_log.email'), fact(entry.actor_email)],
                            [t('ui.activity_log.role'), entry.actor_role ? roleLabel(entry.actor_role) : na],
                            [t('ui.activity_log.ip_address'), fact(entry.ip_address)],
                            [t('ui.activity_log.browser'), fact(entry.browser)],
                            [t('ui.activity_log.platform'), fact(entry.platform)],
                            [t('ui.activity_log.device'), fact(entry.device)],
                            [t('ui.activity_log.user_agent'), fact(entry.user_agent)],
                            [t('ui.activity_log.entity_type'), entry.entity_type ? entityLabel(entry.entity_type) : na],
                            [t('ui.activity_log.entity'), entry.entity_label ?? (entry.entity_type ? `${entityLabel(entry.entity_type)} #${entry.entity_id ?? ''}` : na)],
                        ])]),
                    ]),
                    structured(t('ui.activity_log.before_values'), entry.before),
                    structured(t('ui.activity_log.after_values'), entry.after),
                    structured(t('ui.activity_log.snapshot'), entry.snapshot),
                    structured(t('ui.activity_log.metadata'), entry.metadata)
                );
            },
        });
    }

    function row(entry) {
        return el('div', { class: 'record-card' }, [
            el('div', { class: 'record-card-head' }, [
                badge(actionLabel(entry.action), 'info'),
                entry.actor_role ? badge(roleLabel(entry.actor_role), 'neutral') : null,
                el('span', { class: 'cell-strong', text: entry.actor_name || entry.actor_email || t('ui.activity_log.unknown_actor') }),
            ]),
            el('p', { class: 'record-card-sub', text: joinParts([
                formatDateTime(entry.created_at),
                entry.entity_label ?? (entry.entity_type ? `${entityLabel(entry.entity_type)} #${entry.entity_id ?? ''}` : null),
                entry.ip_address,
                joinParts([entry.browser, entry.platform, entry.device]),
            ]) }),
            el('div', { class: 'record-card-actions' }, [
                button(t('ui.activity_log.view_details'), { onClick: () => openDetail(entry.id) }),
            ]),
        ]);
    }

    async function reload(next = page) {
        page = next;
        mount(list, loading(t('ui.activity_log.loading')));

        try {
            const payload = await client.get(`${endpoints.activityLog}${query({ ...filters, page, per_page: size.get() })}`);
            const found = rows(payload);

            mount(list,
                found.length === 0
                    ? emptyState('clock-rewind', t('ui.activity_log.none_found'), t('ui.activity_log.none_found_description'))
                    : el('div', {}, found.map(row)),
                pagination(pageMeta(payload, size.get()), size, reload)
            );
        } catch (failure) {
            mount(list);
            showError(errors, failure, t('ui.activity_log.unable_load'));
        }
    }

    const exportsBar = el('div', { class: 'screen-actions' }, [
        fileButton(client, () => `${endpoints.activityLog}/xlsx${query(filters)}`, t('ui.activity_log.export_xlsx'), 'activity-log.xlsx', { busyLabel: t('ui.activity_log.exporting'), onFail: (f) => showError(errors, f, t('ui.activity_log.unable_export')) }),
        fileButton(client, () => `${endpoints.activityLog}/csv${query(filters)}`, t('ui.activity_log.export_csv'), 'activity-log.csv', { busyLabel: t('ui.activity_log.exporting'), onFail: (f) => showError(errors, f, t('ui.activity_log.unable_export')) }),
    ]);

    mount(host, exportsBar, controls, list);
    paintControls();

    client.get(`${endpoints.users}?per_page=100`).then((payload) => {
        users = rows(payload);
        paintControls();
    }).catch(() => {});

    reload(1);

    return host;
}

/* --------------------------------------------------------------- journal */

function journalTab(client, errors) {
    const host = el('div');
    const list = el('div');
    const size = pageSize('financial-journal');
    const filters = { search: '', from: '', to: '', entry_kind: '', transaction_type: '', account_id: '' };
    let page = 1;
    let options = { transaction_types: [], accounts: [] };

    const controls = el('div', { class: 'filters' });

    const typeLabel = (value) => domain('financial_journal.transaction_types', value);
    const kindLabel = (value) => t(`ui.financial_journal.kind_${value}`);

    function paintControls() {
        mount(controls,
            filterField(t('ui.financial_journal.search'), textControl({ value: filters.search, placeholder: t('ui.financial_journal.search_placeholder'), type: 'search', maxlength: 255, onInput: (v) => { filters.search = v; reload(1); } }), { span: true }),
            filterField(t('ui.financial_journal.from'), dateControl(filters.from, (v) => { filters.from = v; reload(1); })),
            filterField(t('ui.financial_journal.to'), dateControl(filters.to, (v) => { filters.to = v; reload(1); })),
            filterField(t('ui.financial_journal.entry_kind'), selectControl([
                { value: '', label: t('ui.financial_journal.all_entry_kinds') },
                ...['financial', 'reversal', 'informational'].map((kind) => ({ value: kind, label: kindLabel(kind) })),
            ], filters.entry_kind, (v) => { filters.entry_kind = v; reload(1); })),
            filterField(t('ui.financial_journal.transaction_type'), selectControl([
                { value: '', label: t('ui.financial_journal.all_transaction_types') },
                ...options.transaction_types.map((type) => ({ value: type, label: typeLabel(type) })),
            ], filters.transaction_type, (v) => { filters.transaction_type = v; reload(1); })),
            filterField(t('ui.financial_journal.account'), selectControl([
                { value: '', label: t('ui.financial_journal.all_accounts') },
                ...options.accounts.map((account) => ({ value: account.id, label: `${account.code} — ${account.name}` })),
            ], filters.account_id, (v) => { filters.account_id = v; reload(1); })),
            el('div', { class: 'field' }, [
                el('span', { class: 'label', text: ' ' }),
                button(t('ui.financial_journal.clear_filters'), {
                    onClick: () => {
                        for (const key of Object.keys(filters)) { filters[key] = ''; }
                        paintControls();
                        reload(1);
                    },
                }),
            ])
        );
    }

    async function openDetail(id) {
        let entry = null;

        try {
            entry = await client.get(`${endpoints.financialJournal}/${id}`);
        } catch (failure) {
            showError(errors, failure, t('ui.financial_journal.unable_load_detail'));

            return;
        }

        const na = t('ui.financial_journal.not_available');
        const lines = Array.isArray(entry.lines) ? entry.lines : [];
        const source = entry.source_type ? `${String(entry.source_type).split('\\').pop()} #${entry.source_id ?? ''}` : na;

        await openSheet({
            title: t('ui.financial_journal.detail_heading'),
            description: t('ui.financial_journal.detail_description'),
            width: 'lg',
            submitLabel: t('common.close'),
            fields: [{ name: 'body', type: 'note', label: '' }],
            onSubmit: async () => true,
            onChange: (values, api) => {
                mount(api.get('body').node,
                    el('div', { class: 'dl-block' }, [
                        el('div', { class: 'inline' }, [
                            badge(entry.journal_number ?? `#${entry.id}`, 'mono'),
                            badge(kindLabel(entry.entry_kind), entry.entry_kind === 'reversal' ? 'warning' : 'neutral'),
                            entry.is_reversed ? badge(t('ui.financial_journal.reversed'), 'danger') : null,
                        ]),
                        el('p', { class: 'muted-small', text: joinParts([formatDate(entry.journal_date), formatDateTime(entry.posted_at)]) }),
                    ]),
                    dl([
                        [t('ui.financial_journal.transaction_type'), typeLabel(entry.transaction_type)],
                        [t('ui.financial_journal.actor'), entry.actor_name_snapshot ?? na],
                        [t('ui.financial_journal.source'), source],
                        [t('ui.financial_journal.balance_status'), entry.is_balanced === false ? t('ui.financial_journal.unbalanced') : t('ui.financial_journal.balanced')],
                    ]),
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('ui.financial_journal.description_label') })]),
                        el('div', { class: 'card-body' }, [el('p', { class: 'record-card-sub', text: entry.description ?? na })]),
                    ]),
                    (entry.reversal_of || entry.reversed_by) ? el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [el('h2', { class: 'card-title', text: t('ui.financial_journal.reversal_context') })]),
                        el('div', { class: 'card-body' }, [dl([
                            entry.reversal_of ? [t('ui.financial_journal.reversal_of'), entry.reversal_of.journal_number] : null,
                            entry.reversed_by ? [t('ui.financial_journal.reversed_by'), entry.reversed_by.journal_number] : null,
                            entry.reversed_by?.reversal_reason ? [t('ui.financial_journal.reversal_reason'), entry.reversed_by.reversal_reason] : null,
                        ])]),
                    ]) : null,
                    el('section', { class: 'card' }, [
                        el('header', { class: 'card-head' }, [
                            el('h2', { class: 'card-title', text: t('ui.financial_journal.accounting_lines') }),
                            el('span', { class: 'muted-small', text: t('ui.financial_journal.line_count', { count: lines.length }) }),
                        ]),
                        el('div', { class: 'card-body' }, [
                            lines.length === 0
                                ? el('p', { class: 'record-card-sub', text: t('ui.financial_journal.no_lines') })
                                : el('div', { class: 'stack' }, lines.map((line) => el('div', { class: 'dl-block' }, [
                                    el('div', { class: 'inline' }, [
                                        badge(line.account_code_snapshot ?? '', 'mono'),
                                        el('span', { class: 'cell-strong', text: line.account_name_snapshot ?? '' }),
                                    ]),
                                    line.memo ? el('p', { class: 'muted-small', text: line.memo }) : null,
                                    el('div', { class: 'inline' }, [
                                        el('span', { class: 'muted-small', text: `${t('ui.financial_journal.debit')}: ${money(line.debit ?? line.debit_amount ?? 0)}` }),
                                        el('span', { class: 'muted-small', text: `${t('ui.financial_journal.credit')}: ${money(line.credit ?? line.credit_amount ?? 0)}` }),
                                    ]),
                                ]))),
                            el('div', { class: 'pair-grid pair-grid-2' }, [
                                el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: t('ui.financial_journal.total_debit') }), el('span', { class: 'pair-value', text: money(entry.debit_total ?? 0) })]),
                                el('div', { class: 'pair' }, [el('span', { class: 'pair-label', text: t('ui.financial_journal.total_credit') }), el('span', { class: 'pair-value', text: money(entry.credit_total ?? 0) })]),
                            ]),
                        ]),
                    ])
                );
            },
        });
    }

    function row(entry) {
        return el('div', { class: 'record-card' }, [
            el('div', { class: 'record-card-head' }, [
                badge(entry.journal_number ?? `#${entry.id}`, 'mono'),
                badge(kindLabel(entry.entry_kind), entry.entry_kind === 'reversal' ? 'warning' : 'neutral'),
                entry.is_reversed ? badge(t('ui.financial_journal.reversed'), 'danger') : null,
                el('span', { class: 'cell-strong', text: entry.description || typeLabel(entry.transaction_type) }),
            ]),
            el('p', { class: 'record-card-sub', text: joinParts([formatDate(entry.journal_date), typeLabel(entry.transaction_type), entry.actor_name_snapshot]) }),
            el('div', { class: 'record-card-actions' }, [
                el('span', { class: 'muted-small', text: `${t('ui.financial_journal.debit')} ${money(entry.debit_total ?? 0)}` }),
                el('span', { class: 'muted-small', text: `${t('ui.financial_journal.credit')} ${money(entry.credit_total ?? 0)}` }),
                button(t('ui.financial_journal.view_details'), { onClick: () => openDetail(entry.id) }),
            ]),
        ]);
    }

    async function reload(next = page) {
        page = next;
        mount(list, loading(t('ui.financial_journal.loading')));

        try {
            const payload = await client.get(`${endpoints.financialJournal}${query({ ...filters, page, per_page: size.get() })}`);
            const found = rows(payload);

            mount(list,
                found.length === 0
                    ? emptyState('scale-balanced', t('ui.financial_journal.none_found'), t('ui.financial_journal.none_found_description'))
                    : el('div', {}, found.map(row)),
                pagination(pageMeta(payload, size.get()), size, reload)
            );
        } catch (failure) {
            mount(list);
            showError(errors, failure, t('ui.financial_journal.unable_load'));
        }
    }

    const exportsBar = el('div', { class: 'screen-actions' }, [
        fileButton(client, () => `${endpoints.financialJournal}/xlsx${query(filters)}`, t('ui.financial_journal.export_xlsx'), 'financial-journal.xlsx', { busyLabel: t('ui.financial_journal.exporting'), onFail: (f) => showError(errors, f, t('ui.financial_journal.unable_export')) }),
        fileButton(client, () => `${endpoints.financialJournal}/csv${query(filters)}`, t('ui.financial_journal.export_csv'), 'financial-journal.csv', { busyLabel: t('ui.financial_journal.exporting'), onFail: (f) => showError(errors, f, t('ui.financial_journal.unable_export')) }),
    ]);

    mount(host, exportsBar, controls, list);
    paintControls();

    client.get(`${endpoints.financialJournal}/filter-options`).then((payload) => {
        options = { transaction_types: payload?.transaction_types ?? [], accounts: payload?.accounts ?? [] };
        paintControls();
    }).catch(() => {});

    reload(1);

    return host;
}

/* ---------------------------------------------------------------- screen */

export function auditScreen(client) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const body = el('div');
    let active = 'activity';

    function paint() {
        mount(body,
            tabs([
                { id: 'activity', label: t('ui.audit.tab_activity') },
                { id: 'journal', label: t('ui.audit.tab_journal') },
            ], active, (id) => { active = id; paint(); }),
            active === 'activity' ? activityTab(client, errors) : journalTab(client, errors)
        );
    }

    mount(host,
        screenHead({ eyebrow: t('ui.navigation.manage'), title: t('ui.audit.heading'), sub: t('ui.audit.description') }),
        errors,
        body
    );

    paint();

    return { node: host, reload: paint };
}
