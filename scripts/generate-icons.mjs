/*
 * Compile resources/icons/untitled-ui.json into resources/js/icons.js.
 *
 * The JavaScript half of the product renders a great deal of its own markup,
 * and before this it had no way to draw an icon except to paste one inline.
 * This gives it the same set Blade has, from the same file, so an icon can
 * never mean one thing on a page and another in a drawer.
 *
 *     node scripts/generate-icons.mjs
 *
 * Run it after editing the JSON. The output is checked in so a deployment
 * never has to run Node — the Plesk box does not have it.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = new URL('../', import.meta.url);
const source = fileURLToPath(new URL('resources/icons/untitled-ui.json', root));
const target = fileURLToPath(new URL('resources/js/icons.js', root));

const { icons } = JSON.parse(readFileSync(source, 'utf8'));
const names = Object.keys(icons).sort();

const body = names
    .map(name => `    ${JSON.stringify(name)}: ${JSON.stringify(icons[name])},`)
    .join('\n');

const out = `/*
 * GENERATED FILE — do not edit.
 *
 * Source: resources/icons/untitled-ui.json
 * Regenerate: node scripts/generate-icons.mjs
 *
 * The Untitled UI icon set, for the JavaScript-rendered parts of the
 * product. Blade reads the same JSON through <x-icon>.
 */

const PATHS = {
${body}
};

/**
 * The markup for one icon.
 *
 * @param {string} name  a key from resources/icons/untitled-ui.json
 * @param {object} [options]
 * @param {string} [options.class]  classes for the <svg>
 * @param {number} [options.size]   rendered size in px; 20 by default
 * @returns {string} an <svg> element, or '' if the name is unknown
 */
export function icon(name, options = {}) {
    const paths = PATHS[name];

    if (!paths) {
        /*
         * A missing icon must never break a page in front of a customer, but
         * it should be impossible to miss while building one.
         */
        if (typeof console !== 'undefined') {
            console.warn(\`[icons] unknown icon "\${name}"\`);
        }

        return '';
    }

    const size = options.size || 20;
    const className = options.class || '';

    return \`<svg class="shrink-0 \${className}" width="\${size}" height="\${size}"\`
        + ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        + ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
        + \` aria-hidden="true" focusable="false">\${paths}</svg>\`;
}

/**
 * Every icon name the set holds. Used by the icon audit in the test suite.
 */
export const iconNames = ${JSON.stringify(names, null, 4).replace(/\n/g, '\n')};

export default icon;
`;

writeFileSync(target, out);
console.log(`resources/js/icons.js — ${names.length} icons`);
