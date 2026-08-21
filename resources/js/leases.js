/*
|--------------------------------------------------------------------------
| Patrimoine Leases
|--------------------------------------------------------------------------
|
| Browser-side functionality for Lease management.
|
| This module owns:
|
| - Lease listing;
| - status, tenant, building, frequency and expiry filtering;
| - pagination;
| - Lease creation;
| - Lease deletion;
| - Unit selection;
| - Tenant and Agent Party selection;
| - Lease contractual terms;
| - scheduled rent increments (list, schedule, cancel).
|
| General financial transactions such as Payments, Invoices and owner
| accounting remain in their dedicated modules. Tenant Funds and Security
| Deposit operations live in the Tenants workspace.
|
*/

import {
    apiRequest,
    closeDrawer,
    escapeHtml,
    formValue,
    formatCurrency,
    formatDate,
    getPresentationConfiguration,
    nullableFormValue,
    openDrawer,
    parseJsonResponse,
    setText,
    translate,
} from './core.js';

import {
    browserCan,
} from './permissions.js';

import {
    dateForApi,
    dateForDisplay,
    initializeDateInputs,
    openDatePicker,
} from './date-input.js';

/*
|--------------------------------------------------------------------------
| Module State
|--------------------------------------------------------------------------
*/

let loadedLeasesById =
    new Map();

let availableUnits =
    [];

let availableBuildings =
    [];

let availableTenants =
    [];

let availableAgents =
    [];

let defaultVatRate =
    '18.00';

/*
 * Drawer open/close mechanics (classes, aria, body scroll, timers) are
 * owned by the shared openDrawer/closeDrawer helpers in core.js.
 */

/*
 * Controlled V1.0.5 Extend drawer state.
 */
let extendingLeaseId =
    null;

/*
 * Controlled V1.0.5 Termination drawer state.
 */
let terminatingLeaseId =
    null;

/*
 * Controlled V1.0.5 destructive Lease Delete drawer state.
 */
let deletingLeaseId =
    null;

/*
|--------------------------------------------------------------------------
| Public Initializer
|--------------------------------------------------------------------------
*/

/**
 * Initialize the Lease UI when the current document contains the Lease list.
 */
export async function initializeLeases() {
    const container =
        document.getElementById(
            'leases-list'
        );

    if (! container) {
        return;
    }

    initializeLeaseFilters();

    initializeLeaseForm();

    initializeLeaseExtendDrawer();

    initializeLeaseTerminationDrawer();

    initializeLeaseDeleteDrawer();

    initializeTerminationSettlementDrawer();

    initializeLeaseFinancialHistoryModal();

    initializeRentIncrementsDrawer();

    initializeLeaseFieldHelp();

    try {
        /*
         * Application-level defaults are loaded before the Lease form is
         * first used so newly created Leases inherit the configured VAT rate.
         *
         * Existing Leases remain unaffected because edit mode populates
         * the VAT field from the Lease's own stored vat_rate value.
         */
        await loadLeaseDefaults();

        /*
         * Tenant data is used by both the list filter and Lease form.
         */
        await loadLeaseReferenceData();

        populateLeaseReferenceControls();

        await loadLeases();
    } catch (error) {
        showLeasePageError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.unable_initialize'
                )
        );
    }
}
/*
|--------------------------------------------------------------------------
| Application Defaults
|--------------------------------------------------------------------------
*/

/**
 * Load application-wide Lease defaults.
 *
 * Lease defaults are presentation/operational configuration rather than
 * Administrator-only Managing Organisation data. Reading them from the
 * presentation endpoint keeps Property Manager and Viewer Lease access
 * independent from the manage_settings capability.
 */
async function loadLeaseDefaults() {
    defaultVatRate =
        '18.00';

    const response =
        await apiRequest(
            '/api/presentation-config'
        );

    const configuration =
        await parseJsonResponse(
            response
        );

    const configuredRate =
        Number(
            configuration
                ?.default_vat_rate
        );

    if (
        Number.isFinite(
            configuredRate
        )
        && configuredRate >= 0
        && configuredRate <= 100
    ) {
        defaultVatRate =
            configuredRate.toFixed(2);
    }
}
/*
|--------------------------------------------------------------------------
| Reference Data
|--------------------------------------------------------------------------
*/

/**
 * Load Units, Tenant Parties and Agent Parties required by Lease workflows.
 */
async function loadLeaseReferenceData() {
    const [
        buildingsResponse,
        tenantsResponse,
        agentsResponse,
    ] = await Promise.all([
        apiRequest(
            '/api/buildings?per_page=100'
        ),

        apiRequest(
            '/api/parties?role=tenant&per_page=100'
        ),

        apiRequest(
            '/api/parties?role=agent&per_page=100'
        ),
    ]);

    const buildingsPayload =
        await parseJsonResponse(
            buildingsResponse
        );

    const tenantsPayload =
        await parseJsonResponse(
            tenantsResponse
        );

    const agentsPayload =
        await parseJsonResponse(
            agentsResponse
        );

    const buildings =
        Array.isArray(
            buildingsPayload?.data
        )
            ? buildingsPayload.data
            : [];

    /*
     * The Building list itself feeds the register's Building filter.
     */
    availableBuildings =
        buildings;

    /*
    * Flatten Building -> Unit relationships into one searchable Unit
    * collection.
    *
    * The full Building context is deliberately retained because the Lease
    * composer shows ownership information immediately after a Unit is
    * selected. Patrimoine V1 defines ownership at Building level, therefore
    * every Unit inherits its Building's ownership structure.
    */
    availableUnits =
        buildings.flatMap(
            (building) =>
                Array.isArray(
                    building.units
                )
                    ? building.units.map(
                        (unit) => ({
                            ...unit,

                            building: {
                                id:
                                    building.id,

                                name:
                                    building.name,

                                address:
                                    building.address,

                                location:
                                    building.location,

                                ownerships:
                                    Array.isArray(
                                        building.ownerships
                                    )
                                        ? building.ownerships
                                        : [],
                            },
                        })
                    )
                    : []
        );

    availableTenants =
        Array.isArray(
            tenantsPayload?.data
        )
            ? tenantsPayload.data
            : [];

    availableAgents =
        Array.isArray(
            agentsPayload?.data
        )
            ? agentsPayload.data
            : [];
}

/**
 * Populate Lease filters and modal selects.
 */
function populateLeaseReferenceControls() {
    populatePartySelect(
        'lease-tenant-filter',
        availableTenants,
        translate(
            'leases.all_tenants'
        )
    );

    populatePartySelect(
        'lease-tenant',
        availableTenants,
        translate(
            'leases.select_tenant'
        )
    );

    populatePartySelect(
        'lease-agent',
        availableAgents,
        translate(
            'leases.no_agent'
        )
    );

    populateBuildingFilter();

    /*
    * Unit selection uses the searchable Unit picker rather than a native
    * select. Refreshing here ensures any newly loaded Unit information is
    * available to the search control.
    */
    refreshUnitSearch();
}

/**
 * Render Party options into a select.
 */
function populatePartySelect(
    elementId,
    parties,
    emptyLabel
) {
    const select =
        document.getElementById(
            elementId
        );

    if (! select) {
        return;
    }

    const previousValue =
        select.value;

    select.innerHTML = `
        <option value="">
            ${escapeHtml(
                emptyLabel
            )}
        </option>

        ${
            parties
                .map(
                    (party) => `
                        <option
                            value="${escapeHtml(
                                party.id
                            )}"
                        >
                            ${escapeHtml(
                                partyDisplayName(
                                    party
                                )
                            )}
                        </option>
                    `
                )
                .join('')
        }
    `;

    if (previousValue !== '') {
        select.value =
            previousValue;
    }
}


function partyDisplayName(party) {
    return party?.name
        || party?.legal_name
        || `Party #${party?.id ?? ''}`;
}

/**
 * Render Building options into the register's Building filter.
 */
function populateBuildingFilter() {
    const select =
        document.getElementById(
            'lease-building-filter'
        );

    if (! select) {
        return;
    }

    const previousValue =
        select.value;

    select.innerHTML = `
        <option value="">
            ${escapeHtml(
                translate(
                    'leases.all_buildings'
                )
            )}
        </option>

        ${
            availableBuildings
                .map(
                    (building) => `
                        <option
                            value="${escapeHtml(
                                building.id
                            )}"
                        >
                            ${escapeHtml(
                                building.name
                                || `Building #${building.id ?? ''}`
                            )}
                        </option>
                    `
                )
                .join('')
        }
    `;

    if (previousValue !== '') {
        select.value =
            previousValue;
    }
}


/*
|--------------------------------------------------------------------------
| Searchable Unit Picker
|--------------------------------------------------------------------------
|
| Patrimoine installations may eventually contain hundreds or thousands
| of Units. A standard HTML select therefore becomes impractical.
|
| The Lease composer uses an in-memory search over the Units already
| returned with the Building reference data.
|
| The selected Unit ID is stored in the hidden #lease-unit input so the
| remainder of the Lease submission code can continue treating unit_id
| exactly as it did previously.
|
*/

let selectedLeaseUnit =
    null;

/**
 * Configure the searchable Unit control.
 */
function initializeUnitSearch() {
    const input =
        document.getElementById(
            'lease-unit-search'
        );

    const results =
        document.getElementById(
            'lease-unit-results'
        );

    if (! input || ! results) {
        return;
    }

    input.addEventListener(
        'input',
        () => {
            /*
             * Typing after a Unit has been selected means the user is
             * looking for another Unit.
             */
            const selectedLabel =
                selectedLeaseUnit
                    ? unitSearchLabel(
                        selectedLeaseUnit
                    )
                    : '';

            if (
                selectedLeaseUnit
                && input.value.trim()
                    !== selectedLabel
            ) {
                clearSelectedUnit(
                    false
                );
            }

            renderUnitSearchResults(
                input.value
            );
        }
    );

    input.addEventListener(
        'focus',
        () => {
            if (! selectedLeaseUnit) {
                renderUnitSearchResults(
                    input.value
                );
            }
        }
    );

    document
        .getElementById(
            'lease-unit-clear'
        )
        ?.addEventListener(
            'click',
            () => {
                clearSelectedUnit();

                input.focus();
            }
        );

    /*
     * Clicking elsewhere closes the results without clearing the selected
     * Unit.
     */
    document.addEventListener(
        'click',
        (event) => {
            const picker =
                document.getElementById(
                    'lease-unit-picker'
                );

            if (
                picker
                && ! picker.contains(
                    event.target
                )
            ) {
                hideUnitSearchResults();
            }
        }
    );
}

/**
 * Refresh the Unit search control after reference data is loaded.
 */
function refreshUnitSearch() {
    if (
        selectedLeaseUnit
        && ! availableUnits.some(
            (unit) =>
                Number(unit.id)
                === Number(
                    selectedLeaseUnit.id
                )
        )
    ) {
        clearSelectedUnit();
    }
}

/**
 * Return one human-readable Building / Unit label.
 */
function unitSearchLabel(unit) {
    return [
        unit?.building?.name,
        unit?.name,
    ]
        .filter(Boolean)
        .join(' / ');
}

/**
 * Return all searchable text associated with a Unit.
 *
 * Owners are included as a convenience so searching an owner's name can
 * also locate the properties they own.
 */
function unitSearchHaystack(unit) {
    const owners =
        Array.isArray(
            unit?.building?.ownerships
        )
            ? unit.building.ownerships
                .map(
                    (ownership) =>
                        partyDisplayName(
                            ownership.party
                        )
                )
            : [];

    return [
        unit?.name,
        unit?.description,
        unit?.building?.name,
        unit?.building?.location,
        unit?.building?.address,
        ...owners,
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
}

/**
 * Show Unit search results matching the current text.
 */
function renderUnitSearchResults(
    search
) {
    const results =
        document.getElementById(
            'lease-unit-results'
        );

    if (! results) {
        return;
    }

    const term =
        String(
            search || ''
        )
            .trim()
            .toLowerCase();

    /*
     * When the search field is empty, display only a small initial sample
     * rather than rendering hundreds of Units.
     */
    const matchingUnits =
        availableUnits
            .filter(
                (unit) =>
                    term === ''
                    || unitSearchHaystack(
                        unit
                    ).includes(term)
            )
            .slice(
                0,
                12
            );

    if (matchingUnits.length === 0) {
        results.innerHTML = `
            <div
                class="
                    px-4 py-5
                    text-center text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'leases.no_matching_units'
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
        matchingUnits
            .map(
                (unit) =>
                    unitSearchResult(
                        unit
                    )
            )
            .join('');

    results.classList.remove(
        'hidden'
    );

    results
        .querySelectorAll(
            '[data-select-unit]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        selectLeaseUnit(
                            button.dataset
                                .unitId
                        );
                    }
                );
            }
        );
}

/**
 * Render one Unit search result.
 */
function unitSearchResult(unit) {
    const building =
        unit?.building?.name
        || translate(
            'leases.property'
        );

    const unitName =
        unit?.name
        || `${translate(
            'leases.unit'
        )} #${unit?.id ?? ''}`;

    const location =
        unit?.building?.location
        || unit?.building?.address
        || '';

    const owners =
        unitOwnerNames(
            unit
        );

    return `
        <button
            type="button"
            data-select-unit
            data-unit-id="${escapeHtml(
                unit.id
            )}"
            class="
                block w-full
                border-b border-[var(--pm-border-subtle)]
                px-4 py-3
                text-left
                transition
                last:border-b-0
                hover:bg-[var(--pm-hover)]
                focus:bg-[var(--pm-hover)]
                focus:outline-none
            "
        >
            <div
                class="
                    text-sm font-medium
                    text-[var(--pm-text)]
                "
            >
                ${escapeHtml(
                    unitName
                )}
            </div>

            <div
                class="
                    mt-0.5 text-xs
                    text-[var(--pm-text-secondary)]
                "
            >
                ${escapeHtml(
                    building
                )}
            </div>

            ${
                location
                    ? `
                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-subtle)]
                            "
                        >
                            ${escapeHtml(
                                location
                            )}
                        </div>
                    `
                    : ''
            }

            ${
                owners.length > 0
                    ? `
                        <div
                            class="
                                mt-1.5 text-xs
                                text-[var(--pm-accent)]
                            "
                        >
                            ${escapeHtml(
                                translate(
                                    'leases.owner'
                                )
                            )}:
                            ${escapeHtml(
                                owners.join(
                                    ', '
                                )
                            )}
                        </div>
                    `
                    : ''
            }
        </button>
    `;
}

/**
 * Return owner names inherited by a Unit through its Building.
 */
function unitOwnerNames(unit) {
    const ownerships =
        Array.isArray(
            unit?.building?.ownerships
        )
            ? unit.building.ownerships
            : [];

    return ownerships
        .map(
            (ownership) =>
                partyDisplayName(
                    ownership.party
                )
        )
        .filter(Boolean);
}

/**
 * Select one Unit from the searchable picker.
 */
function selectLeaseUnit(
    unitId
) {
    const numericUnitId =
        Number(
            unitId
        );

    const unit =
        availableUnits.find(
            (candidate) =>
                Number(candidate.id)
                === numericUnitId
        );

    if (! unit) {
        return;
    }

    selectedLeaseUnit =
        unit;

    setFormValue(
        'lease-unit',
        unit.id
    );

    const input =
        document.getElementById(
            'lease-unit-search'
        );

    if (input) {
        input.value =
            unitSearchLabel(
                unit
            );
    }

    renderSelectedUnit(
        unit
    );

    hideUnitSearchResults();
}

/**
 * Clear the selected Unit.
 */
function clearSelectedUnit(
    clearSearch = true
) {
    selectedLeaseUnit =
        null;

    setFormValue(
        'lease-unit',
        ''
    );

    if (clearSearch) {
        setFormValue(
            'lease-unit-search',
            ''
        );
    }

    const selection =
        document.getElementById(
            'lease-unit-selection'
        );

    selection?.classList.add(
        'hidden'
    );

    const owners =
        document.getElementById(
            'lease-unit-owners'
        );

    if (owners) {
        owners.innerHTML = '';
    }
}

/**
 * Display the selected Unit and its inherited Building ownership.
 */
function renderSelectedUnit(unit) {
    const selection =
        document.getElementById(
            'lease-unit-selection'
        );

    if (! selection) {
        return;
    }

    setText(
        'lease-selected-unit-name',
        unitSearchLabel(
            unit
        )
    );

    const location =
        unit?.building?.location
        || unit?.building?.address
        || '';

    setText(
        'lease-selected-unit-location',
        location
    );

    const ownersContainer =
        document.getElementById(
            'lease-unit-owners'
        );

    const ownerships =
        Array.isArray(
            unit?.building?.ownerships
        )
            ? unit.building.ownerships
            : [];

    if (ownersContainer) {
        if (ownerships.length === 0) {
            ownersContainer.innerHTML = `
                <span
                    class="
                        text-sm text-[var(--pm-text-subtle)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.no_ownership_information'
                        )
                    )}
                </span>
            `;
        } else {
            ownersContainer.innerHTML =
                ownerships
                    .map(
                        (ownership) => `
                            <span
                                class="
                                    inline-flex items-center
                                    rounded-full
                                    bg-[var(--pm-surface)]
                                    px-3 py-1.5
                                    text-xs font-medium
                                    text-[var(--pm-text-secondary)]
                                    ring-1 ring-[var(--pm-border)]
                                "
                            >
                                ${escapeHtml(
                                    partyDisplayName(
                                        ownership.party
                                    )
                                )}

                                ${
                                    ownership
                                        .ownership_percentage
                                        !== undefined
                                    && ownership
                                        .ownership_percentage
                                        !== null
                                        ? `
                                            <span
                                                class="
                                                    ml-1
                                                    text-[var(--pm-text-subtle)]
                                                "
                                            >
                                                · ${escapeHtml(
                                                    Number(
                                                        ownership
                                                            .ownership_percentage
                                                    ).toFixed(
                                                        0
                                                    )
                                                )}%
                                            </span>
                                        `
                                        : ''
                                }
                            </span>
                        `
                    )
                    .join('');
        }
    }

    selection.classList.remove(
        'hidden'
    );
}

/**
 * Hide the Unit search-results popup.
 */
function hideUnitSearchResults() {
    document
        .getElementById(
            'lease-unit-results'
        )
        ?.classList.add(
            'hidden'
        );
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

function initializeLeaseFilters() {
    [
        'lease-status-filter',
        'lease-tenant-filter',
        'lease-building-filter',
        'lease-frequency-filter',
    ].forEach(
        (elementId) => {
            document
                .getElementById(
                    elementId
                )
                ?.addEventListener(
                    'change',
                    () => {
                        loadLeases(
                            1
                        );
                    }
                );
        }
    );

    /*
     * The expiry filter is a DD-MM-YYYY date input. Reload only when the
     * field is empty (filter cleared) or contains a complete valid date,
     * so half-typed values never fire spurious requests.
     */
    document
        .getElementById(
            'lease-ending-before-filter'
        )
        ?.addEventListener(
            'change',
            (event) => {
                const value =
                    event.target.value.trim();

                if (
                    value === ''
                    || /^\d{4}-\d{2}-\d{2}$/.test(
                        dateForApi(
                            value
                        )
                    )
                ) {
                    loadLeases(
                        1
                    );
                }
            }
        );
}

function leaseQueryParameters(
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

    const status =
        formValue(
            'lease-status-filter'
        );

    const tenant =
        formValue(
            'lease-tenant-filter'
        );

    const building =
        formValue(
            'lease-building-filter'
        );

    const frequency =
        formValue(
            'lease-frequency-filter'
        );

    const endingBefore =
        dateForApi(
            formValue(
                'lease-ending-before-filter'
            )
        );

    if (status !== '') {
        parameters.set(
            'status',
            status
        );
    }

    if (tenant !== '') {
        parameters.set(
            'tenant_id',
            tenant
        );
    }

    if (building !== '') {
        parameters.set(
            'building_id',
            building
        );
    }

    if (frequency !== '') {
        parameters.set(
            'payment_frequency',
            frequency
        );
    }

    if (
        /^\d{4}-\d{2}-\d{2}$/.test(
            endingBefore
        )
    ) {
        parameters.set(
            'ending_before',
            endingBefore
        );
    }

    return parameters;
}

/*
|--------------------------------------------------------------------------
| Lease Listing
|--------------------------------------------------------------------------
*/

async function loadLeases(
    page = 1
) {
    const container =
        document.getElementById(
            'leases-list'
        );

    if (! container) {
        return;
    }

    hideLeasePageError();

    container.innerHTML = `
        <div
            class="
                py-12 text-center
                text-sm text-[var(--pm-text-subtle)]
            "
        >
            ${escapeHtml(
                translate(
                    'leases.loading'
                )
            )}
        </div>
    `;

    try {
        const parameters =
            leaseQueryParameters(
                page
            );

        const response =
            await apiRequest(
                `/api/leases?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderLeases(
            payload
        );

        renderLeasePagination(
            payload
        );
    } catch (error) {
        container.innerHTML = '';

        showLeasePageError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.unable_load'
                )
        );
    }
}

function renderLeases(payload) {
    const container =
        document.getElementById(
            'leases-list'
        );

    if (! container) {
        return;
    }

    const leases =
        Array.isArray(
            payload?.data
        )
            ? payload.data
            : [];

    loadedLeasesById =
        new Map(
            leases.map(
                (lease) => [
                    String(
                        lease.id
                    ),
                    lease,
                ]
            )
        );

    updateLeaseMetrics(
        payload,
        leases
    );

    if (leases.length === 0) {
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
                        pm-lease-financial-history-title
                        text-sm font-medium
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.none_found'
                        )
                    )}
                </div>

                <div
                    class="
                        pm-lease-financial-history-muted
                        mt-1 text-sm
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.none_found_description'
                        )
                    )}
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        leases
            .map(
                leaseCard
            )
            .join('');

    attachLeaseActionListeners(
        container
    );
}

function updateLeaseMetrics(
    payload,
    leases
) {
    setText(
        'leases-total-count',
        payload?.total
        ?? leases.length
    );

    setText(
        'leases-active-count',
        leases.filter(
            (lease) =>
                lease.status === 'active'
        ).length
    );

    setText(
        'leases-notice-count',
        leases.filter(
            (lease) =>
                lease.status === 'notice'
        ).length
    );

    setText(
        'leases-draft-count',
        leases.filter(
            (lease) =>
                lease.status === 'draft'
        ).length
    );
}

function leaseCard(lease) {
    const building =
        lease.unit?.building?.name
        || translate(
            'leases.property'
        );

    const unit =
        lease.unit?.name
        || translate(
            'leases.unit'
        );

    const tenant =
        partyDisplayName(
            lease.tenant
        );

    const agent =
        lease.agent
            ? partyDisplayName(
                lease.agent
            )
            : null;

    return `
        <article
            class="pm-card mb-4 p-5 last:mb-0"
        >
            <div
                class="
                    flex flex-col gap-5
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
                                text-[var(--pm-text)]
                            "
                        >
                            ${escapeHtml(
                                building
                            )}
                            /
                            ${escapeHtml(
                                unit
                            )}
                        </h3>

                        ${leaseStatusBadge(
                            lease.status
                        )}
                    </div>

                    <div
                        class="
                            mt-2 text-sm
                            text-[var(--pm-text-secondary)]
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'leases.tenant'
                            )
                        )}:
                        <span class="font-medium">
                            ${escapeHtml(
                                tenant
                            )}
                        </span>
                    </div>

                    ${
                        agent
                            ? `
                                <div
                                    class="
                                        mt-1 text-sm
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'leases.agent'
                                        )
                                    )}:
                                    ${escapeHtml(
                                        agent
                                    )}
                                </div>
                            `
                            : ''
                    }

                    <div
                        class="
                            mt-3 flex flex-wrap
                            gap-x-6 gap-y-1
                            text-sm text-[var(--pm-text-muted)]
                        "
                    >
                        <span>
                            ${escapeHtml(
                                formatCurrency(
                                    lease.rent_amount
                                )
                            )}
                            /
                            ${escapeHtml(
                                frequencyLabel(
                                    lease.payment_frequency
                                )
                            )}
                        </span>

                        <span>
                            ${escapeHtml(
                                translate(
                                    'leases.start'
                                )
                            )}:
                            ${escapeHtml(
                                formatDate(
                                    lease.start_date
                                )
                            )}
                        </span>

                        ${
                            lease.end_date
                                ? `
                                    <span>
                                        ${escapeHtml(
                                            translate(
                                                'leases.end'
                                            )
                                        )}:
                                        ${escapeHtml(
                                            formatDate(
                                                lease.end_date
                                            )
                                        )}
                                    </span>
                                `
                                : ''
                        }

                        <span>
                            ${escapeHtml(
                                translate(
                                    'leases.vat'
                                )
                            )}:
                            ${escapeHtml(
                                Number(
                                    lease.vat_rate
                                    ?? 0
                                ).toFixed(2)
                            )}%
                        </span>
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
                        data-financial-history
                        data-lease-id="${escapeHtml(
                            lease.id
                        )}"
                        class="pm-button-secondary"
                    >
                        ${escapeHtml(
                            translate(
                                'leases.financial_history'
                            )
                        )}
                    </button>

                    <button
                        type="button"
                        data-rent-increments
                        data-lease-id="${escapeHtml(
                            lease.id
                        )}"
                        class="pm-button-secondary"
                    >
                        ${escapeHtml(
                            translate(
                                'leases.rent_increments'
                            )
                        )}
                    </button>

                    ${
                        lease.status !== 'notice'
                            ? `
                                <button
                                    type="button"
                                    data-extend-lease
                                    data-lease-id="${escapeHtml(
                                        lease.id
                                    )}"
                                    class="pm-button-secondary"
                                >
                                    ${escapeHtml(
                                        translate(
                                            'leases.extend'
                                        )
                                    )}
                                </button>
                            `
                            : ''
                    }

                    ${
                        lease.status === 'notice'
                            ? `
                                <button
                                    type="button"
                                    data-termination-settlement
                                    data-lease-id="${escapeHtml(
                                        lease.id
                                    )}"
                                    class="
                                        rounded-lg
                                        border border-[var(--pm-warning-border)]
                                        bg-[var(--pm-warning-background)] px-3.5 py-2
                                        text-sm font-medium
                                        text-[var(--pm-warning-text)]
                                        transition
                                        hover:bg-[var(--pm-warning-background)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'leases.termination_settlement'
                                        )
                                    )}
                                </button>
                            `
                            : ''
                    }

                    ${
                        !['notice', 'terminated'].includes(
                            lease.status
                        )
                            ? `
                                <button
                                    type="button"
                                    data-terminate-lease
                                    data-lease-id="${escapeHtml(
                                        lease.id
                                    )}"
                                    class="
                                        rounded-lg
                                        border border-[var(--pm-warning-border)]
                                        bg-[var(--pm-surface)] px-3.5 py-2
                                        text-sm font-medium
                                        text-[var(--pm-warning-text)]
                                        transition
                                        hover:bg-[var(--pm-warning-background)]
                                    "
                                >
                                    ${escapeHtml(
                                        translate(
                                            'leases.terminate'
                                        )
                                    )}
                                </button>
                            `
                            : ''
                    }

                    <button
                        type="button"
                        data-delete-lease
                        data-lease-id="${escapeHtml(
                            lease.id
                        )}"
                        class="
                            rounded-lg
                            border border-[var(--pm-danger-border)]
                            bg-[var(--pm-surface)] px-3.5 py-2
                            text-sm font-medium
                            text-[var(--pm-danger-text)]
                            transition
                            hover:bg-[var(--pm-danger-background)]
                        "
                    >
                        ${escapeHtml(
                            translate(
                                'leases.delete'
                            )
                        )}
                    </button>
                </div>
            </div>
        </article>
    `;
}

function leaseStatusBadge(status) {
    const label =
        statusLabel(
            status
        );

    let classes =
        'bg-[var(--pm-surface-muted)] text-[var(--pm-text-secondary)]';

    if (status === 'active') {
        classes =
            'bg-[var(--pm-success-background)] text-[var(--pm-success-text)]';
    } else if (
        status === 'notice'
    ) {
        classes =
            'bg-[var(--pm-warning-background)] text-[var(--pm-warning-text)]';
    } else if (
        status === 'terminated'
    ) {
        classes =
            'bg-[var(--pm-danger-background)] text-[var(--pm-danger-text)]';
    }

    return `
        <span
            class="
                rounded-full
                px-2.5 py-1
                text-xs font-medium
                ${classes}
            "
        >
            ${escapeHtml(
                label
            )}
        </span>
    `;
}

function statusLabel(status) {
    switch (status) {
        case 'draft':
            return translate(
                'leases.status_draft'
            );

        case 'active':
            return translate(
                'leases.status_active'
            );

        case 'notice':
            return translate(
                'leases.status_notice'
            );

        case 'terminated':
            return translate(
                'leases.status_terminated'
            );

        default:
            return String(
                status || ''
            );
    }
}

function frequencyLabel(frequency) {
    switch (frequency) {
        case 'monthly':
            return translate(
                'leases.frequency_month'
            );

        case 'quarterly':
            return translate(
                'leases.frequency_quarter'
            );

        case 'bi_yearly':
            return translate(
                'leases.frequency_six_months'
            );

        case 'yearly':
            return translate(
                'leases.frequency_year'
            );

        default:
            return String(
                frequency || ''
            );
    }
}

function attachLeaseActionListeners(
    container
) {
    container
        .querySelectorAll(
            '[data-financial-history]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openLeaseFinancialHistoryModal(
                            button.dataset
                                .leaseId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-rent-increments]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openRentIncrementsModal(
                            button.dataset
                                .leaseId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-termination-settlement]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openTerminationSettlementModal(
                            button.dataset
                                .leaseId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-terminate-lease]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openLeaseTerminationModal(
                            button.dataset
                                .leaseId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-extend-lease]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openExtendLeaseModal(
                            button.dataset
                                .leaseId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-delete-lease]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        deleteLease(
                            button.dataset
                                .leaseId
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

function renderLeasePagination(
    payload
) {
    const container =
        document.getElementById(
            'leases-pagination'
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
            <div class="text-sm text-[var(--pm-text-muted)]">
                ${escapeHtml(
                    translate(
                        'leases.page'
                    )
                )} ${currentPage}
                ${escapeHtml(
                    translate(
                        'leases.of'
                    )
                )} ${lastPage}
            </div>

            <div class="flex gap-2">
                <button
                    id="leases-previous"
                    type="button"
                    ${
                        currentPage <= 1
                            ? 'disabled'
                            : ''
                    }
                    class="pm-button-secondary disabled:opacity-40"
                >
                    ${escapeHtml(
                        translate(
                            'leases.previous'
                        )
                    )}
                </button>

                <button
                    id="leases-next"
                    type="button"
                    ${
                        currentPage >= lastPage
                            ? 'disabled'
                            : ''
                    }
                    class="pm-button-secondary disabled:opacity-40"
                >
                    ${escapeHtml(
                        translate(
                            'leases.next'
                        )
                    )}
                </button>
            </div>
        </div>
    `;

    document
        .getElementById(
            'leases-previous'
        )
        ?.addEventListener(
            'click',
            () => {
                if (
                    currentPage > 1
                ) {
                    loadLeases(
                        currentPage - 1
                    );
                }
            }
        );

    document
        .getElementById(
            'leases-next'
        )
        ?.addEventListener(
            'click',
            () => {
                if (
                    currentPage
                    < lastPage
                ) {
                    loadLeases(
                        currentPage + 1
                    );
                }
            }
        );
}
/*
|--------------------------------------------------------------------------
| Lease Field Help Tooltips
|--------------------------------------------------------------------------
|
| Lease forms contain contractual and financial concepts that may not be
| immediately obvious to every user.
|
| Tooltips use a single fixed-position element attached to the document
| rather than placing tooltip content inside each form field.
|
| This provides three important benefits:
|
| - tooltips appear immediately;
| - they are not clipped by the Lease modal's scrolling container;
| - their position can be adjusted automatically to remain visible within
|   the browser viewport.
|
*/

let activeLeaseHelpTrigger =
    null;

/**
 * Initialize contextual-help triggers in the Lease form.
 */
function initializeLeaseFieldHelp() {
    const tooltip =
        document.getElementById(
            'lease-field-tooltip'
        );

    if (! tooltip) {
        return;
    }

    document
        .querySelectorAll(
            '[data-field-help]'
        )
        .forEach(
            (trigger) => {
                trigger.addEventListener(
                    'mouseenter',
                    () => {
                        showLeaseFieldTooltip(
                            trigger
                        );
                    }
                );

                trigger.addEventListener(
                    'mouseleave',
                    hideLeaseFieldTooltip
                );

                /*
                 * Keyboard users receive the same contextual help when the
                 * help button gains focus.
                 */
                trigger.addEventListener(
                    'focus',
                    () => {
                        showLeaseFieldTooltip(
                            trigger
                        );
                    }
                );

                trigger.addEventListener(
                    'blur',
                    hideLeaseFieldTooltip
                );

                /*
                 * Supporting click makes the same help usable on touch
                 * devices where hover does not exist.
                 */
                trigger.addEventListener(
                    'click',
                    (event) => {
                        event.preventDefault();

                        if (
                            activeLeaseHelpTrigger
                            === trigger
                        ) {
                            hideLeaseFieldTooltip();

                            return;
                        }

                        showLeaseFieldTooltip(
                            trigger
                        );
                    }
                );
            }
        );

    /*
     * Recalculate the position if the browser window changes while a
     * tooltip is visible.
     */
    window.addEventListener(
        'resize',
        repositionLeaseFieldTooltip
    );

    /*
     * The Lease modal itself scrolls independently from the page.
     * Reposition the tooltip as the user scrolls through the form.
     */
    document.addEventListener(
        'scroll',
        repositionLeaseFieldTooltip,
        true
    );

    /*
     * Clicking anywhere outside a help button closes a tooltip that may
     * have been opened on a touch device.
     */
    document.addEventListener(
        'click',
        (event) => {
            if (
                ! event.target.closest(
                    '[data-field-help]'
                )
            ) {
                hideLeaseFieldTooltip();
            }
        }
    );
}

/**
 * Display contextual help for one field.
 */
function showLeaseFieldTooltip(
    trigger
) {
    const tooltip =
        document.getElementById(
            'lease-field-tooltip'
        );

    if (! tooltip) {
        return;
    }

    const text =
        trigger.dataset
            .fieldHelpText
        || '';

    if (text === '') {
        return;
    }

    activeLeaseHelpTrigger =
        trigger;

    tooltip.textContent =
        text;

    tooltip.classList.remove(
        'hidden'
    );

    /*
     * Position only after the tooltip is visible because its actual width
     * and height are required for viewport collision detection.
     */
    positionLeaseFieldTooltip(
        trigger,
        tooltip
    );
}

/**
 * Hide the currently visible contextual help.
 */
function hideLeaseFieldTooltip() {
    const tooltip =
        document.getElementById(
            'lease-field-tooltip'
        );

    if (! tooltip) {
        return;
    }

    tooltip.classList.add(
        'hidden'
    );

    activeLeaseHelpTrigger =
        null;
}

/**
 * Reposition the active tooltip following modal scrolling or window
 * resizing.
 */
function repositionLeaseFieldTooltip() {
    if (! activeLeaseHelpTrigger) {
        return;
    }

    const tooltip =
        document.getElementById(
            'lease-field-tooltip'
        );

    if (
        ! tooltip
        || tooltip.classList.contains(
            'hidden'
        )
    ) {
        return;
    }

    positionLeaseFieldTooltip(
        activeLeaseHelpTrigger,
        tooltip
    );
}

/**
 * Position a tooltip next to its help trigger while keeping the complete
 * tooltip inside the visible browser viewport.
 */
function positionLeaseFieldTooltip(
    trigger,
    tooltip
) {
    const triggerRect =
        trigger.getBoundingClientRect();

    const tooltipRect =
        tooltip.getBoundingClientRect();

    /*
     * Keep a comfortable distance from both the trigger and browser edges.
     */
    const gap =
        10;

    const viewportPadding =
        12;

    /*
     * Start by centering the tooltip horizontally above the help button.
     */
    let left =
        triggerRect.left
        + (
            triggerRect.width / 2
        )
        - (
            tooltipRect.width / 2
        );

    /*
     * Prevent the tooltip from extending beyond the left edge.
     */
    left =
        Math.max(
            viewportPadding,
            left
        );

    /*
     * Prevent it from extending beyond the right edge.
     */
    left =
        Math.min(
            left,
            window.innerWidth
            - tooltipRect.width
            - viewportPadding
        );

    /*
     * Prefer displaying above the field because this leaves the form
     * control beneath it visible.
     */
    let top =
        triggerRect.top
        - tooltipRect.height
        - gap;

    /*
     * If there is not enough room above, display below the trigger instead.
     */
    if (
        top < viewportPadding
    ) {
        top =
            triggerRect.bottom
            + gap;
    }

    /*
     * Final protection for unusually small browser windows.
     */
    top =
        Math.min(
            top,
            window.innerHeight
            - tooltipRect.height
            - viewportPadding
        );

    top =
        Math.max(
            viewportPadding,
            top
        );

    tooltip.style.left =
        `${Math.round(left)}px`;

    tooltip.style.top =
        `${Math.round(top)}px`;
}
/*
|--------------------------------------------------------------------------
| Lease Modal
|--------------------------------------------------------------------------
*/

/**
 * Initialise DD-MM-YYYY Lease date inputs while retaining a native
 * calendar picker beside each field.
 */
function initializeLeaseDateInputs() {
    initializeDateInputs(
        '[data-lease-date-input]'
    );

    document
        .querySelectorAll(
            '[data-lease-date-picker]'
        )
        .forEach(
            (button) => {
                if (
                    button.dataset
                        .leaseDatePickerInitialized
                    === 'true'
                ) {
                    return;
                }

                button.dataset
                    .leaseDatePickerInitialized =
                    'true';

                const fieldId =
                    button.dataset
                        .leaseDatePicker;

                const textInput =
                    document.getElementById(
                        fieldId
                    );

                const nativeInput =
                    document.querySelector(
                        `[data-lease-native-date-picker="${fieldId}"]`
                    );

                if (
                    ! textInput
                    || ! nativeInput
                ) {
                    return;
                }

                /*
                 * Keep the hidden native picker synchronized when the
                 * operator types DD-MM-YYYY manually.
                 */
                const syncNativeFromText =
                    () => {
                        const iso =
                            dateForApi(
                                textInput.value
                            );

                        nativeInput.value =
                            /^\d{4}-\d{2}-\d{2}$/
                                .test(
                                    iso
                                )
                                ? iso
                                : '';
                    };

                textInput.addEventListener(
                    'change',
                    syncNativeFromText
                );

                textInput.addEventListener(
                    'blur',
                    syncNativeFromText
                );

                /*
                 * Calendar icon opens the browser's native date chooser.
                 */
                button.addEventListener(
                    'click',
                    () => {
                        openDatePicker(
                            textInput
                        );
                    }
                );

                /*
                 * Convert native YYYY-MM-DD selection back into the
                 * Patrimoine DD-MM-YYYY presentation convention.
                 */
                nativeInput.addEventListener(
                    'change',
                    () => {
                        textInput.value =
                            dateForDisplay(
                                nativeInput.value
                            );

                        textInput.dispatchEvent(
                            new Event(
                                'change',
                                {
                                    bubbles: true,
                                }
                            )
                        );
                    }
                );
            }
        );
}


function initializeLeaseForm() {
    const modal =
        document.getElementById(
            'lease-modal'
        );

    const form =
        document.getElementById(
            'lease-form'
        );

    const openButton =
        document.getElementById(
            'add-lease-button'
        );

    if (
        ! modal
        || ! form
        || ! openButton
    ) {
        return;
    }

    initializeUnitSearch();

    initializeLeaseFinancialControls();

    initializeLeaseDateInputs();

    openButton.addEventListener(
        'click',
        openCreateLeaseModal
    );

    document
        .getElementById(
            'lease-modal-close'
        )
        ?.addEventListener(
            'click',
            closeLeaseModal
        );

    document
        .getElementById(
            'lease-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeLeaseModal
        );

    document
        .getElementById(
            'lease-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeLeaseModal
        );

    form.addEventListener(
        'submit',
        submitLeaseForm
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
                closeLeaseModal();
            }
        }
    );
}

function openCreateLeaseModal() {
    resetLeaseForm();

    /*
     * V1.0.4 default for a newly created Lease:
     * Advance Already Received starts checked.
     *
     * This is deliberately create-mode-only and therefore does not
     * overwrite an existing Lease during editing.
     */
    const advanceReceivedCheckbox =
        document.getElementById(
            'lease-advance-received'
        );

    if (advanceReceivedCheckbox) {
        advanceReceivedCheckbox.checked =
            true;
    }

    updateAdvanceReceivedControls();

    configureLeaseModal();

    showLeaseModal();
}

function configureLeaseModal() {
    setText(
        'lease-modal-title',
        translate(
            'leases.add_lease'
        )
    );

    setText(
        'lease-modal-description',
        translate(
            'leases.add_description'
        )
    );

    setText(
        'lease-submit-button',
        translate(
            'actions.save'
        )
    );
}

function showLeaseModal() {
    openDrawer(
        'lease-modal'
    );
}

function closeLeaseModal() {
    closeDrawer(
        'lease-modal',
        {
            onClosed: () => {
                resetLeaseForm();

                configureLeaseModal();
            },
        }
    );
}

function resetLeaseForm() {
    document
        .getElementById(
            'lease-form'
        )
        ?.reset();

    /*
     * Explicit defaults mirror Lease backend defaults and our V1
     * business rules.
     */
    setFormValue(
        'lease-status',
        'draft'
    );

    setFormValue(
        'lease-frequency',
        'monthly'
    );

    setFormValue(
        'lease-vat-rate',
        defaultVatRate
    );

    setFormValue(
        'lease-security-deposit',
        '0'
    );
    setFormValue(
        'lease-advance-payment',
        '0'
    );


    const advanceReceivedCheckbox =
        document.getElementById(
            'lease-advance-received'
        );

    if (advanceReceivedCheckbox) {
        advanceReceivedCheckbox.checked =
            false;
    }

    setFormValue(
        'lease-advance-received-date',
        ''
    );

    setFormValue(
        'lease-advance-received-method',
        ''
    );

    setFormValue(
        'lease-advance-received-reference',
        ''
    );

    setFormValue(
        'lease-advance-received-collector',
        ''
    );

    updateAdvanceReceivedControls();


    setFormValue(
        'lease-rent-reserve',
        '0'
    );

    setFormValue(
        'lease-rent-increment-type',
        'none'
    );

    setFormValue(
        'lease-rent-increment-value',
        '0'
    );

    setFormValue(
        'lease-next-rent-increment-date',
        ''
    );
    setFormValue(
        'lease-management-fee-type',
        'none'
    );

    setFormValue(
        'lease-management-fee-value',
        '0'
    );

    setFormValue(
        'lease-agent-commission',
        '0'
    );

    clearSelectedUnit();

    updateConsumableAdvance();

    updateRentIncrementControls();

    updateManagementFeeControls();

    hideLeaseFormError();
}

function populateLeaseForm(
    lease
) {
    /*
    * Use the searchable Unit picker so Edit mode displays both the selected
    * Unit and its inherited ownership information.
    */
    selectLeaseUnit(
        lease.unit_id
    );

    setFormValue(
        'lease-tenant',
        lease.tenant_id
    );

    setFormValue(
        'lease-agent',
        lease.agent_id
    );

    setFormValue(
        'lease-start-date',
        dateInputValue(
            lease.start_date
        )
    );

    setFormValue(
        'lease-end-date',
        dateInputValue(
            lease.end_date
        )
    );

    setFormValue(
        'lease-status',
        lease.status
    );

    setFormValue(
        'lease-notice-date',
        dateInputValue(
            lease.termination_notice_date
        )
    );

    setFormValue(
        'lease-rent-amount',
        lease.rent_amount
    );

    setFormValue(
        'lease-frequency',
        lease.payment_frequency
    );

    setFormValue(
        'lease-due-day',
        lease.due_day
    );

    setFormValue(
        'lease-vat-rate',
        lease.vat_rate
    );

    setFormValue(
        'lease-proration',
        lease.proration_amount
    );

    setFormValue(
        'lease-security-deposit',
        lease.security_deposit_amount
    );

    setFormValue(
    'lease-advance-payment',
    lease.advance_payment_amount
    );

    setFormValue(
        'lease-rent-reserve',
        lease.rent_reserve_amount
    );

    setFormValue(
        'lease-rent-increment-type',
        lease.rent_increment_type
    );

    setFormValue(
        'lease-rent-increment-value',
        lease.rent_increment_value
    );

    setFormValue(
        'lease-next-rent-increment-date',
        dateInputValue(
            lease.next_rent_increment_date
        )
    );

    setFormValue(
        'lease-management-fee-type',
        lease.management_fee_type
    );

    setFormValue(
        'lease-management-fee-value',
        lease.management_fee_value
    );

    setFormValue(
        'lease-agent-commission',
        lease.agent_commission_amount
    );

    setFormValue(
        'lease-notes',
        lease.notes
    );

    updateConsumableAdvance();

    updateRentIncrementControls();

    updateManagementFeeControls();


}

function dateInputValue(value) {
    if (! value) {
        return '';
    }

    return dateForDisplay(
        String(value)
            .slice(
                0,
                10
            )
    );
}

function setFormValue(
    id,
    value
) {
    const element =
        document.getElementById(
            id
        );

    if (element) {
        element.value =
            value ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| Lease Financial / Contractual Controls
|--------------------------------------------------------------------------
*/

/**
 * Configure calculated and conditional Lease fields.
 */
function initializeLeaseFinancialControls() {
    document
        .getElementById(
            'lease-advance-payment'
        )
        ?.addEventListener(
            'input',
            () => {
                updateConsumableAdvance();
                updateAdvanceReceivedControls();
            }
        );

    document
        .getElementById(
            'lease-rent-reserve'
        )
        ?.addEventListener(
            'input',
            updateConsumableAdvance
        );

    document
        .getElementById(
            'lease-rent-increment-type'
        )
        ?.addEventListener(
            'change',
            updateRentIncrementControls
        );

    document
        .getElementById(
            'lease-management-fee-type'
        )
        ?.addEventListener(
            'change',
            updateManagementFeeControls
        );

            document
        .getElementById(
            'lease-advance-received'
        )
        ?.addEventListener(
            'change',
            updateAdvanceReceivedControls
        );

    document
        .getElementById(
            'lease-advance-received-method'
        )
        ?.addEventListener(
            'change',
            updateAdvanceReceivedControls
        );

    updateAdvanceReceivedControls();

    updateConsumableAdvance();

    updateRentIncrementControls();

    updateManagementFeeControls();
}

/**
 * Calculate the contractual Consumable Advance.
 *
 * No financial ledger transaction is created here.
 */
function updateConsumableAdvance() {
    const advance =
        Number(
            formValue(
                'lease-advance-payment'
            )
        );

    const reserve =
        Number(
            formValue(
                'lease-rent-reserve'
            )
        );

    const consumable =
        Math.max(
            0,
            (
                Number.isFinite(advance)
                    ? advance
                    : 0
            )
            -
            (
                Number.isFinite(reserve)
                    ? reserve
                    : 0
            )
        );

    setText(
        'lease-consumable-advance',
        formatCurrency(
            consumable
        )
    );
}


/**
 * Show or hide historical Advance Payment receipt fields.
 *
 * These fields are relevant only when the operator confirms that the
 * contractual Advance Payment had already been received before the Lease
 * was entered into Patrimoine.
 */
function updateAdvanceReceivedControls() {
    const checkbox =
        document.getElementById(
            'lease-advance-received'
        );

    const details =
        document.getElementById(
            'lease-advance-received-details'
        );

    const method =
        document.getElementById(
            'lease-advance-received-method'
        );

    const collectorWrapper =
        document.getElementById(
            'lease-advance-received-collector-wrapper'
        );

    const advance =
        Number(
            formValue(
                'lease-advance-payment'
            )
        );

    if (
        ! checkbox
        || ! details
    ) {
        return;
    }

    /*
     * A zero contractual Advance Payment cannot logically have been
     * historically received.
     */
    const hasAdvance =
        Number.isFinite(advance)
        && advance > 0;

    /*
     * V1.0.4:
     * Advance Already Received is a create-form default and should not
     * be cleared merely because the operator has not entered the
     * contractual advance amount yet.
     *
     * Receipt-detail fields still become active only after a positive
     * Advance Payment exists.
     */
    checkbox.disabled =
        false;

    const enabled =
        checkbox.checked
        && hasAdvance;

    details.classList.toggle(
        'hidden',
        ! enabled
    );

    const dateInput =
        document.getElementById(
            'lease-advance-received-date'
        );

    const methodInput =
        document.getElementById(
            'lease-advance-received-method'
        );

    if (dateInput) {
        dateInput.required =
            enabled;
    }

    if (methodInput) {
        methodInput.required =
            enabled;
    }

    const isCash =
        enabled
        && method?.value === 'cash';

    collectorWrapper
        ?.classList.toggle(
            'hidden',
            ! isCash
        );

    const collector =
        document.getElementById(
            'lease-advance-received-collector'
        );

    if (collector) {
        collector.required =
            isCash;

        if (! isCash) {
            collector.value =
                '';
        }
    }
}

/**
 * Update Rent Increment fields according to their selected type.
 */
function updateRentIncrementControls() {
    const type =
        formValue(
            'lease-rent-increment-type'
        );

    const valueInput =
        document.getElementById(
            'lease-rent-increment-value'
        );

    const dateInput =
        document.getElementById(
            'lease-next-rent-increment-date'
        );

    const unit =
        document.getElementById(
            'lease-rent-increment-unit'
        );

    const disabled =
        type === 'none';

    if (valueInput) {
        valueInput.disabled =
            disabled;

        valueInput.step =
            type === 'fixed'
                ? '1'
                : '0.01';

        if (disabled) {
            valueInput.value =
                '0';
        }
    }

    if (dateInput) {
        dateInput.disabled =
            disabled;

        if (disabled) {
            dateInput.value =
                '';
        }
    }

    if (unit) {
        unit.textContent =
            type === 'percentage'
                ? '%'
                : (
                    type === 'fixed'
                        ? (
                            getPresentationConfiguration()
                                .currency
                            || 'GHS'
                        )
                        : '—'
                );
    }
}

/**
 * Make the Managing Organisation Fee value visually unambiguous.
 */
function updateManagementFeeControls() {
    const type =
        formValue(
            'lease-management-fee-type'
        );

    const valueInput =
        document.getElementById(
            'lease-management-fee-value'
        );

    const unit =
        document.getElementById(
            'lease-management-fee-unit'
        );

    if (valueInput) {
        valueInput.disabled =
            type === 'none';

        valueInput.step =
            type === 'fixed'
                ? '1'
                : '0.01';

        if (type === 'none') {
            valueInput.value =
                '0';
        }
    }

    if (unit) {
        unit.textContent =
            type === 'percentage'
                ? '%'
                : (
                    type === 'fixed'
                        ? (
                            getPresentationConfiguration()
                                .currency
                            || 'GHS'
                        )
                        : '—'
                );
    }
}


/*
|--------------------------------------------------------------------------
| Controlled Lease Termination Settlement
|--------------------------------------------------------------------------
|
| This drawer is read-oriented.
|
| The LeaseTerminationSettlementService remains authoritative for every
| amount and blocker. The Lease page deliberately does not reproduce
| Tenant Deposit / Withdrawal / Adjustment workflows.
|
*/

let terminationSettlementLeaseId =
    null;


function initializeTerminationSettlementDrawer() {
    document
        .getElementById(
            'termination-settlement-modal-close'
        )
        ?.addEventListener(
            'click',
            closeTerminationSettlementModal
        );

    document
        .getElementById(
            'termination-settlement-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeTerminationSettlementModal
        );

    document
        .getElementById(
            'termination-settlement-tenant-link'
        )
        ?.addEventListener(
            'click',
            openTerminationSettlementTenant
        );

    document
        .getElementById(
            'termination-settlement-notice'
        )
        ?.addEventListener(
            'click',
            openTerminationSettlementNotice
        );

    document
        .getElementById(
            'termination-settlement-cancel'
        )
        ?.addEventListener(
            'click',
            cancelLeaseTerminationFromSettlement
        );

    document
        .getElementById(
            'termination-settlement-complete'
        )
        ?.addEventListener(
            'click',
            completeLeaseTerminationFromSettlement
        );

    document.addEventListener(
        'keydown',
        (event) => {
            const drawer =
                document.getElementById(
                    'termination-settlement-modal'
                );

            if (
                event.key === 'Escape'
                && drawer?.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closeTerminationSettlementModal();
            }
        }
    );
}


async function openTerminationSettlementModal(
    leaseId
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
    ) {
        return;
    }

    const drawer =
        document.getElementById(
            'termination-settlement-modal'
        );

    if (! drawer) {
        return;
    }

    terminationSettlementLeaseId =
        numericLeaseId;

    resetTerminationSettlementDrawer();

    openDrawer(
        drawer
    );

    await loadTerminationSettlement();
}


function closeTerminationSettlementModal() {
    closeDrawer(
        'termination-settlement-modal',
        {
            onClosed: () => {
                terminationSettlementLeaseId =
                    null;
            },
        }
    );
}


function resetTerminationSettlementDrawer() {
    document
        .getElementById(
            'termination-settlement-error'
        )
        ?.classList
        .add(
            'hidden'
        );

    document
        .getElementById(
            'termination-settlement-loading'
        )
        ?.classList
        .remove(
            'hidden'
        );

    document
        .getElementById(
            'termination-settlement-content'
        )
        ?.classList
        .add(
            'hidden'
        );

    const complete =
        document.getElementById(
            'termination-settlement-complete'
        );

    if (complete) {
        complete.disabled =
            true;
    }
}


async function loadTerminationSettlement() {
    if (
        ! Number.isInteger(
            terminationSettlementLeaseId
        )
    ) {
        return;
    }

    const errorBox =
        document.getElementById(
            'termination-settlement-error'
        );

    try {
        const response =
            await apiRequest(
                `/api/leases/${terminationSettlementLeaseId}/termination-settlement`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderTerminationSettlement(
            payload
        );

        document
            .getElementById(
                'termination-settlement-loading'
            )
            ?.classList
            .add(
                'hidden'
            );

        document
            .getElementById(
                'termination-settlement-content'
            )
            ?.classList
            .remove(
                'hidden'
            );
    } catch (error) {
        document
            .getElementById(
                'termination-settlement-loading'
            )
            ?.classList
            .add(
                'hidden'
            );

        if (errorBox) {
            errorBox.textContent =
                error instanceof Error
                    ? error.message
                    : translate(
                        'leases.termination_settlement_load_failed'
                    );

            errorBox.classList.remove(
                'hidden'
            );
        }
    }
}


function renderTerminationSettlement(
    payload
) {
    const lease =
        payload?.lease
        ?? {};

    const debt =
        payload?.debt
        ?? {};

    const funds =
        payload?.funds
        ?? {};

    const security =
        payload?.security_deposit
        ?? {};

    const settlement =
        payload?.settlement
        ?? {};

    setText(
        'termination-settlement-lease',
        `#${lease.id ?? terminationSettlementLeaseId}`
    );

    setText(
        'termination-settlement-tenant',
        lease.tenant
        ?? '—'
    );

    setText(
        'termination-settlement-building',
        lease.building
        ?? '—'
    );

    setText(
        'termination-settlement-unit',
        lease.unit
        ?? '—'
    );

    setText(
        'termination-settlement-notice-date',
        lease.termination_notice_date
            ? dateForDisplay(
                lease.termination_notice_date
            )
            : '—'
    );

    setText(
        'termination-settlement-date',
        lease.termination_date
            ? dateForDisplay(
                lease.termination_date
            )
            : '—'
    );

    setText(
        'termination-settlement-debt',
        formatCurrency(
            Number(
                debt.total_outstanding
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-rent-reserve',
        formatCurrency(
            Number(
                funds.rent_reserve_remaining
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-consumable-advance',
        formatCurrency(
            Number(
                funds.consumable_advance_remaining
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-security',
        formatCurrency(
            Number(
                funds.security_deposit_held
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-deductions',
        formatCurrency(
            Number(
                security.deduction_total
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-other-funds',
        formatCurrency(
            Number(
                funds.other_tenant_funds_balance
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-owed',
        formatCurrency(
            Number(
                settlement.amount_still_owed_by_tenant
                ?? 0
            )
        )
    );

    setText(
        'termination-settlement-refund',
        formatCurrency(
            Number(
                settlement.potential_refundable_amount
                ?? 0
            )
        )
    );

    renderTerminationSettlementBlockers(
        settlement.blockers
    );

    const tenantButton =
        document.getElementById(
            'termination-settlement-tenant-link'
        );

    if (tenantButton) {
        tenantButton.dataset.tenantId =
            lease.tenant_id
                ? String(
                    lease.tenant_id
                )
                : '';

        tenantButton.disabled =
            ! lease.tenant_id;
    }

    const complete =
        document.getElementById(
            'termination-settlement-complete'
        );

    if (complete) {
        complete.disabled =
            settlement.can_complete
            !== true;
    }

    const notice =
        document.getElementById(
            'termination-settlement-notice'
        );

    if (notice) {
        notice.disabled =
            ! terminationSettlementLeaseId;
    }
}


function renderTerminationSettlementBlockers(
    blockers
) {
    const container =
        document.getElementById(
            'termination-settlement-blockers'
        );

    if (! container) {
        return;
    }

    const items =
        Array.isArray(
            blockers
        )
            ? blockers
            : [];

    if (items.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-xl
                    border border-[var(--pm-success-border)]
                    bg-[var(--pm-success-background)] px-4 py-3
                    text-sm text-[var(--pm-success-text)]
                "
            >
                ${escapeHtml(
                    translate(
                        'leases.termination_no_blockers'
                    )
                )}
            </div>
        `;

        return;
    }

    container.innerHTML = `
        <div
            class="
                rounded-xl
                border border-[var(--pm-warning-border)]
                bg-[var(--pm-warning-background)] px-4 py-4
            "
        >
            <div
                class="
                    text-sm font-semibold
                    text-[var(--pm-warning-text)]
                "
            >
                ${escapeHtml(
                    translate(
                        'leases.termination_unresolved_items'
                    )
                )}
            </div>

            <ul
                class="
                    mt-2 list-disc space-y-1
                    pl-5 text-sm text-[var(--pm-warning-text)]
                "
            >
                ${
                    items
                        .map(
                            (blocker) => `
                                <li>
                                    ${escapeHtml(
                                        blocker?.message
                                        ?? translate(
                                            'leases.termination_unresolved_item'
                                        )
                                    )}
                                    ${
                                        Number(
                                            blocker?.amount
                                            ?? 0
                                        ) > 0
                                            ? ` — ${escapeHtml(
                                                formatCurrency(
                                                    Number(
                                                        blocker.amount
                                                    )
                                                )
                                            )}`
                                            : ''
                                    }
                                </li>
                            `
                        )
                        .join('')
                }
            </ul>
        </div>
    `;
}


function openTerminationSettlementTenant() {
    const button =
        document.getElementById(
            'termination-settlement-tenant-link'
        );

    const tenantId =
        button?.dataset
            ?.tenantId;

    if (! tenantId) {
        return;
    }

    window.location.href =
        `/tenants?tenant_id=${encodeURIComponent(
            tenantId
        )}`;
}


async function openTerminationSettlementNotice() {
    if (
        ! Number.isInteger(
            terminationSettlementLeaseId
        )
    ) {
        return;
    }

    try {
        const response =
            await apiRequest(
                `/api/leases/${terminationSettlementLeaseId}/termination-notice/pdf`
            );

        if (! response.ok) {
            throw new Error(
                translate(
                    'leases.termination_notice_unable_open'
                )
            );
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        window.open(
            url,
            '_blank',
            'noopener,noreferrer'
        );

        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
        );
    } catch (error) {
        window.alert(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.termination_notice_unable_open'
                )
        );
    }
}


async function cancelLeaseTerminationFromSettlement() {
    if (
        ! Number.isInteger(
            terminationSettlementLeaseId
        )
    ) {
        return;
    }

    if (
        ! window.confirm(
            translate(
                'leases.confirm_cancel_termination'
            )
        )
    ) {
        return;
    }

    const button =
        document.getElementById(
            'termination-settlement-cancel'
        );

    try {
        if (button) {
            button.disabled =
                true;
        }

        const response =
            await apiRequest(
                `/api/leases/${terminationSettlementLeaseId}/termination/cancel`,
                {
                    method:
                        'POST',
                }
            );

        await parseJsonResponse(
            response
        );

        closeTerminationSettlementModal();

        await loadLeases();
    } catch (error) {
        window.alert(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.termination_cancel_failed'
                )
        );
    } finally {
        if (button) {
            button.disabled =
                false;
        }
    }
}


async function completeLeaseTerminationFromSettlement() {
    if (
        ! Number.isInteger(
            terminationSettlementLeaseId
        )
    ) {
        return;
    }

    const button =
        document.getElementById(
            'termination-settlement-complete'
        );

    if (
        button?.disabled
        || ! window.confirm(
            translate(
                'leases.confirm_complete_termination'
            )
        )
    ) {
        return;
    }

    try {
        button.disabled =
            true;

        const response =
            await apiRequest(
                `/api/leases/${terminationSettlementLeaseId}/termination/complete`,
                {
                    method:
                        'POST',
                }
            );

        await parseJsonResponse(
            response
        );

        closeTerminationSettlementModal();

        await loadLeases();
    } catch (error) {
        window.alert(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.termination_complete_failed'
                )
        );

        await loadTerminationSettlement();
    }
}


/*
|--------------------------------------------------------------------------
| Controlled Lease Termination
|--------------------------------------------------------------------------
*/

function initializeLeaseTerminationDrawer() {
    const form =
        document.getElementById(
            'lease-termination-form'
        );

    if (! form) {
        return;
    }

    form.addEventListener(
        'submit',
        submitLeaseTerminationForm
    );

    document
        .getElementById(
            'lease-termination-cancel'
        )
        ?.addEventListener(
            'click',
            closeLeaseTerminationModal
        );

    document
        .getElementById(
            'lease-termination-modal-close'
        )
        ?.addEventListener(
            'click',
            closeLeaseTerminationModal
        );

    document
        .getElementById(
            'lease-termination-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeLeaseTerminationModal
        );

    document
        .getElementById(
            'lease-termination-open-notice'
        )
        ?.addEventListener(
            'click',
            openLeaseTerminationNotice
        );

    document.addEventListener(
        'keydown',
        (event) => {
            const drawer =
                document.getElementById(
                    'lease-termination-modal'
                );

            if (
                event.key === 'Escape'
                && drawer?.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closeLeaseTerminationModal();
            }
        }
    );
}

function openLeaseTerminationModal(
    leaseId
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
    ) {
        return;
    }

    const lease =
        loadedLeasesById.get(
            String(
                numericLeaseId
            )
        );

    if (! lease) {
        return;
    }

    const drawer =
        document.getElementById(
            'lease-termination-modal'
        );

    if (! drawer) {
        return;
    }

    terminatingLeaseId =
        numericLeaseId;

    hideLeaseTerminationError();

    setText(
        'lease-termination-context-reference',
        lease.reference
            ?? `#${lease.id}`
    );

    setText(
        'lease-termination-context-tenant',
        lease.tenant?.name
            ?? '—'
    );

    setText(
        'lease-termination-context-building',
        lease.unit?.building?.name
            ?? '—'
    );

    setText(
        'lease-termination-context-unit',
        lease.unit?.name
            ?? '—'
    );

    setText(
        'lease-termination-context-status',
        statusLabel(
            lease.status
        )
    );

    setFormValue(
        'lease-termination-notice-date',
        ''
    );

    setFormValue(
        'lease-termination-date',
        ''
    );

    const prorate =
        document.querySelector(
            'input[name="lease-termination-final-rent-mode"][value="prorate"]'
        );

    if (prorate) {
        prorate.checked =
            true;
    }

    document
        .getElementById(
            'lease-termination-notice-actions'
        )
        ?.classList
        .add(
            'hidden'
        );

    openDrawer(
        drawer
    );
}

function closeLeaseTerminationModal() {
    closeDrawer(
        'lease-termination-modal',
        {
            onClosed: () => {
                terminatingLeaseId =
                    null;

                document
                    .getElementById(
                        'lease-termination-form'
                    )
                    ?.reset();

                hideLeaseTerminationError();
            },
        }
    );
}

function hideLeaseTerminationError() {
    const element =
        document.getElementById(
            'lease-termination-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        '';

    element.classList.add(
        'hidden'
    );
}

function showLeaseTerminationError(
    message
) {
    const element =
        document.getElementById(
            'lease-termination-error'
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

async function submitLeaseTerminationForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'lease-termination-form'
        );

    const submitButton =
        document.getElementById(
            'lease-termination-submit'
        );

    if (
        ! form
        || ! submitButton
        || ! Number.isInteger(
            terminatingLeaseId
        )
    ) {
        return;
    }

    hideLeaseTerminationError();

    if (! form.reportValidity()) {
        return;
    }

    const noticeDate =
        dateForApi(
            formValue(
                'lease-termination-notice-date'
            )
        );

    const terminationDate =
        dateForApi(
            formValue(
                'lease-termination-date'
            )
        );

    const mode =
        document.querySelector(
            'input[name="lease-termination-final-rent-mode"]:checked'
        )?.value;

    if (
        ! noticeDate
        || ! terminationDate
        || ! mode
    ) {
        showLeaseTerminationError(
            translate(
                'leases.termination_required_fields'
            )
        );

        return;
    }

    try {
        submitButton.disabled =
            true;

        const response =
            await apiRequest(
                `/api/leases/${terminatingLeaseId}/termination`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            notice_date:
                                noticeDate,

                            termination_date:
                                terminationDate,

                            final_rent_mode:
                                mode,
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        document
            .getElementById(
                'lease-termination-notice-actions'
            )
            ?.classList
            .remove(
                'hidden'
            );

        await loadLeases(
            1
        );
    } catch (error) {
        showLeaseTerminationError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.termination_failed'
                )
        );
    } finally {
        submitButton.disabled =
            false;
    }
}

async function openLeaseTerminationNotice() {
    if (
        ! Number.isInteger(
            terminatingLeaseId
        )
    ) {
        return;
    }

    try {
        const response =
            await apiRequest(
                `/api/leases/${terminatingLeaseId}/termination-notice/pdf`
            );

        if (! response.ok) {
            throw new Error(
                translate(
                    'leases.termination_notice_unable_open'
                )
            );
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        window.open(
            url,
            '_blank',
            'noopener,noreferrer'
        );

        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
        );
    } catch (error) {
        showLeaseTerminationError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.termination_notice_unable_open'
                )
        );
    }
}

/*
|--------------------------------------------------------------------------
| Controlled Lease Extend
|--------------------------------------------------------------------------
*/

function initializeLeaseExtendDrawer() {
    const form =
        document.getElementById(
            'lease-extend-form'
        );

    if (! form) {
        return;
    }

    form.addEventListener(
        'submit',
        submitLeaseExtendForm
    );

    document
        .getElementById(
            'lease-extend-cancel'
        )
        ?.addEventListener(
            'click',
            closeLeaseExtendModal
        );

    document
        .getElementById(
            'lease-extend-modal-close'
        )
        ?.addEventListener(
            'click',
            closeLeaseExtendModal
        );

    document
        .getElementById(
            'lease-extend-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeLeaseExtendModal
        );

    document
        .getElementById(
            'lease-extend-increment-type'
        )
        ?.addEventListener(
            'change',
            updateLeaseExtendIncrementControls
        );

    document.addEventListener(
        'keydown',
        (event) => {
            const drawer =
                document.getElementById(
                    'lease-extend-modal'
                );

            if (
                event.key === 'Escape'
                && drawer?.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closeLeaseExtendModal();
            }
        }
    );
}

function openExtendLeaseModal(
    leaseId
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
    ) {
        return;
    }

    const lease =
        loadedLeasesById.get(
            String(
                numericLeaseId
            )
        );

    if (! lease) {
        return;
    }

    const drawer =
        document.getElementById(
            'lease-extend-modal'
        );

    if (! drawer) {
        return;
    }

    extendingLeaseId =
        numericLeaseId;

    hideLeaseExtendError();

    setText(
        'lease-extend-current-rent',
        formatCurrency(
            lease.rent_amount ?? 0
        )
    );

    setText(
        'lease-extend-current-frequency',
        frequencyLabel(
            lease.payment_frequency
        )
    );

    setText(
        'lease-extend-current-end-date',
        lease.end_date
            ? dateForDisplay(
                String(
                    lease.end_date
                ).slice(
                    0,
                    10
                )
            )
            : '—'
    );

    setText(
        'lease-extend-current-due-day',
        lease.due_day
            ?? lease.start_date?.slice(
                8,
                10
            )
            ?? '—'
    );

    /*
     * Effective From is intentionally not guessed.
     *
     * The user chooses the contractual boundary explicitly.
     */
    setFormValue(
        'lease-extend-effective-from',
        ''
    );

    setFormValue(
        'lease-extend-end-date',
        dateInputValue(
            lease.end_date
        )
    );

    setFormValue(
        'lease-extend-rent',
        lease.rent_amount
    );

    setFormValue(
        'lease-extend-frequency',
        lease.payment_frequency
    );

    setFormValue(
        'lease-extend-due-day',
        lease.due_day
    );

    setFormValue(
        'lease-extend-vat-rate',
        lease.vat_rate
    );

    setFormValue(
        'lease-extend-increment-type',
        lease.rent_increment_type
            ?? 'none'
    );

    setFormValue(
        'lease-extend-increment-value',
        lease.rent_increment_value
            ?? 0
    );

    setFormValue(
        'lease-extend-next-increment-date',
        dateInputValue(
            lease.next_rent_increment_date
        )
    );

    setFormValue(
        'lease-extend-notes',
        lease.notes
    );

    updateLeaseExtendIncrementControls();

    openDrawer(
        drawer
    );
}

function closeLeaseExtendModal() {
    closeDrawer(
        'lease-extend-modal',
        {
            onClosed: () => {
                extendingLeaseId =
                    null;

                document
                    .getElementById(
                        'lease-extend-form'
                    )
                    ?.reset();

                hideLeaseExtendError();
            },
        }
    );
}

function hideLeaseExtendError() {
    const element =
        document.getElementById(
            'lease-extend-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        '';

    element.classList.add(
        'hidden'
    );
}

function showLeaseExtendError(
    message
) {
    const element =
        document.getElementById(
            'lease-extend-error'
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

function updateLeaseExtendIncrementControls() {
    const type =
        formValue(
            'lease-extend-increment-type'
        );

    const value =
        document.getElementById(
            'lease-extend-increment-value'
        );

    const date =
        document.getElementById(
            'lease-extend-next-increment-date'
        );

    const disabled =
        type === 'none';

    if (value) {
        value.disabled =
            disabled;

        if (disabled) {
            value.value =
                '0';
        }
    }

    if (date) {
        date.disabled =
            disabled;

        if (disabled) {
            date.value =
                '';
        }
    }
}

function buildLeaseExtendPayload() {
    return {
        effective_from:
            dateForApi(
                formValue(
                    'lease-extend-effective-from'
                )
            ),

        end_date:
            nullableDateValue(
                'lease-extend-end-date'
            ),

        rent_amount:
            Number(
                formValue(
                    'lease-extend-rent'
                )
            ),

        payment_frequency:
            formValue(
                'lease-extend-frequency'
            ),

        due_day:
            nullableInteger(
                'lease-extend-due-day'
            ),

        vat_rate:
            Number(
                formValue(
                    'lease-extend-vat-rate'
                )
            ),

        rent_increment_type:
            formValue(
                'lease-extend-increment-type'
            ),

        rent_increment_value:
            Number(
                formValue(
                    'lease-extend-increment-value'
                )
            ),

        next_rent_increment_date:
            nullableDateValue(
                'lease-extend-next-increment-date'
            ),

        notes:
            nullableFormValue(
                'lease-extend-notes'
            ),
    };
}

async function submitLeaseExtendForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'lease-extend-form'
        );

    const submitButton =
        document.getElementById(
            'lease-extend-submit'
        );

    if (
        ! form
        || ! submitButton
        || ! Number.isInteger(
            extendingLeaseId
        )
    ) {
        return;
    }

    hideLeaseExtendError();

    if (! form.reportValidity()) {
        return;
    }

    const payload =
        buildLeaseExtendPayload();

    try {
        submitButton.disabled =
            true;

        const response =
            await apiRequest(
                `/api/leases/${extendingLeaseId}/extend`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closeLeaseExtendModal();

        await loadLeases(
            1
        );
    } catch (error) {
        showLeaseExtendError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.unable_update'
                )
        );
    } finally {
        submitButton.disabled =
            false;
    }
}

/*
|--------------------------------------------------------------------------
| Submission
|--------------------------------------------------------------------------
*/

async function submitLeaseForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'lease-form'
        );

    const submitButton =
        document.getElementById(
            'lease-submit-button'
        );

    if (
        ! form
        || ! submitButton
    ) {
        return;
    }

    hideLeaseFormError();

    if (! form.reportValidity()) {
        return;
    }

    const payload =
        buildLeasePayload();


        if (
            ! Number.isInteger(
                payload.unit_id
            )
            || payload.unit_id <= 0
        ) {
            showLeaseFormError(
                translate(
                    'leases.select_valid_unit'
                )
            );

            return;
        }

        if (
            payload.rent_reserve_amount
            > payload.advance_payment_amount
        ) {
            showLeaseFormError(
                translate(
                    'leases.reserve_exceeds_advance'
                )
            );

            return;
        }

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            translate(
                'leases.creating'
            );

        const response =
            await apiRequest(
                '/api/leases',
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        await parseJsonResponse(
            response
        );

        closeLeaseModal();

        await loadLeases(
            1
        );
    } catch (error) {
        showLeaseFormError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.unable_create'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            translate(
                'actions.save'
            );
    }
}

function buildLeasePayload() {
    return {
        unit_id:
            Number(
                formValue(
                    'lease-unit'
                )
            ),

        tenant_id:
            Number(
                formValue(
                    'lease-tenant'
                )
            ),

        agent_id:
            nullableInteger(
                'lease-agent'
            ),

        start_date:
            dateForApi(
                formValue(
                    'lease-start-date'
                )
            ),

        end_date:
            nullableDateForApi(
                'lease-end-date'
            ),

        status:
            formValue(
                'lease-status'
            ),

        termination_notice_date:
            nullableDateForApi(
                'lease-notice-date'
            ),

        rent_amount:
            Number(
                formValue(
                    'lease-rent-amount'
                )
            ),

        payment_frequency:
            formValue(
                'lease-frequency'
            ),

        due_day:
            nullableInteger(
                'lease-due-day'
            ),

        vat_rate:
            Number(
                formValue(
                    'lease-vat-rate'
                )
            ),

        /*
         * Null means automatic proration.
         *
         * Explicit zero must remain zero because V1 permits zero as a
         * deliberate override.
         */
        proration_amount:
            nullableInteger(
                'lease-proration'
            ),

        security_deposit_amount:
            Number(
                formValue(
                    'lease-security-deposit'
                )
            ),


        /*
        * Contractual tenant advance terms.
        *
        * These values do not create actual tenant-fund balances.
        */
        advance_payment_amount:
            Number(
                formValue(
                    'lease-advance-payment'
                )
            ),

        rent_reserve_amount:
            Number(
                formValue(
                    'lease-rent-reserve'
                )
            ),

        /*
        * Contractual next rent increment.
        */
        rent_increment_type:
            formValue(
                'lease-rent-increment-type'
            ),

        rent_increment_value:
            Number(
                formValue(
                    'lease-rent-increment-value'
                )
            ),

        next_rent_increment_date:
            nullableDateForApi(
                'lease-next-rent-increment-date'
            ),


        management_fee_type:
            formValue(
                'lease-management-fee-type'
            ),

        management_fee_value:
            Number(
                formValue(
                    'lease-management-fee-value'
                )
            ),

        agent_commission_amount:
            Number(
                formValue(
                    'lease-agent-commission'
                )
            ),

        notes:
            nullableFormValue(
                'lease-notes'
            ),


        advance_received:
        document
            .getElementById(
                'lease-advance-received'
            )
            ?.checked
            ?? false,

        advance_received_date:
            nullableDateForApi(
                'lease-advance-received-date'
            ),

        advance_received_method:
            nullableFormValue(
                'lease-advance-received-method'
            ),

        advance_received_reference:
            nullableFormValue(
                'lease-advance-received-reference'
            ),

        advance_received_collector:
            nullableFormValue(
                'lease-advance-received-collector'
            ),


    };
}

/**
 * Return an optional date in API YYYY-MM-DD format.
 *
 * Visible Lease date controls use DD-MM-YYYY. Empty optional dates must
 * remain null rather than becoming an invalid or empty API date.
 *
 * @param {string} id
 * @returns {string|null}
 */
function nullableDateForApi(id) {
    const value =
        nullableFormValue(
            id
        );

    if (value === null) {
        return null;
    }

    return dateForApi(
        value
    );
}

function nullableInteger(id) {
    const value =
        formValue(id);

    if (value === '') {
        return null;
    }

    return Number(
        value
    );
}

/*
|--------------------------------------------------------------------------
| Deletion
|--------------------------------------------------------------------------
*/

async function deleteLease(
    leaseId
) {
    const lease =
        loadedLeasesById.get(
            String(
                leaseId
            )
        );

    if (! lease) {
        showLeasePageError(
            translate(
                'leases.unable_delete'
            )
        );

        return;
    }

    await openLeaseDeleteDrawer(
        lease
    );
}

function initializeLeaseDeleteDrawer() {
    document
        .getElementById(
            'lease-delete-cancel'
        )
        ?.addEventListener(
            'click',
            closeLeaseDeleteDrawer
        );

    document
        .getElementById(
            'lease-delete-modal-close'
        )
        ?.addEventListener(
            'click',
            closeLeaseDeleteDrawer
        );

    document
        .getElementById(
            'lease-delete-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeLeaseDeleteDrawer
        );

    document
        .getElementById(
            'lease-delete-form'
        )
        ?.addEventListener(
            'submit',
            submitLeaseDeletion
        );

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const drawer =
                document.getElementById(
                    'lease-delete-modal'
                );

            if (
                drawer
                && drawer.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closeLeaseDeleteDrawer();
            }
        }
    );
}

async function openLeaseDeleteDrawer(
    lease
) {
    const drawer =
        document.getElementById(
            'lease-delete-modal'
        );

    if (! drawer) {
        return;
    }

    deletingLeaseId =
        Number(
            lease.id
        );

    hideLeaseDeleteError();

    const form =
        document.getElementById(
            'lease-delete-form'
        );

    form?.reset();

    setText(
        'lease-delete-context-reference',
        `#${lease.id}`
    );

    setText(
        'lease-delete-context-tenant',
        partyDisplayName(
            lease.tenant
        )
    );

    setText(
        'lease-delete-context-building',
        lease?.unit?.building?.name
            || '—'
    );

    setText(
        'lease-delete-context-unit',
        lease?.unit?.name
            || '—'
    );

    const loading =
        document.getElementById(
            'lease-delete-loading'
        );

    const content =
        document.getElementById(
            'lease-delete-impact'
        );

    loading?.classList.remove(
        'hidden'
    );

    content?.classList.add(
        'hidden'
    );

    const submit =
        document.getElementById(
            'lease-delete-submit'
        );

    if (submit) {
        submit.disabled =
            true;
    }

    openDrawer(
        drawer
    );

    drawer
        .querySelector(
            '.pm-drawer-panel'
        )
        ?.focus();

    try {
        const response =
            await apiRequest(
                `/api/leases/${deletingLeaseId}/deletion-impact`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        if (! response.ok) {
            throw new Error(
                payload?.message
                || translate(
                    'leases.delete_impact_failed'
                )
            );
        }

        renderLeaseDeleteImpact(
            payload
        );

        loading?.classList.add(
            'hidden'
        );

        content?.classList.remove(
            'hidden'
        );

        if (submit) {
            submit.disabled =
                payload?.eligibility
                    ?.safe_to_execute
                !== true;
        }
    } catch (error) {
        loading?.classList.add(
            'hidden'
        );

        showLeaseDeleteError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.delete_impact_failed'
                )
        );
    }
}

function closeLeaseDeleteDrawer() {
    closeDrawer(
        'lease-delete-modal',
        {
            onClosed: () => {
                deletingLeaseId =
                    null;
            },
        }
    );
}

function renderLeaseDeleteImpact(
    payload
) {
    /*
     * The deletion-impact endpoint returns the authoritative
     * LeaseDeletionRestorationPlanService contract directly.
     *
     * Do not reconstruct financial meaning in the browser.
     */
    const eligibility =
        payload?.eligibility
        ?? {};

    const monetary =
        payload?.operational
            ?.monetary_restoration
        ?? {};

    const operationalSteps =
        Array.isArray(
            payload?.operational
                ?.delete_in_order
        )
            ? payload.operational
                .delete_in_order
            : [];

    const accounting =
        payload?.accounting
        ?? {};

    const invoices =
        monetary?.invoices
        ?? {};

    const payments =
        monetary?.payments
        ?? {};

    const tenantFunds =
        monetary?.tenant_funds
        ?? {};

    const owner =
        monetary?.owner
        ?? {};

    const accountByType =
        (
            type
        ) => {
            const accounts =
                Array.isArray(
                    tenantFunds?.accounts
                )
                    ? tenantFunds.accounts
                    : [];

            return accounts.find(
                (account) =>
                    account?.type
                    === type
            )
            ?? null;
        };

    const deletionCount =
        (
            table
        ) => {
            const step =
                operationalSteps.find(
                    (candidate) =>
                        candidate?.table
                        === table
                );

            return Array.isArray(
                step?.ids
            )
                ? step.ids.length
                : 0;
        };

    const reserve =
        accountByType(
            'rent_reserve'
        );

    const advance =
        accountByType(
            'consumable_advance'
        );

    const security =
        accountByType(
            'security_deposit'
        );

    setText(
        'lease-delete-impact-invoices',
        Number(
            invoices?.count
            ?? 0
        )
    );

    setText(
        'lease-delete-impact-payments',
        Number(
            payments?.count
            ?? 0
        )
    );

    setText(
        'lease-delete-impact-allocations',
        deletionCount(
            'payment_allocations'
        )
    );

    setText(
        'lease-delete-impact-receipts',
        deletionCount(
            'withdrawal_receipts'
        )
    );

    setText(
        'lease-delete-impact-security',
        formatCurrency(
            Number(
                security?.balance
                ?? 0
            )
        )
    );

    setText(
        'lease-delete-impact-rent-reserve',
        formatCurrency(
            Number(
                reserve?.balance
                ?? 0
            )
        )
    );

    setText(
        'lease-delete-impact-consumable',
        formatCurrency(
            Number(
                advance?.balance
                ?? 0
            )
        )
    );

    /*
     * Do not manufacture one "total financial effect" by adding values
     * with different accounting meanings. Invoice outstanding is an
     * authoritative standalone position exposed by the monetary service.
     */
    setText(
        'lease-delete-impact-total',
        formatCurrency(
            Number(
                invoices?.outstanding
                ?? 0
            )
        )
    );

    setText(
        'lease-delete-impact-reversals',
        Array.isArray(
            accounting
                ?.reversal_candidates
        )
            ? accounting
                .reversal_candidates
                .length
            : 0
    );

    setText(
        'lease-delete-impact-owner',
        formatCurrency(
            Number(
                owner?.net_lease_effect
                ?? 0
            )
        )
    );

    const blockers =
        Array.isArray(
            eligibility
                ?.blocking_reasons
        )
            ? eligibility
                .blocking_reasons
            : [];

    const blockerBox =
        document.getElementById(
            'lease-delete-blockers'
        );

    if (! blockerBox) {
        return;
    }

    if (
        eligibility
            ?.safe_to_execute
        === true
        && blockers.length === 0
    ) {
        blockerBox.innerHTML = `
            <div
                class="
                    rounded-xl
                    border border-[var(--pm-success-border)]
                    bg-[var(--pm-success-background)] px-4 py-3
                    text-sm text-[var(--pm-success-text)]
                "
            >
                ${escapeHtml(
                    translate(
                        'leases.delete_impact_safe'
                    )
                )}
            </div>
        `;

        blockerBox.classList.remove(
            'hidden'
        );

        return;
    }

    blockerBox.innerHTML = `
        <div class="font-semibold">
            ${escapeHtml(
                translate(
                    'leases.delete_blocked'
                )
            )}
        </div>

        ${
            blockers.length > 0
                ? `
                    <ul
                        class="
                            mt-2 list-disc
                            space-y-1 pl-5
                        "
                    >
                        ${
                            blockers
                                .map(
                                    (blocker) => `
                                        <li>
                                            ${escapeHtml(
                                                String(
                                                    blocker
                                                    ?? ''
                                                )
                                            )}
                                        </li>
                                    `
                                )
                                .join('')
                        }
                    </ul>
                `
                : ''
        }
    `;

    blockerBox.classList.remove(
        'hidden'
    );
}

async function submitLeaseDeletion(
    event
) {
    event.preventDefault();

    if (! deletingLeaseId) {
        return;
    }

    hideLeaseDeleteError();

    const reason =
        formValue(
            'lease-delete-reason'
        ).trim();

    const confirmation =
        formValue(
            'lease-delete-confirmation'
        );

    const currentPassword =
        formValue(
            'lease-delete-password'
        );

    if (! reason) {
        showLeaseDeleteError(
            translate(
                'leases.delete_reason_required'
            )
        );

        return;
    }

    if (
        confirmation
        !== 'DELETE'
    ) {
        showLeaseDeleteError(
            translate(
                'leases.delete_confirmation_invalid'
            )
        );

        return;
    }

    if (! currentPassword) {
        showLeaseDeleteError(
            translate(
                'leases.delete_password_required'
            )
        );

        return;
    }

    if (
        ! window.confirm(
            translate(
                'leases.delete_final_confirmation'
            )
        )
    ) {
        return;
    }

    const submit =
        document.getElementById(
            'lease-delete-submit'
        );

    if (submit) {
        submit.disabled =
            true;
    }

    try {
        const response =
            await apiRequest(
                `/api/leases/${deletingLeaseId}`,
                {
                    method:
                        'DELETE',

                    body:
                        JSON.stringify({
                            reason,

                            confirmation,

                            current_password:
                                currentPassword,
                        }),
                }
            );

        if (
            response.status
            !== 204
        ) {
            await parseJsonResponse(
                response
            );
        }

        closeLeaseDeleteDrawer();

        await loadLeases(
            1
        );
    } catch (error) {
        showLeaseDeleteError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.unable_delete'
                )
        );

        if (submit) {
            submit.disabled =
                false;
        }
    }
}

function showLeaseDeleteError(
    message
) {
    const element =
        document.getElementById(
            'lease-delete-error'
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

function hideLeaseDeleteError() {
    const element =
        document.getElementById(
            'lease-delete-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        '';

    element.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showLeaseFormError(message) {
    const box =
        document.getElementById(
            'lease-form-error'
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

function hideLeaseFormError() {
    const box =
        document.getElementById(
            'lease-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}

function showLeasePageError(message) {
    const box =
        document.getElementById(
            'leases-error'
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

function hideLeasePageError() {
    const box =
        document.getElementById(
            'leases-error'
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
| Rent Increments
|--------------------------------------------------------------------------
|
| V1.0.7 discretionary rent-increment workflow.
|
| Increments are scheduled and cancelled here; applying a due increment
| remains exclusive to the daily patrimoine:apply-due-rent-increments
| scheduler command, so rent never changes ad hoc from the browser.
|
*/

let rentIncrementsLeaseId =
    null;

/**
 * Register Rent Increments drawer controls.
 */
function initializeRentIncrementsDrawer() {
    document
        .getElementById(
            'rent-increments-modal-close'
        )
        ?.addEventListener(
            'click',
            closeRentIncrementsModal
        );

    document
        .getElementById(
            'rent-increments-close-footer'
        )
        ?.addEventListener(
            'click',
            closeRentIncrementsModal
        );

    document
        .getElementById(
            'rent-increments-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeRentIncrementsModal
        );

    document
        .getElementById(
            'rent-increment-form'
        )
        ?.addEventListener(
            'submit',
            submitRentIncrementSchedule
        );

    /*
     * Keep the value suffix unambiguous: % for percentage increments,
     * the configured currency for fixed amounts.
     */
    document
        .getElementById(
            'rent-increment-type'
        )
        ?.addEventListener(
            'change',
            updateRentIncrementScheduleUnit
        );

    document.addEventListener(
        'keydown',
        (event) => {
            const drawer =
                document.getElementById(
                    'rent-increments-modal'
                );

            if (
                event.key === 'Escape'
                && drawer?.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closeRentIncrementsModal();
            }
        }
    );
}

/**
 * Open and load the Rent Increments drawer for one Lease.
 */
async function openRentIncrementsModal(
    leaseId
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
    ) {
        return;
    }

    const drawer =
        document.getElementById(
            'rent-increments-modal'
        );

    if (! drawer) {
        return;
    }

    rentIncrementsLeaseId =
        numericLeaseId;

    resetRentIncrementsDrawer();

    openDrawer(
        drawer
    );

    await loadRentIncrements();
}

function closeRentIncrementsModal() {
    closeDrawer(
        'rent-increments-modal',
        {
            onClosed: () => {
                rentIncrementsLeaseId =
                    null;
            },
        }
    );
}

/**
 * Return the drawer to its pristine loading state and apply the
 * manage_operations presentation gate to the scheduling controls.
 */
function resetRentIncrementsDrawer() {
    hideRentIncrementsError();

    document
        .getElementById(
            'rent-increments-loading'
        )
        ?.classList
        .remove(
            'hidden'
        );

    const list =
        document.getElementById(
            'rent-increments-list'
        );

    if (list) {
        list.innerHTML = '';

        list.classList.add(
            'hidden'
        );
    }

    document
        .getElementById(
            'rent-increment-form'
        )
        ?.reset();

    updateRentIncrementScheduleUnit();

    /*
     * The schedule form and its footer action are Manager controls.
     * permissions.js remains declarative via data-requires-capability;
     * this browserCan() gate is the operative presentation guard.
     */
    const canManage =
        browserCan(
            'manage_operations'
        );

    document
        .getElementById(
            'rent-increment-schedule'
        )
        ?.classList
        .toggle(
            'hidden',
            ! canManage
        );

    document
        .getElementById(
            'rent-increment-submit'
        )
        ?.classList
        .toggle(
            'hidden',
            ! canManage
        );
}

/**
 * Reflect the selected increment type in the value suffix.
 */
function updateRentIncrementScheduleUnit() {
    const unit =
        document.getElementById(
            'rent-increment-unit'
        );

    if (! unit) {
        return;
    }

    unit.textContent =
        formValue(
            'rent-increment-type'
        ) === 'fixed'
            ? (
                getPresentationConfiguration()
                    .currency
                || 'GHS'
            )
            : '%';
}

/**
 * Load every rent increment recorded for the open Lease.
 */
async function loadRentIncrements() {
    if (
        ! Number.isInteger(
            rentIncrementsLeaseId
        )
    ) {
        return;
    }

    try {
        const response =
            await apiRequest(
                `/api/leases/${rentIncrementsLeaseId}/rent-increments`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderRentIncrements(
            Array.isArray(
                payload?.rent_increments
            )
                ? payload.rent_increments
                : []
        );

        document
            .getElementById(
                'rent-increments-loading'
            )
            ?.classList
            .add(
                'hidden'
            );

        document
            .getElementById(
                'rent-increments-list'
            )
            ?.classList
            .remove(
                'hidden'
            );
    } catch (error) {
        document
            .getElementById(
                'rent-increments-loading'
            )
            ?.classList
            .add(
                'hidden'
            );

        showRentIncrementsError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.increments_unable_load'
                )
        );
    }
}

/**
 * Render the increment history with status pills and Manager cancel
 * actions on scheduled rows.
 */
function renderRentIncrements(
    increments
) {
    const container =
        document.getElementById(
            'rent-increments-list'
        );

    if (! container) {
        return;
    }

    if (increments.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-xl border
                    border-dashed border-[var(--pm-border)]
                    px-6 py-10 text-center
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                ${escapeHtml(
                    translate(
                        'leases.no_rent_increments'
                    )
                )}
            </div>
        `;

        return;
    }

    const canManage =
        browserCan(
            'manage_operations'
        );

    container.innerHTML =
        increments
            .map(
                (increment) =>
                    rentIncrementRow(
                        increment,
                        canManage
                    )
            )
            .join('');

    container
        .querySelectorAll(
            '[data-cancel-rent-increment]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        cancelRentIncrement(
                            button.dataset
                                .incrementId
                        );
                    }
                );
            }
        );
}

/**
 * Render one increment record.
 */
function rentIncrementRow(
    increment,
    canManage
) {
    const timeline =
        [
            increment.notification_sent_at
                ? `${translate(
                    'leases.notification_sent'
                )}: ${formatDate(
                    increment.notification_sent_at
                )}`
                : null,

            increment.applied_at
                ? `${translate(
                    'leases.applied_on'
                )}: ${formatDate(
                    increment.applied_at
                )}`
                : null,

            increment.cancelled_at
                ? `${translate(
                    'leases.cancelled_on'
                )}: ${formatDate(
                    increment.cancelled_at
                )}`
                : null,
        ].filter(
            Boolean
        );

    return `
        <div
            class="
                rounded-xl border
                border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)] p-4
            "
        >
            <div
                class="
                    flex flex-wrap items-center
                    justify-between gap-2
                "
            >
                <div
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        formatCurrency(
                            Number(
                                increment.old_rent_amount
                                ?? 0
                            )
                        )
                    )}
                    →
                    ${escapeHtml(
                        formatCurrency(
                            Number(
                                increment.new_rent_amount
                                ?? 0
                            )
                        )
                    )}
                </div>

                ${rentIncrementStatusBadge(
                    increment.status
                )}
            </div>

            <div
                class="
                    mt-2 flex flex-wrap
                    gap-x-5 gap-y-1
                    text-xs text-[var(--pm-text-muted)]
                "
            >
                <span>
                    ${escapeHtml(
                        rentIncrementAmountLabel(
                            increment
                        )
                    )}
                </span>

                <span>
                    ${escapeHtml(
                        translate(
                            'leases.effective_date'
                        )
                    )}:
                    ${escapeHtml(
                        formatDate(
                            increment.effective_date
                        )
                    )}
                </span>
            </div>

            ${
                timeline.length > 0
                    ? `
                        <div
                            class="
                                mt-2 space-y-0.5
                                text-xs text-[var(--pm-text-subtle)]
                            "
                        >
                            ${
                                timeline
                                    .map(
                                        (entry) => `
                                            <div>
                                                ${escapeHtml(
                                                    entry
                                                )}
                                            </div>
                                        `
                                    )
                                    .join('')
                            }
                        </div>
                    `
                    : ''
            }

            ${
                canManage
                && increment.status === 'scheduled'
                    ? `
                        <div
                            class="mt-3"
                            data-requires-capability="manage_operations"
                        >
                            <button
                                type="button"
                                data-cancel-rent-increment
                                data-increment-id="${escapeHtml(
                                    increment.id
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
                                        'leases.cancel_increment'
                                    )
                                )}
                            </button>
                        </div>
                    `
                    : ''
            }
        </div>
    `;
}

/**
 * Status pill for one increment.
 */
function rentIncrementStatusBadge(
    status
) {
    let classes =
        'bg-[var(--pm-surface-muted)] text-[var(--pm-text-secondary)]';

    let label =
        String(
            status || ''
        );

    if (status === 'scheduled') {
        classes =
            'bg-[var(--pm-info-background)] text-[var(--pm-info-text)]';

        label =
            translate(
                'leases.increment_status_scheduled'
            );
    } else if (
        status === 'applied'
    ) {
        classes =
            'bg-[var(--pm-success-background)] text-[var(--pm-success-text)]';

        label =
            translate(
                'leases.increment_status_applied'
            );
    } else if (
        status === 'cancelled'
    ) {
        label =
            translate(
                'leases.increment_status_cancelled'
            );
    }

    return `
        <span
            class="
                rounded-full
                px-2.5 py-1
                text-xs font-medium
                ${classes}
            "
        >
            ${escapeHtml(
                label
            )}
        </span>
    `;
}

/**
 * Human description of the configured increase.
 */
function rentIncrementAmountLabel(
    increment
) {
    const value =
        Number(
            increment.increment_value
            ?? 0
        );

    if (
        increment.increment_type
        === 'percentage'
    ) {
        return `+${value}%`;
    }

    return `+${formatCurrency(
        value
    )}`;
}

/**
 * Schedule a future rent increment (Manager workflow).
 *
 * Business-rule refusals from the server (pending increment exists,
 * non-increasing rent, interval too short, …) arrive as 422 validation
 * messages and render inline in the drawer.
 */
async function submitRentIncrementSchedule(
    event
) {
    event.preventDefault();

    if (
        ! Number.isInteger(
            rentIncrementsLeaseId
        )
    ) {
        return;
    }

    const effectiveDate =
        dateForApi(
            formValue(
                'rent-increment-effective-date'
            )
        );

    if (
        ! /^\d{4}-\d{2}-\d{2}$/.test(
            effectiveDate
        )
    ) {
        showRentIncrementsError(
            translate(
                'leases.increment_invalid_date'
            )
        );

        return;
    }

    const submitButton =
        document.getElementById(
            'rent-increment-submit'
        );

    try {
        hideRentIncrementsError();

        if (submitButton) {
            submitButton.disabled =
                true;
        }

        const response =
            await apiRequest(
                `/api/leases/${rentIncrementsLeaseId}/rent-increments`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            increment_type:
                                formValue(
                                    'rent-increment-type'
                                ),

                            increment_value:
                                Number(
                                    formValue(
                                        'rent-increment-value'
                                    )
                                ),

                            effective_date:
                                effectiveDate,
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        document
            .getElementById(
                'rent-increment-form'
            )
            ?.reset();

        updateRentIncrementScheduleUnit();

        await loadRentIncrements();
    } catch (error) {
        showRentIncrementsError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.increment_schedule_failed'
                )
        );
    } finally {
        if (submitButton) {
            submitButton.disabled =
                false;
        }
    }
}

/**
 * Cancel one scheduled increment before the scheduler applies it.
 */
async function cancelRentIncrement(
    incrementId
) {
    const numericIncrementId =
        Number(
            incrementId
        );

    if (
        ! Number.isInteger(
            numericIncrementId
        )
        || numericIncrementId <= 0
    ) {
        return;
    }

    if (
        ! window.confirm(
            translate(
                'leases.confirm_cancel_increment'
            )
        )
    ) {
        return;
    }

    try {
        hideRentIncrementsError();

        const response =
            await apiRequest(
                `/api/rent-increments/${numericIncrementId}/cancel`,
                {
                    method:
                        'POST',
                }
            );

        await parseJsonResponse(
            response
        );

        await loadRentIncrements();
    } catch (error) {
        showRentIncrementsError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.increment_cancel_failed'
                )
        );
    }
}

/**
 * Surface a Rent Increments drawer failure.
 */
function showRentIncrementsError(
    message
) {
    const box =
        document.getElementById(
            'rent-increments-error'
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
 * Clear Rent Increments drawer failures.
 */
function hideRentIncrementsError() {
    const box =
        document.getElementById(
            'rent-increments-error'
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
| Lease Financial History
|--------------------------------------------------------------------------
*/

/**
 * Register Financial History drawer controls.
 */
function initializeLeaseFinancialHistoryModal() {
    document
        .getElementById(
            'lease-financial-history-modal-close'
        )
        ?.addEventListener(
            'click',
            closeLeaseFinancialHistoryModal
        );

    document
        .getElementById(
            'lease-financial-history-close-footer'
        )
        ?.addEventListener(
            'click',
            closeLeaseFinancialHistoryModal
        );

    document
        .getElementById(
            'lease-financial-history-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeLeaseFinancialHistoryModal
        );

    document.addEventListener(
        'keydown',
        (event) => {
            const drawer =
                document.getElementById(
                    'lease-financial-history-modal'
                );

            if (
                event.key === 'Escape'
                && drawer?.classList.contains(
                    'pm-drawer-active'
                )
            ) {
                closeLeaseFinancialHistoryModal();
            }
        }
    );
}

/**
 * Open and load the canonical financial history for one Lease.
 */
async function openLeaseFinancialHistoryModal(
    leaseId
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
    ) {
        return;
    }

    resetLeaseFinancialHistoryModal();

    const lease =
        loadedLeasesById.get(
            String(
                numericLeaseId
            )
        );

    setText(
        'lease-financial-history-modal-description',
        [
            lease?.unit?.building?.name,
            lease?.unit?.name,
            partyDisplayName(
                lease?.tenant
            ),
        ]
            .filter(Boolean)
            .join(' · ')
        || translate(
            'leases.financial_history_description'
        )
    );

    showLeaseFinancialHistoryDrawer();

    try {
        const response =
            await apiRequest(
                `/api/leases/${numericLeaseId}/financial-history`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderLeaseFinancialHistory(
            {
                ...payload,

                export_lease_id:
                    numericLeaseId,
            }
        );
    } catch (error) {
        showLeaseFinancialHistoryError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.financial_history_unable_load'
                )
        );
    }
}

function showLeaseFinancialHistoryDrawer() {
    openDrawer(
        'lease-financial-history-modal'
    );
}

function closeLeaseFinancialHistoryModal() {
    closeDrawer(
        'lease-financial-history-modal',
        {
            onClosed:
                resetLeaseFinancialHistoryModal,
        }
    );
}

function resetLeaseFinancialHistoryModal() {
    const loading =
        document.getElementById(
            'lease-financial-history-loading'
        );

    const content =
        document.getElementById(
            'lease-financial-history-content'
        );

    const error =
        document.getElementById(
            'lease-financial-history-error'
        );

    loading?.classList.remove(
        'hidden'
    );

    content?.classList.add(
        'hidden'
    );

    if (content) {
        content.innerHTML = '';
    }

    error?.classList.add(
        'hidden'
    );

    if (error) {
        error.textContent = '';
    }
}

function showLeaseFinancialHistoryError(
    message
) {
    document
        .getElementById(
            'lease-financial-history-loading'
        )
        ?.classList.add(
            'hidden'
        );

    const error =
        document.getElementById(
            'lease-financial-history-error'
        );

    if (! error) {
        return;
    }

    error.textContent =
        message;

    error.classList.remove(
        'hidden'
    );
}


/**
 * Download one Lease Financial History export through authenticated API.
 */
async function downloadLeaseFinancialHistoryExport(
    leaseId,
    format
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
        || ! [
            'pdf',
            'xlsx',
            'csv',
        ].includes(
            format
        )
    ) {
        return;
    }

    try {
        const response =
            await apiRequest(
                `/api/leases/${numericLeaseId}/financial-history/${format}`
            );

        if (! response.ok) {
            throw new Error(
                translate(
                    'leases.financial_history_unable_load'
                )
            );
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
            `lease-financial-history-${numericLeaseId}.${format}`;

        document.body.appendChild(
            link
        );

        link.click();
        link.remove();

        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
        );
    } catch (error) {
        showLeaseFinancialHistoryError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.financial_history_unable_load'
                )
        );
    }
}


/**
 * Render the three frozen Phase 6 history export actions.
 */
function leaseFinancialHistoryExportActions(
    leaseId
) {
    const numericLeaseId =
        Number(
            leaseId
        );

    if (
        ! Number.isInteger(
            numericLeaseId
        )
        || numericLeaseId <= 0
    ) {
        return '';
    }

    return `
        <div
            class="
                pm-lease-financial-history-exports
                mb-4 grid grid-cols-3 gap-2
            "
        >
            <button
                type="button"
                data-financial-history-export="pdf"
                data-lease-id="${escapeHtml(
                    numericLeaseId
                )}"
                class="pm-button-secondary w-full"
            >
                ${escapeHtml(
                    translate(
                        'leases.financial_history_export_pdf'
                    )
                )}
            </button>

            <button
                type="button"
                data-financial-history-export="xlsx"
                data-lease-id="${escapeHtml(
                    numericLeaseId
                )}"
                class="pm-button-secondary w-full"
            >
                ${escapeHtml(
                    translate(
                        'leases.financial_history_export_excel'
                    )
                )}
            </button>

            <button
                type="button"
                data-financial-history-export="csv"
                data-lease-id="${escapeHtml(
                    numericLeaseId
                )}"
                class="pm-button-secondary w-full"
            >
                ${escapeHtml(
                    translate(
                        'leases.financial_history_export_csv'
                    )
                )}
            </button>
        </div>
    `;
}


/**
 * Wire Financial History export actions after dynamic rendering.
 */
function initializeLeaseFinancialHistoryExportActions(
    container
) {
    container
        ?.querySelectorAll(
            '[data-financial-history-export]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        await downloadLeaseFinancialHistoryExport(
                            button.dataset.leaseId,
                            button.dataset.financialHistoryExport
                        );
                    }
                );
            }
        );
}


function renderLeaseFinancialHistory(
    payload
) {
    const loading =
        document.getElementById(
            'lease-financial-history-loading'
        );

    const content =
        document.getElementById(
            'lease-financial-history-content'
        );

    if (! content) {
        return;
    }

    loading?.classList.add(
        'hidden'
    );

    const events =
        Array.isArray(
            payload?.events
        )
            ? payload.events
            : [];

    if (events.length === 0) {
        content.innerHTML = `
            ${leaseFinancialHistoryExportActions(
                payload?.export_lease_id
            )}

            <div
                class="
                    pm-lease-financial-history-empty
                    rounded-xl border border-dashed
                    px-6 py-12 text-center
                "
            >
                <div
                    class="
                        text-sm font-medium
                        text-[var(--pm-text)]
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.financial_history_empty'
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
                            'leases.financial_history_empty_description'
                        )
                    )}
                </div>
            </div>
        `;

        content.classList.remove(
            'hidden'
        );

        initializeLeaseFinancialHistoryExportActions(
            content
        );

        return;
    }

    content.innerHTML =
        leaseFinancialHistoryExportActions(
            payload?.export_lease_id
        )
        + events
            .map(
                leaseFinancialHistoryEvent
            )
            .join('');

    content.classList.remove(
        'hidden'
    );

    initializeLeaseFinancialHistoryExportActions(
        content
    );

    content
        .querySelectorAll(
            '[data-financial-history-document]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openLeaseFinancialHistoryDocument(
                            button.dataset.endpoint
                        );
                    }
                );
            }
        );
}

function leaseFinancialHistoryEvent(
    event
) {
    const reference =
        event.reference
            ? `
                <div
                    class="
                        pm-lease-financial-history-muted
                        mt-1 text-xs
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.financial_history_reference'
                        )
                    )}:
                    ${escapeHtml(
                        event.reference
                    )}
                </div>
            `
            : '';

    const method =
        event.payment_method
            ? `
                <div
                    class="
                        pm-lease-financial-history-muted
                        mt-1 text-xs
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.financial_history_payment_method'
                        )
                    )}:
                    ${escapeHtml(
                        financialHistoryPaymentMethodLabel(
                            event.payment_method
                        )
                    )}
                </div>
            `
            : '';

    const fund =
        event.fund_type
            ? `
                <div
                    class="
                        pm-lease-financial-history-muted
                        mt-1 text-xs
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.financial_history_fund'
                        )
                    )}:
                    ${escapeHtml(
                        financialHistoryFundLabel(
                            event.fund_type
                        )
                    )}
                </div>
            `
            : '';

    const documentButton =
        event.document?.endpoint
            ? `
                <button
                    type="button"
                    data-financial-history-document
                    data-endpoint="${escapeHtml(
                        event.document.endpoint
                    )}"
                    class="
                        pm-lease-financial-history-document
                        mt-3 inline-flex
                        items-center rounded-lg
                        border px-3 py-2
                        text-xs font-medium
                        transition
                    "
                >
                    ${escapeHtml(
                        translate(
                            'leases.financial_history_open_document'
                        )
                    )}
                </button>
            `
            : '';

    return `
        <article
            class="
                pm-lease-financial-history-event
                rounded-xl border p-4
            "
        >
            <div
                class="
                    flex items-start
                    justify-between gap-4
                "
            >
                <div class="min-w-0">
                    <div
                        class="
                            pm-lease-financial-history-title
                            text-sm font-semibold
                        "
                    >
                        ${escapeHtml(
                            financialHistoryEventLabel(
                                event
                            )
                        )}
                    </div>

                    <div
                        class="
                            pm-lease-financial-history-muted
                            mt-1 text-xs
                        "
                    >
                        ${escapeHtml(
                            formatDate(
                                event.occurred_on
                            )
                        )}
                    </div>

                    ${reference}
                    ${method}
                    ${fund}
                </div>

                <div
                    class="
                        pm-lease-financial-history-title
                        shrink-0 text-right
                        text-sm font-semibold
                    "
                >
                    ${escapeHtml(
                        formatCurrency(
                            Number(
                                event.amount
                                ?? 0
                            )
                        )
                    )}
                </div>
            </div>

            ${documentButton}
        </article>
    `;
}

function financialHistoryEventLabel(
    event
) {
    const key =
        {
            invoice:
                'leases.financial_history_event_invoice',

            payment:
                'leases.financial_history_event_payment',

            fund_deposit:
                'leases.financial_history_event_fund_deposit',

            rent_reserve_consumption:
                'leases.financial_history_event_rent_reserve_consumption',

            advance_consumption:
                'leases.financial_history_event_advance_consumption',

            withdrawal:
                'leases.financial_history_event_withdrawal',

            adjustment:
                'leases.financial_history_event_adjustment',

            security_deposit_application:
                'leases.financial_history_event_security_application',

            security_deposit_deduction:
                'leases.financial_history_event_security_deduction',

            security_deposit_settlement:
                'leases.financial_history_event_security_settlement',

            security_deposit_movement:
                'leases.financial_history_event_security_movement',

            fund_movement:
                'leases.financial_history_event_fund_movement',
        }[event.event_type];

    if (key) {
        return translate(
            key
        );
    }

    return String(
        event.description
        || event.event_type
        || ''
    );
}

function financialHistoryFundLabel(
    type
) {
    const key =
        {
            rent_reserve:
                'leases.financial_history_fund_rent_reserve',

            consumable_advance:
                'leases.financial_history_fund_consumable_advance',

            security_deposit:
                'leases.financial_history_fund_security_deposit',
        }[type];

    return key
        ? translate(
            key
        )
        : String(
            type || ''
        );
}

function financialHistoryPaymentMethodLabel(
    method
) {
    const key =
        {
            cash:
                'leases.financial_history_method_cash',

            bank_transfer:
                'leases.financial_history_method_bank_transfer',

            mobile_money:
                'leases.financial_history_method_mobile_payment',

            momo:
                'leases.financial_history_method_mobile_payment',
        }[method];

    return key
        ? translate(
            key
        )
        : String(
            method || ''
        );
}

/**
 * Fetch and open an authenticated financial document.
 */
async function openLeaseFinancialHistoryDocument(
    endpoint
) {
    if (! endpoint) {
        return;
    }

    try {
        const response =
            await apiRequest(
                endpoint
            );

        if (! response.ok) {
            throw new Error(
                translate(
                    'leases.financial_history_unable_open_document'
                )
            );
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        window.open(
            url,
            '_blank',
            'noopener,noreferrer'
        );

        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
        );
    } catch (error) {
        showLeaseFinancialHistoryError(
            error instanceof Error
                ? error.message
                : translate(
                    'leases.financial_history_unable_open_document'
                )
        );
    }
}
