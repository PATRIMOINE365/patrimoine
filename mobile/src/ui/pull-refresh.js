/*
 * Pull down to refresh.
 *
 * iOS gives a scrolling element its rubber-band bounce for free, and that
 * bounce is the affordance: a list that resists and springs back is a list
 * somebody will try to pull. This turns a deliberate pull into a refresh.
 *
 * THE RULE THAT KEEPS THE BOUNCE. preventDefault is called ONLY while the
 * finger is dragging downwards from a scroll position of zero. Any earlier
 * and the browser stops scrolling the list at all; any broader and the
 * bounce disappears, because the bounce IS the default behaviour being
 * allowed to happen.
 */

import { el } from './dom.js';
import { icon } from './icon.js';

/* Far enough that it cannot be a mis-swipe, close enough to be easy. */
const THRESHOLD = 72;

/* Beyond this the indicator stops following, so it cannot be dragged away. */
const MAX_PULL = 120;

/**
 * @param {HTMLElement} scroller  the element that scrolls
 * @param {() => Promise<void>} onRefresh
 * @returns {() => void} detach
 */
export function attachPullToRefresh(scroller, onRefresh) {
    const spinner = el('div', { class: 'pull-indicator' }, [icon('refresh-cw', { size: 20 })]);

    scroller.prepend(spinner);

    /*
     * The indicator lives inside the scroller, and the screens replace that
     * scroller's contents wholesale on every render - so it was being swept
     * away the first time anything repainted, and the gesture worked with
     * nothing to show for it. Rather than make every caller preserve it,
     * put it back whenever it goes.
     */
    const keeper = new MutationObserver(() => {
        if (! scroller.contains(spinner)) {
            scroller.prepend(spinner);
        }
    });

    keeper.observe(scroller, { childList: true });

    let startY = 0;
    let pulling = false;
    let refreshing = false;

    function setPull(distance) {
        spinner.style.transform = `translateY(${distance}px)`;
        spinner.style.opacity = String(Math.min(1, distance / THRESHOLD));
        spinner.classList.toggle('is-ready', distance >= THRESHOLD);
    }

    function reset() {
        spinner.classList.remove('is-dragging');
        spinner.style.transform = '';
        spinner.style.opacity = '';
        spinner.classList.remove('is-ready');
    }

    function onStart(event) {
        if (refreshing || event.touches.length !== 1) {
            return;
        }

        /*
         * Only from the very top. Starting a pull halfway down a list would
         * fight the scroll the person actually asked for.
         */
        if (scroller.scrollTop > 0) {
            return;
        }

        pulling = true;
        startY = event.touches[0].clientY;
    }

    function onMove(event) {
        if (! pulling) {
            return;
        }

        const delta = event.touches[0].clientY - startY;

        if (delta <= 0) {
            /* Upwards: an ordinary scroll. Let go of it entirely. */
            pulling = false;
            reset();

            return;
        }

        /* Now, and only now, the gesture is ours. */
        event.preventDefault();

        spinner.classList.add('is-dragging');

        /* Resistance: the further it is pulled, the less it follows. */
        setPull(Math.min(MAX_PULL, delta * 0.5));
    }

    async function onEnd() {
        if (! pulling) {
            return;
        }

        pulling = false;

        const distance = parseFloat(
            (spinner.style.transform.match(/translateY\(([-\d.]+)px\)/) ?? [])[1] ?? '0'
        );

        if (distance < THRESHOLD || refreshing) {
            reset();

            return;
        }

        refreshing = true;
        spinner.classList.remove('is-dragging');
        spinner.classList.add('is-refreshing');
        setPull(THRESHOLD * 0.6);

        try {
            await onRefresh();
        } finally {
            refreshing = false;
            spinner.classList.remove('is-refreshing');
            reset();
        }
    }

    scroller.addEventListener('touchstart', onStart, { passive: true });
    /* Not passive: this one may preventDefault. */
    scroller.addEventListener('touchmove', onMove, { passive: false });
    scroller.addEventListener('touchend', onEnd, { passive: true });
    scroller.addEventListener('touchcancel', onEnd, { passive: true });

    return () => {
        keeper.disconnect();
        scroller.removeEventListener('touchstart', onStart);
        scroller.removeEventListener('touchmove', onMove);
        scroller.removeEventListener('touchend', onEnd);
        scroller.removeEventListener('touchcancel', onEnd);
        spinner.remove();
    };
}
