/*
 * Lift the web application's own wording into the mobile client.
 *
 * The tablet mirrors the browser application screen for screen, and Komla's
 * rule is that a thing is called the same on every surface. Rather than
 * hand-copy two thousand strings in two languages - and drift the first
 * time one is reworded - this reads lang/{en,fr}/*.php at build time and
 * emits them as one flat catalogue, keyed the way Laravel keys them:
 * "ui.navigation.dashboard", "reports.arrears.title".
 *
 * The files are plain `return [ ... ];` arrays of quoted strings, nothing
 * else - checked before this was written - so a small tokenizer is enough
 * and the build machine needs no PHP. The Mac does not have one.
 *
 * Runs before dev and build, alongside the design tokens and the icons.
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const langRoot = resolve(here, '../../lang');
const target = resolve(here, '../src/generated/web-strings.js');

const LANGUAGES = ['en', 'fr'];
const FILES = ['ui', 'reports', 'business', 'activity_log', 'financial_journal', 'documents', 'errors'];

/* ------------------------------------------------------------ tokenizer */

function tokenize(source) {
    const tokens = [];
    let i = 0;

    const peek = (n = 0) => source[i + n];

    while (i < source.length) {
        const ch = peek();

        if (/\s/.test(ch)) {
            i += 1;
            continue;
        }

        /* Comments: // to end of line, # to end of line, and block comments. */
        if ((ch === '/' && peek(1) === '/') || ch === '#') {
            while (i < source.length && source[i] !== '\n') {
                i += 1;
            }

            continue;
        }

        if (ch === '/' && peek(1) === '*') {
            const end = source.indexOf('*/', i + 2);

            i = end === -1 ? source.length : end + 2;
            continue;
        }

        if (ch === "'" || ch === '"') {
            const quote = ch;
            let value = '';

            i += 1;

            while (i < source.length && source[i] !== quote) {
                if (source[i] === '\\') {
                    const next = source[i + 1];

                    if (quote === "'") {
                        /* Single quotes escape only \' and \\. */
                        if (next === "'" || next === '\\') {
                            value += next;
                            i += 2;
                        } else {
                            value += '\\';
                            i += 1;
                        }
                    } else {
                        const map = { n: '\n', t: '\t', r: '\r', '"': '"', '\\': '\\', $: '$' };

                        value += map[next] ?? `\\${next}`;
                        i += 2;
                    }

                    continue;
                }

                value += source[i];
                i += 1;
            }

            i += 1;
            tokens.push({ type: 'string', value });
            continue;
        }

        if (ch === '=' && peek(1) === '>') {
            tokens.push({ type: 'arrow' });
            i += 2;
            continue;
        }

        if (ch === '[' || ch === ']' || ch === ',' || ch === ';' || ch === '(' || ch === ')') {
            tokens.push({ type: ch });
            i += 1;
            continue;
        }

        /* Bare words: <?php, return, array, and nothing else expected. */
        const word = /^[A-Za-z_?<][A-Za-z0-9_?]*/.exec(source.slice(i));

        if (word !== null) {
            tokens.push({ type: 'word', value: word[0] });
            i += word[0].length;
            continue;
        }

        const number = /^-?\d+(\.\d+)?/.exec(source.slice(i));

        if (number !== null) {
            tokens.push({ type: 'string', value: number[0] });
            i += number[0].length;
            continue;
        }

        throw new Error(`Unexpected character ${JSON.stringify(ch)} at offset ${i}`);
    }

    return tokens;
}

/* --------------------------------------------------------------- parser */

function parse(source, file) {
    const tokens = tokenize(source);
    let pos = 0;

    const next = () => tokens[pos++];
    const peek = () => tokens[pos];

    function expect(type) {
        const token = next();

        if (token === undefined || token.type !== type) {
            throw new Error(`${file}: expected ${type}, got ${token?.type ?? 'end'} at token ${pos}`);
        }

        return token;
    }

    function array() {
        /* `[` ... `]` or `array(` ... `)` */
        const opener = next();
        const closer = opener.type === '[' ? ']' : ')';

        if (opener.type === 'word' && opener.value === 'array') {
            expect('(');
        } else if (opener.type !== '[') {
            throw new Error(`${file}: expected an array at token ${pos}`);
        }

        const result = {};
        let index = 0;

        while (peek() !== undefined && peek().type !== closer) {
            let key;
            let value;

            const first = next();

            if (peek()?.type === 'arrow') {
                next();
                key = first.value;
                value = valueFrom(next());
            } else {
                key = String(index);
                index += 1;
                value = valueFrom(first);
            }

            result[key] = value;

            if (peek()?.type === ',') {
                next();
            }
        }

        expect(closer);

        return result;
    }

    function valueFrom(token) {
        if (token.type === 'string') {
            return token.value;
        }

        if (token.type === '[' || (token.type === 'word' && token.value === 'array')) {
            pos -= 1;

            return array();
        }

        if (token.type === 'word' && ['true', 'false', 'null'].includes(token.value)) {
            return token.value;
        }

        throw new Error(`${file}: unexpected ${token.type} ${token.value ?? ''} at token ${pos}`);
    }

    /* <?php ... return [ ... ]; */
    while (peek() !== undefined && ! (peek().type === 'word' && peek().value === 'return')) {
        next();
    }

    expect('word');

    return array();
}

function flatten(tree, prefix, into) {
    for (const [key, value] of Object.entries(tree)) {
        const path = `${prefix}.${key}`;

        if (typeof value === 'object') {
            flatten(value, path, into);
        } else {
            into[path] = value;
        }
    }

    return into;
}

/* ----------------------------------------------------------------- main */

const catalogues = {};

/*
 * The browser's runtime catalogue, resources/js/translations.js, is a
 * SUPERSET of ui.php: every drawer the web JavaScript renders - lease
 * termination, extension, deletion, the settlement - has its wording only
 * there. It is a plain ES module with no imports, so it is loaded as one
 * and merged in under the same "ui." prefix, and it wins where both have a
 * key, because it is what the browser actually shows.
 */
const runtime = (await import(pathToFileURL(resolve(here, '../../resources/js/translations.js')).href)).translations;

/*
 * The Help page keeps its own wording in a literal inside resources/js/
 * help.js, deliberately outside translations.js. That file touches the DOM
 * and cannot be imported here, so the literal is cut out of the source and
 * evaluated on its own - it is a plain object of strings.
 */
const helpSource = readFileSync(resolve(here, '../../resources/js/help.js'), 'utf8');
const helpStart = helpSource.indexOf('const helpTranslations = {');
const helpEnd = helpSource.indexOf('\n};', helpStart);
const helpTranslations = helpStart === -1 || helpEnd === -1
    ? {}
    : new Function(`return ${helpSource.slice(helpStart + 'const helpTranslations = '.length, helpEnd + 2)};`)();

for (const language of LANGUAGES) {
    catalogues[language] = {};

    for (const file of FILES) {
        const path = resolve(langRoot, language, `${file}.php`);

        if (! existsSync(path)) {
            continue;
        }

        flatten(parse(readFileSync(path, 'utf8'), `${language}/${file}.php`), file, catalogues[language]);
    }

    for (const [key, value] of Object.entries(runtime[language] ?? {})) {
        catalogues[language][`ui.${key}`] = value;
    }

    for (const [key, value] of Object.entries(helpTranslations[language] ?? {})) {
        catalogues[language][`ui.${key}`] = value;
    }
}

const counts = LANGUAGES.map((language) => `${language}: ${Object.keys(catalogues[language]).length}`).join(', ');

const out = `/*
 * GENERATED - do not edit.
 *
 * Source: lang/{en,fr}/{${FILES.join(',')}}.php + resources/js/translations.js + resources/js/help.js
 * Regenerate: node scripts/build-web-strings.mjs
 *
 * The browser application's own wording, flattened to Laravel's dotted
 * keys. Every label the tablet shares with the web is read from here so
 * the two cannot disagree.
 */

export const WEB_STRINGS = ${JSON.stringify(catalogues, null, 4)};
`;

mkdirSync(dirname(target), { recursive: true });
writeFileSync(target, out);

console.log(`build-web-strings: ${counts} -> src/generated/web-strings.js`);

/*
 * The country list travels the same way: the telephone field needs the
 * same 244 entries and dialling codes the browser's picker has, and the
 * file is a plain ES module, so it is copied rather than re-derived.
 */
const countriesSource = resolve(here, '../../resources/js/countries.js');
const countriesTarget = resolve(here, '../src/generated/countries.js');

writeFileSync(countriesTarget, `/* GENERATED - do not edit. Source: resources/js/countries.js */\n${readFileSync(countriesSource, 'utf8')}`);
console.log('build-web-strings: countries -> src/generated/countries.js');
