/*
 * The form sheet — every write the application can perform.
 *
 * One component rather than a form per flow, because the flows differ only
 * in their fields and the parts that must NOT differ are the parts that go
 * wrong: that a submit cannot be fired twice, that a server rejection lands
 * on the field it belongs to, and that the sheet cannot be dismissed while
 * a request is in flight.
 *
 * It mirrors the browser application's drawers. A drawer there is a form
 * of labelled fields, optional sections, a preview of what the numbers
 * will become, sometimes a read-only review page before the final
 * Confirm, and a footer with Cancel and one primary action. Every one of
 * those shapes is expressible here, which is what lets the tablet carry
 * every drawer the web has without a component per drawer.
 *
 * MONEY IS WHOLE CURRENCY UNITS. Every amount in this API is validated as
 * `integer`, so "1500.50" is not a smaller amount - it is a 422. The money
 * field reduces what is typed to whole units as it is typed; see
 * wholeUnits() below, which exists because the obvious way of doing that
 * multiplies the amount by a hundred.
 */

import { el, mount, clear } from './dom.js';
import { icon } from './icon.js';
import { t, language } from '../i18n/index.js';
import { money } from './money.js';
import { COUNTRIES, PREFERRED_FOR_CODE, KEEPS_TRUNK_ZERO } from '../generated/countries.js';

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

/** Today, as the date inputs want it. */
export function today() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/* ------------------------------------------------------------ telephone */

const DEFAULT_COUNTRY = 'GH';

function countryFor(iso) {
    return COUNTRIES.find((country) => country.iso === iso) ?? null;
}

/** Split a stored +E.164 number back into the country and national digits. */
export function splitPhone(stored, isoHint = null) {
    const digits = String(stored ?? '').replace(/[^0-9]/g, '');

    if (digits === '') {
        return { country: isoHint ?? DEFAULT_COUNTRY, national: '' };
    }

    if (isoHint) {
        const hinted = countryFor(isoHint);

        if (hinted !== null && digits.startsWith(String(hinted.code))) {
            return { country: isoHint, national: digits.slice(String(hinted.code).length) };
        }
    }

    for (let length = 4; length >= 1; length -= 1) {
        const code = Number(digits.slice(0, length));

        if (! code) {
            continue;
        }

        const iso = PREFERRED_FOR_CODE[code] ?? COUNTRIES.find((country) => country.code === code)?.iso;

        if (iso) {
            return { country: iso, national: digits.slice(length) };
        }
    }

    return { country: isoHint ?? DEFAULT_COUNTRY, national: digits };
}

/**
 * Join what was typed to the chosen country, exactly as the browser's
 * phone field does: a trunk zero is dropped (024 123 4567 in Ghana is
 * +233 24 123 4567), except in the countries that keep it.
 */
export function composePhone(country, national) {
    const found = countryFor(country);
    let digits = String(national ?? '').replace(/[^0-9]/g, '');

    if (found === null || ! KEEPS_TRUNK_ZERO.includes(found.iso)) {
        digits = digits.replace(/^0+/, '');
    }

    if (digits === '') {
        return '';
    }

    return found === null ? digits : `+${found.code}${digits}`;
}

function countryOptions() {
    const lang = language();

    return [...COUNTRIES]
        .sort((a, b) => a[lang].localeCompare(b[lang], lang))
        .map((country) => ({ value: country.iso, label: `${country[lang]} (+${country.code})` }));
}

/* ---------------------------------------------------------------- fields */

function inputFor(spec, id) {
    const input = el('input', {
        id,
        class: 'input',
        type: spec.type === 'date' ? 'date'
            : spec.type === 'password' ? 'password'
            : spec.type === 'email' ? 'email'
            : spec.type === 'number' ? 'number'
            : 'text',
        value: spec.value ?? '',
        placeholder: spec.placeholder,
        inputmode: spec.type === 'money' ? 'numeric'
            : spec.type === 'number' ? 'decimal'
            : spec.type === 'email' ? 'email'
            : undefined,
        autocapitalize: spec.type === 'text' || spec.type === 'textarea' ? 'sentences' : 'none',
        autocorrect: 'off',
        autocomplete: spec.autocomplete ?? 'off',
        maxlength: spec.maxlength,
        min: spec.min,
        max: spec.max,
        step: spec.step,
        readonly: spec.readonly === true,
        disabled: spec.disabled === true,
    });

    if (spec.type === 'money') {
        input.addEventListener('input', () => {
            const cleaned = wholeUnits(input.value);

            if (cleaned !== input.value) {
                input.value = cleaned;
            }
        });
    }

    return input;
}

/**
 * One field: the label, the control and the place its error goes.
 *
 * Returns { name, node, read(), write(value), setError(text), show(bool) }.
 * `read()` gives the typed value in the form the API wants for that type:
 * a string for text, digits for money, a boolean for a toggle, an array of
 * rows for lines, and { number, country } for a telephone.
 */
function build(spec, ctx) {
    const id = `sheet-${ctx.prefix}${spec.name}`;
    let read;
    let write = () => {};
    let control;
    let focusable = null;

    if (spec.type === 'note') {
        return {
            name: spec.name, spec,
            node: el('div', { class: `sheet-note${spec.tone ? ` is-${spec.tone}` : ''}`, text: spec.label }),
            read: () => undefined, write: () => {}, setError: () => {}, show: () => {},
        };
    }

    if (spec.type === 'heading') {
        return {
            name: spec.name, spec,
            node: el('div', { class: 'sheet-heading' }, [
                el('h3', { class: 'sheet-heading-title', text: spec.label }),
                spec.hint ? el('p', { class: 'sheet-heading-sub', text: spec.hint }) : null,
            ]),
            read: () => undefined, write: () => {}, setError: () => {}, show: () => {},
        };
    }

    if (spec.type === 'readonly') {
        const value = el('div', { class: 'sheet-readonly', text: spec.value ?? '—' });

        control = value;
        read = () => spec.value;
        write = (v) => { value.textContent = v === null || v === undefined || v === '' ? '—' : String(v); spec.value = v; };
    } else if (spec.type === 'select') {
        control = el('select', { id, class: 'input', disabled: spec.disabled === true }, (spec.options ?? []).map((option) => el('option', {
            value: option.value,
            text: option.label,
            selected: String(option.value) === String(spec.value ?? ''),
            disabled: option.disabled === true,
        })));
        read = () => control.value;
        write = (v) => { control.value = String(v ?? ''); };
        focusable = control;
    } else if (spec.type === 'textarea') {
        control = el('textarea', { id, class: 'input', rows: spec.rows ?? 3, maxlength: spec.maxlength, placeholder: spec.placeholder });
        control.value = spec.value ?? '';
        read = () => control.value.trim();
        write = (v) => { control.value = v ?? ''; };
        focusable = control;
    } else if (spec.type === 'toggle') {
        const box = el('input', { id, type: 'checkbox', class: 'toggle-input', checked: spec.value === true });

        control = el('label', { class: 'toggle', for: id }, [
            box,
            el('span', { class: 'toggle-track' }, [el('span', { class: 'toggle-thumb' })]),
            el('span', { class: 'toggle-label' }, [
                el('span', { text: spec.label }),
                spec.hint ? el('span', { class: 'field-hint', text: spec.hint }) : null,
            ]),
        ]);
        read = () => box.checked;
        write = (v) => { box.checked = v === true; };
        focusable = box;

        const node = el('div', { class: 'field field-toggle' }, [control, el('span', { class: 'field-error', hidden: true })]);

        box.addEventListener('change', () => ctx.changed(spec.name));

        return wrap(node);
    } else if (spec.type === 'radio') {
        const group = el('div', { class: 'radio-group' }, (spec.options ?? []).map((option) => {
            const radio = el('input', { type: 'radio', name: id, value: option.value, checked: String(option.value) === String(spec.value ?? '') });

            radio.addEventListener('change', () => ctx.changed(spec.name));

            return el('label', { class: 'radio' }, [
                radio,
                el('span', { class: 'radio-words' }, [
                    el('span', { class: 'radio-label', text: option.label }),
                    option.hint ? el('span', { class: 'field-hint', text: option.hint }) : null,
                ]),
            ]);
        }));

        control = group;
        read = () => group.querySelector('input:checked')?.value ?? '';
        write = (v) => { for (const r of group.querySelectorAll('input')) { r.checked = r.value === String(v); } };
    } else if (spec.type === 'phone') {
        const start = splitPhone(spec.value, spec.country);
        const country = el('select', { class: 'input phone-country' }, countryOptions().map((option) => el('option', {
            value: option.value, text: option.label, selected: option.value === start.country,
        })));
        const national = el('input', {
            id, class: 'input phone-number', type: 'tel', inputmode: 'tel', value: start.national,
            autocomplete: 'off', placeholder: spec.placeholder,
        });

        control = el('div', { class: 'phone' }, [country, national]);
        read = () => ({ number: composePhone(country.value, national.value), country: national.value.trim() === '' ? null : country.value });
        write = (v) => {
            const split = splitPhone(v?.number ?? v, v?.country);

            country.value = split.country;
            national.value = split.national;
        };
        focusable = national;
        country.addEventListener('change', () => ctx.changed(spec.name));
    } else if (spec.type === 'picker') {
        return picker(spec, ctx, id);
    } else if (spec.type === 'lines') {
        return lines(spec, ctx, id);
    } else {
        control = inputFor(spec, id);
        read = () => control.value.trim();
        write = (v) => { control.value = v ?? ''; };
        focusable = control;
    }

    const suffix = spec.suffix ? el('span', { class: 'input-suffix', text: spec.suffix }) : null;
    const controlNode = suffix
        ? el('div', { class: 'input-with-suffix' }, [control, suffix])
        : control;

    const node = el('div', { class: `field${spec.type === 'money' ? ' field-money' : ''}` }, [
        el('label', { class: 'label', for: id }, [
            el('span', { text: spec.label ?? '' }),
            spec.required ? el('span', { class: 'label-required', text: ' *' }) : null,
        ]),
        controlNode,
        spec.hint === undefined ? null : el('span', { class: 'field-hint', text: spec.hint }),
        el('span', { class: 'field-error', hidden: true }),
    ]);

    if (spec.type === 'money') {
        const preview = el('span', { class: 'field-hint field-money-preview' });

        node.insertBefore(preview, node.querySelector('.field-error'));

        const paint = () => { preview.textContent = control.value === '' ? '' : money(control.value); };

        control.addEventListener('input', paint);
        paint();
    }

    if (control.tagName === 'INPUT' || control.tagName === 'SELECT' || control.tagName === 'TEXTAREA') {
        control.addEventListener('input', () => ctx.changed(spec.name));
        control.addEventListener('change', () => ctx.changed(spec.name));
    }

    return wrap(node);

    function wrap(wrapped) {
        const errorNode = wrapped.querySelector('.field-error');

        return {
            name: spec.name,
            spec,
            node: wrapped,
            read,
            write,
            focus: () => focusable?.focus?.(),
            setError(text) {
                errorNode.textContent = text ?? '';
                errorNode.hidden = ! text;
                wrapped.classList.toggle('has-error', Boolean(text));
            },
            show(visible) {
                wrapped.hidden = ! visible;
            },
            setDisabled(disabled) {
                for (const c of wrapped.querySelectorAll('input, select, textarea, button')) {
                    c.disabled = disabled;
                }

                wrapped.classList.toggle('is-disabled', disabled);
            },
            setOptions(options, keep = true) {
                if (control.tagName !== 'SELECT') {
                    return;
                }

                const current = control.value;

                clear(control).append(...options.map((option) => el('option', { value: option.value, text: option.label, disabled: option.disabled === true })));

                if (keep && options.some((option) => String(option.value) === current)) {
                    control.value = current;
                } else if (options.length > 0) {
                    control.value = String(options[0].value);
                }
            },
        };
    }
}

/**
 * A searchable picker: type to narrow, tap to choose. `options` is the seed
 * list; `search(term)` may fetch more. The chosen id is the value.
 */
function picker(spec, ctx, id) {
    let options = [...(spec.options ?? [])];
    let chosen = spec.value === undefined || spec.value === null ? '' : String(spec.value);
    let timer = null;

    const input = el('input', {
        id, class: 'input', type: 'search', autocapitalize: 'none', autocorrect: 'off', spellcheck: 'false',
        autocomplete: 'off', placeholder: spec.placeholder ?? t('search.placeholder'),
        value: options.find((o) => String(o.value) === chosen)?.label ?? '',
    });
    const results = el('ul', { class: 'picker-results', hidden: true });
    const errorNode = el('span', { class: 'field-error', hidden: true });

    function paint(term) {
        const lowered = term.toLowerCase();
        const found = options.filter((o) => lowered === '' || o.label.toLowerCase().includes(lowered) || (o.keywords ?? '').toLowerCase().includes(lowered)).slice(0, 12);

        clear(results);

        if (found.length === 0) {
            results.append(el('li', { class: 'picker-empty', text: spec.empty ?? t('search.none', { query: term }) }));
        } else {
            results.append(...found.map((o) => el('li', {
                class: `picker-option${String(o.value) === chosen ? ' is-chosen' : ''}`,
                onmousedown: (event) => event.preventDefault(),
                onclick: () => choose(o),
            }, [
                el('span', { class: 'picker-option-label', text: o.label }),
                o.sub ? el('span', { class: 'picker-option-sub', text: o.sub }) : null,
            ])));
        }

        results.hidden = false;
    }

    function choose(option) {
        chosen = String(option.value);
        input.value = option.label;
        results.hidden = true;
        ctx.changed(spec.name);
    }

    input.addEventListener('focus', () => paint(input.value.trim()));
    input.addEventListener('blur', () => { results.hidden = true; if (input.value.trim() === '') { chosen = ''; ctx.changed(spec.name); } });
    input.addEventListener('input', () => {
        const term = input.value.trim();

        /* Typing over a chosen label un-chooses it until something is tapped. */
        if (chosen !== '' && options.find((o) => String(o.value) === chosen)?.label !== input.value) {
            chosen = '';
            ctx.changed(spec.name);
        }

        paint(term);

        if (spec.search) {
            clearTimeout(timer);
            timer = setTimeout(async () => {
                try {
                    const more = await spec.search(term);

                    for (const o of more ?? []) {
                        if (! options.some((held) => String(held.value) === String(o.value))) {
                            options.push(o);
                        }
                    }

                    if (document.activeElement === input) {
                        paint(input.value.trim());
                    }
                } catch {
                    /* A failed lookup leaves the seed list; nothing to say. */
                }
            }, 250);
        }
    });

    const node = el('div', { class: 'field field-picker' }, [
        el('label', { class: 'label', for: id }, [
            el('span', { text: spec.label ?? '' }),
            spec.required ? el('span', { class: 'label-required', text: ' *' }) : null,
        ]),
        el('div', { class: 'picker' }, [input, results]),
        spec.hint === undefined ? null : el('span', { class: 'field-hint', text: spec.hint }),
        errorNode,
    ]);

    return {
        name: spec.name, spec, node,
        read: () => chosen,
        chosenOption: () => options.find((o) => String(o.value) === chosen) ?? null,
        write(v) {
            chosen = v === null || v === undefined ? '' : String(v);
            input.value = options.find((o) => String(o.value) === chosen)?.label ?? '';
        },
        setOptions(next) {
            options = [...next];

            if (! options.some((o) => String(o.value) === chosen)) {
                chosen = '';
                input.value = '';
            }
        },
        focus: () => input.focus(),
        setError(text) { errorNode.textContent = text ?? ''; errorNode.hidden = ! text; node.classList.toggle('has-error', Boolean(text)); },
        show(visible) { node.hidden = ! visible; },
        setDisabled(disabled) { input.disabled = disabled; node.classList.toggle('is-disabled', disabled); },
    };
}

/**
 * Repeating rows - expense lines, owners and their shares, the units of a
 * new property. `columns` are field specs; each row is one object.
 */
function lines(spec, ctx, id) {
    const rows = [];
    const list = el('div', { class: 'lines' });
    const errorNode = el('span', { class: 'field-error', hidden: true });
    const total = spec.total ? el('div', { class: 'lines-total' }) : null;

    function paintTotal() {
        if (total) {
            total.textContent = spec.total(read());
        }
    }

    function addRow(values = {}) {
        const fields = spec.columns.map((column) => build({ ...column, value: values[column.name] ?? column.value }, {
            prefix: `${spec.name}-${rows.length}-`,
            changed: () => { applyRow(); paintTotal(); ctx.changed(spec.name); },
        }));

        /* A column may depend on another in the same row, as a field may. */
        function applyRow() {
            const current = Object.fromEntries(fields.map((f) => [f.name, f.read()]));

            for (const f of fields) {
                if (typeof f.spec.when === 'function') {
                    f.show(f.spec.when(current));
                }
            }
        }

        applyRow();

        const remove = el('button', { class: 'icon-button lines-remove', type: 'button', 'aria-label': spec.removeLabel ?? t('common.remove') }, [icon('x-close', { size: 18 })]);
        const node = el('div', { class: 'lines-row' }, [
            el('div', { class: 'lines-fields' }, fields.map((f) => f.node)),
            remove,
        ]);
        const row = { node, fields };

        remove.addEventListener('click', () => {
            if (rows.length <= (spec.min ?? 0)) {
                errorNode.textContent = spec.minMessage ?? '';
                errorNode.hidden = ! spec.minMessage;

                return;
            }

            rows.splice(rows.indexOf(row), 1);
            node.remove();
            paintTotal();
            ctx.changed(spec.name);
        });

        rows.push(row);
        list.append(node);
        paintTotal();

        return row;
    }

    function read() {
        return rows.map((row) => Object.fromEntries(row.fields.map((f) => [f.name, f.read()])));
    }

    for (const values of spec.value ?? []) {
        addRow(values);
    }

    while (rows.length < (spec.min ?? 0)) {
        addRow();
    }

    const node = el('div', { class: 'field field-lines' }, [
        spec.label ? el('div', { class: 'label' }, [
            el('span', { text: spec.label }),
            spec.required ? el('span', { class: 'label-required', text: ' *' }) : null,
        ]) : null,
        spec.hint === undefined ? null : el('span', { class: 'field-hint', text: spec.hint }),
        list,
        el('div', { class: 'lines-foot' }, [
            el('button', { class: 'button button-secondary button-compact', type: 'button', onclick: () => { addRow(); ctx.changed(spec.name); } }, [
                icon('plus', { size: 16 }), el('span', { text: spec.addLabel ?? t('common.add') }),
            ]),
            total,
        ]),
        errorNode,
    ]);

    return {
        name: spec.name, spec, node,
        read,
        rows: () => rows,
        write(values) {
            for (const row of rows) { row.node.remove(); }
            rows.length = 0;
            for (const v of values ?? []) { addRow(v); }
        },
        focus: () => rows[0]?.fields[0]?.focus?.(),
        setError(text) { errorNode.textContent = text ?? ''; errorNode.hidden = ! text; },
        setRowError(index, name, text) { rows[index]?.fields.find((f) => f.name === name)?.setError(text); },
        show(visible) { node.hidden = ! visible; },
        setDisabled() {},
    };
}

/* ----------------------------------------------------------------- sheet */

/**
 * @param {object} options
 * @param {string} options.title
 * @param {string} [options.description]
 * @param {Array}  options.fields
 * @param {string} options.submitLabel
 * @param {'primary'|'danger'} [options.submitKind]
 * @param {'sm'|'lg'} [options.width]
 * @param {(values, api) => void} [options.onChange]  runs after every edit and once at open
 * @param {(values) => object|null} [options.validate]  field -> message, or null when fine
 * @param {(values) => Node} [options.review]  when given, Submit first shows this and asks to Confirm
 * @param {(values: object) => Promise<any>} options.onSubmit  throws ApiError to show errors
 * @param {boolean} [options.submitDisabled]
 * @returns {Promise<any|false>} what onSubmit returned (true when nothing), false when dismissed
 */
export function openSheet({
    title, description, fields, submitLabel, submitKind = 'primary', width = 'sm',
    onChange, validate, review, onSubmit, submitDisabled = false, cancelLabel,
}) {
    return new Promise((resolve) => {
        const backdrop = el('div', { class: 'sheet-backdrop' });
        let busy = false;
        let reviewing = false;

        function close(result) {
            if (busy) {
                return;
            }

            document.removeEventListener('keydown', onKey);
            backdrop.remove();
            resolve(result);
        }

        function onKey(event) {
            if (event.key === 'Escape') {
                /* Only the topmost sheet answers Escape, as the web's drawers do. */
                const sheets = document.querySelectorAll('.sheet-backdrop');

                if (sheets[sheets.length - 1] === backdrop) {
                    close(false);
                }
            }
        }

        /* Nothing to fill in: this sheet is telling, not asking. */
        const informational = cancelLabel === undefined && fields.every((spec) => spec.type === 'note' || spec.type === 'heading' || spec.type === 'readonly');

        const built = [];
        const byName = new Map();

        const ctx = {
            prefix: '',
            changed(name) {
                applyConditions();
                onChange?.(values(), api, name);
            },
        };

        for (const spec of fields) {
            const f = build(spec, ctx);

            built.push(f);
            byName.set(f.name, f);
        }

        function values() {
            return Object.fromEntries(built.filter((f) => f.spec.type !== 'note' && f.spec.type !== 'heading').map((f) => [f.name, f.read()]));
        }

        function applyConditions() {
            const current = values();

            for (const f of built) {
                if (typeof f.spec.when === 'function') {
                    f.show(f.spec.when(current));
                }
            }
        }

        const api = {
            get: (name) => byName.get(name),
            set: (name, value) => byName.get(name)?.write(value),
            show: (name, visible) => byName.get(name)?.show(visible),
            disable: (name, disabled) => byName.get(name)?.setDisabled(disabled),
            options: (name, options, keep) => byName.get(name)?.setOptions(options, keep),
            error: (name, text) => byName.get(name)?.setError(text),
            values,
            setSubmitDisabled(disabled) { submit.disabled = disabled; },
            setSubmitLabel(label) { submit.textContent = label; },
        };

        const submit = el('button', { class: `button ${submitKind === 'danger' ? 'button-danger' : ''}`, type: 'submit', text: submitLabel, disabled: submitDisabled });
        const message = el('p', { class: 'error', role: 'alert', hidden: true });
        const body = el('div', { class: 'sheet-body' }, built.map((f) => f.node));
        const reviewPane = el('div', { class: 'sheet-body', hidden: true });
        const cancel = el('button', { class: 'button button-secondary', type: 'button', text: cancelLabel ?? t('common.cancel'), onclick: () => (reviewing ? leaveReview() : close(false)) });

        function showMessage(text) {
            message.textContent = text ?? '';
            message.hidden = ! text;
        }

        function clearErrors() {
            for (const f of built) {
                f.setError(null);
            }

            showMessage(null);
        }

        function leaveReview() {
            reviewing = false;
            reviewPane.hidden = true;
            body.hidden = false;
            submit.textContent = submitLabel;
            cancel.textContent = cancelLabel ?? t('common.cancel');
        }

        function enterReview(current) {
            reviewing = true;
            mount(reviewPane, review(current));
            body.hidden = true;
            reviewPane.hidden = false;
            submit.textContent = t('ui.tenants.confirm');
            cancel.textContent = t('ui.tenants.back');
            sheet.scrollTop = 0;
        }

        /** Laravel's 422 shape, mapped field by field, dotted keys included. */
        function showFailure(failure) {
            const errors = failure?.errors ?? null;
            let landed = false;

            if (errors) {
                for (const [key, list] of Object.entries(errors)) {
                    const text = Array.isArray(list) ? list[0] : String(list);
                    const parts = key.split('.');
                    const f = byName.get(key) ?? byName.get(parts[0]);

                    if (f && f.spec.type === 'lines' && parts.length === 3) {
                        f.setRowError(Number(parts[1]), parts[2], text);
                        landed = true;
                    } else if (f) {
                        f.setError(text);
                        landed = true;
                    }
                }
            }

            const code = failure?.code ? ` (${failure.code})` : '';

            if (! landed || failure?.isValidation !== true) {
                showMessage(`${failure?.message ?? t('signin.offline')}${code}`);
            } else if (errors) {
                /* Say it once above too: the rejected field may be scrolled away. */
                const first = Object.values(errors)[0];

                showMessage(`${Array.isArray(first) ? first[0] : first}${code}`);
            }

            sheet.scrollTop = 0;
        }

        const form = el('form', {
            class: 'sheet-form',
            novalidate: true,
            onsubmit: async (event) => {
                event.preventDefault();

                if (busy) {
                    return;
                }

                clearErrors();

                const current = values();

                if (! reviewing) {
                    const problems = validate?.(current) ?? null;

                    if (problems && Object.keys(problems).length > 0) {
                        for (const [name, text] of Object.entries(problems)) {
                            if (name === '_') {
                                showMessage(text);
                            } else {
                                byName.get(name)?.setError(text);
                            }
                        }

                        if (! problems._) {
                            showMessage(Object.values(problems)[0]);
                        }

                        sheet.scrollTop = 0;

                        return;
                    }

                    if (review) {
                        enterReview(current);

                        return;
                    }
                }

                busy = true;
                submit.disabled = true;
                cancel.disabled = true;
                const label = submit.textContent;

                submit.textContent = t('sheet.saving');

                try {
                    const result = await onSubmit(current);

                    busy = false;

                    /*
                     * A submit that returns false was not a submit: the
                     * person declined the confirmation inside it. The sheet
                     * stays, with everything they typed.
                     */
                    if (result === false) {
                        submit.disabled = false;
                        cancel.disabled = false;
                        submit.textContent = label;

                        if (reviewing) {
                            leaveReview();
                        }

                        return;
                    }

                    close(result === undefined ? true : result);
                } catch (failure) {
                    busy = false;
                    submit.disabled = false;
                    cancel.disabled = false;
                    submit.textContent = label;

                    if (reviewing) {
                        leaveReview();
                    }

                    showFailure(failure);
                }
            },
        }, [
            message,
            body,
            reviewPane,
            el('div', { class: 'sheet-actions' }, informational ? [submit] : [cancel, submit]),
        ]);

        const sheet = el('div', {
            class: `sheet sheet-${width}`,
            role: 'dialog',
            'aria-modal': 'true',
        }, [
            el('header', { class: 'sheet-head' }, [
                el('div', { class: 'sheet-head-words' }, [
                    el('h2', { class: 'sheet-title', text: title }),
                    description ? el('p', { class: 'sheet-description', text: description }) : null,
                ]),
                el('button', {
                    class: 'icon-button', type: 'button',
                    'aria-label': t('common.cancel'),
                    onclick: () => close(false),
                }, [icon('x-close', { size: 20 })]),
            ]),
            form,
        ]);

        /* A tap on the backdrop dismisses; a tap inside must not. */
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) {
                close(false);
            }
        });

        document.addEventListener('keydown', onKey);

        clear(backdrop).append(sheet);
        document.body.append(backdrop);

        applyConditions();
        onChange?.(values(), api, null);

        /*
         * A note field has no input to focus. Calling it anyway threw, and
         * because the sheet is built inside a promise the throw surfaced as
         * an unhandled rejection.
         */
        built.find((f) => f.focus && ! f.node.hidden)?.focus?.();
    });
}

/**
 * A plain question with two answers. Replaces window.confirm, which a
 * WebView renders as a bare system alert with no product wording.
 */
export async function confirmSheet({ title, body, confirmLabel, danger = false }) {
    const answer = await openSheet({
        title,
        fields: [{ name: 'note', type: 'note', label: body }],
        submitLabel: confirmLabel ?? t('common.confirm'),
        submitKind: danger ? 'danger' : 'primary',
        onSubmit: async () => true,
    });

    return answer === true;
}

/** Something to read, and one button to close it. */
export async function informSheet({ title, body, tone }) {
    await openSheet({
        title,
        fields: [{ name: 'note', type: 'note', label: body, tone }],
        submitLabel: t('common.close'),
        onSubmit: async () => true,
    });
}
