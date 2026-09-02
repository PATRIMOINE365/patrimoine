/*
 * Edge-swipe to go back.
 *
 * A Capacitor application is one WKWebView with no UINavigationController,
 * so there is no native pop gesture to inherit - on iOS the swipe simply
 * does nothing, which reads as the application being broken rather than as
 * a missing feature. This provides it.
 *
 * It tracks the finger rather than firing on release. A back gesture you
 * cannot see following your thumb, and cannot change your mind about
 * halfway, is the thing iOS users notice immediately.
 */

/* The width of the live edge, in CSS pixels. iOS uses roughly this. */
const EDGE = 24;

/* Past a third of the screen, or thrown fast enough, the screen goes. */
const DISTANCE_RATIO = 0.35;
const VELOCITY = 0.4;

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * @param {HTMLElement} layer    the screen being dragged
 * @param {object} handlers
 * @param {() => HTMLElement|null} handlers.beneath  the screen behind it, parallaxed
 * @param {() => void} handlers.onComplete           called once the screen is gone
 */
export function attachSwipeBack(layer, { beneath, onComplete }) {
    let startX = 0;
    let startY = 0;
    let startedAt = 0;
    let width = 0;
    let dragging = false;
    /* null until the direction is known, then true once it is horizontal. */
    let horizontal = null;

    function under() {
        return beneath();
    }

    function paint(dx) {
        layer.style.transform = `translateX(${dx}px)`;

        const previous = under();

        if (previous !== null) {
            /*
             * iOS parallaxes the screen underneath at about a third of the
             * distance, which is what makes the gesture feel like moving a
             * stack rather than sliding one card off another.
             */
            previous.style.transform = `translateX(${(dx - width) / 3}px)`;
        }
    }

    function release(previous) {
        layer.classList.remove('is-dragging');
        layer.style.transform = '';

        if (previous !== null) {
            previous.classList.remove('is-dragging');
            previous.style.transform = '';
        }
    }

    layer.addEventListener('touchstart', (event) => {
        if (event.touches.length !== 1) {
            return;
        }

        const touch = event.touches[0];

        if (touch.clientX > EDGE) {
            return;
        }

        dragging = true;
        horizontal = null;
        startX = touch.clientX;
        startY = touch.clientY;
        startedAt = event.timeStamp;
        width = layer.offsetWidth;
    }, { passive: true });

    layer.addEventListener('touchmove', (event) => {
        if (! dragging) {
            return;
        }

        const touch = event.touches[0];
        const dx = touch.clientX - startX;
        const dy = touch.clientY - startY;

        /*
         * Decide once whether this is a back gesture or a scroll, and then
         * stop guessing. Re-deciding mid-drag makes a list impossible to
         * scroll near the left edge.
         */
        if (horizontal === null) {
            if (Math.abs(dx) < 8 && Math.abs(dy) < 8) {
                return;
            }

            horizontal = Math.abs(dx) > Math.abs(dy);

            if (! horizontal) {
                dragging = false;

                return;
            }

            layer.classList.add('is-dragging');

            const previous = under();

            if (previous !== null) {
                previous.classList.add('is-dragging');
            }
        }

        /* Only now: the browser must not also scroll the list sideways. */
        event.preventDefault();

        paint(Math.max(0, dx));
    }, { passive: false });

    function finish(event) {
        if (! dragging) {
            return;
        }

        dragging = false;

        if (horizontal !== true) {
            return;
        }

        const dx = Math.max(0, (event.changedTouches?.[0]?.clientX ?? startX) - startX);
        const elapsed = Math.max(1, event.timeStamp - startedAt);
        const previous = under();

        const goes = dx > width * DISTANCE_RATIO || dx / elapsed > VELOCITY;

        if (! goes) {
            /* Snap back: the transition class is restored by release(). */
            release(previous);

            return;
        }

        if (reducedMotion()) {
            release(previous);
            onComplete();

            return;
        }

        /*
         * Carry on from where the finger left off rather than restarting
         * the animation from zero, which would jump backwards first.
         */
        layer.style.transform = `translateX(${width}px)`;

        if (previous !== null) {
            previous.style.transform = 'translateX(0px)';
        }

        window.setTimeout(() => {
            release(previous);
            onComplete();
        }, 240);
    }

    layer.addEventListener('touchend', finish, { passive: true });
    layer.addEventListener('touchcancel', finish, { passive: true });
}
