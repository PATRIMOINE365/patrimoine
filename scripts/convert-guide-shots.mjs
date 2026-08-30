/*
 * Turn the PNGs capture-guide.mjs produces into the WebP files the guide
 * ships.
 *
 *   node scripts/convert-guide-shots.mjs <shots-dir> <locale>
 *
 * e.g.  node scripts/convert-guide-shots.mjs /tmp/guide-shots en
 *
 * The capture runs at deviceScaleFactor 2, so a 1440-wide page arrives as a
 * 2880-wide PNG. It is halved back to its logical size: at 1440 the text in
 * a screenshot is the size the reader sees it in the product, which is the
 * point of the picture. A drawer captured at 896 keeps its own width.
 *
 * This step used to be done by hand, which is why it was possible for the
 * 136 images to fall a redesign behind.
 */
import { readdir, mkdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import sharp from 'sharp';

const [source, locale] = process.argv.slice(2);

if (!source || !locale) {
    console.error('usage: node scripts/convert-guide-shots.mjs <shots-dir> <locale>');
    process.exit(1);
}

const out = join('public/guide', locale);
await mkdir(out, { recursive: true });

const files = (await readdir(source)).filter(f => f.endsWith('.png'));

if (files.length === 0) {
    console.error(`no PNGs in ${source}`);
    process.exit(1);
}

let total = 0;

for (const file of files) {
    const image = sharp(join(source, file));
    const { width } = await image.metadata();

    const buffer = await image
        .resize({ width: Math.round(width / 2), withoutEnlargement: true })
        .webp({ quality: 82, effort: 6 })
        .toBuffer();

    const name = file.replace(/\.png$/, '.webp');
    await writeFile(join(out, name), buffer);

    total += buffer.length;
    console.log(`  ${name}  ${(buffer.length / 1024).toFixed(0)} KB`);
}

console.log(`\n${locale}: ${files.length} images, ${(total / 1024 / 1024).toFixed(1)} MB`);
