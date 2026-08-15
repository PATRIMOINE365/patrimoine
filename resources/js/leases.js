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
| - status and tenant filtering;
| - pagination;
| - Lease creation;
| - Lease editing;
| - Lease deletion;
| - Unit selection;
| - Tenant and Agent Party selection;
| - Lease contractual terms;
| - Security Deposit close-out;
| - itemized Security Deposit deductions;
| - final Security Deposit settlement and voucher access.
|
| General financial transactions such as Payments, Invoices and owner
| accounting remain in their dedicated modules.
|
*/

import {
    apiRequest,
    escapeHtml,
    formValue,
    formatCurrency,
    formatDate,
    getPresentationConfiguration,
    nullableFormValue,
    parseJsonResponse,
    setText,
} from './core.js';

/*
|--------------------------------------------------------------------------
| Module State
|--------------------------------------------------------------------------
*/

let loadedLeasesById =
    new Map();

let availableUnits =
    [];

let availableTenants =
    [];

let availableAgents =
    [];

let leaseFormMode =
    'create';

let editingLeaseId =
    null;

let defaultVatRate =
    '18.00';
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

    initializeSecurityDepositModal();

    /*
     * Register operational Tenant Funds controls.
     *
     * This initializer owns the modal close/backdrop actions, Rent Reserve
     * and Consumable Advance forms, and the hand-off to the Security Deposit
     * close-out workflow.
     */
    initializeTenantFundsModal();

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
                : 'Unable to initialize Leases.'
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
 * Patrimoine currently exposes financial defaults through the existing
 * Managing Organisation endpoint because ApplicationSetting belongs to
 * the single Patrimoine installation.
 *
 * A fresh installation may not yet have a Managing Organisation.
 * In that case the historical Patrimoine default of 18% remains active.
 */
async function loadLeaseDefaults() {
    defaultVatRate =
        '18.00';

    const response =
        await apiRequest(
            '/api/managing-organisation'
        );

    /*
     * A fresh installation can legitimately have no configured managing
     * organisation yet. Preserve the built-in fallback rather than blocking
     * Lease creation.
     */
    if (response.status === 404) {
        return;
    }

    const organisation =
        await parseJsonResponse(
            response
        );

    const configuredRate =
        Number(
            organisation
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
        'All Tenants'
    );

    populatePartySelect(
        'lease-tenant',
        availableTenants,
        'Select tenant…'
    );

    populatePartySelect(
        'lease-agent',
        availableAgents,
        'No Agent'
    );

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
                    text-slate-500
                "
            >
                No matching units found.
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
        || 'Property';

    const unitName =
        unit?.name
        || `Unit #${unit?.id ?? ''}`;

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
                border-b border-slate-100
                px-4 py-3
                text-left
                transition
                last:border-b-0
                hover:bg-patrimoine-50
                focus:bg-patrimoine-50
                focus:outline-none
            "
        >
            <div
                class="
                    text-sm font-medium
                    text-slate-950
                "
            >
                ${escapeHtml(
                    unitName
                )}
            </div>

            <div
                class="
                    mt-0.5 text-xs
                    text-slate-600
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
                                text-slate-400
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
                                text-patrimoine-700
                            "
                        >
                            Owner:
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
                        text-sm text-slate-400
                    "
                >
                    No ownership information available.
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
                                    bg-white
                                    px-3 py-1.5
                                    text-xs font-medium
                                    text-slate-700
                                    ring-1 ring-slate-200
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
                                                    text-slate-400
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
    document
        .getElementById(
            'lease-status-filter'
        )
        ?.addEventListener(
            'change',
            () => {
                loadLeases(
                    1
                );
            }
        );

    document
        .getElementById(
            'lease-tenant-filter'
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
                text-sm text-slate-400
            "
        >
            Loading leases…
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
                : 'Unable to load Leases.'
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
                    border-dashed border-slate-200
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        text-sm font-medium
                        text-slate-900
                    "
                >
                    No leases found
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                >
                    Create a Lease or change the current filters.
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
        || 'Property';

    const unit =
        lease.unit?.name
        || 'Unit';

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
            class="
                mb-4 rounded-xl
                border border-slate-200
                bg-white p-5
                last:mb-0
            "
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
                                text-slate-950
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
                            text-slate-600
                        "
                    >
                        Tenant:
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
                                        text-slate-500
                                    "
                                >
                                    Agent:
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
                            text-sm text-slate-500
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
                            Start:
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
                                        End:
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
                            VAT:
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
                        data-tenant-funds
                        data-lease-id="${escapeHtml(
                            lease.id
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
                        Tenant Funds
                    </button>

                    <button
                        type="button"
                        data-edit-lease
                        data-lease-id="${escapeHtml(
                            lease.id
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
                        data-delete-lease
                        data-lease-id="${escapeHtml(
                            lease.id
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

function leaseStatusBadge(status) {
    const label =
        statusLabel(
            status
        );

    let classes =
        'bg-slate-100 text-slate-600';

    if (status === 'active') {
        classes =
            'bg-green-100 text-green-700';
    } else if (
        status === 'notice'
    ) {
        classes =
            'bg-amber-100 text-amber-700';
    } else if (
        status === 'terminated'
    ) {
        classes =
            'bg-red-100 text-red-700';
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
            return 'Draft';

        case 'active':
            return 'Active';

        case 'notice':
            return 'Notice';

        case 'terminated':
            return 'Terminated';

        default:
            return String(
                status || ''
            );
    }
}

function frequencyLabel(frequency) {
    switch (frequency) {
        case 'monthly':
            return 'month';

        case 'quarterly':
            return 'quarter';

        case 'bi_yearly':
            return 'six months';

        case 'yearly':
            return 'year';

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
            '[data-tenant-funds]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openTenantFundsModal(
                            button.dataset
                                .leaseId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-edit-lease]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openEditLeaseModal(
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
            <div class="text-sm text-slate-500">
                Page ${currentPage}
                of ${lastPage}
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
                    class="
                        rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        disabled:opacity-40
                    "
                >
                    Previous
                </button>

                <button
                    id="leases-next"
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
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closeLeaseModal();
            }
        }
    );
}

function openCreateLeaseModal() {
    resetLeaseForm();

    leaseFormMode =
        'create';

    editingLeaseId =
        null;

    configureLeaseModal();

    showLeaseModal();
}

async function openEditLeaseModal(
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

    resetLeaseForm();

    leaseFormMode =
        'edit';

    editingLeaseId =
        numericLeaseId;

    configureLeaseModal();

    showLeaseModal();

    try {
        const response =
            await apiRequest(
                `/api/leases/${numericLeaseId}`
            );

        const lease =
            await parseJsonResponse(
                response
            );

        populateLeaseForm(
            lease
        );
    } catch (error) {
        showLeaseFormError(
            error instanceof Error
                ? error.message
                : 'Unable to load Lease.'
        );
    }
}

function configureLeaseModal() {
    const editing =
        leaseFormMode === 'edit';

    setText(
        'lease-modal-title',
        editing
            ? 'Edit Lease'
            : 'Add Lease'
    );

    setText(
        'lease-modal-description',
        editing
            ? 'Update the tenancy agreement and contractual terms.'
            : 'Create a tenancy agreement for a property unit.'
    );

    setText(
        'lease-submit-button',
        editing
            ? 'Save Changes'
            : 'Create Lease'
    );
}

function showLeaseModal() {
    const modal =
        document.getElementById(
            'lease-modal'
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

function closeLeaseModal() {
    const modal =
        document.getElementById(
            'lease-modal'
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

    leaseFormMode =
        'create';

    editingLeaseId =
        null;

    resetLeaseForm();

    configureLeaseModal();
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
    return value
        ? String(value)
            .slice(
                0,
                10
            )
        : '';
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

    checkbox.disabled =
        ! hasAdvance;

    if (! hasAdvance) {
        checkbox.checked =
            false;
    }

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

    const editing =
        leaseFormMode === 'edit'
        && Number.isInteger(
            editingLeaseId
        );

    const payload =
        buildLeasePayload();


        if (
            ! Number.isInteger(
                payload.unit_id
            )
            || payload.unit_id <= 0
        ) {
            showLeaseFormError(
                'Select a valid Property / Unit.'
            );

            return;
        }

        if (
            payload.rent_reserve_amount
            > payload.advance_payment_amount
        ) {
            showLeaseFormError(
                'Rent Reserve cannot exceed Total Advance Payment.'
            );

            return;
        }

    try {
        submitButton.disabled =
            true;

        submitButton.textContent =
            editing
                ? 'Saving Changes…'
                : 'Creating Lease…';

        const endpoint =
            editing
                ? `/api/leases/${editingLeaseId}`
                : '/api/leases';

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

        closeLeaseModal();

        await loadLeases(
            1
        );
    } catch (error) {
        showLeaseFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? 'Unable to update Lease.'
                        : 'Unable to create Lease.'
                )
        );
    } finally {
        submitButton.disabled =
            false;

        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Create Lease';
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
            formValue(
                'lease-start-date'
            ),

        end_date:
            nullableFormValue(
                'lease-end-date'
            ),

        status:
            formValue(
                'lease-status'
            ),

        termination_notice_date:
            nullableFormValue(
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
            nullableFormValue(
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
            nullableFormValue(
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

    const label =
        [
            lease?.unit?.building?.name,
            lease?.unit?.name,
        ]
            .filter(Boolean)
            .join(' / ');

    const confirmed =
        window.confirm(
            `Delete ${label || 'this Lease'}?\n\n`
            + 'Leases with financial history may not be deleted.'
        );

    if (! confirmed) {
        return;
    }

    try {
        hideLeasePageError();

        const response =
            await apiRequest(
                `/api/leases/${numericLeaseId}`,
                {
                    method:
                        'DELETE',
                }
            );

        if (
            response.status !== 204
        ) {
            await parseJsonResponse(
                response
            );
        }

        await loadLeases(
            1
        );
    } catch (error) {
        showLeasePageError(
            error instanceof Error
                ? error.message
                : 'Unable to delete Lease.'
        );
    }
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
| Security Deposit Operations
|--------------------------------------------------------------------------
|
| Security Deposit settlement is an operational Lease close-out workflow.
|
| Contractual deposit terms remain in the normal Lease editor, while this
| modal works with actual held funds, deductions and immutable settlement
| records from the server.
|
*/

let securityDepositLeaseId =
    null;

/**
 * Initialize Security Deposit modal controls.
 */
function initializeSecurityDepositModal() {
    document
        .getElementById(
            'security-deposit-modal-close'
        )
        ?.addEventListener(
            'click',
            closeSecurityDepositModal
        );

    document
        .getElementById(
            'security-deposit-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeSecurityDepositModal
        );

    document
        .getElementById(
            'security-deposit-deduction-form'
        )
        ?.addEventListener(
            'submit',
            submitSecurityDepositDeduction
        );

    document
        .getElementById(
            'security-deposit-settlement-form'
        )
        ?.addEventListener(
            'submit',
            submitSecurityDepositSettlement
        );


            /*
        * Financial document API routes require the same Sanctum Bearer token
        * used by the rest of the authenticated application.
        *
        * The voucher must therefore be fetched through apiRequest() rather than
        * opened through ordinary browser navigation.
        */
        document
            .getElementById(
                'security-deposit-voucher-link'
            )
            ?.addEventListener(
                'click',
                openSecurityDepositVoucher
            );



    document.addEventListener(
        'keydown',
        (event) => {
            const modal =
                document.getElementById(
                    'security-deposit-modal'
                );

            if (
                event.key === 'Escape'
                && modal
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closeSecurityDepositModal();
            }
        }
    );
}

/**
 * Open the operational Security Deposit view for one Lease.
 */
async function openSecurityDepositModal(
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

    securityDepositLeaseId =
        numericLeaseId;

    resetSecurityDepositModal();

    const lease =
        loadedLeasesById.get(
            String(
                numericLeaseId
            )
        );

    setText(
        'security-deposit-modal-description',
        [
            lease?.unit?.building?.name,
            lease?.unit?.name,
            partyDisplayName(
                lease?.tenant
            ),
        ]
            .filter(Boolean)
            .join(' · ')
            || 'Review held funds, deductions and final settlement.'
    );

    const modal =
        document.getElementById(
            'security-deposit-modal'
        );

    modal?.classList.remove(
        'hidden'
    );

    modal?.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    await loadSecurityDepositPosition();
}

/**
 * Close and reset the Security Deposit modal.
 */
function closeSecurityDepositModal() {
    const modal =
        document.getElementById(
            'security-deposit-modal'
        );

    modal?.classList.add(
        'hidden'
    );

    modal?.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    securityDepositLeaseId =
        null;

    resetSecurityDepositModal();
}

/**
 * Restore modal controls to their initial loading state.
 */
function resetSecurityDepositModal() {
    hideSecurityDepositError();

    document
        .getElementById(
            'security-deposit-loading'
        )
        ?.classList.remove(
            'hidden'
        );

    document
        .getElementById(
            'security-deposit-content'
        )
        ?.classList.add(
            'hidden'
        );

    document
        .getElementById(
            'security-deposit-deduction-form'
        )
        ?.reset();

    document
        .getElementById(
            'security-deposit-settlement-form'
        )
        ?.reset();

    document
        .getElementById(
            'security-deposit-settled'
        )
        ?.classList.add(
            'hidden'
        );

    const lifecycle =
        document.getElementById(
            'security-deposit-lifecycle-message'
        );

    if (lifecycle) {
        lifecycle.textContent = '';

        lifecycle.classList.add(
            'hidden'
        );
    }

    const voucherButton =
        document.getElementById(
            'security-deposit-voucher-link'
        );

    if (voucherButton) {
        delete voucherButton.dataset.endpoint;

        voucherButton.disabled =
            true;
    }
}

/**
 * Retrieve server-calculated Security Deposit position.
 */
async function loadSecurityDepositPosition() {
    if (! securityDepositLeaseId) {
        return;
    }

    try {
        hideSecurityDepositError();

        const response =
            await apiRequest(
                `/api/leases/${securityDepositLeaseId}/security-deposit`
            );

        const position =
            await parseJsonResponse(
                response
            );

        renderSecurityDepositPosition(
            position
        );
    } catch (error) {
        showSecurityDepositError(
            error instanceof Error
                ? error.message
                : 'Unable to load Security Deposit.'
        );
    } finally {
        document
            .getElementById(
                'security-deposit-loading'
            )
            ?.classList.add(
                'hidden'
            );

        document
            .getElementById(
                'security-deposit-content'
            )
            ?.classList.remove(
                'hidden'
            );
    }
}

/**
 * Render authoritative Security Deposit values returned by the API.
 */
function renderSecurityDepositPosition(
    position
) {
    const lease =
        loadedLeasesById.get(
            String(
                securityDepositLeaseId
            )
        );

    setText(
        'security-deposit-contractual',
        formatCurrency(
            Number(
                position?.contractual_amount
                ?? 0
            )
        )
    );

    setText(
        'security-deposit-held',
        formatCurrency(
            Number(
                position?.held_balance
                ?? 0
            )
        )
    );

    setText(
        'security-deposit-deduction-total',
        formatCurrency(
            Number(
                position?.deduction_total
                ?? 0
            )
        )
    );

    setText(
        'security-deposit-refund',
        formatCurrency(
            Number(
                position?.estimated_refund
                ?? 0
            )
        )
    );

    setText(
        'security-deposit-debt',
        formatCurrency(
            Number(
                position?.estimated_tenant_debt
                ?? 0
            )
        )
    );

    renderSecurityDepositDeductions(
        position?.deductions
    );

    const settlement =
        position?.settlement
        ?? null;

    const terminated =
        lease?.status
        === 'terminated';

    const deductionForm =
        document.getElementById(
            'security-deposit-deduction-form'
        );

    const settlementForm =
        document.getElementById(
            'security-deposit-settlement-form'
        );

    const settledBox =
        document.getElementById(
            'security-deposit-settled'
        );

    const lifecycle =
        document.getElementById(
            'security-deposit-lifecycle-message'
        );

    deductionForm?.classList.add(
        'hidden'
    );

    settlementForm?.classList.add(
        'hidden'
    );

    settledBox?.classList.add(
        'hidden'
    );

    lifecycle?.classList.add(
        'hidden'
    );

    if (settlement) {
        settledBox?.classList.remove(
            'hidden'
        );

        setText(
            'security-deposit-voucher-number',
            `Voucher ${settlement.refund_voucher_number}`
        );


        const voucherButton =
            document.getElementById(
                'security-deposit-voucher-link'
            );

        if (voucherButton) {
            /*
             * Store the authenticated API endpoint as data rather than
             * assigning it as an href. Ordinary navigation would omit the
             * sessionStorage Bearer token and receive HTTP 401.
             */
            voucherButton.dataset.endpoint =
                `/api/security-deposit-settlements/${settlement.id}/voucher`;

            voucherButton.disabled =
                false;
        }




        return;
    }

    if (! terminated) {
        if (lifecycle) {
            lifecycle.textContent =
                'Final Security Deposit deductions and settlement become available once the Lease is terminated.';

            lifecycle.classList.remove(
                'hidden'
            );
        }

        return;
    }

    deductionForm?.classList.remove(
        'hidden'
    );

    settlementForm?.classList.remove(
        'hidden'
    );

    /*
     * Default operational dates to today while still allowing the Property
     * Manager to enter the actual assessment/settlement date.
     */
    const today =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );

    const deductionDate =
        document.getElementById(
            'security-deduction-date'
        );

    if (
        deductionDate
        && ! deductionDate.value
    ) {
        deductionDate.value =
            today;
    }

    const settlementDate =
        document.getElementById(
            'security-settlement-date'
        );

    if (
        settlementDate
        && ! settlementDate.value
    ) {
        settlementDate.value =
            today;
    }
}

/**
 * Render itemized close-out deductions.
 */
function renderSecurityDepositDeductions(
    deductions
) {
    const container =
        document.getElementById(
            'security-deposit-deductions'
        );

    if (! container) {
        return;
    }

    const items =
        Array.isArray(
            deductions
        )
            ? deductions
            : [];

    if (items.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-lg border
                    border-dashed border-slate-200
                    px-4 py-6 text-center
                    text-sm text-slate-500
                "
            >
                No deductions recorded.
            </div>
        `;

        return;
    }

    container.innerHTML = `
        <div class="overflow-x-auto">
            <table
                class="
                    w-full border-collapse
                    text-left text-sm
                "
            >
                <thead>
                    <tr
                        class="
                            border-b border-slate-200
                            text-xs uppercase
                            tracking-wide text-slate-500
                        "
                    >
                        <th class="px-3 py-2">
                            Date
                        </th>

                        <th class="px-3 py-2">
                            Description
                        </th>

                        <th class="px-3 py-2">
                            Reference
                        </th>

                        <th
                            class="
                                px-3 py-2
                                text-right
                            "
                        >
                            Amount
                        </th>
                    </tr>
                </thead>

                <tbody>
                    ${
                        items
                            .map(
                                (deduction) => `
                                    <tr
                                        class="
                                            border-b
                                            border-slate-100
                                        "
                                    >
                                        <td
                                            class="
                                                px-3 py-3
                                                text-slate-600
                                            "
                                        >
                                            ${escapeHtml(
                                                formatDate(
                                                    deduction
                                                        .deduction_date
                                                )
                                            )}
                                        </td>

                                        <td class="px-3 py-3">
                                            <div
                                                class="
                                                    font-medium
                                                    text-slate-900
                                                "
                                            >
                                                ${escapeHtml(
                                                    deduction
                                                        .description
                                                    ?? ''
                                                )}
                                            </div>

                                            ${
                                                deduction.notes
                                                    ? `
                                                        <div
                                                            class="
                                                                mt-1 text-xs
                                                                text-slate-500
                                                            "
                                                        >
                                                            ${escapeHtml(
                                                                deduction.notes
                                                            )}
                                                        </div>
                                                    `
                                                    : ''
                                            }
                                        </td>

                                        <td
                                            class="
                                                px-3 py-3
                                                text-slate-600
                                            "
                                        >
                                            ${escapeHtml(
                                                deduction.reference
                                                || '—'
                                            )}
                                        </td>

                                        <td
                                            class="
                                                px-3 py-3
                                                text-right
                                                font-medium
                                                text-slate-900
                                            "
                                        >
                                            ${escapeHtml(
                                                formatCurrency(
                                                    Number(
                                                        deduction.amount
                                                        ?? 0
                                                    )
                                                )
                                            )}
                                        </td>
                                    </tr>
                                `
                            )
                            .join('')
                    }
                </tbody>
            </table>
        </div>
    `;
}


/**
 * Download and display the finalized Security Deposit voucher.
 *
 * Financial document routes are protected by Sanctum. The application token
 * therefore has to be attached through apiRequest() rather than relying on a
 * normal browser link.
 */
async function openSecurityDepositVoucher() {
    const button =
        document.getElementById(
            'security-deposit-voucher-link'
        );

    const endpoint =
        button?.dataset
            ?.endpoint;

    if (
        ! button
        || ! endpoint
    ) {
        return;
    }

    hideSecurityDepositError();

    /*
     * Open an empty browser tab immediately while this code is still running
     * inside the user's click event. This avoids popup blockers treating the
     * eventual PDF window as an unsolicited popup after the asynchronous
     * authenticated request completes.
     */
    const viewer =
        window.open(
            '',
            '_blank'
        );

    if (! viewer) {
        showSecurityDepositError(
            'The voucher could not be opened because the browser blocked the new tab.'
        );

        return;
    }

    const originalLabel =
        button.textContent;

    try {
        button.disabled =
            true;

        button.textContent =
            'Opening…';

        const response =
            await apiRequest(
                endpoint,
                {
                    headers: {
                        Accept:
                            'application/pdf',
                    },
                }
            );

        if (! response.ok) {
            throw new Error(
                'Unable to open Security Deposit voucher.'
            );
        }

        const blob =
            await response.blob();

        const url =
            URL.createObjectURL(
                blob
            );

        /*
         * Navigate the already-opened tab to the authenticated PDF blob.
         */
        viewer.location.href =
            url;

        /*
         * The browser no longer needs the temporary object URL once the PDF
         * viewer has had sufficient time to load it.
         */
        window.setTimeout(
            () => {
                URL.revokeObjectURL(
                    url
                );
            },
            60000
        );
    } catch (error) {
        viewer.close();

        showSecurityDepositError(
            error instanceof Error
                ? error.message
                : 'Unable to open Security Deposit voucher.'
        );
    } finally {
        button.disabled =
            false;

        button.textContent =
            originalLabel
            || 'Download Voucher';
    }
}



/**
 * Persist one itemized deduction and refresh the server-calculated preview.
 */
async function submitSecurityDepositDeduction(
    event
) {
    event.preventDefault();

    if (! securityDepositLeaseId) {
        return;
    }

    const button =
        document.getElementById(
            'security-deduction-submit'
        );

    try {
        hideSecurityDepositError();

        if (button) {
            button.disabled =
                true;

            button.textContent =
                'Adding…';
        }

        const response =
            await apiRequest(
                `/api/leases/${securityDepositLeaseId}/security-deposit/deductions`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            description:
                                formValue(
                                    'security-deduction-description'
                                ),

                            amount:
                                Number(
                                    formValue(
                                        'security-deduction-amount'
                                    )
                                ),

                            deduction_date:
                                formValue(
                                    'security-deduction-date'
                                ),

                            reference:
                                nullableFormValue(
                                    'security-deduction-reference'
                                ),

                            notes:
                                nullableFormValue(
                                    'security-deduction-notes'
                                ),
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        document
            .getElementById(
                'security-deposit-deduction-form'
            )
            ?.reset();

        await loadSecurityDepositPosition();
    } catch (error) {
        showSecurityDepositError(
            error instanceof Error
                ? error.message
                : 'Unable to add Security Deposit deduction.'
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                'Add Deduction';
        }
    }
}

/**
 * Finalize Security Deposit close-out.
 */
async function submitSecurityDepositSettlement(
    event
) {
    event.preventDefault();

    if (! securityDepositLeaseId) {
        return;
    }

    const confirmed =
        window.confirm(
            'Finalize this Security Deposit settlement?\n\n'
            + 'This action is permanent. No further deductions can be added afterward.'
        );

    if (! confirmed) {
        return;
    }

    const button =
        document.getElementById(
            'security-settlement-submit'
        );

    try {
        hideSecurityDepositError();

        if (button) {
            button.disabled =
                true;

            button.textContent =
                'Finalizing…';
        }

        const response =
            await apiRequest(
                `/api/leases/${securityDepositLeaseId}/security-deposit/settle`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            settlement_date:
                                formValue(
                                    'security-settlement-date'
                                ),

                            notes:
                                nullableFormValue(
                                    'security-settlement-notes'
                                ),
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        await loadSecurityDepositPosition();
    } catch (error) {
        showSecurityDepositError(
            error instanceof Error
                ? error.message
                : 'Unable to finalize Security Deposit.'
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                'Finalize Settlement';
        }
    }
}

/**
 * Display an operational Security Deposit error.
 */
function showSecurityDepositError(
    message
) {
    const box =
        document.getElementById(
            'security-deposit-error'
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
 * Clear an operational Security Deposit error.
 */
function hideSecurityDepositError() {
    const box =
        document.getElementById(
            'security-deposit-error'
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
| Tenant Fund Operations
|--------------------------------------------------------------------------
|
| This workspace exposes actual tenant-held money rather than contractual
| Lease expectations.
|
| All balances and Invoice obligations originate from the backend. The
| browser never performs accounting calculations or mutates balances itself.
|
*/

let tenantFundsLeaseId =
    null;

let tenantFundsLease =
    null;

/**
 * Register Tenant Funds modal controls.
 */
function initializeTenantFundsModal() {
    document
        .getElementById(
            'tenant-funds-modal-close'
        )
        ?.addEventListener(
            'click',
            closeTenantFundsModal
        );

    document
        .getElementById(
            'tenant-funds-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeTenantFundsModal
        );

    document
        .getElementById(
            'tenant-funds-reserve-form'
        )
        ?.addEventListener(
            'submit',
            submitRentReserveConsumption
        );

    document
        .getElementById(
            'tenant-funds-advance-form'
        )
        ?.addEventListener(
            'submit',
            submitConsumableAdvanceConsumption
        );

    document
        .getElementById(
            'tenant-funds-security-manage'
        )
        ?.addEventListener(
            'click',
            () => {
                const leaseId =
                    tenantFundsLeaseId;

                if (! leaseId) {
                    return;
                }

                closeTenantFundsModal();

                openSecurityDepositModal(
                    leaseId
                );
            }
        );
}

/**
 * Open the actual tenant-held fund position for one Lease.
 */
async function openTenantFundsModal(
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

    tenantFundsLeaseId =
        numericLeaseId;

    tenantFundsLease =
        null;

    hideTenantFundsError();

    document
        .getElementById(
            'tenant-funds-loading'
        )
        ?.classList.remove(
            'hidden'
        );

    document
        .getElementById(
            'tenant-funds-content'
        )
        ?.classList.add(
            'hidden'
        );

    const lease =
        loadedLeasesById.get(
            String(
                numericLeaseId
            )
        );

    setText(
        'tenant-funds-modal-description',
        [
            lease?.unit?.building?.name,
            lease?.unit?.name,
            partyDisplayName(
                lease?.tenant
            ),
        ]
            .filter(Boolean)
            .join(' · ')
            || 'Review actual tenant-held funds.'
    );

    const modal =
        document.getElementById(
            'tenant-funds-modal'
        );

    modal?.classList.remove(
        'hidden'
    );

    modal?.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    await loadTenantFundsLease();
}

/**
 * Reload authoritative Lease financial information.
 */
async function loadTenantFundsLease() {
    if (! tenantFundsLeaseId) {
        return;
    }

    try {
        hideTenantFundsError();

        const response =
            await apiRequest(
                `/api/leases/${tenantFundsLeaseId}`
            );

        tenantFundsLease =
            await parseJsonResponse(
                response
            );

        renderTenantFunds();
    } catch (error) {
        showTenantFundsError(
            error instanceof Error
                ? error.message
                : 'Unable to load Tenant Funds.'
        );
    } finally {
        document
            .getElementById(
                'tenant-funds-loading'
            )
            ?.classList.add(
                'hidden'
            );

        document
            .getElementById(
                'tenant-funds-content'
            )
            ?.classList.remove(
                'hidden'
            );
    }
}

/**
 * Render server-calculated balances and outstanding Invoices.
 */
function renderTenantFunds() {
    const accounts =
        Array.isArray(
            tenantFundsLease
                ?.tenant_fund_accounts
        )
            ? tenantFundsLease
                .tenant_fund_accounts
            : [];

    const reserve =
        accounts.find(
            (account) =>
                account.type
                === 'rent_reserve'
        )
        ?? null;

    const advance =
        accounts.find(
            (account) =>
                account.type
                === 'consumable_advance'
        )
        ?? null;

    const securityDeposit =
        accounts.find(
            (account) =>
                account.type
                === 'security_deposit'
        )
        ?? null;

    setText(
        'tenant-funds-reserve-balance',
        formatCurrency(
            Number(
                reserve?.balance
                ?? 0
            )
        )
    );

    setText(
        'tenant-funds-advance-balance',
        formatCurrency(
            Number(
                advance?.balance
                ?? 0
            )
        )
    );

    setText(
        'tenant-funds-security-balance',
        formatCurrency(
            Number(
                securityDeposit?.balance
                ?? 0
            )
        )
    );

    renderTenantFundInvoiceSelect(
        'tenant-funds-reserve-invoice'
    );

    renderTenantFundInvoiceSelect(
        'tenant-funds-advance-invoice'
    );

    configureRentReserveOperation(
        reserve
    );

    configureConsumableAdvanceOperation(
        advance
    );

    const today =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );

    setFormValue(
        'tenant-funds-reserve-date',
        today
    );

    setFormValue(
        'tenant-funds-advance-date',
        today
    );
}

/**
 * Populate an Invoice selector from authoritative outstanding balances.
 */
function renderTenantFundInvoiceSelect(
    id
) {
    const select =
        document.getElementById(
            id
        );

    if (! select) {
        return;
    }

    const invoices =
        Array.isArray(
            tenantFundsLease
                ?.invoices
        )
            ? tenantFundsLease
                .invoices
            : [];

    const outstanding =
        invoices.filter(
            (invoice) =>
                Number(
                    invoice.outstanding_amount
                    ?? 0
                ) > 0
        );

    if (outstanding.length === 0) {
        select.innerHTML = `
            <option value="">
                No outstanding Invoice
            </option>
        `;

        select.disabled =
            true;

        return;
    }

    select.disabled =
        false;

    select.innerHTML = `
        <option value="">
            Select Invoice…
        </option>

        ${outstanding
            .map(
                (invoice) => `
                    <option
                        value="${escapeHtml(
                            invoice.id
                        )}"
                    >
                        ${escapeHtml(
                            invoice.invoice_number
                            || `Invoice #${invoice.id}`
                        )}
                        ·
                        ${escapeHtml(
                            formatCurrency(
                                Number(
                                    invoice.outstanding_amount
                                    ?? 0
                                )
                            )
                        )}
                        outstanding
                    </option>
                `
            )
            .join('')}
    `;
}

/**
 * Determine whether Rent Reserve consumption can be offered.
 */
function configureRentReserveOperation(
    account
) {
    const form =
        document.getElementById(
            'tenant-funds-reserve-form'
        );

    const unavailable =
        document.getElementById(
            'tenant-funds-reserve-unavailable'
        );

    const balance =
        Number(
            account?.balance
            ?? 0
        );

    const noticeStarted =
        Boolean(
            tenantFundsLease
                ?.termination_notice_date
        );

    form?.classList.remove(
        'hidden'
    );

    unavailable?.classList.add(
        'hidden'
    );

    if (! account || balance <= 0) {
        form?.classList.add(
            'hidden'
        );

        if (unavailable) {
            unavailable.textContent =
                'No Rent Reserve balance is currently available.';

            unavailable.classList.remove(
                'hidden'
            );
        }

        return;
    }

    if (! noticeStarted) {
        form?.classList.add(
            'hidden'
        );

        if (unavailable) {
            unavailable.textContent =
                'Rent Reserve remains protected until termination notice has been recorded.';

            unavailable.classList.remove(
                'hidden'
            );
        }

        return;
    }

    setText(
        'tenant-funds-reserve-help',
        'Termination notice has been recorded. Available Reserve may now be applied to outstanding rent.'
    );
}

/**
 * Determine whether Consumable Advance consumption can be offered.
 */
function configureConsumableAdvanceOperation(
    account
) {
    const form =
        document.getElementById(
            'tenant-funds-advance-form'
        );

    const unavailable =
        document.getElementById(
            'tenant-funds-advance-unavailable'
        );

    const balance =
        Number(
            account?.balance
            ?? 0
        );

    form?.classList.remove(
        'hidden'
    );

    unavailable?.classList.add(
        'hidden'
    );

    if (! account || balance <= 0) {
        form?.classList.add(
            'hidden'
        );

        if (unavailable) {
            unavailable.textContent =
                'No Consumable Advance balance is currently available.';

            unavailable.classList.remove(
                'hidden'
            );
        }
    }
}

/**
 * Apply Rent Reserve against an outstanding Invoice.
 */
async function submitRentReserveConsumption(
    event
) {
    event.preventDefault();

    const account =
        tenantFundAccountByType(
            'rent_reserve'
        );

    if (! account) {
        return;
    }

    const button =
        document.getElementById(
            'tenant-funds-reserve-submit'
        );

    try {
        hideTenantFundsError();

        if (button) {
            button.disabled =
                true;

            button.textContent =
                'Applying…';
        }

        const response =
            await apiRequest(
                `/api/tenant-funds/${account.id}/consume-rent`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            invoice_id:
                                Number(
                                    formValue(
                                        'tenant-funds-reserve-invoice'
                                    )
                                ),

                            amount:
                                Number(
                                    formValue(
                                        'tenant-funds-reserve-amount'
                                    )
                                ),

                            transaction_date:
                                formValue(
                                    'tenant-funds-reserve-date'
                                ),
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        document
            .getElementById(
                'tenant-funds-reserve-form'
            )
            ?.reset();

        await loadTenantFundsLease();
    } catch (error) {
        showTenantFundsError(
            error instanceof Error
                ? error.message
                : 'Unable to apply Rent Reserve.'
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                'Apply Rent Reserve';
        }
    }
}

/**
 * Apply Consumable Advance against an outstanding Invoice.
 */
async function submitConsumableAdvanceConsumption(
    event
) {
    event.preventDefault();

    const account =
        tenantFundAccountByType(
            'consumable_advance'
        );

    if (! account) {
        return;
    }

    const button =
        document.getElementById(
            'tenant-funds-advance-submit'
        );

    try {
        hideTenantFundsError();

        if (button) {
            button.disabled =
                true;

            button.textContent =
                'Applying…';
        }

        const response =
            await apiRequest(
                `/api/tenant-funds/${account.id}/consume-advance`,
                {
                    method:
                        'POST',

                    body:
                        JSON.stringify({
                            invoice_id:
                                Number(
                                    formValue(
                                        'tenant-funds-advance-invoice'
                                    )
                                ),

                            amount:
                                Number(
                                    formValue(
                                        'tenant-funds-advance-amount'
                                    )
                                ),

                            transaction_date:
                                formValue(
                                    'tenant-funds-advance-date'
                                ),
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        document
            .getElementById(
                'tenant-funds-advance-form'
            )
            ?.reset();

        await loadTenantFundsLease();
    } catch (error) {
        showTenantFundsError(
            error instanceof Error
                ? error.message
                : 'Unable to apply Consumable Advance.'
        );
    } finally {
        if (button) {
            button.disabled =
                false;

            button.textContent =
                'Apply Consumable Advance';
        }
    }
}

/**
 * Find one actual tenant-fund account by type.
 */
function tenantFundAccountByType(
    type
) {
    const accounts =
        Array.isArray(
            tenantFundsLease
                ?.tenant_fund_accounts
        )
            ? tenantFundsLease
                .tenant_fund_accounts
            : [];

    return accounts.find(
        (account) =>
            account.type
            === type
    )
    ?? null;
}

/**
 * Close the Tenant Funds workspace.
 */
function closeTenantFundsModal() {
    const modal =
        document.getElementById(
            'tenant-funds-modal'
        );

    modal?.classList.add(
        'hidden'
    );

    modal?.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    tenantFundsLeaseId =
        null;

    tenantFundsLease =
        null;

    hideTenantFundsError();
}

/**
 * Display Tenant Funds operational failures.
 */
function showTenantFundsError(
    message
) {
    const box =
        document.getElementById(
            'tenant-funds-error'
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
 * Clear Tenant Funds operational failures.
 */
function hideTenantFundsError() {
    const box =
        document.getElementById(
            'tenant-funds-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}
