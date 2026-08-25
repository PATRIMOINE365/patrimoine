/*
|--------------------------------------------------------------------------
| Patrimoine Parties
|--------------------------------------------------------------------------
|
| Browser-side functionality for Patrimoine Party management.
|
| A Party is Patrimoine's central identity record and may represent:
|
| - a Person;
| - an Organisation;
| - an Association.
|
| A Party may simultaneously carry several functional roles such as
| Owner, Tenant and Agent.
|
| This module owns:
|
| - Party listing;
| - search;
| - filtering by type;
| - filtering by role;
| - pagination;
| - Party creation;
| - Party editing;
| - Party deletion;
| - Person / Organisation / Association form behaviour;
| - optional identification, banking and notes fields.
|
| Generic API and DOM helpers remain in core.js.
|
*/

import {
    apiRequest,
    closeDrawer,
    escapeHtml,
    formValue,
    nullableFormValue,
    openDrawer,
    parseJsonResponse,
    setText,
    translate,
    wireDrawer,
    requireDangerConfirmation,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Module State
|--------------------------------------------------------------------------
*/

/*
 * Debounce search requests so the API is not called for every individual
 * keystroke.
 */
let partySearchTimer =
    null;

/*
 * Parties returned for the currently rendered page.
 *
 * The map is useful for edit operations because the existing Party data
 * can be accessed by ID without embedding large JSON objects in HTML.
 */
let loadedPartiesById =
    new Map();

/*
 * The Party form is shared by Create and Edit operations.
 */
let partyFormMode =
    'create';

let editingPartyId =
    null;
/*
 * Complete API representation of the Party currently being edited.
 *
 * This is particularly important for preserving system-controlled roles
 * such as managing_organisation.
 */
let editingPartyRecord =
    null;

/*
 * Last successful API payload for the Parties list.
 *
 * Kept so the presentation-only controls (surname sort, has-email filter)
 * can re-render the loaded page without another API request.
 */
let lastPartiesPayload =
    null;

/*
 * The Party currently pending deletion in the confirm drawer.
 */
let deletingPartyId =
    null;

let deletingPartyName =
    null;

/*
 * Open/close controls returned by wireDrawer for the delete drawer.
 */
let deletePartyDrawerControls =
    null;

/*
|--------------------------------------------------------------------------
| Public Initializer
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Parties screen when the current document contains it.
 *
 * The function safely returns when another Patrimoine page is being
 * displayed, allowing app.js to initialize every feature module globally.
 */
export async function initializeParties() {
    const container =
        document.getElementById(
            'parties-list'
        );

    if (! container) {
        return;
    }

    initializePartyFilters();

    initializePartyForm();

    initializeDeletePartyDrawer();

    await loadParties();
}

/*
|--------------------------------------------------------------------------
| Filtering
|--------------------------------------------------------------------------
*/

/**
 * Configure Party search and filter controls.
 */
function initializePartyFilters() {
    const searchInput =
        document.getElementById(
            'party-search'
        );

    const typeFilter =
        document.getElementById(
            'party-type-filter'
        );

    const roleFilter =
        document.getElementById(
            'party-role-filter'
        );

    if (searchInput) {
        searchInput.addEventListener(
            'input',
            () => {
                clearTimeout(
                    partySearchTimer
                );

                partySearchTimer =
                    setTimeout(
                        () => {
                            loadParties(
                                1
                            );
                        },
                        300
                    );
            }
        );
    }

    typeFilter?.addEventListener(
        'change',
        () => {
            loadParties(
                1
            );
        }
    );

    roleFilter?.addEventListener(
        'change',
        () => {
            loadParties(
                1
            );
        }
    );

    /*
     * Presentation-only controls.
     *
     * The has-email filter and the surname sort act on the Parties
     * already loaded for the current page, so they re-render from the
     * cached payload instead of calling the API.
     */
    document
        .getElementById(
            'party-email-filter'
        )
        ?.addEventListener(
            'change',
            rerenderLoadedParties
        );

    document
        .getElementById(
            'party-sort-surname'
        )
        ?.addEventListener(
            'change',
            rerenderLoadedParties
        );
}

/**
 * Re-render the currently loaded page after a presentation-only
 * control (has-email filter, surname sort) changes.
 */
function rerenderLoadedParties() {
    if (lastPartiesPayload) {
        renderParties(
            lastPartiesPayload
        );
    }
}

/**
 * Build query parameters from the current Party controls.
 */
function partyQueryParameters(
    page = 1
) {
    const parameters =
        new URLSearchParams();

    parameters.set(
        'per_page',
        '25'
    );

    parameters.set(
        'page',
        String(page)
    );

    const search =
        formValue(
            'party-search'
        );

    const type =
        formValue(
            'party-type-filter'
        );

    const role =
        formValue(
            'party-role-filter'
        );

    if (search !== '') {
        parameters.set(
            'search',
            search
        );
    }

    if (type !== '') {
        parameters.set(
            'type',
            type
        );
    }

    if (role !== '') {
        parameters.set(
            'role',
            role
        );
    }

    return parameters;
}

/*
|--------------------------------------------------------------------------
| Party Listing
|--------------------------------------------------------------------------
*/

/**
 * Load a paginated Party collection from the API.
 */
async function loadParties(
    page = 1
) {
    const container =
        document.getElementById(
            'parties-list'
        );

    const errorBox =
        document.getElementById(
            'parties-error'
        );

    if (! container) {
        return;
    }

    hidePageError();

    container.innerHTML = `
        <div
            class="
                py-12 text-center
                text-sm text-[var(--pm-text-subtle)]
            "
        >
            ${escapeHtml(
                translate(
                    'parties.loading'
                )
            )}
        </div>
    `;

    try {
        const parameters =
            partyQueryParameters(
                page
            );

        const response =
            await apiRequest(
                `/api/parties?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderParties(
            payload
        );

        renderPartyPagination(
            payload
        );
    } catch (error) {
        container.innerHTML = '';

        if (errorBox) {
            errorBox.textContent =
                error instanceof Error
                    ? error.message
                    : translate(
                        'parties.unable_to_load'
                    );

            errorBox.classList.remove(
                'hidden'
            );
        }
    }
}

/**
 * Render Party records returned by the API.
 */
function renderParties(payload) {
    const container =
        document.getElementById(
            'parties-list'
        );

    if (! container) {
        return;
    }

    lastPartiesPayload =
        payload;

    const parties =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    /*
     * Maintain an in-memory Party lookup for edit operations.
     *
     * The lookup always covers the full loaded page, regardless of the
     * presentation-only filter below.
     */
    loadedPartiesById =
        new Map(
            parties.map(
                (party) => [
                    String(party.id),
                    party,
                ]
            )
        );

    updatePartyMetrics(
        payload,
        parties
    );

    /*
     * Presentation-only pass: has-email filter, then surname sort.
     *
     * Both act on the loaded page only; server search, filters and
     * ordering remain authoritative.
     */
    const displayedParties =
        presentLoadedParties(
            parties
        );

    if (displayedParties.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-xl border
                    border-dashed border-[var(--pm-border)]
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        mx-auto flex h-11 w-11
                        items-center justify-center
                        rounded-full
                        bg-[var(--pm-surface-muted)]
                        text-[var(--pm-accent)]
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    </svg>
                </div>

                <div
                    class="
                        mt-4 text-sm font-medium
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'parties.no_parties_found'
                        )
                    )}
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-[var(--pm-text-muted)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'parties.empty_description'
                        )
                    )}
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        displayedParties
            .map(
                (party) =>
                    partyCard(
                        party
                    )
            )
            .join('');

    attachPartyActionListeners(
        container
    );
}

/**
 * Apply the presentation-only has-email filter and surname sort to the
 * Parties loaded for the current page.
 */
function presentLoadedParties(
    parties
) {
    let displayed =
        [...parties];

    const emailFilter =
        formValue(
            'party-email-filter'
        );

    if (emailFilter === 'yes') {
        displayed =
            displayed.filter(
                partyHasEmail
            );
    } else if (emailFilter === 'no') {
        displayed =
            displayed.filter(
                (party) =>
                    ! partyHasEmail(
                        party
                    )
            );
    }

    const sortToggle =
        document.getElementById(
            'party-sort-surname'
        );

    if (sortToggle?.checked) {
        displayed.sort(
            (first, second) =>
                surnameSortKey(
                    first
                ).localeCompare(
                    surnameSortKey(
                        second
                    ),
                    undefined,
                    {
                        sensitivity:
                            'base',
                    }
                )
        );
    }

    return displayed;
}

/**
 * Whether a Party has any deliverable email address on file.
 */
function partyHasEmail(party) {
    return Boolean(
        party?.email
        || party?.contact_person_email
    );
}

/**
 * Sort key for the presentation-only surname sort.
 *
 * Falls back to the composed display name for Parties without a stored
 * surname (organisations, associations, legacy people).
 */
function surnameSortKey(party) {
    return String(
        party?.surname
        || party?.name
        || party?.legal_name
        || ''
    ).trim();
}

/**
 * Update summary cards using the Parties visible in the current result set.
 *
 * The total Party count comes from Laravel pagination while the role/type
 * figures represent the currently loaded page.
 */
function updatePartyMetrics(
    payload,
    parties
) {
    const total =
        Number(
            payload?.total
            ?? parties.length
        );

    const people =
        parties.filter(
            (party) =>
                party.type === 'person'
        ).length;

    const organisations =
        parties.filter(
            (party) =>
                party.type === 'organisation'
                || party.type === 'association'
        ).length;

    const multiRole =
        parties.filter(
            (party) =>
                partyRoles(
                    party
                ).length > 1
        ).length;

    setText(
        'parties-total-count',
        total
    );

    setText(
        'parties-person-count',
        people
    );

    setText(
        'parties-organisation-count',
        organisations
    );

    setText(
        'parties-multi-role-count',
        multiRole
    );
}

/**
 * Return normalized role names for a Party.
 */
function partyRoles(party) {
    if (
        ! Array.isArray(
            party?.roles
        )
    ) {
        return [];
    }

    return party.roles
        .map(
            (role) =>
                typeof role === 'string'
                    ? role
                    : role?.role
        )
        .filter(Boolean);
}

/**
 * Return the primary display name for a Party.
 */
function partyDisplayName(party) {
    return party?.name
        || party?.legal_name
        || `${translate(
            'parties.party'
        )} #${party?.id ?? ''}`;
}

/**
 * Convert database Party type values to user-friendly labels.
 */
function partyTypeLabel(type) {
    switch (type) {
        case 'person':
            return translate(
                'parties.person'
            );

        case 'organisation':
            return translate(
                'parties.organisation'
            );

        case 'association':
            return translate(
                'parties.association'
            );

        default:
            return translate(
                'parties.party'
            );
    }
}

/**
 * Convert functional role values to user-friendly labels.
 */
function partyRoleLabel(role) {
    switch (role) {
        case 'tenant':
            return translate(
                'parties.tenant'
            );

        case 'owner':
            return translate(
                'parties.owner'
            );

        case 'agent':
            return translate(
                'parties.agent'
            );

        case 'managing_organisation':
            return translate(
                'parties.managing_organisation'
            );

        default:
            return String(role || '');
    }
}

/**
 * Render one Party card.
 */
function partyCard(party) {
    const roles =
        partyRoles(
            party
        );

    const displayName =
        partyDisplayName(
            party
        );

    const email =
        party.type === 'person'
            ? party.email
            : party.contact_person_email;

    const phone =
        party.type === 'person'
            ? party.phone
            : party.contact_person_phone;

    const contactName =
        party.type !== 'person'
            ? party.contact_person_name
            : null;

    const roleBadges =
        roles.length > 0
            ? roles
                .map(
                    (role) => `
                        <span class="pm-party-role-badge">
                            ${escapeHtml(
                                partyRoleLabel(
                                    role
                                )
                            )}
                        </span>
                    `
                )
                .join('')
            : `
                <span class="pm-party-no-role">
                    ${escapeHtml(
                        translate(
                            'parties.no_assigned_role'
                        )
                    )}
                </span>
            `;

    return `
        <article class="pm-party-row">
            <div class="pm-party-row-main">
                <div class="min-w-0 flex-1">
                    <div class="pm-party-title-line">
                        <h3 class="pm-party-name">
                            ${escapeHtml(
                                displayName
                            )}
                        </h3>

                        <span class="pm-party-type-badge">
                            ${escapeHtml(
                                partyTypeLabel(
                                    party.type
                                )
                            )}
                        </span>
                    </div>

                    ${
                        contactName
                            ? `
                                <div class="pm-party-contact">
                                    ${escapeHtml(
                                        translate(
                                            'parties.contact'
                                        )
                                    )}:
                                    <span class="font-medium">
                                        ${escapeHtml(
                                            contactName
                                        )}
                                    </span>
                                </div>
                            `
                            : ''
                    }

                    <div class="pm-party-meta">
                        ${
                            phone
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            phone
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            email
                                ? `
                                    <span class="break-all">
                                        ${escapeHtml(
                                            email
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        ${
                            party.address
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            party.address
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </div>

                    <div class="pm-party-roles">
                        ${roleBadges}
                    </div>
                </div>

                <div class="pm-party-actions">
                    <button
                        type="button"
                        data-edit-party
                        data-party-id="${escapeHtml(
                            party.id
                        )}"
                        class="pm-button-secondary pm-party-action"
                    >
                        ${escapeHtml(
                            translate(
                                'parties.edit'
                            )
                        )}
                    </button>

                    <button
                        type="button"
                        data-delete-party
                        data-party-id="${escapeHtml(
                            party.id
                        )}"
                        data-party-name="${escapeHtml(
                            displayName
                        )}"
                        class="pm-button-danger-outline pm-party-action"
                    >
                        ${escapeHtml(
                            translate(
                                'parties.delete'
                            )
                        )}
                    </button>
                </div>
            </div>
        </article>
    `;
}

/**
 * Attach action listeners after Party cards are rendered.
 */
function attachPartyActionListeners(
    container
) {
    container
        .querySelectorAll(
            '[data-edit-party]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openEditPartyModal(
                            button.dataset
                                .partyId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-delete-party]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openDeletePartyModal(
                            button.dataset
                                .partyId,

                            button.dataset
                                .partyName
                        );
                    }
                );
            }
        );
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

/**
 * Render Laravel paginator navigation.
 */
function renderPartyPagination(
    payload
) {
    const container =
        document.getElementById(
            'parties-pagination'
        );

    if (! container) {
        return;
    }

    const currentPage =
        Number(
            payload?.current_page
            ?? 1
        );

    const lastPage =
        Number(
            payload?.last_page
            ?? 1
        );

    if (lastPage <= 1) {
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
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'parties.page'
                    )
                )} ${currentPage}
                ${escapeHtml(
                    translate(
                        'parties.of'
                    )
                )} ${lastPage}
            </div>

            <div class="flex gap-2">

                <button
                    id="parties-previous"
                    type="button"
                    ${
                        currentPage <= 1
                            ? 'disabled'
                            : ''
                    }
                    class="
                        pm-button-secondary
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'parties.previous'
                        )
                    )}
                </button>

                <button
                    id="parties-next"
                    type="button"
                    ${
                        currentPage >= lastPage
                            ? 'disabled'
                            : ''
                    }
                    class="
                        pm-button-secondary
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'parties.next'
                        )
                    )}
                </button>

            </div>
        </div>
    `;

    document
        .getElementById(
            'parties-previous'
        )
        ?.addEventListener(
            'click',
            () => {
                if (currentPage > 1) {
                    loadParties(
                        currentPage - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'parties-next'
        )
        ?.addEventListener(
            'click',
            () => {
                if (
                    currentPage < lastPage
                ) {
                    loadParties(
                        currentPage + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Party Create / Edit Modal
|--------------------------------------------------------------------------
*/

/**
 * Configure the shared Party modal.
 */
function initializePartyForm() {
    const modal =
        document.getElementById(
            'party-modal'
        );

    const form =
        document.getElementById(
            'party-form'
        );

    const openButton =
        document.getElementById(
            'add-party-button'
        );

    if (
        ! modal
        || ! form
        || ! openButton
    ) {
        return;
    }

    openButton.addEventListener(
        'click',
        openCreatePartyModal
    );

    document
        .getElementById(
            'party-modal-close'
        )
        ?.addEventListener(
            'click',
            closePartyModal
        );

    document
        .getElementById(
            'party-cancel-button'
        )
        ?.addEventListener(
            'click',
            closePartyModal
        );

    document
        .getElementById(
            'party-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closePartyModal
        );

    document
        .getElementById(
            'party-type'
        )
        ?.addEventListener(
            'change',
            updatePartyTypeFields
        );

    form.addEventListener(
        'submit',
        submitPartyForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && modal.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closePartyModal();
            }
        }
    );
}

/**
 * Open the modal for creation.
 */
function openCreatePartyModal() {
    resetPartyForm();

    partyFormMode =
        'create';

    editingPartyId =
        null;
    editingPartyRecord =
    null;

    configurePartyModal();

    showPartyModal();

    document
        .getElementById(
            'party-given-names'
        )
        ?.focus();
}

/**
 * Open the modal for an existing Party.
 *
 * The complete Party record is fetched from the API before the form is
 * populated. This ensures Edit always works with current Party data and
 * preserves system-controlled roles such as managing_organisation.
 */
async function openEditPartyModal(
    partyId
) {
    const numericPartyId =
        Number(
            partyId
        );

    if (
        ! Number.isInteger(
            numericPartyId
        )
        || numericPartyId <= 0
    ) {
        return;
    }

    resetPartyForm();

    partyFormMode =
        'edit';

    editingPartyId =
        numericPartyId;

    /*
     * Clear any previously edited Party while the fresh API record loads.
     */
    editingPartyRecord =
        null;

    configurePartyModal();

    showPartyModal();

    try {
        /*
         * Fetch the current API representation instead of relying solely on
         * the paginated list. This prevents stale Party or role data from
         * being edited.
         */
        const response =
            await apiRequest(
                `/api/parties/${numericPartyId}`
            );

        const party =
            await parseJsonResponse(
                response
            );

        /*
         * Keep the complete Party record available while editing.
         *
         * buildPartyPayload() uses this record to preserve protected roles
         * such as managing_organisation when necessary.
         */
        editingPartyRecord =
            party;

        populatePartyForm(
            party
        );
    } catch (error) {
        showPartyFormError(
            error instanceof Error
                ? error.message
                : translate(
                    'parties.unable_to_load_party'
                )
        );
    }
}

/**
 * Apply create/edit wording.
 */
function configurePartyModal() {
    const editing =
        partyFormMode === 'edit';

    setText(
        'party-modal-title',
        editing
            ? translate(
                'parties.edit_party'
            )
            : translate(
                'parties.add_party'
            )
    );

    setText(
        'party-modal-description',
        editing
            ? translate(
                'parties.edit_party_description'
            )
            : translate(
                'parties.add_party_description'
            )
    );

    setText(
        'party-submit-button',
        translate(
            'actions.save'
        )
    );
}

/**
 * Display the Party modal.
 *
 * The open/close transition itself is owned by core.js
 * (openDrawer / closeDrawer).
 */
function showPartyModal() {
    openDrawer(
        'party-modal'
    );

    updatePartyTypeFields();
}

/**
 * Close and reset the Party modal.
 */
function closePartyModal() {
    closeDrawer(
        'party-modal',
        {
            /*
             * Reset only after the slide-out completes so the form does
             * not visibly clear while the drawer is still on screen.
             */
            onClosed: () => {
                partyFormMode =
                    'create';

                editingPartyId =
                    null;

                editingPartyRecord =
                    null;

                resetPartyForm();

                configurePartyModal();
            },
        }
    );
}

/**
 * Reset all Party form state.
 */
function resetPartyForm() {
    document
        .getElementById(
            'party-form'
        )
        ?.reset();

    hidePartyFormError();

    /*
     * The normal default is a Person.
     */
    const type =
        document.getElementById(
            'party-type'
        );

    if (type) {
        type.value =
            'person';
    }

    updatePartyTypeFields();
}

/**
 * Toggle type-specific form fields.
 */
function updatePartyTypeFields() {
    const type =
        formValue(
            'party-type'
        )
        || 'person';

    const person =
        type === 'person';

    document
        .getElementById(
            'party-person-fields'
        )
        ?.classList.toggle(
            'hidden',
            ! person
        );

    document
        .getElementById(
            'party-organisation-fields'
        )
        ?.classList.toggle(
            'hidden',
            person
        );

    /*
     * Required attributes mirror the backend validation rules.
     *
     * Hidden fields should not remain browser-required.
     */
    setRequired(
        'party-given-names',
        person
    );

    setRequired(
        'party-surname',
        person
    );

    setRequired(
        'party-phone',
        person
    );

    setRequired(
        'party-email',
        person
    );

    setRequired(
        'party-legal-name',
        ! person
    );

    setRequired(
        'party-contact-name',
        ! person
    );

    setRequired(
        'party-contact-phone',
        ! person
    );

    setRequired(
        'party-contact-email',
        ! person
    );
}

/**
 * Safely toggle an input's required state.
 */
function setRequired(
    id,
    required
) {
    const element =
        document.getElementById(id);

    if (element) {
        element.required =
            required;
    }
}

/**
 * Populate the shared form for editing.
 */
function populatePartyForm(party) {
    setFormValue(
        'party-type',
        party.type
        || 'person'
    );

    updatePartyTypeFields();

    /*
     * V1.0.7 structured person names.
     *
     * The API returns given_names and surname alongside the composed
     * display name. Legacy people created before the split may carry
     * only `name`; derive a best-effort split (last token → surname)
     * so the required fields are pre-filled for correction on save.
     */
    let givenNames =
        party.given_names
        ?? '';

    let surname =
        party.surname
        ?? '';

    if (
        party.type === 'person'
        && givenNames === ''
        && surname === ''
        && typeof party.name === 'string'
        && party.name.trim() !== ''
    ) {
        const tokens =
            party.name
                .trim()
                .split(/\s+/);

        surname =
            tokens.length > 1
                ? tokens[tokens.length - 1]
                : tokens[0];

        givenNames =
            tokens
                .slice(
                    0,
                    -1
                )
                .join(' ');
    }

    setFormValue(
        'party-given-names',
        givenNames
    );

    setFormValue(
        'party-surname',
        surname
    );

    setFormValue(
        'party-legal-name',
        party.legal_name
    );

    setFormValue(
        'party-phone',
        party.phone
    );

    setFormValue(
        'party-alternate-phone',
        party.alternate_phone
    );

    setFormValue(
        'party-email',
        party.email
    );

    setFormValue(
        'party-address',
        party.address
    );

    setFormValue(
        'party-contact-name',
        party.contact_person_name
    );

    setFormValue(
        'party-contact-phone',
        party.contact_person_phone
    );

    setFormValue(
        'party-contact-email',
        party.contact_person_email
    );

    setFormValue(
        'party-id-number',
        party.id_number
    );

    setFormValue(
        'party-registration-number',
        party.registration_number
    );

    setFormValue(
        'party-vat-tin',
        party.vat_tin
    );

    setFormValue(
        'party-bank-name',
        party.bank_name
    );

    setFormValue(
        'party-bank-account-name',
        party.bank_account_name
    );

    setFormValue(
        'party-bank-account-number',
        party.bank_account_number
    );

    setFormValue(
        'party-bank-branch',
        party.bank_branch
    );

    setFormValue(
        'party-notes',
        party.notes
    );

    const roles =
        partyRoles(
            party
        );

    /*
     * managing_organisation is deliberately not user-editable here.
     *
     * We preserve it automatically when editing an existing Party carrying
     * that role.
     */
    setCheckbox(
        'party-role-owner',
        roles.includes(
            'owner'
        )
    );

    setCheckbox(
        'party-role-tenant',
        roles.includes(
            'tenant'
        )
    );

    setCheckbox(
        'party-role-agent',
        roles.includes(
            'agent'
        )
    );
}

/**
 * Assign a form control value safely.
 */
function setFormValue(
    id,
    value
) {
    const element =
        document.getElementById(id);

    if (element) {
        element.value =
            value ?? '';
    }
}

/**
 * Assign a checkbox state safely.
 */
function setCheckbox(
    id,
    checked
) {
    const element =
        document.getElementById(id);

    if (element) {
        element.checked =
            checked;
    }
}

/*
|--------------------------------------------------------------------------
| Party Form Submission
|--------------------------------------------------------------------------
*/

/**
 * Collect normal user-selectable Party roles.
 */
function collectPartyRoles() {
    const roles =
        [];

    if (
        document
            .getElementById(
                'party-role-owner'
            )
            ?.checked
    ) {
        roles.push(
            'owner'
        );
    }

    if (
        document
            .getElementById(
                'party-role-tenant'
            )
            ?.checked
    ) {
        roles.push(
            'tenant'
        );
    }

    if (
        document
            .getElementById(
                'party-role-agent'
            )
            ?.checked
    ) {
        roles.push(
            'agent'
        );
    }

    /*
     * Preserve the protected application identity role on existing Parties.
     *
     * This prevents a generic Edit Party operation from accidentally
     * stripping managing_organisation from the configured organisation.
     */
    if (
        partyFormMode === 'edit'
        && editingPartyId !== null
    ) {



if (
    partyRoles(
        editingPartyRecord
    ).includes(
        'managing_organisation'
    )
)

        {
            roles.push(
                'managing_organisation'
            );
        }
    }

    return roles;
}

/**
 * Build a complete API representation from the current form.
 */
function buildPartyPayload() {
    const type =
        formValue(
            'party-type'
        );

    const payload = {
        type,

        /*
         * Fields not applicable to the selected Party type are explicitly
         * sent as null so converting between Person and Organisation does
         * not retain stale identity information.
         */
        /*
         * V1.0.7 structured person names.
         *
         * People send given_names + surname and omit `name`; the
         * display name is composed server-side. Organisations and
         * associations clear all person identity fields so a type
         * change does not retain stale information.
         */
        ...(
            type === 'person'
                ? {
                    given_names:
                        nullableFormValue(
                            'party-given-names'
                        ),

                    surname:
                        nullableFormValue(
                            'party-surname'
                        ),
                }
                : {
                    name:
                        null,

                    given_names:
                        null,

                    surname:
                        null,
                }
        ),

        legal_name:
            type === 'person'
                ? null
                : nullableFormValue(
                    'party-legal-name'
                ),

        phone:
            type === 'person'
                ? nullableFormValue(
                    'party-phone'
                )
                : null,

        alternate_phone:
            nullableFormValue(
                'party-alternate-phone'
            ),

        email:
            type === 'person'
                ? nullableFormValue(
                    'party-email'
                )
                : null,

        address:
            nullableFormValue(
                'party-address'
            ),

        contact_person_name:
            type === 'person'
                ? null
                : nullableFormValue(
                    'party-contact-name'
                ),

        contact_person_phone:
            type === 'person'
                ? null
                : nullableFormValue(
                    'party-contact-phone'
                ),

        contact_person_email:
            type === 'person'
                ? null
                : nullableFormValue(
                    'party-contact-email'
                ),

        id_number:
            nullableFormValue(
                'party-id-number'
            ),

        registration_number:
            nullableFormValue(
                'party-registration-number'
            ),

        vat_tin:
            nullableFormValue(
                'party-vat-tin'
            ),

        bank_name:
            nullableFormValue(
                'party-bank-name'
            ),

        bank_account_name:
            nullableFormValue(
                'party-bank-account-name'
            ),

        bank_account_number:
            nullableFormValue(
                'party-bank-account-number'
            ),

        bank_branch:
            nullableFormValue(
                'party-bank-branch'
            ),

        notes:
            nullableFormValue(
                'party-notes'
            ),

        roles:
            collectPartyRoles(),
    };

    return payload;
}

/**
 * Create or update a Party.
 */
async function submitPartyForm(event) {
    event.preventDefault();

    const form =
        document.getElementById(
            'party-form'
        );

    const submitButton =
        document.getElementById(
            'party-submit-button'
        );

    if (
        ! form
        || ! submitButton
    ) {
        return;
    }

    hidePartyFormError();

    if (! form.reportValidity()) {
        return;
    }

    const editing =
        partyFormMode === 'edit'
        && Number.isInteger(
            editingPartyId
        );

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            editing
                ? translate(
                    'parties.saving_changes'
                )
                : translate(
                    'parties.creating_party'
                );

        const payload =
            buildPartyPayload();

        const endpoint =
            editing
                ? `/api/parties/${editingPartyId}`
                : '/api/parties';

        const response =
            await apiRequest(
                endpoint,
                {
                    method:
                        editing
                            ? 'PATCH'
                            : 'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closePartyModal();

        await loadParties(
            1
        );
    } catch (error) {
        showPartyFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? translate(
                            'parties.unable_to_update_party'
                        )
                        : translate(
                            'parties.unable_to_create_party'
                        )
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            editing
                ? translate(
                    'parties.save_changes'
                )
                : translate(
                    'parties.create_party'
                );
    }
}

/*
|--------------------------------------------------------------------------
| Party Deletion
|--------------------------------------------------------------------------
*/

/**
 * Wire the Delete Party confirm drawer once at page initialization.
 *
 * The drawer follows the shared core.js lifecycle: backdrop click, the
 * header close button, the Cancel button and Escape all dismiss it.
 */
function initializeDeletePartyDrawer() {
    const drawer =
        document.getElementById(
            'party-delete-modal'
        );

    if (! drawer) {
        return;
    }

    deletePartyDrawerControls =
        wireDrawer(
            drawer,
            {
                closers: [
                    'party-delete-modal-close',
                    'party-delete-cancel',
                ],

                onClose: () => {
                    deletingPartyId =
                        null;

                    deletingPartyName =
                        null;

                    hideDeletePartyError();
                },
            }
        );

    document
        .getElementById(
            'party-delete-confirm'
        )
        ?.addEventListener(
            'click',
            confirmDeleteParty
        );
}

/**
 * Open the Delete Party confirm drawer for one Party.
 */
function openDeletePartyModal(
    partyId,
    partyName
) {
    const numericPartyId =
        Number(
            partyId
        );

    if (
        ! Number.isInteger(
            numericPartyId
        )
        || numericPartyId <= 0
    ) {
        return;
    }

    deletingPartyId =
        numericPartyId;

    deletingPartyName =
        partyName
        || translate(
            'parties.this_party'
        );

    hideDeletePartyError();

    setText(
        'party-delete-name',
        deletingPartyName
    );

    if (deletePartyDrawerControls) {
        deletePartyDrawerControls.open();
    } else {
        openDrawer(
            'party-delete-modal'
        );
    }
}

/**
 * Delete the Party pending in the confirm drawer.
 *
 * Backend foreign-key restrictions and managing-organisation safeguards
 * remain authoritative. A 409 rejection reason from the server is
 * surfaced inside the drawer so the user understands why the Party
 * must be retained.
 */
async function confirmDeleteParty() {
    if (
        ! Number.isInteger(
            deletingPartyId
        )
        || deletingPartyId <= 0
    ) {
        return;
    }

    const confirmButton =
        document.getElementById(
            'party-delete-confirm'
        );

    try {
        hideDeletePartyError();

        if (confirmButton) {
            confirmButton.disabled =
                true;

            confirmButton.textContent =
                translate(
                    'parties.deleting_party'
                );
        }

        if (
            ! await requireDangerConfirmation({
                entityLabel: String(deletingPartyName ?? ""),
            })
        ) {
            return;
        }

        const response =
            await apiRequest(
                `/api/parties/${deletingPartyId}`,
                {
                    method: 'DELETE',
                }
            );

        /*
         * A successful DELETE returns HTTP 204 and therefore has no JSON
         * body. Only parse a body when the API actually returns one.
         * A 409 rejection carries a message explaining the restriction.
         */
        if (
            response.status !== 204
        ) {
            await parseJsonResponse(
                response
            );
        }

        if (deletePartyDrawerControls) {
            deletePartyDrawerControls.close();
        } else {
            closeDrawer(
                'party-delete-modal'
            );
        }

        await loadParties(
            1
        );
    } catch (error) {
        showDeletePartyError(
            error instanceof Error
                ? error.message
                : translate(
                    'parties.unable_to_delete_party'
                )
        );
    } finally {
        if (confirmButton) {
            confirmButton.disabled =
                false;

            confirmButton.textContent =
                translate(
                    'parties.delete_party'
                );
        }
    }
}

/**
 * Show the server rejection reason inside the delete drawer.
 */
function showDeletePartyError(
    message
) {
    const box =
        document.getElementById(
            'party-delete-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );
}

/**
 * Clear the delete drawer error.
 */
function hideDeletePartyError() {
    const box =
        document.getElementById(
            'party-delete-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Error Helpers
|--------------------------------------------------------------------------
*/

/**
 * Display a form-level validation or API error.
 */
function showPartyFormError(
    message
) {
    const box =
        document.getElementById(
            'party-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
}

/**
 * Hide the Party modal error.
 */
function hidePartyFormError() {
    const box =
        document.getElementById(
            'party-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}

/**
 * Show an error affecting the Parties page rather than the modal.
 */
function showPageError(message) {
    const box =
        document.getElementById(
            'parties-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
}

/**
 * Clear page-level errors.
 */
function hidePageError() {
    const box =
        document.getElementById(
            'parties-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}
