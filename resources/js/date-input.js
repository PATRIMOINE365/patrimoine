

/*
|--------------------------------------------------------------------------
| Patrimoine Date Input Helpers
|--------------------------------------------------------------------------
|
| User-facing business dates follow the organisation language:
|
| French:  DD-MM-YYYY
| English: DD/MM/YYYY
|
| API payloads continue to use ISO YYYY-MM-DD.
|
| This module intentionally handles presentation/input conversion only.
| Business date calculations remain in the existing application services.
|
*/

import { icon } from './icons.js';

/**
 * Convert ISO YYYY-MM-DD into the Patrimoine display date format.
 *
 * Existing non-ISO values are returned unchanged.
 *
 * @param {string|null|undefined} value
 * @returns {string}
 */
/**
 * Return true when the current application language is French.
 *
 * @returns {boolean}
 */
export function usesFrenchDateFormat() {
    return document.documentElement.lang
        ?.toLowerCase()
        .startsWith('fr')
        ?? false;
}


/**
 * Return the visible date separator for the organisation language.
 *
 * French:  -
 * English: /
 *
 * @returns {string}
 */
export function dateSeparator() {
    return usesFrenchDateFormat()
        ? '-'
        : '/';
}


/**
 * Return the empty visible date placeholder.
 *
 * French:  jj-mm-aaaa
 * English: dd/mm/yyyy
 *
 * @returns {string}
 */
export function datePlaceholder() {
    return usesFrenchDateFormat()
        ? 'jj-mm-aaaa'
        : 'dd/mm/yyyy';
}


/**
 * Return today's LOCAL browser date as ISO YYYY-MM-DD.
 *
 * Do not use toISOString() for business-date defaults because UTC can
 * produce the previous or following calendar day around midnight.
 *
 * @returns {string}
 */
export function localToday() {
    const now =
        new Date();

    const year =
        now.getFullYear();

    const month =
        String(
            now.getMonth() + 1
        ).padStart(
            2,
            '0'
        );

    const day =
        String(
            now.getDate()
        ).padStart(
            2,
            '0'
        );

    return `${year}-${month}-${day}`;
}


export function dateForDisplay(
    value
) {
    if (! value) {
        return '';
    }

    const text =
        String(value);

    const match =
        text.match(
            /^(\d{4})-(\d{2})-(\d{2})$/
        );

    if (! match) {
        return text;
    }

    const separator =
        dateSeparator();

    return `${match[3]}${separator}${match[2]}${separator}${match[1]}`;
}


/**
 * Convert a Patrimoine display date into ISO YYYY-MM-DD for API submission.
 *
 * Existing ISO values are accepted unchanged.
 *
 * Unknown/incomplete values are returned unchanged so the existing
 * validation/API layers remain responsible for rejecting invalid dates.
 *
 * @param {string|null|undefined} value
 * @returns {string}
 */
export function dateForApi(
    value
) {
    const text =
        String(
            value
            ?? ''
        ).trim();

    if (
        /^\d{4}-\d{2}-\d{2}$/.test(
            text
        )
    ) {
        return text;
    }

    const match =
        text.match(
            /^(\d{2})[-/](\d{2})[-/](\d{4})$/
        );

    if (! match) {
        return text;
    }

    return `${match[3]}-${match[2]}-${match[1]}`;
}


/**
 * Apply Patrimoine's language-aware date typing behaviour to matching inputs.
 *
 * Digits entered as DDMMYYYY are progressively rendered using the
 * organisation language separator.
 *
 * The function is safe to call more than once. Each initialized input is
 * marked so duplicate event listeners are not attached.
 *
 * @param {string} selector
 */
export function initializeDateInputs(
    selector = '[data-pm-date-input]'
) {
    document
        .querySelectorAll(
            selector
        )
        .forEach(
            (input) => {
                /*
                 * Every Patrimoine business-date field should retain access
                 * to a calendar picker.
                 *
                 * Existing page-specific controls such as Lease, Reports,
                 * Security Deposit and Record Payment keep their dedicated
                 * implementations. Plain shared date inputs receive the
                 * generic Patrimoine calendar control here.
                 */
                ensureDateCalendarPicker(
                    input
                );

                if (
                    input.dataset
                        .pmDateInitialized
                    === 'true'
                ) {
                    return;
                }

                input.dataset
                    .pmDateInitialized =
                    'true';

                /*
                 * Placeholder follows the current organisation language.
                 * Existing populated values are never altered here.
                 */
                input.placeholder =
                    datePlaceholder();

                input.addEventListener(
                    'input',
                    () => {
                        const digits =
                            input.value
                                .replace(
                                    /\D/g,
                                    ''
                                )
                                .slice(
                                    0,
                                    8
                                );

                        const separator =
                            dateSeparator();

                        let formatted =
                            digits.slice(
                                0,
                                2
                            );

                        if (
                            digits.length
                            > 2
                        ) {
                            formatted +=
                                separator
                                + digits.slice(
                                    2,
                                    4
                                );
                        }

                        if (
                            digits.length
                            > 4
                        ) {
                            formatted +=
                                separator
                                + digits.slice(
                                    4,
                                    8
                                );
                        }

                        input.value =
                            formatted;
                    }
                );
            }
        );
}


/*
|--------------------------------------------------------------------------
| Shared Patrimoine Calendar Picker
|--------------------------------------------------------------------------
|
| Patrimoine uses one application-owned calendar popup for all business
| date fields.
|
| This avoids browser-native date-field formatting inconsistencies while
| preserving:
|
| French:  DD-MM-YYYY
| English: DD/MM/YYYY
| API:     YYYY-MM-DD
|
*/

let activeCalendarInput = null;
let activeCalendarDate = null;
let calendarDocumentListenersInitialized = false;


/**
 * Parse ISO YYYY-MM-DD into a local Date.
 *
 * @param {string|null|undefined} value
 * @returns {Date|null}
 */
function dateFromIso(
    value
) {
    const match =
        String(
            value
            ?? ''
        ).match(
            /^(\d{4})-(\d{2})-(\d{2})$/
        );

    if (! match) {
        return null;
    }

    const date =
        new Date(
            Number(match[1]),
            Number(match[2]) - 1,
            Number(match[3])
        );

    return Number.isNaN(
        date.getTime()
    )
        ? null
        : date;
}


/**
 * Convert a local Date into ISO YYYY-MM-DD.
 *
 * @param {Date} date
 * @returns {string}
 */
function isoFromDate(
    date
) {
    const year =
        date.getFullYear();

    const month =
        String(
            date.getMonth() + 1
        ).padStart(
            2,
            '0'
        );

    const day =
        String(
            date.getDate()
        ).padStart(
            2,
            '0'
        );

    return `${year}-${month}-${day}`;
}


/**
 * Return the shared popup element, creating it once.
 *
 * @returns {HTMLDivElement}
 */
function calendarElement() {
    let calendar =
        document.getElementById(
            'pm-shared-calendar'
        );

    if (calendar) {
        return calendar;
    }

    calendar =
        document.createElement(
            'div'
        );

    calendar.id =
        'pm-shared-calendar';

    calendar.className =
        'pm-calendar-popover';

    calendar.hidden =
        true;

    calendar.setAttribute(
        'role',
        'dialog'
    );

    calendar.setAttribute(
        'aria-modal',
        'false'
    );

    document.body.appendChild(
        calendar
    );

    if (
        ! calendarDocumentListenersInitialized
    ) {
        calendarDocumentListenersInitialized =
            true;

        document.addEventListener(
            'mousedown',
            (event) => {
                if (
                    calendar.hidden
                    || calendar.contains(
                        event.target
                    )
                    || activeCalendarInput
                        ?.closest(
                            '.pm-date-control, .pm-lease-date-control, .pm-payment-date-control, .pm-security-date-control, .pm-tenant-funds-date-control, .relative'
                        )
                        ?.contains(
                            event.target
                        )
                ) {
                    return;
                }

                closeDatePicker();
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key === 'Escape'
                    && ! calendar.hidden
                ) {
                    closeDatePicker();

                    activeCalendarInput
                        ?.focus();
                }
            }
        );

        window.addEventListener(
            'resize',
            () => {
                if (! calendar.hidden) {
                    positionCalendar(
                        calendar,
                        activeCalendarInput
                    );
                }
            }
        );

        window.addEventListener(
            'scroll',
            () => {
                if (! calendar.hidden) {
                    positionCalendar(
                        calendar,
                        activeCalendarInput
                    );
                }
            },
            true
        );
    }

    return calendar;
}


/**
 * Localized weekday labels.
 *
 * @returns {string[]}
 */
function calendarWeekdays() {
    return usesFrenchDateFormat()
        ? [
            'Lu',
            'Ma',
            'Me',
            'Je',
            'Ve',
            'Sa',
            'Di',
        ]
        : [
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun',
        ];
}


/**
 * Localized month heading.
 *
 * @param {Date} date
 * @returns {string}
 */
function calendarMonthLabel(
    date
) {
    return new Intl.DateTimeFormat(
        usesFrenchDateFormat()
            ? 'fr-FR'
            : 'en-GB',
        {
            month: 'long',
            year: 'numeric',
        }
    ).format(
        date
    );
}


/**
 * Render the active month.
 *
 * @param {HTMLDivElement} calendar
 */
function renderCalendar(
    calendar
) {
    if (
        ! activeCalendarDate
        || ! activeCalendarInput
    ) {
        return;
    }

    const viewYear =
        activeCalendarDate
            .getFullYear();

    const viewMonth =
        activeCalendarDate
            .getMonth();

    const firstDay =
        new Date(
            viewYear,
            viewMonth,
            1
        );

    const daysInMonth =
        new Date(
            viewYear,
            viewMonth + 1,
            0
        ).getDate();

    /*
     * Convert JavaScript Sunday=0 into Monday=0.
     */
    const leadingBlankDays =
        (
            firstDay.getDay()
            + 6
        ) % 7;

    const selectedIso =
        dateForApi(
            activeCalendarInput.value
        );

    const todayIso =
        localToday();

    let dayButtons =
        '';

    for (
        let index = 0;
        index < leadingBlankDays;
        index += 1
    ) {
        dayButtons +=
            '<span class="pm-calendar-empty"></span>';
    }

    for (
        let day = 1;
        day <= daysInMonth;
        day += 1
    ) {
        const date =
            new Date(
                viewYear,
                viewMonth,
                day
            );

        const iso =
            isoFromDate(
                date
            );

        const classes = [
            'pm-calendar-day',
        ];

        if (iso === todayIso) {
            classes.push(
                'pm-calendar-day-today'
            );
        }

        if (iso === selectedIso) {
            classes.push(
                'pm-calendar-day-selected'
            );
        }

        dayButtons += `
            <button
                type="button"
                class="${classes.join(' ')}"
                data-pm-calendar-date="${iso}"
            >
                ${day}
            </button>
        `;
    }

    calendar.innerHTML = `
        <div class="pm-calendar-header">
            <button
                type="button"
                class="pm-calendar-nav"
                data-pm-calendar-previous
                aria-label="${usesFrenchDateFormat() ? 'Mois précédent' : 'Previous month'}"
            >
                ${icon('chevron-left')}
            </button>

            <div class="pm-calendar-month">
                ${calendarMonthLabel(
                    activeCalendarDate
                )}
            </div>

            <button
                type="button"
                class="pm-calendar-nav"
                data-pm-calendar-next
                aria-label="${usesFrenchDateFormat() ? 'Mois suivant' : 'Next month'}"
            >
                ${icon('chevron-right')}
            </button>
        </div>

        <div class="pm-calendar-weekdays">
            ${calendarWeekdays()
                .map(
                    (weekday) =>
                        `<span>${weekday}</span>`
                )
                .join('')}
        </div>

        <div class="pm-calendar-grid">
            ${dayButtons}
        </div>

        <div class="pm-calendar-footer">
            <button
                type="button"
                class="pm-calendar-today"
                data-pm-calendar-today
            >
                ${usesFrenchDateFormat() ? "Aujourd'hui" : 'Today'}
            </button>
        </div>
    `;

    calendar
        .querySelector(
            '[data-pm-calendar-previous]'
        )
        ?.addEventListener(
            'click',
            () => {
                activeCalendarDate =
                    new Date(
                        viewYear,
                        viewMonth - 1,
                        1
                    );

                renderCalendar(
                    calendar
                );
            }
        );

    calendar
        .querySelector(
            '[data-pm-calendar-next]'
        )
        ?.addEventListener(
            'click',
            () => {
                activeCalendarDate =
                    new Date(
                        viewYear,
                        viewMonth + 1,
                        1
                    );

                renderCalendar(
                    calendar
                );
            }
        );

    calendar
        .querySelector(
            '[data-pm-calendar-today]'
        )
        ?.addEventListener(
            'click',
            () => {
                selectCalendarDate(
                    localToday()
                );
            }
        );

    calendar
        .querySelectorAll(
            '[data-pm-calendar-date]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        selectCalendarDate(
                            button.dataset
                                .pmCalendarDate
                        );
                    }
                );
            }
        );
}


/**
 * Position calendar under the active field while keeping it in viewport.
 *
 * @param {HTMLDivElement} calendar
 * @param {HTMLInputElement|null} input
 */
function positionCalendar(
    calendar,
    input
) {
    if (! input) {
        return;
    }

    const rect =
        input.getBoundingClientRect();

    const calendarRect =
        calendar.getBoundingClientRect();

    const margin =
        8;

    let left =
        rect.left;

    let top =
        rect.bottom
        + margin;

    if (
        left + calendarRect.width
        > window.innerWidth - margin
    ) {
        left =
            window.innerWidth
            - calendarRect.width
            - margin;
    }

    left =
        Math.max(
            margin,
            left
        );

    if (
        top + calendarRect.height
        > window.innerHeight - margin
    ) {
        top =
            rect.top
            - calendarRect.height
            - margin;
    }

    top =
        Math.max(
            margin,
            top
        );

    calendar.style.left =
        `${Math.round(left)}px`;

    calendar.style.top =
        `${Math.round(top)}px`;
}


/**
 * Commit one selected date.
 *
 * @param {string} iso
 */
function selectCalendarDate(
    iso
) {
    if (! activeCalendarInput) {
        return;
    }

    activeCalendarInput.value =
        dateForDisplay(
            iso
        );

    activeCalendarInput.dispatchEvent(
        new Event(
            'change',
            {
                bubbles: true,
            }
        )
    );

    closeDatePicker();

    activeCalendarInput
        ?.focus();
}


/**
 * Open the shared Patrimoine calendar for one visible input.
 *
 * @param {HTMLInputElement|string} inputOrId
 */
export function openDatePicker(
    inputOrId
) {
    const input =
        typeof inputOrId === 'string'
            ? document.getElementById(
                inputOrId
            )
            : inputOrId;

    if (
        ! input
        || input.disabled
        || input.readOnly
    ) {
        return;
    }

    activeCalendarInput =
        input;

    const selected =
        dateFromIso(
            dateForApi(
                input.value
            )
        );

    const today =
        dateFromIso(
            localToday()
        );

    activeCalendarDate =
        selected
        || today
        || new Date();

    activeCalendarDate =
        new Date(
            activeCalendarDate
                .getFullYear(),
            activeCalendarDate
                .getMonth(),
            1
        );

    const calendar =
        calendarElement();

    renderCalendar(
        calendar
    );

    calendar.hidden =
        false;

    /*
     * Position after display so dimensions are measurable.
     */
    requestAnimationFrame(
        () => {
            positionCalendar(
                calendar,
                input
            );
        }
    );
}


/**
 * Close the shared calendar.
 */
export function closeDatePicker() {
    const calendar =
        document.getElementById(
            'pm-shared-calendar'
        );

    if (calendar) {
        calendar.hidden =
            true;
    }
}


/**
 * Return true when another workspace already provides a calendar button.
 *
 * @param {HTMLInputElement} input
 * @returns {boolean}
 */
function hasExistingDateCalendar(
    input
) {
    if (! input.id) {
        return false;
    }

    const id =
        CSS.escape(
            input.id
        );

    return Boolean(
        document.querySelector(
            `[data-lease-date-picker="${id}"], `
            + `[data-payment-date-picker="${id}"], `
            + `[data-security-date-picker="${id}"], `
            + `[data-tenant-funds-date-picker="${id}"], `
            + `[data-report-date-picker="${id}"], `
            + `[data-pm-date-picker="${id}"]`
        )
    );
}


/**
 * Add the generic Patrimoine calendar button to shared date fields.
 *
 * @param {HTMLInputElement} input
 */
function ensureDateCalendarPicker(
    input
) {
    if (
        ! input
        || ! input.id
        || hasExistingDateCalendar(
            input
        )
    ) {
        return;
    }

    const originalParent =
        input.parentNode;

    if (! originalParent) {
        return;
    }

    const wrapper =
        document.createElement(
            'div'
        );

    wrapper.className =
        'pm-date-control';

    originalParent.insertBefore(
        wrapper,
        input
    );

    wrapper.appendChild(
        input
    );

    const button =
        document.createElement(
            'button'
        );

    button.type =
        'button';

    button.className =
        'pm-date-picker-button';

    button.dataset.pmDatePicker =
        input.id;

    button.setAttribute(
        'aria-label',
        usesFrenchDateFormat()
            ? 'Choisir une date'
            : 'Choose date'
    );

    button.innerHTML = `
        ${icon('calendar')}
    `;

    button.addEventListener(
        'click',
        () => {
            openDatePicker(
                input
            );
        }
    );

    wrapper.appendChild(
        button
    );
}
