/*
 * The iPad's record screens - one per kind, showing what the web
 * application shows, and editing what can be edited safely.
 *
 * WHAT IS EDITABLE, AND WHY NOT EVERYTHING.
 *
 * A building and a party have small, well-understood update contracts and
 * are edited here. A LEASE IS NOT. UpdateLeaseRequest requires some twenty
 * fields - every deposit, every fee, the increment policy, the advance -
 * and Laravel's `required` means absent is invalid, not unchanged. A form
 * that showed six of them and sent six would blank the rest, silently, on
 * a record that decides what somebody is charged. The web application uses
 * a guided assistant for exactly this reason, and the mobile contract
 * already says lease creation is tablet-only and lease editing does not
 * exist as a concept.
 *
 * So a lease is shown in full and reached through the actions that DO have
 * their own endpoints - extend, rent increments, financial history.
 */

import { el } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { money, shortDate } from '../ui/money.js';
import { titleOf, subtitleOf, hasRole, fieldLabel, fieldValue } from '../data/record.js';
import { openSheet } from '../ui/sheet.js';
import { openDocument, FORMATS } from '../data/exports.js';
import * as store from '../data/store.js';

function rows(payload) {
    return Array.isArray(payload) ? payload : (payload?.data ?? []);
}

/* Fields the reader should never be shown: plumbing, not information. */
const HIDDEN = new Set([
    'id', 'created_at', 'updated_at', 'deleted_at', 'archived_at',
    'organisation_id', 'building_id', 'unit_id', 'tenant_id', 'agent_id',
    'party_id', 'lease_id', 'latitude', 'longitude',
]);

/* Anything whose name says it is money gets written as money. */
const MONEY = /(_amount|_balance|^balance$|_fee$|rent$|_rent)/;

function value(key, raw) {
    if (typeof raw === 'boolean') {
        return raw ? t('common.yes') : t('common.no');
    }

    if (MONEY.test(key) && Number.isFinite(Number(raw))) {
        return money(raw);
    }

    if (/_date$|^date$/.test(key) && typeof raw === 'string') {
        return shortDate(raw);
    }

    return fieldValue(raw);
}

function facts(record, only) {
    const entries = Object.entries(record).filter(([key, raw]) => (
        ! HIDDEN.has(key)
        && raw !== null && raw !== ''
        && typeof raw !== 'object'
        && (only === undefined || only.includes(key))
    ));

    if (entries.length === 0) {
        return null;
    }

    return el('dl', { class: 'facts' }, entries.flatMap(([key, raw]) => [
        el('dt', { class: 'fact-label', text: fieldLabel(key) }),
        el('dd', { class: 'fact-value', text: value(key, raw) }),
    ]));
}

function section(title, body) {
    return body === null ? null : el('section', { class: 'card' }, [
        el('header', { class: 'card-head' }, [
            el('h2', { class: 'card-title', text: title }),
        ]),
        body,
    ]);
}

function relatedList(items, onOpen) {
    if (items.length === 0) {
        return null;
    }

    return el('ul', { class: 'list list-flush' }, items.map((item) => {
        const subtitle = subtitleOf(item);

        return el('li', {
            class: onOpen ? 'row row-tappable' : 'row',
            onclick: onOpen ? () => onOpen(item) : undefined,
        }, [
            el('div', { class: 'row-main' }, [
                el('span', { class: 'row-title', text: titleOf(item) }),
                subtitle === '' ? null : el('span', { class: 'row-subtitle', text: subtitle }),
            ]),
            item.status ? el('span', { class: `chip chip-${item.status}`, text: String(item.status) }) : null,
            onOpen ? icon('chevron-right', { size: 20, class: 'row-chevron' }) : null,
        ]);
    }));
}

function actionBar(children) {
    return el('div', { class: 'record-actions' }, children.filter(Boolean));
}

function actionButton(label, iconName, onClick, kind = 'button-secondary') {
    return el('button', { class: `button button-compact ${kind}`, onclick: onClick }, [
        icon(iconName, { size: 18 }),
        el('span', { text: label }),
    ]);
}

/* ------------------------------------------------------------------ edits */

/**
 * The owners array an update must send back, from whatever the record has.
 *
 * Pure, and tested, because this is the one mapping that can strip the
 * ownership of a property: `owners` is required, and Laravel reads absent
 * or empty as invalid input rather than as "leave it alone". An entry that
 * cannot be mapped completely is dropped, and a caller that ends up with
 * nothing must refuse to save rather than send an empty array.
 */
export function ownersFor(building) {
    return (building.ownerships ?? building.owners ?? [])
        .map((entry) => ({
            party_id: entry.party_id ?? entry.party?.id,
            ownership_percentage: entry.ownership_percentage ?? entry.share ?? null,
        }))
        .filter((entry) => (
            entry.party_id !== undefined
            && entry.party_id !== null
            && entry.ownership_percentage !== null
        ));
}

export async function editBuilding(client, building, reload) {
    const owners = ownersFor(building);

    /*
     * Refused rather than risked. Saving with an empty owners array would
     * ask the API to rewrite who owns this property.
     */
    if (owners.length === 0) {
        await openSheet({
            title: t('edit.building'),
            submitLabel: t('common.close'),
            fields: [{ name: 'note', type: 'note', label: t('edit.no_owners') }],
            onSubmit: async () => {},
        });

        return;
    }

    const saved = await openSheet({
        title: t('edit.building'),
        submitLabel: t('edit.save'),
        fields: [
            { name: 'name', label: t('field.name'), type: 'text', value: building.name ?? '' },
            { name: 'address', label: t('field.address'), type: 'text', value: building.address ?? '' },
            { name: 'location', label: t('field.location'), type: 'text', value: building.location ?? '' },
            { name: 'description', label: t('field.description'), type: 'textarea', value: building.description ?? '' },
            { name: 'notes', label: t('write.notes'), type: 'textarea', value: building.notes ?? '' },
        ],
        onSubmit: async (values) => {
            await client.put(endpoints.building(building.id), {
                name: values.name,
                address: values.address || null,
                location: values.location || null,
                description: values.description || null,
                notes: values.notes || null,
                /* Sent back untouched: required, and not this form's business. */
                owners,
            });
        },
    });

    if (saved) {
        await store.refreshAll(client);
        reload();
    }
}

/*
 * A party. `type` is required and `roles` decides what the party may be, so
 * both are resent from the record rather than offered for edit - changing
 * what somebody IS is not a contact-details change.
 */
export async function editParty(client, party, reload) {
    const saved = await openSheet({
        title: t('edit.party'),
        submitLabel: t('edit.save'),
        fields: [
            { name: 'name', label: t('field.name'), type: 'text', value: party.name ?? '' },
            { name: 'legal_name', label: t('field.legal_name'), type: 'text', value: party.legal_name ?? '' },
            { name: 'email', label: t('signin.email'), type: 'text', value: party.email ?? '' },
            { name: 'address', label: t('field.address'), type: 'text', value: party.address ?? '' },
            { name: 'notes', label: t('write.notes'), type: 'textarea', value: party.notes ?? '' },
        ],
        onSubmit: async (values) => {
            await client.put(endpoints.party(party.id), {
                type: party.type,
                roles: party.roles ?? undefined,
                name: values.name || null,
                legal_name: values.legal_name || null,
                email: values.email || null,
                address: values.address || null,
                notes: values.notes || null,
            });
        },
    });

    if (saved) {
        await store.refreshAll(client);
        reload();
    }
}

/* ---------------------------------------------------------------- screens */

/**
 * The right screen for a record, by section.
 *
 * @returns {(client, record, helpers) => Promise<Node>}
 */
export function recordView(sectionId) {
    return VIEWS[sectionId] ?? VIEWS.generic;
}

const VIEWS = {
    async properties(client, record, { reload, open }) {
        const full = (await client.get(endpoints.building(record.id)))?.data ?? record;
        const units = rows(full.units);
        const owners = full.ownerships ?? [];

        return el('div', { class: 'record' }, [
            actionBar([
                actionButton(t('edit.edit'), 'edit-02', () => editBuilding(client, full, reload), 'button-primary'),
                actionButton(t('export.pdf'), 'file-05', () => openDocument(client, `/reports/buildings/${full.id}/pdf`)),
                actionButton(t('export.xlsx'), 'grid-01', () => openDocument(client, `/reports/buildings/${full.id}/xlsx`)),
            ]),
            section(t('record.details'), facts(full)),
            /*
             * A unit opens into its own record, with the report the API
             * produces per unit. This is what the chevron promises.
             */
            section(t('record.units'), relatedList(units, (unit) => open({
                label: 'record.unit',
                icon: 'building-02',
                async load(inner) {
                    const full = (await inner.get(endpoints.unit(unit.id)))?.data ?? unit;

                    return el('div', { class: 'record' }, [
                        actionBar(FORMATS.map((format) => actionButton(
                            t(format.label),
                            format.icon,
                            () => openDocument(inner, `/reports/units/${full.id}/${format.id}`)
                        ))),
                        section(t('record.details'), facts(full)),
                    ]);
                },
            }))),
            section(t('nav.owners'), relatedList(owners.map((o) => o.party ?? o))),
        ]);
    },

    async parties(client, record, { reload }) {
        const full = (await client.get(endpoints.party(record.id)))?.data ?? record;

        /*
         * A party's report exists per role: an owner statement and a tenant
         * statement are different documents. Only the ones the party's role
         * actually supports are offered.
         */
        const asOwner = hasRole(full, 'owner');
        const asTenant = hasRole(full, 'tenant');

        return el('div', { class: 'record' }, [
            actionBar([
                actionButton(t('edit.edit'), 'edit-02', () => editParty(client, full, reload), 'button-primary'),
                ...(asOwner ? FORMATS.map((format) => actionButton(
                    `${t('record.owner_statement')} ${format.label ? t(format.label) : ''}`.trim(),
                    format.icon,
                    () => openDocument(client, `/reports/owners/${full.id}/${format.id}`)
                )) : []),
                ...(asTenant ? FORMATS.map((format) => actionButton(
                    `${t('record.tenant_statement')} ${t(format.label)}`,
                    format.icon,
                    () => openDocument(client, `/reports/tenants/${full.id}/${format.id}`)
                )) : []),
            ]),
            section(t('record.details'), facts(full)),
            /*
             * Erasure is destructive and irreversible - it pseudonymises
             * every identity field while the ledger keeps the row - so it
             * sits apart from everything else, at the bottom, behind a
             * confirmation that names the person.
             */
            el('section', { class: 'card card-danger' }, [
                el('header', { class: 'card-head' }, [
                    el('h2', { class: 'card-title', text: t('erase.title') }),
                ]),
                el('div', { class: 'card-body' }, [
                    el('p', { class: 'record-note', text: t('erase.body') }),
                    el('button', {
                        class: 'button button-danger button-compact',
                        text: t('erase.action'),
                        onclick: async () => {
                            const confirmed = await openSheet({
                                title: t('erase.title'),
                                submitLabel: t('erase.action'),
                                fields: [
                                    { name: 'note', type: 'note', label: t('erase.confirm', { name: titleOf(full) }) },
                                ],
                                onSubmit: async () => {
                                    await client.post(`${endpoints.party(full.id)}/erase`, {});
                                },
                            });

                            if (confirmed) {
                                await store.refreshAll(client);
                                reload();
                            }
                        },
                    }),
                ]),
            ]),
        ]);
    },

    async leases(client, record) {
        const full = (await client.get(endpoints.lease(record.id)))?.data ?? record;

        return el('div', { class: 'record' }, [
            /*
             * No Edit. See the note at the top of this file: a lease update
             * requires every field, and a partial one blanks the rest.
             */
            actionBar([
                actionButton(t('record.financial_history'), 'coins-stacked',
                    () => openDocument(client, `${endpoints.leaseFinancialHistory(full.id)}/pdf`)),
                actionButton(t('export.csv'), 'file-check',
                    () => openDocument(client, `${endpoints.leaseFinancialHistory(full.id)}/csv`)),
            ]),
            el('p', { class: 'record-note', text: t('record.lease_edit_note') }),
            section(t('record.details'), facts(full)),
        ]);
    },

    async accounting(client, record) {
        const full = (await client.get(endpoints.ownerAccount(record.id)))?.data ?? record;
        const owner = full.party ?? {};

        return el('div', { class: 'record' }, [
            /* An owner account's document is the owner's statement. */
            actionBar(owner.id === undefined ? [] : FORMATS.map((format) => actionButton(
                `${t('record.owner_statement')} ${t(format.label)}`,
                format.icon,
                () => openDocument(client, `/reports/owners/${owner.id}/${format.id}`)
            ))),
            section(t('record.details'), facts(full)),
            owner.id === undefined ? null : section(t('nav.owners'), relatedList([owner])),
        ]);
    },

    async users(client, record) {
        return el('div', { class: 'record' }, [
            section(t('record.details'), facts(record)),
        ]);
    },

    async generic(client, record) {
        return el('div', { class: 'record' }, [
            section(t('record.details'), facts(record)),
        ]);
    },
};

/*
 * Archive: a record here can be restored. DELETE /archive/{kind}/{id} is
 * the restore - the archive is a state, and removing the archive entry is
 * what puts the record back into the lists and pickers.
 */
VIEWS.archive = async function archive(client, record, { reload }) {
    const kind = record.kind ?? record.type;

    return el('div', { class: 'record' }, [
        actionBar([
            kind === undefined ? null : actionButton(t('archive.restore'), 'corner-up-left', async () => {
                await client.delete(`/archive/${kind}/${record.id}`);
                await store.refreshAll(client);
                reload();
            }, 'button-primary'),
        ]),
        section(t('record.details'), facts(record)),
    ]);
};

/* Tenants and owners are parties, so they read the party screen. */
VIEWS.tenants = VIEWS.parties;
VIEWS.owners = VIEWS.parties;
