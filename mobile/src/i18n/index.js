/*
 * Interface wording only.
 *
 * Anything the server can say - every failure, every PM-code sentence -
 * arrives already written in the requested language and is displayed as
 * received. Translating a server message here would produce a sentence the
 * error-code catalogue cannot match back to its code.
 *
 * TWO CATALOGUES, ONE LOOKUP. The mobile client's own strings live in
 * en.js/fr.js. Everything it shares with the browser application - a
 * screen title, a column header, a drawer's field labels, a confirmation
 * sentence - is read from the browser application's OWN lang files,
 * compiled at build time into generated/web-strings.js and addressed by
 * Laravel's dotted key, ui.leases.<name>. One vocabulary,
 * and a reword on the web reaches the tablet on the next build.
 */

import { en } from './en.js';
import { fr } from './fr.js';
import { WEB_STRINGS } from '../generated/web-strings.js';

const catalogues = { en, fr };

let current = 'en';

export function setLanguage(language) {
    current = catalogues[language] === undefined ? 'en' : language;

    if (typeof document !== 'undefined') {
        document.documentElement.lang = current;
    }

    return current;
}

export function language() {
    return current;
}

/** Pick the best supported language from the device's own preferences. */
export function preferredLanguage(deviceLanguages, supported = ['en', 'fr']) {
    for (const tag of deviceLanguages ?? []) {
        const base = String(tag).toLowerCase().split('-')[0];

        if (supported.includes(base)) {
            return base;
        }
    }

    return 'en';
}

/** Does either catalogue know this key, in this language? */
export function has(key, lang = current) {
    return catalogues[lang]?.[key] !== undefined || WEB_STRINGS[lang]?.[key] !== undefined;
}

function lookup(key) {
    return catalogues[current]?.[key]
        ?? WEB_STRINGS[current]?.[key]
        ?? catalogues.en[key]
        ?? WEB_STRINGS.en?.[key]
        ?? key;
}

function replace(line, replacements) {
    /*
     * Laravel writes placeholders as :name, and also :Name / :NAME for a
     * capitalised or upper-cased value. The longest names are replaced
     * first so ":count" cannot eat the front of ":count_label".
     */
    return Object.entries(replacements)
        .sort(([a], [b]) => b.length - a.length)
        .reduce((text, [name, value]) => {
            const written = String(value ?? '');

            return text
                /* The browser's runtime catalogue writes some as {name}. */
                .replaceAll(`{${name}}`, written)
                .replaceAll(`:${name.toUpperCase()}`, written.toUpperCase())
                .replaceAll(`:${name.charAt(0).toUpperCase()}${name.slice(1)}`, written.charAt(0).toUpperCase() + written.slice(1))
                .replaceAll(`:${name}`, written);
        }, line);
}

export function t(key, replacements = {}) {
    return replace(lookup(key), replacements);
}

/**
 * Laravel's `trans_choice` shape: "one thing|:count things", or the
 * bracketed "{0} none|{1} one|[2,*] many". Enough of it for the catalogue.
 */
export function tc(key, count, replacements = {}) {
    const parts = lookup(key).split('|');
    let chosen = parts[parts.length - 1];

    if (parts.length === 2 && ! /^[{[]/.test(parts[0])) {
        chosen = count === 1 ? parts[0] : parts[1];
    } else {
        for (const part of parts) {
            const exact = /^\{(\d+)\}\s*(.*)$/.exec(part);

            if (exact !== null && Number(exact[1]) === count) {
                chosen = exact[2];
                break;
            }

            const range = /^\[(\d+),(\d+|\*)\]\s*(.*)$/.exec(part);

            if (range !== null && count >= Number(range[1]) && (range[2] === '*' || count <= Number(range[2]))) {
                chosen = range[3];
                break;
            }
        }
    }

    return replace(chosen, { count, ...replacements });
}
