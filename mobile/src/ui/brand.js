/*
 * The Patrimoine mark, drawn.
 *
 * Copied from brand/patrimoine365-app-tile-square.svg: two white pillars and
 * three mint ledger bars. Drawn rather than loaded so the launch screen and
 * the header never wait on a request, and so the mark cannot go missing if
 * an asset path changes.
 */

const SVG_NS = 'http://www.w3.org/2000/svg';

const RECTS = [
    ['2', '4', '10', '56', '#FFFFFF'],
    ['52', '4', '10', '56', '#FFFFFF'],
    ['18', '9', '28', '10', '#39D6A3'],
    ['18', '27', '28', '10', '#39D6A3'],
    ['18', '45', '28', '10', '#39D6A3'],
];

/** The bare mark, on whatever ground it is placed. */
export function brandMark(size) {
    const svg = document.createElementNS(SVG_NS, 'svg');

    svg.setAttribute('width', String(size));
    svg.setAttribute('height', String(size));
    svg.setAttribute('viewBox', '0 0 64 64');
    svg.setAttribute('aria-hidden', 'true');

    for (const [x, y, width, height, fill] of RECTS) {
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
