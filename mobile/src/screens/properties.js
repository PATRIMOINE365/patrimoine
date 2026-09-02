/*
 * Properties - buildings, their ownership and their units, as the browser
 * application's page has them: four figures, a search, a classification
 * filter, one card per building with its units table, and the drawers
 * that create, edit, delete and archive each of them.
 */

import { el, mount } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { can } from '../auth/capabilities.js';
import { openSheet } from '../ui/sheet.js';
import { dangerConfirmation, archiveRecord } from '../ui/confirm.js';
import { table, pagination, pageSize, loading, emptyState, badge, button, stat } from '../ui/table.js';
import { formatNumber, joinParts } from '../ui/format.js';
import * as store from '../data/store.js';
import { partySheet } from './party-form.js';
import {
    screenHead, bannerHost, showError, showSuccess, rows, totalOf, pageMeta, filterField, selectControl,
    textControl, query, partyName, partyContact, dash,
} from './common.js';
import { searchField } from '../ui/search.js';

async function ownerOptions(client, term = '') {
    const found = rows(await client.get(`${endpoints.parties}?role=owner&per_page=${term ? 15 : 100}${term ? `&search=${encodeURIComponent(term)}` : ''}`));

    return found.map((party) => ({ value: String(party.id), label: partyName(party), sub: partyContact(party), keywords: partyContact(party) }));
}

/**
 * The ownership rows shared by Add and Edit: an owner picker and a share,
 * totalling 100. `party_id` is the value the API wants back.
 */
function ownersField(client, owners, building) {
    return {
        name: 'owners', type: 'lines', label: t('ui.properties.ownership'), hint: t('ui.properties.ownership_description'),
        min: 1, minMessage: t('ui.properties.validation_owner_required'), addLabel: t('ui.properties.add_owner'), removeLabel: t('ui.properties.remove'),
        value: (building?.ownerships ?? []).map((o) => ({ party_id: String(o.party_id ?? o.party?.id ?? ''), ownership_percentage: String(o.ownership_percentage ?? '') })),
        columns: [
            { name: 'party_id', type: 'picker', label: t('ui.properties.owner'), placeholder: t('ui.properties.search_owner_placeholder'), empty: t('ui.properties.no_matching_owners'), options: owners, search: (term) => ownerOptions(client, term), required: true },
            { name: 'ownership_percentage', type: 'number', label: t('ui.properties.ownership_percentage'), min: 0.01, max: 100, step: 0.01, suffix: '%', value: '100', required: true },
        ],
        total: (values) => `${t('ui.properties.total')}: ${formatNumber(values.reduce((sum, row) => sum + (Number(row.ownership_percentage) || 0), 0))}%`,
    };
}

function validateOwners(values) {
    const owners = values.owners ?? [];

    if (owners.length === 0) {
        return { owners: t('ui.properties.validation_owner_required') };
    }

    if (owners.some((row) => ! row.party_id)) {
        return { owners: t('ui.properties.validation_select_every_owner') };
    }

    if (new Set(owners.map((row) => row.party_id)).size !== owners.length) {
        return { owners: t('ui.properties.validation_duplicate_owner') };
    }

    if (owners.some((row) => ! (Number(row.ownership_percentage) > 0 && Number(row.ownership_percentage) <= 100))) {
        return { owners: t('ui.properties.validation_owner_percentage') };
    }

    const total = owners.reduce((sum, row) => sum + Number(row.ownership_percentage), 0);

    if (Math.abs(total - 100) > 0.001) {
        return { owners: t('ui.properties.validation_ownership_total') };
    }

    return null;
}

export function propertiesScreen(client, { onOpenLease } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const kpis = el('div', { class: 'kpis' });
    const list = el('div');
    const size = pageSize('properties');
    let page = 1;
    let search = '';
    let classification = 'all';
    let payload = null;
    const expanded = new Set();

    const searchBox = searchField((value) => { search = value; reload(1); });

    searchBox.input.placeholder = t('ui.properties.search_placeholder');

    const classificationSelect = selectControl([
        { value: 'all', label: t('ui.properties.filter_all_units') },
        { value: 'commercial', label: t('ui.properties.commercial') },
        { value: 'residential', label: t('ui.properties.residential') },
    ], 'all', (value) => { classification = value; paint(); });

    /* ------------------------------------------------------- drawers */

    async function newOwnerInline() {
        const created = await partySheet(client, null, { forceRoles: ['owner'], title: t('ui.properties.create_owner'), description: t('ui.properties.create_owner_description') });

        return created ? (created.data ?? created) : null;
    }

    async function propertyForm(building = null) {
        const editing = building !== null;
        let owners = [];

        try {
            owners = await ownerOptions(client);
        } catch (failure) {
            showError(errors, failure);
        }

        const saved = await openSheet({
            title: editing ? t('ui.properties.edit_property') : t('ui.properties.add_property'),
            description: editing ? t('ui.properties.edit_property_description') : t('ui.properties.add_property_description'),
            width: 'lg',
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'h1', type: 'heading', label: t('ui.properties.property_details'), hint: t('ui.properties.property_details_description') },
                { name: 'name', type: 'text', label: t('ui.properties.property_name'), value: building?.name ?? '', placeholder: t('ui.properties.property_name_placeholder'), required: true, maxlength: 255 },
                { name: 'location', type: 'text', label: t('ui.properties.location'), value: building?.location ?? '', placeholder: t('ui.properties.location_placeholder'), maxlength: 255 },
                { name: 'address', type: 'text', label: t('ui.properties.address'), value: building?.address ?? '', placeholder: t('ui.properties.address_placeholder') },
                { name: 'description', type: 'textarea', rows: 3, label: t('ui.properties.description'), value: building?.description ?? '', placeholder: t('ui.properties.optional_property_description') },
                { name: 'h2', type: 'heading', label: t('ui.properties.ownership') },
                { name: 'new_owner', type: 'note', label: '' },
                ownersField(client, owners, building),
                ...(editing ? [] : [
                    { name: 'h3', type: 'heading', label: t('ui.properties.units'), hint: t('ui.properties.units_description') },
                    {
                        name: 'units', type: 'lines', min: 1, minMessage: t('ui.properties.validation_unit_required'), addLabel: t('ui.properties.add_unit'), removeLabel: t('ui.properties.remove'),
                        value: [{ name: '', description: '' }],
                        columns: [
                            { name: 'name', type: 'text', label: t('ui.properties.unit_name_number'), placeholder: t('ui.properties.unit_name_placeholder'), required: true, maxlength: 255 },
                            { name: 'description', type: 'text', label: t('ui.properties.description'), placeholder: t('ui.properties.optional_description') },
                        ],
                    },
                ]),
            ],
            onChange: (values, api, changed) => {
                if (changed === null) {
                    /* "+ New" beside the owners: create one and choose it in the first empty row. */
                    const note = api.get('new_owner').node;

                    mount(note, button(t('ui.properties.new'), { iconName: 'plus', title: t('ui.properties.create_new_owner'), onClick: async () => {
                        const party = await newOwnerInline();

                        if (! party) {
                            return;
                        }

                        const option = { value: String(party.id), label: partyName(party), sub: partyContact(party) };
                        const lines = api.get('owners');
                        const target = lines.rows().find((row) => row.fields[0].read() === '') ?? lines.rows()[0];

                        for (const row of lines.rows()) {
                            row.fields[0].setOptions([option, ...(row.fields[0].chosenOption() ? [row.fields[0].chosenOption()] : [])]);
                        }

                        target?.fields[0].write(option.value);
                    } }));
                }
            },
            validate: (values) => {
                if (values.name === '') {
                    return { name: t('ui.properties.property_name') };
                }

                const ownersProblem = validateOwners(values);

                if (ownersProblem) {
                    return ownersProblem;
                }

                if (! editing) {
                    const units = values.units ?? [];

                    if (units.length === 0) {
                        return { units: t('ui.properties.validation_unit_required') };
                    }

                    if (units.some((row) => row.name === '')) {
                        return { units: t('ui.properties.validation_every_unit_name') };
                    }

                    if (new Set(units.map((row) => row.name.toLowerCase())).size !== units.length) {
                        return { units: t('ui.properties.validation_unique_unit_names') };
                    }
                }

                return null;
            },
            onSubmit: async (values) => {
                const body = {
                    name: values.name,
                    location: values.location || null,
                    address: values.address || null,
                    description: values.description || null,
                    owners: values.owners.map((row) => ({ party_id: Number(row.party_id), ownership_percentage: Number(row.ownership_percentage) })),
                };

                if (editing) {
                    await client.patch(endpoints.building(building.id), body);

                    return;
                }

                const created = await client.post(endpoints.buildings, body);
                const id = created?.id ?? created?.data?.id;

                /* One POST per unit, in order, as the browser does it. */
                for (const unit of values.units) {
                    await client.post(endpoints.units, { building_id: id, name: unit.name, description: unit.description || null });
                }
            },
        });

        if (saved) {
            showSuccess(errors, editing ? t('ui.properties.property_updated') : t('ui.properties.property_created'));
            await store.refreshAll(client);
            reload(1);
        }
    }

    async function unitForm(building, unit = null) {
        const editing = unit !== null;

        const saved = await openSheet({
            title: editing ? t('ui.properties.edit_unit') : t('ui.properties.add_unit'),
            description: editing ? t('ui.properties.edit_unit_description') : t('ui.properties.add_unit_description'),
            submitLabel: t('ui.actions.save'),
            fields: [
                { name: 'property', type: 'readonly', label: t('ui.properties.property'), value: building.name },
                { name: 'name', type: 'text', label: t('ui.properties.unit_name_number'), value: unit?.name ?? '', placeholder: t('ui.properties.existing_unit_name_placeholder'), required: true, maxlength: 255 },
                { name: 'description', type: 'textarea', rows: 3, label: t('ui.properties.description'), value: unit?.description ?? '', placeholder: t('ui.properties.optional_unit_description') },
                { name: 'is_commercial', type: 'toggle', label: t('ui.properties.commercial_unit'), hint: t('ui.properties.commercial_unit_help'), value: unit?.is_commercial === true },
            ],
            validate: (values) => (values.name === '' ? { name: t('ui.properties.validation_unit_name_required') } : null),
            onSubmit: async (values) => {
                const body = { building_id: building.id, name: values.name, description: values.description || null, is_commercial: values.is_commercial };

                if (editing) {
                    await client.patch(endpoints.unit(unit.id), body);
                } else {
                    await client.post(endpoints.units, body);
                }
            },
        });

        if (saved) {
            showSuccess(errors, editing ? t('ui.properties.unit_updated') : t('ui.properties.unit_added'));
            expanded.add(building.id);
            await store.refreshAll(client);
            reload(page);
        }
    }

    async function deleteBuilding(building) {
        const confirmed = await openSheet({
            title: t('ui.properties.delete_property'),
            description: t('ui.properties.delete_property_description'),
            submitLabel: t('ui.properties.delete_property'),
            submitKind: 'danger',
            submitDisabled: true,
            fields: [
                { name: 'property', type: 'readonly', label: t('ui.properties.property'), value: building.name },
                { name: 'warning', type: 'note', tone: 'danger', label: t('ui.properties.delete_property_warning') },
                { name: 'typed', type: 'text', label: t('ui.properties.type_name_to_confirm'), required: true },
            ],
            onChange: (values, api) => api.setSubmitDisabled(values.typed !== building.name),
            onSubmit: async () => true,
        });

        if (! confirmed || ! await dangerConfirmation(client, { entityLabel: building.name })) {
            return;
        }

        try {
            await client.delete(endpoints.building(building.id));
            showSuccess(errors, t('ui.properties.property_deleted'));
            await store.refreshAll(client);
            reload(1);
        } catch (failure) {
            showError(errors, failure, t('ui.properties.unable_to_delete_property'));
        }
    }

    async function deleteUnit(building, unit) {
        const confirmed = await openSheet({
            title: t('ui.properties.delete_unit'),
            description: t('ui.properties.delete_unit_description'),
            submitLabel: t('ui.properties.delete_unit'),
            submitKind: 'danger',
            fields: [
                { name: 'unit', type: 'readonly', label: t('ui.properties.unit'), value: unit.name },
                { name: 'property', type: 'readonly', label: t('ui.properties.property'), value: building.name },
                { name: 'warning', type: 'note', tone: 'danger', label: t('ui.properties.delete_unit_warning') },
            ],
            onSubmit: async () => true,
        });

        if (! confirmed || ! await dangerConfirmation(client, { entityLabel: `${unit.name} · ${building.name}` })) {
            return;
        }

        try {
            await client.delete(endpoints.unit(unit.id));
            showSuccess(errors, t('ui.properties.unit_deleted'));
            expanded.add(building.id);
            await store.refreshAll(client);
            reload(page);
        } catch (failure) {
            showError(errors, failure, t('ui.properties.unable_to_delete_unit'));
        }
    }

    /* Delete becomes Archive once the API says the record cannot go. */
    function removalButton(record, kind, label, onDelete) {
        if (record.is_deletable === false) {
            return can('delete_records') ? button(t('ui.archive.archive'), { kind: 'danger-outline', iconName: 'archive', onClick: async () => {
                try {
                    if (await archiveRecord(client, { kind, id: record.id, label })) {
                        await store.refreshAll(client);
                        reload(1);
                    }
                } catch (failure) {
                    showError(errors, failure, t('ui.archive.archive_failed'));
                }
            } }) : null;
        }

        return can('delete_records') ? button(t('ui.properties.delete'), { kind: 'danger-outline', iconName: 'trash-01', onClick: onDelete }) : null;
    }

    /* --------------------------------------------------------- cards */

    function unitMatches(unit) {
        if (classification === 'commercial') {
            return unit.is_commercial === true;
        }

        if (classification === 'residential') {
            return unit.is_commercial !== true;
        }

        return true;
    }

    function unitsTable(building) {
        const units = (building.units ?? []).filter(unitMatches);

        if ((building.units ?? []).length === 0) {
            return el('p', { class: 'table-empty', text: t('ui.properties.no_units') });
        }

        if (units.length === 0) {
            return el('p', { class: 'table-empty', text: t('ui.properties.no_units_match_filter') });
        }

        const lowered = search.toLowerCase();

        return table([
            { label: t('ui.properties.unit'), cell: (unit) => el('span', { class: 'cell-strong', text: unit.name || t('ui.properties.unnamed_unit') }) },
            { label: t('ui.properties.classification'), cell: (unit) => el('span', { class: 'inline' }, [
                badge(unit.is_commercial ? t('ui.properties.commercial') : t('ui.properties.residential'), unit.is_commercial ? 'info' : 'neutral'),
                badge(unit.is_occupied ? t('ui.properties.occupied') : t('ui.properties.vacant'), unit.is_occupied ? 'success' : 'warning'),
            ]) },
            { label: t('ui.properties.description'), cell: (unit) => dash(unit.description) },
            { label: t('ui.properties.actions'), align: 'right', cell: (unit) => el('span', { class: 'cell-actions' }, [
                can('manage_operations') ? button(t('ui.properties.edit'), { iconName: 'edit-02', onClick: () => unitForm(building, unit) }) : null,
                removalButton(unit, 'unit', `${unit.name} · ${building.name}`, () => deleteUnit(building, unit)),
            ].filter(Boolean)) },
        ], units, {
            rowClass: (unit) => (lowered !== '' && [unit.name, unit.description].some((f) => String(f ?? '').toLowerCase().includes(lowered)) ? 'is-highlight' : ''),
        });
    }

    function card(building) {
        const units = building.units ?? [];
        const lowered = search.toLowerCase();
        const unitHit = lowered !== '' && units.some((u) => [u.name, u.description].some((f) => String(f ?? '').toLowerCase().includes(lowered)));
        const filterHit = classification !== 'all' && units.some(unitMatches);
        const open = expanded.has(building.id) || unitHit || filterHit;
        const ownerships = building.ownerships ?? [];

        const body = el('div', { class: 'record-card-body', hidden: ! open }, [unitsTable(building)]);
        const toggle = button(open ? t('ui.properties.hide_units') : t('ui.properties.view_units'), { iconName: open ? 'chevron-up' : 'chevron-down', onClick: () => {
            const now = body.hidden;

            body.hidden = ! now;
            toggle.querySelector('span').textContent = now ? t('ui.properties.hide_units') : t('ui.properties.view_units');

            if (now) { expanded.add(building.id); } else { expanded.delete(building.id); }
        } });

        return el('article', { class: 'record-card' }, [
            el('div', { class: 'record-card-head' }, [
                el('h3', { class: 'record-card-title', text: building.name || t('ui.properties.unnamed_property') }),
                badge(`${units.length} ${units.length === 1 ? t('ui.properties.unit_lower') : t('ui.properties.units_lower')}`, 'info'),
            ]),
            el('p', { class: 'record-card-sub', text: joinParts([building.location, building.address]) }),
            el('div', { class: 'inline' }, ownerships.length === 0
                ? [el('span', { class: 'muted-small', text: t('ui.properties.no_ownership_information') })]
                : ownerships.map((o) => badge(`${partyName(o.party) || t('ui.properties.owner')} · ${Number(o.ownership_percentage ?? 0).toFixed(0)}%`, 'owner'))),
            el('div', { class: 'record-card-actions' }, [
                can('manage_operations') ? button(t('ui.properties.edit'), { iconName: 'edit-02', onClick: () => propertyForm(building) }) : null,
                removalButton(building, 'building', building.name, () => deleteBuilding(building)),
                can('manage_operations') ? button(t('ui.properties.add_unit'), { iconName: 'plus', onClick: () => unitForm(building) }) : null,
                toggle,
            ].filter(Boolean)),
            body,
        ]);
    }

    function paint() {
        const found = rows(payload);

        mount(kpis,
            stat(t('ui.properties.buildings'), formatNumber(totalOf(payload))),
            stat(t('ui.properties.total_units'), formatNumber(found.reduce((sum, b) => sum + (b.units?.length ?? 0), 0))),
            stat(t('ui.properties.single_unit_properties'), formatNumber(found.filter((b) => (b.units?.length ?? 0) === 1).length)),
            stat(t('ui.properties.multi_unit_properties'), formatNumber(found.filter((b) => (b.units?.length ?? 0) > 1).length))
        );

        mount(list,
            found.length === 0
                ? emptyState('building-02', t('ui.properties.no_properties_found'), t('ui.properties.no_properties_hint'))
                : el('div', {}, found.map(card)),
            pagination(pageMeta(payload, size.get()), size, reload)
        );
    }

    async function reload(next = page) {
        page = next;
        mount(list, loading(t('ui.properties.loading')));

        try {
            payload = await client.get(`${endpoints.buildings}${query({ page, per_page: size.get(), search })}`);
            paint();
        } catch (failure) {
            mount(list);
            showError(errors, failure, t('ui.properties.unable_to_load'));
        }
    }

    mount(host,
        screenHead({
            eyebrow: t('ui.properties.portfolio'), title: t('ui.properties.heading'), sub: t('ui.properties.page_description'),
            actions: [can('manage_operations') ? button(t('ui.properties.add_property'), { kind: 'primary', iconName: 'plus', compact: false, onClick: () => propertyForm(null) }) : null],
        }),
        errors,
        kpis,
        el('div', { class: 'filters' }, [
            filterField(t('ui.properties.search'), searchBox.node, { span: true }),
            filterField(t('ui.properties.filter_units_label'), classificationSelect),
        ]),
        list
    );

    reload(1);

    return { node: host, reload: () => reload(page) };
}
