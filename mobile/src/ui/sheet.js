/*
 * The form sheet — every write the application can perform.
 *
 * One component rather than a form per flow, because the flows differ only
 * in their fields and the parts that must NOT differ are the parts that go
 * wrong: that a submit cannot be fired twice, that a server rejection lands
 * on the field it belongs to, and that the sheet cannot be dismissed while
 * a request is in flight.
 *
 * MONEY IS WHOLE CURRENCY UNITS. Every amount in this API is validated as
 * `integer`, so "1500.50" is not a smaller amount - it is a 422. The money
 * field reduces what is typed to whole units as it is typed; see
 * wholeUnits() below, which exists because the obvious way of doing that
 * multiplies the amount by a hundred.
 */

import { el, mount, clear } from './dom.js';
import { icon } from './icon.js';
import { t } from '../i18n/index.js';

/**
 * Reduce typed money to the whole currency units the API accepts.
 *
 * THIS EXISTS BECAUSE THE OBVIOUS VERSION IS DANGEROUS. Simply stripping
 * every non-digit turns "1500.50" into 150050 - a hundredfold overpayment,
 * silently, on a screen where somebody has just counted cash. Truncating at
 * the first separator instead turns "1,500" into 1.
 *
 * So separators are classified before anything is removed: one followed by
 * exactly three digits is a thousands separator and goes; anything left is
 * a decimal point and everything after it is dropped. Rounding down is
 * deliberate - the API takes whole units, and inventing a unit somebody did
 * not type would be worse than losing a fraction they cannot send anyway.
 *
 *   "1500.50"   -> "1500"      "1,500"    -> "1500"
 *   "1,500.50"  -> "1500"      "1 500,50" -> "1500"
 *   "1.500,50"  -> "1500"
 *
 * @param {string} raw
 * @returns {string} digits only
 */
export function wholeUnits(raw) {
    return String(raw)
        /* JS \s already covers the non-breaking space French grouping uses. */
        .replace(/\s/g, '')
        /* A separator followed by exactly three digits groups thousands. */
        .replace(/[.,](?=\d{3}(?:\D|$))/g, '')
        /* Whatever separator survives is decimal; drop the fraction. */
        .split(/[.,]/)[0]
        .replace(/\D/g, '');
}

function field(spec, error) {
    const id = `sheet-${spec.name}`;
    let input;

    if (spec.type === 'select') {
        input = el('select', { id, class: 'input' }, spec.options.map((option) => el('option', {
            value: option.value,
            text: option.label,
            selected: option.value === spec.value,
        })));
    } else if (spec.type === 'textarea') {
        input = el('textarea', { id, class: 'input', rows: 3 });
        input.value = spec.value ?? '';
    } else {
        input = el('input', {
            id,
            class: 'input',
            type: spec.type === 'date' ? 'date' : 'text',
            value: spec.value ?? '',
            /*
             * Money and dates: a numeric keypad, and no autocorrect trying
             * to be helpful with a reference number.
             */
            inputmode: spec.type === 'money' ? 'numeric' : undefined,
            autocapitalize: spec.type === 'text' ? 'sentences' : 'none',
            autocorrect: 'off',
        });

        if (spec.type === 'money') {
            input.addEventListener('input', () => {
                const cleaned = wholeUnits(input.value);

                if (cleaned !== input.value) {
                    input.value = cleaned;
                }
            });
        }
    }

    return {
        name: spec.name,
        input,
        node: el('div', { class: 'field' }, [
            el('label', { class: 'label', for: id, text: spec.label }),
            input,
            spec.hint === undefined ? null : el('span', { class: 'field-hint', text: spec.hint }),
            error === null || error === undefined
                ? null
                : el('span', { class: 'field-error', text: error }),
        ]),
    };
}

/**
 * @param {object} options
 * @param {string} options.title
 * @param {Array}  options.fields
 * @param {string} options.submitLabel
 * @param {(values: object) => Promise<void>} options.onSubmit  throws ApiError to show errors
 * @returns {Promise<boolean>} true when it submitted, false when dismissed
 */
export function openSheet({ title, fields, submitLabel, onSubmit }) {
    return new Promise((resolve) => {
        const backdrop = el('div', { class: 'sheet-backdrop' });
        let busy = false;

        function close(submitted) {
            if (busy) {
                return;
            }

            document.removeEventListener('keydown', onKey);
            backdrop.remove();
            resolve(submitted);
        }

        function onKey(event) {
            if (event.key === 'Escape') {
                close(false);
            }
        }

        function render(errors = {}, message = null) {
            const built = fields.map((spec) => field(spec, errors[spec.name]));

            const submit = el('button', {
                class: 'button',
                type: 'submit',
                text: submitLabel,
            });

            const form = el('form', {
                class: 'sheet-form',
                onsubmit: async (event) => {
                    event.preventDefault();

                    if (busy) {
                        return;
                    }

                    busy = true;
                    submit.disabled = true;
                    submit.textContent = t('sheet.saving');

                    const values = Object.fromEntries(
                        built.map((f) => [f.name, f.input.value.trim()])
                    );

                    try {
                        await onSubmit(values);
                        busy = false;
                        close(true);
                    } catch (failure) {
                        busy = false;

                        /*
                         * Laravel's 422 shape maps field by field. Anything
                         * else is shown whole, in the sentence the server
                         * wrote, with its PM-code.
                         */
                        render(
                            failure?.errors
                                ? Object.fromEntries(
                                    Object.entries(failure.errors)
                                        .map(([key, list]) => [key, list[0]])
                                )
                                : {},
                            failure?.isValidation === true
                                ? null
                                : `${failure?.message ?? t('signin.offline')}${failure?.code ? ` (${failure.code})` : ''}`
                        );
                    }
                },
            }, [
                message === null ? null : el('p', { class: 'error', role: 'alert', text: message }),
                ...built.map((f) => f.node),
                el('div', { class: 'sheet-actions' }, [
                    el('button', {
                        class: 'button button-secondary',
                        type: 'button',
                        text: t('common.cancel'),
                        onclick: () => close(false),
                    }),
                    submit,
                ]),
            ]);

            mount(sheet,
                el('header', { class: 'sheet-head' }, [
                    el('h2', { class: 'sheet-title', text: title }),
                    el('button', {
                        class: 'icon-button',
                        'aria-label': t('common.cancel'),
                        onclick: () => close(false),
                    }, [icon('x-close', { size: 20 })]),
                ]),
                form
            );

            built[0]?.input.focus();
        }

        const sheet = el('div', {
            class: 'sheet',
            role: 'dialog',
            'aria-modal': 'true',
        });

        /* A tap on the backdrop dismisses; a tap inside must not. */
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) {
                close(false);
            }
        });

        document.addEventListener('keydown', onKey);

        clear(backdrop).append(sheet);
        document.body.append(backdrop);

        render();
    });
}

/** Today, as the date inputs want it. */
export function today() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}
