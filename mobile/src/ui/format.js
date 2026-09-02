/*
 * Dates and numbers written the way the browser application writes them.
 *
 * The web formats a date as DD/MM/YYYY in English and DD-MM-YYYY in
 * French, and a number with the organisation's own grouping. A figure
 * that reads one way on a screen and another on the PDF beside it looks
 * like an error, so the tablet follows the same rules rather than the
 * handset's locale.
 */

import { language, t } from '../i18n/index.js';

/** "2026-09-05" -> "05/09/2026" (en) or "05-09-2026" (fr). Anything else returns as given. */
export function formatDate(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value));

    if (match === null) {
        return String(value);
    }

    const separator = language() === 'fr' ? '-' : '/';

    return `${match[3]}${separator}${match[2]}${separator}${match[1]}`;
}

/** A timestamp with its time, in the organisation's language. */
export function formatDateTime(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat(language() === 'fr' ? 'fr-FR' : 'en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
    }).format(date);
}

/** Weekday, day, long month and year: "Monday 2 September 2026". */
export function formatLongDate(value) {
    if (! value) {
        return '';
    }

    const date = new Date(String(value).length === 10 ? `${value}T00:00:00` : value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    const written = new Intl.DateTimeFormat(language() === 'fr' ? 'fr-FR' : 'en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    }).format(date);

    return written.replace(/(^|\s)(\p{L})/gu, (all, space, letter) => `${space}${letter.toUpperCase()}`);
}

export function formatNumber(value) {
    if (value === null || value === undefined || value === '') {
        return '0';
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return String(value);
    }

    return new Intl.NumberFormat(language() === 'fr' ? 'fr-FR' : 'en-GB', { maximumFractionDigits: 2 }).format(number);
}

export function percent(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `${formatNumber(value)}%`;
}

/** Today as YYYY-MM-DD. */
export function isoToday() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/** Add a number of days to a YYYY-MM-DD date. */
export function addDays(iso, days) {
    const date = new Date(`${iso}T00:00:00`);

    date.setDate(date.getDate() + days);

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** Add months and subtract one day, as the lease assistant computes an end date. */
export function endDateAfterMonths(startIso, months) {
    const date = new Date(`${startIso}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    date.setMonth(date.getMonth() + months);
    date.setDate(date.getDate() - 1);

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** "Bank transfer" from "bank_transfer", when no catalogue names it. */
export function prettify(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const words = String(value).replace(/[_-]+/g, ' ').trim();

    return words.charAt(0).toUpperCase() + words.slice(1);
}

/**
 * A domain value through the web catalogue: `domain('tenants.payment_method', 'momo')`
 * reads ui.tenants.payment_method.momo and falls back to a prettified word.
 */
export function domain(prefix, value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const key = `ui.${prefix}.${value}`;
    const found = t(key);

    return found === key ? prettify(value) : found;
}

export function joinParts(parts, separator = ' · ') {
    return parts.filter((part) => part !== null && part !== undefined && part !== '').join(separator);
}
