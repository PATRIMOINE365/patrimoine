/*
|--------------------------------------------------------------------------
| Guided Lease Creation
|--------------------------------------------------------------------------
|
| V1.0.43: the assistant IS the lease drawer, paginated. One page per
| section of the drawer, in the drawer's order and in the drawer's words:
|
|     1  Information        what these words mean
|     2  Property & Tenant   unit, owners, tenant
|     3  Lease Period        start, duration, end, notice
|     4  Rent Terms          rent, frequency, due day, VAT, proration,
|                            deposit and its receipt
|     5  Advance Payment     advance, reserve, consumable, receipt
|     6  Rent Increment      type, value, next date
|     7  Fees & Commission   agent, their commission, management fee,
|                            notes
|     8  Review
|
| The two had drifted into different products asking different questions,
| and the worst of the differences was silent: this page called the rent
| field "Rent" and put "Paid every: Quarter" under it, while the drawer
| called it "Monthly Rent" and the engine has always read it as a month.
| A quarter's rent typed here was billed at three times itself.
|
| Eight pages that end in ONE request. Nothing is written until the last
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
    formatCurrency,
    formatMoneyDigits,
    getPresentationConfiguration,
    messageWithErrorCode,
    parseJsonResponse,
    parseMoneyInput,
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

const TOTAL_STEPS = 8;

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
     * V1.0.45: the VAT the organisation charges on its fee.
     *
     * The retired Add lease drawer read this from the organisation's own
     * settings and the assistant never did - it started every letting at
     * zero. Now that the assistant is the only way in, losing that
     * default would mean every new lease silently charging no VAT until
     * somebody noticed.
     *
     * Applied before anything is restored, so a saved answer still wins.
     */
    applyDefaultVatRate();

    /*
     * Continuing a saved assistant wins over the progress this tab kept:
     * somebody who followed a Continue link means that one.
     */
    const resumed = await resumeDraft();

    if (! resumed) {
        restoreProgress();
    }

    addOwnerRow();

    /*
     * The property list only arrives with the reference data, so whether
     * ownership needs asking for cannot be known until now.
     */
    applyOwnersBlock();

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
 * Mount a party picker for one role.
 *
 * Used by the tenant and the agent, and by every owner row, so the three
 * search the same way and read the same way.
 *
 * @param {string} id
 * @param {HTMLElement} mount
 * @param {string} role
 */
function mountPartyPicker(id, mount, role) {
    mountPicker({
        id,
        mount,
        placeholderKey: `wizard.search_${role}`,
        emptyKey: 'wizard.no_party_found',
        seed: () => partyCache[role] ?? [],
        label: partyName,
        detail: (party) => [party?.phone, party?.email]
            .filter(Boolean)
            .join(' \u00b7 '),
        search: async (term) => {
            const payload = await parseJsonResponse(
                await apiRequest(
                    `/api/parties?role=${encodeURIComponent(role)}`
                    + `&search=${encodeURIComponent(term)}&per_page=15`
                )
            );

            return payload?.data ?? [];
        },
    });
}

/**
 * Start the VAT field at the organisation's own rate.
 */
function applyDefaultVatRate() {
    const rate = Number(
        getPresentationConfiguration()?.default_vat_rate
    );

    if (
        ! Number.isFinite(rate)
        || rate < 0
        || rate > 100
    ) {
        return;
    }

    setValue(
        'wizard-vat-rate',
        String(rate)
    );
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
    const mount = document.getElementById(
        'wizard-building-picker'
    );

    if (! mount) {
        return;
    }

    mountPicker({
        id: 'wizard-building-id',
        mount,
        placeholderKey: 'wizard.search_property',
        emptyKey: 'wizard.no_property_found',
        seed: () => buildings,
        label: (building) => String(building.name ?? ''),
        detail: (building) => String(building.location ?? building.address ?? ''),
        search: async (term) => {
            const payload = await parseJsonResponse(
                await apiRequest(
                    `/api/buildings?search=${encodeURIComponent(term)}&per_page=15`
                )
            );

            const found = payload?.data ?? [];

            /*
             * A property found by searching has to join the cache: the
             * unit picker and the ownership question both read the
             * chosen property's own record out of it, and a property
             * beyond the first hundred was never in there.
             */
            found.forEach(
                (building) => {
                    if (
                        ! buildings.some(
                            (known) => Number(known.id) === Number(building.id)
                        )
                    ) {
                        buildings.push(building);
                    }
                }
            );

            return found;
        },
        onChange: () => {
            /*
             * A different property means different units, and possibly a
             * different answer to whether ownership is known.
             */
            setPickerValue('wizard-unit-id', null, { silent: true });

            populateUnitOptions();

            applyOwnersBlock();
        },
    });

    if (buildings.length === 0) {
        /*
         * A first-time organisation has no property yet, which is
         * precisely who this wizard is for: start them on the new
         * property fields rather than an empty list.
         */
        setValue('wizard-building-mode', 'new');
    }

    applyBuildingMode();

    populateUnitOptions();
}

/**
 * Whether somebody chose the unit question's answer themselves.
 *
 * Only a trusted event sets it, so the automatic tracking above can tell
 * its own writes apart from a person's.
 */
let unitModeChosenByHand = false;

/**
 * Fill the unit picker with the chosen property's VACANT units.
 *
 * A unit that already carries an active lease cannot take another, so
 * offering it would only produce a rejection at the end.
 */
function populateUnitOptions() {
    const mount = document.getElementById(
        'wizard-unit-picker'
    );

    if (! mount) {
        return;
    }

    const vacantUnits = () => {
        const building = selectedBuilding();

        return (building?.units ?? []).filter(
            (unit) => ! unit.is_occupied
        );
    };

    const units = vacantUnits();

    /*
     * The units of one property arrive inside the property itself, so
     * this picker filters what is already here rather than asking the
     * server. It is still a picker: a block of forty flats is no easier
     * to read in a dropdown than four hundred tenants.
     */
    mountPicker({
        id: 'wizard-unit-id',
        mount,
        placeholderKey: 'wizard.search_unit',
        emptyKey: 'wizard.no_unit_found',
        seed: vacantUnits,
        label: (unit) => String(unit.name ?? ''),
        search: async (term) => {
            const needle = term.toLowerCase();

            return vacantUnits().filter(
                (unit) => String(unit.name ?? '')
                    .toLowerCase()
                    .includes(needle)
            );
        },
    });

    /*
     * V1.0.46: the choice follows what the property actually has, in
     * both directions.
     *
     * It only ever went one way before, which did not show while the
     * property was a dropdown - a dropdown selects its first option, so
     * a property was always chosen and its units were always known. A
     * picker starts empty, so the page opened with no property, no
     * vacant units, and the unit question forced to "add a new one" -
     * and choosing a property afterwards never undid it.
     *
     * A deliberate choice is left alone: unitModeChosenByHand is only
     * set by an event the browser itself raised.
     */
    if (units.length === 0) {
        setValue('wizard-unit-mode', 'new');
    } else if (! unitModeChosenByHand) {
        setValue('wizard-unit-mode', 'existing');
    }

    disableExistingChoice(
        document.getElementById('wizard-unit-mode'),
        units.length === 0,
        'new'
    );

    applyUnitMode();
}

/**
 * Fill one role's party picker.
 *
 * @param {string} role
 */
function populatePartyOptions(role) {
    /*
     * The owner rows carry a picker each and mount their own; only the
     * tenant and the agent have one fixed place on the page.
     */
    const mount = document.getElementById(
        `wizard-${role}-picker`
    );

    if (mount) {
        mountPartyPicker(
            `wizard-${role}-id`,
            mount,
            role
        );
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

    /*
     * V1.0.48: the branch that refreshed <select data-owner-party-select>
     * options is gone with the selects themselves — it matched nothing
     * since V1.0.46 and referenced a variable this function never had,
     * which only never crashed because the loop body never ran.
     */
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
 * When it does, there is nothing to ask and the ownership block on the
 * Property & Tenant page stays out of the way.
 *
 * V1.0.43: this used to skip a whole page. Ownership now sits inside the
 * page that names the property, so it is a block that appears rather than
 * a page that is stepped over — which also means the step numbers no
 * longer change depending on what was chosen two answers ago.
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

/**
 * Show the ownership block only when the property needs one.
 */
function applyOwnersBlock() {
    toggle(
        'wizard-owners-block',
        ownersPageIsNeeded()
    );
}

/*
|--------------------------------------------------------------------------
| Searchable Pickers
|--------------------------------------------------------------------------
|
| V1.0.45: the assistant used plain dropdowns while the lease drawer beside
| it had type-to-search boxes. Two problems with that, and the second is
| the serious one.
|
| A dropdown of four hundred tenants is unusable, and the reference lists
| were loaded ONE HUNDRED AT A TIME - so beyond a hundred properties or a
| hundred tenants the assistant could not offer the record at all, and
| nothing on screen said so. A picker that asks the server as you type has
| no such ceiling.
|
| One factory serves the property, the unit, the tenant, the agent and
| every owner row, so the five can never behave differently. Each keeps the
| chosen id in a hidden input carrying the id the select used to carry,
| which is what lets the draft machinery, the review page and the error
| mapping go on reading them exactly as before.
|
*/

/**
 * Every picker on the page, by the id of its hidden value input.
 *
 * @type {Object<string, object>}
 */
const pickers = {};

/**
 * How long to wait after a keystroke before asking the server.
 */
const PICKER_DEBOUNCE = 250;

/**
 * How many suggestions to show before anything has been typed.
 */
const PICKER_SEED_LIMIT = 12;

/**
 * The markup for one picker.
 *
 * @param {string} id
 * @param {string} placeholderKey
 * @returns {string}
 */
function pickerMarkup(id, placeholderKey) {
    const placeholder = escapeHtml(
        translate(placeholderKey)
    );

    return `
        <div class="pm-wizard-picker" data-picker="${escapeHtml(id)}">
            <input id="${escapeHtml(id)}" type="hidden">

            <div class="relative">
                <input
                    id="${escapeHtml(id)}-search"
                    type="search"
                    autocomplete="off"
                    placeholder="${placeholder}"
                    class="pm-input pm-input-search pm-input-search-clearable"
                >

                <button
                    id="${escapeHtml(id)}-clear"
                    type="button"
                    class="pm-wizard-picker-clear"
                    aria-label="${escapeHtml(translate('wizard.clear_choice'))}"
                >&times;</button>
            </div>

            <div
                id="${escapeHtml(id)}-results"
                class="pm-card pm-wizard-picker-results hidden"
            ></div>
        </div>
    `;
}

/**
 * Render a picker into its mount point and wire it up.
 *
 * @param {{
 *     id: string,
 *     mount: string|HTMLElement,
 *     placeholderKey: string,
 *     emptyKey: string,
 *     seed: function(): Array<object>,
 *     search: function(string): Promise<Array<object>>,
 *     label: function(object): string,
 *     detail?: function(object): string,
 *     onChange?: function(object|null): void,
 * }} config
 */
function mountPicker(config) {
    const mount = typeof config.mount === 'string'
        ? document.getElementById(config.mount)
        : config.mount;

    if (! mount) {
        return;
    }

    mount.innerHTML = pickerMarkup(
        config.id,
        config.placeholderKey
    );

    pickers[config.id] = {
        ...config,
        selected: null,
        timer: null,
    };

    const search = document.getElementById(`${config.id}-search`);

    const clear = document.getElementById(`${config.id}-clear`);

    search?.addEventListener('input', () => {
        /*
         * Typing over a chosen name means the reader is looking for
         * somebody else, so the choice is released before the search
         * runs. Leaving it would submit a record nobody can still see.
         */
        if (pickers[config.id].selected) {
            setPickerValue(config.id, null, { keepText: true });
        }

        schedulePickerSearch(config.id, search.value);
    });

    search?.addEventListener('focus', () => {
        if (! pickers[config.id].selected) {
            schedulePickerSearch(config.id, search.value);
        }
    });

    clear?.addEventListener('click', () => {
        setPickerValue(config.id, null);

        search?.focus();

        schedulePickerSearch(config.id, '');
    });

    /*
     * A click anywhere else closes the suggestions. Without this the
     * list stays open over the next question.
     */
    document.addEventListener('click', (event) => {
        if (! mount.contains(event.target)) {
            hidePickerResults(config.id);
        }
    });

    updatePickerClear(config.id);
}

/**
 * Ask for suggestions, once the typing has paused.
 *
 * @param {string} id
 * @param {string} term
 */
function schedulePickerSearch(id, term) {
    const picker = pickers[id];

    if (! picker) {
        return;
    }

    window.clearTimeout(picker.timer);

    const trimmed = String(term ?? '').trim();

    if (trimmed === '') {
        renderPickerResults(
            id,
            picker.seed().slice(0, PICKER_SEED_LIMIT)
        );

        return;
    }

    picker.timer = window.setTimeout(
        async () => {
            try {
                renderPickerResults(
                    id,
                    await picker.search(trimmed)
                );
            } catch {
                /*
                 * A failed suggestion must never block the page: fall
                 * back to what is already loaded.
                 */
                renderPickerResults(
                    id,
                    picker.seed().slice(0, PICKER_SEED_LIMIT)
                );
            }
        },
        PICKER_DEBOUNCE
    );
}

/**
 * Draw one picker's suggestions.
 *
 * @param {string} id
 * @param {Array<object>} rows
 */
function renderPickerResults(id, rows) {
    const picker = pickers[id];

    const results = document.getElementById(`${id}-results`);

    if (! picker || ! results) {
        return;
    }

    if (rows.length === 0) {
        results.innerHTML = `
            <div class="pm-wizard-picker-empty">
                ${escapeHtml(translate(picker.emptyKey))}
            </div>
        `;

        results.classList.remove('hidden');

        return;
    }

    results.innerHTML = rows
        .map(
            (row) => {
                const detail = picker.detail
                    ? picker.detail(row)
                    : '';

                return `
                    <button
                        type="button"
                        data-picker-choice="${escapeHtml(row.id)}"
                        class="pm-wizard-picker-option"
                    >
                        <span class="pm-wizard-picker-option-name">
                            ${escapeHtml(picker.label(row))}
                        </span>

                        ${
                            detail === ''
                                ? ''
                                : `<span class="pm-wizard-picker-option-detail">${escapeHtml(detail)}</span>`
                        }
                    </button>
                `;
            }
        )
        .join('');

    results.classList.remove('hidden');

    results
        .querySelectorAll('[data-picker-choice]')
        .forEach(
            (button) => {
                button.addEventListener('click', () => {
                    const chosen = rows.find(
                        (row) => String(row.id)
                            === String(button.dataset.pickerChoice)
                    );

                    if (chosen) {
                        setPickerValue(id, chosen);
                    }
                });
            }
        );
}

/**
 * Close one picker's suggestions.
 *
 * @param {string} id
 */
function hidePickerResults(id) {
    document
        .getElementById(`${id}-results`)
        ?.classList.add('hidden');
}

/**
 * Choose a record, or release the choice.
 *
 * @param {string} id
 * @param {object|null} item
 * @param {{keepText?: boolean, silent?: boolean}} options
 */
function setPickerValue(id, item, options = {}) {
    const picker = pickers[id];

    if (! picker) {
        return;
    }

    picker.selected = item ?? null;

    const hidden = document.getElementById(id);

    const search = document.getElementById(`${id}-search`);

    if (hidden) {
        hidden.value = item
            ? String(item.id)
            : '';
    }

    if (search && ! options.keepText) {
        search.value = item
            ? picker.label(item)
            : '';
    }

    if (item) {
        hidePickerResults(id);
    }

    updatePickerClear(id);

    if (! options.silent) {
        picker.onChange?.(picker.selected);

        saveProgress();
    }
}

/**
 * Show the clear button only when there is something to clear.
 *
 * @param {string} id
 */
function updatePickerClear(id) {
    const search = document.getElementById(`${id}-search`);

    const clear = document.getElementById(`${id}-clear`);

    if (! clear) {
        return;
    }

    clear.hidden = String(search?.value ?? '').trim() === '';
}

/**
 * The name shown for whatever a picker currently holds.
 *
 * The review page reads this where it used to read a select's chosen
 * option text.
 *
 * @param {string} id
 * @returns {string}
 */
function pickerLabel(id) {
    return String(
        document.getElementById(`${id}-search`)?.value ?? ''
    ).trim();
}

/**
 * Put every picker back in step with the values just restored into it.
 *
 * The hidden input and the search box are both ordinary fields, so a
 * restored assistant already carries the id and the name it was showing.
 * What it does not carry is the selected record itself, which is what the
 * clear button and the "typing means a new search" rule read.
 */
function resyncPickers() {
    Object.keys(pickers).forEach(
        (id) => {
            const chosen = document.getElementById(id)?.value ?? '';

            pickers[id].selected = chosen === ''
                ? null
                : {
                    id: chosen,
                    name: pickerLabel(id),
                };

            updatePickerClear(id);
        }
    );
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

            <div>
                <label for="${prefix}-email-policy" class="pm-field-label">
                    ${escapeHtml(translate('wizard.email_policy'))}
                </label>

                <select id="${prefix}-email-policy" class="pm-input">
                    <option value="inherit">
                        ${escapeHtml(translate('wizard.email_policy_inherit'))}
                    </option>

                    <option value="always">
                        ${escapeHtml(translate('wizard.email_policy_always'))}
                    </option>

                    <option value="never">
                        ${escapeHtml(translate('wizard.email_policy_never'))}
                    </option>
                </select>

                <p class="pm-wizard-help">
                    ${escapeHtml(translate('wizard.email_policy_help'))}
                </p>
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

        /*
         * V1.0.45: whether Patrimoine may write to this person. It is
         * the one thing on the full party form that cannot sensibly be
         * left until later - by the time somebody notices, the letting
         * has already sent them something, or failed to.
         */
        email_policy: value(`${prefix}-email-policy`) || 'inherit',
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

                <div class="pm-input-affix">
                    <input
                        id="${prefix}-share"
                        data-owner-share
                        type="text"
                        inputmode="decimal"
                        class="pm-input pr-14"
                        value="100"
                    >

                    <span class="pm-input-unit">%</span>
                </div>
            </div>
        </div>

        <div data-owner-existing>
            <label for="${prefix}-id-search" class="pm-field-label">
                ${escapeHtml(translate('wizard.choose_owner'))}
            </label>

            <div data-owner-party-picker></div>
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

    mountPartyPicker(
        `${prefix}-id`,
        row.querySelector('[data-owner-party-picker]'),
        'owner'
    );

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
                    /*
                     * V1.0.48: the chosen owner lives in the picker's
                     * hidden input, wizard-owner-N-id. The old selector
                     * looked for the <select> the pickers replaced in
                     * V1.0.46, found nothing, and submitted every
                     * existing owner as null — so a letting with an
                     * existing owner on a new property could never be
                     * created through the assistant at all.
                     */
                    return {
                        id: Number(
                            document.getElementById(`${prefix}-id`)?.value
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

    /*
     * V1.0.43: Next carries all the way through and becomes Create and
     * activate on the last page. Save as draft sits at the top beside
     * Cancel and is offered on every page including the last, because
     * somebody who reaches the review and is not ready to commit should
     * not have to walk backwards to keep their work.
     */
    toggle('wizard-next', step < TOTAL_STEPS);

    toggle('wizard-submit', step === TOTAL_STEPS);

    if (step === TOTAL_STEPS) {
        renderSummary();
    }
}

/**
 * The next step.
 *
 * V1.0.43: no page is skipped any more. Ownership was the only one that
 * ever was, and it now lives inside the page that names the property, so
 * "step 4 of 8" means the same thing every time the assistant is opened.
 *
 * @param {number} from
 * @param {number} direction
 * @returns {number}
 */
function nextStep(from, direction) {
    return Math.min(
        Math.max(from + direction, 1),
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

        /*
         * V1.0.43: the drawer's Notes field, which this page never had.
         */
        notes: value('wizard-notes') || null,

        advance_payment_amount: integer('wizard-advance-amount'),
        advance_received: advanceReceived,

        rent_increment_type: value('wizard-increment-type'),
        rent_increment_value: value('wizard-increment-type') === 'none'
            ? 0
            : dualValue(
                'wizard-increment-value',
                value('wizard-increment-type') === 'fixed'
            ),
        next_rent_increment_date: value('wizard-increment-type') === 'none'
            ? null
            : (dateValue('wizard-increment-date') || null),

        vat_rate: decimal('wizard-vat-rate'),
        management_fee_type: value('wizard-fee-type'),
        management_fee_value: value('wizard-fee-type') === 'none'
            ? 0
            : dualValue(
                'wizard-fee-value',
                value('wizard-fee-type') === 'fixed'
            ),

        agent_commission_amount: value('wizard-agent-mode') === 'none'
            ? 0
            : integer('wizard-agent-commission'),
    };

    /*
     * V1.0.43: receiving the security deposit.
     *
     * Entering a deposit receives it — the money goes into the lease's
     * own Security Deposit account, which every lease has owned since
     * V1.0.8 and which nothing ever funded. These three only say when it
     * changed hands and how, and the date is deliberately unbounded by
     * the lease start.
     */
    if (terms.security_deposit_amount > 0) {
        terms.security_deposit_received_date =
            dateValue('wizard-deposit-date') || null;

        terms.security_deposit_received_method =
            value('wizard-deposit-method') || 'bank_transfer';

        const depositReference = value('wizard-deposit-reference');

        if (depositReference) {
            terms.security_deposit_received_reference = depositReference;
        }
    }

    if (advanceReceived) {
        terms.advance_received_date = dateValue('wizard-advance-date') || null;
        terms.advance_received_method = value('wizard-advance-method');

        if (terms.advance_received_method === 'cash') {
            terms.advance_received_collector =
                value('wizard-advance-collector')
                || String(document.body.dataset.currentUserName ?? '');
        }

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
 * A money field, as a reader should see it.
 *
 * The check page printed the digits exactly as they were typed — 1250000,
 * with no grouping and no currency — on the one page whose purpose is to
 * be read back before anything is created.
 *
 * @param {string} id
 * @returns {string}
 */
function money(id) {
    return formatCurrency(
        integer(id)
    );
}

/**
 * The management fee, with the unit that gives it its meaning.
 *
 * @returns {string}
 */
function feeSummary() {
    const type = value('wizard-fee-type');

    if (type === 'none') {
        return translate('wizard.fee_none');
    }

    const amount = type === 'fixed'
        ? money('wizard-fee-value')
        : `${decimal('wizard-fee-value')}%`;

    return `${selectedText('wizard-fee-type')} · ${amount}`;
}

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
                ? pickerLabel('wizard-unit-id')
                : value('wizard-unit-name'),
        ],
        [
            translate('wizard.tenant'),
            value('wizard-tenant-mode') === 'existing'
                ? pickerLabel('wizard-tenant-id')
                : summaryPartyName('wizard-tenant-party'),
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
            `${money('wizard-rent-amount')} · ${selectedText('wizard-frequency')}`,
        ],
        [
            translate('wizard.security_deposit'),
            money('wizard-deposit'),
        ],
        [
            translate('wizard.fee_vat'),
            `${decimal('wizard-vat-rate')}%`,
        ],
        /*
         * The reserve is money the tenant hands over and cannot be spent
         * on ordinary rent. It was the one amount the check page did not
         * show, which is the page whose whole job is to show them.
         */
        [
            translate('wizard.rent_reserve'),
            money('wizard-reserve'),
        ],
        [
            translate('wizard.advance_amount'),
            checked('wizard-advance-received')
                ? `${money('wizard-advance-amount')} · ${translate('wizard.advance_received')}`
                : money('wizard-advance-amount'),
        ],
        [
            translate('wizard.consumable_advance'),
            formatCurrency(consumableAdvance()),
        ],
        [
            translate('wizard.fee_type'),
            feeSummary(),
        ],
        /*
         * V1.0.45: the agent is read where they are now asked for, so the
         * check page walks the assistant in its own order rather than in
         * the order the pages used to be in.
         */
        [
            translate('wizard.agent'),
            agentSummary(),
        ],
        [
            translate('wizard.agent_commission'),
            value('wizard-agent-mode') === 'none'
                ? translate('wizard.no_agent')
                : money('wizard-agent-commission'),
        ],
        [
            translate('wizard.notes'),
            value('wizard-notes'),
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
        ? pickerLabel('wizard-agent-id')
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
                    ?? translate('wizard.save_failed'),
                    payload?.code ?? null
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
            : (payload?.message ?? translate('wizard.save_failed')),
        payload?.code ?? null
    );

    const steps = keys
        .map(stepForErrorKey)
        .filter((step) => step !== null);

    if (steps.length > 0) {
        showStep(Math.min(...steps));
    }

    markRejectedFields(keys);

    revealError();
}

/*
 * The server names a field by the name it has in the payload, and the
 * assistant shows a screen full of fields. "The telephone number field is
 * required" over a page holding two people and eleven boxes leaves the
 * reader to work out which box. These say which.
 */

/** What a party field is called on the screen. */
const PARTY_FIELD_ELEMENTS = {
    name: ['given-names', 'surname'],
    given_names: 'given-names',
    surname: 'surname',
    legal_name: 'legal-name',
    contact_name: 'contact-name',
    phone: 'phone-number',
    email: 'email',
};

/** What a lease field is called on the screen. */
const LEASE_FIELD_ELEMENTS = {
    start_date: 'wizard-start-date',
    end_date: 'wizard-end-date',
    termination_notice_date: 'wizard-notice-date',
    rent_amount: 'wizard-rent-amount',
    payment_frequency: 'wizard-frequency',
    due_day: 'wizard-due-day',
    proration_amount: 'wizard-proration',
    security_deposit_amount: 'wizard-deposit',
    rent_reserve_amount: 'wizard-reserve',
    advance_payment_amount: 'wizard-advance-amount',
    advance_received_date: 'wizard-advance-date',
    advance_received_method: 'wizard-advance-method',
    advance_received_reference: 'wizard-advance-reference',
    advance_received_collector: 'wizard-advance-collector',
    rent_increment_type: 'wizard-increment-type',
    rent_increment_value: 'wizard-increment-value',
    next_rent_increment_date: 'wizard-increment-date',
    vat_rate: 'wizard-vat-rate',
    management_fee_type: 'wizard-fee-type',
    management_fee_value: 'wizard-fee-value',
    agent_commission_amount: 'wizard-agent-commission',

    /* V1.0.43: the fields the assistant gained from the drawer. */
    notes: 'wizard-notes',
    security_deposit_received_date: 'wizard-deposit-date',
    security_deposit_received_method: 'wizard-deposit-method',
    security_deposit_received_reference: 'wizard-deposit-reference',
};

/**
 * The element or elements a rejected key belongs to, if any.
 *
 * @param {string} key
 * @returns {HTMLElement|Array<HTMLElement>|null}
 */
function elementForErrorKey(key) {
    /*
     * A person's name is sent as one string built from two boxes, so a
     * rejected `name` belongs to both of them.
     */
    const party = (prefix, field) => {
        const names = PARTY_FIELD_ELEMENTS[field];

        if (! names) {
            return null;
        }

        return [names]
            .flat()
            .map((name) => document.getElementById(`${prefix}-${name}`))
            .filter(Boolean);
    };

    if (key.startsWith('building.attributes.')) {
        const field = key.slice('building.attributes.'.length);

        return document.getElementById(
            field === 'address'
                ? 'wizard-building-address'
                : 'wizard-building-name'
        );
    }

    if (key.startsWith('unit.attributes.')) {
        return document.getElementById('wizard-unit-name');
    }

    if (key.startsWith('owners.')) {
        const parts = key.split('.');

        /*
         * readOwners() walks the rows in document order, so the index in
         * the rejected key is the position of the row rather than the
         * number in its data attribute — which does not come back down
         * when a row is removed.
         */
        const row = document.querySelectorAll('[data-owner-row]')[
            Number(parts[1])
        ];

        if (! row) {
            return null;
        }

        if (parts[2] === 'ownership_percentage') {
            return row.querySelector('[data-owner-share]');
        }

        return party(
            `wizard-owner-${row.dataset.ownerRow}-party`,
            parts[3] ?? ''
        );
    }

    if (key.startsWith('tenant.attributes.')) {
        return party(
            'wizard-tenant-party',
            key.slice('tenant.attributes.'.length)
        );
    }

    if (key.startsWith('agent.attributes.')) {
        return party(
            'wizard-agent-party',
            key.slice('agent.attributes.'.length)
        );
    }

    if (key.startsWith('lease.')) {
        const id = LEASE_FIELD_ELEMENTS[key.slice('lease.'.length)];

        return id
            ? document.getElementById(id)
            : null;
    }

    return null;
}

/**
 * Put a ring round every rejected field and land on the first of them.
 *
 * @param {Array<string>} keys
 */
function markRejectedFields(keys) {
    clearRejectedFields();

    const marked = keys
        .map(elementForErrorKey)
        .flat()
        .filter(Boolean);

    marked.forEach((element) => {
        element.setAttribute('aria-invalid', 'true');
    });

    /*
     * Focusing scrolls, and the error box is above the fields, so the box
     * is read first and the cursor is already in the field to correct.
     */
    marked[0]?.focus({ preventScroll: false });
}

/**
 * Take the rings off. A correction the operator has already made should
 * not still be marked wrong.
 */
function clearRejectedFields() {
    document
        .querySelectorAll('.pm-wizard-page [aria-invalid="true"]')
        .forEach((element) => {
            element.removeAttribute('aria-invalid');
        });
}

/**
 * Which page owns a rejected field.
 *
 * @param {string} key
 * @returns {number|null}
 */
function stepForErrorKey(key) {
    /*
     * V1.0.43: the property, its owners and the tenant are one page now,
     * so three of these answers are the same page.
     */
    if (
        key.startsWith('building')
        || key.startsWith('unit')
        || key.startsWith('owners')
        || key.startsWith('tenant')
    ) {
        return 2;
    }

    /*
     * V1.0.45: the agent moved to Fees & Commission, beside the money
     * that is theirs. A rejected agent field has to follow them there,
     * or the operator is sent to a page the field is no longer on.
     */
    if (key.startsWith('agent')) {
        return 7;
    }

    if (! key.startsWith('lease.')) {
        return null;
    }

    const field = key.slice('lease.'.length);

    if (
        [
            'start_date',
            'end_date',
            'termination_notice_date',
        ].includes(field)
    ) {
        return 3;
    }

    if (
        [
            'rent_amount',
            'payment_frequency',
            'due_day',
            'vat_rate',
            'proration_amount',
            'security_deposit_amount',
        ].includes(field)
        || field.startsWith('security_deposit_received')
    ) {
        return 4;
    }

    if (
        [
            'advance_payment_amount',
            'rent_reserve_amount',
        ].includes(field)
        || field.startsWith('advance_received')
    ) {
        return 5;
    }

    if (
        field.startsWith('rent_increment')
        || field === 'next_rent_increment_date'
    ) {
        return 6;
    }

    if (
        field.startsWith('management_fee')
        || field === 'agent_commission_amount'
        || field === 'notes'
    ) {
        return 7;
    }

    /*
     * unit_id, tenant_id and status are decided by the pages above but
     * are only rejected once everything is assembled, so the review page
     * is where the explanation belongs.
     */
    return TOTAL_STEPS;
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
                /*
                 * V1.0.45: the page number is no longer kept. The
                 * assistant opens on page one whatever happened before,
                 * so storing where somebody was would only be a value
                 * nothing reads.
                 */
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

    /*
     * V1.0.43: textarea as well as input and select. The assistant gained
     * the drawer's Notes field, and a refresh would have dropped it.
     */
    document
        .querySelectorAll(
            '.pm-wizard-page input,'
            + ' .pm-wizard-page select,'
            + ' .pm-wizard-page textarea'
        )
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

    /*
     * V1.0.45: the assistant always opens on page one.
     *
     * It used to reopen wherever you last were, which reads as being
     * dropped into the middle of something — you cannot see what has
     * already been answered above you, and there is no way to tell the
     * assistant you would rather start at the beginning.
     *
     * What was typed is still put back, so nothing is lost. Only the
     * page number is forgotten, and Next walks to wherever you were.
     */
    currentStep = 1;

    applyBuildingMode();

    applyUnitMode();

    applyTenantMode();

    applyAgentMode();

    applyIncrementType();

    applyFeeType();

    applyAdvanceReceived();

    applyOwnersBlock();

    applyDepositReceipt();

    applyConsumableAdvance();

    applyCurrencyUnits();

    regroupMoneyFields();

    /*
     * The pickers hold their id and their name in ordinary fields, so
     * both came back with everything else. What did not come back is the
     * record each one is holding, which is what the clear button and the
     * "typing means a new search" rule read.
     */
    resyncPickers();
}

/**
 * What is left of the advance once the reserve is taken out of it.
 *
 * The drawer has always shown this. It is the one number that says what
 * the tenant can actually spend on rent, and working it out was the
 * reader's own arithmetic on this page.
 *
 * @returns {number}
 */
function consumableAdvance() {
    return Math.max(
        0,
        integer('wizard-advance-amount')
        - integer('wizard-reserve')
    );
}

/**
 * Show it.
 */
function applyConsumableAdvance() {
    setText(
        'wizard-consumable-advance',
        formatCurrency(
            consumableAdvance()
        )
    );
}

/**
 * Ask when and how the deposit was received, once there is a deposit.
 *
 * V1.0.43. Entering a deposit receives it into the lease's own Security
 * Deposit account — every lease has owned one since V1.0.8 and nothing
 * ever funded it — so these fields appear the moment there is money to
 * have received, and go away again when the amount returns to nothing.
 */
function applyDepositReceipt() {
    toggle(
        'wizard-deposit-receipt',
        integer('wizard-deposit') > 0
    );
}

/**
 * Put the thousands separators back after values are written in bulk.
 *
 * The live grouping is driven by the input event, and setting .value from
 * script raises none — so a restored draft, or a lease copied into the
 * assistant, would show its amounts as bare digits.
 */
function regroupMoneyFields() {
    document
        .querySelectorAll('[data-money-input="on"], [data-money-input=""]')
        .forEach((input) => {
            input.value = formatMoneyDigits(
                parseMoneyInput(input.value)
            );
        });
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
    const type = value('wizard-increment-type');

    toggle(
        'wizard-increment-details',
        type !== 'none'
    );

    /*
     * A rise of 10 is ten per cent or ten thousand cedis depending on the
     * setting above it, and the field said neither.
     */
    setMoneyMode('wizard-increment-value', type === 'fixed');

    setUnit(
        'wizard-increment-unit',
        type === 'percentage'
            ? '%'
            : (type === 'fixed' ? currencyLabel() : '')
    );
}

function applyFeeType() {
    const type = value('wizard-fee-type');

    setMoneyMode('wizard-fee-value', type === 'fixed');

    setUnit(
        'wizard-fee-unit',
        type === 'percentage'
            ? '%'
            : (type === 'fixed' ? currencyLabel() : '')
    );

    const input = document.getElementById('wizard-fee-value');

    if (input) {
        input.disabled = type === 'none';

        if (type === 'none') {
            input.value = '0';
        }
    }
}

/**
 * Every whole-currency field on the assistant says what currency it is.
 *
 * The amounts are the only thing on these pages a person cannot check by
 * reading: 1250000 and 125000 look alike at a glance, and getting it
 * wrong sets the rent for a year.
 */
function applyCurrencyUnits() {
    const currency = currencyLabel();

    [
        'wizard-agent-commission-unit',
        'wizard-rent-amount-unit',
        'wizard-proration-unit',
        'wizard-deposit-unit',
        'wizard-reserve-unit',
        'wizard-advance-amount-unit',
    ].forEach((id) => setUnit(id, currency));
}

function applyAdvanceReceived() {
    toggle(
        'wizard-advance-details',
        checked('wizard-advance-received')
    );

    applyAdvanceMethod();
}

/**
 * The cashier, when the money came in as cash.
 *
 * It is always the person signed in — the server overwrites whatever is
 * sent — so it is shown rather than asked for. The lease form does
 * exactly this; the assistant had no such field at all, and the rule on
 * the lease form therefore refused every cash advance created through it
 * with "The cashier field is required", naming a field that was not on
 * the screen.
 */
function applyAdvanceMethod() {
    const isCash =
        checked('wizard-advance-received')
        && value('wizard-advance-method') === 'cash';

    toggle('wizard-advance-collector-field', isCash);

    setValue(
        'wizard-advance-collector',
        isCash
            ? String(document.body.dataset.currentUserName ?? '')
            : ''
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

        applyOwnersBlock();
    });

    on('wizard-unit-mode', 'change', (event) => {
        if (event?.isTrusted) {
            unitModeChosenByHand = true;
        }

        applyUnitMode();
    });

    on('wizard-tenant-mode', 'change', applyTenantMode);

    on('wizard-agent-mode', 'change', applyAgentMode);

    on('wizard-increment-type', 'change', applyIncrementType);

    on('wizard-fee-type', 'change', applyFeeType);

    on('wizard-advance-received', 'change', applyAdvanceReceived);

    on('wizard-advance-method', 'change', applyAdvanceMethod);

    /*
     * V1.0.43: the deposit receipt appears with the deposit, and the
     * consumable advance is recomputed whenever either half of it moves.
     */
    on('wizard-deposit', 'input', applyDepositReceipt);

    on('wizard-advance-amount', 'input', applyConsumableAdvance);

    on('wizard-reserve', 'input', applyConsumableAdvance);

    applyOwnersBlock();

    applyDepositReceipt();

    applyConsumableAdvance();

    applyCurrencyUnits();

    applyFeeType();

    applyIncrementType();

    applyAdvanceMethod();

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

    /*
     * Choosing a property is what decides whether ownership is asked for,
     * and the property list arrives after the controls are wired.
     */
    on('wizard-unit-mode', 'change', applyOwnersBlock);

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

/**
 * A field that holds a percentage on one setting and money on another.
 *
 * It has to be read the way it is currently written. A GHS amount is
 * grouped with commas, and decimal() turns a comma into a decimal point —
 * so reading a grouped 500,000 as a decimal would send 500 to the server
 * and nobody would see it happen.
 *
 * @param {string} id
 * @param {boolean} isMoney
 * @returns {number}
 */
function dualValue(id, isMoney) {
    return isMoney
        ? integer(id)
        : decimal(id);
}

/**
 * The currency the organisation keeps its books in.
 *
 * @returns {string}
 */
function currencyLabel() {
    const configuration = getPresentationConfiguration();

    return String(
        configuration?.currency_definition?.symbol
        || configuration?.currency
        || ''
    );
}

/**
 * Put a field into, or out of, money entry.
 *
 * Money is grouped as it is typed; a percentage is left as a plain
 * number. Switching between them re-reads the digits already there, so
 * changing the setting never changes the amount.
 *
 * @param {string} id
 * @param {boolean} isMoney
 */
function setMoneyMode(id, isMoney) {
    const input = document.getElementById(id);

    if (! input) {
        return;
    }

    const digits = parseMoneyInput(input.value);

    if (isMoney) {
        input.dataset.moneyInput = 'on';

        input.inputMode = 'numeric';

        input.value = formatMoneyDigits(digits);

        return;
    }

    input.dataset.moneyInput = 'off';

    input.inputMode = 'decimal';
}

/**
 * Write the unit that sits at the end of a field.
 *
 * @param {string} id
 * @param {string} unit
 */
function setUnit(id, unit) {
    setText(id, unit);
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

/**
 * Show a refusal, with the code that explains it.
 *
 * Every other error box in Patrimoine carries a PM code and core.js turns
 * it into a link to the page that explains it. This one printed the
 * sentence alone, so the one screen where a person is most likely to be
 * stuck was the one screen that would not tell them where to read about
 * it.
 *
 * @param {string} message
 * @param {string|null} code
 */
function showError(message, code = null) {
    const box = document.getElementById('wizard-error');

    if (box) {
        box.textContent = messageWithErrorCode(message, code);

        box.classList.remove('hidden');
    }
}

/**
 * Bring the error box into view when it is off screen.
 *
 * Stepping deliberately leaves the page where it is, so a refusal raised
 * from a page the operator cannot currently see would otherwise be
 * invisible. Only scrolls when it has to.
 */
function revealError() {
    const box = document.getElementById('wizard-error');

    if (! box || box.classList.contains('hidden')) {
        return;
    }

    const boxTop = box.getBoundingClientRect().top;

    if (boxTop >= 0 && boxTop <= window.innerHeight) {
        return;
    }

    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideError() {
    document
        .getElementById('wizard-error')
        ?.classList
        .add('hidden');

    clearRejectedFields();
}
