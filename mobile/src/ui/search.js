/*
 * The search field.
 *
 * It searches what is already held rather than asking the server, so it
 * answers as fast as somebody can type and works with no signal at all.
 * That is only possible because the working set is prefetched; it is the
 * second thing that decision bought.
 */

import { el } from './dom.js';
import { icon } from './icon.js';
import { t } from '../i18n/index.js';

/**
 * @param {(query: string) => void} onChange  called with the trimmed query
 */
export function searchField(onChange) {
    const input = el('input', {
        class: 'search-input',
        type: 'search',
        /*
         * type=search gives iOS the clear button and a Search key. No
         * autocorrect: it fights proper nouns, which is most of what is
         * searched for here.
         */
        autocapitalize: 'none',
        autocorrect: 'off',
        spellcheck: 'false',
        placeholder: t('search.placeholder'),
        'aria-label': t('search.placeholder'),
    });

    input.addEventListener('input', () => onChange(input.value.trim()));

    /* On a phone the keyboard covers the results it is filtering. */
    input.addEventListener('search', () => input.blur());

    return {
        input,
        node: el('div', { class: 'search' }, [
            icon('search-lg', { size: 20, class: 'search-icon' }),
            input,
        ]),
        clear() {
            input.value = '';
            onChange('');
        },
    };
}
