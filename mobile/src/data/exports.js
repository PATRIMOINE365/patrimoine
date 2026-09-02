/*
 * Downloading a document.
 *
 * The export endpoints are ordinary authenticated routes, so a WebView
 * cannot simply open one in Safari - Safari has no bearer token. And a
 * fetch inside the WebView produces bytes with nowhere to put them: a
 * blob: URL cannot be saved from a Capacitor page, and iOS blocks a
 * download the page starts itself.
 *
 * The API already solves this. POST /document-links takes an endpoint and
 * returns a SIGNED url, valid without a token, which is exactly what
 * DocumentLinkService exists for and what the browser application uses for
 * the same PDFs. Opening that in the system browser downloads it properly,
 * with iOS handling the save or share.
 *
 * So nothing here streams a file. It asks for a signed link and hands it
 * over.
 */

import { endpoints } from '../api/endpoints.js';

/**
 * @param {object} client
 * @param {string} endpoint  an API path such as '/reports/arrears/pdf'
 * @returns {Promise<string>} the signed URL
 */
export async function signedLink(client, endpoint) {
    const response = await client.post(endpoints.documentLinks ?? '/document-links', { endpoint });

    const url = response?.url ?? response?.data?.url;

    if (! url) {
        throw new Error('No signed link was returned.');
    }

    return url;
}

/**
 * Ask for a signed link and open it.
 *
 * The window is opened BEFORE the request, not after: iOS treats a
 * window.open that happens in a later tick as a pop-up rather than as
 * something the person asked for, and blocks it. Opening first and setting
 * the location when the link arrives keeps it attributed to the tap.
 */
export async function openDocument(client, endpoint) {
    const target = window.open('', '_blank');

    try {
        const url = await signedLink(client, endpoint);

        if (target === null) {
            /* Pop-up blocked anyway: navigate in place rather than fail. */
            window.location.href = url;

            return;
        }

        target.location.href = url;
    } catch (failure) {
        target?.close();

        throw failure;
    }
}

/*
 * The three formats the API offers, in the order the browser application
 * lists them. Not every endpoint has all three; each screen says which of
 * them it actually has.
 */
export const FORMATS = [
    { id: 'pdf', label: 'export.pdf', icon: 'file-05' },
    { id: 'csv', label: 'export.csv', icon: 'file-check' },
    { id: 'xlsx', label: 'export.xlsx', icon: 'grid-01' },
];
