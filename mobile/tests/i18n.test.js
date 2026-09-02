/*
 * The language catalogues.
 *
 * A syntax error in one of these does not degrade the French: it stops the
 * module graph loading and the application does not start at all, in any
 * language. That happened here - 'Aujourd'hui' written with a straight
 * apostrophe closed the string - and it happened once before in the Laravel
 * half, where lang/fr/releases.php had not parsed for two releases and
 * nothing noticed, because the only screen that asked for that group asked
 * for it in English.
 *
 * So: both catalogues are parsed, and they are compared to each other.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import { en } from '../src/i18n/en.js';
import { fr } from '../src/i18n/fr.js';

test('both catalogues parse and are not empty', () => {
    assert.ok(Object.keys(en).length > 0);
    assert.ok(Object.keys(fr).length > 0);
});

test('every English key has a French translation', () => {
    const missing = Object.keys(en).filter((key) => fr[key] === undefined);

    assert.deepEqual(missing, [], `untranslated: ${missing.join(', ')}`);
});

test('French carries no key English does not have', () => {
    const orphaned = Object.keys(fr).filter((key) => en[key] === undefined);

    assert.deepEqual(orphaned, [], `orphaned in French: ${orphaned.join(', ')}`);
});

test('no value is left empty', () => {
    for (const [name, table] of [['en', en], ['fr', fr]]) {
        const blank = Object.entries(table)
            .filter(([, value]) => String(value).trim() === '')
            .map(([key]) => key);

        assert.deepEqual(blank, [], `${name} has blank values: ${blank.join(', ')}`);
    }
});

/*
 * A placeholder that exists in one language and not the other renders the
 * literal ":name" to somebody. Both sides must interpolate the same things.
 */
test('placeholders match between the two languages', () => {
    const placeholders = (value) => (String(value).match(/:[a-z_]+/g) ?? []).sort();

    const mismatched = Object.keys(en)
        .filter((key) => fr[key] !== undefined)
        .filter((key) => placeholders(en[key]).join() !== placeholders(fr[key]).join());

    assert.deepEqual(mismatched, [], `placeholder mismatch: ${mismatched.join(', ')}`);
});
