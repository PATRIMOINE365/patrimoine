/*
 * The green banner the browser application shows after a write, and the
 * red one it shows when a page fails.
 *
 * One banner per host: a new message replaces the old rather than
 * stacking. A banner carrying a button (Download receipt, Email to owner)
 * stays ten seconds, a plain sentence five - the web's own timings.
 */

import { el, mount } from './dom.js';
import { icon } from './icon.js';

const timers = new WeakMap();

/**
 * @param {HTMLElement} host  an empty element kept at the top of the screen
 * @param {object} options
 * @param {string} options.message
 * @param {'success'|'error'|'info'} [options.tone]
 * @param {Array<{label:string, onClick:Function}>} [options.actions]
 * @param {boolean} [options.sticky]
 */
export function banner(host, { message, tone = 'success', actions = [], sticky = false }) {
    if (! host) {
        return;
    }

    clearTimeout(timers.get(host));

    const node = el('div', { class: `banner banner-${tone}`, role: tone === 'error' ? 'alert' : 'status' }, [
        icon(tone === 'error' ? 'alert-circle' : tone === 'info' ? 'info-circle' : 'check-circle', { size: 18 }),
        el('span', { class: 'banner-text', text: message }),
        ...actions.map((action) => el('button', {
            class: 'button button-secondary button-compact',
            type: 'button',
            text: action.label,
            onclick: async (event) => {
                const button = event.currentTarget;
                const label = button.textContent;

                button.disabled = true;

                try {
                    await action.onClick(button);
                } finally {
                    button.disabled = false;

                    if (button.isConnected && button.textContent === label) {
                        button.textContent = label;
                    }
                }
            },
        })),
        el('button', { class: 'icon-button banner-close', type: 'button', 'aria-label': 'Close', onclick: () => clearBanner(host) }, [icon('x-close', { size: 16 })]),
    ]);

    mount(host, node);
    host.hidden = false;

    if (! sticky && tone !== 'error') {
        timers.set(host, setTimeout(() => clearBanner(host), actions.length > 0 ? 10000 : 5000));
    }
}

export function clearBanner(host) {
    if (! host) {
        return;
    }

    clearTimeout(timers.get(host));
    mount(host);
    host.hidden = true;
}
