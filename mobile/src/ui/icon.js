/*
 * One icon, as a real SVG element.
 *
 * The web twin returns a markup string because the pages it serves build
 * markup as text. Here everything is built as nodes, so this returns a node
 * - which also means the icon can be handed straight to el() alongside text
 * without an innerHTML round trip anywhere in the view layer.
 */

import { ICON_PATHS } from '../generated/icons.js';

const SVG_NS = 'http://www.w3.org/2000/svg';

/**
 * @param {string} name  a key from resources/icons/untitled-ui.json
 * @param {object} [options]
 * @param {number} [options.size]   rendered size in px; 20 by default, as
 *                                  in the product. The tab bar uses 24.
 * @param {string} [options.class]
 * @returns {SVGSVGElement|null} null when the name is unknown
 */
export function icon(name, { size = 20, class: className = '' } = {}) {
    const paths = ICON_PATHS[name];

    if (paths === undefined) {
        /*
         * A missing icon must never break a screen in front of a customer,
         * but it should be impossible to miss while building one.
         */
        console.warn(`[icons] unknown icon "${name}"`);

        return null;
    }

    const svg = document.createElementNS(SVG_NS, 'svg');

    svg.setAttribute('width', String(size));
    svg.setAttribute('height', String(size));
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    /*
     * 2 at 24, scaled to 20, is an effective 1.67px stroke - what Untitled
     * UI's own interfaces use. A true 2px at 20px reads heavy beside Inter.
     */
    svg.setAttribute('stroke-width', '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('focusable', 'false');

    if (className !== '') {
        svg.setAttribute('class', className);
    }

    /*
     * The path data comes from our own checked-in JSON, never from a
     * response, so parsing it as markup introduces nothing untrusted.
     */
    svg.innerHTML = paths;

    return svg;
}
