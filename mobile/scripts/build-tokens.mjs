/*
 * Generate the mobile design tokens from the product's own stylesheets.
 *
 * WHY THIS EXISTS. The application's design system is one source of truth -
 * Untitled UI structure over Patrimoine Brand Package colour - and the
 * mobile client must not fork it into a second, drifting copy. But it
 * cannot import those files directly either:
 *
 *   - scale.css declares the ramps inside Tailwind 4's `@theme` block, and
 *     Tailwind 4 itself supports Safari 16.4+. Our floor is iOS 15.8,
 *     which is Safari 15.6.
 *   - tokens.css resolves 41 values through `color-mix()`, supported only
 *     from Safari 16.2. On a 15.8 handset each one is an invalid value, so
 *     the token silently falls back and the colour is simply wrong.
 *
 * So the ramps and the semantic tokens are read from the real files and
 * emitted as plain CSS custom properties with every color-mix() already
 * computed. Re-run whenever scale.css or tokens.css changes:
 *
 *     npm run build:tokens        (also runs automatically before dev/build)
 */

import { readFileSync, writeFileSync, mkdirSync, copyFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const css = (name) => resolve(here, '../../resources/css', name);
const out = resolve(here, '../src/generated/design-tokens.css');

/* ------------------------------------------------------------------ ramps */

/*
 * scale.css keeps the ramps in `@theme { … }`. Everything inside is an
 * ordinary custom-property declaration; only the wrapper is Tailwind.
 */
function themeBlock(source) {
    const start = source.indexOf('@theme');

    if (start === -1) {
        throw new Error('scale.css no longer has an @theme block');
    }

    let depth = 0;

    for (let i = source.indexOf('{', start); i < source.length; i += 1) {
        if (source[i] === '{') {
            depth += 1;
        } else if (source[i] === '}') {
            depth -= 1;

            if (depth === 0) {
                return source.slice(source.indexOf('{', start) + 1, i);
            }
        }
    }

    throw new Error('unterminated @theme block');
}

function declarations(block) {
    const map = new Map();

    for (const [, name, value] of block.matchAll(/(--[\w-]+)\s*:\s*([^;]+);/g)) {
        map.set(name, value.trim());
    }

    return map;
}

/* ----------------------------------------------------------------- colour */

function parseHex(hex) {
    const clean = hex.replace('#', '').trim();
    const full = clean.length === 3
        ? clean.split('').map((c) => c + c).join('')
        : clean;

    return [
        parseInt(full.slice(0, 2), 16),
        parseInt(full.slice(2, 4), 16),
        parseInt(full.slice(4, 6), 16),
    ];
}

/* Follow var() chains down to a literal. Returns null for anything else. */
function literal(value, ramps, seen = new Set()) {
    const text = String(value).trim();

    if (text.startsWith('#')) {
        return parseHex(text);
    }

    const reference = text.match(/^var\((--[\w-]+)\)$/);

    if (reference === null) {
        return null;
    }

    const name = reference[1];

    if (seen.has(name)) {
        return null;
    }

    seen.add(name);

    const next = ramps.get(name);

    return next === undefined ? null : literal(next, ramps, seen);
}

/*
 * Two shapes appear in tokens.css and both are exactly defined by the spec
 * for `in srgb`:
 *
 *   color-mix(in srgb, C p%, transparent)  -> C at alpha p/100
 *   color-mix(in srgb, A p%, B)            -> channel-wise A*p + B*(1-p)
 */
function resolveMix(expression, ramps) {
    const inner = expression.slice(expression.indexOf(',') + 1, -1);
    const [first, second] = inner.split(/,(?![^(]*\))/).map((part) => part.trim());

    const firstMatch = first.match(/^(.*?)\s+([\d.]+)%$/);

    if (firstMatch === null) {
        return null;
    }

    const colour = literal(firstMatch[1], ramps);
    const share = parseFloat(firstMatch[2]) / 100;

    if (colour === null) {
        return null;
    }

    if (second === 'transparent') {
        /* Rounded to three places: enough for 8-bit output, no float noise. */
        return `rgba(${colour[0]}, ${colour[1]}, ${colour[2]}, ${Math.round(share * 1000) / 1000})`;
    }

    const other = literal(second.replace(/\s+[\d.]+%$/, ''), ramps);

    if (other === null) {
        return null;
    }

    const mixed = colour.map(
        (channel, index) => Math.round(channel * share + other[index] * (1 - share))
    );

    return `rgb(${mixed[0]}, ${mixed[1]}, ${mixed[2]})`;
}

function resolveAllMixes(source, ramps) {
    let unresolved = 0;

    /* Balanced-paren scan: color-mix() nests var(), so a regex cannot. */
    let result = '';
    let index = 0;

    while (index < source.length) {
        const start = source.indexOf('color-mix(', index);

        if (start === -1) {
            result += source.slice(index);
            break;
        }

        result += source.slice(index, start);

        let depth = 0;
        let end = start;

        for (let i = source.indexOf('(', start); i < source.length; i += 1) {
            if (source[i] === '(') {
                depth += 1;
            } else if (source[i] === ')') {
                depth -= 1;

                if (depth === 0) {
                    end = i;
                    break;
                }
            }
        }

        const expression = source.slice(start, end + 1);
        const resolved = resolveMix(expression, ramps);

        if (resolved === null) {
            unresolved += 1;
            result += expression;
        } else {
            result += resolved;
        }

        index = end + 1;
    }

    return { result, unresolved };
}

/* ------------------------------------------------------------------ build */

const scale = readFileSync(css('scale.css'), 'utf8');
const tokens = readFileSync(css('tokens.css'), 'utf8');

const ramps = declarations(themeBlock(scale));

/*
 * Drop the Tailwind-only directives. `@custom-variant` defines the panel-*
 * container-query variants, which are Safari 16 anyway and unused here.
 */
const rampCss = [...ramps]
    .map(([name, value]) => `    ${name}: ${value};`)
    .join('\n');

const { result: resolvedTokens, unresolved } = resolveAllMixes(tokens, ramps);

if (unresolved > 0) {
    console.warn(
        `build-tokens: ${unresolved} color-mix() expression(s) could not be resolved. `
        + 'They will fail on iOS 15. Check for a new value shape in tokens.css.'
    );
}

const banner = `/*
 * GENERATED - DO NOT EDIT.
 *
 * Built by mobile/scripts/build-tokens.mjs from the product's own
 * resources/css/scale.css and resources/css/tokens.css, with every
 * color-mix() computed so the result parses on Safari 15.6 (iOS 15.8).
 *
 * To change a colour, change tokens.css and re-run. Editing this file makes
 * the mobile application disagree with the product.
 */
`;

mkdirSync(dirname(out), { recursive: true });
writeFileSync(out, `${banner}\n:root {\n${rampCss}\n}\n\n${resolvedTokens}`, 'utf8');

console.log(
    `build-tokens: ${ramps.size} ramp values, `
    + `${(tokens.match(/color-mix\(/g) ?? []).length - unresolved} mixes resolved -> src/generated/design-tokens.css`
);

/* ------------------------------------------------------------------ fonts */

/*
 * Inter is self-hosted rather than fetched from Google, because a Google
 * Fonts stylesheet sends every user's IP to Google and the privacy policy
 * says there are no third parties in the page. That reasoning applies to a
 * handset at least as strongly.
 *
 * The two files are copied out of the product's public/fonts rather than
 * duplicated in version control: one axis file per unicode range covers
 * every weight, and mobile/public/fonts is ignored by git.
 */
const fontsFrom = resolve(here, '../../public/fonts');
const fontsTo = resolve(here, '../public/fonts');

mkdirSync(fontsTo, { recursive: true });

for (const file of ['inter-latin.woff2', 'inter-latin-ext.woff2', 'inter-OFL.txt']) {
    copyFileSync(resolve(fontsFrom, file), resolve(fontsTo, file));
}

console.log('build-tokens: Inter copied -> public/fonts');
