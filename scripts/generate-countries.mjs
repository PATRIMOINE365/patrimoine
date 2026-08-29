/*
|--------------------------------------------------------------------------
| Country and calling-code data
|--------------------------------------------------------------------------
|
| Writes config/countries.php, lang/{en,fr}/countries.php and
| resources/js/countries.js from one table, so the four never drift.
|
| Country names come from ICU through Intl.DisplayNames rather than being
| typed out, which is why this is a generator and not a data file. The
| calling codes are the ITU assignments and are maintained here by hand.
|
| Run with:  node scripts/generate-countries.mjs
|
*/

import { existsSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

/*
 * Flags are rasterised into one sprite rather than shipped as 244 SVGs.
 * Several national flags carry a detailed coat of arms — Serbia's is 181 kB
 * on its own — and none of that detail survives being drawn 20 pixels wide.
 * The sprite is a single request and around a hundred kilobytes.
 */
const FLAG_WIDTH = 40;
const FLAG_HEIGHT = 30;

/*
 * ISO 3166-1 alpha-2 → ITU calling code.
 *
 * Territories with no telephone assignment of their own (Antarctic and
 * uninhabited dependencies) are deliberately absent: a picker entry nobody
 * can be called on is noise.
 */
const DIALLING_CODES = {
    AD: 376, AE: 971, AF: 93, AG: 1, AI: 1, AL: 355, AM: 374, AO: 244,
    AQ: 672, AR: 54, AS: 1, AT: 43, AU: 61, AW: 297, AX: 358, AZ: 994,
    BA: 387, BB: 1, BD: 880, BE: 32, BF: 226, BG: 359, BH: 973, BI: 257,
    BJ: 229, BL: 590, BM: 1, BN: 673, BO: 591, BQ: 599, BR: 55, BS: 1,
    BT: 975, BW: 267, BY: 375, BZ: 501, CA: 1, CC: 61, CD: 243, CF: 236,
    CG: 242, CH: 41, CI: 225, CK: 682, CL: 56, CM: 237, CN: 86, CO: 57,
    CR: 506, CU: 53, CV: 238, CW: 599, CX: 61, CY: 357, CZ: 420, DE: 49,
    DJ: 253, DK: 45, DM: 1, DO: 1, DZ: 213, EC: 593, EE: 372, EG: 20,
    EH: 212, ER: 291, ES: 34, ET: 251, FI: 358, FJ: 679, FK: 500, FM: 691,
    FO: 298, FR: 33, GA: 241, GB: 44, GD: 1, GE: 995, GF: 594, GG: 44,
    GH: 233, GI: 350, GL: 299, GM: 220, GN: 224, GP: 590, GQ: 240, GR: 30,
    GT: 502, GU: 1, GW: 245, GY: 592, HK: 852, HN: 504, HR: 385, HT: 509,
    HU: 36, ID: 62, IE: 353, IL: 972, IM: 44, IN: 91, IO: 246, IQ: 964,
    IR: 98, IS: 354, IT: 39, JE: 44, JM: 1, JO: 962, JP: 81, KE: 254,
    KG: 996, KH: 855, KI: 686, KM: 269, KN: 1, KP: 850, KR: 82, KW: 965,
    KY: 1, KZ: 7, LA: 856, LB: 961, LC: 1, LI: 423, LK: 94, LR: 231,
    LS: 266, LT: 370, LU: 352, LV: 371, LY: 218, MA: 212, MC: 377, MD: 373,
    ME: 382, MF: 590, MG: 261, MH: 692, MK: 389, ML: 223, MM: 95, MN: 976,
    MO: 853, MP: 1, MQ: 596, MR: 222, MS: 1, MT: 356, MU: 230, MV: 960,
    MW: 265, MX: 52, MY: 60, MZ: 258, NA: 264, NC: 687, NE: 227, NF: 672,
    NG: 234, NI: 505, NL: 31, NO: 47, NP: 977, NR: 674, NU: 683, NZ: 64,
    OM: 968, PA: 507, PE: 51, PF: 689, PG: 675, PH: 63, PK: 92, PL: 48,
    PM: 508, PN: 64, PR: 1, PS: 970, PT: 351, PW: 680, PY: 595, QA: 974,
    RE: 262, RO: 40, RS: 381, RU: 7, RW: 250, SA: 966, SB: 677, SC: 248,
    SD: 249, SE: 46, SG: 65, SH: 290, SI: 386, SJ: 47, SK: 421, SL: 232,
    SM: 378, SN: 221, SO: 252, SR: 597, SS: 211, ST: 239, SV: 503, SX: 1,
    SY: 963, SZ: 268, TC: 1, TD: 235, TG: 228, TH: 66, TJ: 992, TK: 690,
    TL: 670, TM: 993, TN: 216, TO: 676, TR: 90, TT: 1, TV: 688, TW: 886,
    TZ: 255, UA: 380, UG: 256, US: 1, UY: 598, UZ: 998, VA: 379, VC: 1,
    VE: 58, VG: 1, VI: 1, VN: 84, VU: 678, WF: 681, WS: 685, YE: 967,
    YT: 262, ZA: 27, ZM: 260, ZW: 263,
};

/*
 * A calling code shared by several countries cannot be read backwards on
 * its own. These are the countries a bare +code is attributed to when the
 * stored number is all there is to go on, chosen by subscriber numbers.
 */
const PREFERRED_FOR_CODE = {
    1: 'US',
    7: 'RU',
    39: 'IT',
    44: 'GB',
    47: 'NO',
    61: 'AU',
    64: 'NZ',
    212: 'MA',
    262: 'RE',
    358: 'FI',
    590: 'GP',
    599: 'CW',
    672: 'NF',
};

/*
 * Almost everywhere, the leading zero is how a number is dialled from
 * inside the country and is dropped internationally: 024 434 7118 in Ghana
 * is +233 24 434 7118. Italy is the exception that matters — a Rome
 * landline really is +39 06 …, zero and all — and San Marino and the
 * Vatican share its numbering.
 */
const KEEPS_TRUNK_ZERO = ['IT', 'SM', 'VA'];

const english = new Intl.DisplayNames(['en'], { type: 'region' });
const french = new Intl.DisplayNames(['fr'], { type: 'region' });

const countries = Object.keys(DIALLING_CODES)
    .sort()
    .map((iso) => {
        const en = english.of(iso);
        const fr = french.of(iso);

        if (! en || en === iso || ! fr || fr === iso) {
            throw new Error(`No ICU name for ${iso}. Refusing to write a code as a name.`);
        }

        return { iso, code: DIALLING_CODES[iso], en, fr };
    });

for (const iso of KEEPS_TRUNK_ZERO) {
    if (! DIALLING_CODES[iso]) {
        throw new Error(`${iso} keeps its trunk zero but has no calling code.`);
    }
}

for (const [code, iso] of Object.entries(PREFERRED_FOR_CODE)) {
    if (DIALLING_CODES[iso] !== Number(code)) {
        throw new Error(`Preferred country ${iso} is not on +${code}.`);
    }
}

const banner = (what) => `<?php

/*
|--------------------------------------------------------------------------
| ${what}
|--------------------------------------------------------------------------
|
| Generated by scripts/generate-countries.mjs. Do not edit by hand.
|
| ${countries.length} countries and territories.
|
*/
`;

const php = (value) => "'" + String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

writeFileSync(
    join(root, 'config/countries.php'),
    banner('Telephone country codes')
    + `
return [

    /*
     * ISO 3166-1 alpha-2 → ITU calling code.
     */
    'dialling_codes' => [
${countries.map((c) => `        '${c.iso}' => ${c.code},`).join('\n')}
    ],

    /*
     * The country a shared calling code is read as when a stored number is
     * all there is to go on.
     */
    'preferred_for_code' => [
${Object.entries(PREFERRED_FOR_CODE).map(([code, iso]) => `        ${code} => '${iso}',`).join('\n')}
    ],

    /*
     * Countries whose leading zero is part of the number rather than a way
     * of dialling it from inside the country.
     */
    'keeps_trunk_zero' => [
${KEEPS_TRUNK_ZERO.map((iso) => `        '${iso}',`).join('\n')}
    ],

];
`,
);

for (const [locale, key] of [['en', 'en'], ['fr', 'fr']]) {
    writeFileSync(
        join(root, `lang/${locale}/countries.php`),
        banner('Country names')
        + `
return [
${countries.map((c) => `    '${c.iso}' => ${php(c[key])},`).join('\n')}
];
`,
    );
}

writeFileSync(
    join(root, 'resources/js/countries.js'),
    `/*
|--------------------------------------------------------------------------
| Countries, as the browser knows them
|--------------------------------------------------------------------------
|
| Generated by scripts/generate-countries.mjs. Do not edit by hand.
|
| The signup page is public and has no session to ask, so the list is
| bundled rather than fetched. Both names travel with each entry because
| the picker sorts alphabetically in whichever language is on screen, and
| 'Germany' and 'Allemagne' do not sort to the same place.
|
| ${countries.length} countries and territories.
|
*/

export const COUNTRIES = [
${countries.map((c) => `    { iso: '${c.iso}', code: ${c.code}, en: ${JSON.stringify(c.en)}, fr: ${JSON.stringify(c.fr)} },`).join('\n')}
];

/*
 * The country a shared calling code is read as when a stored number is all
 * there is to go on.
 */
export const PREFERRED_FOR_CODE = {
${Object.entries(PREFERRED_FOR_CODE).map(([code, iso]) => `    ${code}: '${iso}',`).join('\n')}
};

/*
 * Countries whose leading zero is part of the number rather than a way of
 * dialling it from inside the country.
 */
export const KEEPS_TRUNK_ZERO = [
${KEEPS_TRUNK_ZERO.map((iso) => `    '${iso}',`).join('\n')}
];
`,
);

/*
 * The flag sprite, and the stylesheet that indexes into it.
 */
const source = (iso) => join(
    root,
    'node_modules/flag-icons/flags/4x3',
    `${iso.toLowerCase()}.svg`,
);

const missing = countries.filter((c) => ! existsSync(source(c.iso)));

if (missing.length) {
    throw new Error(`No flag artwork for ${missing.map((c) => c.iso).join(', ')}.`);
}

const tiles = await Promise.all(
    countries.map((c) => sharp(source(c.iso))
        .resize(FLAG_WIDTH, FLAG_HEIGHT, { fit: 'fill' })
        .png()
        .toBuffer()),
);

await sharp({
    create: {
        width: FLAG_WIDTH,
        height: FLAG_HEIGHT * countries.length,
        channels: 4,
        background: { r: 0, g: 0, b: 0, alpha: 0 },
    },
})
    .composite(tiles.map((input, index) => ({
        input,
        top: index * FLAG_HEIGHT,
        left: 0,
    })))
    .png({ palette: true, compressionLevel: 9 })
    .toFile(join(root, 'public/flags.png'));

/*
 * The sprite is drawn at half its pixel size so it stays sharp on a dense
 * screen; every offset below is therefore in display pixels, not source
 * pixels.
 */
const displayWidth = FLAG_WIDTH / 2;
const displayHeight = FLAG_HEIGHT / 2;

writeFileSync(
    join(root, 'resources/css/flags.css'),
    `/*
|--------------------------------------------------------------------------
| Country flags
|--------------------------------------------------------------------------
|
| Generated by scripts/generate-countries.mjs. Do not edit by hand.
|
| One sprite, ${countries.length} flags, indexed by ISO 3166-1 alpha-2. Emoji flags were
| not an option: Windows ships no flag glyphs at all and renders them as a
| pair of letters.
|
*/

.pm-flag {
    display: inline-block;

    width: ${displayWidth}px;
    height: ${displayHeight}px;

    flex: none;

    border-radius: 2px;

    background-image: url('/flags.png');
    background-repeat: no-repeat;
    background-size: ${displayWidth}px auto;

    /* Flags with a white edge would otherwise dissolve into a light field. */
    box-shadow: inset 0 0 0 1px rgb(0 0 0 / 12%);
}

${countries.map((c, index) => `.pm-flag-${c.iso.toLowerCase()} { background-position: 0 -${index * displayHeight}px; }`).join('\n')}
`,
);

console.log(`Wrote ${countries.length} countries and their flag sprite.`);
