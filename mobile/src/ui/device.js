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
        const short = Math.min(
            window.screen?.width ?? window.innerWidth,
            window.screen?.height ?? window.innerHeight
        );

        decided = short >= TABLET_SHORT_EDGE;
    }

    return decided;
}

/** Testing seam, and an escape hatch if a device ever reports oddly. */
export function forceLayout(tablet) {
    decided = tablet;
}
