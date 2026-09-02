/*
 * Parties - owners, tenants, agents, organisations and associations.
 *
 * Four figures, the search and its three filters, one row per party with
 * its type and role badges, and every action the browser offers: Edit,
 * Data, Erase, Delete or Archive. Data and Erase appear only when the
 * organisation has switched the data-protection tools on in Settings.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { openSheet } from '../ui/sheet.js';
import { dangerConfirmation, archiveRecord } from '../ui/confirm.js';
import { pagination, pageSize, loading, emptyState, badge, button, stat } from '../ui/table.js';
import { formatNumber, joinParts } from '../ui/format.js';
import * as store from '../data/store.js';
import { partySheet } from './party-form.js';
import {
    screenHead, bannerHost, showError, showSuccess, rows, totalOf, pageMeta, filterField, selectControl,
    query, partyName, partyContact, partyTypeChip, partyRoleChip, fileButton,
} from './common.js';
import { searchField } from '../ui/search.js';

export function partiesScreen(client, { role: fixedRole = null, title, eyebrow, sub, icon: iconName = 'users-03' } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const kpis = el('div', { class: 'kpis' });
    const list = el('div');
    const size = pageSize(fixedRole ? `parties-${fixedRole}` : 'parties');
    const filters = { search: '', type: '', role: fixedRole ?? '', email: '' };
    let page = 1;
    let payload = null;
    let presentation = null;

    const searchBox = searchField((value) => { filters.search = value; reload(1); });

    searchBox.input.placeholder = t('ui.parties.search_placeholder');

    function dataTools() {
        const organisation = store.read('organisation').data;

        return organisation?.data_tools_enabled === true || presentation?.data_tools_enabled === true;
    }

    function partyEmailsEnabled() {
        const organisation = store.read('organisation').data;

        return organisation?.party_emails_enabled !== false && presentation?.party_emails_enabled !== false;
    }

    /* ------------------------------------------------------- actions */

    async function edit(party) {
        let fresh = party;

        try {
            fresh = await client.get(endpoints.party(party.id));
        } catch (failure) {
            showError(errors, failure, t('ui.parties.unable_to_load_party'));

            return;
        }

        if (await partySheet(client, fresh)) {
            await store.refreshAll(client);
            reload(1);
        }
    }

    async function remove(party) {
        const confirmed = await openSheet({
            title: t('ui.parties.delete_party'),
            description: t('ui.parties.delete_party_description'),
            submitLabel: t('ui.parties.delete_party'),
            submitKind: 'danger',
            fields: [
                { name: 'who', type: 'readonly', label: t('ui.parties.delete_party_prompt'), value: partyName(party) },
                { name: 'note', type: 'note', label: t('ui.parties.delete_restriction') },
            ],
            onSubmit: async () => true,
        });

        if (! confirmed || ! await dangerConfirmation(client, { entityLabel: partyName(party) })) {
            return;
        }

        try {
            await client.delete(endpoints.party(party.id));
            await store.refreshAll(client);
            reload(1);
        } catch (failure) {
            showError(errors, failure, t('ui.parties.unable_to_delete_party'));
        }
    }

    async function erase(party) {
        const name = partyName(party);

        const done = await openSheet({
            title: t('ui.parties.erase_title'),
            description: t('ui.parties.erase_description'),
            submitLabel: t('ui.parties.erase_confirm'),
            submitKind: 'danger',
            fields: [
                { name: 'warning', type: 'note', tone: 'danger', label: t('ui.parties.erase_warning') },
                { name: 'kept', type: 'note', label: t('ui.parties.erase_kept') },
                { name: 'name_confirmation', type: 'text', label: t('ui.parties.erase_name_label'), hint: t('ui.parties.erase_name_hint', { name }), required: true },
                { name: 'password', type: 'password', label: t('ui.parties.erase_password_label'), autocomplete: 'current-password', required: true },
            ],
            onSubmit: async (values) => {
                await client.post(`${endpoints.party(party.id)}/erase`, { name_confirmation: values.name_confirmation, password: values.password });
            },
        });

        if (done) {
            await store.refreshAll(client);
            reload(1);
        }
    }

    async function archive(party) {
        try {
            if (await archiveRecord(client, { kind: 'party', id: party.id, label: partyName(party) })) {
                await store.refreshAll(client);
                reload(1);
            }
        } catch (failure) {
            showError(errors, failure, t('ui.archive.archive_failed'));
        }
    }

    /* ---------------------------------------------------------- rows */

    function hasEmail(party) {
        return Boolean(party.email || party.contact_person_email);
    }

    function silenced(party) {
        return party.email_policy === 'never' || (party.email_policy === 'inherit' && ! partyEmailsEnabled());
    }

    function roleList(party) {
        const roles = Array.isArray(party.roles) ? party.roles.map((r) => (typeof r === 'string' ? r : r?.role ?? r?.name)) : [];

        return roles.filter(Boolean);
    }

    function row(party) {
        const roles = roleList(party);

        return el('div', { class: 'record-card' }, [
            el('div', { class: 'record-card-head' }, [
                el('h3', { class: 'record-card-title', text: partyName(party) || `${t('ui.parties.heading')} #${party.id}` }),
                partyTypeChip(party.type),
                ...(roles.length === 0 ? [badge(t('ui.parties.no_assigned_role'), 'neutral')] : roles.map(partyRoleChip)),
                silenced(party) ? badge(t('ui.parties.emails_off'), 'warning') : null,
                party.erased_at ? badge(t('ui.parties.erase_title'), 'danger') : null,
            ]),
            el('p', { class: 'record-card-sub', text: joinParts([partyContact(party), party.contact_person_email, party.address]) }),
            el('div', { class: 'record-card-actions' }, [
                can('manage_operations') ? button(t('ui.parties.edit'), { iconName: 'edit-02', onClick: () => edit(party) }) : null,
                dataTools() && can('manage_settings') ? fileButton(client, `${endpoints.party(party.id)}/data`, t('ui.parties.export_data'), `patrimoine-party-${party.id}.json`, { busyLabel: t('ui.parties.exporting'), onFail: (f) => showError(errors, f) }) : null,
                dataTools() && can('manage_settings') && ! party.erased_at ? button(t('ui.parties.erase'), { kind: 'danger-outline', iconName: 'eye-off', onClick: () => erase(party) }) : null,
                party.is_deletable === false
                    ? (can('delete_records') ? button(t('ui.archive.archive'), { kind: 'danger-outline', iconName: 'archive', onClick: () => archive(party) }) : null)
                    : (can('delete_records') ? button(t('ui.parties.delete_party'), { kind: 'danger-outline', iconName: 'trash-01', onClick: () => remove(party) }) : null),
            ].filter(Boolean)),
        ]);
    }

    function paint() {
        let found = rows(payload);

        if (filters.email === 'yes') {
            found = found.filter(hasEmail);
        } else if (filters.email === 'no') {
            found = found.filter((party) => ! hasEmail(party));
        }

        const organisation = store.read('organisation').data;

        if (organisation?.sort_parties_by_surname === true || presentation?.sort_parties_by_surname === true) {
            found = [...found].sort((a, b) => String(a.surname || a.name || a.legal_name || '').localeCompare(String(b.surname || b.name || b.legal_name || ''), undefined, { sensitivity: 'base' }));
        }

        const all = rows(payload);

        mount(kpis,
            stat(t('ui.parties.total_parties'), formatNumber(totalOf(payload))),
            stat(t('ui.parties.people'), formatNumber(all.filter((p) => p.type === 'person').length)),
            stat(t('ui.parties.organisations'), formatNumber(all.filter((p) => p.type === 'organisation' || p.type === 'association').length)),
            stat(t('ui.parties.multiple_roles'), formatNumber(all.filter((p) => roleList(p).length > 1).length))
        );

        mount(list,
            found.length === 0
                ? emptyState(iconName, t('ui.parties.no_parties_found'), t('ui.parties.empty_description'))
                : el('div', {}, found.map(row)),
            pagination(pageMeta(payload, size.get()), size, reload)
        );
    }

    async function reload(next = page) {
        page = next;
        mount(list, loading(t('ui.parties.loading')));

        try {
            payload = await client.get(`${endpoints.parties}${query({ page, per_page: size.get(), search: filters.search, type: filters.type, role: filters.role })}`);
            paint();
        } catch (failure) {
            mount(list);
            showError(errors, failure, t('ui.parties.unable_to_load'));
        }
    }

    mount(host,
        screenHead({
            eyebrow: eyebrow ?? t('ui.parties.contacts_stakeholders'),
            title: title ?? t('ui.parties.heading'),
            sub: sub ?? t('ui.parties.page_description'),
            actions: [can('manage_operations') ? button(t('ui.parties.add_party'), { kind: 'primary', iconName: 'plus', compact: false, onClick: async () => {
                if (await partySheet(client, null, fixedRole ? { forceRoles: [fixedRole] } : {})) {
                    await store.refreshAll(client);
                    reload(1);
                }
            } }) : null],
        }),
        errors,
        kpis,
        el('div', { class: 'filters' }, [
            filterField(t('ui.parties.directory'), searchBox.node, { span: true }),
            filterField(t('ui.parties.party_type'), selectControl([
                { value: '', label: t('ui.parties.all_types') },
                { value: 'person', label: t('ui.parties.people') },
                { value: 'organisation', label: t('ui.parties.organisations') },
                { value: 'association', label: t('ui.parties.associations') },
            ], '', (v) => { filters.type = v; reload(1); })),
            fixedRole ? null : filterField(t('ui.parties.roles'), selectControl([
                { value: '', label: t('ui.parties.all_roles') },
                { value: 'owner', label: t('ui.parties.owners') },
                { value: 'tenant', label: t('ui.parties.tenants') },
                { value: 'agent', label: t('ui.parties.agents') },
                { value: 'managing_organisation', label: t('ui.parties.managing_organisation') },
            ], '', (v) => { filters.role = v; reload(1); })),
            filterField(t('ui.parties.email'), selectControl([
                { value: '', label: t('ui.parties.has_email_all') },
                { value: 'yes', label: t('ui.parties.has_email_yes') },
                { value: 'no', label: t('ui.parties.has_email_no') },
            ], '', (v) => { filters.email = v; paint(); })),
        ]),
        list
    );

    client.get('/presentation-config').then((config) => { presentation = config; if (payload) { paint(); } }).catch(() => {});
    reload(1);

    return { node: host, reload: () => reload(page) };
}
