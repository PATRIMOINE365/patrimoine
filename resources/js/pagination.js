/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
|
| One control for every list in Patrimoine. Before this module each page
| carried its own renderer, and all of them said the same small thing:
| "Page 3 of 9", Previous, Next. Reaching page 9 of an activity log took
| eight clicks, and the page size was whatever the controller defaulted to.
|
| The control this module renders instead offers:
|
|   - how many rows are on screen out of how many exist;
|   - a choice of 25, 50 or 100 rows, remembered per list, per browser;
|   - numbered pages, so any page is one click away;
|   - the page being read, marked both visually and for a screen reader.
|
| A list adopts it by handing over its container, the Laravel paginator
| payload it just received, and what to do when the reader asks for a
| different page or a different page size.
|
| The page numbers are windowed: the first page, the last page, and the
| pages either side of the current one. Everything else collapses into an
| ellipsis, so a 400-page journal still renders a control that fits.
|
*/

import {
    escapeHtml,
    translate,
} from './core.js';

import {
    translationFor,
} from './translations.js';

/**
 * The row counts a reader may choose between.
 *
 * The first entry is also the default, and the threshold below which a
 * list needs no pagination at all.
 */
export const PAGE_SIZES = [25, 50, 100];

/**
 * Where a remembered page size is kept.
 *
 * Per list and per browser: somebody who wants 100 rows of activity does
 * not necessarily want 100 parties.
 */
const STORAGE_PREFIX = 'pm.rows.';

/**
 * How many pages are drawn either side of the current one.
 */
const WINDOW = 1;

/**
 * The page size this list should ask the server for.
 *
 * Falls back to the smallest offered size whenever nothing is remembered,
 * the stored value has been tampered with, or storage is unavailable.
 *
 * @param {string} key
 * @returns {number}
 */
export function pageSizeFor(key) {
    let stored = null;

    try {
        stored = window.localStorage.getItem(STORAGE_PREFIX + key);
    } catch (error) {
        stored = null;
    }

    const size = Number(stored);

    return PAGE_SIZES.includes(size)
        ? size
        : PAGE_SIZES[0];
}

/**
 * Remember a page size for this list.
 *
 * A browser refusing storage is not an error worth surfacing; the reader
 * simply gets the default again next time.
 *
 * @param {string} key
 * @param {number} size
 */
export function rememberPageSize(key, size) {
    try {
        window.localStorage.setItem(
            STORAGE_PREFIX + key,
            String(size)
        );
    } catch (error) {
        // Storage is a convenience here, never a requirement.
    }
}

/**
 * Which page numbers to draw.
 *
 * Returns page numbers interleaved with nulls, where each null is a run of
 * pages the control collapses into an ellipsis.
 *
 * @param {number} currentPage
 * @param {number} lastPage
 * @returns {Array<number|null>}
 */
function pageWindow(currentPage, lastPage) {
    const wanted = new Set([1, lastPage]);

    for (
        let page = currentPage - WINDOW;
        page <= currentPage + WINDOW;
        page += 1
    ) {
        if (page >= 1 && page <= lastPage) {
            wanted.add(page);
        }
    }

    const pages = [...wanted].sort((a, b) => a - b);

    const drawn = [];

    pages.forEach((page, index) => {
        if (index > 0 && page - pages[index - 1] > 1) {
            drawn.push(null);
        }

        drawn.push(page);
    });

    return drawn;
}

/**
 * Turn rows already held in the browser into one page of rows plus the
 * paginator payload the control expects.
 *
 * Some lists are small enough, or fixed enough, to arrive whole: the error
 * code reference, the release history, the handful of platform staff. They
 * still deserve the same control, so they borrow its shape rather than its
 * server.
 *
 * @param {Array} rows
 * @param {number} page
 * @param {number} perPage
 * @returns {{rows: Array, meta: object}}
 */
export function clientPage(rows, page, perPage) {
    const all = Array.isArray(rows) ? rows : [];

    const total = all.length;

    const lastPage = Math.max(1, Math.ceil(total / perPage));

    const current = Math.min(Math.max(1, Number(page) || 1), lastPage);

    const start = (current - 1) * perPage;

    const slice = all.slice(start, start + perPage);

    return {
        rows: slice,
        meta: {
            current_page: current,
            last_page: lastPage,
            per_page: perPage,
            total,
            from: total === 0 ? 0 : start + 1,
            to: start + slice.length,
        },
    };
}

/**
 * How this control should speak.
 *
 * Everything customer-facing follows the reader's own language. The
 * platform console is deliberately monolingual English, so it asks for
 * English regardless of who is signed in.
 *
 * @param {boolean} english
 * @returns {function(string, object=): string}
 */
function translator(english) {
    return english
        ? (key, replacements = {}) => translationFor('en', key, replacements)
        : translate;
}

/**
 * One numbered page button.
 *
 * @param {number} page
 * @param {number} currentPage
 * @param {function} t
 * @returns {string}
 */
function pageButton(page, currentPage, t) {
    const current = page === currentPage;

    const label = t(
        current
            ? 'pagination.current_page'
            : 'pagination.go_to_page',
        { page }
    );

    return `
        <button
            type="button"
            data-pagination-page="${page}"
            aria-label="${escapeHtml(label)}"
            ${current ? 'aria-current="page"' : ''}
            class="pm-pagination-page${current ? ' is-current' : ''}"
        >${page}</button>
    `;
}

/**
 * Draw the control, or take it off the page when it earns nothing.
 *
 * `meta` is a Laravel paginator payload: current_page, last_page, per_page,
 * total, and the from/to pair describing the slice on screen.
 *
 * @param {HTMLElement|string} target
 * @param {object} meta
 * @param {{
 *     onPage: function(number): void,
 *     onPageSize?: function(number): void,
 *     storageKey?: string,
 *     english?: boolean,
 * }} options
 */
export function renderPagination(target, meta, options = {}) {
    const container = typeof target === 'string'
        ? document.getElementById(target)
        : target;

    if (! container) {
        return;
    }

    const t = translator(options.english === true);

    const currentPage = Number(meta?.current_page ?? 1);
    const lastPage = Number(meta?.last_page ?? 1);
    const total = Number(meta?.total ?? 0);
    const perPage = Number(meta?.per_page ?? PAGE_SIZES[0]);

    /*
     * A list nobody needs to page through says nothing at all. The test is
     * the total rather than the page count, so a reader who chose 100 rows
     * and now sees 90 of them can still drop back to 25.
     */
    if (total <= PAGE_SIZES[0] && lastPage <= 1) {
        container.innerHTML = '';

        container.classList.add('hidden');

        return;
    }

    container.classList.remove('hidden');

    const from = Number(meta?.from ?? 0);
    const to = Number(meta?.to ?? 0);

    const summary = total === 0
        ? t('pagination.empty')
        : t('pagination.summary', { from, to, total });

    const sizes = PAGE_SIZES
        .map((size) => `
            <option
                value="${size}"
                ${size === perPage ? 'selected' : ''}
            >${size}</option>
        `)
        .join('');

    const pages = lastPage <= 1
        ? ''
        : pageWindow(currentPage, lastPage)
            .map((page) => (
                page === null
                    ? '<span class="pm-pagination-gap" aria-hidden="true">&hellip;</span>'
                    : pageButton(page, currentPage, t)
            ))
            .join('');

    const steps = lastPage <= 1
        ? ''
        : `
            <button
                type="button"
                data-pagination-step="previous"
                ${currentPage <= 1 ? 'disabled' : ''}
                aria-label="${escapeHtml(t('pagination.previous'))}"
                class="pm-pagination-step"
            >
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path
                        d="M12.5 15 7.5 10l5-5"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

            ${pages}

            <button
                type="button"
                data-pagination-step="next"
                ${currentPage >= lastPage ? 'disabled' : ''}
                aria-label="${escapeHtml(t('pagination.next'))}"
                class="pm-pagination-step"
            >
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path
                        d="m7.5 5 5 5-5 5"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>
        `;

    container.innerHTML = `
        <div class="pm-pagination">
            <p class="pm-pagination-summary">${escapeHtml(summary)}</p>

            <div class="pm-pagination-controls">
                <label class="pm-pagination-size">
                    <span>${escapeHtml(t('pagination.rows_per_page'))}</span>

                    <select data-pagination-size class="pm-input">
                        ${sizes}
                    </select>
                </label>

                <nav
                    class="pm-pagination-pages"
                    aria-label="${escapeHtml(t('pagination.navigation'))}"
                >${steps}</nav>
            </div>
        </div>
    `;

    const goTo = (page) => {
        if (
            page >= 1
            && page <= lastPage
            && page !== currentPage
            && typeof options.onPage === 'function'
        ) {
            options.onPage(page);
        }
    };

    container
        .querySelectorAll('[data-pagination-page]')
        .forEach((button) => {
            button.addEventListener('click', () => {
                goTo(Number(button.dataset.paginationPage));
            });
        });

    container
        .querySelectorAll('[data-pagination-step]')
        .forEach((button) => {
            button.addEventListener('click', () => {
                goTo(
                    button.dataset.paginationStep === 'previous'
                        ? currentPage - 1
                        : currentPage + 1
                );
            });
        });

    container
        .querySelector('[data-pagination-size]')
        ?.addEventListener('change', (event) => {
            const size = Number(event.target.value);

            if (! PAGE_SIZES.includes(size)) {
                return;
            }

            if (options.storageKey) {
                rememberPageSize(options.storageKey, size);
            }

            /*
             * Changing the page size returns the reader to the first page.
             * Row 200 is not on page 3 any more once pages hold 100 rows,
             * so keeping the page number would land them somewhere they
             * did not ask to be.
             */
            if (typeof options.onPageSize === 'function') {
                options.onPageSize(size);
            }
        });
}
