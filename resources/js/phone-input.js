/*
|--------------------------------------------------------------------------
| Telephone fields
|--------------------------------------------------------------------------
|
| A telephone number is two answers, not one: which country, and then the
| number itself. Splitting them is what lets Patrimoine store E.164 — the
| only form an SMS or WhatsApp gateway will accept — while the person
| typing still sees the number the way they say it out loud.
|
| The control is three inputs behind one field:
|
|   [data-phone-national]  what the person types and sees
|   [data-phone-value]     the two joined, in E.164; this carries the id
|                          the page reads, so nothing else had to change
|   [data-phone-country]   the ISO country, so the right flag comes back
|
| Every listener here is delegated from the document, like the money
| inputs, because most of these fields live in drawers that are rendered
| long after the page loads.
|
*/

import { COUNTRIES, KEEPS_TRUNK_ZERO, PREFERRED_FOR_CODE } from './countries.js';
import { escapeHtml, getPresentationConfiguration, translate } from './core.js';

/**
 * The active interface language, which decides both the country names and
 * the order they are listed in.
 */
function language() {
    return getPresentationConfiguration().language === 'fr'
        ? 'fr'
        : 'en';
}

/**
 * Countries in the alphabetical order of the language on screen.
 *
 * 'Germany' and 'Allemagne' do not sort to the same place, so the order is
 * recomputed whenever the language changes rather than baked into the data.
 */
const ordered = {};

function countries() {
    const active = language();

    ordered[active] ??= [...COUNTRIES].sort(
        (a, b) => a[active].localeCompare(
            b[active],
            active
        )
    );

    return ordered[active];
}

/**
 * Fold accents and case away so "cote" finds "Côte d'Ivoire".
 */
function foldable(value) {
    return value
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

const searchable = new WeakMap();

function matches(country, term) {
    if (term === '') {
        return true;
    }

    let haystack =
        searchable.get(country);

    if (haystack === undefined) {
        haystack = foldable(
            `${country.en} ${country.fr} ${country.iso} +${country.code} ${country.code}`
        );

        searchable.set(country, haystack);
    }

    return haystack.includes(term);
}

/*
|--------------------------------------------------------------------------
| Reading and writing a field
|--------------------------------------------------------------------------
*/

/**
 * The three inputs of one field, given any element inside it.
 */
function parts(element) {
    const field =
        element?.closest('[data-phone-field]');

    if (! field) {
        return null;
    }

    return {
        field,
        national: field.querySelector('[data-phone-national]'),
        value: field.querySelector('[data-phone-value]'),
        country: field.querySelector('[data-phone-country]'),
        toggle: field.querySelector('[data-phone-toggle]'),
        flag: field.querySelector('[data-phone-flag]'),
        code: field.querySelector('[data-phone-code]'),
        menu: field.querySelector('[data-phone-menu]'),
        search: field.querySelector('[data-phone-search]'),
        list: field.querySelector('[data-phone-list]'),
    };
}

function countryFor(iso) {
    return COUNTRIES.find(
        (country) => country.iso === iso
    ) ?? null;
}

/**
 * Redraw the country button.
 */
function paintCountry(field) {
    const parts_ = parts(field);
    const country = countryFor(parts_.country?.value || '');

    if (parts_.flag) {
        parts_.flag.className =
            country === null
                ? 'pm-flag pm-flag-empty'
                : `pm-flag pm-flag-${country.iso.toLowerCase()}`;
    }

    if (parts_.code) {
        parts_.code.textContent =
            country === null
                ? translate('phone.select')
                : `+${country.code}`;
    }

    parts_.toggle?.classList.toggle(
        'pm-phone-country-empty',
        country === null
    );
}

/**
 * The country a full international number belongs to, longest code first.
 *
 * Only used to make sense of a number somebody pasted; a number typed into
 * the field carries its country in the button beside it.
 */
function countryForNumber(value) {
    const digits =
        value.replace(/[^0-9]/g, '');

    for (let length = 4; length >= 1; length--) {
        const code =
            Number(digits.slice(0, length));

        if (! code) {
            continue;
        }

        if (PREFERRED_FOR_CODE[code]) {
            return countryFor(PREFERRED_FOR_CODE[code]);
        }

        const match =
            COUNTRIES.find((country) => country.code === code);

        if (match) {
            return match;
        }
    }

    return null;
}

/**
 * Join what the person typed to the country they chose.
 *
 * A trunk prefix is dropped: 024 123 4567 dialled inside Ghana is
 * +233 24 123 4567 to everyone else. Italy is the exception — a Rome
 * landline really is +39 06 …, and its neighbours number the same way.
 */
function compose(field) {
    const parts_ = parts(field);
    const country = countryFor(parts_.country?.value || '');

    let digits =
        (parts_.national?.value ?? '')
            .replace(/[^0-9]/g, '');

    if (
        country === null
        || ! KEEPS_TRUNK_ZERO.includes(country.iso)
    ) {
        digits = digits.replace(/^0+/, '');
    }

    if (! parts_.value) {
        return;
    }

    if (digits === '') {
        parts_.value.value = '';

        return;
    }

    /*
     * With no country there is nothing to prefix. The digits still travel
     * so the server can say which of the two answers is missing.
     */
    parts_.value.value =
        country === null
            ? digits
            : `+${country.code}${digits}`;
}

/**
 * Show a stored number in the control.
 *
 * Pages call this when they open a record for editing. A number recorded
 * before V1.0.30 has no country and is shown exactly as it was typed.
 *
 * @param {string} id      the id of the hidden E.164 input
 * @param {?string} number the stored number
 * @param {?string} iso    the stored country
 */
export function applyPhoneValue(id, number, iso) {
    const value =
        document.getElementById(id);

    const parts_ = parts(value);

    if (parts_ === null) {
        /*
         * The page may not have been converted to the new control. Setting
         * the plain value keeps it working.
         */
        if (value) {
            value.value = number ?? '';
        }

        return;
    }

    const country = countryFor(
        (iso ?? '').toUpperCase()
    );

    parts_.country.value =
        country?.iso ?? '';

    parts_.value.value =
        number ?? '';

    if (country !== null && (number ?? '').startsWith(`+${country.code}`)) {
        parts_.national.value =
            number.slice(`+${country.code}`.length);
    } else {
        parts_.national.value =
            number ?? '';
    }

    paintCountry(parts_.field);
}

/**
 * Read a field, for pages that would rather ask than reach for two ids.
 *
 * @returns {{number: ?string, country: ?string}}
 */
export function readPhoneValue(id) {
    const parts_ = parts(
        document.getElementById(id)
    );

    if (parts_ === null) {
        const value =
            document.getElementById(id);

        return {
            number: (value?.value || null),
            country: null,
        };
    }

    return {
        number: parts_.value.value || null,
        country: parts_.country.value || null,
    };
}

/*
|--------------------------------------------------------------------------
| The country list
|--------------------------------------------------------------------------
*/

function renderList(field, term = '') {
    const parts_ = parts(field);

    if (! parts_.list) {
        return;
    }

    const active = language();
    const folded = foldable(term.trim());
    const chosen = parts_.country?.value || '';

    const rows = countries()
        .filter((country) => matches(country, folded))
        .map((country) => `
            <li
                role="option"
                data-phone-option="${country.iso}"
                aria-selected="${country.iso === chosen ? 'true' : 'false'}"
                class="pm-phone-option${country.iso === chosen ? ' pm-phone-option-chosen' : ''}"
            >
                <span class="pm-flag pm-flag-${country.iso.toLowerCase()}"></span>
                <span class="pm-phone-option-name">${escapeHtml(country[active])}</span>
                <span class="pm-phone-option-code">+${country.code}</span>
            </li>
        `);

    parts_.list.innerHTML =
        rows.length === 0
            ? `<li class="pm-phone-empty">${escapeHtml(translate('phone.none'))}</li>`
            : rows.join('');
}

/**
 * Put the open menu beside its button, in viewport coordinates.
 *
 * Most of these fields sit inside a drawer that scrolls, and a menu
 * positioned inside that scrolling box would be cut off at its edge. The
 * menu is therefore taken out of the flow entirely and placed against the
 * button, above it when there is not enough room below.
 */
function placeMenu(field) {
    const parts_ = parts(field);

    if (! parts_?.menu || parts_.menu.hidden) {
        return;
    }

    const anchor =
        parts_.field.getBoundingClientRect();

    const height =
        parts_.menu.offsetHeight;

    const below =
        window.innerHeight - anchor.bottom;

    const above =
        anchor.top;

    const flip =
        below < height + 8 && above > below;

    parts_.menu.style.width =
        `${Math.max(anchor.width, 288)}px`;

    parts_.menu.style.left =
        `${Math.max(8, Math.min(anchor.left, window.innerWidth - parts_.menu.offsetWidth - 8))}px`;

    parts_.menu.style.top =
        flip
            ? `${Math.max(8, anchor.top - height - 4)}px`
            : `${anchor.bottom + 4}px`;
}

function closeMenu(field) {
    const parts_ = parts(field);

    if (! parts_?.menu || parts_.menu.hidden) {
        return;
    }

    parts_.menu.hidden = true;

    parts_.toggle?.setAttribute(
        'aria-expanded',
        'false'
    );
}

function closeEveryMenu() {
    document
        .querySelectorAll('[data-phone-menu]:not([hidden])')
        .forEach(
            (menu) => closeMenu(menu)
        );
}

function openMenu(field) {
    const parts_ = parts(field);

    if (! parts_?.menu) {
        return;
    }

    closeEveryMenu();

    if (parts_.search) {
        parts_.search.value = '';

        parts_.search.placeholder =
            translate('phone.search');
    }

    renderList(field);

    parts_.menu.hidden = false;

    placeMenu(field);

    parts_.toggle?.setAttribute(
        'aria-expanded',
        'true'
    );

    parts_.search?.focus();

    /*
     * Bring the chosen country into view, so reopening a field lands on
     * the answer rather than at the letter A.
     */
    parts_.list
        ?.querySelector('.pm-phone-option-chosen')
        ?.scrollIntoView({ block: 'center' });
}

function choose(field, iso) {
    const parts_ = parts(field);

    parts_.country.value = iso;

    paintCountry(field);
    compose(field);
    closeMenu(field);

    parts_.national?.focus();
}

/**
 * Move the highlight through the filtered list.
 */
function moveHighlight(field, step) {
    const parts_ = parts(field);

    const options = [
        ...(parts_.list?.querySelectorAll('[data-phone-option]') ?? []),
    ];

    if (options.length === 0) {
        return;
    }

    const current =
        options.findIndex(
            (option) => option.classList.contains('pm-phone-option-active')
        );

    const next =
        Math.min(
            Math.max(
                (current === -1 ? -1 : current) + step,
                0
            ),
            options.length - 1
        );

    options.forEach(
        (option) => option.classList.remove('pm-phone-option-active')
    );

    options[next].classList.add('pm-phone-option-active');

    options[next].scrollIntoView({ block: 'nearest' });
}

/*
|--------------------------------------------------------------------------
| Wiring
|--------------------------------------------------------------------------
*/

let wired = false;

/**
 * Install the delegated listeners. Safe to call more than once.
 */
export function initializePhoneInputs() {
    if (wired) {
        return;
    }

    wired = true;

    document.addEventListener(
        'click',
        (event) => {
            const toggle =
                event.target.closest?.('[data-phone-toggle]');

            if (toggle) {
                event.preventDefault();
                event.stopPropagation();

                const parts_ = parts(toggle);

                if (parts_.menu.hidden) {
                    openMenu(toggle);
                } else {
                    closeMenu(toggle);
                }

                return;
            }

            const option =
                event.target.closest?.('[data-phone-option]');

            if (option) {
                event.preventDefault();
                event.stopPropagation();

                choose(
                    option,
                    option.dataset.phoneOption
                );

                return;
            }

            /*
             * A click inside the menu that is not on an option — the search
             * box — must not close it.
             */
            if (event.target.closest?.('[data-phone-menu]')) {
                return;
            }

            closeEveryMenu();
        }
    );

    document.addEventListener(
        'input',
        (event) => {
            const input = event.target;

            if (! (input instanceof HTMLInputElement)) {
                return;
            }

            if (input.matches('[data-phone-national]')) {
                /*
                 * Somebody pasting a whole international number has
                 * answered both questions at once. Take them at their
                 * word rather than prefixing a second calling code onto
                 * the front of the first.
                 */
                if (input.value.trim().startsWith('+')) {
                    const pasted =
                        countryForNumber(input.value);

                    if (pasted !== null) {
                        const parts_ = parts(input);

                        parts_.country.value = pasted.iso;

                        input.value =
                            input.value
                                .replace(/[^0-9]/g, '')
                                .slice(String(pasted.code).length);

                        paintCountry(parts_.field);
                        compose(input);

                        return;
                    }
                }

                /*
                 * Spaces are allowed while typing — people group digits as
                 * they say them — but nothing else.
                 */
                const cleaned =
                    input.value.replace(/[^0-9 ]/g, '');

                if (cleaned !== input.value) {
                    const caret =
                        input.value.length
                        - (input.selectionStart ?? input.value.length);

                    input.value = cleaned;

                    const position =
                        Math.max(0, cleaned.length - caret);

                    input.setSelectionRange(position, position);
                }

                compose(input);

                return;
            }

            if (input.matches('[data-phone-search]')) {
                renderList(input, input.value);
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {
            const search =
                event.target.closest?.('[data-phone-search]');

            if (! search) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();

                closeMenu(search);

                parts(search).toggle?.focus();

                return;
            }

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();

                moveHighlight(
                    search,
                    event.key === 'ArrowDown' ? 1 : -1
                );

                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();

                const parts_ = parts(search);

                const target =
                    parts_.list?.querySelector('.pm-phone-option-active')
                    ?? parts_.list?.querySelector('[data-phone-option]');

                if (target) {
                    choose(
                        target,
                        target.dataset.phoneOption
                    );
                }
            }
        }
    );

    /*
     * The menu is positioned against the viewport, so anything that moves
     * the button underneath it has to move the menu too.
     */
    const reposition = () => {
        document
            .querySelectorAll('[data-phone-menu]:not([hidden])')
            .forEach(
                (menu) => placeMenu(menu)
            );
    };

    document.addEventListener('scroll', reposition, true);

    window.addEventListener('resize', reposition);

    /*
     * The country names, their order and the search placeholder all follow
     * the interface language.
     */
    document.addEventListener(
        'patrimoine:language-changed',
        () => {
            document
                .querySelectorAll('[data-phone-field]')
                .forEach(
                    (field) => paintCountry(field)
                );

            closeEveryMenu();
        }
    );
}

/**
 * Paint every field on the page from the values already in it.
 *
 * Server-rendered pages set the hidden inputs directly; this makes the
 * button agree with them.
 */
export function refreshPhoneInputs(root = document) {
    root
        .querySelectorAll('[data-phone-field]')
        .forEach(
            (field) => {
                const parts_ = parts(field);

                applyPhoneValue(
                    parts_.value.id,
                    parts_.value.value || null,
                    parts_.country.value || null
                );
            }
        );
}

/**
 * The markup for one field, for forms that are built in JavaScript.
 *
 * @param {{id: string, national?: string, required?: boolean}} options
 */
export function phoneFieldMarkup({ id, national = `${id}-number`, required = false }) {
    return `
        <div class="pm-phone" data-phone-field>
            <button
                type="button"
                data-phone-toggle
                aria-haspopup="listbox"
                aria-expanded="false"
                aria-label="${escapeHtml(translate('phone.country'))}"
                class="pm-phone-country pm-phone-country-empty"
            >
                <span class="pm-flag pm-flag-empty" data-phone-flag></span>
                <span class="pm-phone-code" data-phone-code>${escapeHtml(translate('phone.select'))}</span>
                <svg class="pm-phone-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <input
                id="${escapeHtml(national)}"
                type="tel"
                inputmode="tel"
                autocomplete="tel-national"
                maxlength="20"
                class="pm-phone-number"
                data-phone-national
                ${required ? 'required' : ''}
            >

            <input id="${escapeHtml(id)}" type="hidden" data-phone-value>
            <input id="${escapeHtml(id)}-country" type="hidden" data-phone-country>

            <div class="pm-phone-menu" data-phone-menu hidden>
                <input
                    type="search"
                    autocomplete="off"
                    role="combobox"
                    aria-expanded="true"
                    aria-autocomplete="list"
                    class="pm-phone-search"
                    data-phone-search
                    placeholder="${escapeHtml(translate('phone.search'))}"
                >

                <ul class="pm-phone-list" role="listbox" data-phone-list></ul>
            </div>
        </div>
    `;
}
