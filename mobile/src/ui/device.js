/*
 * Which shell to render.
 *
 * The two clients are deliberately different products, not one layout at two
 * widths: the tablet is the full client - sidebar, list, detail, tables,
 * and the flows that move money - while the phone stays a reading and
 * reference tool for the field.
 *
 * So this decides which application runs, and it must not change while it is
 * running. Rotating an iPad does not turn it into a phone.
 */

/*
 * The SHORTER screen edge, not the current width. An iPhone in landscape is
 * 812pt wide and would pass any width test; an iPad mini in portrait is
 * 768pt wide and would fail one. The short edge is the only dimension that
 * actually distinguishes the two, and it does not change with rotation.
 */
const TABLET_SHORT_EDGE = 768;

let decided = null;

export function isTablet() {
    if (decided === null) {
        /*
         * A screen that reports 0x0 - an embedded WebView before layout, a
         * headless pane - must not be read as a tiny phone. The window's
         * own size is the next best witness.
         */
        const width = window.screen?.width > 0 ? window.screen.width : window.innerWidth;
        const height = window.screen?.height > 0 ? window.screen.height : window.innerHeight;
        const short = Math.min(width, height);

        decided = short >= TABLET_SHORT_EDGE;
    }

    return decided;
}

/** Testing seam, and an escape hatch if a device ever reports oddly. */
export function forceLayout(tablet) {
    decided = tablet;
}
