/*
 * Files that are not PDFs: a CSV, a workbook, a JSON copy of somebody's
 * data.
 *
 * A PDF opens through a signed link in the system browser (exports.js).
 * The other formats the browser application DOWNLOADS, and a WebView has
 * nowhere to put a download: a blob: URL cannot be saved from a Capacitor
 * page, and iOS blocks a download the page starts itself. So the bytes are
 * fetched with the bearer token, written to the app's cache, and handed to
 * the share sheet - which on an iPad offers Save to Files, AirDrop, Mail
 * and every other destination the person has.
 *
 * In a plain browser (npm run dev) the same call falls back to an anchor
 * download, so layout work needs no device.
 */

import { Capacitor } from '@capacitor/core';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

function toBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onerror = () => reject(reader.error);
        reader.onload = () => resolve(String(reader.result).split(',')[1] ?? '');
        reader.readAsDataURL(blob);
    });
}

/**
 * @param {Blob} blob
 * @param {string} filename
 */
export async function saveAndShare(blob, filename) {
    const safe = filename.replace(/[\\/:*?"<>|]+/g, '-');

    if (! Capacitor.isNativePlatform()) {
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');

        anchor.href = url;
        anchor.download = safe;
        document.body.append(anchor);
        anchor.click();
        anchor.remove();
        setTimeout(() => URL.revokeObjectURL(url), 10000);

        return;
    }

    const written = await Filesystem.writeFile({
        path: safe,
        data: await toBase64(blob),
        directory: Directory.Cache,
    });

    await Share.share({ title: safe, url: written.uri });
}

/**
 * Fetch an authenticated file and offer it. `fallback` names it when the
 * server proposes nothing.
 */
export async function downloadFile(client, path, fallback) {
    const { blob, filename } = await client.download(path);

    await saveAndShare(blob, filename ?? fallback);
}
