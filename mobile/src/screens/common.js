/*
 * What every full-client screen shares: the page header, the banner
 * host, the document buttons, the role and status vocabularies.
 */

import { el } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { openDocument } from '../data/exports.js';
import { downloadFile } from '../data/files.js';
import { banner } from '../ui/toast.js';
import { badge, button } from '../ui/table.js';
import { money } from '../ui/money.js';
import { formatDate, domain, joinParts } from '../ui/format.js';

export function rows(payload) {
    return Array.isArray(payload) ? payload : (payload?.data ?? []);
}

export function totalOf(payload) {
    return payload?.total ?? payload?.meta?.total ?? rows(payload).length;
}

export function pageMeta(payload, fallbackPerPage) {
    return {
        current_page: payload?.current_page ?? 1,
        last_page: payload?.last_page ?? 1,
        total: totalOf(payload),
        from: payload?.from ?? null,
        to: payload?.to ?? null,
        per_page: payload?.per_page ?? fallbackPerPage,
    };
}

export function screenHead({ eyebrow, title, sub, actions = [] }) {
    return el('header', { class: 'screen-head' }, [
        el('div', {}, [
            eyebrow ? el('p', { class: 'screen-eyebrow', text: eyebrow }) : null,
            el('h1', { class: 'screen-title', text: title }),
            sub ? el('p', { class: 'screen-sub', text: sub }) : null,
        ]),
        actions.filter(Boolean).length > 0 ? el('div', { class: 'screen-actions' }, actions.filter(Boolean)) : null,
    ]);
}

export function bannerHost() {
    return el('div', { class: 'banner-host', hidden: true });
}

/** Show a failure in the page banner, in the server's words with its code. */
export function showError(host, failure, fallback) {
    const message = failure?.isOffline === true
        ? t('signin.offline')
        : (failure?.message ?? fallback ?? t('ui.core.request_failed'));

    banner(host, { message: `${message}${failure?.code ? ` (${failure.code})` : ''}`, tone: 'error', sticky: true });
}

export function showSuccess(host, message, actions = []) {
    banner(host, { message, tone: 'success', actions });
}

/** A button that opens a PDF through the signed-link exchange. */
export function pdfButton(client, endpoint, label, { kind = 'secondary', iconName = 'file-05', onFail } = {}) {
    return button(label, {
        kind, iconName,
        onClick: async () => {
            try {
                await openDocument(client, endpoint);
            } catch (failure) {
                onFail?.(failure);
            }
        },
    });
}

/** A button that fetches a CSV/XLSX/JSON and hands it to the share sheet. */
export function fileButton(client, endpoint, label, fallbackName, { kind = 'secondary', iconName = 'download-01', onFail, busyLabel } = {}) {
    return button(label, {
        kind, iconName,
        onClick: async (node) => {
            const span = node.querySelector('span');
            const was = span.textContent;

            span.textContent = busyLabel ?? t('ui.settings.exporting');

            try {
                await downloadFile(client, endpoint, fallbackName);
            } catch (failure) {
                onFail?.(failure);
            } finally {
                span.textContent = was;
            }
        },
    });
}

/** "Resend" that reads Sending… then Sent for 1.8 s, as the web's does. */
export function resendButton(client, endpoint, { label, sending, sent, onFail } = {}) {
    return button(label ?? t('ui.owners.resend'), {
        iconName: 'send',
        onClick: async (node) => {
            const span = node.querySelector('span');
            const was = span.textContent;

            span.textContent = sending ?? t('ui.owners.sending');

            try {
                await client.post(endpoint, {});
                span.textContent = sent ?? t('ui.owners.sent');
                await new Promise((resolve) => setTimeout(resolve, 1800));
            } catch (failure) {
                onFail?.(failure);
            } finally {
                if (node.isConnected) {
                    span.textContent = was;
                }
            }
        },
    });
}

export function moneyCell(amount, { negative = false, strong = false } = {}) {
    return el('span', { class: [strong ? 'cell-strong' : '', negative ? 'is-negative' : ''].join(' ').trim() || undefined, text: money(amount ?? 0) });
}

export function partyName(party) {
    return party?.name || party?.legal_name || (party?.id ? `#${party.id}` : '');
}

export function partyContact(party) {
    return joinParts([party?.phone, party?.email]);
}

export function initials(name) {
    return String(name ?? '').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0].toUpperCase()).join('') || '?';
}

export function avatar(user, { size } = {}) {
    const node = el('span', { class: `avatar-initials${size === 'lg' ? ' avatar-lg' : ''}` });

    if (user?.avatar) {
        node.append(el('img', { src: user.avatar, alt: '' }));
    } else {
        node.textContent = initials(user?.name);
    }

    return node;
}

export function roleLabel(role) {
    return domain('roles', role);
}

export function roleChip(role) {
    return badge(roleLabel(role), role === 'administrator' ? 'info' : role === 'property_manager' ? 'success' : 'neutral');
}

export function partyTypeChip(type) {
    return badge(domain('parties', type), type);
}

export function partyRoleChip(role) {
    const label = role === 'managing_organisation' ? t('ui.parties.managing_organisation') : domain('parties', role);

    return badge(label, role === 'managing_organisation' ? 'neutral' : role);
}

export function leaseStatusChip(status) {
    return badge(domain('tenants.lease_status', status), status);
}

export function leaseStatusLabel(status) {
    return domain('tenants.lease_status', status);
}

export function frequencyLabel(value) {
    const map = { monthly: 'ui.leases.frequency_month', quarterly: 'ui.leases.frequency_quarter', bi_yearly: 'ui.leases.frequency_six_months', yearly: 'ui.leases.frequency_year' };

    return map[value] ? t(map[value]) : domain('leases', value);
}

export function paymentMethodLabel(value) {
    return domain('tenants.payment_method', value === 'mobile_money' ? 'momo' : value);
}

export function fundTypeLabel(value) {
    return domain('tenants.fund_type', value);
}

export function capitalise(value) {
    const text = String(value ?? '');

    return text.charAt(0).toUpperCase() + text.slice(1);
}

export function dateCell(value) {
    return formatDate(value) || '—';
}

export function dash(value) {
    return value === null || value === undefined || value === '' ? '—' : String(value);
}

export function iconLabel(iconName, text) {
    return el('span', { class: 'inline' }, [icon(iconName, { size: 16 }), el('span', { text })]);
}

/** A labelled control for a filter bar. */
export function filterField(label, control, { span } = {}) {
    return el('div', { class: `field${span ? ' span-2' : ''}` }, [
        el('label', { class: 'label', text: label }),
        control,
    ]);
}

export function selectControl(options, value, onChange) {
    const select = el('select', { class: 'input' }, options.map((option) => el('option', {
        value: String(option.value), text: option.label, selected: String(option.value) === String(value ?? ''),
    })));

    select.addEventListener('change', () => onChange(select.value));

    return select;
}

export function textControl({ value = '', placeholder, type = 'text', onInput, onChange, debounce = 300, maxlength, inputmode }) {
    const input = el('input', { class: 'input', type, value, placeholder, maxlength, inputmode, autocapitalize: 'none', autocorrect: 'off' });
    let timer = null;

    if (onInput) {
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => onInput(input.value.trim()), debounce);
        });
    }

    if (onChange) {
        input.addEventListener('change', () => onChange(input.value.trim()));
    }

    return input;
}

export function dateControl(value, onChange) {
    const input = el('input', { class: 'input', type: 'date', value: value ?? '' });

    input.addEventListener('change', () => onChange(input.value));

    return input;
}

export function tabs(entries, active, onSelect) {
    return el('div', { class: 'tabs', role: 'tablist' }, entries.filter(Boolean).map((entry) => el('button', {
        class: `tab-pill${entry.id === active ? ' is-active' : ''}`, type: 'button', role: 'tab',
        'aria-selected': entry.id === active ? 'true' : 'false',
        text: entry.label, onclick: () => onSelect(entry.id),
    })));
}

export function query(params) {
    const search = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value !== null && value !== undefined && String(value).trim() !== '') {
            search.set(key, String(value));
        }
    }

    const text = search.toString();

    return text === '' ? '' : `?${text}`;
}

export function dl(entries) {
    return el('dl', { class: 'dl' }, entries.filter((entry) => entry !== null).flatMap(([label, value]) => [
        el('dt', { text: label }),
        el('dd', { text: value === null || value === undefined || value === '' ? '—' : String(value) }),
    ]));
}
