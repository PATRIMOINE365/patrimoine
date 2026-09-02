/*
 * The party form - one definition, used wherever a party is created or
 * edited: the Parties page, the inline owner on a property, and the lease
 * assistant's "add someone new" blocks.
 *
 * The web application's drawer, field for field: type, then the person or
 * organisation block, contact and identification, roles, banking, the
 * e-mail policy and notes. The payload sends explicit nulls for the other
 * type's fields so a type change cannot leave stale identity data behind -
 * the same rule the browser follows.
 */

import { t } from '../i18n/index.js';
import { openSheet } from '../ui/sheet.js';
import { endpoints } from '../api/endpoints.js';
import { hasRole } from '../data/record.js';

export const PARTY_TYPES = ['person', 'organisation', 'association'];

export function partyTypeOptions() {
    return PARTY_TYPES.map((type) => ({ value: type, label: t(`ui.parties.${type}`) }));
}

const isPerson = (values) => values.type === 'person';
const isOrganisation = (values) => values.type !== 'person';

/**
 * The fields of the full party drawer.
 * @param {object|null} party  an existing record, or null
 * @param {object} [options]
 * @param {string[]} [options.forceRoles]  roles fixed by the caller (an inline owner)
 * @param {boolean} [options.compact]  identity + contact only (the assistant's blocks)
 */
export function partyFields(party, { forceRoles = null, compact = false } = {}) {
    const type = party?.type ?? 'person';
    const roles = party ? ['owner', 'tenant', 'agent'].filter((role) => hasRole(party, role)) : (forceRoles ?? []);

    return [
        { name: 'h_type', type: 'heading', label: t('ui.parties.party_type'), hint: t('ui.parties.party_type_description') },
        { name: 'type', type: 'select', label: t('ui.parties.party_type'), value: type, options: partyTypeOptions(), required: true },

        { name: 'h_person', type: 'heading', label: t('ui.parties.personal_details'), when: isPerson },
        { name: 'given_names', type: 'text', label: t('ui.parties.given_names'), value: party?.given_names ?? '', maxlength: 255, when: isPerson, required: true },
        { name: 'surname', type: 'text', label: t('ui.parties.surname'), value: party?.surname ?? '', maxlength: 255, when: isPerson, required: true },
        { name: 'phone', type: 'phone', label: t('ui.parties.phone'), value: party?.phone, country: party?.phone_country, when: isPerson, required: true },
        { name: 'email', type: 'email', label: t('ui.parties.email'), value: party?.email ?? '', maxlength: 255, when: isPerson, required: true },

        { name: 'h_org', type: 'heading', label: t('ui.parties.organisation_details'), when: isOrganisation },
        { name: 'legal_name', type: 'text', label: t('ui.parties.legal_name'), value: party?.legal_name ?? '', maxlength: 255, when: isOrganisation, required: true },
        { name: 'contact_person_name', type: 'text', label: t('ui.parties.contact_person'), value: party?.contact_person_name ?? '', maxlength: 255, when: isOrganisation, required: true },
        { name: 'contact_person_phone', type: 'phone', label: t('ui.parties.contact_phone'), value: party?.contact_person_phone, country: party?.contact_person_phone_country, when: isOrganisation, required: true },
        { name: 'contact_person_email', type: 'email', label: t('ui.parties.contact_email'), value: party?.contact_person_email ?? '', maxlength: 255, when: isOrganisation, required: true },

        ...(compact ? [
            { name: 'address', type: 'textarea', rows: 2, label: t('ui.parties.address'), value: party?.address ?? '' },
        ] : [
            { name: 'h_contact', type: 'heading', label: t('ui.parties.contact_identification'), hint: t('ui.parties.contact_identification_description') },
            { name: 'alternate_phone', type: 'phone', label: t('ui.parties.alternate_phone'), value: party?.alternate_phone, country: party?.alternate_phone_country },
            { name: 'id_number', type: 'text', label: t('ui.parties.id_number'), value: party?.id_number ?? '', maxlength: 255 },
            { name: 'registration_number', type: 'text', label: t('ui.parties.registration_number'), value: party?.registration_number ?? '', maxlength: 255 },
            { name: 'vat_tin', type: 'text', label: t('ui.parties.vat_tin'), value: party?.vat_tin ?? '', maxlength: 255 },
            { name: 'address', type: 'textarea', rows: 2, label: t('ui.parties.address'), value: party?.address ?? '' },

            ...(forceRoles ? [] : [
                { name: 'h_roles', type: 'heading', label: t('ui.parties.roles'), hint: t('ui.parties.roles_description') },
                { name: 'role_owner', type: 'toggle', label: t('ui.parties.owner'), value: roles.includes('owner') },
                { name: 'role_tenant', type: 'toggle', label: t('ui.parties.tenant'), value: roles.includes('tenant') },
                { name: 'role_agent', type: 'toggle', label: t('ui.parties.agent'), value: roles.includes('agent') },
            ]),

            { name: 'h_bank', type: 'heading', label: t('ui.parties.banking_details'), hint: t('ui.parties.banking_description') },
            { name: 'bank_name', type: 'text', label: t('ui.parties.bank_name'), value: party?.bank_name ?? '', maxlength: 255 },
            { name: 'bank_branch', type: 'text', label: t('ui.parties.bank_branch'), value: party?.bank_branch ?? '', maxlength: 255 },
            { name: 'bank_account_name', type: 'text', label: t('ui.parties.account_name'), value: party?.bank_account_name ?? '', maxlength: 255 },
            { name: 'bank_account_number', type: 'text', label: t('ui.parties.account_number'), value: party?.bank_account_number ?? '', maxlength: 255 },

            { name: 'h_email', type: 'heading', label: t('ui.parties.email_policy'), hint: t('ui.parties.email_policy_description') },
            { name: 'email_policy', type: 'select', label: t('ui.parties.email_policy'), value: party?.email_policy ?? 'inherit', hint: t('ui.parties.email_policy_help'), options: [
                { value: 'inherit', label: t('ui.parties.email_policy_inherit') },
                { value: 'always', label: t('ui.parties.email_policy_always') },
                { value: 'never', label: t('ui.parties.email_policy_never') },
            ] },
            { name: 'notes', type: 'textarea', rows: 4, label: t('ui.parties.notes'), value: party?.notes ?? '', placeholder: t('ui.parties.notes_placeholder') },
        ]),
    ];
}

/** The client-side checks the browser makes before sending. */
export function validateParty(values) {
    if (values.type === 'person') {
        if (! values.surname || ! values.phone?.number || ! values.email) {
            return { _: t('ui.properties.person_required_fields') };
        }
    } else if (! values.legal_name || ! values.contact_person_name || ! values.contact_person_phone?.number || ! values.contact_person_email) {
        return { _: t('ui.properties.organisation_required_fields') };
    }

    return null;
}

/** The body for POST/PUT /parties. */
export function partyPayload(values, { existing = null, forceRoles = null } = {}) {
    const person = values.type === 'person';

    let roles;

    if (forceRoles) {
        roles = forceRoles;
    } else {
        roles = ['owner', 'tenant', 'agent'].filter((role) => values[`role_${role}`] === true);

        /* Preserved silently, as the browser does: it is not editable here. */
        if (existing && hasRole(existing, 'managing_organisation')) {
            roles.push('managing_organisation');
        }
    }

    return {
        type: values.type,
        name: null,
        given_names: person ? (values.given_names || null) : null,
        surname: person ? (values.surname || null) : null,
        phone: person ? (values.phone?.number || null) : null,
        phone_country: person ? (values.phone?.country ?? null) : null,
        email: person ? (values.email || null) : null,
        legal_name: person ? null : (values.legal_name || null),
        contact_person_name: person ? null : (values.contact_person_name || null),
        contact_person_phone: person ? null : (values.contact_person_phone?.number || null),
        contact_person_phone_country: person ? null : (values.contact_person_phone?.country ?? null),
        contact_person_email: person ? null : (values.contact_person_email || null),
        alternate_phone: values.alternate_phone?.number || null,
        alternate_phone_country: values.alternate_phone?.number ? values.alternate_phone.country : null,
        address: values.address || null,
        id_number: values.id_number || null,
        registration_number: values.registration_number || null,
        vat_tin: values.vat_tin || null,
        bank_name: values.bank_name || null,
        bank_account_name: values.bank_account_name || null,
        bank_account_number: values.bank_account_number || null,
        bank_branch: values.bank_branch || null,
        notes: values.notes || null,
        email_policy: values.email_policy ?? 'inherit',
        roles,
    };
}

/**
 * Open the drawer and create or update a party.
 * @returns {Promise<object|false>} the saved party, or false
 */
export async function partySheet(client, party = null, { forceRoles = null, title, description } = {}) {
    const editing = party !== null;

    return openSheet({
        title: title ?? (editing ? t('ui.parties.edit_party') : t('ui.parties.add_party')),
        description: description ?? (editing ? t('ui.parties.edit_party_description') : t('ui.parties.add_party_description')),
        width: 'lg',
        submitLabel: t('ui.actions.save'),
        fields: partyFields(party, { forceRoles }),
        validate: validateParty,
        onSubmit: async (values) => {
            const payload = partyPayload(values, { existing: party, forceRoles });

            if (editing) {
                return client.patch(endpoints.party(party.id), payload);
            }

            return client.post(endpoints.parties, payload);
        },
    });
}
