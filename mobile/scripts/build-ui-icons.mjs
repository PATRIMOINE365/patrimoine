/*
 * Compile the product's Untitled UI icon set for the mobile client.
 *
 * The set has one source of truth - resources/icons/untitled-ui.json - which
 * Blade reads through <x-icon> and the web JavaScript reads through a
 * generated resources/js/icons.js. This is the third consumer, generated the
 * same way and from the same file, so an icon cannot mean one thing on a
 * page and another in the application.
 *
 * The whole set is emitted rather than the subset in use today: it is about
 * 12 KB raw and 4 KB over the wire, and a subset would silently go stale the
 * first time a screen reaches for an icon nobody remembered to add.
 *
 * Runs automatically before dev and build, alongside the design tokens.
 */

import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const source = resolve(here, '../../resources/icons/untitled-ui.json');
const target = resolve(here, '../src/generated/icons.js');

const { icons } = JSON.parse(readFileSync(source, 'utf8'));
const names = Object.keys(icons).sort();

const body = names
    .map((name) => `    ${JSON.stringify(name)}: ${JSON.stringify(icons[name])},`)
    .join('\n');

const out = `/*
 * GENERATED - do not edit.
 *
 * Source: resources/icons/untitled-ui.json
 * Regenerate: node scripts/build-ui-icons.mjs
 *
 * Every icon is drawn on a 24 grid with a 2px stroke, round caps and round
 * joins. Nothing has a fill: colour arrives as currentColor from whatever
 * the icon sits inside, so an icon in a danger control is red without
 * knowing it.
 */

export const ICON_PATHS = {
${body}
};

export const iconNames = ${JSON.stringify(names, null, 4)};
`;

mkdirSync(dirname(target), { recursive: true });
writeFileSync(target, out);

console.log(`build-ui-icons: ${names.length} icons -> src/generated/icons.js`);
