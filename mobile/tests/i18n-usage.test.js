/*
 * Every key the code asks for must exist.
 *
 * The catalogue-parity test compares the two languages to EACH OTHER, which
 * says nothing about whether the code asks for a key either of them has. So
 * when a key was renamed and one caller was missed, both catalogues agreed
 * perfectly and the screen rendered the literal text "more.signout" where a
 * person expected "Sign out".
 *
 * This reads the source and checks what it actually asks for - against the
 * client's own catalogue AND the browser application's, since the tablet
 * addresses the web's strings by their Laravel keys.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';

import { en } from '../src/i18n/en.js';
import { fr } from '../src/i18n/fr.js';
import { WEB_STRINGS } from '../src/generated/web-strings.js';

function sourceFiles(dir) {
    return readdirSync(dir).flatMap((entry) => {
        const path = join(dir, entry);

        if (statSync(path).isDirectory()) {
            /* generated/ is machine-written and holds no translation calls. */
            return entry === 'generated' ? [] : sourceFiles(path);
        }

        return path.endsWith('.js') ? [path] : [];
    });
}

function keysUsed() {
    const keys = new Set();

    for (const file of sourceFiles('src')) {
        const text = readFileSync(file, 'utf8');

        for (const match of text.matchAll(/\bt\(\s*'([a-z0-9_.]+)'/g)) {
            keys.add(match[1]);
        }
    }

    return [...keys].sort();
}

test('every key the source asks for exists in English', () => {
    const missing = keysUsed().filter((key) => en[key] === undefined && WEB_STRINGS.en[key] === undefined);

    assert.deepEqual(missing, [], `used but undefined: ${missing.join(', ')}`);
});

test('every key the source asks for exists in French', () => {
    const missing = keysUsed().filter((key) => fr[key] === undefined && WEB_STRINGS.fr[key] === undefined);

    assert.deepEqual(missing, [], `used but undefined: ${missing.join(', ')}`);
});

test('the source asks for a sensible number of keys', () => {
    /* A guard against the scan silently matching nothing at all. */
    assert.ok(keysUsed().length > 50, 'the key scan found almost nothing');
});
