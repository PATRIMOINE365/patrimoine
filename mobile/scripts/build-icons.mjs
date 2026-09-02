/*
 * Render the iOS app icon and launch image from the brand masters.
 *
 * Run explicitly, not on every build - the output lives inside the Xcode
 * project and is committed, so regenerating it every build would churn the
 * repository for nothing:
 *
 *     npm run build:icons
 *
 * The sources in brand/ are copied verbatim from the Patrimoine 365 Brand
 * Package, whose README is explicit that the SVGs are the masters and that
 * the app icon comes from the app-tile forms. Nothing here redraws the
 * mark; it is rasterised and nothing else.
 *
 * Two things the icon must satisfy, both of which are Apple's rules rather
 * than choices:
 *
 *   1. NO ROUNDED CORNERS. iOS applies its own superellipse mask to
 *      whatever it is given. The brand package ships a `-square` tile for
 *      exactly this reason - the rounded `app-tile` would be masked twice
 *      and show pale wedges at the corners.
 *   2. NO ALPHA CHANNEL. An icon with alpha is rejected at submission and
 *      renders black on a device.
 */

import { mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import sharp from 'sharp';

const here = dirname(fileURLToPath(import.meta.url));
const brand = resolve(here, '../brand');
const assets = resolve(here, '../ios/App/App/Assets.xcassets');

/* Patrimoine Green — the tile ground the brand package uses. */
const GROUND = '#123D35';

async function write(input, size, destination) {
    mkdirSync(dirname(destination), { recursive: true });

    await sharp(input)
        .resize(size, size)
        /* Flattening onto the ground guarantees no alpha survives. */
        .flatten({ background: GROUND })
        .png({ compressionLevel: 9 })
        .toFile(destination);

    console.log(`build-icons: ${size}x${size} -> ${destination.split('Assets.xcassets').pop()}`);
}

/*
 * The icon. The square tile is already full-bleed on the brand ground, so
 * it only needs rasterising at 1024. Density is set so the vector renders
 * at the target rather than being scaled up from its 64pt viewBox.
 */
await write(
    await sharp(resolve(brand, 'patrimoine365-app-tile-square.svg'), { density: 1152 })
        .resize(1024, 1024)
        .png()
        .toBuffer(),
    1024,
    resolve(assets, 'AppIcon.appiconset/AppIcon-512@2x.png')
);

/*
 * The launch image is one square shown on every device and orientation, so
 * the lockup sits well inside it: the edges are cropped differently on a
 * phone in portrait and an iPad in landscape, and anything near them is
 * lost. The stacked reverse lockup is the brand's form for a dark ground.
 */
const SPLASH = 2732;
const LOCKUP_WIDTH = Math.round(SPLASH * 0.34);

const lockup = await sharp(
    resolve(brand, 'patrimoine365-logo-stacked-reverse.svg'),
    { density: 600 }
)
    .resize({ width: LOCKUP_WIDTH })
    .png()
    .toBuffer();

const splash = await sharp({
    create: {
        width: SPLASH,
        height: SPLASH,
        channels: 3,
        background: GROUND,
    },
})
    .composite([{ input: lockup, gravity: 'centre' }])
    .png()
    .toBuffer();

/*
 * Three identical files: the asset catalogue declares 1x, 2x and 3x, and
 * Capacitor generates the same square for each.
 */
for (const name of ['splash-2732x2732.png', 'splash-2732x2732-1.png', 'splash-2732x2732-2.png']) {
    await write(splash, SPLASH, resolve(assets, `Splash.imageset/${name}`));
}
