/*
 * Interface wording only.
 *
 * Anything the server can say - every failure, every PM-code sentence -
 * arrives already written in the requested language and is displayed as
 * received. Translating a server message here would produce a sentence the
 * error-code catalogue cannot match back to its code.
 */

import { en } from './en.js';
import { fr } from './fr.js';

const catalogues = { en, fr };

let current = 'en';

export function setLanguage(language) {
    current = catalogues[language] === undefined ? 'en' : language;

    document.documentElement.lang = current;

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

export function t(key, replacements = {}) {
    const line = catalogues[current]?.[key] ?? catalogues.en[key] ?? key;

    return Object.entries(replacements).reduce(
        (text, [name, value]) => text.replaceAll(`:${name}`, String(value)),
        line
    );
}
