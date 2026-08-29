/*
|--------------------------------------------------------------------------
| Guided Lease Creation (V1.0.29)
|--------------------------------------------------------------------------
|
| Ten pages that end in ONE request. Nothing is written until the last
| page, so somebody can walk through the whole thing, change their mind
| and leave the registry exactly as they found it.
|
| The browser therefore holds the whole letting in memory (and in
| sessionStorage, so a refresh does not cost the work), and posts it to
| /api/lease-wizard, which creates the property, its owners, the tenant,
| the agent and the lease inside a single transaction.
|
| Server validation is authoritative. Rejections come back keyed by block
| — building.attributes.name, owners.0.attributes.email, lease.rent_amount
| — and this module maps those keys back to the page that owns the field,
| so an operator is returned to the question rather than to a message.
|
*/

import {
    apiRequest,
    escapeHtml,
    parseJsonResponse,
    setButtonBusy,
    restoreButton,
    translate,
} from './core.js';

import {
    phoneFieldMarkup,
    readPhoneValue,
} from './phone-input.js';

import {
    dateForApi,
    dateForDisplay,
    initializeDateInputs,
} from './date-input.js';

/*
|--------------------------------------------------------------------------
| Module State
|--------------------------------------------------------------------------
*/

const TOTAL_STEPS = 10;

const STORAGE_KEY = 'patrimoine.lease_wizard';

/**
 * Every property in the organisation, with its ownership and units.
 *
 * One request feeds pages two and three: the unit list, and whether the
 * owners page is needed at all.
 */
let buildings = [];

/**
 * Parties by role, for the existing-party pickers.
 */
const partyCache = {
    owner: [],
    tenant: [],
    agent: [],
};

let currentStep = 1;

let ownerRowSequence = 0;

/**
 * The saved assistant this page is continuing, if it is continuing one.
 *
 * Saving again overwrites it rather than leaving a second copy behind.
 */
let draftId = null;

/*
|--------------------------------------------------------------------------
| Entry Point
|--------------------------------------------------------------------------
*/

/**
 * Boot the wizard when its page is on screen.
 */
export async function initializeLeaseWizard() {
    const page = document.querySelector(
        '.pm-wizard-page'
    );

    if (! page) {
        return;
    }

    renderPartyFieldBlocks();

    wireControls();

    await loadReferenceData();

    /*
     * Continuing a saved assistant wins over the progress this tab kept:
     * somebody who followed a Continue link means that one.
     */
    const resumed = await resumeDraft();

    if (! resumed) {
        restoreProgress();
    }

    addOwnerRow();

    showStep(currentStep);
}

/*
|--------------------------------------------------------------------------
| Reference Data
|--------------------------------------------------------------------------
*/

/**
 * Load the properties and the parties the pickers offer.
 */
async function loadReferenceData() {
    try {
        const response = await apiRequest(
            '/api/buildings?per_page=100'
        );

        const payload = await parseJsonResponse(
            response
        );

        buildings = payload?.data ?? [];

        populateBuildingOptions();
    } catch {
        showError(
            translate('wizard.load_failed')
        );
    }

    await Promise.all([
        loadParties('owner'),
        loadParties('tenant'),
        loadParties('agent'),
    ]);
}

/**
 * Load one role's parties into its picker.
 *
 * @param {string} role
 */
async function loadParties(role) {
    try {
        const response = await apiRequest(
            `/api/parties?role=${role}&per_page=100`
        );

        const payload = await parseJsonResponse(
            response
        );

        partyCache[role] = payload?.data ?? [];
    } catch {
        partyCache[role] = [];
    }

    populatePartyOptions(role);
}

/**
 * Display name for a party, whichever kind it is.
 *
 * @param {object} party
 * @returns {string}
 */
function partyName(party) {
    return party.name
        || party.legal_name
        || `#${party.id}`;
}

/*
|--------------------------------------------------------------------------
| Pickers
|--------------------------------------------------------------------------
*/

/**
 * Fill the property picker, then its units.
 */
function populateBuildingOptions() {
    const select = document.getElementById(
        'wizard-building-id'
    );

    if (! select) {
        return;
    }

    const previous = select.value;

    select.innerHTML = buildings
        .map(
            (building) => `
                <option value="${building.id}">
                    ${escapeHtml(building.name)}
                </option>
            `
        )
        .join('');

    if (buildings.length === 0) {
        /*
         * A first-time organisation has no property yet, which is
         * precisely who this wizard is for: start them on the new
         * property fields rather than an empty list.
         */
        setValue('wizard-building-mode', 'new');
    } else if (previous) {
        select.value = previous;
    }

    applyBuildingMode();

    populateUnitOptions();
}

/**
 * Fill the unit picker with the chosen property's VACANT units.
 *
 * A unit that already carries an active lease cannot take another, so
 * offering it would only produce a rejection at the end.
 */
function populateUnitOptions() {
    const select = document.getElementById(
        'wizard-unit-id'
    );

    if (! select) {
        return;
    }

    const building = selectedBuilding();

    const units = (building?.units ?? []).filter(
        (unit) => ! unit.is_occupied
    );

    select.innerHTML = units
        .map(
            (unit) => `
                <option value="${unit.id}">
                    ${escapeHtml(unit.name)}
                </option>
            `
        )
        .join('');

    if (units.length === 0) {
        setValue('wizard-unit-mode', 'new');
    }

    applyUnitMode();
}

/**
 * Fill one role's party picker.
 *
 * @param {string} role
 */
function populatePartyOptions(role) {
    const select = document.getElementById(
        `wizard-${role === 'owner' ? 'owner' : role}-id`
    );

    const markup = partyCache[role]
        .map(
            (party) => `
                <option value="${party.id}">
                    ${escapeHtml(partyName(party))}
                </option>
            `
        )
        .join('');

    if (select) {
        select.innerHTML = markup;
    }

    /*
     * A first-time organisation has no parties yet. Offering to choose
     * one produces an empty picker and, worse, a submission with nobody
     * in it, so the choice is withdrawn until there is somebody to pick.
     */
    disableExistingChoice(
        document.getElementById(`wizard-${role}-mode`),
        partyCache[role].length === 0,
        role === 'agent'
            ? 'none'
            : 'new'
    );

    if (role === 'owner') {
        document
            .querySelectorAll('[data-owner-party-select]')
            .forEach(
                (ownerSelect) => {
                    const previous = ownerSelect.value;

                    ownerSelect.innerHTML = markup;

                    if (previous) {
                        ownerSelect.value = previous;
                    }
                }
            );
    }
}

/**
 * Withdraw the "choose an existing one" option while there is nothing to
 * choose, falling back to the mode that can still be completed.
 *
 * @param {HTMLSelectElement|null} select
 * @param {boolean} empty
 * @param {string} fallback
 */
function disableExistingChoice(select, empty, fallback) {
    if (! select) {
        return;
    }

    const option = [...select.options].find(
        (candidate) => candidate.value === 'existing'
    );

    if (! option) {
        return;
    }

    option.disabled = empty;

    if (empty && select.value === 'existing') {
        select.value = fallback;

        select.dispatchEvent(
            new Event('change')
        );
    }
}

/**
 * The property currently chosen on page two, when it is an existing one.
 *
 * @returns {object|null}
 */
function selectedBuilding() {
    if (value('wizard-building-mode') !== 'existing') {
        return null;
    }

    const id = Number(
        value('wizard-building-id')
    );

    return buildings.find(
        (building) => Number(building.id) === id
    ) ?? null;
}

/**
 * Does the chosen property already have its ownership recorded?
 *
 * When it does, the owners page has nothing to ask and is skipped.
 *
 * @returns {boolean}
 */
function ownersPageIsNeeded() {
    const building = selectedBuilding();

    if (building === null) {
        return true;
    }

    return (building.ownerships ?? []).length === 0;
}

/*
|--------------------------------------------------------------------------
| Party Field Blocks
|--------------------------------------------------------------------------
*/

/**
 * The fields for a party nobody has recorded yet.
 *
 * One function serves the owner rows, the tenant page and the agent page,
 * so the three can never ask for different things.
 *
 * @param {string} prefix
 * @returns {string}
 */
function partyFieldsMarkup(prefix) {
    return `
        <div class="pm-wizard-subfields">
            <div>
                <label for="${prefix}-type" class="pm-field-label">
                    ${escapeHtml(translate('wizard.party_type'))}
                </label>

                <select id="${prefix}-type" data-party-type class="pm-input">
                    <option value="person">
                        ${escapeHtml(translate('wizard.person'))}
                    </option>

                    <option value="organisation">
                        ${escapeHtml(translate('wizard.organisation'))}
                    </option>
                </select>
            </div>

            <div data-person-fields class="pm-wizard-grid">
                <div>
                    <label for="${prefix}-given-names" class="pm-field-label">
                        ${escapeHtml(translate('wizard.given_names'))}
                    </label>

                    <input id="${prefix}-given-names" type="text" maxlength="255" class="pm-input">
                </div>

                <div>
                    <label for="${prefix}-surname" class="pm-field-label">
                        ${escapeHtml(translate('wizard.surname'))}
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input id="${prefix}-surname" type="text" maxlength="255" class="pm-input">
                </div>
            </div>

            <div data-organisation-fields class="hidden pm-wizard-grid">
                <div>
                    <label for="${prefix}-legal-name" class="pm-field-label">
                        ${escapeHtml(translate('wizard.legal_name'))}
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input id="${prefix}-legal-name" type="text" maxlength="255" class="pm-input">
                </div>

                <div>
                    <label for="${prefix}-contact-name" class="pm-field-label">
                        ${escapeHtml(translate('wizard.contact_name'))}
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input id="${prefix}-contact-name" type="text" maxlength="255" class="pm-input">
                </div>
            </div>

            <div class="pm-wizard-grid">
                <div>
                    <label for="${prefix}-phone-number" class="pm-field-label">
                        ${escapeHtml(translate('wizard.phone'))}
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    ${phoneFieldMarkup({ id: `${prefix}-phone`, required: true })}
                </div>

                <div>
                    <label for="${prefix}-email" class="pm-field-label">
                        ${escapeHtml(translate('wizard.email'))}
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input id="${prefix}-email" type="email" maxlength="255" class="pm-input">
                </div>
            </div>
        </div>
    `;
}

/**
 * Render the tenant and agent field blocks, and wire their type toggles.
 */
function renderPartyFieldBlocks() {
    ['tenant', 'agent'].forEach(
        (role) => {
            const container = document.getElementById(
                `wizard-${role}-new`
            );

            if (container) {
                container.innerHTML = partyFieldsMarkup(
                    `wizard-${role}-party`
                );

                wirePartyTypeToggle(container);
            }
        }
    );
}

/**
 * A party is a person or an organisation, and they need different names.
 *
 * @param {HTMLElement} container
 */
function wirePartyTypeToggle(container) {
    const type = container.querySelector(
        '[data-party-type]'
    );

    if (! type) {
        return;
    }

    const apply = () => {
        const person = type.value === 'person';

        container
            .querySelector('[data-person-fields]')
            ?.classList
            .toggle('hidden', ! person);

        container
            .querySelector('[data-organisation-fields]')
            ?.classList
            .toggle('hidden', person);
    };

    type.addEventListener('change', apply);

    apply();
}

/**
 * Read a party block into the shape the API expects.
 *
 * @param {HTMLElement} container
 * @param {string} prefix
 * @returns {object}
 */
function readPartyFields(container, prefix) {
    const type = container
        .querySelector('[data-party-type]')
        ?.value
        ?? 'person';

    const telephone =
        readPhoneValue(`${prefix}-phone`);

    const attributes = {
        type,
        phone: telephone.number,
        phone_country: telephone.country,
        email: value(`${prefix}-email`),
    };

    if (type === 'person') {
        attributes.given_names = value(`${prefix}-given-names`);
        attributes.surname = value(`${prefix}-surname`);
    } else {
        attributes.legal_name = value(`${prefix}-legal-name`);
        attributes.contact_person_name = value(`${prefix}-contact-name`);
        attributes.contact_person_phone = telephone.number;
        attributes.contact_person_phone_country = telephone.country;
        attributes.contact_person_email = value(`${prefix}-email`);
    }

    return attributes;
}

/*
|--------------------------------------------------------------------------
| Owner Rows
|--------------------------------------------------------------------------
*/

/**
 * Add one owner row. A property can be owned by several people, and the
 * shares must add up to the whole.
 */
function addOwnerRow() {
    const container = document.getElementById(
        'wizard-owner-rows'
    );

    if (! container || container.children.length > 0) {
        return;
    }

    appendOwnerRow();
}

/**
 * Append a further owner row.
 */
function appendOwnerRow() {
    const container = document.getElementById(
        'wizard-owner-rows'
    );

    if (! container) {
        return;
    }

    const index = ownerRowSequence++;

    const prefix = `wizard-owner-${index}`;

    const row = document.createElement('div');

    row.className = 'pm-wizard-owner-row';

    row.dataset.ownerRow = String(index);

    row.innerHTML = `
        <div class="pm-wizard-grid">
            <div>
                <label for="${prefix}-mode" class="pm-field-label">
                    ${escapeHtml(translate('wizard.owner'))}
                </label>

                <select id="${prefix}-mode" data-owner-mode class="pm-input">
                    <option value="existing">
                        ${escapeHtml(translate('wizard.use_existing_party'))}
                    </option>

                    <option value="new">
                        ${escapeHtml(translate('wizard.add_new_party'))}
                    </option>
                </select>
            </div>

            <div>
                <label for="${prefix}-share" class="pm-field-label">
                    ${escapeHtml(translate('wizard.share'))}
                </label>

                <input
                    id="${prefix}-share"
                    data-owner-share
                    type="text"
                    inputmode="decimal"
                    class="pm-input"
                    value="100"
                >
            </div>
        </div>

        <div data-owner-existing>
            <label for="${prefix}-id" class="pm-field-label">
                ${escapeHtml(translate('wizard.choose_owner'))}
            </label>

            <select id="${prefix}-id" data-owner-party-select class="pm-input"></select>
        </div>

        <div data-owner-new class="hidden">
            ${partyFieldsMarkup(`${prefix}-party`)}
        </div>

        <div class="flex justify-end">
            <button
                type="button"
                data-remove-owner
                class="pm-wizard-remove"
            >
                ${escapeHtml(translate('wizard.remove'))}
            </button>
        </div>
    `;

    container.appendChild(row);

    wirePartyTypeToggle(
        row.querySelector('[data-owner-new]')
    );

    const mode = row.querySelector('[data-owner-mode]');

    const applyMode = () => {
        const existing = mode.value === 'existing';

        row.querySelector('[data-owner-existing]')
            ?.classList
            .toggle('hidden', ! existing);

        row.querySelector('[data-owner-new]')
            ?.classList
            .toggle('hidden', existing);
    };

    mode.addEventListener('change', applyMode);

    row.querySelector('[data-remove-owner]')
        ?.addEventListener(
            'click',
            () => {
                if (container.children.length === 1) {
                    return;
                }

                row.remove();

                updateOwnerTotal();
            }
        );

    row.querySelector('[data-owner-share]')
        ?.addEventListener('input', updateOwnerTotal);

    populatePartyOptions('owner');

    disableExistingChoice(
        mode,
        partyCache.owner.length === 0,
        'new'
    );

    applyMode();

    updateOwnerTotal();
}

/**
 * Show the running total so the 100% rule is visible before submission.
 */
function updateOwnerTotal() {
    const total = ownerShares().reduce(
        (sum, share) => sum + share,
        0
    );

    const label = document.getElementById(
        'wizard-owner-total'
    );

    if (label) {
        label.textContent = translate(
            'wizard.owner_total',
            {
                total: String(
                    Math.round(total * 100) / 100
                ),
            }
        );
    }
}

/**
 * @returns {Array<number>}
 */
function ownerShares() {
    return [...document.querySelectorAll('[data-owner-share]')]
        .map(
            (input) => Number(
                String(input.value).replace(',', '.')
            ) || 0
        );
}

/**
 * Read every owner row.
 *
 * @returns {Array<object>}
 */
function readOwners() {
    return [...document.querySelectorAll('[data-owner-row]')]
        .map(
            (row) => {
                const index = row.dataset.ownerRow;

                const prefix = `wizard-owner-${index}`;

                const share = Number(
                    String(
                        row.querySelector('[data-owner-share]')?.value ?? ''
                    ).replace(',', '.')
                ) || 0;

                if (
                    row.querySelector('[data-owner-mode]')?.value
                    === 'existing'
                ) {
                    return {
                        id: Number(
                            row.querySelector('[data-owner-party-select]')?.value
                        ) || null,
                        ownership_percentage: share,
                    };
                }

                return {
                    attributes: readPartyFields(
                        row.querySelector('[data-owner-new]'),
                        `${prefix}-party`
                    ),
                    ownership_percentage: share,
                };
            }
        );
}

/*
|--------------------------------------------------------------------------
| Steps
|--------------------------------------------------------------------------
*/

/**
 * Reveal one step and describe where the operator is.
 *
 * @param {number} step
 */
function showStep(step) {
    currentStep = step;

    document
        .querySelectorAll('[data-wizard-step]')
        .forEach(
            (section) => {
                section.classList.toggle(
                    'hidden',
                    Number(section.dataset.wizardStep) !== step
                );
            }
        );

    setText(
        'wizard-step-title',
        translate(`wizard.step${step}_title`)
    );

    setText(
        'wizard-step-counter',
        translate(
            'wizard.step_counter',
            {
                current: String(step),
                total: String(TOTAL_STEPS),
            }
        )
    );

    const bar = document.getElementById(
        'wizard-progress-bar'
    );

    if (bar) {
        bar.style.width = `${(step / TOTAL_STEPS) * 100}%`;
    }

    toggle('wizard-back', step > 1);

    toggle('wizard-next', step < TOTAL_STEPS);

    toggle('wizard-submit', step === TOTAL_STEPS);

    /*
     * The last page offers the letting, not a draft. Everywhere before it
     * the draft is the one thing worth writing, so it is the button the
     * eye lands on.
     */
    toggle('wizard-draft', step < TOTAL_STEPS);

    if (step === 9) {
        setText(
            'wizard-commission-echo',
            value('wizard-agent-commission') || '0'
        );
    }

    if (step === TOTAL_STEPS) {
        renderSummary();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * The next step, skipping the owners page when the property already has
 * its ownership recorded.
 *
 * @param {number} from
 * @param {number} direction
 * @returns {number}
 */
function nextStep(from, direction) {
    let step = from + direction;

    if (step === 3 && ! ownersPageIsNeeded()) {
        step += direction;
    }

    return Math.min(
        Math.max(step, 1),
        TOTAL_STEPS
    );
}

/*
|--------------------------------------------------------------------------
| Reading the Pages
|--------------------------------------------------------------------------
*/

/**
 * Assemble everything the wizard collected.
 *
 * @param {string} status
 * @returns {object}
 */
function buildPayload(status) {
    const payload = {
        building: value('wizard-building-mode') === 'existing'
            ? { id: Number(value('wizard-building-id')) || null }
            : {
                attributes: {
                    name: value('wizard-building-name'),
                    address: value('wizard-building-address') || null,
                },
            },

        unit: value('wizard-unit-mode') === 'existing'
            ? { id: Number(value('wizard-unit-id')) || null }
            : {
                attributes: {
                    name: value('wizard-unit-name'),
                    is_commercial: checked('wizard-unit-commercial'),
                },
            },

        tenant: value('wizard-tenant-mode') === 'existing'
            ? { id: Number(value('wizard-tenant-id')) || null }
            : {
                attributes: readPartyFields(
                    document.getElementById('wizard-tenant-new'),
                    'wizard-tenant-party'
                ),
            },

        lease: readLeaseTerms(status),
    };

    if (ownersPageIsNeeded()) {
        payload.owners = readOwners();
    }

    const agentMode = value('wizard-agent-mode');

    if (agentMode === 'existing') {
        payload.agent = {
            id: Number(value('wizard-agent-id')) || null,
        };
    } else if (agentMode === 'new') {
        payload.agent = {
            attributes: readPartyFields(
                document.getElementById('wizard-agent-new'),
                'wizard-agent-party'
            ),
        };
    }

    return payload;
}

/**
 * The lease terms from pages six to nine.
 *
 * @param {string} status
 * @returns {object}
 */
function readLeaseTerms(status) {
    const advanceReceived = checked(
        'wizard-advance-received'
    );

    const terms = {
        status,
        start_date: dateValue('wizard-start-date'),
        end_date: value('wizard-duration') === 'open'
            ? null
            : (dateValue('wizard-end-date') || null),

        termination_notice_date: dateValue('wizard-notice-date') || null,

        rent_amount: integer('wizard-rent-amount'),
        payment_frequency: value('wizard-frequency'),
        due_day: value('wizard-due-day')
            ? Number(value('wizard-due-day'))
            : null,
        proration_amount: value('wizard-proration')
            ? integer('wizard-proration')
            : null,

        security_deposit_amount: integer('wizard-deposit'),
        rent_reserve_amount: integer('wizard-reserve'),

        advance_payment_amount: integer('wizard-advance-amount'),
        advance_received: advanceReceived,

        rent_increment_type: value('wizard-increment-type'),
        rent_increment_value: value('wizard-increment-type') === 'none'
            ? 0
            : decimal('wizard-increment-value'),
        next_rent_increment_date: value('wizard-increment-type') === 'none'
            ? null
            : (dateValue('wizard-increment-date') || null),

        vat_rate: decimal('wizard-vat-rate'),
        management_fee_type: value('wizard-fee-type'),
        management_fee_value: value('wizard-fee-type') === 'none'
            ? 0
            : decimal('wizard-fee-value'),

        agent_commission_amount: value('wizard-agent-mode') === 'none'
            ? 0
            : integer('wizard-agent-commission'),
    };

    if (advanceReceived) {
        terms.advance_received_date = dateValue('wizard-advance-date') || null;
        terms.advance_received_method = value('wizard-advance-method');

        const reference = value('wizard-advance-reference');

        if (reference) {
            terms.advance_received_reference = reference;
        }
    }

    return terms;
}

/*
|--------------------------------------------------------------------------
| Review
|--------------------------------------------------------------------------
*/

/**
 * Show what is about to be created, in the operator's own words.
 */
function renderSummary() {
    const summary = document.getElementById(
        'wizard-summary'
    );

    if (! summary) {
        return;
    }

    const building = selectedBuilding();

    const rows = [
        [
            translate('wizard.property'),
            building
                ? building.name
                : value('wizard-building-name'),
        ],
        [
            translate('wizard.unit'),
            value('wizard-unit-mode') === 'existing'
                ? selectedText('wizard-unit-id')
                : value('wizard-unit-name'),
        ],
        [
            translate('wizard.tenant'),
            value('wizard-tenant-mode') === 'existing'
                ? selectedText('wizard-tenant-id')
                : summaryPartyName('wizard-tenant-party'),
        ],
        [
            translate('wizard.agent'),
            agentSummary(),
        ],
        [
            translate('wizard.start_date'),
            value('wizard-start-date'),
        ],
        [
            translate('wizard.end_date'),
            value('wizard-duration') === 'open'
                ? translate('wizard.duration_open')
                : value('wizard-end-date'),
        ],
        [
            translate('wizard.rent_amount'),
            `${value('wizard-rent-amount')} · ${selectedText('wizard-frequency')}`,
        ],
        [
            translate('wizard.security_deposit'),
            value('wizard-deposit'),
        ],
        [
            translate('wizard.advance_amount'),
            checked('wizard-advance-received')
                ? `${value('wizard-advance-amount')} · ${translate('wizard.advance_received')}`
                : value('wizard-advance-amount'),
        ],
        [
            translate('wizard.fee_type'),
            value('wizard-fee-type') === 'none'
                ? translate('wizard.fee_none')
                : `${selectedText('wizard-fee-type')} · ${value('wizard-fee-value')}`,
        ],
    ];

    summary.innerHTML = rows
        .map(
            ([term, description]) => `
                <div>
                    <dt>${escapeHtml(term)}</dt>
                    <dd>${escapeHtml(description || '—')}</dd>
                </div>
            `
        )
        .join('');
}

/**
 * @param {string} prefix
 * @returns {string}
 */
function summaryPartyName(prefix) {
    const legal = value(`${prefix}-legal-name`);

    if (legal) {
        return legal;
    }

    return [
        value(`${prefix}-given-names`),
        value(`${prefix}-surname`),
    ]
        .filter(Boolean)
        .join(' ');
}

/**
 * @returns {string}
 */
function agentSummary() {
    const mode = value('wizard-agent-mode');

    if (mode === 'none') {
        return translate('wizard.no_agent');
    }

    const name = mode === 'existing'
        ? selectedText('wizard-agent-id')
        : summaryPartyName('wizard-agent-party');

    const commission = value('wizard-agent-commission');

    return [
        name,
        commission && commission !== '0'
            ? commission
            : null,
    ]
        .filter(Boolean)
        .join(' · ');
}

/*
|--------------------------------------------------------------------------
| Submission
|--------------------------------------------------------------------------
*/

/**
 * Create the letting.
 *
 * @param {string} status
 * @param {HTMLElement} button
 */
async function submitWizard(status, button) {
    hideError();

    setButtonBusy(button, 'wizard.saving');

    try {
        const response = await apiRequest(
            '/api/lease-wizard',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(
                    buildPayload(status)
                ),
            }
        );

        /*
         * Read the body directly rather than through parseJsonResponse,
         * which throws on a non-ok response: a rejection carries the
         * field keys this page needs to send the operator back to the
         * question that was wrong.
         */
        if (! response.ok) {
            const payload = await response
                .json()
                .catch(() => ({}));

            if (response.status === 422) {
                reportValidationErrors(payload);
            } else {
                showError(
                    payload?.message
                    ?? translate('wizard.save_failed')
                );
            }

            return;
        }

        clearProgress();

        /*
         * The letting exists now, so the assistant that made it has
         * nothing left to say. A failure here is not worth reporting:
         * the letting is created either way.
         */
        if (draftId !== null) {
            try {
                await apiRequest(
                    `/api/lease-wizard/drafts/${draftId}`,
                    { method: 'DELETE' }
                );
            } catch {
                /* An orphaned assistant can be discarded by hand. */
            }
        }

        window.location.href = '/leases';
    } catch {
        showError(
            translate('wizard.save_failed')
        );
    } finally {
        restoreButton(button);
    }
}

/**
 * Send the operator back to the page that owns the rejected field.
 *
 * @param {object} payload
 */
function reportValidationErrors(payload) {
    const errors = payload?.errors ?? {};

    const keys = Object.keys(errors);

    const messages = keys
        .map((key) => errors[key][0])
        .filter(Boolean);

    showError(
        messages.length > 0
            ? messages.join(' ')
            : (payload?.message ?? translate('wizard.save_failed'))
    );

    const steps = keys
        .map(stepForErrorKey)
        .filter((step) => step !== null);

    if (steps.length > 0) {
        showStep(Math.min(...steps));
    }
}

/**
 * Which page owns a rejected field.
 *
 * @param {string} key
 * @returns {number|null}
 */
function stepForErrorKey(key) {
    if (key.startsWith('building') || key.startsWith('unit')) {
        return 2;
    }

    if (key.startsWith('owners')) {
        return 3;
    }

    if (key.startsWith('tenant')) {
        return 4;
    }

    if (key.startsWith('agent')) {
        return 5;
    }

    if (! key.startsWith('lease.')) {
        return null;
    }

    const field = key.slice('lease.'.length);

    if (['start_date', 'end_date'].includes(field)) {
        return 6;
    }

    if (
        field === 'termination_notice_date'
        || field.startsWith('rent_increment')
        || field === 'next_rent_increment_date'
    ) {
        return 7;
    }

    if (
        [
            'rent_amount',
            'payment_frequency',
            'due_day',
            'proration_amount',
            'security_deposit_amount',
            'rent_reserve_amount',
            'advance_payment_amount',
        ].includes(field)
        || field.startsWith('advance_received')
    ) {
        return 8;
    }

    if (
        field.startsWith('management_fee')
        || field === 'vat_rate'
        || field === 'agent_commission_amount'
    ) {
        return 9;
    }

    /*
     * unit_id, tenant_id and status are decided by the pages above but
     * are only rejected once everything is assembled, so the review page
     * is where the explanation belongs.
     */
    return 10;
}

/*
|--------------------------------------------------------------------------
| Progress Across Reloads
|--------------------------------------------------------------------------
*/

/**
 * Keep what has been typed so a refresh does not cost the work. It is
 * per-tab only: this is convenience, not a saved draft.
 */
function saveProgress() {
    try {
        sessionStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                step: currentStep,
                fields: collectFieldValues(),
            })
        );
    } catch {
        /* A private window without storage is not a reason to stop. */
    }
}

/**
 * @returns {object}
 */
function collectFieldValues() {
    const fields = {};

    document
        .querySelectorAll('.pm-wizard-page input, .pm-wizard-page select')
        .forEach(
            (element) => {
                if (! element.id) {
                    return;
                }

                fields[element.id] = element.type === 'checkbox'
                    ? element.checked
                    : element.value;
            }
        );

    return fields;
}

/**
 * Put back what was typed before the reload.
 */
function restoreProgress() {
    let stored = null;

    try {
        stored = JSON.parse(
            sessionStorage.getItem(STORAGE_KEY) ?? 'null'
        );
    } catch {
        stored = null;
    }

    if (! stored) {
        return;
    }

    applyStoredState(stored);
}

/**
 * Put a saved set of answers back on the page.
 *
 * Used by both the progress the browser keeps while the tab is open and
 * the assistant saved on the server.
 */
function applyStoredState(stored) {
    /*
     * Owner rows are built as they are needed; the values below have
     * nowhere to go until they exist.
     */
    const rows = Number(stored.owner_rows) || 0;

    const container = document.getElementById('wizard-owner-rows');

    while (container && container.children.length < rows) {
        appendOwnerRow();
    }

    Object.entries(stored.fields ?? {}).forEach(
        ([id, storedValue]) => {
            const element = document.getElementById(id);

            if (! element) {
                return;
            }

            if (element.type === 'checkbox') {
                element.checked = Boolean(storedValue);
            } else {
                element.value = storedValue;
            }
        }
    );

    currentStep = Number(stored.step) || 1;

    applyBuildingMode();

    applyUnitMode();

    applyTenantMode();

    applyAgentMode();

    applyIncrementType();

    applyAdvanceReceived();
}

/**
 * Forget the draft once the letting exists.
 */
function clearProgress() {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
    } catch {
        /* Nothing to clear. */
    }
}

/*
|--------------------------------------------------------------------------
| Conditional Fields
|--------------------------------------------------------------------------
*/

function applyBuildingMode() {
    const existing = value('wizard-building-mode') === 'existing';

    toggle('wizard-building-existing', existing);

    toggle('wizard-building-new', ! existing);
}

function applyUnitMode() {
    const existing = value('wizard-unit-mode') === 'existing';

    toggle('wizard-unit-existing', existing);

    toggle('wizard-unit-new', ! existing);
}

function applyTenantMode() {
    const existing = value('wizard-tenant-mode') === 'existing';

    toggle('wizard-tenant-existing', existing);

    toggle('wizard-tenant-new', ! existing);
}

function applyAgentMode() {
    const mode = value('wizard-agent-mode');

    toggle('wizard-agent-existing', mode === 'existing');

    toggle('wizard-agent-new', mode === 'new');

    toggle('wizard-agent-commission-field', mode !== 'none');
}

function applyIncrementType() {
    toggle(
        'wizard-increment-details',
        value('wizard-increment-type') !== 'none'
    );
}

function applyAdvanceReceived() {
    toggle(
        'wizard-advance-details',
        checked('wizard-advance-received')
    );
}

/**
 * Duration presets fill the end date; the operator can still edit it.
 */
function applyDuration() {
    const duration = value('wizard-duration');

    toggle(
        'wizard-end-date-field',
        duration !== 'open'
    );

    if (duration === 'open' || duration === 'custom') {
        return;
    }

    const start = dateValue('wizard-start-date');

    if (! start) {
        return;
    }

    const months = Number(duration);

    const date = new Date(`${start}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return;
    }

    date.setMonth(date.getMonth() + months);

    /*
     * A twelve-month lease starting on the first ends on the last day of
     * the twelfth month, not on the first of the thirteenth.
     */
    date.setDate(date.getDate() - 1);

    setDateValue(
        'wizard-end-date',
        date.toISOString().slice(0, 10)
    );
}

/*
|--------------------------------------------------------------------------
| Saving an unfinished assistant
|--------------------------------------------------------------------------
|
| A lease needs a unit and a tenant before it can exist at all, so there
| is no such thing as a lease saved from page three. What is saved is the
| assistant: its field values, however many of them are blank, resumable
| from the Leases page.
|
| The last page is different. There the button offers the letting itself,
| and anybody who would rather keep a draft steps back one page.
|
*/

/**
 * Everything needed to carry on later.
 */
function draftPayload() {
    return {
        step: currentStep,
        fields: collectFieldValues(),

        /*
         * Owner rows are built as they are needed, so restoring has to
         * know how many to build before the values can go back in.
         */
        owner_rows: document
            .getElementById('wizard-owner-rows')
            ?.children.length
            ?? 0,
    };
}

async function saveDraft(button) {
    hideError();

    setButtonBusy(button, 'wizard.saving');

    try {
        const response = await apiRequest(
            '/api/lease-wizard/drafts',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: draftId,
                    payload: draftPayload(),
                }),
            }
        );

        const payload = await parseJsonResponse(response);

        draftId = payload?.draft?.id ?? draftId;

        clearProgress();

        window.location.href = '/leases?drafts=1';
    } catch (error) {
        showError(
            error instanceof Error
                ? error.message
                : translate('wizard.save_failed')
        );
    } finally {
        restoreButton(button);
    }
}

/**
 * Pick up a saved assistant, when the page was opened to continue one.
 */
async function resumeDraft() {
    const requested = new URLSearchParams(window.location.search).get('draft');

    if (! requested) {
        return false;
    }

    try {
        const payload = await parseJsonResponse(
            await apiRequest(`/api/lease-wizard/drafts/${requested}`)
        );

        draftId = payload.id;

        applyStoredState(payload.payload ?? {});

        return true;
    } catch (error) {
        showError(
            error instanceof Error
                ? error.message
                : translate('wizard.draft_missing')
        );

        return false;
    }
}

/*
|--------------------------------------------------------------------------
| A card that does not jump
|--------------------------------------------------------------------------
|
| Ten pages of different lengths inside one card meant the buttons moved
| every time somebody pressed Next. The card is held at the height of its
| tallest page instead, measured by showing each one in turn where it
| cannot be seen.
|
| A minimum, not a fixed height: the review page grows with what was
| filled in, and a page that outgrows the measurement should be readable
| rather than clipped.
|
*/

function holdCardHeight() {
    const card = document.getElementById('wizard-steps');

    if (! card) {
        return;
    }

    const steps = [...document.querySelectorAll('[data-wizard-step]')];

    if (steps.length === 0) {
        return;
    }

    const shown = steps.filter(
        (step) => ! step.classList.contains('hidden')
    );

    card.style.minHeight = '';

    let tallest = 0;

    for (const step of steps) {
        const hidden = step.classList.contains('hidden');

        if (hidden) {
            /*
             * Laid out, so it has a height, but not painted and not in
             * the flow, so nothing on screen moves while it is measured.
             */
            step.classList.remove('hidden');

            step.style.position = 'absolute';
            step.style.visibility = 'hidden';
            step.style.width = `${card.clientWidth - 48}px`;
        }

        tallest = Math.max(tallest, step.offsetHeight);

        if (hidden) {
            step.style.position = '';
            step.style.visibility = '';
            step.style.width = '';

            step.classList.add('hidden');
        }
    }

    for (const step of shown) {
        step.classList.remove('hidden');
    }

    if (tallest > 0) {
        card.style.minHeight = `${tallest}px`;
    }
}

/*
|--------------------------------------------------------------------------
| Wiring
|--------------------------------------------------------------------------
*/

/**
 * Wire every control on the page.
 */
function wireControls() {
    document
        .getElementById('wizard-next')
        ?.addEventListener(
            'click',
            () => {
                showStep(nextStep(currentStep, 1));

                saveProgress();
            }
        );

    document
        .getElementById('wizard-back')
        ?.addEventListener(
            'click',
            () => {
                showStep(nextStep(currentStep, -1));

                saveProgress();
            }
        );

    document
        .getElementById('wizard-submit')
        ?.addEventListener(
            'click',
            (event) => submitWizard(
                'active',
                event.currentTarget
            )
        );

    document
        .getElementById('wizard-draft')
        ?.addEventListener(
            'click',
            (event) => saveDraft(event.currentTarget)
        );

    document
        .getElementById('wizard-add-owner')
        ?.addEventListener('click', appendOwnerRow);

    on('wizard-building-mode', 'change', () => {
        applyBuildingMode();

        populateUnitOptions();
    });

    on('wizard-building-id', 'change', populateUnitOptions);

    on('wizard-unit-mode', 'change', applyUnitMode);

    on('wizard-tenant-mode', 'change', applyTenantMode);

    on('wizard-agent-mode', 'change', applyAgentMode);

    on('wizard-increment-type', 'change', applyIncrementType);

    on('wizard-advance-received', 'change', applyAdvanceReceived);

    on('wizard-duration', 'change', applyDuration);

    /*
     * Business dates are typed in the organisation's format and get the
     * shared Patrimoine calendar, like every other date in the
     * application. A native picker would show the browser's format.
     */
    initializeDateInputs();

    /*
     * After the first render, so every page is measured with its real
     * content, and again whenever the column width changes.
     */
    requestAnimationFrame(holdCardHeight);

    setTimeout(holdCardHeight, 200);

    let remeasure = null;

    window.addEventListener(
        'resize',
        () => {
            clearTimeout(remeasure);

            remeasure = setTimeout(holdCardHeight, 150);
        }
    );

    on('wizard-start-date', 'change', applyDuration);

    document
        .querySelector('.pm-wizard-page')
        ?.addEventListener('change', saveProgress);
}

/*
|--------------------------------------------------------------------------
| Small Helpers
|--------------------------------------------------------------------------
*/

function on(id, event, handler) {
    document
        .getElementById(id)
        ?.addEventListener(event, handler);
}

function value(id) {
    return document.getElementById(id)?.value ?? '';
}

/**
 * A date field, as the API wants it.
 *
 * Business dates are typed and shown in the organisation's format —
 * DD-MM-YYYY in French, DD/MM/YYYY in English — and travel as ISO.
 */
function dateValue(id) {
    return dateForApi(
        value(id)
    );
}

/**
 * Put an ISO date into a date field, in the format on screen.
 */
function setDateValue(id, iso) {
    setValue(
        id,
        dateForDisplay(iso)
    );
}

function selectedText(id) {
    const element = document.getElementById(id);

    return element?.selectedOptions?.[0]?.textContent?.trim() ?? '';
}

function checked(id) {
    return Boolean(
        document.getElementById(id)?.checked
    );
}

function integer(id) {
    return Number(
        String(value(id)).replace(/[^\d-]/g, '')
    ) || 0;
}

function decimal(id) {
    return Number(
        String(value(id)).replace(',', '.')
    ) || 0;
}

function setValue(id, newValue) {
    const element = document.getElementById(id);

    if (element) {
        element.value = newValue;
    }
}

function setText(id, text) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = text;
    }
}

function toggle(id, visible) {
    document
        .getElementById(id)
        ?.classList
        .toggle('hidden', ! visible);
}

function showError(message) {
    const box = document.getElementById('wizard-error');

    if (box) {
        box.textContent = message;

        box.classList.remove('hidden');
    }
}

function hideError() {
    document
        .getElementById('wizard-error')
        ?.classList
        .add('hidden');
}
