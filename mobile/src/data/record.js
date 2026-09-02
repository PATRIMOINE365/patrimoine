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
