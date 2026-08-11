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
    escapeHtml,
    formValue,
    nullableFormValue,
    parseJsonResponse,
    setText,
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
                text-sm text-slate-400
            "
        >
            Loading parties…
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
                    : 'Unable to load parties.';

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

    const parties =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    /*
     * Maintain an in-memory Party lookup for edit operations.
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

    if (parties.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-xl border
                    border-dashed border-slate-200
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        mx-auto flex h-11 w-11
                        items-center justify-center
                        rounded-full
                        bg-patrimoine-50
                        text-patrimoine-700
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
                        text-slate-900
                    "
                >
                    No parties found
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                >
                    Add a Party or change the current filters.
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        parties
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
        || `Party #${party?.id ?? ''}`;
}

/**
 * Convert database Party type values to user-friendly labels.
 */
function partyTypeLabel(type) {
    switch (type) {
        case 'person':
            return 'Person';

        case 'organisation':
            return 'Organisation';

        case 'association':
            return 'Association';

        default:
            return 'Party';
    }
}

/**
 * Convert functional role values to user-friendly labels.
 */
function partyRoleLabel(role) {
    switch (role) {
        case 'tenant':
            return 'Tenant';

        case 'owner':
            return 'Owner';

        case 'agent':
            return 'Agent';

        case 'managing_organisation':
            return 'Managing Organisation';

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
                        <span
                            class="
                                inline-flex items-center
                                rounded-full
                                bg-patrimoine-50
                                px-2.5 py-1
                                text-xs font-medium
                                text-patrimoine-700
                            "
                        >
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
                <span
                    class="
                        text-xs text-slate-400
                    "
                >
                    No assigned role
                </span>
            `;

    return `
        <article
            class="
                mb-4 overflow-hidden
                rounded-xl border
                border-slate-200 bg-white
                last:mb-0
            "
        >
            <div
                class="
                    flex flex-col gap-5
                    px-5 py-5
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                "
            >
                <div class="min-w-0 flex-1">

                    <div
                        class="
                            flex flex-wrap
                            items-center gap-3
                        "
                    >
                        <h3
                            class="
                                text-base font-semibold
                                text-slate-950
                            "
                        >
                            ${escapeHtml(
                                displayName
                            )}
                        </h3>

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
                                partyTypeLabel(
                                    party.type
                                )
                            )}
                        </span>
                    </div>

                    ${
                        contactName
                            ? `
                                <div
                                    class="
                                        mt-2 text-sm
                                        text-slate-600
                                    "
                                >
                                    Contact:
                                    <span class="font-medium">
                                        ${escapeHtml(
                                            contactName
                                        )}
                                    </span>
                                </div>
                            `
                            : ''
                    }

                    <div
                        class="
                            mt-2 flex flex-wrap
                            gap-x-5 gap-y-1
                            text-sm text-slate-500
                        "
                    >
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
                                    <span>
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

                    <div
                        class="
                            mt-3 flex flex-wrap
                            gap-2
                        "
                    >
                        ${roleBadges}
                    </div>
                </div>

                <div
                    class="
                        flex shrink-0
                        flex-wrap items-center gap-2
                    "
                >
                    <button
                        type="button"
                        data-edit-party
                        data-party-id="${escapeHtml(
                            party.id
                        )}"
                        class="
                            rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        Edit
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
                        class="
                            rounded-lg
                            border border-red-200
                            bg-white px-3.5 py-2
                            text-sm font-medium
                            text-red-600
                            transition
                            hover:bg-red-50
                        "
                    >
                        Delete
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
                        deleteParty(
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
                    text-sm text-slate-500
                "
            >
                Page ${currentPage}
                of ${lastPage}
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
                        rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    Previous
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
                        rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    Next
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
                && ! modal.classList.contains(
                    'hidden'
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
            'party-name'
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
                : 'Unable to load Party.'
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
            ? 'Edit Party'
            : 'Add Party'
    );

    setText(
        'party-modal-description',
        editing
            ? 'Update Party identity, contact details and roles.'
            : 'Create a person, organisation or association.'
    );

    setText(
        'party-submit-button',
        editing
            ? 'Save Changes'
            : 'Create Party'
    );
}

/**
 * Display the Party modal.
 */
function showPartyModal() {
    const modal =
        document.getElementById(
            'party-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.remove(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    updatePartyTypeFields();
}

/**
 * Close and reset the Party modal.
 */
function closePartyModal() {
    const modal =
        document.getElementById(
            'party-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    partyFormMode =
        'create';

    editingPartyId =
        null;
    editingPartyRecord =
    null;

    resetPartyForm();

    configurePartyModal();
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
        'party-name',
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

    setFormValue(
        'party-name',
        party.name
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
        name:
            type === 'person'
                ? nullableFormValue(
                    'party-name'
                )
                : null,

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
                ? 'Saving Changes…'
                : 'Creating Party…';

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
                        ? 'Unable to update Party.'
                        : 'Unable to create Party.'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Create Party';
    }
}

/*
|--------------------------------------------------------------------------
| Party Deletion
|--------------------------------------------------------------------------
*/

/**
 * Delete a Party after user confirmation.
 *
 * Backend foreign-key restrictions and managing-organisation safeguards
 * remain authoritative. Any rejection is displayed to the user.
 */
async function deleteParty(
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

    const confirmed =
        window.confirm(
            `Delete "${partyName || 'this Party'}"?\n\n`
            + 'A Party referenced by leases, ownership or financial records cannot be deleted.'
        );

    if (! confirmed) {
        return;
    }

    try {
        hidePageError();

        const response =
            await apiRequest(
                `/api/parties/${numericPartyId}`,
                {
                    method: 'DELETE',
                }
            );

        /*
         * A successful DELETE returns HTTP 204 and therefore has no JSON
         * body. Only parse a body when the API actually returns one.
         */
        if (
            response.status !== 204
        ) {
            await parseJsonResponse(
                response
            );
        }

        await loadParties(
            1
        );
    } catch (error) {
        showPageError(
            error instanceof Error
                ? error.message
                : 'Unable to delete Party.'
        );
    }
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
