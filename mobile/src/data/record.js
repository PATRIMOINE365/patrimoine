/*
 * How a record is named, described and searched — in one place.
 *
 * Both shells render the same records, and before this each had its own
 * copy of "what is the title of this thing". Two copies is how a property
 * comes to be called one thing on a phone and another on a tablet.
 */

/**
 * The line a person reads first.
 *
 * The order matters and is not arbitrary. An owner account carries no name
 * of its own - it belongs to a `party` - so without that branch the whole
 * Finance tab read "#4, #3, #2, #1", which tells an operator nothing about
 * whose money it is. A lease is named for its tenant, a property for
 * itself. Falling through to an id is the last resort, not a default.
 */
export function titleOf(record) {
    return String(
        record.name
        /* Owner and tenant accounts: the account is the party's. */
        ?? record.party?.name
        ?? record.owner?.name
        ?? record.tenant?.name
        ?? record.tenant_name
        ?? record.label
        ?? record.description
        ?? record.reference
        ?? `#${record.id ?? ''}`
    );
}

/**
 * The line beneath it. De-duplicated, because a single-unit property is
 * commonly named after its building and "6 Osekere Rd House · 6 Osekere Rd
 * House" reads like a bug even though both fields are correct.
 */
export function subtitleOf(record) {
    return [...new Set([
        record.unit?.label ?? record.unit?.name ?? record.unit_name,
        record.unit?.building?.name ?? record.building?.name ?? record.building_name,
        record.address,
        record.role_label ?? record.role,
        record.party?.legal_name,
    ].filter((part) => part !== undefined && part !== null && part !== ''))].join(' · ');
}

/*
 * Search runs against what is already held, so it is instant and works with
 * no signal. It matches the two lines a person can actually see - searching
 * fields nobody is shown produces results that look like a bug.
 */
export function matches(record, query) {
    if (query === '') {
        return true;
    }

    const haystack = `${titleOf(record)} ${subtitleOf(record)}`.toLowerCase();

    /*
     * Every word must appear, in any order: "lina golf" finds the Golf
     * Hills lease for Lina without her having to remember which came first.
     */
    return query
        .toLowerCase()
        .split(/\s+/)
        .filter((word) => word !== '')
        .every((word) => haystack.includes(word));
}

export function filterRecords(records, query) {
    const trimmed = String(query ?? '').trim();

    return trimmed === '' ? records : records.filter((record) => matches(record, trimmed));
}


/**
 * Does this party hold a role?
 *
 * `roles` is an eager-loaded RELATION - an array of role rows - not a
 * string. Testing it with String(...).includes() stringifies to
 * "[object Object]" and matches nothing, which is why a party who is
 * plainly a tenant was invisible to anything that asked.
 */
export function hasRole(party, role) {
    const roles = party?.roles ?? party?.role;

    if (Array.isArray(roles)) {
        return roles.some((entry) => (
            (typeof entry === 'string' ? entry : entry?.role ?? entry?.name) === role
        ));
    }

    return typeof roles === 'string' && roles.split(/[,\s]+/).includes(role);
}

/*
 * A human label for an API field name. The record screens used to print the
 * key with its underscores swapped for spaces - "vat rate", "rent amount",
 * "advance received" - which is a database column, not a caption.
 */
const LABELS = {
    name: 'Name',
    legal_name: 'Legal name',
    given_names: 'Given names',
    surname: 'Surname',
    email: 'Email address',
    phone: 'Telephone',
    alternate_phone: 'Alternate telephone',
    address: 'Address',
    location: 'Location',
    description: 'Description',
    notes: 'Notes',
    status: 'Status',
    type: 'Type',
    reference: 'Reference',
    id_number: 'ID number',
    registration_number: 'Registration number',
    vat_tin: 'VAT / TIN',
    vat_rate: 'VAT rate',
    bank_name: 'Bank',
    bank_account_name: 'Account name',
    bank_account_number: 'Account number',
    bank_branch: 'Branch',
    contact_person_name: 'Contact person',
    contact_person_phone: 'Contact telephone',
    contact_person_email: 'Contact email',
    rent_amount: 'Rent',
    monthly_rent: 'Monthly rent',
    payment_frequency: 'Payment frequency',
    start_date: 'Start date',
    end_date: 'End date',
    due_day: 'Payment due day',
    security_deposit_amount: 'Security deposit',
    advance_payment_amount: 'Advance payment',
    advance_received: 'Advance received',
    rent_reserve_amount: 'Rent reserve',
    rent_increment_type: 'Rent increment',
    rent_increment_value: 'Increment value',
    management_fee_type: 'Management fee',
    management_fee_value: 'Fee value',
    agent_commission_amount: 'Agent commission',
    balance: 'Balance',
    credited_amount: 'Credited',
    debited_amount: 'Debited',
    property_count: 'Properties',
    units_count: 'Units',
    invoice_number: 'Invoice number',
    total_amount: 'Total',
    paid_amount: 'Paid',
    outstanding_amount: 'Outstanding',
    due_date: 'Due date',
    occupancy_rate: 'Occupancy',
    currency: 'Currency',
    language: 'Language',
};

/** Title-case a key nobody has named yet: "unit_label" -> "Unit label". */
export function fieldLabel(key) {
    if (LABELS[key] !== undefined) {
        return LABELS[key];
    }

    const words = String(key).replace(/_/g, ' ').trim();

    return words.charAt(0).toUpperCase() + words.slice(1);
}

/*
 * Values that arrive as machine tokens - "bank_transfer", "bi_yearly" - and
 * should be read as words.
 */
export function fieldValue(raw) {
    if (typeof raw !== 'string') {
        return String(raw);
    }

    if (! /^[a-z][a-z0-9_]*$/.test(raw) || raw.length > 40) {
        return raw;
    }

    const words = raw.replace(/_/g, ' ');

    return words.charAt(0).toUpperCase() + words.slice(1);
}
