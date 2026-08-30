/*
 * Build the browser-tab icons from the Brand Package's mark.
 *
 *     node scripts/generate-favicons.mjs
 *
 * Two sets come out of one drawing:
 *
 *   the customer set   Patrimoine Green tile, white pillars, Mint bars
 *   the admin set      the same, hue-rotated to red
 *
 * The console wears the red set so that an admin tab is identifiable at a
 * glance in a row of Patrimoine tabs. The rotation keeps saturation and
 * lightness and moves only the hue, which is what makes the two sets read as
 * the same mark in two colours rather than as two different marks.
 *
 * Output is checked in. The Plesk box has no Node, so nothing is generated
 * at deploy time.
 */
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = new URL('../', import.meta.url);
const out = fileURLToPath(new URL('public/branding/favicon/', root));
mkdirSync(out, { recursive: true });

/* ----------------------------------------------------------------- colour */

function hexToRgb(hex) {
    const h = hex.replace('#', '');
    return [0, 2, 4].map(i => parseInt(h.slice(i, i + 2), 16));
}

function rgbToHex([r, g, b]) {
    return '#' + [r, g, b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('');
}

/**
 * Move a colour's hue to red, leaving saturation and lightness alone.
 */
function rotateToRed(hex) {
    const [r, g, b] = hexToRgb(hex).map(v => v / 255);
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;

    if (max === min) return hex;

    const d = max - min;
    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

    // Hue 0 with the same s and l.
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;

    const channel = t => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    };

    return rgbToHex([channel(1 / 3), channel(0), channel(-1 / 3)].map(v => v * 255));
}

/* ------------------------------------------------------------------- mark */

const GREEN = '#123D35';   // Patrimoine Green — the tile
const MINT = '#39D6A3';    // Patrimoine Mint  — the ledger bars
const WHITE = '#FFFFFF';   // the pillars

/**
 * The small-size mark: two pillars and two ledger bars on a rounded tile.
 * Three bars turn to mud below about 32px, which is why the brand package
 * draws a separate favicon rather than scaling the full mark down.
 */
function tile(bg, bar) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="512" height="512">
  <rect width="64" height="64" rx="12" fill="${bg}"/>
  <g transform="translate(4.5,4.5) scale(0.86)">
    <rect x="3"  y="8"  width="13" height="48" rx="2" fill="${WHITE}"/>
    <rect x="48" y="8"  width="13" height="48" rx="2" fill="${WHITE}"/>
    <rect x="22" y="14" width="20" height="15" rx="2" fill="${bar}"/>
    <rect x="22" y="35" width="20" height="15" rx="2" fill="${bar}"/>
  </g>
</svg>`;
}

/* -------------------------------------------------------------------- ico */

/**
 * An .ico is a tiny header plus, in every format since Vista, whole PNG
 * files. Writing one by hand is a dozen lines and saves a dependency.
 */
function ico(entries) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0);
    header.writeUInt16LE(1, 2);
    header.writeUInt16LE(entries.length, 4);

    let offset = 6 + entries.length * 16;
    const dir = [];

    for (const { size, png } of entries) {
        const e = Buffer.alloc(16);
        e.writeUInt8(size >= 256 ? 0 : size, 0);
        e.writeUInt8(size >= 256 ? 0 : size, 1);
        e.writeUInt8(0, 2);
        e.writeUInt8(0, 3);
        e.writeUInt16LE(1, 4);
        e.writeUInt16LE(32, 6);
        e.writeUInt32LE(png.length, 8);
        e.writeUInt32LE(offset, 12);
        dir.push(e);
        offset += png.length;
    }

    return Buffer.concat([header, ...dir, ...entries.map(e => e.png)]);
}

/* ------------------------------------------------------------------- runs */

const SETS = [
    { suffix: '', bg: GREEN, bar: MINT },
    { suffix: '-admin', bg: rotateToRed(GREEN), bar: rotateToRed(MINT) },
];

for (const { suffix, bg, bar } of SETS) {
    const svg = Buffer.from(tile(bg, bar));

    const png = size =>
        sharp(svg, { density: 512 }).resize(size, size).png({ compressionLevel: 9 }).toBuffer();

    const [p16, p32, p48, p180, p192, p512] = await Promise.all(
        [16, 32, 48, 180, 192, 512].map(png)
    );

    const name = n => out + n;

    writeFileSync(name(`favicon${suffix || ''}-16.png`), p16);
    writeFileSync(name(`favicon${suffix || ''}-32.png`), p32);
    writeFileSync(name(`apple-touch-icon${suffix}.png`), p180);
    writeFileSync(name(`patrimoine-icon${suffix}-192.png`), p192);
    writeFileSync(name(`patrimoine-icon${suffix}-512.png`), p512);

    const icoBytes = ico([
        { size: 16, png: p16 },
        { size: 32, png: p32 },
        { size: 48, png: p48 },
    ]);

    if (suffix) {
        writeFileSync(name('favicon-admin.ico'), icoBytes);
    } else {
        writeFileSync(name('favicon.ico'), icoBytes);
        writeFileSync(fileURLToPath(new URL('public/favicon.ico', root)), icoBytes);
    }

    console.log(`${suffix || 'customer'}: tile ${bg}, bars ${bar}`);
}

/*
 * The full three-bar mark, for the places with room for it: the e-mail
 * letterhead and the PDF documents, neither of which can read a CSS
 * variable, so each ground gets its own file.
 */
const fullMark = (pillar, barColour) =>
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" role="img" aria-label="Patrimoine 365">
  <title>Patrimoine 365</title>
  <rect x="2"  y="4"  width="10" height="56" rx="2" fill="${pillar}"/>
  <rect x="52" y="4"  width="10" height="56" rx="2" fill="${pillar}"/>
  <rect x="18" y="9"  width="28" height="10" rx="2" fill="${barColour}"/>
  <rect x="18" y="27" width="28" height="10" rx="2" fill="${barColour}"/>
  <rect x="18" y="45" width="28" height="10" rx="2" fill="${barColour}"/>
</svg>\n`;

const MINT_DEEP = '#0E7A56';

writeFileSync(
    fileURLToPath(new URL('public/branding/patrimoine-mark-light.svg', root)),
    fullMark(GREEN, MINT_DEEP)
);

writeFileSync(
    fileURLToPath(new URL('public/branding/patrimoine-mark-reverse.svg', root)),
    fullMark(WHITE, MINT)
);

/*
 * E-mail clients will not render an SVG, and a PDF renderer that does is the
 * exception. Both marks therefore also ship as PNG at three times the size
 * they are shown at.
 */
for (const [name, svg] of [
    ['patrimoine-mark-light', fullMark(GREEN, MINT_DEEP)],
    ['patrimoine-mark-reverse', fullMark(WHITE, MINT)],
]) {
    const png = await sharp(Buffer.from(svg), { density: 512 })
        .resize(144, 144)
        .png({ compressionLevel: 9 })
        .toBuffer();

    writeFileSync(
        fileURLToPath(new URL(`public/branding/${name}.png`, root)),
        png
    );
}

console.log('marks written');
