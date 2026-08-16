/*
|--------------------------------------------------------------------------
| Patrimoine Activity Log
|--------------------------------------------------------------------------
|
| Administrator-only, read-only browser workspace for V1.0.3.
|
| The API remains the authoritative authorization boundary. This UI performs
| no Activity Log writes and exposes no edit/delete functionality.
|
*/

import {
    apiRequest,
    escapeHtml,
    formValue,
    getPresentationConfiguration,
    parseJsonResponse,
    translate,
} from './core.js';

let activitySearchTimer =
    null;

/**
 * Initialize the Administrator Activity Log workspace.
 */
export async function initializeActivityLog() {
    const workspace =
        document.getElementById(
            'activity-log-workspace'
        );

    if (! workspace) {
        return;
    }

    if (
        document.body.dataset
            .currentUserRole
        !== 'administrator'
    ) {
        window.location.replace(
            '/dashboard'
        );

        return;
    }

    initializeFilters();
    initializeExportActions();
    initializeDetailModal();

    await Promise.all([
        loadUserFilter(),
        loadActivityLog(),
    ]);
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

function initializeFilters() {
    document
        .getElementById(
            'activity-log-search'
        )
        ?.addEventListener(
            'input',
            () => {
                clearTimeout(
                    activitySearchTimer
                );

                activitySearchTimer =
                    setTimeout(
                        () => loadActivityLog(1),
                        300
                    );
            }
        );

    [
        'activity-log-from',
        'activity-log-to',
        'activity-log-user',
        'activity-log-role',
    ].forEach(
        (id) => {
            document
                .getElementById(id)
                ?.addEventListener(
                    'change',
                    () => loadActivityLog(1)
                );
        }
    );

    [
        'activity-log-action',
        'activity-log-entity-type',
    ].forEach(
        (id) => {
            document
                .getElementById(id)
                ?.addEventListener(
                    'input',
                    () => {
                        clearTimeout(
                            activitySearchTimer
                        );

                        activitySearchTimer =
                            setTimeout(
                                () => loadActivityLog(1),
                                300
                            );
                    }
                );
        }
    );

    document
        .getElementById(
            'activity-log-clear-filters'
        )
        ?.addEventListener(
            'click',
            clearFilters
        );
}

/**
 * Build the canonical browser filter query.
 *
 * Activity Log exports use this function directly so they contain every
 * record matching the current filters rather than only the visible page.
 */
function activityFilterParameters() {
    const parameters =
        new URLSearchParams();

    const values = {
        search:
            formValue(
                'activity-log-search'
            ),

        from:
            formValue(
                'activity-log-from'
            ),

        to:
            formValue(
                'activity-log-to'
            ),

        user_id:
            formValue(
                'activity-log-user'
            ),

        role:
            formValue(
                'activity-log-role'
            ),

        action:
            formValue(
                'activity-log-action'
            ),

        entity_type:
            formValue(
                'activity-log-entity-type'
            ),
    };

    Object.entries(
        values
    ).forEach(
        ([name, value]) => {
            if (value !== '') {
                parameters.set(
                    name,
                    value
                );
            }
        }
    );

    return parameters;
}

/**
 * Add list-only pagination to the canonical Activity Log filters.
 */
function activityQueryParameters(
    page = 1
) {
    const parameters =
        activityFilterParameters();

    parameters.set(
        'page',
        String(page)
    );

    parameters.set(
        'per_page',
        '25'
    );

    return parameters;
}

function clearFilters() {
    [
        'activity-log-search',
        'activity-log-from',
        'activity-log-to',
        'activity-log-user',
        'activity-log-role',
        'activity-log-action',
        'activity-log-entity-type',
    ].forEach(
        (id) => {
            const element =
                document.getElementById(
                    id
                );

            if (element) {
                element.value = '';
            }
        }
    );

    loadActivityLog(1);
}

/*
|--------------------------------------------------------------------------
| Export
|--------------------------------------------------------------------------
*/

/**
 * Wire Administrator-only Activity Log export controls.
 *
 * Server capability middleware remains the authoritative authorization
 * boundary. This browser module only exposes controls inside the already
 * restricted Activity Log workspace.
 */
function initializeExportActions() {
    document
        .getElementById(
            'activity-log-export-pdf'
        )
        ?.addEventListener(
            'click',
            () => exportActivityLog(
                'pdf'
            )
        );

    document
        .getElementById(
            'activity-log-export-csv'
        )
        ?.addEventListener(
            'click',
            () => exportActivityLog(
                'csv'
            )
        );
}

/**
 * Export all Activity Log rows matching the current filters.
 *
 * Pagination is deliberately excluded.
 *
 * @param {'pdf'|'csv'} format
 */
async function exportActivityLog(
    format
) {
    const button =
        document.getElementById(
            format === 'pdf'
                ? 'activity-log-export-pdf'
                : 'activity-log-export-csv'
        );

    const originalLabel =
        button?.textContent
            ?.trim()
        || '';

    hideError();

    try {
        if (button) {
            button.disabled =
                true;

            button.textContent =
                translate(
                    'activity_log.exporting'
                );
        }

        const parameters =
            activityFilterParameters();

        const query =
            parameters.toString();

        const endpoint =
            `/api/activity-log/${format}`
            + (
                query !== ''
                    ? `?${query}`
                    : ''
            );

        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            format === 'pdf'
                                ? 'application/pdf'
                                : 'text/csv',
                    },
                }
            );

        if (! response.ok) {
            await parseJsonResponse(
                response
            );

            return;
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        const link =
            document.createElement(
                'a'
            );

        link.href =
            url;

        link.download =
            activityExportFilename(
                response,
                format
            );

        document.body.appendChild(
            link
        );

        link.click();

        link.remove();

        /*
         * Revocation is deferred briefly so the browser has finished
         * consuming the object URL before it is released.
         */
        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            1000
        );
    } catch (error) {
        showError(
            error instanceof Error
                ? error.message
                : translate(
                    'activity_log.unable_export'
                )
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                originalLabel
                || translate(
                    format === 'pdf'
                        ? 'activity_log.export_pdf'
                        : 'activity_log.export_csv'
                );
        }
    }
}

/**
 * Prefer the attachment filename supplied by Laravel.
 *
 * @param {Response} response
 * @param {'pdf'|'csv'} format
 * @returns {string}
 */
function activityExportFilename(
    response,
    format
) {
    const disposition =
        response.headers.get(
            'Content-Disposition'
        )
        || '';

    const encoded =
        disposition.match(
            /filename\*=UTF-8''([^;]+)/i
        );

    if (encoded?.[1]) {
        try {
            return decodeURIComponent(
                encoded[1]
                    .trim()
                    .replace(
                        /^["']|["']$/g,
                        ''
                    )
            );
        } catch {
            /*
             * Fall through to the standard filename form.
             */
        }
    }

    const standard =
        disposition.match(
            /filename\s*=\s*"?([^";]+)"?/i
        );

    if (standard?.[1]) {
        return standard[1]
            .trim();
    }

    return `activity-log.${format}`;
}

/*
|--------------------------------------------------------------------------
| User Filter
|--------------------------------------------------------------------------
*/

async function loadUserFilter() {
    const select =
        document.getElementById(
            'activity-log-user'
        );

    if (! select) {
        return;
    }

    try {
        const response =
            await apiRequest(
                '/api/users?per_page=100'
            );

        const payload =
            await parseJsonResponse(
                response
            );

        const users =
            Array.isArray(
                payload?.data
            )
                ? payload.data
                : [];

        users.forEach(
            (user) => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(user.id);

                option.textContent =
                    user.name;

                select.appendChild(
                    option
                );
            }
        );
    } catch {
        /*
         * Failure to load current Users must not prevent historical browsing.
         * Deleted users also remain discoverable through free-text search.
         */
    }
}

/*
|--------------------------------------------------------------------------
| Directory
|--------------------------------------------------------------------------
*/

async function loadActivityLog(
    page = 1
) {
    const container =
        document.getElementById(
            'activity-log-list'
        );

    if (! container) {
        return;
    }

    hideError();

    container.innerHTML = `
        <div
            class="
                px-5 py-12 text-center
                text-sm text-slate-400
            "
        >
            ${escapeHtml(
                translate(
                    'activity_log.loading'
                )
            )}
        </div>
    `;

    try {
        const parameters =
            activityQueryParameters(
                page
            );

        const response =
            await apiRequest(
                `/api/activity-log?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderActivityLog(
            payload
        );

        renderPagination(
            payload
        );
    } catch (error) {
        container.innerHTML = '';

        showError(
            error instanceof Error
                ? error.message
                : translate(
                    'activity_log.unable_load'
                )
        );
    }
}

function renderActivityLog(
    payload
) {
    const container =
        document.getElementById(
            'activity-log-list'
        );

    if (! container) {
        return;
    }

    const events =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    if (events.length === 0) {
        container.innerHTML = `
            <div
                class="
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        text-sm font-medium
                        text-slate-900
                    "
                >
                    ${escapeHtml(
                        translate(
                            'activity_log.none_found'
                        )
                    )}
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                >
                    ${escapeHtml(
                        translate(
                            'activity_log.none_found_description'
                        )
                    )}
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        events
            .map(
                activityRow
            )
            .join('');

    container
        .querySelectorAll(
            '[data-activity-log-id]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => openActivityDetail(
                        button.dataset
                            .activityLogId
                    )
                );
            }
        );
}

function activityRow(event) {
    const actor =
        event.actor_name
        || event.actor_email
        || translate(
            'activity_log.unknown_actor'
        );

    const entity =
        event.entity_label
        || entityReference(
            event
        );

    return `
        <article
            class="
                px-5 py-5
                transition
                hover:bg-slate-50/70
            "
        >
            <div
                class="
                    flex flex-col gap-4
                    xl:flex-row
                    xl:items-center
                    xl:justify-between
                "
            >
                <div class="min-w-0 flex-1">
                    <div
                        class="
                            flex flex-wrap
                            items-center gap-2
                        "
                    >
                        <span
                            class="
                                rounded-full
                                bg-patrimoine-50
                                px-2.5 py-1
                                text-xs font-medium
                                text-patrimoine-700
                            "
                        >
                            ${escapeHtml(
                                humanizeIdentifier(
                                    event.action
                                )
                            )}
                        </span>

                        ${
                            event.actor_role
                                ? `
                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-2.5 py-1
                                            text-xs font-medium
                                            text-slate-600
                                        "
                                    >
                                        ${escapeHtml(
                                            roleLabel(
                                                event.actor_role
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>

                    <div
                        class="
                            mt-3 text-sm
                            font-semibold
                            text-slate-950
                        "
                    >
                        ${escapeHtml(actor)}
                    </div>

                    <div
                        class="
                            mt-1 flex flex-wrap
                            gap-x-5 gap-y-1
                            text-xs text-slate-500
                        "
                    >
                        <span>
                            ${escapeHtml(
                                formatActivityTimestamp(
                                    event.created_at
                                )
                            )}
                        </span>

                        ${
                            entity
                                ? `
                                    <span>
                                        ${escapeHtml(entity)}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            event.ip_address
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            event.ip_address
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>
                </div>

                <button
                    type="button"
                    data-activity-log-id="${escapeHtml(event.id)}"
                    class="
                        shrink-0 rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2
                        text-xs font-medium
                        text-slate-700
                        hover:bg-slate-50
                    "
                >
                    ${escapeHtml(
                        translate(
                            'activity_log.view_details'
                        )
                    )}
                </button>
            </div>
        </article>
    `;
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

function renderPagination(
    payload
) {
    const container =
        document.getElementById(
            'activity-log-pagination'
        );

    if (! container) {
        return;
    }

    const current =
        Number(
            payload?.current_page
            ?? 1
        );

    const last =
        Number(
            payload?.last_page
            ?? 1
        );

    if (last <= 1) {
        container.innerHTML = '';
        container.classList.add(
            'hidden'
        );

        return;
    }

    container.classList.remove(
        'hidden'
    );

    container.innerHTML = `
        <div
            class="
                flex items-center
                justify-between gap-4
            "
        >
            <div
                class="
                    text-sm text-slate-500
                "
            >
                ${escapeHtml(
                    translate(
                        'activity_log.page_of',
                        {
                            current,
                            last,
                        }
                    )
                )}
            </div>

            <div class="flex gap-2">
                <button
                    id="activity-log-previous"
                    type="button"
                    ${current <= 1 ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        px-3 py-2
                        text-sm text-slate-700
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'activity_log.previous'
                        )
                    )}
                </button>

                <button
                    id="activity-log-next"
                    type="button"
                    ${current >= last ? 'disabled' : ''}
                    class="
                        rounded-lg border
                        border-slate-200
                        px-3 py-2
                        text-sm text-slate-700
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'activity_log.next'
                        )
                    )}
                </button>
            </div>
        </div>
    `;

    document
        .getElementById(
            'activity-log-previous'
        )
        ?.addEventListener(
            'click',
            () => {
                if (current > 1) {
                    loadActivityLog(
                        current - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'activity-log-next'
        )
        ?.addEventListener(
            'click',
            () => {
                if (current < last) {
                    loadActivityLog(
                        current + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Detail
|--------------------------------------------------------------------------
*/

function initializeDetailModal() {
    document
        .getElementById(
            'activity-log-modal-close'
        )
        ?.addEventListener(
            'click',
            closeActivityDetail
        );

    document
        .getElementById(
            'activity-log-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeActivityDetail
        );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
            ) {
                closeActivityDetail();
            }
        }
    );
}

async function openActivityDetail(
    id
) {
    const container =
        document.getElementById(
            'activity-log-detail'
        );

    if (! container) {
        return;
    }

    container.innerHTML = `
        <div
            class="
                py-12 text-center
                text-sm text-slate-400
            "
        >
            ${escapeHtml(
                translate(
                    'activity_log.loading_detail'
                )
            )}
        </div>
    `;

    showActivityModal();

    try {
        const response =
            await apiRequest(
                `/api/activity-log/${id}`
            );

        const event =
            await parseJsonResponse(
                response
            );

        container.innerHTML =
            activityDetailMarkup(
                event
            );
    } catch (error) {
        container.innerHTML = `
            <div
                class="
                    rounded-lg border
                    border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700
                "
            >
                ${escapeHtml(
                    error instanceof Error
                        ? error.message
                        : translate(
                            'activity_log.unable_load_detail'
                        )
                )}
            </div>
        `;
    }
}

function activityDetailMarkup(
    event
) {
    return `
        <div class="space-y-6">
            <section>
                <h3
                    class="
                        text-xs font-semibold
                        uppercase tracking-[0.12em]
                        text-slate-500
                    "
                >
                    ${escapeHtml(
                        translate(
                            'activity_log.event'
                        )
                    )}
                </h3>

                <dl
                    class="
                        mt-3 grid gap-4
                        sm:grid-cols-2
                    "
                >
                    ${detailItem(
                        translate(
                            'activity_log.timestamp'
                        ),
                        formatActivityTimestamp(
                            event.created_at
                        )
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.action'
                        ),
                        humanizeIdentifier(
                            event.action
                        )
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.actor'
                        ),
                        event.actor_name
                        || translate(
                            'activity_log.unknown_actor'
                        )
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.email'
                        ),
                        event.actor_email
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.role'
                        ),
                        roleLabel(
                            event.actor_role
                        )
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.ip_address'
                        ),
                        event.ip_address
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.entity_type'
                        ),
                        humanizeIdentifier(
                            event.entity_type
                        )
                    )}

                    ${detailItem(
                        translate(
                            'activity_log.entity'
                        ),
                        event.entity_label
                        || entityReference(
                            event
                        )
                    )}
                </dl>
            </section>

            ${structuredSection(
                translate(
                    'activity_log.before_values'
                ),
                event.before_values
            )}

            ${structuredSection(
                translate(
                    'activity_log.after_values'
                ),
                event.after_values
            )}

            ${structuredSection(
                translate(
                    'activity_log.snapshot'
                ),
                event.snapshot
            )}

            ${structuredSection(
                translate(
                    'activity_log.metadata'
                ),
                event.metadata
            )}
        </div>
    `;
}

function detailItem(
    label,
    value
) {
    const displayed =
        value === null
        || value === undefined
        || value === ''
            ? translate(
                'activity_log.not_available'
            )
            : String(value);

    return `
        <div
            class="
                rounded-lg
                border border-slate-200
                bg-slate-50/50
                px-4 py-3
            "
        >
            <dt
                class="
                    text-xs font-medium
                    text-slate-500
                "
            >
                ${escapeHtml(label)}
            </dt>

            <dd
                class="
                    mt-1 break-words
                    text-sm text-slate-900
                "
            >
                ${escapeHtml(displayed)}
            </dd>
        </div>
    `;
}

function structuredSection(
    title,
    values
) {
    if (
        values === null
        || values === undefined
        || (
            typeof values === 'object'
            && Object.keys(
                values
            ).length === 0
        )
    ) {
        return '';
    }

    return `
        <section>
            <h3
                class="
                    text-xs font-semibold
                    uppercase tracking-[0.12em]
                    text-slate-500
                "
            >
                ${escapeHtml(title)}
            </h3>

            <div
                class="
                    mt-3 overflow-hidden
                    rounded-lg border
                    border-slate-200
                "
            >
                ${structuredRows(values)}
            </div>
        </section>
    `;
}

function structuredRows(
    values
) {
    if (
        values === null
        || typeof values !== 'object'
    ) {
        return detailStructuredRow(
            '',
            values
        );
    }

    return Object.entries(
        values
    )
        .map(
            ([key, value]) =>
                detailStructuredRow(
                    humanizeIdentifier(
                        key
                    ),
                    value
                )
        )
        .join('');
}

function detailStructuredRow(
    label,
    value
) {
    const display =
        value !== null
        && typeof value === 'object'
            ? JSON.stringify(
                value,
                null,
                2
            )
            : String(
                value
                ?? ''
            );

    return `
        <div
            class="
                grid gap-2
                border-b border-slate-100
                px-4 py-3
                last:border-b-0
                sm:grid-cols-[180px_minmax(0,1fr)]
            "
        >
            ${
                label
                    ? `
                        <div
                            class="
                                text-xs font-medium
                                text-slate-500
                            "
                        >
                            ${escapeHtml(label)}
                        </div>
                    `
                    : ''
            }

            <pre
                class="
                    whitespace-pre-wrap
                    break-words
                    font-sans text-sm
                    text-slate-900
                "
            >${escapeHtml(display)}</pre>
        </div>
    `;
}

function showActivityModal() {
    const modal =
        document.getElementById(
            'activity-log-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.remove(
        'hidden'
    );

    modal.classList.add(
        'flex'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );
}

function closeActivityDetail() {
    const modal =
        document.getElementById(
            'activity-log-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.classList.remove(
        'flex'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Presentation
|--------------------------------------------------------------------------
*/

function roleLabel(
    role
) {
    if (! role) {
        return '';
    }

    const key =
        `roles.${role}`;

    const translated =
        translate(
            key
        );

    return translated === key
        ? humanizeIdentifier(
            role
        )
        : translated;
}

function humanizeIdentifier(
    value
) {
    if (! value) {
        return '';
    }

    return String(value)
        .replaceAll('.', ' ')
        .replaceAll('_', ' ')
        .replace(
            /\b\w/g,
            (character) =>
                character.toUpperCase()
        );
}

function entityReference(
    event
) {
    if (! event?.entity_type) {
        return '';
    }

    const type =
        humanizeIdentifier(
            event.entity_type
        );

    if (! event.entity_id) {
        return type;
    }

    return `${type} #${event.entity_id}`;
}

/**
 * Activity Log timestamps require date AND time.
 *
 * Existing formatDate() intentionally models date-only business values.
 * Activity Log therefore uses the same centralized V1.0.2 browser locale
 * while preserving the event's actual timestamp.
 */
function formatActivityTimestamp(
    value
) {
    if (! value) {
        return '';
    }

    const date =
        new Date(value);

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(value);
    }

    const configuration =
        getPresentationConfiguration();

    return new Intl.DateTimeFormat(
        configuration.browser_locale
        || 'en-GB',
        {
            dateStyle: 'medium',
            timeStyle: 'medium',
        }
    ).format(
        date
    );
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showError(
    message
) {
    const element =
        document.getElementById(
            'activity-log-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        message;

    element.classList.remove(
        'hidden'
    );
}

function hideError() {
    const element =
        document.getElementById(
            'activity-log-error'
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}
