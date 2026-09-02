/*
 * Tables and their pagination, as the browser application draws them.
 *
 * ONE pagination control everywhere - the web's own rule since v1.0.32:
 * "Showing 1–25 of 132", a rows-per-page choice, numbered pages windowed
 * around the current one. Full-page lists offer 25/50/100 and default to
 * 25; the panels inside a record offer 5/10/25/50/100 and default to 10.
 * The choice is remembered per list, as the web remembers it per browser.
 */

import { el, mount } from './dom.js';
import { icon } from './icon.js';
import { t } from '../i18n/index.js';

export const PAGE_SIZES = [25, 50, 100];
export const PANEL_SIZES = [5, 10, 25, 50, 100];

function remembered(key, fallback, sizes) {
    try {
        const held = Number(localStorage.getItem(`pm.rows.${key}`));

        return sizes.includes(held) ? held : fallback;
    } catch {
        return fallback;
    }
}

function remember(key, size) {
    try {
        localStorage.setItem(`pm.rows.${key}`, String(size));
    } catch {
        /* Private mode, or storage refused: the default is fine. */
    }
}

/**
 * Page-size state for one list. `panel: true` gives the in-record sizes.
 */
export function pageSize(key, { panel = false } = {}) {
    const sizes = panel ? PANEL_SIZES : PAGE_SIZES;
    const fallback = panel ? 10 : 25;
    let current = remembered(key, fallback, sizes);

    return {
        sizes,
        get() {
            return current;
        },
        set(size) {
            current = Number(size);
            remember(key, current);
        },
    };
}

/** Page numbers to show: first, last, the current one and its neighbours, with gaps. */
function pageWindow(current, last) {
    const pages = new Set([1, last, current - 1, current, current + 1].filter((n) => n >= 1 && n <= last));
    const sorted = [...pages].sort((a, b) => a - b);
    const out = [];

    for (let i = 0; i < sorted.length; i += 1) {
        if (i > 0 && sorted[i] - sorted[i - 1] > 1) {
            out.push('gap');
        }

        out.push(sorted[i]);
    }

    return out;
}

/**
 * The control. Hidden entirely when everything fits on the smallest page.
 *
 * @param {object} meta  { current_page, last_page, total, from, to, per_page }
 * @param {object} size  from pageSize()
 * @param {(page:number) => void} onPage
 */
export function pagination(meta, size, onPage) {
    const total = meta?.total ?? 0;
    const lastPage = Math.max(1, meta?.last_page ?? 1);
    const current = Math.min(Math.max(1, meta?.current_page ?? 1), lastPage);

    if (total <= size.sizes[0] && lastPage <= 1) {
        return null;
    }

    const from = meta?.from ?? (total === 0 ? 0 : (current - 1) * size.get() + 1);
    const to = meta?.to ?? Math.min(total, current * size.get());

    const select = el('select', { class: 'input input-compact', 'aria-label': t('ui.pagination.rows_per_page') }, size.sizes.map((s) => el('option', {
        value: String(s), text: String(s), selected: s === size.get(),
    })));

    select.addEventListener('change', () => {
        size.set(select.value);
        onPage(1);
    });

    return el('nav', { class: 'pagination', 'aria-label': t('ui.pagination.navigation') }, [
        el('span', { class: 'pagination-summary', text: total === 0 ? t('ui.pagination.empty') : t('ui.pagination.summary', { from, to, total }) }),
        el('label', { class: 'pagination-size' }, [
            el('span', { text: t('ui.pagination.rows_per_page') }),
            select,
        ]),
        el('div', { class: 'pagination-pages' }, [
            el('button', {
                class: 'icon-button', type: 'button', disabled: current <= 1,
                'aria-label': t('ui.pagination.previous'), onclick: () => onPage(current - 1),
            }, [icon('chevron-left', { size: 18 })]),
            ...pageWindow(current, lastPage).map((entry) => entry === 'gap'
                ? el('span', { class: 'pagination-gap', text: '…' })
                : el('button', {
                    class: `pagination-page${entry === current ? ' is-current' : ''}`, type: 'button',
                    'aria-label': entry === current ? t('ui.pagination.current_page', { page: entry }) : t('ui.pagination.go_to_page', { page: entry }),
                    'aria-current': entry === current ? 'page' : undefined,
                    text: String(entry), onclick: () => onPage(entry),
                })),
            el('button', {
                class: 'icon-button', type: 'button', disabled: current >= lastPage,
                'aria-label': t('ui.pagination.next'), onclick: () => onPage(current + 1),
            }, [icon('chevron-right', { size: 18 })]),
        ]),
    ]);
}

/** Slice rows already held into one page, with the meta the control wants. */
export function clientPage(rows, page, perPage) {
    const total = rows.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const current = Math.min(Math.max(1, page), lastPage);
    const start = (current - 1) * perPage;

    return {
        rows: rows.slice(start, start + perPage),
        meta: {
            current_page: current, last_page: lastPage, total, per_page: perPage,
            from: total === 0 ? 0 : start + 1, to: Math.min(total, start + perPage),
        },
    };
}

/**
 * A table. Each column: { label, cell(row) -> Node|string, align, class }.
 * Rows without an `onRow` are inert; with one they are tappable.
 */
export function table(columns, rows, { empty, onRow, rowClass, footer } = {}) {
    if (rows.length === 0 && empty !== undefined) {
        return el('div', { class: 'table-empty', text: empty });
    }

    const wrap = el('div', { class: 'table-wrap' }, [
        el('table', { class: 'table' }, [
            el('thead', {}, [el('tr', {}, columns.map((column) => el('th', {
                class: column.align === 'right' ? 'is-numeric' : undefined, text: column.label,
            })))]),
            el('tbody', {}, rows.map((row) => el('tr', {
                class: [onRow ? 'is-tappable' : '', rowClass?.(row) ?? ''].join(' ').trim() || undefined,
                onclick: onRow ? () => onRow(row) : undefined,
            }, columns.map((column) => {
                const content = column.cell(row);

                return el('td', { class: [column.align === 'right' ? 'is-numeric' : '', column.class ?? ''].join(' ').trim() || undefined },
                    typeof content === 'string' || typeof content === 'number' ? [String(content)] : [content]);
            })))),
            footer ? el('tfoot', {}, [footer]) : null,
        ]),
    ]);

    return wrap;
}

/**
 * A list that pages itself over rows already held: the in-record panels.
 * Returns a node that re-renders on page change.
 */
export function pagedTable(key, columns, rows, options = {}) {
    const size = pageSize(key, { panel: options.panel !== false });
    const host = el('div', { class: 'paged' });

    function paint(page) {
        const { rows: slice, meta } = clientPage(rows, page, size.get());

        mount(host, table(columns, slice, options), pagination(meta, size, paint));
    }

    paint(1);

    return host;
}

/** A stat card: label above a figure, optional caption, optional tone. */
export function stat(label, value, { sub, tone } = {}) {
    return el('div', { class: `kpi${tone ? ` kpi-${tone}` : ''}` }, [
        el('span', { class: 'kpi-label', text: label }),
        el('span', { class: 'kpi-value', text: value }),
        sub ? el('span', { class: 'kpi-sub', text: sub }) : null,
    ]);
}

/** The web's "pair grid": a label over a value, several abreast. */
export function pairs(entries, { columns = 3 } = {}) {
    return el('div', { class: `pair-grid pair-grid-${columns}` }, entries
        .filter((entry) => entry !== null)
        .map(([label, value, options = {}]) => el('div', { class: `pair${options.tone ? ` pair-${options.tone}` : ''}` }, [
            el('span', { class: 'pair-label', text: label }),
            el('span', { class: 'pair-value', text: value === null || value === undefined || value === '' ? '—' : String(value) }),
            options.meta ? el('span', { class: 'pair-meta', text: options.meta }) : null,
        ])));
}

/** A titled block inside a screen. */
export function section(title, body, { sub, actions, tone } = {}) {
    return el('section', { class: `card${tone ? ` card-${tone}` : ''}` }, [
        el('header', { class: 'card-head' }, [
            el('div', { class: 'card-words' }, [
                el('h2', { class: 'card-title', text: title }),
                sub ? el('p', { class: 'card-sub', text: sub }) : null,
            ]),
            actions ? el('div', { class: 'card-actions' }, actions.filter(Boolean)) : null,
        ]),
        el('div', { class: 'card-body' }, [].concat(body).filter(Boolean)),
    ]);
}

export function badge(text, tone = 'neutral') {
    return el('span', { class: `chip chip-${tone}`, text });
}

export function button(label, { kind = 'secondary', iconName, onClick, disabled = false, title, compact = true } = {}) {
    return el('button', {
        class: `button button-${kind}${compact ? ' button-compact' : ''}`,
        type: 'button', disabled, title,
        onclick: onClick ? async (event) => {
            const node = event.currentTarget;

            node.disabled = true;

            try {
                await onClick(node);
            } finally {
                if (node.isConnected) {
                    node.disabled = disabled;
                }
            }
        } : undefined,
    }, [
        iconName ? icon(iconName, { size: 16 }) : null,
        el('span', { text: label }),
    ]);
}

export function loading(text) {
    return el('p', { class: 'muted centred', text: text ?? t('list.loading') });
}

export function emptyState(iconName, title, sub) {
    return el('div', { class: 'empty' }, [
        el('div', { class: 'empty-icon' }, [icon(iconName, { size: 24 })]),
        el('p', { class: 'empty-text', text: title }),
        sub ? el('p', { class: 'empty-sub', text: sub }) : null,
    ]);
}
