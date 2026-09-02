/*
 * The three screens that refuse to continue.
 *
 * They exist in the first build because they cannot be added later: a
 * handset stuck below the floor only obeys a floor its own build knows how
 * to enforce.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';

function screen(title, body, action = null) {
    return el('div', { class: 'blocked' }, [
        el('h1', { class: 'blocked-title', text: title }),
        body === null ? null : el('p', { class: 'blocked-body', text: body }),
        action,
    ]);
}

/*
 * Forced update. There is no dismiss and no "later": below the floor the
 * application does not open. The store button is absent rather than dead
 * when no store URL is published yet - which is the case until the app is
 * actually in a store.
 */
export function updateRequired(root, { storeUrl }) {
    const action = storeUrl === null
        ? null
        : el('button', {
            class: 'button',
            text: t('update.action'),
            onclick: () => window.open(storeUrl, '_blank'),
        });

    mount(root, screen(t('update.title'), t('update.body'), action));
}

/* Whatever the server wrote, in the language it was asked for. */
export function maintenance(root, { message }) {
    mount(root, screen(t('maintenance.title'), message));
}

/*
 * Unreachable is not signed-out. A token that is still valid stays valid,
 * so this offers retry and nothing else.
 */
export function unreachable(root, { onRetry }) {
    mount(root, screen(
        t('launch.unreachable.title'),
        t('launch.unreachable.body'),
        el('button', { class: 'button', text: t('common.retry'), onclick: onRetry })
    ));
}
