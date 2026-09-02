/*
 * Settings - the browser application's seven tabs, entry for entry:
 * Organisation, Users, License, Preferences, Devices, Data, About.
 *
 * Administrator territory. The Organisation and Preferences tabs edit one
 * record and share one PUT, exactly as the web does; the danger zone at
 * the foot of Organisation closes the account, which is the one act in the
 * product from which there is nothing to return to.
 */

import { el, mount } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can, role } from '../auth/capabilities.js';
import { openSheet, confirmSheet, splitPhone } from '../ui/sheet.js';
import { dangerConfirmation } from '../ui/confirm.js';
import { table, pagination, pageSize, loading, emptyState, badge, button, stat, section } from '../ui/table.js';
import { formatDate, formatDateTime, formatNumber, joinParts } from '../ui/format.js';
import { openDocument } from '../data/exports.js';
import { downloadFile } from '../data/files.js';
import * as store from '../data/store.js';
import {
    screenHead, bannerHost, showError, showSuccess, rows, pageMeta, tabs, filterField, selectControl,
    textControl, query, avatar, roleLabel, dl, pdfButton, fileButton,
} from './common.js';

const TABS = ['organisation', 'users', 'license', 'preferences', 'devices', 'data', 'about'];

function phoneValue(number, country) {
    return { number: number ?? '', country: country ?? null };
}

export function settingsScreen(client, { config, version, onOrganisationSaved, onSignedOut, initialTab } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const body = el('div');
    let active = initialTab && TABS.includes(initialTab) ? initialTab : 'organisation';
    let organisation = null;
    let notConfigured = false;
    let licence = null;
    let presentation = null;

    async function loadOrganisation() {
        try {
            organisation = await client.get(endpoints.managingOrganisation);
            notConfigured = false;
        } catch (failure) {
            if (failure?.status === 404) {
                organisation = null;
                notConfigured = true;
            } else {
                throw failure;
            }
        }

        licence = await client.get(endpoints.license).catch(() => null);
        presentation = await client.get('/presentation-config').catch(() => null);
    }

    /* ---------------------------------------------------- one merged PUT */

    function organisationPayload(values) {
        const org = organisation ?? {};

        return {
            legal_name: values.legal_name ?? org.legal_name ?? org.name ?? '',
            address: values.address ?? org.address ?? '',
            phone: values.phone?.number ?? org.phone ?? null,
            phone_country: values.phone?.country ?? org.phone_country ?? null,
            alternate_phone: values.alternate_phone?.number ?? org.alternate_phone ?? null,
            alternate_phone_country: values.alternate_phone?.country ?? org.alternate_phone_country ?? null,
            email: (values.email ?? org.email) || null,
            contact_person_name: values.contact_person_name ?? org.contact_person_name ?? '',
            contact_person_phone: values.contact_person_phone?.number ?? org.contact_person_phone ?? '',
            contact_person_phone_country: values.contact_person_phone?.country ?? org.contact_person_phone_country ?? null,
            contact_person_email: values.contact_person_email ?? org.contact_person_email ?? '',
            registration_number: (values.registration_number ?? org.registration_number) || null,
            vat_tin: (values.vat_tin ?? org.vat_tin) || null,
            default_vat_rate: Number(values.default_vat_rate ?? org.default_vat_rate ?? presentation?.default_vat_rate ?? 0),
            language: values.language ?? org.language ?? presentation?.language ?? 'en',
            currency: values.currency ?? org.currency ?? presentation?.currency ?? 'GHS',
            party_emails_enabled: values.party_emails_enabled ?? (org.party_emails_enabled !== false),
            data_tools_enabled: values.data_tools_enabled ?? (org.data_tools_enabled === true),
            sort_parties_by_surname: values.sort_parties_by_surname ?? (org.sort_parties_by_surname === true),
            bank_name: (values.bank_name ?? org.bank_name) || null,
            bank_account_name: (values.bank_account_name ?? org.bank_account_name) || null,
            bank_account_number: (values.bank_account_number ?? org.bank_account_number) || null,
            bank_branch: (values.bank_branch ?? org.bank_branch) || null,
            notes: (values.notes ?? org.notes) || null,
        };
    }

    async function saveOrganisation(values) {
        const before = { language: organisation?.language, currency: organisation?.currency };
        const saved = await client.put(endpoints.managingOrganisation, organisationPayload(values));

        organisation = saved;
        notConfigured = false;
        await store.fetchKey(client, 'organisation', endpoints.managingOrganisation);
        onOrganisationSaved?.(saved, before.language !== saved.language || before.currency !== saved.currency);
    }

    /* ------------------------------------------------------ organisation */

    function organisationTab() {
        const org = organisation ?? {};
        const usage = licence?.usage ?? {};

        const summary = section(t('ui.settings.summary'), dl([
            [t('ui.settings.summary_account'), licence?.organisation?.name ?? org.name],
            [t('ui.settings.summary_plan'), licence?.plan_label ?? licence?.plan],
            [t('ui.settings.summary_users'), usage.users],
            [t('ui.settings.summary_leases'), usage.active_leases],
            [t('ui.settings.summary_parties'), usage.parties],
            licence?.organisation?.created_on ? [t('ui.settings.summary_created'), formatDate(licence.organisation.created_on)] : null,
            licence?.on_trial ? [t('ui.settings.summary_trial'), formatDate(licence.trial_ends_on)] : null,
        ]), { sub: t('ui.settings.summary_description') });

        return el('div', { class: 'stack' }, [
            notConfigured ? el('p', { class: 'sheet-note is-info', text: t('ui.settings.not_configured') }) : null,
            section(t('ui.settings.organisation_details'), [
                dl([
                    [t('ui.settings.legal_name'), org.legal_name ?? org.name],
                    [t('ui.settings.address'), org.address],
                    [t('ui.settings.phone'), org.phone],
                    [t('ui.settings.alternate_phone'), org.alternate_phone],
                    [t('ui.settings.general_email'), org.email],
                ]),
                el('h3', { class: 'sheet-heading-title', text: t('ui.settings.primary_contact') }),
                dl([
                    [t('ui.settings.contact_person'), org.contact_person_name],
                    [t('ui.settings.contact_phone'), org.contact_person_phone],
                    [t('ui.settings.contact_email'), org.contact_person_email],
                ]),
                el('h3', { class: 'sheet-heading-title', text: t('ui.settings.registration') }),
                dl([
                    [t('ui.settings.registration_number'), org.registration_number],
                    [t('ui.settings.vat_tin'), org.vat_tin],
                ]),
                el('h3', { class: 'sheet-heading-title', text: t('ui.settings.banking_details') }),
                dl([
                    [t('ui.settings.bank_name'), org.bank_name],
                    [t('ui.settings.bank_branch'), org.bank_branch],
                    [t('ui.settings.account_name'), org.bank_account_name],
                    [t('ui.settings.account_number'), org.bank_account_number],
                ]),
                org.notes ? dl([[t('ui.settings.notes'), org.notes]]) : null,
            ], {
                actions: [button(t('ui.settings.save'), { kind: 'primary', iconName: 'edit-02', onClick: editOrganisation })],
            }),
            summary,
            section(t('ui.settings.close_account'), [
                el('p', { class: 'record-card-sub', text: t('ui.settings.close_account_description') }),
                button(t('ui.settings.close_account_action'), { kind: 'danger', iconName: 'trash-01', onClick: closeAccount }),
            ], { tone: 'danger' }),
        ]);
    }

    async function editOrganisation() {
        const org = organisation ?? {};

        const saved = await openSheet({
            title: t('ui.settings.organisation_details'),
            width: 'lg',
            submitLabel: t('ui.settings.save'),
            fields: [
                { name: 'legal_name', type: 'text', label: t('ui.settings.legal_name'), value: org.legal_name ?? org.name ?? '', placeholder: t('ui.settings.legal_name_placeholder'), required: true, maxlength: 255 },
                { name: 'address', type: 'textarea', rows: 2, label: t('ui.settings.address'), value: org.address ?? '', placeholder: t('ui.settings.address_placeholder'), required: true },
                { name: 'phone', type: 'phone', label: t('ui.settings.phone'), value: org.phone, country: org.phone_country },
                { name: 'alternate_phone', type: 'phone', label: t('ui.settings.alternate_phone'), value: org.alternate_phone, country: org.alternate_phone_country },
                { name: 'email', type: 'email', label: t('ui.settings.general_email'), value: org.email ?? '', maxlength: 255 },
                { name: 'h1', type: 'heading', label: t('ui.settings.primary_contact') },
                { name: 'contact_person_name', type: 'text', label: t('ui.settings.contact_person'), value: org.contact_person_name ?? '', required: true, maxlength: 255 },
                { name: 'contact_person_phone', type: 'phone', label: t('ui.settings.contact_phone'), value: org.contact_person_phone, country: org.contact_person_phone_country, required: true },
                { name: 'contact_person_email', type: 'email', label: t('ui.settings.contact_email'), value: org.contact_person_email ?? '', required: true, maxlength: 255 },
                { name: 'h2', type: 'heading', label: t('ui.settings.registration') },
                { name: 'registration_number', type: 'text', label: t('ui.settings.registration_number'), value: org.registration_number ?? '', maxlength: 255 },
                { name: 'vat_tin', type: 'text', label: t('ui.settings.vat_tin'), value: org.vat_tin ?? '', maxlength: 255 },
                { name: 'h3', type: 'heading', label: t('ui.settings.banking_details'), hint: t('ui.settings.optional') },
                { name: 'bank_name', type: 'text', label: t('ui.settings.bank_name'), value: org.bank_name ?? '', maxlength: 255 },
                { name: 'bank_branch', type: 'text', label: t('ui.settings.bank_branch'), value: org.bank_branch ?? '', maxlength: 255 },
                { name: 'bank_account_name', type: 'text', label: t('ui.settings.account_name'), value: org.bank_account_name ?? '', maxlength: 255 },
                { name: 'bank_account_number', type: 'text', label: t('ui.settings.account_number'), value: org.bank_account_number ?? '', maxlength: 255 },
                { name: 'notes', type: 'textarea', rows: 3, label: t('ui.settings.notes'), value: org.notes ?? '' },
            ],
            onSubmit: saveOrganisation,
        });

        if (saved) {
            showSuccess(errors, t('ui.settings.saved'));
            paint();
        }
    }

    async function closeAccount() {
        const name = licence?.organisation?.name ?? organisation?.name ?? '';
        const usage = licence?.usage ?? {};

        const closed = await openSheet({
            title: t('ui.settings.close_account'),
            description: t('ui.settings.close_account_drawer'),
            submitLabel: t('ui.settings.close_account_confirm'),
            submitKind: 'danger',
            fields: [
                { name: 'warning', type: 'note', tone: 'danger', label: t('ui.settings.close_account_warning') },
                { name: 'inventory', type: 'note', label: joinParts([
                    `${t('ui.settings.summary_users')}: ${formatNumber(usage.users ?? 0)}`,
                    `${t('ui.settings.summary_leases')}: ${formatNumber(usage.active_leases ?? 0)}`,
                    `${t('ui.settings.summary_parties')}: ${formatNumber(usage.parties ?? 0)}`,
                ]) },
                { name: 'name_confirmation', type: 'text', label: t('ui.settings.close_account_name_label'), hint: t('ui.settings.close_account_name_hint', { name }), required: true },
                { name: 'password', type: 'password', label: t('ui.settings.close_account_password_label'), autocomplete: 'current-password', required: true },
            ],
            onSubmit: async (values) => {
                await client.delete('/organisation', { name_confirmation: values.name_confirmation, password: values.password });

                return true;
            },
        });

        if (closed) {
            onSignedOut?.();
        }
    }

    /* ------------------------------------------------------------- users */

    function usersTab() {
        const wrap = el('div');
        const list = el('div');
        const size = pageSize('users');
        const filters = { search: '', role: '', is_active: '' };
        let page = 1;
        const me = store.read('me').data ?? {};
        const myId = (me.user ?? me).id;

        const controls = el('div', { class: 'filters' }, [
            filterField(t('ui.users.search'), textControl({ placeholder: t('ui.users.search_placeholder'), type: 'search', onInput: (v) => { filters.search = v; reload(1); } })),
            filterField(t('ui.users.role'), selectControl([
                { value: '', label: t('ui.users.all_roles') },
                { value: 'administrator', label: roleLabel('administrator') },
                { value: 'property_manager', label: roleLabel('property_manager') },
                { value: 'viewer', label: roleLabel('viewer') },
            ], '', (v) => { filters.role = v; reload(1); })),
            filterField(t('ui.users.status'), selectControl([
                { value: '', label: t('ui.users.all_statuses') },
                { value: '1', label: t('ui.users.active') },
                { value: '0', label: t('ui.users.inactive') },
            ], '', (v) => { filters.is_active = v; reload(1); })),
        ]);

        async function userForm(user = null) {
            const editing = user !== null;
            const self = editing && user.id === myId;

            const saved = await openSheet({
                title: editing ? t('ui.users.edit_user') : t('ui.users.add_user'),
                description: editing ? t('ui.users.edit_description') : t('ui.users.create_description'),
                submitLabel: t('ui.actions.save'),
                fields: [
                    { name: 'given_names', type: 'text', label: t('ui.users.given_names'), value: user?.given_names ?? '', maxlength: 255 },
                    { name: 'surname', type: 'text', label: t('ui.users.surname'), value: user?.surname ?? '', required: true, maxlength: 255 },
                    { name: 'email', type: 'email', label: t('ui.users.email'), value: user?.email ?? '', required: true, maxlength: 255, readonly: editing },
                    { name: 'phone', type: 'phone', label: t('ui.users.phone'), value: user?.phone, country: user?.phone_country },
                    { name: 'role', type: 'select', label: t('ui.users.role'), value: user?.role ?? 'viewer', disabled: self, options: [
                        { value: 'administrator', label: roleLabel('administrator') },
                        { value: 'property_manager', label: roleLabel('property_manager') },
                        { value: 'viewer', label: roleLabel('viewer') },
                    ] },
                    { name: 'is_active', type: 'toggle', label: t('ui.users.active_account'), hint: t('ui.users.active_account_help'), value: user ? user.is_active !== false : true, disabled: self },
                ],
                validate: (values) => (values.surname === '' ? { surname: t('ui.users.surname') } : null),
                onSubmit: async (values) => {
                    const payload = {
                        given_names: values.given_names || null,
                        surname: values.surname,
                        phone: values.phone.number || null,
                        phone_country: values.phone.country,
                    };

                    if (! editing) {
                        payload.email = values.email;
                    }

                    if (! self) {
                        payload.role = values.role;
                        payload.is_active = values.is_active;
                    }

                    if (editing) {
                        await client.patch(endpoints.user(user.id), payload);
                    } else {
                        await client.post(endpoints.users, payload);
                    }
                },
            });

            if (saved) {
                showSuccess(errors, editing ? t('ui.users.updated') : t('ui.users.created'));
                reload(1);
            }
        }

        function userRow(user) {
            const pending = ! user.email_verified_at;
            const self = user.id === myId;

            return el('div', { class: 'record-card' }, [
                el('div', { class: 'record-card-head' }, [
                    avatar(user),
                    el('h3', { class: 'record-card-title', text: user.name ?? '' }),
                    self ? badge(t('ui.users.you'), 'info') : null,
                    badge(user.is_active !== false ? t('ui.users.active') : t('ui.users.inactive'), user.is_active !== false ? 'success' : 'neutral'),
                    pending ? badge(t('ui.users.invitation_pending'), 'warning') : null,
                ]),
                el('p', { class: 'record-card-sub', text: user.email ?? '' }),
                el('p', { class: 'muted-small', text: joinParts([roleLabel(user.role), user.phone]) }),
                el('div', { class: 'record-card-actions' }, [
                    button(t('ui.users.edit'), { iconName: 'edit-02', onClick: () => userForm(user) }),
                    pending
                        ? button(t('ui.users.resend_invitation'), { iconName: 'send', onClick: async () => {
                            if (await confirmSheet({ title: t('ui.users.resend_invitation'), body: t('ui.users.resend_confirmation', { name: user.name }), confirmLabel: t('ui.users.resend_invitation') })) {
                                try {
                                    await client.post(`${endpoints.user(user.id)}/resend-invitation`, {});
                                    showSuccess(errors, t('ui.users.invitation_resent'));
                                } catch (failure) {
                                    showError(errors, failure, t('ui.users.action_failed'));
                                }
                            }
                        } })
                        : button(t('ui.users.send_password_reset'), { iconName: 'key-01', onClick: async () => {
                            if (await confirmSheet({ title: t('ui.users.send_password_reset'), body: t('ui.users.reset_confirmation', { name: user.name }), confirmLabel: t('ui.users.send_password_reset') })) {
                                try {
                                    await client.post(`${endpoints.user(user.id)}/password-reset`, {});
                                    showSuccess(errors, t('ui.users.reset_sent'));
                                } catch (failure) {
                                    showError(errors, failure, t('ui.users.action_failed'));
                                }
                            }
                        } }),
                    self ? null : button(t('ui.users.delete'), { kind: 'danger', iconName: 'trash-01', onClick: async () => {
                        if (! await confirmSheet({ title: t('ui.users.delete'), body: t('ui.users.delete_confirmation', { name: user.name }), confirmLabel: t('ui.users.delete'), danger: true })) {
                            return;
                        }

                        if (! await dangerConfirmation(client, { entityLabel: user.name })) {
                            return;
                        }

                        try {
                            await client.delete(endpoints.user(user.id));
                            showSuccess(errors, t('ui.users.deleted'));
                            reload(1);
                        } catch (failure) {
                            showError(errors, failure, t('ui.users.unable_delete'));
                        }
                    } }),
                ]),
            ]);
        }

        async function reload(next = page) {
            page = next;
            mount(list, loading(t('ui.users.loading')));

            try {
                const payload = await client.get(`${endpoints.users}${query({ ...filters, page, per_page: size.get() })}`);
                const found = rows(payload);

                mount(list,
                    found.length === 0
                        ? emptyState('user-01', t('ui.users.none_found'), t('ui.users.none_found_description'))
                        : el('div', {}, found.map(userRow)),
                    pagination(pageMeta(payload, size.get()), size, reload)
                );
            } catch (failure) {
                mount(list);
                showError(errors, failure, t('ui.users.unable_load'));
            }
        }

        mount(wrap,
            el('div', { class: 'screen-head' }, [
                el('div', {}, [
                    el('h2', { class: 'card-title', text: t('ui.users.heading') }),
                    el('p', { class: 'screen-sub', text: t('ui.users.description') }),
                ]),
                el('div', { class: 'screen-actions' }, [button(t('ui.users.add_user'), { kind: 'primary', iconName: 'plus', onClick: () => userForm(null) })]),
            ]),
            controls,
            list
        );

        reload(1);

        return wrap;
    }

    /* ----------------------------------------------------------- licence */

    function licenceTab() {
        const lic = licence ?? {};
        const usage = lic.usage ?? {};
        const limits = lic.limits ?? {};
        const planKey = `ui.license.plan_${lic.plan}`;
        const planName = t(planKey) === planKey ? (lic.plan_label ?? lic.plan ?? '—') : t(planKey);

        function meter(label, used, limit) {
            const percentage = limit ? Math.min(100, Math.round((Number(used ?? 0) / Number(limit)) * 100)) : 0;

            return el('div', { class: 'kpi' }, [
                el('span', { class: 'kpi-label', text: label }),
                el('span', { class: 'kpi-value', text: `${formatNumber(used ?? 0)} / ${limit ? formatNumber(limit) : t('ui.license.unlimited')}` }),
                el('div', { class: 'meter' }, [el('div', { class: 'meter-fill', style: `width:${percentage}%;${percentage >= 90 ? 'background:var(--pm-danger-solid)' : ''}` })]),
            ]);
        }

        return el('div', { class: 'stack' }, [
            el('div', {}, [
                el('h2', { class: 'card-title', text: t('ui.license.heading') }),
                el('p', { class: 'screen-sub', text: t('ui.license.description') }),
            ]),
            section(t('ui.license.current_plan'), [
                el('div', { class: 'inline' }, [
                    el('span', { class: 'kpi-value', text: planName }),
                    lic.on_trial ? badge(`${t('ui.license.trial_until')} ${formatDate(lic.trial_ends_on)}`, 'warning') : null,
                ]),
                el('p', { class: 'muted-small', text: `${t('ui.license.upgrade_hint')} billing@patrimoine365.com` }),
            ]),
            el('div', { class: 'kpis' }, [
                meter(t('ui.license.usage_users'), usage.users, limits.users),
                meter(t('ui.license.usage_active_leases'), usage.active_leases, limits.active_leases),
                meter(t('ui.license.usage_parties'), usage.parties, limits.parties),
                meter(t('ui.license.usage_emails'), usage.emails_this_month, limits.emails_per_month),
            ]),
            el('p', { class: 'muted-small', text: t('ui.license.footnotes') }),
        ]);
    }

    /* ------------------------------------------------------- preferences */

    function preferencesTab() {
        const org = organisation ?? {};
        const languages = presentation?.supported_languages ?? ['en', 'fr'];
        const currencies = presentation?.supported_currencies ?? ['GHS', 'FCFA'];

        return el('div', { class: 'stack' }, [
            section(t('ui.settings.language_currency'), dl([
                [t('ui.settings.language'), t(`ui.language.${org.language ?? presentation?.language ?? 'en'}`)],
                [t('ui.settings.currency'), org.currency ?? presentation?.currency],
            ]), { sub: t('ui.settings.language_currency_description') }),
            section(t('ui.settings.financial_defaults'), dl([
                [t('ui.settings.default_vat_rate'), `${org.default_vat_rate ?? presentation?.default_vat_rate ?? 0}%`],
            ]), { sub: t('ui.settings.financial_defaults_description') }),
            section(t('ui.settings.communications'), dl([
                [t('ui.settings.party_emails_enabled'), org.party_emails_enabled !== false ? t('common.yes') : t('common.no')],
                [t('ui.settings.data_tools_enabled'), org.data_tools_enabled === true ? t('common.yes') : t('common.no')],
                [t('ui.settings.sort_parties_by_surname'), org.sort_parties_by_surname === true ? t('common.yes') : t('common.no')],
            ]), { sub: t('ui.settings.communications_description') }),
            el('div', {}, [button(t('ui.settings.save_preferences'), { kind: 'primary', iconName: 'edit-02', compact: false, onClick: async () => {
                const saved = await openSheet({
                    title: t('ui.settings.tab_preferences'),
                    width: 'lg',
                    submitLabel: t('ui.settings.save_preferences'),
                    fields: [
                        { name: 'h1', type: 'heading', label: t('ui.settings.language_currency'), hint: t('ui.settings.language_currency_description') },
                        { name: 'language', type: 'select', label: t('ui.settings.language'), value: org.language ?? presentation?.language ?? 'en', hint: t('ui.settings.language_help'), options: languages.map((code) => ({ value: code, label: t(`ui.language.${code}`) })) },
                        { name: 'currency', type: 'select', label: t('ui.settings.currency'), value: org.currency ?? presentation?.currency ?? 'GHS', hint: t('ui.settings.currency_help'), options: currencies.map((code) => ({ value: code, label: t(`ui.currency.${code}`) === `ui.currency.${code}` ? code : t(`ui.currency.${code}`) })) },
                        { name: 'h2', type: 'heading', label: t('ui.settings.financial_defaults'), hint: t('ui.settings.financial_defaults_description') },
                        { name: 'default_vat_rate', type: 'number', label: t('ui.settings.default_vat_rate'), value: String(org.default_vat_rate ?? presentation?.default_vat_rate ?? ''), min: 0, max: 100, step: 0.01, suffix: '%', required: true, hint: t('ui.settings.vat_starting_rate') },
                        { name: 'h3', type: 'heading', label: t('ui.settings.communications'), hint: t('ui.settings.communications_description') },
                        { name: 'party_emails_enabled', type: 'toggle', label: t('ui.settings.party_emails_enabled'), hint: t('ui.settings.party_emails_help'), value: org.party_emails_enabled !== false },
                        { name: 'data_tools_enabled', type: 'toggle', label: t('ui.settings.data_tools_enabled'), hint: t('ui.settings.data_tools_help'), value: org.data_tools_enabled === true },
                        { name: 'sort_parties_by_surname', type: 'toggle', label: t('ui.settings.sort_parties_by_surname'), hint: t('ui.settings.sort_parties_by_surname_help'), value: org.sort_parties_by_surname === true },
                    ],
                    onSubmit: saveOrganisation,
                });

                if (saved) {
                    showSuccess(errors, t('ui.settings.saved'));
                    paint();
                }
            } })]),
        ]);
    }

    /* ----------------------------------------------------------- devices */

    function devicesTab() {
        const wrap = el('div', { class: 'stack' });
        const list = el('div');

        async function reload() {
            mount(list, loading(t('ui.devices.loading')));

            try {
                const found = rows(await client.get(endpoints.auth.devices));

                mount(list, found.length === 0
                    ? emptyState('smartphone', t('ui.devices.empty'))
                    : el('div', {}, found.map((device) => el('div', { class: 'record-card' }, [
                        el('div', { class: 'record-card-head' }, [
                            el('h3', { class: 'record-card-title', text: device.name || t('ui.devices.unnamed') }),
                            device.is_current ? badge(t('ui.devices.this_device'), 'success') : null,
                        ]),
                        el('p', { class: 'muted-small', text: joinParts([
                            t(`ui.devices.client_${device.client ?? 'web'}`),
                            device.app_version,
                            device.last_used_at ? `${t('ui.devices.last_used')} ${formatDateTime(device.last_used_at)}` : t('ui.devices.never_used'),
                            device.created_at ? `${t('ui.devices.signed_in')} ${formatDateTime(device.created_at)}` : null,
                            device.last_used_ip,
                        ]) }),
                        el('div', { class: 'record-card-actions' }, [
                            button(t('ui.devices.sign_out'), { kind: 'danger', iconName: 'log-out-01', onClick: async () => {
                                try {
                                    const result = await client.delete(endpoints.auth.device(device.id));

                                    if (result?.signed_out === true || device.is_current) {
                                        onSignedOut?.();

                                        return;
                                    }

                                    reload();
                                } catch (failure) {
                                    showError(errors, failure);
                                }
                            } }),
                        ]),
                    ])))
                );
            } catch (failure) {
                mount(list);
                showError(errors, failure);
            }
        }

        mount(wrap,
            el('div', { class: 'screen-head' }, [
                el('div', {}, [
                    el('h2', { class: 'card-title', text: t('ui.devices.heading') }),
                    el('p', { class: 'screen-sub', text: t('ui.devices.description') }),
                ]),
                el('div', { class: 'screen-actions' }, [button(t('ui.devices.sign_out_others'), { iconName: 'log-out-01', onClick: async () => {
                    try {
                        const result = await client.delete(endpoints.auth.devices);

                        showSuccess(errors, result?.message ?? t('ui.devices.sign_out_others'));
                        reload();
                    } catch (failure) {
                        showError(errors, failure);
                    }
                } })]),
            ]),
            list,
            el('p', { class: 'muted-small', text: t('ui.devices.expiry_note') })
        );

        reload();

        return wrap;
    }

    /* -------------------------------------------------------------- data */

    function dataTab() {
        const entities = ['parties', 'buildings', 'units', 'leases'];
        const fail = (failure) => showError(errors, failure, t('ui.settings.unable_export'));

        const exportRows = entities.map((entity) => el('div', { class: 'inline' }, [
            el('span', { class: 'grow cell-strong', text: t(`ui.settings.entity_${entity}`) }),
            pdfButton(client, `/registry/export/pdf?entity=${entity}`, t('ui.settings.format_pdf'), { onFail: fail }),
            fileButton(client, `/registry/export?entity=${entity}&format=xlsx`, t('ui.settings.format_xlsx'), `registry-${entity}.xlsx`, { onFail: fail }),
            fileButton(client, `/registry/export?entity=${entity}&format=csv`, t('ui.settings.format_csv'), `registry-${entity}.csv`, { onFail: fail }),
        ]));

        /* Import / restore: a file, a data set, a mandatory dry run, then apply. */
        const fileInput = el('input', { type: 'file', accept: '.csv,.xlsx', class: 'hidden-input' });
        const fileName = el('span', { class: 'muted-small', text: t('ui.settings.no_file_selected') });
        const entitySelect = selectControl([
            ...entities.map((entity) => ({ value: entity, label: t(`ui.settings.entity_${entity}`) })),
            { value: 'full', label: t('ui.settings.entity_full') },
        ], 'parties', () => invalidate());
        const result = el('div', { class: 'stack' });
        const applyRow = el('div', { hidden: true });
        let pending = null;

        function invalidate() {
            pending = null;
            applyRow.hidden = true;
            mount(result);
        }

        fileInput.addEventListener('change', () => {
            fileName.textContent = fileInput.files?.[0]?.name ?? t('ui.settings.no_file_selected');
            invalidate();
        });

        function counts(block) {
            return el('div', { class: 'pair-grid pair-grid-4' }, [
                ['import_created', block.created], ['import_updated', block.updated], ['import_unchanged', block.unchanged], ['import_skipped', Array.isArray(block.skipped) ? block.skipped.length : block.skipped],
            ].map(([key, value]) => el('div', { class: 'pair' }, [
                el('span', { class: 'pair-label', text: t(`ui.settings.${key}`) }),
                el('span', { class: 'pair-value', text: formatNumber(value ?? 0) }),
            ])));
        }

        function skipped(block) {
            const list = Array.isArray(block.skipped) ? block.skipped : [];

            return list.length === 0 ? null : el('ul', { class: 'stack' }, list.map((item) => el('li', { class: 'muted-small is-negative', text: t('ui.settings.import_skipped_row', { row: item.row ?? '', reason: item.reason ?? String(item) }) })));
        }

        function renderResult(payload, dryRun) {
            const blocks = payload?.entity ? [payload] : Object.entries(payload ?? {}).filter(([, v]) => v && typeof v === 'object' && 'created' in v).map(([entity, v]) => ({ entity, ...v }));

            mount(result,
                el('h3', { class: 'sheet-heading-title', text: t('ui.settings.import_result_heading') }),
                dryRun ? el('p', { class: 'sheet-note is-info', text: t('ui.settings.import_dry_run_notice') }) : null,
                ...blocks.map((block) => el('div', { class: 'stack' }, [
                    blocks.length > 1 ? el('span', { class: 'cell-strong', text: t(`ui.settings.entity_${block.entity}`) }) : null,
                    counts(block),
                    skipped(block),
                ]))
            );
        }

        async function run(dryRun) {
            const file = fileInput.files?.[0];
            const entity = entitySelect.value;

            if (! file) {
                showError(errors, null, t('ui.settings.import_select_file'));

                return;
            }

            if (entity === 'full' && ! /\.xlsx$/i.test(file.name)) {
                showError(errors, null, t('ui.settings.full_requires_xlsx'));

                return;
            }

            const form = new FormData();

            form.append('file', file);
            form.append('dry_run', dryRun ? '1' : '0');

            if (entity !== 'full') {
                form.append('entity', entity);
            }

            const payload = await client.upload(entity === 'full' ? '/registry/import/full' : '/registry/import', form);

            renderResult(payload, dryRun);

            if (dryRun) {
                pending = { file, entity, payload };
                applyRow.hidden = false;
            } else {
                pending = null;
                applyRow.hidden = true;
                showSuccess(errors, t('ui.settings.restore_success'));
            }
        }

        const dryRunButton = button(t('ui.settings.run_dry_run'), { kind: 'primary', onClick: async (node) => {
            node.querySelector('span').textContent = t('ui.settings.dry_run_running');

            try {
                await run(true);
            } catch (failure) {
                showError(errors, failure, t('ui.settings.unable_import'));
            } finally {
                node.querySelector('span').textContent = t('ui.settings.run_dry_run');
            }
        } });

        mount(applyRow, button(t('ui.settings.apply_restore'), { kind: 'danger', onClick: async () => {
            if (pending === null || pending.file !== fileInput.files?.[0] || pending.entity !== entitySelect.value) {
                invalidate();

                return;
            }

            const block = pending.payload?.entity ? pending.payload : null;
            const summary = block ? `${t('ui.settings.import_created')} ${block.created ?? 0} · ${t('ui.settings.import_updated')} ${block.updated ?? 0} · ${t('ui.settings.import_unchanged')} ${block.unchanged ?? 0} · ${t('ui.settings.import_skipped')} ${Array.isArray(block.skipped) ? block.skipped.length : block.skipped ?? 0}` : '';

            const confirmed = await openSheet({
                title: t('ui.settings.confirm_restore_title'),
                description: t('ui.settings.confirm_restore_description'),
                submitLabel: t('ui.settings.confirm_restore_apply'),
                submitKind: 'danger',
                fields: [
                    { name: 'file', type: 'readonly', label: t('ui.settings.import_file'), value: pending.file.name },
                    { name: 'entity', type: 'readonly', label: t('ui.settings.import_entity'), value: pending.entity === 'full' ? t('ui.settings.entity_full') : t(`ui.settings.entity_${pending.entity}`) },
                    summary ? { name: 'summary', type: 'note', label: summary } : { name: 'summary', type: 'note', label: '' },
                    { name: 'warning', type: 'note', tone: 'danger', label: t('ui.settings.confirm_restore_warning') },
                ],
                onSubmit: async () => {
                    await run(false);

                    return true;
                },
            });

            if (! confirmed) {
                return;
            }
        } }));

        return el('div', { class: 'stack' }, [
            section(t('ui.settings.backup_restore'), [
                el('p', { class: 'sheet-note is-warning', text: t('ui.settings.backup_financial_note') }),
                el('h3', { class: 'sheet-heading-title', text: t('ui.settings.export_heading') }),
                ...exportRows,
                el('div', { class: 'inline' }, [
                    fileButton(client, '/registry/export/full', t('ui.settings.export_full'), 'registry-full.xlsx', { onFail: fail, kind: 'primary' }),
                ]),
            ], { sub: t('ui.settings.backup_restore_description') }),
            section(t('ui.settings.everything_title'), [
                el('p', { class: 'record-card-sub', text: t('ui.settings.everything_description') }),
                fileButton(client, '/organisation/data', t('ui.settings.everything_action'), 'patrimoine-organisation.json', { onFail: fail, kind: 'primary' }),
            ]),
            section(t('ui.settings.import_heading'), [
                el('p', { class: 'muted-small', text: t('ui.settings.dry_run_help') }),
                el('div', { class: 'field' }, [
                    el('span', { class: 'label', text: t('ui.settings.import_file') }),
                    el('div', { class: 'inline' }, [
                        el('label', { class: 'button button-secondary button-compact' }, [icon('upload-01', { size: 16 }), el('span', { text: t('ui.settings.choose_file') }), fileInput]),
                        fileName,
                    ]),
                ]),
                el('div', { class: 'field' }, [el('span', { class: 'label', text: t('ui.settings.import_entity') }), entitySelect]),
                el('div', { class: 'inline' }, [dryRunButton, applyRow]),
                result,
            ]),
        ]);
    }

    /* ------------------------------------------------------------- about */

    function aboutTab() {
        return section(t('ui.settings.about'), [
            dl([[t('ui.settings.application_version'), presentation?.release ? `v${presentation.release}` : (config?.release ? `v${config.release}` : '—')]]),
            el('p', { class: 'muted-small', text: `${t('app.name')} ${version ? `v${version}` : ''}`.trim() }),
        ]);
    }

    /* ------------------------------------------------------------ screen */

    const available = TABS.filter((id) => (id === 'users' ? can('manage_users') : true));

    function paint() {
        const render = { organisation: organisationTab, users: usersTab, license: licenceTab, preferences: preferencesTab, devices: devicesTab, data: dataTab, about: aboutTab }[active];

        mount(body,
            tabs(available.map((id) => ({ id, label: id === 'users' ? t('ui.navigation.users') : id === 'license' ? t('ui.navigation.license') : t(`ui.settings.tab_${id}`) === `ui.settings.tab_${id}` ? t('ui.settings.about') : t(`ui.settings.tab_${id}`) })), active, (id) => { active = id; paint(); }),
            render()
        );
    }

    async function load() {
        mount(body, loading());

        try {
            await loadOrganisation();
            paint();
        } catch (failure) {
            mount(body);
            showError(errors, failure, t('ui.settings.unable_to_load'));
        }
    }

    mount(host,
        screenHead({ eyebrow: t('ui.settings.administration'), title: t('ui.settings.heading'), sub: t('ui.settings.description') }),
        errors,
        body
    );

    load();

    return { node: host, reload: load, show(tab) { if (TABS.includes(tab)) { active = tab; paint(); } } };
}
