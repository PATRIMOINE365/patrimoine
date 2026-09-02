/*
 * Writing an amount.
 *
 * The currency belongs to the managing organisation - GHS by default, but
 * it is a setting, not a constant - so it is READ, never assumed. A figure
 * rendered with the wrong symbol is a worse defect than one rendered with
 * none: it is confidently wrong, and it is money.
 *
 * Amounts arrive as whole currency units, the same integers the API
 * validates on the way in, so nothing here divides by a hundred.
 */

import { language, t } from '../i18n/index.js';

let currency = null;

/*
 * A three-letter ISO 4217 code is the only thing Intl accepts. The
 * organisation's setting is NOT guaranteed to be one: this account's is
 * "FCFA", which is what people call the West African franc, whose actual
 * code is XOF. Intl throws on it, and rejecting it outright - which is what
 * this did first - dropped the currency from every figure in the
 * application and left bare numbers on a money screen.
 *
 * So anything that is not an ISO code is kept and used as a LABEL. The
 * organisation said what its money is called; writing it is more honest
 * than writing nothing.
 */
const ISO_CODE = /^[A-Z]{3}$/;

/** Called once the organisation is known. */
export function setCurrency(code) {
    currency = typeof code === 'string' && code.trim() !== '' ? code.trim() : null;
}

export function currencyCode() {
    return currency;
}

/**
 * @param {number|string|null} amount  whole currency units
 * @returns {string} e.g. "GHS 42,300", or the bare number if no currency is known
 */
export function money(amount) {
    if (amount === null || amount === undefined || amount === '') {
        return '';
    }

    const value = Number(amount);

    if (Number.isNaN(value)) {
        return String(amount);
    }

    const grouped = new Intl.NumberFormat(language(), {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);

    if (currency === null) {
        /*
         * Grouped, but unlabelled. Better a number nobody can mistake for a
         * currency than a guess at a symbol.
         */
        return grouped;
    }

    if (! ISO_CODE.test(currency.toUpperCase())) {
        /* A name, not a code: "FCFA 42,300". */
        return `${currency} ${grouped}`;
    }

    try {
        return new Intl.NumberFormat(language(), {
            style: 'currency',
            currency: currency.toUpperCase(),
            /* Whole units in, whole units out. */
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    } catch {
        /* A well-formed code Intl still will not take. */
        return `${currency} ${grouped}`;
    }
}

/** "Due in 3 days", "Today", "5 days ago" - for a date the API returned. */
export function relativeDays(isoDate) {
    if (! isoDate) {
        return null;
    }

    const then = new Date(`${isoDate}T00:00:00`);

    if (Number.isNaN(then.getTime())) {
        return null;
    }

    const today = new Date();

    today.setHours(0, 0, 0, 0);

    const days = Math.round((then - today) / 86_400_000);

    return new Intl.RelativeTimeFormat(language(), { numeric: 'auto' }).format(days, 'day');
}

/** A date written out: "Sep 5, 2026". */
export function shortDate(isoDate) {
    if (! isoDate) {
        return '';
    }

    const date = new Date(`${isoDate}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return String(isoDate);
    }

    return new Intl.DateTimeFormat(language(), { dateStyle: 'medium' }).format(date);
}

/**
 * When something happened, written the way the design does it: "Today,
 * 8:45 AM" for today, "Yesterday, 4:30 PM" for yesterday, and the date
 * otherwise. A full timestamp on every row is both longer than it needs to
 * be and squeezes the text beside it into a bad wrap.
 */
export function whenLabel(isoTimestamp) {
    if (! isoTimestamp) {
        return null;
    }

    const at = new Date(isoTimestamp);

    if (Number.isNaN(at.getTime())) {
        return String(isoTimestamp);
    }

    const midnight = new Date();

    midnight.setHours(0, 0, 0, 0);

    const days = Math.floor((midnight - at) / 86_400_000);
    const time = new Intl.DateTimeFormat(language(), { timeStyle: 'short' }).format(at);

    if (days < 0) {
        return `${t('when.today')}, ${time}`;
    }

    if (days < 1) {
        return `${t('when.yesterday')}, ${time}`;
    }

    return new Intl.DateTimeFormat(language(), { dateStyle: 'medium' }).format(at);
}
