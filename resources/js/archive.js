/*
|--------------------------------------------------------------------------
| The archive
|--------------------------------------------------------------------------
|
| What has been put out of the way, and putting it back.
|
| Archiving is what Patrimoine offers instead of deletion for a record the
| accounting still refers to. Nothing about the record moves — every
| invoice, receipt and journal entry still names it — it simply stops
| appearing in the lists and in the pickers that build new records. This
| page is the one place those records are still visible, which is what
| makes archiving safe to offer at all: nothing disappears without
| somewhere to find it again.
|
| Restoring puts a record back into every list and every picker, so it is
| gated on manage_settings; reading the page is ordinary work.
|
| V1.0.43 gives the page three things it needed once it held more than a
| handful of rows:
|
|   - a search, over the name, the line under it and the reason given;
|   - a filter by kind, so one sort of record can be read on its own;
|   - a coloured chip per kind, because this is the only list in the
|     product that mixes parties, properties, units and lettings in one
|     column, and the kind is what a reader is scanning for.
|
| The filtering is done here rather than on the server. The endpoint
| already answers with everything an organisation has archived — it is a
| list of things nobody is using — so asking again for a narrower version
| of what is already in the browser would be slower and no more correct.
|
*/

import {
    apiRequest,
    escapeHtml,
    formatDate,
    hideArchiveDrawerError,
    messageWithErrorCode,
    openDrawer,
    closeDrawer,
    parseJsonResponse,
    restoreButton,
    setButtonBusy,
    showArchiveDrawerError,
    translate,
} from './core.js';

/**
 * Everything the organisation has archived, as the server last said it.
 *
 * Kept so the search and the kind filter can redraw without asking again.
 */
let archivedRows = [];

/**
 * The row the restore drawer is currently asking about.
 */
let pendingRestore = null;

/**
 * The kinds a reader may narrow the list to, in the order they are drawn.
 *
 * 'all' is not a kind; it is the absence of the filter, and it is first
 * because it is where the page starts.
 */
const KINDS = ['all', 'party', 'building', 'unit', 'lease'];

/**
 * Load the archive and draw it.
 */
async function loadArchive() {
    const loading = document.getElementById('archive-loading');
    const error = document.getElementById('archive-error');

    error?.classList.add('hidden');
    document.getElementById('archive-list')?.classList.add('hidden');
    document.getElementById('archive-empty')?.classList.add('hidden');
    document.getElementById('archive-no-matches')?.classList.add('hidden');
    loading?.classList.remove('hidden');

    try {
        const payload = await parseJsonResponse(
            await apiRequest('/api/archive')
        );

        archivedRows = Array.isArray(payload?.data)
            ? payload.data
            : [];

        loading?.classList.add('hidden');

        renderControls();

        render();
    } catch (failure) {
        loading?.classList.add('hidden');

        if (error) {
            error.textContent = messageWithErrorCode(
                failure instanceof Error
                    ? failure.message
                    : translate('archive.load_failed')
            );

            error.classList.remove('hidden');
        }
    }
}

/**
 * Draw the rows that match the search and the kind filter.
 */
function render() {
    const list = document.getElementById('archive-list');
    const empty = document.getElementById('archive-empty');
    const noMatches = document.getElementById('archive-no-matches');
    const controls = document.getElementById('archive-controls');

    if (! list) {
        return;
    }

    /*
     * An organisation that has archived nothing is a different state from
     * one whose search matched nothing, and it deserves a different
     * answer: there is nothing to search, so there is nothing to filter
     * with either.
     */
    if (archivedRows.length === 0) {
        list.classList.add('hidden');
        noMatches?.classList.add('hidden');
        controls?.classList.add('hidden');
        empty?.classList.remove('hidden');

        return;
    }

    empty?.classList.add('hidden');
    controls?.classList.remove('hidden');

    const matches = archivedRows.filter(matchesFilters);

    updateCounts();

    if (matches.length === 0) {
        list.classList.add('hidden');
        noMatches?.classList.remove('hidden');

        return;
    }

    noMatches?.classList.add('hidden');

    list.innerHTML = matches.map(archiveRow).join('');

    list.classList.remove('hidden');

    attachRestoreListeners(list);
}

/**
 * Whether one archived record survives the search and the kind filter.
 *
 * @param {object} row
 * @returns {boolean}
 */
function matchesFilters(row) {
    const kind = selectedKind();

    if (kind !== 'all' && row.kind !== kind) {
        return false;
    }

    const term = searchTerm();

    if (term === '') {
        return true;
    }

    /*
     * The reason is searched as well as the name. Somebody looking for
     * everything that was put away when a building changed hands is
     * searching for the reason they typed, not for a record they can
     * already name.
     */
    return [row.label, row.context, row.reason, row.archived_by]
        .filter(Boolean)
        .some(
            (value) => String(value)
                .toLowerCase()
                .includes(term)
        );
}

/**
 * @returns {string}
 */
function searchTerm() {
    return String(
        document.getElementById('archive-search')?.value ?? ''
    )
        .trim()
        .toLowerCase();
}

/**
 * @returns {string}
 */
function selectedKind() {
    return document
        .querySelector('[data-archive-kind-filter].pm-filter-chip-active')
        ?.dataset.archiveKindFilter
        ?? 'all';
}

/**
 * Draw one chip per kind, each carrying how many records it holds.
 *
 * Kinds that have never been archived are still drawn, at zero, so the
 * filter bar does not change shape as records come and go.
 */
function renderControls() {
    const container = document.getElementById('archive-kind-filters');

    if (! container) {
        return;
    }

    const active = selectedKind();

    container.innerHTML = KINDS
        .map((kind) => {
            const label = kind === 'all'
                ? translate('archive.kind_all')
                : translate(`archive.kind_${kind}`);

            const chipClass = kind === active
                ? 'pm-filter-chip pm-filter-chip-active'
                : 'pm-filter-chip';

            return `
                <button
                    type="button"
                    data-archive-kind-filter="${escapeHtml(kind)}"
                    class="${chipClass}"
                >
                    ${escapeHtml(label)}

                    <span
                        data-archive-kind-count="${escapeHtml(kind)}"
                        class="pm-filter-chip-count"
                    ></span>
                </button>
            `;
        })
        .join('');

    container
        .querySelectorAll('[data-archive-kind-filter]')
        .forEach((chip) => {
            chip.addEventListener('click', () => {
                container
                    .querySelectorAll('[data-archive-kind-filter]')
                    .forEach((other) => {
                        other.classList.remove('pm-filter-chip-active');
                    });

                chip.classList.add('pm-filter-chip-active');

                render();
            });
        });
}

/**
 * Say how many records each kind holds, and how many are on screen.
 */
function updateCounts() {
    KINDS.forEach((kind) => {
        const count = kind === 'all'
            ? archivedRows.length
            : archivedRows.filter((row) => row.kind === kind).length;

        const element = document.querySelector(
            `[data-archive-kind-count="${kind}"]`
        );

        if (element) {
            element.textContent = String(count);
        }
    });

    const showing = document.getElementById('archive-showing');

    if (showing) {
        showing.textContent = translate('archive.showing', {
            shown: String(archivedRows.filter(matchesFilters).length),
            total: String(archivedRows.length),
        });
    }
}

/**
 * The chip class for one kind of archived record.
 *
 * Built whole rather than interpolated into a class attribute, because
 * the markup audit cannot read `pm-badge-archive-${kind}` and a class it
 * cannot read is a class nobody notices is undefined.
 *
 * @param {string} kind
 * @returns {string}
 */
function kindBadgeClass(kind) {
    switch (kind) {
        case 'party':
            return 'pm-badge pm-badge-archive-party';
        case 'building':
            return 'pm-badge pm-badge-archive-building';
        case 'unit':
            return 'pm-badge pm-badge-archive-unit';
        case 'lease':
            return 'pm-badge pm-badge-archive-lease';
        default:
            return 'pm-badge';
    }
}

/**
 * One archived record.
 *
 * @param {object} row
 * @returns {string}
 */
function archiveRow(row) {
    const when = row.archived_at
        ? formatDate(String(row.archived_at).slice(0, 10))
        : '';

    const by = row.archived_by
        ? translate('archive.by', { name: row.archived_by })
        : '';

    return `
        <article class="pm-archive-row">
            <div class="min-w-0 flex-1">
                <div class="pm-archive-title-line">
                    <h3 class="pm-archive-name">
                        ${escapeHtml(row.label || '—')}
                    </h3>

                    <span class="${kindBadgeClass(row.kind)}">
                        ${escapeHtml(translate(`archive.kind_${row.kind}`))}
                    </span>

                    ${
                        row.context
                            ? `
                                <span class="pm-archive-context">
                                    ${escapeHtml(row.context)}
                                </span>
                            `
                            : ''
                    }
                </div>

                <p class="pm-archive-meta">
                    ${escapeHtml(
                        translate('archive.archived_on', { date: when })
                    )}
                    ${escapeHtml(by)}
                </p>

                ${
                    row.reason
                        ? `
                            <p class="pm-archive-reason">
                                ${escapeHtml(row.reason)}
                            </p>
                        `
                        : ''
                }
            </div>

            <button
                type="button"
                data-restore
                data-kind="${escapeHtml(row.kind)}"
                data-id="${escapeHtml(row.id)}"
                data-label="${escapeHtml(row.label || '')}"
                data-reason="${escapeHtml(row.reason || '')}"
                data-requires-capability="manage_settings"
                class="pm-button-secondary rbac-hidden max-sm:w-full"
            >
                ${escapeHtml(translate('archive.restore'))}
            </button>
        </article>
    `;
}

/**
 * @param {HTMLElement} container
 */
function attachRestoreListeners(container) {
    container
        .querySelectorAll('[data-restore]')
        .forEach((button) => {
            button.addEventListener('click', () => {
                openRestoreDrawer(button);
            });
        });
}

/**
 * Ask before putting a record back into every list and every picker.
 *
 * @param {HTMLElement} button
 */
function openRestoreDrawer(button) {
    pendingRestore = {
        kind: button.dataset.kind,
        id: button.dataset.id,
        label: button.dataset.label ?? '',
        reason: button.dataset.reason ?? '',
    };

    const record = document.getElementById('archive-restore-drawer-record');

    if (record) {
        record.textContent = pendingRestore.label;
    }

    /*
     * Why it was put away in the first place, shown while deciding
     * whether to undo that.
     */
    const original = document.getElementById(
        'archive-restore-drawer-original'
    );

    const originalReason = document.getElementById(
        'archive-restore-drawer-original-reason'
    );

    if (original && originalReason) {
        originalReason.textContent = pendingRestore.reason;

        original.classList.toggle(
            'hidden',
            pendingRestore.reason === ''
        );
    }

    const reason = document.getElementById('archive-restore-drawer-reason');

    if (reason instanceof HTMLTextAreaElement) {
        reason.value = '';
    }

    hideArchiveDrawerError('archive-restore-drawer-error');

    openDrawer('archive-restore-drawer');

    reason?.focus();
}

/**
 * Put one record back.
 */
async function submitRestore() {
    if (! pendingRestore) {
        return;
    }

    const submit = document.getElementById('archive-restore-drawer-submit');

    const reason = String(
        document.getElementById('archive-restore-drawer-reason')?.value ?? ''
    ).trim();

    hideArchiveDrawerError('archive-restore-drawer-error');

    setButtonBusy(submit, 'archive.restoring');

    try {
        const response = await apiRequest(
            `/api/archive/${pendingRestore.kind}/${pendingRestore.id}`,
            {
                method: 'DELETE',
                body: JSON.stringify({ reason }),
            }
        );

        if (! response.ok) {
            const payload = await response.json().catch(() => ({}));

            const validation = Object
                .values(payload?.errors ?? {})
                .flat()
                .filter(Boolean)
                .join(' ');

            throw new Error(
                messageWithErrorCode(
                    validation
                    || payload?.message
                    || translate('archive.restore_failed'),
                    payload?.code ?? null
                )
            );
        }

        restoreButton(submit);

        closeDrawer('archive-restore-drawer');

        pendingRestore = null;

        await loadArchive();
    } catch (failure) {
        restoreButton(submit);

        showArchiveDrawerError(
            'archive-restore-drawer-error',
            failure instanceof Error
                ? failure.message
                : translate('archive.restore_failed')
        );
    }
}

/**
 * Wire the search, the filter chips and the restore drawer.
 */
function wireControls() {
    document
        .getElementById('archive-search')
        ?.addEventListener('input', render);

    document
        .getElementById('archive-restore-drawer-form')
        ?.addEventListener('submit', async (event) => {
            event.preventDefault();

            await submitRestore();
        });

    [
        'archive-restore-drawer-cancel',
        'archive-restore-drawer-close',
        'archive-restore-drawer-backdrop',
    ].forEach((id) => {
        document
            .getElementById(id)
            ?.addEventListener('click', () => {
                closeDrawer('archive-restore-drawer');
            });
    });
}

/**
 * Wire the archive page, if this is it.
 */
export async function initializeArchive() {
    if (! document.getElementById('archive-page')) {
        return;
    }

    wireControls();

    await loadArchive();
}
