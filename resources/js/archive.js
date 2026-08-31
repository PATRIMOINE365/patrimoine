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
*/

import {
    apiRequest,
    escapeHtml,
    formatDate,
    messageWithErrorCode,
    parseJsonResponse,
    restoreButton,
    setButtonBusy,
    translate,
} from './core.js';

/**
 * Load the archive and draw it.
 */
async function loadArchive() {
    const list = document.getElementById('archive-list');
    const loading = document.getElementById('archive-loading');
    const empty = document.getElementById('archive-empty');
    const error = document.getElementById('archive-error');

    error?.classList.add('hidden');
    list?.classList.add('hidden');
    empty?.classList.add('hidden');
    loading?.classList.remove('hidden');

    try {
        const payload = await parseJsonResponse(
            await apiRequest('/api/archive')
        );

        const rows = Array.isArray(payload?.data)
            ? payload.data
            : [];

        loading?.classList.add('hidden');

        if (rows.length === 0) {
            empty?.classList.remove('hidden');

            return;
        }

        if (list) {
            list.innerHTML = rows.map(archiveRow).join('');

            list.classList.remove('hidden');

            attachRestoreListeners(list);
        }
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

                    <span class="pm-badge">
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
            </div>

            <button
                type="button"
                data-restore
                data-kind="${escapeHtml(row.kind)}"
                data-id="${escapeHtml(row.id)}"
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
            button.addEventListener('click', () => restore(button));
        });
}

/**
 * Put one record back.
 *
 * @param {HTMLButtonElement} button
 */
async function restore(button) {
    const error = document.getElementById('archive-error');

    error?.classList.add('hidden');

    setButtonBusy(button, 'archive.restoring');

    try {
        const response = await apiRequest(
            `/api/archive/${button.dataset.kind}/${button.dataset.id}`,
            { method: 'DELETE' }
        );

        if (! response.ok) {
            const payload = await response.json().catch(() => ({}));

            throw new Error(
                messageWithErrorCode(
                    payload?.message ?? translate('archive.restore_failed'),
                    payload?.code ?? null
                )
            );
        }

        await loadArchive();
    } catch (failure) {
        restoreButton(button);

        if (error) {
            error.textContent = failure instanceof Error
                ? failure.message
                : translate('archive.restore_failed');

            error.classList.remove('hidden');
        }
    }
}

/**
 * Wire the archive page, if this is it.
 */
export async function initializeArchive() {
    if (! document.getElementById('archive-page')) {
        return;
    }

    await loadArchive();
}
