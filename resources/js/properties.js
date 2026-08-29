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
    closeDrawer,
    escapeHtml,
    formValue,
    nullableFormValue,
    openDrawer,
    parseJsonResponse,
    setText,
    translate,
    requireDangerConfirmation,
} from './core.js';

import { readPhoneValue } from './phone-input.js';

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
 * Unit classification filter selected in the page filter bar.
 *
 * 'all' | 'commercial' | 'residential'
 */
let unitClassificationFilter =
    'all';

/*
 * Deletion confirmation state.
 */
let deletingPropertyId =
    null;

let deletingPropertyName =
    '';

let deletingUnitId =
    null;

let deletingUnitName =
    '';

/*
 * Auto-hide timer for the page activity banner.
 */
let propertiesBannerTimer =
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
    initializeOwnerPickers();

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

    const classificationFilter =
        document.getElementById(
            'property-classification-filter'
        );

    if (classificationFilter) {
        classificationFilter.addEventListener(
            'change',
            () => {
                unitClassificationFilter =
                    classificationFilter.value === 'commercial'
                    || classificationFilter.value === 'residential'
                        ? classificationFilter.value
                        : 'all';

                loadProperties(
                    formValue(
                        'property-search'
                    )
                );
            }
        );
    }

    initializePropertyCreation();
    initializeOwnerCreation();
    initializeExistingUnitCreation();
    initializePropertyDeletion();
    initializeUnitDeletion();

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
                text-sm text-[var(--pm-text-subtle)]
            "
        >
            ${escapeHtml(
                translate(
                    'properties.loading'
                )
            )}
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
                    : translate(
                        'properties.unable_to_load'
                    );

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
                    border-dashed border-[var(--pm-border)]
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        mx-auto flex h-11 w-11
                        items-center justify-center
                        rounded-full
                        bg-[color-mix(in_srgb,var(--pm-accent)_10%,var(--pm-surface))]
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
                        <path d="M3 21h18"/>
                        <path d="M6 21V5l6-2 6 2v16"/>
                    </svg>
                </div>

                <div
                    class="
                        mt-4 text-sm font-medium
                        text-[var(--pm-text)]
                    "
                >
                    ${translate('properties.no_properties_found')}
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-[var(--pm-text-muted)]
                    "
                >
                    ${translate('properties.no_properties_hint')}
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

    container
        .querySelectorAll(
            '[data-delete-building]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openDeletePropertyModal(
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
            '[data-delete-unit]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openDeleteUnitModal(
                            button.dataset
                                .unitId,

                            button.dataset
                                .unitName,

                            button.dataset
                                .buildingName
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

    /*
     * The classification filter narrows the Unit rows rendered inside each
     * Property card without hiding the parent Property itself.
     */
    const filteredUnits =
        unitClassificationFilter === 'all'
            ? units
            : units.filter(
                (unit) =>
                    Boolean(
                        unit.is_commercial
                    )
                    === (
                        unitClassificationFilter
                        === 'commercial'
                    )
            );

    const expandUnits =
        matchingUnitIds.size > 0
        || (
            unitClassificationFilter !== 'all'
            && filteredUnits.length > 0
        );

    const ownerships =
        Array.isArray(
            building.ownerships
        )
            ? building.ownerships
            : [];

    const address =
        building.address
        || building.location
        || translate(
            'properties.no_address'
        );

    const buildingName =
        building.name
        || translate(
            'properties.unnamed_property'
        );

    const owners =
        renderPropertyOwners(
            ownerships
        );

    const unitRows =
        renderPropertyUnits(
            building,
            filteredUnits,
            buildingName,
            matchingUnitIds,
            units.length
        );

    return `
        <article
            class="
                pm-card mb-4 overflow-hidden
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
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(
                                buildingName
                            )}
                        </h3>

                        <span
                            class="
                                inline-flex rounded-full
                                bg-[color-mix(in_srgb,var(--pm-accent)_10%,var(--pm-surface))]
                                px-2.5 py-1
                                text-xs font-medium
                                text-[var(--pm-accent)]
                            "
                        >
                            ${units.length}
                            ${
                                units.length === 1
                                    ? translate(
                                        'properties.unit_lower'
                                    )
                                    : translate(
                                        'properties.units_lower'
                                    )
                            }
                        </span>
                    </div>

                    <div
                        class="
                            mt-1 flex items-center gap-2
                            text-sm text-[var(--pm-text-muted)]
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
                        class="pm-button-secondary gap-2"
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

                        ${escapeHtml(
                            translate(
                                'properties.edit'
                            )
                        )}
                    </button>

                    <button
                        type="button"
                        data-delete-building
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        data-building-name="${escapeHtml(
                            buildingName
                        )}"
                        class="
                            inline-flex min-h-[2.625rem]
                            items-center justify-center gap-2
                            rounded-lg
                            border border-[var(--pm-danger-border)]
                            bg-[var(--pm-surface)] px-4 py-2.5
                            text-sm font-semibold
                            text-[var(--pm-danger-text)]
                            transition
                            hover:bg-[var(--pm-danger-background)]
                        "
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M3 6h18"/>
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                            <path d="M10 11v6"/>
                            <path d="M14 11v6"/>
                        </svg>

                        ${escapeHtml(
                            translate(
                                'properties.delete'
                            )
                        )}
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
                        class="pm-button-primary gap-2"
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

                        ${escapeHtml(
                            translate(
                                'properties.add_unit'
                            )
                        )}
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
                        class="pm-button-secondary gap-2"
                    >
                        <span
                            data-property-toggle-label
                        >
                            ${
                                expandUnits
                                    ? translate(
                                        'properties.hide_units'
                                    )
                                    : translate(
                                        'properties.view_units'
                                    )
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
                    border-t border-[var(--pm-border-subtle)]
                    bg-[var(--pm-surface-subtle)]
                    px-5 py-4
                "
            >
                <div
                    class="
                        mb-2 text-xs font-semibold
                        uppercase tracking-[0.12em]
                        text-[var(--pm-text-subtle)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'properties.units'
                        )
                    )}
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
                    text-xs text-[var(--pm-text-subtle)]
                "
            >
                ${escapeHtml(
                    translate(
                        'properties.no_ownership_information'
                    )
                )}
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
                    || translate(
                        'properties.owner'
                    );

                const percentage =
                    ownership
                        .ownership_percentage;

                return `
                    <span
                        class="
                            inline-flex items-center
                            rounded-full
                            bg-[var(--pm-surface-muted)]
                            px-2.5 py-1
                            text-xs font-medium
                            text-[var(--pm-text-secondary)]
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
 * Render the classification badge for a Unit.
 *
 * Commercial Units use the info status tokens; residential Units use the
 * neutral surface tokens.
 *
 * @param {object} unit
 * @returns {string}
 */
/**
 * V1.0.8: vacant / occupied pill derived from the server-side
 * is_occupied flag (an active or notice Lease on the Unit).
 */
function unitOccupancyBadge(
    unit
) {
    const occupied =
        Boolean(
            unit?.is_occupied
        );

    const classes =
        occupied
            ? 'border border-[var(--pm-success-border)] '
                + 'bg-[var(--pm-success-background)] '
                + 'text-[var(--pm-success-text)]'
            : 'border border-[var(--pm-border)] '
                + 'bg-[var(--pm-surface-muted)] '
                + 'text-[var(--pm-text-secondary)]';

    return `
        <span
            class="
                inline-flex rounded-full
                px-2 py-0.5
                text-xs font-medium
                ${classes}
            "
        >
            ${escapeHtml(
                translate(
                    occupied
                        ? 'properties.occupied'
                        : 'properties.vacant'
                )
            )}
        </span>
    `;
}


function unitClassificationBadge(
    unit
) {
    const commercial =
        Boolean(
            unit?.is_commercial
        );

    const badgeClasses =
        commercial
            ? 'border-[var(--pm-info-border)] bg-[var(--pm-info-background)] text-[var(--pm-info-text)]'
            : 'border-[var(--pm-border)] bg-[var(--pm-surface-muted)] text-[var(--pm-text-secondary)]';

    return `
        <span
            class="
                inline-flex whitespace-nowrap
                rounded-full border
                px-2.5 py-1
                text-xs font-medium
                ${badgeClasses}
            "
        >
            ${escapeHtml(
                translate(
                    commercial
                        ? 'properties.commercial'
                        : 'properties.residential'
                )
            )}
        </span>
    `;
}

/**
 * Render the Units of a Building as a table.
 *
 * The table receives the classification-filtered Unit collection; the
 * total Unit count distinguishes "the Building has no Units" from "no
 * Unit matches the active classification filter".
 *
 * @param {object} building
 * @param {Array} units
 * @param {string} buildingName
 * @param {Set<string>} matchingUnitIds
 * @param {number} totalUnitCount
 * @returns {string}
 */
function renderPropertyUnits(
    building,
    units,
    buildingName,
    matchingUnitIds = new Set(),
    totalUnitCount = 0
) {
    if (
        totalUnitCount === 0
    ) {
        return `
            <div
                class="
                    py-5 text-sm
                    text-[var(--pm-text-subtle)]
                "
            >
                ${escapeHtml(
                    translate(
                        'properties.no_units'
                    )
                )}
            </div>
        `;
    }

    if (
        units.length === 0
    ) {
        return `
            <div
                class="
                    py-5 text-sm
                    text-[var(--pm-text-subtle)]
                "
            >
                ${escapeHtml(
                    translate(
                        'properties.no_units_match_filter'
                    )
                )}
            </div>
        `;
    }

    const rows =
        units
            .map(
                (unit) => {
                    const matchesSearch =
                        matchingUnitIds.has(
                            String(
                                unit.id
                            )
                        );

                    const unitName =
                        unit.name
                        || translate(
                            'properties.unnamed_unit'
                        );

                    return `
                        <tr
                            data-property-unit-id="${escapeHtml(
                                unit.id
                            )}"
                            class="
                                border-b border-[var(--pm-border-subtle)]
                                last:border-b-0
                                ${
                                    matchesSearch
                                        ? 'bg-[var(--pm-selected)]'
                                        : ''
                                }
                            "
                        >
                            <td
                                class="
                                    px-3 py-3 text-sm
                                    font-medium
                                    text-[var(--pm-text)]
                                "
                            >
                                ${escapeHtml(
                                    unitName
                                )}
                            </td>

                            <td class="px-3 py-3">
                                <div class="flex items-center gap-1.5">
                                    ${unitClassificationBadge(
                                        unit
                                    )}

                                    ${unitOccupancyBadge(
                                        unit
                                    )}
                                </div>
                            </td>

                            <td
                                class="
                                    max-w-[22rem] px-3 py-3
                                    text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                ${
                                    unit.description
                                        ? escapeHtml(
                                            unit.description
                                        )
                                        : '—'
                                }
                            </td>

                            <td class="px-3 py-3">
                                <div
                                    class="
                                        flex items-center
                                        justify-end gap-2
                                    "
                                >
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
                                            border border-[var(--pm-border)]
                                            bg-[var(--pm-surface)] px-3 py-1.5
                                            text-xs font-medium
                                            text-[var(--pm-text-secondary)]
                                            transition
                                            hover:bg-[var(--pm-hover)]
                                        "
                                    >
                                        ${escapeHtml(
                                            translate(
                                                'properties.edit'
                                            )
                                        )}
                                    </button>

                                    <button
                                        type="button"
                                        data-delete-unit
                                        data-unit-id="${escapeHtml(
                                            unit.id
                                        )}"
                                        data-unit-name="${escapeHtml(
                                            unitName
                                        )}"
                                        data-building-id="${escapeHtml(
                                            building.id
                                        )}"
                                        data-building-name="${escapeHtml(
                                            buildingName
                                        )}"
                                        class="
                                            rounded-lg
                                            border border-[var(--pm-danger-border)]
                                            bg-[var(--pm-surface)] px-3 py-1.5
                                            text-xs font-medium
                                            text-[var(--pm-danger-text)]
                                            transition
                                            hover:bg-[var(--pm-danger-background)]
                                        "
                                    >
                                        ${escapeHtml(
                                            translate(
                                                'properties.delete'
                                            )
                                        )}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            )
            .join('');

    return `
        <div class="overflow-x-auto">
            <table
                class="
                    w-full min-w-[560px]
                    text-left
                "
            >
                <thead>
                    <tr
                        class="
                            border-b border-[var(--pm-border-subtle)]
                            text-xs font-semibold uppercase
                            tracking-[0.08em]
                            text-[var(--pm-text-subtle)]
                        "
                    >
                        <th
                            class="
                                px-3 py-2 font-semibold
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'properties.unit'
                                )
                            )}
                        </th>

                        <th
                            class="
                                px-3 py-2 font-semibold
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'properties.classification'
                                )
                            )}
                        </th>

                        <th
                            class="
                                px-3 py-2 font-semibold
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'properties.description'
                                )
                            )}
                        </th>

                        <th
                            class="
                                px-3 py-2 text-right
                                font-semibold
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'properties.actions'
                                )
                            )}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    ${rows}
                </tbody>
            </table>
        </div>
    `;
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
                ? translate(
                    'properties.view_units'
                )
                : translate(
                    'properties.hide_units'
                );
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
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'properties.page'
                    )
                )} ${currentPage}
                ${escapeHtml(
                    translate(
                        'properties.of'
                    )
                )} ${lastPage}
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
                        pm-button-secondary
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'properties.previous'
                        )
                    )}
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
                        pm-button-secondary
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    ${escapeHtml(
                        translate(
                            'properties.next'
                        )
                    )}
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
                && modal
                    .classList
                    .contains(
                        'pm-drawer-active'
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
            'property-modal-title-text'
        );

    const description =
        document.getElementById(
            'property-modal-description-text'
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
                ? translate(
                    'properties.edit_property'
                )
                : translate(
                    'properties.add_property'
                );
    }

    if (description) {
        description.textContent =
            editing
                ? translate(
                    'properties.edit_property_description'
                )
                : translate(
                    'properties.add_property_description'
                );
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
            translate(
                'properties.save'
            );
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
                : translate(
                    'properties.unable_to_load_owners'
                )
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
                : translate(
                    'properties.unable_to_load_property'
                )
        );
    }
}

/**
 * Make the shared property modal visible.
 */
function showPropertyModal() {
    openDrawer(
        'property-modal'
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
    closeDrawer(
        'property-modal',
        {
            onClosed: () => {
                resetPropertyForm();

                editingPropertyId =
                    null;

                configurePropertyModal(
                    'create'
                );
            },
        }
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
        || `${translate(
            'properties.party'
        )} #${party?.id ?? ''}`;
}

/**
 * Build Owner <option> elements.
 *
 * @param {number|string|null} selectedPartyId
 * @returns {string}
 */
/**
 * V1.0.8: display name for a preselected owner id (edit mode / after
 * inline owner creation).
 */
function ownerPartyNameById(
    partyId
) {
    if (! partyId) {
        return '';
    }

    const party =
        availableOwnerParties.find(
            (candidate) =>
                Number(candidate.id)
                === Number(partyId)
        );

    return party
        ? ownerPartyDisplayName(
            party
        )
        : '';
}

let ownerSearchTimer = null;

/**
 * Wire the searchable owner pickers once through delegation, so the
 * dynamically added rows need no per-row setup.
 */
function initializeOwnerPickers() {
    const container =
        document.getElementById(
            'property-owner-rows'
        );

    if (! container) {
        return;
    }

    container.addEventListener(
        'input',
        (event) => {
            const input =
                event.target;

            if (
                ! (input instanceof HTMLInputElement)
                || input.dataset.ownerSearch === undefined
            ) {
                return;
            }

            const picker =
                input.closest(
                    '[data-owner-picker]'
                );

            /*
             * Typing over a selection clears it until a result is
             * picked again.
             */
            picker
                ?.querySelector(
                    '[data-owner-party]'
                )
                ?.setAttribute(
                    'value',
                    ''
                );

            const hidden =
                picker?.querySelector(
                    '[data-owner-party]'
                );

            if (hidden) {
                hidden.value = '';
            }

            renderOwnerSearchResults(
                picker,
                input.value
            );

            /*
             * Remote refinement: parties beyond the preloaded page.
             */
            window.clearTimeout(
                ownerSearchTimer
            );

            const term =
                input.value.trim();

            if (term === '') {
                return;
            }

            ownerSearchTimer =
                window.setTimeout(
                    async () => {
                        try {
                            const response =
                                await apiRequest(
                                    `/api/parties?role=owner&search=${encodeURIComponent(
                                        term
                                    )}&per_page=15`
                                );

                            const payload =
                                await parseJsonResponse(
                                    response
                                );

                            const remote =
                                Array.isArray(
                                    payload?.data
                                )
                                    ? payload.data
                                    : [];

                            /*
                             * Merge unseen owners into the local list
                             * so later lookups resolve their names.
                             */
                            remote.forEach(
                                (party) => {
                                    if (
                                        ! availableOwnerParties.some(
                                            (known) =>
                                                Number(known.id)
                                                === Number(party.id)
                                        )
                                    ) {
                                        availableOwnerParties.push(
                                            party
                                        );
                                    }
                                }
                            );

                            if (
                                document.activeElement
                                === input
                            ) {
                                renderOwnerSearchResults(
                                    picker,
                                    input.value
                                );
                            }
                        } catch {
                            /*
                             * Local results remain shown.
                             */
                        }
                    },
                    250
                );
        }
    );

    container.addEventListener(
        'focusin',
        (event) => {
            const input =
                event.target;

            if (
                input instanceof HTMLInputElement
                && input.dataset.ownerSearch !== undefined
            ) {
                renderOwnerSearchResults(
                    input.closest(
                        '[data-owner-picker]'
                    ),
                    input.value
                );
            }
        }
    );

    /*
     * Result selection, also via delegation.
     */
    container.addEventListener(
        'click',
        (event) => {
            const option =
                event.target.closest(
                    '[data-owner-option]'
                );

            if (! option) {
                return;
            }

            const picker =
                option.closest(
                    '[data-owner-picker]'
                );

            const hidden =
                picker?.querySelector(
                    '[data-owner-party]'
                );

            const search =
                picker?.querySelector(
                    '[data-owner-search]'
                );

            if (hidden) {
                hidden.value =
                    option.dataset.ownerOption;
            }

            if (search) {
                search.value =
                    ownerPartyNameById(
                        option.dataset.ownerOption
                    );
            }

            picker
                ?.querySelector(
                    '[data-owner-results]'
                )
                ?.classList.add(
                    'hidden'
                );
        }
    );

    /*
     * Clicking outside any picker closes its results.
     */
    document.addEventListener(
        'click',
        (event) => {
            document
                .querySelectorAll(
                    '[data-owner-picker]'
                )
                .forEach(
                    (picker) => {
                        if (
                            ! picker.contains(
                                event.target
                            )
                        ) {
                            picker
                                .querySelector(
                                    '[data-owner-results]'
                                )
                                ?.classList.add(
                                    'hidden'
                                );
                        }
                    }
                );
        }
    );
}

/**
 * Filter the owner list for one picker and render its dropdown.
 */
function renderOwnerSearchResults(
    picker,
    term
) {
    const results =
        picker?.querySelector(
            '[data-owner-results]'
        );

    if (! results) {
        return;
    }

    const normalized =
        String(
            term
            ?? ''
        )
            .trim()
            .toLowerCase();

    const matches =
        availableOwnerParties
            .filter(
                (party) =>
                    normalized === ''
                    || [
                        party.name,
                        party.legal_name,
                        party.phone,
                        party.email,
                    ]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase()
                        .includes(normalized)
            )
            .slice(0, 12);

    if (matches.length === 0) {
        results.innerHTML = `
            <div
                class="
                    px-4 py-4
                    text-center text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'properties.no_matching_owners'
                    )
                )}
            </div>
        `;

        results.classList.remove(
            'hidden'
        );

        return;
    }

    results.innerHTML =
        matches
            .map(
                (party) => `
                    <button
                        type="button"
                        data-owner-option="${escapeHtml(
                            party.id
                        )}"
                        class="
                            block w-full
                            border-b border-[var(--pm-border-subtle)]
                            px-4 py-2.5
                            text-left text-sm
                            transition
                            last:border-b-0
                            hover:bg-[var(--pm-hover)]
                        "
                    >
                        <span class="font-medium text-[var(--pm-text)]">
                            ${escapeHtml(
                                ownerPartyDisplayName(
                                    party
                                )
                            )}
                        </span>

                        ${
                            party.phone || party.email
                                ? `
                                    <span
                                        class="
                                            block text-xs
                                            text-[var(--pm-text-muted)]
                                        "
                                    >
                                        ${escapeHtml(
                                            [
                                                party.phone,
                                                party.email,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')
                                        )}
                                    </span>
                                `
                                : ''
                        }
                    </button>
                `
            )
            .join('');

    results.classList.remove(
        'hidden'
    );
}

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
                    ? translate(
                        'properties.create_owner_first'
                    )
                    : translate(
                        'properties.select_owner'
                    )
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
        'property-owner-row grid gap-3 rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface-subtle)] p-3 sm:grid-cols-[1fr_150px_auto] sm:items-end';

    row.innerHTML = `
        <div>
            <label
                class="pm-field-label"
            >
                ${escapeHtml(
                    translate(
                        'properties.owner'
                    )
                )}
            </label>

            <div class="flex gap-2">
                <div
                    class="relative min-w-0 flex-1"
                    data-owner-picker
                >
                    <input
                        type="hidden"
                        data-owner-party
                        value="${escapeHtml(
                            selectedPartyId
                            ?? ''
                        )}"
                    >

                    <input
                        type="search"
                        data-owner-search
                        autocomplete="off"
                        placeholder="${escapeHtml(
                            translate(
                                'properties.search_owner_placeholder'
                            )
                        )}"
                        value="${escapeHtml(
                            ownerPartyNameById(
                                selectedPartyId
                            )
                        )}"
                        class="pm-input"
                    >

                    <div
                        data-owner-results
                        class="pm-card absolute z-50 mt-1 hidden max-h-72 w-full overflow-y-auto shadow-xl"
                    ></div>
                </div>

                <button
                    type="button"
                    data-create-owner
                    class="
                        shrink-0 rounded-lg
                        border border-[var(--pm-border)]
                        bg-[var(--pm-surface)] px-3 py-2.5
                        text-sm font-medium
                        text-[var(--pm-accent)]
                        transition
                        hover:bg-[var(--pm-hover)]
                    "
                    title="${escapeHtml(
                        translate(
                            'properties.create_new_owner'
                        )
                    )}"
                >
                    ${escapeHtml(
                        translate(
                            'properties.new'
                        )
                    )}
                </button>
            </div>

            ${
                availableOwnerParties
                    .length === 0
                    ? `
                        <p
                            class="
                                mt-1.5 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'properties.no_owners_yet'
                                )
                            )}
                        </p>
                    `
                    : ''
            }
        </div>

        <div>
            <label
                class="pm-field-label"
            >
                ${escapeHtml(
                    translate(
                        'properties.ownership_percentage'
                    )
                )}
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
                class="pm-input"
            >
        </div>

        <button
            type="button"
            data-remove-owner
            class="
                inline-flex h-10
                items-center justify-center
                rounded-lg
                border border-[var(--pm-border)]
                bg-[var(--pm-surface)] px-3
                text-sm text-[var(--pm-text-muted)]
                transition
                hover:border-[var(--pm-danger-border)]
                hover:bg-[var(--pm-danger-background)]
                hover:text-[var(--pm-danger-text)]
            "
        >
            ${escapeHtml(
                translate(
                    'properties.remove'
                )
            )}
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
                        translate('properties.validation_owner_required')
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
        `${translate(
            'properties.total'
        )}: ${normalized}%`;

    output.classList.remove(
        'pm-ownership-total-valid',
        'pm-ownership-total-incomplete',
        'pm-ownership-total-excess'
    );

    if (
        Math.abs(
            total - 100
        ) < 0.001
    ) {
        output.classList.add(
            'pm-ownership-total-valid'
        );
    } else if (
        total > 100
    ) {
        output.classList.add(
            'pm-ownership-total-excess'
        );
    } else {
        output.classList.add(
            'pm-ownership-total-incomplete'
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
        'property-unit-row grid gap-3 rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface-subtle)] p-3 sm:grid-cols-[220px_1fr_auto] sm:items-end';

    row.innerHTML = `
        <div>
            <label
                class="pm-field-label"
            >
                ${escapeHtml(
                    translate(
                        'properties.unit_name_number'
                    )
                )}
            </label>

            <input
                data-unit-name
                type="text"
                required
                maxlength="255"
                value="${escapeHtml(
                    name
                )}"
                placeholder="${escapeHtml(
                    translate(
                        'properties.unit_name_placeholder'
                    )
                )}"
                class="pm-input"
            >
        </div>

        <div>
            <label
                class="pm-field-label"
            >
                ${escapeHtml(
                    translate(
                        'properties.description'
                    )
                )}
            </label>

            <input
                data-unit-description
                type="text"
                value="${escapeHtml(
                    description
                )}"
                placeholder="${escapeHtml(
                    translate(
                        'properties.optional_description'
                    )
                )}"
                class="pm-input"
            >
        </div>

        <button
            type="button"
            data-remove-unit
            class="
                inline-flex h-10
                items-center justify-center
                rounded-lg
                border border-[var(--pm-border)]
                bg-[var(--pm-surface)] px-3
                text-sm text-[var(--pm-text-muted)]
                transition
                hover:border-[var(--pm-danger-border)]
                hover:bg-[var(--pm-danger-background)]
                hover:text-[var(--pm-danger-text)]
            "
        >
            ${escapeHtml(
                translate(
                    'properties.remove'
                )
            )}
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
                        translate('properties.validation_unit_required')
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
            translate('properties.validation_owner_required')
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
            translate('properties.validation_select_every_owner')
        );
    }

    if (
        new Set(
            ownerIds
        ).size
        !== ownerIds.length
    ) {
        throw new Error(
            translate('properties.validation_duplicate_owner')
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
            translate('properties.validation_owner_percentage')
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
            translate('properties.validation_ownership_total')
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
            translate('properties.validation_unit_required')
        );
    }

    if (
        units.some(
            (unit) =>
                unit.name === ''
        )
    ) {
        throw new Error(
            translate('properties.validation_every_unit_name')
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
            translate('properties.validation_unique_unit_names')
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
                ? translate(
                    'properties.saving_changes'
                )
                : translate(
                    'properties.creating_property'
                );

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

        showPropertiesBanner(
            editing
                ? translate(
                    'properties.property_updated'
                )
                : translate(
                    'properties.property_created'
                )
        );

        await refreshPropertyPortfolio();
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? translate(
                            'properties.unable_to_update_property'
                        )
                        : translate(
                            'properties.unable_to_create_property'
                        )
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            translate(
                'properties.save'
            );
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
| Page Activity Feedback
|--------------------------------------------------------------------------
*/

/**
 * Show the page activity banner after a successful mutation.
 *
 * The banner reuses the page's inline banner pattern (the same markup
 * shape as #properties-error) with the success status tokens and hides
 * itself automatically.
 *
 * @param {string} message
 */
function showPropertiesBanner(
    message
) {
    const banner =
        document.getElementById(
            'properties-banner'
        );

    if (! banner) {
        return;
    }

    banner.textContent =
        message;

    banner.classList.remove(
        'hidden'
    );

    if (propertiesBannerTimer) {
        window.clearTimeout(
            propertiesBannerTimer
        );
    }

    propertiesBannerTimer =
        window.setTimeout(
            () => {
                banner.classList.add(
                    'hidden'
                );

                banner.textContent =
                    '';
            },
            6000
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
                && modal
                    .classList
                    .contains(
                        'pm-drawer-active'
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

    openDrawer(
        modal
    );

    document
        .getElementById(
            'owner-given-names'
        )
        ?.focus();
}

/**
 * Close the inline Owner creation modal.
 */
function closeOwnerModal() {
    closeDrawer(
        'owner-modal',
        {
            onClosed: () => {
                ownerTargetRow =
                    null;

                hideOwnerFormError();
            },
        }
    );
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
            translate(
                'properties.creating_owner'
            );

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
                : translate(
                    'properties.unable_to_create_owner'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            translate(
                'properties.create_owner'
            );
    }
}

/**
 * Build and validate a Person Owner payload.
 *
 * Person names are captured as Given names + Surname. The API composes
 * the display name server-side from these two fields.
 *
 * @returns {object|null}
 */
function buildPersonOwnerPayload() {
    const givenNames =
        formValue(
            'owner-given-names'
        );

    const surname =
        formValue(
            'owner-surname'
        );

    const telephone =
        readPhoneValue(
            'owner-phone'
        );

    const phone =
        telephone.number ?? '';

    const email =
        formValue(
            'owner-email'
        );

    if (
        givenNames === ''
        || surname === ''
        || phone === ''
        || email === ''
    ) {
        showOwnerFormError(
            translate(
                'properties.person_required_fields'
            )
        );

        return null;
    }

    return {
        type:
            'person',

        given_names:
            givenNames,

        surname,

        phone,

        phone_country:
            telephone.country,

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

    const contactTelephone =
        readPhoneValue(
            'owner-contact-phone'
        );

    const contactPhone =
        contactTelephone.number ?? '';

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
            translate(
                'properties.organisation_required_fields'
            )
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

        contact_person_phone_country:
            contactTelephone.country,

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
            const hidden =
                row.querySelector(
                    '[data-owner-party]'
                );

            const search =
                row.querySelector(
                    '[data-owner-search]'
                );

            if (! hidden) {
                return;
            }

            const shouldSelectNewOwner =
                ownerTargetRow === row
                && newlyCreatedOwnerId
                    !== null;

            if (shouldSelectNewOwner) {
                hidden.value =
                    String(
                        newlyCreatedOwnerId
                    );
            }

            if (search) {
                search.value =
                    ownerPartyNameById(
                        hidden.value
                    );
            }
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
                && modal
                    .classList
                    .contains(
                        'pm-drawer-active'
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
                translate(
                    'properties.unable_to_locate_unit'
                )
            );

            return;
        }

        populateExistingUnitForm(
            unit
        );
    }

    openDrawer(
        modal
    );

    window.setTimeout(
        () => {
            document
                .getElementById(
                    'existing-unit-name'
                )
                ?.focus();
        },
        50
    );
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
            || translate(
                'properties.property'
            );
    }

    if (title) {
        title.textContent =
            editing
                ? translate(
                    'properties.edit_unit'
                )
                : translate(
                    'properties.add_unit'
                );
    }

    if (descriptionElement) {
        descriptionElement.textContent =
            editing
                ? translate(
                    'properties.edit_unit_description'
                )
                : translate(
                    'properties.add_unit_description'
                );
    }

    if (submitButton) {
        submitButton.textContent =
            translate(
                'properties.save'
            );
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

    const commercialInput =
        document.getElementById(
            'existing-unit-commercial'
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

    if (commercialInput) {
        commercialInput.checked =
            Boolean(
                unit.is_commercial
            );
    }
}

/**
 * Close and reset the shared Unit modal.
 */
function closeExistingUnitModal() {
    const form =
        document.getElementById(
            'existing-unit-form'
        );

    closeDrawer(
        'existing-unit-modal',
        {
            onClosed: () => {
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
            },
        }
    );
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
            translate(
                'properties.add_unit'
            );
    }

    if (descriptionElement) {
        descriptionElement.textContent =
            translate(
                'properties.add_unit_description'
            );
    }

    if (submitButton) {
        submitButton.textContent =
            translate(
                'properties.save'
            );
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

    const isCommercial =
        document
            .getElementById(
                'existing-unit-commercial'
            )
            ?.checked
        === true;

    if (
        ! Number.isInteger(
            buildingId
        )
        || buildingId <= 0
    ) {
        showExistingUnitFormError(
            translate(
                'properties.validation_valid_property'
            )
        );

        return;
    }

    if (name === '') {
        showExistingUnitFormError(
            translate(
                'properties.validation_unit_name_required'
            )
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
                ? translate(
                    'properties.saving_changes'
                )
                : translate(
                    'properties.adding_unit'
                );

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

                            is_commercial:
                                isCommercial,
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        closeExistingUnitModal();

        showPropertiesBanner(
            editing
                ? translate(
                    'properties.unit_updated'
                )
                : translate(
                    'properties.unit_added'
                )
        );

        await refreshPropertyPortfolio();
    } catch (error) {
        showExistingUnitFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? translate(
                            'properties.unable_to_update_unit'
                        )
                        : translate(
                            'properties.unable_to_add_unit'
                        )
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            editing
                ? translate(
                    'properties.save_changes'
                )
                : translate(
                    'properties.add_unit'
                );
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

/*
|--------------------------------------------------------------------------
| Property Deletion
|--------------------------------------------------------------------------
|
| Deleting a Building is destructive and irreversible, so the confirmation
| drawer requires the user to retype the Property name before the DELETE
| request is allowed. A 409 response carries a localized reason explaining
| which dependent records block the deletion; that reason is surfaced
| inline inside the drawer.
|
*/

/**
 * Initialize the Delete Property confirmation drawer.
 */
function initializePropertyDeletion() {
    const modal =
        document.getElementById(
            'delete-property-modal'
        );

    const form =
        document.getElementById(
            'delete-property-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    document
        .getElementById(
            'delete-property-modal-close'
        )
        ?.addEventListener(
            'click',
            closeDeletePropertyModal
        );

    document
        .getElementById(
            'delete-property-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeDeletePropertyModal
        );

    document
        .getElementById(
            'delete-property-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeDeletePropertyModal
        );

    document
        .getElementById(
            'delete-property-confirmation'
        )
        ?.addEventListener(
            'input',
            updateDeletePropertyConfirmationState
        );

    form.addEventListener(
        'submit',
        submitDeletePropertyForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && modal
                    .classList
                    .contains(
                        'pm-drawer-active'
                    )
            ) {
                closeDeletePropertyModal();
            }
        }
    );
}

/**
 * Enable the destructive submit button only when the typed Property name
 * matches the Property being deleted.
 */
function updateDeletePropertyConfirmationState() {
    const submitButton =
        document.getElementById(
            'delete-property-submit-button'
        );

    if (! submitButton) {
        return;
    }

    const typedName =
        formValue(
            'delete-property-confirmation'
        );

    submitButton.disabled =
        deletingPropertyName === ''
        || typedName
            !== deletingPropertyName;
}

/**
 * Open the Delete Property confirmation drawer.
 *
 * @param {string|number} buildingId
 * @param {string} buildingName
 */
function openDeletePropertyModal(
    buildingId,
    buildingName
) {
    const modal =
        document.getElementById(
            'delete-property-modal'
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

    deletingPropertyId =
        numericBuildingId;

    deletingPropertyName =
        String(
            buildingName
            || ''
        ).trim();

    const nameElement =
        document.getElementById(
            'delete-property-name'
        );

    if (nameElement) {
        nameElement.textContent =
            deletingPropertyName
            || translate(
                'properties.unnamed_property'
            );
    }

    const confirmationInput =
        document.getElementById(
            'delete-property-confirmation'
        );

    if (confirmationInput) {
        confirmationInput.value =
            '';
    }

    hideDeletePropertyError();

    updateDeletePropertyConfirmationState();

    openDrawer(
        modal
    );

    window.setTimeout(
        () => {
            confirmationInput?.focus();
        },
        50
    );
}

/**
 * Close and reset the Delete Property confirmation drawer.
 */
function closeDeletePropertyModal() {
    closeDrawer(
        'delete-property-modal',
        {
            onClosed: () => {
                deletingPropertyId =
                    null;

                deletingPropertyName =
                    '';

                const confirmationInput =
                    document.getElementById(
                        'delete-property-confirmation'
                    );

                if (confirmationInput) {
                    confirmationInput.value =
                        '';
                }

                hideDeletePropertyError();

                updateDeletePropertyConfirmationState();
            },
        }
    );
}

/**
 * Delete the confirmed Building.
 *
 * A 409 response means dependent records block the deletion; the server's
 * localized reason is shown inline.
 */
async function submitDeletePropertyForm(
    event
) {
    event.preventDefault();

    const submitButton =
        document.getElementById(
            'delete-property-submit-button'
        );

    if (
        ! submitButton
        || ! Number.isInteger(
            deletingPropertyId
        )
        || deletingPropertyId <= 0
    ) {
        return;
    }

    const typedName =
        formValue(
            'delete-property-confirmation'
        );

    if (
        typedName
        !== deletingPropertyName
    ) {
        showDeletePropertyError(
            translate(
                'properties.type_name_to_confirm'
            )
        );

        return;
    }

    hideDeletePropertyError();

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            translate(
                'properties.deleting'
            );

        if (
            ! await requireDangerConfirmation({
                entityLabel: String(deletingPropertyName ?? ""),
            })
        ) {
            return;
        }

        const response =
            await apiRequest(
                `/api/buildings/${deletingPropertyId}`,
                {
                    method:
                        'DELETE',
                }
            );

        await parseJsonResponse(
            response
        );

        closeDeletePropertyModal();

        showPropertiesBanner(
            translate(
                'properties.property_deleted'
            )
        );

        await refreshPropertyPortfolio();
    } catch (error) {
        showDeletePropertyError(
            error instanceof Error
                ? error.message
                : translate(
                    'properties.unable_to_delete_property'
                )
        );
    } finally {
        submitButton.textContent =
            translate(
                'properties.delete_property'
            );

        updateDeletePropertyConfirmationState();
    }
}

function showDeletePropertyError(
    message
) {
    const box =
        document.getElementById(
            'delete-property-error'
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

function hideDeletePropertyError() {
    const box =
        document.getElementById(
            'delete-property-error'
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
| Unit Deletion
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Delete Unit confirmation drawer.
 */
function initializeUnitDeletion() {
    const modal =
        document.getElementById(
            'delete-unit-modal'
        );

    const form =
        document.getElementById(
            'delete-unit-form'
        );

    if (
        ! modal
        || ! form
    ) {
        return;
    }

    document
        .getElementById(
            'delete-unit-modal-close'
        )
        ?.addEventListener(
            'click',
            closeDeleteUnitModal
        );

    document
        .getElementById(
            'delete-unit-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeDeleteUnitModal
        );

    document
        .getElementById(
            'delete-unit-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeDeleteUnitModal
        );

    form.addEventListener(
        'submit',
        submitDeleteUnitForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && modal
                    .classList
                    .contains(
                        'pm-drawer-active'
                    )
            ) {
                closeDeleteUnitModal();
            }
        }
    );
}

/**
 * Open the Delete Unit confirmation drawer.
 *
 * @param {string|number} unitId
 * @param {string} unitName
 * @param {string} buildingName
 */
function openDeleteUnitModal(
    unitId,
    unitName,
    buildingName
) {
    const modal =
        document.getElementById(
            'delete-unit-modal'
        );

    const numericUnitId =
        Number(
            unitId
        );

    if (
        ! modal
        || ! Number.isInteger(
            numericUnitId
        )
        || numericUnitId <= 0
    ) {
        return;
    }

    deletingUnitId =
        numericUnitId;

    deletingUnitName =
        String(
            unitName
            ?? ''
        );

    const unitNameElement =
        document.getElementById(
            'delete-unit-name'
        );

    if (unitNameElement) {
        unitNameElement.textContent =
            unitName
            || translate(
                'properties.unnamed_unit'
            );
    }

    const buildingNameElement =
        document.getElementById(
            'delete-unit-building-name'
        );

    if (buildingNameElement) {
        buildingNameElement.textContent =
            buildingName
            || translate(
                'properties.property'
            );
    }

    hideDeleteUnitError();

    openDrawer(
        modal
    );
}

/**
 * Close and reset the Delete Unit confirmation drawer.
 */
function closeDeleteUnitModal() {
    closeDrawer(
        'delete-unit-modal',
        {
            onClosed: () => {
                deletingUnitId =
                    null;

                deletingUnitName =
                    '';

                hideDeleteUnitError();
            },
        }
    );
}

/**
 * Delete the confirmed Unit.
 *
 * A 409 response means dependent records block the deletion; the server's
 * localized reason is shown inline.
 */
async function submitDeleteUnitForm(
    event
) {
    event.preventDefault();

    const submitButton =
        document.getElementById(
            'delete-unit-submit-button'
        );

    if (
        ! submitButton
        || ! Number.isInteger(
            deletingUnitId
        )
        || deletingUnitId <= 0
    ) {
        return;
    }

    hideDeleteUnitError();

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            translate(
                'properties.deleting'
            );

        if (
            ! await requireDangerConfirmation({
                entityLabel: String(deletingUnitName ?? ""),
            })
        ) {
            return;
        }

        const response =
            await apiRequest(
                `/api/units/${deletingUnitId}`,
                {
                    method:
                        'DELETE',
                }
            );

        await parseJsonResponse(
            response
        );

        closeDeleteUnitModal();

        showPropertiesBanner(
            translate(
                'properties.unit_deleted'
            )
        );

        await refreshPropertyPortfolio();
    } catch (error) {
        showDeleteUnitError(
            error instanceof Error
                ? error.message
                : translate(
                    'properties.unable_to_delete_unit'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            translate(
                'properties.delete_unit'
            );
    }
}

function showDeleteUnitError(
    message
) {
    const box =
        document.getElementById(
            'delete-unit-error'
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

function hideDeleteUnitError() {
    const box =
        document.getElementById(
            'delete-unit-error'
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
