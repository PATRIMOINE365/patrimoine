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
| - Lease contractual terms.
|
| Financial transactions such as payments, invoices, rent reserve
| movements and security-deposit settlement intentionally remain outside
| this module.
|
*/

import {
    apiRequest,
    escapeHtml,
    formValue,
    formatCurrency,
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

    initializeLeaseFieldHelp();

    try {
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
     * Flatten Building -> Unit relationships into one collection while
     * retaining the Building identity for display.
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

    populateUnitSelect();
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

/**
 * Populate Units grouped visually by Building.
 */
function populateUnitSelect() {
    const select =
        document.getElementById(
            'lease-unit'
        );

    if (! select) {
        return;
    }

    const previousValue =
        select.value;

    const groups =
        new Map();

    availableUnits.forEach(
        (unit) => {
            const buildingId =
                String(
                    unit.building?.id
                    ?? ''
                );

            if (! groups.has(buildingId)) {
                groups.set(
                    buildingId,
                    {
                        name:
                            unit.building?.name
                            || 'Property',

                        units:
                            [],
                    }
                );
            }

            groups
                .get(buildingId)
                .units
                .push(unit);
        }
    );

    select.innerHTML = `
        <option value="">
            Select unit…
        </option>

        ${
            Array
                .from(groups.values())
                .map(
                    (group) => `
                        <optgroup
                            label="${escapeHtml(
                                group.name
                            )}"
                        >
                            ${
                                group.units
                                    .map(
                                        (unit) => `
                                            <option
                                                value="${escapeHtml(
                                                    unit.id
                                                )}"
                                            >
                                                ${escapeHtml(
                                                    unit.name
                                                    || `Unit #${unit.id}`
                                                )}
                                            </option>
                                        `
                                    )
                                    .join('')
                            }
                        </optgroup>
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

function formatDate(value) {
    if (! value) {
        return '';
    }

    const date =
        new Date(
            `${String(value).slice(0, 10)}T00:00:00`
        );

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(
            value
        );
    }

    return new Intl.DateTimeFormat(
        'en-GH',
        {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }
    ).format(date);
}

function attachLeaseActionListeners(
    container
) {
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
        '18'
    );

    setFormValue(
        'lease-security-deposit',
        '0'
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

    hideLeaseFormError();
}

function populateLeaseForm(
    lease
) {
    setFormValue(
        'lease-unit',
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
