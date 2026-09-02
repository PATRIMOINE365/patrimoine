/*
 * Archive - the records Patrimoine will not delete, put out of the way.
 *
 * GET /archive returns everything at once, so the search and the kind
 * chips filter what is already held, exactly as the browser page does.
 * Restore is the only action, and only an administrator sees it.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { restoreRecord } from '../ui/confirm.js';
import { badge, button, loading, emptyState } from '../ui/table.js';
import { formatDate, joinParts } from '../ui/format.js';
import { screenHead, bannerHost, showError, rows } from './common.js';
import { searchField } from '../ui/search.js';

const KINDS = ['party', 'building', 'unit', 'lease'];

export function archiveScreen(client, { onChanged } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const body = el('div');
    let all = [];
    let kind = 'all';
    let query = '';

    const search = searchField((value) => {
        query = value.toLowerCase();
        paint();
    });

    search.input.placeholder = t('ui.archive.search_placeholder');
    search.input.setAttribute('aria-label', t('ui.archive.search_label'));

    function matches(row) {
        if (kind !== 'all' && row.kind !== kind) {
            return false;
        }

        if (query === '') {
            return true;
        }

        return [row.label, row.context, row.reason, row.archived_by]
            .filter(Boolean)
            .some((field) => String(field).toLowerCase().includes(query));
    }

    function chipBar() {
        const counts = Object.fromEntries(KINDS.map((k) => [k, all.filter((row) => row.kind === k).length]));

        return el('div', { class: 'chips' }, [
            el('button', {
                class: `filter-chip${kind === 'all' ? ' is-active' : ''}`, type: 'button',
                text: `${t('ui.archive.kind_all')} (${all.length})`, onclick: () => { kind = 'all'; paint(); },
            }),
            ...KINDS.map((k) => el('button', {
                class: `filter-chip${kind === k ? ' is-active' : ''}`, type: 'button',
                text: `${t(`ui.archive.kind_${k}`)} (${counts[k]})`, onclick: () => { kind = k; paint(); },
            })),
        ]);
    }

    function row(record) {
        return el('div', { class: 'record-card' }, [
            el('div', { class: 'record-card-head' }, [
                el('h3', { class: 'record-card-title', text: record.label ?? `#${record.id}` }),
                badge(t(`ui.archive.kind_${record.kind}`), `archive-${record.kind}`),
            ]),
            record.context ? el('p', { class: 'record-card-sub', text: String(record.context) }) : null,
            el('p', { class: 'muted-small', text: joinParts([
                t('ui.archive.archived_on', { date: formatDate(String(record.archived_at ?? '').slice(0, 10)) }),
                record.archived_by ? t('ui.archive.by', { name: record.archived_by }) : null,
            ], ' ') }),
            record.reason ? el('p', { class: 'record-card-sub', text: record.reason }) : null,
            can('manage_settings') ? el('div', { class: 'record-card-actions' }, [
                button(t('ui.archive.restore'), {
                    kind: 'primary', iconName: 'corner-up-left',
                    onClick: async () => {
                        try {
                            if (await restoreRecord(client, { kind: record.kind, id: record.id, label: record.label, reason: record.reason })) {
                                await load();
                                onChanged?.();
                            }
                        } catch (failure) {
                            showError(errors, failure, t('ui.archive.restore_failed'));
                        }
                    },
                }),
            ]) : null,
        ]);
    }

    function paint() {
        const found = all.filter(matches);

        mount(body,
            all.length === 0 ? null : el('div', { class: 'stack' }, [search.node, chipBar()]),
            el('p', { class: 'muted-small', text: t('ui.archive.showing', { shown: found.length, total: all.length }) }),
            all.length === 0
                ? emptyState('archive', t('ui.archive.empty'))
                : found.length === 0
                    ? emptyState('search-lg', t('ui.archive.no_matches'))
                    : el('div', {}, found.map(row))
        );
    }

    async function load() {
        mount(body, loading(t('ui.archive.loading')));

        try {
            all = rows(await client.get(endpoints.archive));
            paint();
        } catch (failure) {
            mount(body);
            showError(errors, failure, t('ui.archive.load_failed'));
        }
    }

    mount(host,
        screenHead({ eyebrow: t('ui.archive.eyebrow'), title: t('ui.archive.heading'), sub: t('ui.archive.description') }),
        errors,
        body
    );

    load();

    return { node: host, reload: load };
}
