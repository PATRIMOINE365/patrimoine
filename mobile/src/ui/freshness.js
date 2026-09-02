/*
 * "Updated 2 minutes ago", and "Updating…" while it is.
 *
 * The application renders from cache so that moving between screens costs
 * nothing. The price of that is that a figure on screen may be a few
 * minutes old - and in a product where somebody reads arrears and payouts,
 * that must be visible rather than assumed. This line is what makes the
 * cache honest.
 */

import { el, clear } from './dom.js';
import { t, language } from '../i18n/index.js';

const MINUTE = 60_000;

/*
 * Intl.RelativeTimeFormat is Safari 14+, so it is safe on the iOS 15.8
 * floor, and it gives us French for free rather than a hand-written table.
 */
function ago(stamp) {
    const elapsed = Date.now() - stamp;

    const format = new Intl.RelativeTimeFormat(language(), { numeric: 'auto' });

    if (elapsed < MINUTE) {
        return t('freshness.now');
    }

    if (elapsed < 60 * MINUTE) {
        return format.format(-Math.floor(elapsed / MINUTE), 'minute');
    }

    return format.format(-Math.floor(elapsed / (60 * MINUTE)), 'hour');
}

export function freshnessLine({ onRefresh }) {
    const text = el('span', { class: 'freshness-text' });

    const button = el('button', {
        class: 'freshness-refresh',
        'aria-label': t('freshness.refresh'),
        onclick: onRefresh,
    }, [el('span', { text: '⟳' })]);

    const node = el('div', { class: 'freshness' }, [text, button]);

    /**
     * @param {{ fetchedAt: number|null, loading: boolean }} state
     */
    function update({ fetchedAt, loading }) {
        clear(text);

        if (loading) {
            text.textContent = t('freshness.updating');
            node.classList.add('is-updating');

            return;
        }

        node.classList.remove('is-updating');

        text.textContent = fetchedAt === null
            ? ''
            : t('freshness.updated', { when: ago(fetchedAt) });
    }

    return { node, update };
}
