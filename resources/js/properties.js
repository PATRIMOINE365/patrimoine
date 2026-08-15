/*
|--------------------------------------------------------------------------
| Patrimoine Properties
|--------------------------------------------------------------------------
|
| Browser-side functionality for Patrimoine property management.
|
| This module owns the complete Properties UI:
|
| - property portfolio listing;
| - search and pagination;
| - property summary metrics;
| - unit expansion;
| - property creation;
| - property editing;
| - inline Owner Party creation;
| - adding Units to existing Buildings;
| - editing existing Units.
|
| API and generic DOM utilities live in core.js. Keeping all property
| workflows together avoids fragmenting one functional area across many
| very small files.
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

let propertySearchTimer =
    null;

/*
 * Buildings returned for the currently rendered property page.
 *
 * This allows Unit editing to use the loaded API representation without
 * embedding complete JSON objects into HTML attributes.
 */
let loadedPropertiesById =
    new Map();

/*
 * Owner Parties available to property ownership forms.
 */
let availableOwnerParties =
    [];

/*
 * Property form state.
 */
let propertyFormMode =
    'create';

let editingPropertyId =
    null;

/*
 * Inline owner creation state.
 */
let ownerTargetRow =
    null;

/*
 * Existing Unit modal state.
 */
let existingUnitFormMode =
    'create';

let editingUnitId =
    null;

/*
|--------------------------------------------------------------------------
| Properties Page Initialization
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Properties page when present.
 *
 * All sub-workflows are initialized here so app.js only needs to know
 * about one Properties entry point.
 */
export async function initializeProperties() {
    const container =
        document.getElementById(
            'properties-list'
        );

    if (! container) {
        return;
    }

    const searchInput =
        document.getElementById(
            'property-search'
        );

    if (searchInput) {
        searchInput.addEventListener(
            'input',
            () => {
                clearTimeout(
                    propertySearchTimer
                );

                propertySearchTimer =
                    setTimeout(
                        () => {
                            loadProperties(
                                searchInput
                                    .value
                                    .trim()
                            );
                        },
                        300
                    );
            }
        );
    }

    initializePropertyCreation();
    initializeOwnerCreation();
    initializeExistingUnitCreation();

    await loadProperties();
}

/*
|--------------------------------------------------------------------------
| Property Portfolio Loading
|--------------------------------------------------------------------------
*/

/**
 * Load one paginated page of Buildings.
 *
 * @param {string} search
 * @param {number} page
 */
async function loadProperties(
    search = '',
    page = 1
) {
    const container =
        document.getElementById(
            'properties-list'
        );

    const errorBox =
        document.getElementById(
            'properties-error'
        );

    if (! container) {
        return;
    }

    if (errorBox) {
        errorBox.classList.add(
            'hidden'
        );

        errorBox.textContent =
            '';
    }

    container.innerHTML = `
        <div
            class="
                py-10 text-center
                text-sm text-slate-400
            "
        >
            Loading properties…
        </div>
    `;

    try {
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

        if (search !== '') {
            parameters.set(
                'search',
                search
            );
        }

        const response =
            await apiRequest(
                `/api/buildings?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderProperties(
            payload,
            search
        );

        renderPropertiesPagination(
            payload,
            search
        );
    } catch (error) {
        container.innerHTML =
            '';

        if (errorBox) {
            errorBox.textContent =
                error instanceof Error
                    ? error.message
                    : 'Unable to load properties.';

            errorBox.classList.remove(
                'hidden'
            );
        }
    }
}

/**
 * Render Buildings and attach interactions to generated controls.
 *
 * When a search matches one or more Units, the parent Building remains
 * the normal search result but its Unit panel is automatically expanded
 * so the matching Unit is immediately visible.
 *
 * @param {object} payload
 * @param {string} search
 */
function renderProperties(
    payload,
    search = ''
) {
    const container =
        document.getElementById(
            'properties-list'
        );

    if (! container) {
        return;
    }

    const buildings =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    loadedPropertiesById =
        new Map(
            buildings.map(
                (building) => [
                    String(
                        building.id
                    ),
                    building,
                ]
            )
        );

    updatePropertyMetrics(
        payload,
        buildings
    );

    if (
        buildings.length === 0
    ) {
        container.innerHTML = `
            <div
                class="
                    rounded-lg border
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
                        <path d="M3 21h18"/>
                        <path d="M6 21V5l6-2 6 2v16"/>
                    </svg>
                </div>

                <div
                    class="
                        mt-4 text-sm font-medium
                        text-slate-900
                    "
                >
                    No properties found
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                >
                    Add a property or change your search.
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        buildings
            .map(
                (building) =>
                    propertyCard(
                        building,
                        search
                    )
            )
            .join('');

    bindRenderedPropertyActions(
        container
    );
}

/**
 * Attach event listeners to controls generated inside property cards.
 *
 * @param {HTMLElement} container
 */
function bindRenderedPropertyActions(
    container
) {
    container
        .querySelectorAll(
            '[data-property-toggle]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        togglePropertyUnits(
                            button
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-add-existing-unit]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openExistingUnitModal(
                            button.dataset
                                .buildingId,

                            button.dataset
                                .buildingName
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-edit-property]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openEditPropertyModal(
                            button.dataset
                                .buildingId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-edit-unit]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openExistingUnitModal(
                            button.dataset
                                .buildingId,

                            button.dataset
                                .buildingName,

                            button.dataset
                                .unitId
                        );
                    }
                );
            }
        );
}

/*
|--------------------------------------------------------------------------
| Property Summary Metrics
|--------------------------------------------------------------------------
*/

/**
 * Update summary cards above the portfolio.
 *
 * @param {object} payload
 * @param {Array} buildings
 */
function updatePropertyMetrics(
    payload,
    buildings
) {
    const totalBuildings =
        Number(
            payload?.total
            ?? buildings.length
        );

    /*
     * The currently loaded API page includes Unit relationships for each
     * Building. This produces accurate V1 totals while the portfolio fits
     * within the configured page size.
     */
    const totalUnits =
        buildings.reduce(
            (
                total,
                building
            ) =>
                total
                + (
                    Array.isArray(
                        building.units
                    )
                        ? building
                            .units
                            .length
                        : 0
                ),
            0
        );

    const singleUnit =
        buildings.filter(
            (building) =>
                Array.isArray(
                    building.units
                )
                && building.units
                    .length === 1
        ).length;

    const multiUnit =
        buildings.filter(
            (building) =>
                Array.isArray(
                    building.units
                )
                && building.units
                    .length > 1
        ).length;

    setText(
        'properties-building-count',
        totalBuildings
    );

    setText(
        'properties-unit-count',
        totalUnits
    );

    setText(
        'properties-single-unit-count',
        singleUnit
    );

    setText(
        'properties-multi-unit-count',
        multiUnit
    );
}

/*
|--------------------------------------------------------------------------
| Property Card Rendering
|--------------------------------------------------------------------------
*/

/**
 * Generate the HTML representation of a Building.
 *
 * A Unit match automatically expands the Unit panel. Building-only
 * matches retain the normal collapsed presentation.
 *
 * @param {object} building
 * @param {string} search
 * @returns {string}
 */
function propertyCard(
    building,
    search = ''
) {
    const units =
        Array.isArray(
            building.units
        )
            ? building.units
            : [];

    const normalizedSearch =
        normalizePropertySearch(
            search
        );

    const matchingUnitIds =
        new Set(
            normalizedSearch === ''
                ? []
                : units
                    .filter(
                        (unit) =>
                            propertyUnitMatchesSearch(
                                unit,
                                normalizedSearch
                            )
                    )
                    .map(
                        (unit) =>
                            String(
                                unit.id
                            )
                    )
        );

    const expandUnits =
        matchingUnitIds.size > 0;

    const ownerships =
        Array.isArray(
            building.ownerships
        )
            ? building.ownerships
            : [];

    const address =
        building.address
        || building.location
        || 'No address provided';

    const buildingName =
        building.name
        || 'Unnamed Property';

    const owners =
        renderPropertyOwners(
            ownerships
        );

    const unitRows =
        renderPropertyUnits(
            building,
            units,
            buildingName,
            matchingUnitIds
        );

    return `
        <article
            class="
                mb-4 overflow-hidden
                rounded-xl border border-slate-200
                bg-white
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
                <div
                    class="
                        min-w-0 flex-1
                    "
                >
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
                                buildingName
                            )}
                        </h3>

                        <span
                            class="
                                inline-flex rounded-full
                                bg-patrimoine-50
                                px-2.5 py-1
                                text-xs font-medium
                                text-patrimoine-700
                            "
                        >
                            ${units.length}
                            ${
                                units.length === 1
                                    ? 'unit'
                                    : 'units'
                            }
                        </span>
                    </div>

                    <div
                        class="
                            mt-1 flex items-center gap-2
                            text-sm text-slate-500
                        "
                    >
                        <svg
                            class="h-4 w-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2"/>
                        </svg>

                        <span
                            class="truncate"
                        >
                            ${escapeHtml(
                                address
                            )}
                        </span>
                    </div>

                    <div
                        class="
                            mt-3 flex flex-wrap
                            gap-2
                        "
                    >
                        ${owners}
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
                        data-edit-property
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        class="
                            inline-flex items-center gap-2
                            rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                        </svg>

                        Edit
                    </button>

                    <button
                        type="button"
                        data-add-existing-unit
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        data-building-name="${escapeHtml(
                            buildingName
                        )}"
                        class="
                            inline-flex items-center gap-2
                            rounded-lg
                            bg-patrimoine-950
                            px-3.5 py-2
                            text-sm font-medium
                            text-white
                            transition
                            hover:bg-patrimoine-900
                        "
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 5v14"/>
                            <path d="M5 12h14"/>
                        </svg>

                        Add Unit
                    </button>

                    <button
                        type="button"
                        data-property-toggle
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        aria-expanded="${
                            expandUnits
                                ? 'true'
                                : 'false'
                        }"
                        class="
                            inline-flex items-center
                            gap-2 rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        <span
                            data-property-toggle-label
                        >
                            ${
                                expandUnits
                                    ? 'Hide Units'
                                    : 'View Units'
                            }
                        </span>

                        <svg
                            data-property-chevron
                            class="
                                h-4 w-4
                                transition-transform
                                ${
                                    expandUnits
                                        ? 'rotate-180'
                                        : ''
                                }
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div
                id="property-units-${escapeHtml(
                    building.id
                )}"
                class="
                    ${
                        expandUnits
                            ? ''
                            : 'hidden'
                    }
                    border-t border-slate-100
                    bg-slate-50/60
                    px-5 py-4
                "
            >
                <div
                    class="
                        mb-2 text-xs font-semibold
                        uppercase tracking-[0.12em]
                        text-slate-400
                    "
                >
                    Units
                </div>

                <div>
                    ${unitRows}
                </div>
            </div>
        </article>
    `;
}

/**
 * Render ownership badges for a Building.
 *
 * @param {Array} ownerships
 * @returns {string}
 */
function renderPropertyOwners(
    ownerships
) {
    if (
        ownerships.length === 0
    ) {
        return `
            <span
                class="
                    text-xs text-slate-400
                "
            >
                No ownership information
            </span>
        `;
    }

    return ownerships
        .map(
            (ownership) => {
                const party =
                    ownership.party
                    || {};

                const name =
                    party.name
                    || party.legal_name
                    || 'Owner';

                const percentage =
                    ownership
                        .ownership_percentage;

                return `
                    <span
                        class="
                            inline-flex items-center
                            rounded-full
                            bg-slate-100
                            px-2.5 py-1
                            text-xs font-medium
                            text-slate-600
                        "
                    >
                        ${escapeHtml(
                            name
                        )}

                        ${
                            percentage
                                !== undefined
                                && percentage
                                !== null
                                    ? ` · ${escapeHtml(
                                        Number(
                                            percentage
                                        ).toFixed(0)
                                    )}%`
                                    : ''
                        }
                    </span>
                `;
            }
        )
        .join('');
}

/**
 * Render all Units associated with a Building.
 *
 * @param {object} building
 * @param {Array} units
 * @param {string} buildingName
 * @param {Set<string>} matchingUnitIds
 * @returns {string}
 */
function renderPropertyUnits(
    building,
    units,
    buildingName,
    matchingUnitIds = new Set()
) {
    if (
        units.length === 0
    ) {
        return `
            <div
                class="
                    py-5 text-sm
                    text-slate-400
                "
            >
                No units have been added to this property.
            </div>
        `;
    }

    return units
        .map(
            (unit) => {
                const matchesSearch =
                    matchingUnitIds.has(
                        String(
                            unit.id
                        )
                    );

                return `
                <div
                    data-property-unit-id="${escapeHtml(
                        unit.id
                    )}"
                    class="
                        flex items-center
                        justify-between gap-4
                        border-b border-slate-100
                        px-3 py-3 last:border-b-0
                        ${
                            matchesSearch
                                ? 'rounded-lg bg-patrimoine-50 ring-1 ring-inset ring-patrimoine-200'
                                : ''
                        }
                    "
                >
                    <div
                        class="
                            min-w-0 flex-1
                        "
                    >
                        <div
                            class="
                                text-sm font-medium
                                text-slate-900
                            "
                        >
                            ${escapeHtml(
                                unit.name
                                || 'Unnamed Unit'
                            )}
                        </div>

                        ${
                            unit.description
                                ? `
                                    <div
                                        class="
                                            mt-1 text-xs
                                            text-slate-500
                                        "
                                    >
                                        ${escapeHtml(
                                            unit.description
                                        )}
                                    </div>
                                `
                                : ''
                        }
                    </div>

                    <div
                        class="
                            flex shrink-0
                            items-center gap-2
                        "
                    >
                        <span
                            class="
                                rounded-full
                                bg-slate-100
                                px-2.5 py-1
                                text-xs font-medium
                                text-slate-500
                            "
                        >
                            Unit
                        </span>

                        <button
                            type="button"
                            data-edit-unit
                            data-unit-id="${escapeHtml(
                                unit.id
                            )}"
                            data-building-id="${escapeHtml(
                                building.id
                            )}"
                            data-building-name="${escapeHtml(
                                buildingName
                            )}"
                            class="
                                rounded-lg
                                border border-slate-200
                                bg-white px-3 py-1.5
                                text-xs font-medium
                                text-slate-700
                                transition
                                hover:bg-slate-50
                            "
                        >
                            Edit
                        </button>
                    </div>
                </div>
            `;
            }
        )
        .join('');
}

/**
 * Normalize text used by the Properties Unit-match UI.
 *
 * @param {unknown} value
 * @returns {string}
 */
function normalizePropertySearch(value) {
    return String(
        value ?? ''
    )
        .trim()
        .toLocaleLowerCase();
}

/**
 * Determine whether a Unit matches the active Properties search.
 *
 * BuildingController searches Unit name and description, so the browser
 * mirrors those same fields when deciding which Unit row to highlight.
 *
 * @param {object} unit
 * @param {string} normalizedSearch
 * @returns {boolean}
 */
function propertyUnitMatchesSearch(
    unit,
    normalizedSearch
) {
    if (normalizedSearch === '') {
        return false;
    }

    return [
        unit?.name,
        unit?.description,
    ].some(
        (value) =>
            normalizePropertySearch(
                value
            ).includes(
                normalizedSearch
            )
    );
}

/**
 * Expand or collapse the Unit list for a Building.
 *
 * @param {HTMLElement} button
 */
function togglePropertyUnits(button) {
    const buildingId =
        button.dataset
            .buildingId;

    if (! buildingId) {
        return;
    }

    const panel =
        document.getElementById(
            `property-units-${buildingId}`
        );

    if (! panel) {
        return;
    }

    const expanded =
        button.getAttribute(
            'aria-expanded'
        ) === 'true';

    button.setAttribute(
        'aria-expanded',
        expanded
            ? 'false'
            : 'true'
    );

    panel.classList.toggle(
        'hidden',
        expanded
    );

    const chevron =
        button.querySelector(
            '[data-property-chevron]'
        );

    chevron?.classList.toggle(
        'rotate-180',
        ! expanded
    );

    const label =
        button.querySelector(
            '[data-property-toggle-label]'
        );

    if (label) {
        label.textContent =
            expanded
                ? 'View Units'
                : 'Hide Units';
    }
}

/*
|--------------------------------------------------------------------------
| Property Pagination
|--------------------------------------------------------------------------
*/

/**
 * Render Previous and Next pagination controls.
 *
 * @param {object} payload
 * @param {string} search
 */
function renderPropertiesPagination(
    payload,
    search
) {
    const container =
        document.getElementById(
            'properties-pagination'
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
        container.classList.add(
            'hidden'
        );

        container.innerHTML =
            '';

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

            <div
                class="flex gap-2"
            >
                <button
                    type="button"
                    id="properties-previous"
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
                    type="button"
                    id="properties-next"
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
            'properties-previous'
        )
        ?.addEventListener(
            'click',
            () => {
                if (
                    currentPage > 1
                ) {
                    loadProperties(
                        search,
                        currentPage - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'properties-next'
        )
        ?.addEventListener(
            'click',
            () => {
                if (
                    currentPage
                    < lastPage
                ) {
                    loadProperties(
                        search,
                        currentPage + 1
                    );
                }
            }
        );
}

/*
|--------------------------------------------------------------------------
| Property Create / Edit Initialization
|--------------------------------------------------------------------------
*/

/**
 * Initialize the shared Create/Edit Property modal.
 */
function initializePropertyCreation() {
    const modal =
        document.getElementById(
            'property-modal'
        );

    const form =
        document.getElementById(
            'property-form'
        );

    const openButton =
        document.getElementById(
            'add-property-button'
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
        openPropertyModal
    );

    document
        .getElementById(
            'property-modal-close'
        )
        ?.addEventListener(
            'click',
            closePropertyModal
        );

    document
        .getElementById(
            'property-cancel-button'
        )
        ?.addEventListener(
            'click',
            closePropertyModal
        );

    document
        .getElementById(
            'property-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closePropertyModal
        );

    document
        .getElementById(
            'add-owner-button'
        )
        ?.addEventListener(
            'click',
            () => {
                addPropertyOwnerRow();
            }
        );

    document
        .getElementById(
            'add-unit-button'
        )
        ?.addEventListener(
            'click',
            () => {
                addPropertyUnitRow();
            }
        );

    form.addEventListener(
        'submit',
        submitPropertyForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal
                    .classList
                    .contains(
                        'hidden'
                    )
            ) {
                closePropertyModal();
            }
        }
    );
}

/**
 * Configure the shared property form for Create or Edit mode.
 *
 * @param {'create'|'edit'} mode
 */
function configurePropertyModal(mode) {
    propertyFormMode =
        mode === 'edit'
            ? 'edit'
            : 'create';

    const editing =
        propertyFormMode
        === 'edit';

    const title =
        document.getElementById(
            'property-modal-title'
        );

    const description =
        document.getElementById(
            'property-modal-description'
        );

    const unitsSection =
        document.getElementById(
            'property-units-section'
        );

    const submitButton =
        document.getElementById(
            'property-submit-button'
        );

    if (title) {
        title.textContent =
            editing
                ? 'Edit Property'
                : 'Add Property';
    }

    if (description) {
        description.textContent =
            editing
                ? 'Update the building details and ownership allocation.'
                : 'Create a building, define its ownership and add its units.';
    }

    /*
     * Units are independently managed after Building creation.
     * Existing Units are therefore intentionally hidden during Building
     * editing.
     */
    unitsSection?.classList.toggle(
        'hidden',
        editing
    );

    if (submitButton) {
        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Create Property';
    }
}

/**
 * Open the Property modal in Create mode.
 */
async function openPropertyModal() {
    const modal =
        document.getElementById(
            'property-modal'
        );

    if (! modal) {
        return;
    }

    resetPropertyForm();

    editingPropertyId =
        null;

    configurePropertyModal(
        'create'
    );

    showPropertyModal();

    try {
        await loadOwnerParties();

        addPropertyOwnerRow(
            null,
            100
        );

        addPropertyUnitRow();

        document
            .getElementById(
                'property-name'
            )
            ?.focus();
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : 'Unable to load property owners.'
        );
    }
}

/**
 * Open the Property modal in Edit mode and load fresh Building data.
 *
 * @param {string|number} buildingId
 */
async function openEditPropertyModal(
    buildingId
) {
    const modal =
        document.getElementById(
            'property-modal'
        );

    const numericBuildingId =
        Number(
            buildingId
        );

    if (
        ! modal
        || ! Number.isInteger(
            numericBuildingId
        )
        || numericBuildingId <= 0
    ) {
        return;
    }

    resetPropertyForm();

    editingPropertyId =
        numericBuildingId;

    configurePropertyModal(
        'edit'
    );

    showPropertyModal();

    try {
        /*
         * Fetch fresh Building data rather than relying on the list
         * representation, especially because ownership may have changed.
         */
        const [
            buildingResponse,
        ] = await Promise.all([
            apiRequest(
                `/api/buildings/${numericBuildingId}`
            ),

            loadOwnerParties(),
        ]);

        const building =
            await parseJsonResponse(
                buildingResponse
            );

        populatePropertyForm(
            building
        );
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : 'Unable to load property.'
        );
    }
}

/**
 * Make the shared property modal visible.
 */
function showPropertyModal() {
    const modal =
        document.getElementById(
            'property-modal'
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
}

/**
 * Populate Property fields from an API Building representation.
 *
 * @param {object} building
 */
function populatePropertyForm(
    building
) {
    const nameInput =
        document.getElementById(
            'property-name'
        );

    const locationInput =
        document.getElementById(
            'property-location'
        );

    const addressInput =
        document.getElementById(
            'property-address'
        );

    const descriptionInput =
        document.getElementById(
            'property-description'
        );

    if (nameInput) {
        nameInput.value =
            building.name
            || '';
    }

    if (locationInput) {
        locationInput.value =
            building.location
            || '';
    }

    if (addressInput) {
        addressInput.value =
            building.address
            || '';
    }

    if (descriptionInput) {
        descriptionInput.value =
            building.description
            || '';
    }

    const ownerships =
        Array.isArray(
            building.ownerships
        )
            ? building.ownerships
            : [];

    if (
        ownerships.length > 0
    ) {
        ownerships.forEach(
            (ownership) => {
                addPropertyOwnerRow(
                    ownership.party_id
                    ?? ownership.party?.id
                    ?? null,

                    ownership
                        .ownership_percentage
                );
            }
        );
    } else {
        /*
         * Normally unreachable because Patrimoine requires ownership to
         * total 100%, but this keeps imported historical data editable.
         */
        addPropertyOwnerRow(
            null,
            100
        );
    }

    nameInput?.focus();
}

/**
 * Close and reset the Property modal.
 */
function closePropertyModal() {
    const modal =
        document.getElementById(
            'property-modal'
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

    resetPropertyForm();

    editingPropertyId =
        null;

    configurePropertyModal(
        'create'
    );
}

/**
 * Clear all form state belonging to the shared Property form.
 */
function resetPropertyForm() {
    const form =
        document.getElementById(
            'property-form'
        );

    form?.reset();

    const ownerRows =
        document.getElementById(
            'property-owner-rows'
        );

    const unitRows =
        document.getElementById(
            'property-unit-rows'
        );

    if (ownerRows) {
        ownerRows.innerHTML =
            '';
    }

    if (unitRows) {
        unitRows.innerHTML =
            '';
    }

    hidePropertyFormError();

    updateOwnershipTotal();
}

/*
|--------------------------------------------------------------------------
| Property Ownership
|--------------------------------------------------------------------------
*/

/**
 * Load all Parties carrying the Owner role.
 */
async function loadOwnerParties() {
    const response =
        await apiRequest(
            '/api/parties?role=owner&per_page=100'
        );

    const payload =
        await parseJsonResponse(
            response
        );

    availableOwnerParties =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];
}

/**
 * Determine an Owner Party's display name.
 *
 * @param {object} party
 * @returns {string}
 */
function ownerPartyDisplayName(
    party
) {
    return party?.name
        || party?.legal_name
        || `Party #${party?.id ?? ''}`;
}

/**
 * Build Owner <option> elements.
 *
 * @param {number|string|null} selectedPartyId
 * @returns {string}
 */
function ownerPartyOptions(
    selectedPartyId = null
) {
    const options =
        availableOwnerParties
            .map(
                (party) => `
                    <option
                        value="${escapeHtml(
                            party.id
                        )}"
                        ${
                            String(
                                party.id
                            )
                            === String(
                                selectedPartyId
                            )
                                ? 'selected'
                                : ''
                        }
                    >
                        ${escapeHtml(
                            ownerPartyDisplayName(
                                party
                            )
                        )}
                    </option>
                `
            )
            .join('');

    return `
        <option value="">
            ${
                availableOwnerParties
                    .length === 0
                    ? 'Create an owner first…'
                    : 'Select owner…'
            }
        </option>

        ${options}
    `;
}

/**
 * Add one ownership allocation row to the Property form.
 *
 * @param {number|string|null} selectedPartyId
 * @param {number|string} percentage
 */
function addPropertyOwnerRow(
    selectedPartyId = null,
    percentage = ''
) {
    const container =
        document.getElementById(
            'property-owner-rows'
        );

    if (! container) {
        return;
    }

    const row =
        document.createElement(
            'div'
        );

    row.className =
        'property-owner-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:grid-cols-[1fr_150px_auto] sm:items-end';

    row.innerHTML = `
        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Owner
            </label>

            <div class="flex gap-2">
                <select
                    data-owner-party
                    required
                    class="
                        min-w-0 flex-1 rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm
                        outline-none transition
                        focus:border-patrimoine-500
                        focus:ring-2
                        focus:ring-patrimoine-100
                    "
                >
                    ${ownerPartyOptions(
                        selectedPartyId
                    )}
                </select>

                <button
                    type="button"
                    data-create-owner
                    class="
                        shrink-0 rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-800
                        transition
                        hover:bg-patrimoine-50
                    "
                    title="Create a new owner"
                >
                    + New
                </button>
            </div>

            ${
                availableOwnerParties
                    .length === 0
                    ? `
                        <p
                            class="
                                mt-1.5 text-xs
                                text-slate-500
                            "
                        >
                            No owners yet. Create the first Owner Party.
                        </p>
                    `
                    : ''
            }
        </div>

        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Ownership %
            </label>

            <input
                data-owner-percentage
                type="number"
                required
                min="0.01"
                max="100"
                step="0.01"
                value="${escapeHtml(
                    percentage
                )}"
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white px-3 py-2.5
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
            >
        </div>

        <button
            type="button"
            data-remove-owner
            class="
                inline-flex h-10
                items-center justify-center
                rounded-lg
                border border-slate-200
                bg-white px-3
                text-sm text-slate-500
                transition
                hover:border-red-200
                hover:bg-red-50
                hover:text-red-600
            "
        >
            Remove
        </button>
    `;

    container.appendChild(
        row
    );

    row
        .querySelector(
            '[data-owner-percentage]'
        )
        ?.addEventListener(
            'input',
            updateOwnershipTotal
        );

    row
        .querySelector(
            '[data-create-owner]'
        )
        ?.addEventListener(
            'click',
            () => {
                openOwnerModal(
                    row
                );
            }
        );

    row
        .querySelector(
            '[data-remove-owner]'
        )
        ?.addEventListener(
            'click',
            () => {
                const rows =
                    container.querySelectorAll(
                        '.property-owner-row'
                    );

                if (
                    rows.length <= 1
                ) {
                    showPropertyFormError(
                        'A property must have at least one owner.'
                    );

                    return;
                }

                row.remove();

                updateOwnershipTotal();
            }
        );

    updateOwnershipTotal();
}

/**
 * Recalculate and display the Building ownership total.
 */
function updateOwnershipTotal() {
    const output =
        document.getElementById(
            'ownership-total'
        );

    if (! output) {
        return;
    }

    const inputs =
        document.querySelectorAll(
            '#property-owner-rows [data-owner-percentage]'
        );

    const total =
        Array
            .from(inputs)
            .reduce(
                (
                    sum,
                    input
                ) => {
                    const value =
                        Number(
                            input.value
                        );

                    return sum
                        + (
                            Number.isFinite(
                                value
                            )
                                ? value
                                : 0
                        );
                },
                0
            );

    const normalized =
        Math.round(
            total * 100
        ) / 100;

    output.textContent =
        `Total: ${normalized}%`;

    output.classList.remove(
        'bg-slate-100',
        'text-slate-600',
        'bg-green-100',
        'text-green-700',
        'bg-red-100',
        'text-red-700'
    );

    if (
        Math.abs(
            total - 100
        ) < 0.001
    ) {
        output.classList.add(
            'bg-green-100',
            'text-green-700'
        );
    } else if (
        total > 100
    ) {
        output.classList.add(
            'bg-red-100',
            'text-red-700'
        );
    } else {
        output.classList.add(
            'bg-slate-100',
            'text-slate-600'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Units During Property Creation
|--------------------------------------------------------------------------
*/

/**
 * Add one Unit row to the new Property form.
 *
 * @param {string} name
 * @param {string} description
 */
function addPropertyUnitRow(
    name = '',
    description = ''
) {
    const container =
        document.getElementById(
            'property-unit-rows'
        );

    if (! container) {
        return;
    }

    const row =
        document.createElement(
            'div'
        );

    row.className =
        'property-unit-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:grid-cols-[220px_1fr_auto] sm:items-end';

    row.innerHTML = `
        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Unit Name / Number
            </label>

            <input
                data-unit-name
                type="text"
                required
                maxlength="255"
                value="${escapeHtml(
                    name
                )}"
                placeholder="e.g. Apartment A1"
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white px-3 py-2.5
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
            >
        </div>

        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Description
            </label>

            <input
                data-unit-description
                type="text"
                value="${escapeHtml(
                    description
                )}"
                placeholder="Optional description"
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white px-3 py-2.5
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
            >
        </div>

        <button
            type="button"
            data-remove-unit
            class="
                inline-flex h-10
                items-center justify-center
                rounded-lg
                border border-slate-200
                bg-white px-3
                text-sm text-slate-500
                transition
                hover:border-red-200
                hover:bg-red-50
                hover:text-red-600
            "
        >
            Remove
        </button>
    `;

    container.appendChild(
        row
    );

    row
        .querySelector(
            '[data-remove-unit]'
        )
        ?.addEventListener(
            'click',
            () => {
                const rows =
                    container.querySelectorAll(
                        '.property-unit-row'
                    );

                if (
                    rows.length <= 1
                ) {
                    showPropertyFormError(
                        'A property must have at least one unit.'
                    );

                    return;
                }

                row.remove();
            }
        );
}

/*
|--------------------------------------------------------------------------
| Property Form Collection and Validation
|--------------------------------------------------------------------------
*/

/**
 * Collect all ownership allocations from the current Property form.
 *
 * @returns {Array<object>}
 */
function collectPropertyOwners() {
    const rows =
        document.querySelectorAll(
            '#property-owner-rows .property-owner-row'
        );

    return Array
        .from(rows)
        .map(
            (row) => ({
                party_id:
                    Number(
                        row.querySelector(
                            '[data-owner-party]'
                        )?.value
                    ),

                ownership_percentage:
                    Number(
                        row.querySelector(
                            '[data-owner-percentage]'
                        )?.value
                    ),
            })
        );
}

/**
 * Collect Units entered while creating a new Building.
 *
 * @returns {Array<object>}
 */
function collectPropertyUnits() {
    const rows =
        document.querySelectorAll(
            '#property-unit-rows .property-unit-row'
        );

    return Array
        .from(rows)
        .map(
            (row) => ({
                name:
                    String(
                        row.querySelector(
                            '[data-unit-name]'
                        )?.value
                        || ''
                    ).trim(),

                description:
                    String(
                        row.querySelector(
                            '[data-unit-description]'
                        )?.value
                        || ''
                    ).trim(),
            })
        );
}

/**
 * Validate ownership rules shared by create and edit operations.
 *
 * @param {Array<object>} owners
 * @throws {Error}
 */
function validatePropertyOwnership(
    owners
) {
    if (
        owners.length === 0
    ) {
        throw new Error(
            'A property must have at least one owner.'
        );
    }

    const ownerIds =
        owners.map(
            (owner) =>
                owner.party_id
        );

    if (
        ownerIds.some(
            (id) =>
                ! Number.isInteger(id)
                || id <= 0
        )
    ) {
        throw new Error(
            'Select an owner for every ownership row.'
        );
    }

    if (
        new Set(
            ownerIds
        ).size
        !== ownerIds.length
    ) {
        throw new Error(
            'The same owner cannot be added more than once.'
        );
    }

    if (
        owners.some(
            (owner) =>
                ! Number.isFinite(
                    owner
                        .ownership_percentage
                )
                || owner
                    .ownership_percentage
                    <= 0
                || owner
                    .ownership_percentage
                    > 100
        )
    ) {
        throw new Error(
            'Enter a valid ownership percentage for every owner.'
        );
    }

    const totalOwnership =
        owners.reduce(
            (
                total,
                owner
            ) =>
                total
                + owner
                    .ownership_percentage,
            0
        );

    if (
        Math.abs(
            totalOwnership - 100
        ) > 0.001
    ) {
        throw new Error(
            'Property ownership must total exactly 100%.'
        );
    }
}

/**
 * Validate fields specific to Building creation.
 *
 * @param {Array<object>} owners
 * @param {Array<object>} units
 * @throws {Error}
 */
function validatePropertyCreation(
    owners,
    units
) {
    validatePropertyOwnership(
        owners
    );

    if (
        units.length === 0
    ) {
        throw new Error(
            'A property must have at least one unit.'
        );
    }

    if (
        units.some(
            (unit) =>
                unit.name === ''
        )
    ) {
        throw new Error(
            'Every unit must have a name or number.'
        );
    }

    const normalizedUnitNames =
        units.map(
            (unit) =>
                unit.name
                    .toLowerCase()
        );

    if (
        new Set(
            normalizedUnitNames
        ).size
        !== normalizedUnitNames
            .length
    ) {
        throw new Error(
            'Unit names must be unique within the property.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Property Form Submission
|--------------------------------------------------------------------------
*/

/**
 * Submit either a new Building or changes to an existing Building.
 *
 * Existing Units are intentionally not modified through Building editing.
 */
async function submitPropertyForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'property-form'
        );

    const submitButton =
        document.getElementById(
            'property-submit-button'
        );

    if (
        ! form
        || ! submitButton
    ) {
        return;
    }

    hidePropertyFormError();

    if (
        ! form.reportValidity()
    ) {
        return;
    }

    const owners =
        collectPropertyOwners();

    const editing =
        propertyFormMode === 'edit'
        && Number.isInteger(
            editingPropertyId
        );

    const units =
        editing
            ? []
            : collectPropertyUnits();

    try {
        if (editing) {
            validatePropertyOwnership(
                owners
            );
        } else {
            validatePropertyCreation(
                owners,
                units
            );
        }

        submitButton.disabled =
            true;

        submitButton.textContent =
            editing
                ? 'Saving Changes…'
                : 'Creating Property…';

        const buildingPayload = {
            name:
                formValue(
                    'property-name'
                ),

            location:
                nullableFormValue(
                    'property-location'
                ),

            address:
                nullableFormValue(
                    'property-address'
                ),

            description:
                nullableFormValue(
                    'property-description'
                ),

            owners,
        };

        if (editing) {
            await updateProperty(
                buildingPayload
            );
        } else {
            await createProperty(
                buildingPayload,
                units
            );
        }

        closePropertyModal();

        await refreshPropertyPortfolio();
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? 'Unable to update property.'
                        : 'Unable to create property.'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Create Property';
    }
}

/**
 * Update an existing Building through the API.
 *
 * @param {object} buildingPayload
 */
async function updateProperty(
    buildingPayload
) {
    const response =
        await apiRequest(
            `/api/buildings/${editingPropertyId}`,
            {
                method:
                    'PATCH',

                body:
                    JSON.stringify(
                        buildingPayload
                    ),
            }
        );

    await parseJsonResponse(
        response
    );
}

/**
 * Create a Building followed by all Units entered with it.
 *
 * @param {object} buildingPayload
 * @param {Array<object>} units
 */
async function createProperty(
    buildingPayload,
    units
) {
    /*
     * Unit creation requires an existing Building ID, so the Building must
     * be persisted before its Units.
     */
    const buildingResponse =
        await apiRequest(
            '/api/buildings',
            {
                method:
                    'POST',

                body:
                    JSON.stringify(
                        buildingPayload
                    ),
            }
        );

    const building =
        await parseJsonResponse(
            buildingResponse
        );

    for (
        const unit of units
    ) {
        const unitResponse =
            await apiRequest(
                '/api/units',
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            building_id:
                                building.id,

                            name:
                                unit.name,

                            description:
                                unit.description
                                || null,
                        }),
                }
            );

        await parseJsonResponse(
            unitResponse
        );
    }
}

/**
 * Refresh the Property list using the current search text.
 */
async function refreshPropertyPortfolio() {
    await loadProperties(
        formValue(
            'property-search'
        ),
        1
    );
}

/*
|--------------------------------------------------------------------------
| Property Form Errors
|--------------------------------------------------------------------------
*/

function showPropertyFormError(
    message
) {
    const box =
        document.getElementById(
            'property-form-error'
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
        behavior:
            'smooth',

        block:
            'nearest',
    });
}

function hidePropertyFormError() {
    const box =
        document.getElementById(
            'property-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        '';

    box.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Inline Owner Creation
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Owner creation modal embedded in the Property workflow.
 */
function initializeOwnerCreation() {
    const modal =
        document.getElementById(
            'owner-modal'
        );

    const form =
        document.getElementById(
            'owner-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    document
        .getElementById(
            'owner-modal-close'
        )
        ?.addEventListener(
            'click',
            closeOwnerModal
        );

    document
        .getElementById(
            'owner-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeOwnerModal
        );

    document
        .getElementById(
            'owner-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeOwnerModal
        );

    document
        .getElementById(
            'owner-type'
        )
        ?.addEventListener(
            'change',
            updateOwnerTypeFields
        );

    form.addEventListener(
        'submit',
        submitOwnerForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal
                    .classList
                    .contains(
                        'hidden'
                    )
            ) {
                closeOwnerModal();
            }
        }
    );
}

/**
 * Open inline Owner creation for the ownership row that requested it.
 *
 * @param {HTMLElement} targetRow
 */
function openOwnerModal(
    targetRow
) {
    const modal =
        document.getElementById(
            'owner-modal'
        );

    const form =
        document.getElementById(
            'owner-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    ownerTargetRow =
        targetRow;

    form.reset();

    hideOwnerFormError();

    updateOwnerTypeFields();

    modal.classList.remove(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document
        .getElementById(
            'owner-name'
        )
        ?.focus();
}

/**
 * Close the inline Owner creation modal.
 */
function closeOwnerModal() {
    const modal =
        document.getElementById(
            'owner-modal'
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

    ownerTargetRow =
        null;

    hideOwnerFormError();
}

/**
 * Show fields appropriate to the selected Party type.
 */
function updateOwnerTypeFields() {
    const type =
        document
            .getElementById(
                'owner-type'
            )
            ?.value
        || 'person';

    const personFields =
        document.getElementById(
            'owner-person-fields'
        );

    const organisationFields =
        document.getElementById(
            'owner-organisation-fields'
        );

    const person =
        type === 'person';

    personFields?.classList.toggle(
        'hidden',
        ! person
    );

    organisationFields
        ?.classList
        .toggle(
            'hidden',
            person
        );
}

/**
 * Create an Owner Party and automatically select it in the originating
 * ownership row.
 */
async function submitOwnerForm(
    event
) {
    event.preventDefault();

    const type =
        document
            .getElementById(
                'owner-type'
            )
            ?.value
        || 'person';

    const submitButton =
        document.getElementById(
            'owner-submit-button'
        );

    if (! submitButton) {
        return;
    }

    hideOwnerFormError();

    let payload;

    if (type === 'person') {
        payload =
            buildPersonOwnerPayload();

        if (! payload) {
            return;
        }
    } else {
        payload =
            buildOrganisationOwnerPayload(
                type
            );

        if (! payload) {
            return;
        }
    }

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            'Creating Owner…';

        const response =
            await apiRequest(
                '/api/parties',
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        const owner =
            await parseJsonResponse(
                response
            );

        availableOwnerParties.push(
            owner
        );

        availableOwnerParties.sort(
            (
                a,
                b
            ) =>
                ownerPartyDisplayName(
                    a
                ).localeCompare(
                    ownerPartyDisplayName(
                        b
                    )
                )
        );

        refreshOwnerSelects(
            owner.id
        );

        closeOwnerModal();

        hidePropertyFormError();
    } catch (error) {
        showOwnerFormError(
            error instanceof Error
                ? error.message
                : 'Unable to create owner.'
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            'Create Owner';
    }
}

/**
 * Build and validate a Person Owner payload.
 *
 * @returns {object|null}
 */
function buildPersonOwnerPayload() {
    const name =
        formValue(
            'owner-name'
        );

    const phone =
        formValue(
            'owner-phone'
        );

    const email =
        formValue(
            'owner-email'
        );

    if (
        name === ''
        || phone === ''
        || email === ''
    ) {
        showOwnerFormError(
            'Name, phone and email are required for a person.'
        );

        return null;
    }

    return {
        type:
            'person',

        name,

        phone,

        email,

        address:
            nullableFormValue(
                'owner-address'
            ),

        roles: [
            'owner',
        ],
    };
}

/**
 * Build and validate an Organisation or Association Owner payload.
 *
 * @param {string} type
 * @returns {object|null}
 */
function buildOrganisationOwnerPayload(
    type
) {
    const legalName =
        formValue(
            'owner-legal-name'
        );

    const contactName =
        formValue(
            'owner-contact-name'
        );

    const contactPhone =
        formValue(
            'owner-contact-phone'
        );

    const contactEmail =
        formValue(
            'owner-contact-email'
        );

    if (
        legalName === ''
        || contactName === ''
        || contactPhone === ''
        || contactEmail === ''
    ) {
        showOwnerFormError(
            'Legal name and contact person details are required.'
        );

        return null;
    }

    return {
        type,

        legal_name:
            legalName,

        address:
            nullableFormValue(
                'owner-address'
            ),

        contact_person_name:
            contactName,

        contact_person_phone:
            contactPhone,

        contact_person_email:
            contactEmail,

        roles: [
            'owner',
        ],
    };
}

/**
 * Refresh all ownership dropdowns after an Owner Party is created.
 *
 * @param {number|null} newlyCreatedOwnerId
 */
function refreshOwnerSelects(
    newlyCreatedOwnerId = null
) {
    const rows =
        document.querySelectorAll(
            '#property-owner-rows .property-owner-row'
        );

    rows.forEach(
        (row) => {
            const select =
                row.querySelector(
                    '[data-owner-party]'
                );

            if (! select) {
                return;
            }

            const previousValue =
                select.value;

            const shouldSelectNewOwner =
                ownerTargetRow === row
                && newlyCreatedOwnerId
                    !== null;

            select.innerHTML =
                ownerPartyOptions(
                    shouldSelectNewOwner
                        ? newlyCreatedOwnerId
                        : previousValue
                );
        }
    );
}

function showOwnerFormError(
    message
) {
    const box =
        document.getElementById(
            'owner-form-error'
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

function hideOwnerFormError() {
    const box =
        document.getElementById(
            'owner-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        '';

    box.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Add / Edit Unit on Existing Property
|--------------------------------------------------------------------------
*/

/**
 * Initialize the shared Add/Edit Unit modal.
 */
function initializeExistingUnitCreation() {
    const modal =
        document.getElementById(
            'existing-unit-modal'
        );

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    document
        .getElementById(
            'existing-unit-modal-close'
        )
        ?.addEventListener(
            'click',
            closeExistingUnitModal
        );

    document
        .getElementById(
            'existing-unit-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeExistingUnitModal
        );

    document
        .getElementById(
            'existing-unit-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeExistingUnitModal
        );

    form.addEventListener(
        'submit',
        submitExistingUnitForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal
                    .classList
                    .contains(
                        'hidden'
                    )
            ) {
                closeExistingUnitModal();
            }
        }
    );
}

/**
 * Open the shared Unit modal.
 *
 * Supplying a Unit ID switches the modal to Edit mode.
 *
 * @param {string|number} buildingId
 * @param {string} buildingName
 * @param {string|number|null} unitId
 */
function openExistingUnitModal(
    buildingId,
    buildingName,
    unitId = null
) {
    const modal =
        document.getElementById(
            'existing-unit-modal'
        );

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    form.reset();

    hideExistingUnitFormError();

    const numericBuildingId =
        Number(
            buildingId
        );

    const numericUnitId =
        unitId !== null
            ? Number(unitId)
            : null;

    if (
        ! Number.isInteger(
            numericBuildingId
        )
        || numericBuildingId <= 0
    ) {
        return;
    }

    const editing =
        Number.isInteger(
            numericUnitId
        )
        && numericUnitId > 0;

    existingUnitFormMode =
        editing
            ? 'edit'
            : 'create';

    editingUnitId =
        editing
            ? numericUnitId
            : null;

    configureExistingUnitModal(
        numericBuildingId,
        buildingName,
        editing
    );

    if (editing) {
        const unit =
            findLoadedUnit(
                numericBuildingId,
                numericUnitId
            );

        if (! unit) {
            showExistingUnitFormError(
                'Unable to locate this unit.'
            );

            return;
        }

        populateExistingUnitForm(
            unit
        );
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

    document
        .getElementById(
            'existing-unit-name'
        )
        ?.focus();
}

/**
 * Configure labels and Building context for the Unit modal.
 *
 * @param {number} buildingId
 * @param {string} buildingName
 * @param {boolean} editing
 */
function configureExistingUnitModal(
    buildingId,
    buildingName,
    editing
) {
    const buildingIdInput =
        document.getElementById(
            'existing-unit-building-id'
        );

    const buildingNameElement =
        document.getElementById(
            'existing-unit-building-name'
        );

    const title =
        document.getElementById(
            'existing-unit-modal-title'
        );

    const descriptionElement =
        document.getElementById(
            'existing-unit-modal-description'
        );

    const submitButton =
        document.getElementById(
            'existing-unit-submit-button'
        );

    if (buildingIdInput) {
        buildingIdInput.value =
            String(
                buildingId
            );
    }

    if (buildingNameElement) {
        buildingNameElement.textContent =
            buildingName
            || 'Property';
    }

    if (title) {
        title.textContent =
            editing
                ? 'Edit Unit'
                : 'Add Unit';
    }

    if (descriptionElement) {
        descriptionElement.textContent =
            editing
                ? 'Update this unit\'s name or description.'
                : 'Add a leasable unit to an existing property.';
    }

    if (submitButton) {
        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Add Unit';
    }
}

/**
 * Locate a Unit within the currently loaded Building collection.
 *
 * @param {number} buildingId
 * @param {number} unitId
 * @returns {object|null}
 */
function findLoadedUnit(
    buildingId,
    unitId
) {
    const building =
        loadedPropertiesById.get(
            String(
                buildingId
            )
        );

    if (
        ! Array.isArray(
            building?.units
        )
    ) {
        return null;
    }

    return building.units.find(
        (candidate) =>
            Number(
                candidate.id
            ) === unitId
    ) || null;
}

/**
 * Populate Unit fields for editing.
 *
 * @param {object} unit
 */
function populateExistingUnitForm(
    unit
) {
    const nameInput =
        document.getElementById(
            'existing-unit-name'
        );

    const descriptionInput =
        document.getElementById(
            'existing-unit-description'
        );

    if (nameInput) {
        nameInput.value =
            unit.name
            || '';
    }

    if (descriptionInput) {
        descriptionInput.value =
            unit.description
            || '';
    }
}

/**
 * Close and reset the shared Unit modal.
 */
function closeExistingUnitModal() {
    const modal =
        document.getElementById(
            'existing-unit-modal'
        );

    const form =
        document.getElementById(
            'existing-unit-form'
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

    form?.reset();

    const buildingNameElement =
        document.getElementById(
            'existing-unit-building-name'
        );

    if (buildingNameElement) {
        buildingNameElement.textContent =
            '—';
    }

    existingUnitFormMode =
        'create';

    editingUnitId =
        null;

    resetExistingUnitModalLabels();

    hideExistingUnitFormError();
}

/**
 * Restore Add Unit labels after the modal closes.
 */
function resetExistingUnitModalLabels() {
    const title =
        document.getElementById(
            'existing-unit-modal-title'
        );

    const descriptionElement =
        document.getElementById(
            'existing-unit-modal-description'
        );

    const submitButton =
        document.getElementById(
            'existing-unit-submit-button'
        );

    if (title) {
        title.textContent =
            'Add Unit';
    }

    if (descriptionElement) {
        descriptionElement.textContent =
            'Add a leasable unit to an existing property.';
    }

    if (submitButton) {
        submitButton.textContent =
            'Add Unit';
    }
}

/**
 * Create a Unit or update an existing Unit.
 */
async function submitExistingUnitForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    const submitButton =
        document.getElementById(
            'existing-unit-submit-button'
        );

    if (
        ! form
        || ! submitButton
    ) {
        return;
    }

    hideExistingUnitFormError();

    if (
        ! form.reportValidity()
    ) {
        return;
    }

    const buildingId =
        Number(
            document
                .getElementById(
                    'existing-unit-building-id'
                )
                ?.value
        );

    const name =
        formValue(
            'existing-unit-name'
        );

    const description =
        nullableFormValue(
            'existing-unit-description'
        );

    if (
        ! Number.isInteger(
            buildingId
        )
        || buildingId <= 0
    ) {
        showExistingUnitFormError(
            'A valid property must be selected.'
        );

        return;
    }

    if (name === '') {
        showExistingUnitFormError(
            'Unit name or number is required.'
        );

        return;
    }

    const editing =
        existingUnitFormMode === 'edit'
        && Number.isInteger(
            editingUnitId
        )
        && editingUnitId > 0;

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            editing
                ? 'Saving Changes…'
                : 'Adding Unit…';

        const endpoint =
            editing
                ? `/api/units/${editingUnitId}`
                : '/api/units';

        const response =
            await apiRequest(
                endpoint,
                {
                    method:
                        editing
                            ? 'PATCH'
                            : 'POST',

                    body:
                        JSON.stringify({
                            building_id:
                                buildingId,

                            name,

                            description,
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        closeExistingUnitModal();

        await refreshPropertyPortfolio();
    } catch (error) {
        showExistingUnitFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? 'Unable to update unit.'
                        : 'Unable to add unit.'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Add Unit';
    }
}

function showExistingUnitFormError(
    message
) {
    const box =
        document.getElementById(
            'existing-unit-form-error'
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

function hideExistingUnitFormError() {
    const box =
        document.getElementById(
            'existing-unit-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        '';

    box.classList.add(
        'hidden'
    );
}
