/*
 * The launch screen, and the rule that governs it.
 *
 * NOTHING HALF-BUILT IS EVER SHOWN. The application opens on the brand mark
 * and stays there until the organisation's working set has been fetched, so
 * the first thing anybody sees after it is a finished screen with data on
 * it - not a spinner, then a heading, then rows arriving underneath.
 *
 * On a device the native splash does this: launchAutoHide is false in
 * capacitor.config.json, so iOS holds the launch image until hide() is
 * called. This module is the web half - the same mark on the same ground -
 * so `npm run dev` in a browser behaves identically, and so that there is
 * no flash of page background in the moment between the native splash
 * hiding and the first screen painting.
 */

import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';

import { el } from './dom.js';

/* Patrimoine Green, matching the native splash and the app tile exactly. */
const GROUND = '#123D35';
const SVG_NS = 'http://www.w3.org/2000/svg';

/*
 * The mark, copied from brand/patrimoine365-app-tile-square.svg: two white
 * pillars and three mint ledger bars. Drawn rather than loaded so the
 * launch screen never waits on a network request of its own.
 */
function mark(size) {
    const svg = document.createElementNS(SVG_NS, 'svg');

    svg.setAttribute('width', String(size));
    svg.setAttribute('height', String(size));
    svg.setAttribute('viewBox', '0 0 64 64');
    svg.setAttribute('aria-hidden', 'true');

    const rects = [
        ['2', '4', '10', '56', '#FFFFFF'],
        ['52', '4', '10', '56', '#FFFFFF'],
        ['18', '9', '28', '10', '#39D6A3'],
        ['18', '27', '28', '10', '#39D6A3'],
        ['18', '45', '28', '10', '#39D6A3'],
    ];

    for (const [x, y, width, height, fill] of rects) {
        const rect = document.createElementNS(SVG_NS, 'rect');

        rect.setAttribute('x', x);
        rect.setAttribute('y', y);
        rect.setAttribute('width', width);
        rect.setAttribute('height', height);
        rect.setAttribute('rx', '2');
        rect.setAttribute('fill', fill);
        svg.append(rect);
    }

    return svg;
}

let node = null;

/** Cover the screen. Called before anything else in boot(). */
export function showSplash() {
    if (node !== null) {
        return;
    }

    node = el('div', { class: 'splash' }, [mark(96)]);
    document.body.append(node);
}

/**
 * Uncover it, once there is a finished screen underneath.
 *
 * Called from a `finally`, so a failed launch reveals the screen that says
 * what went wrong rather than leaving somebody looking at a logo for ever.
 */
export async function hideSplash() {
    if (node !== null) {
        /*
         * Faded rather than cut: the native splash and this element are the
         * same image, so a hard swap is invisible, but the reveal of the
         * application underneath should not be a jump cut.
         */
        node.classList.add('is-hiding');

        const toRemove = node;

        node = null;

        window.setTimeout(() => toRemove.remove(), 260);
    }

    if (Capacitor.isNativePlatform()) {
        await SplashScreen.hide({ fadeOutDuration: 240 }).catch(() => {
            /* Never let a splash plugin failure hold the application shut. */
        });
    }
}
