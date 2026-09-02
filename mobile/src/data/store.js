/*
 * The organisation's working set, held in front of the API.
 *
 * WHY. Every screen used to fetch on arrival, so moving between tabs showed
 * a spinner each time - on a phone, over cellular, that is most of what the
 * application feels like. The working set is small and bounded, so it is
 * fetched once at sign-in and again in the background, and screens render
 * from memory.
 *
 * WHAT IS NOT HERE, deliberately: reports, the activity log and the archive.
 * They are unbounded and paginated, and prefetching them would trade one
 * spinner for a slow launch. They are cached after first use instead, so
 * the second visit is instant.
 *
 * FRESHNESS IS SHOWN, NEVER ASSUMED. This is a money product: an operator
 * reading an arrears figure has to be able to tell how old it is. Every
 * entry carries the moment it was fetched, and the shell renders that under
 * the header. Nothing is silently stale.
 */

const entries = new Map();
const listeners = new Map();

/*
 * Listeners for "anything changed". The freshness line needs these: it
 * reports on the whole store, so a per-key subscription leaves it stale
 * whenever some OTHER key is the last to finish - which is exactly what it
 * is there to prevent.
 */
const anyListeners = new Set();

/*
 * The working set: what an operator opens without thinking, fetched
 * together at sign-in. Keys are stable names, not paths, so a screen never
 * has to know a URL.
 */
export const WORKING_SET = {
    me: '/auth/me',
    buildings: '/buildings',
    parties: '/parties',
    leases: '/leases',
    ownerAccounts: '/owner-accounts',
    notifications: '/notifications',
    /*
     * Home is the first screen, so what it shows is fetched with everything
     * else rather than after it. The organisation is here for one field -
     * the currency - which every figure in the application is written with.
     */
    organisation: '/managing-organisation',
    dashboard: '/dashboard',
    upcoming: '/dashboard/upcoming',
    activity: '/activity-log',
};

function entry(key) {
    if (! entries.has(key)) {
        entries.set(key, { data: null, fetchedAt: null, error: null, loading: false });
    }

    return entries.get(key);
}

function announce(key) {
    for (const listener of listeners.get(key) ?? []) {
        listener(entry(key));
    }

    for (const listener of anyListeners) {
        listener(key);
    }
}

/** Subscribe to every change in the store. Returns an unsubscribe function. */
export function subscribeAny(listener) {
    anyListeners.add(listener);

    return () => anyListeners.delete(listener);
}

/** Subscribe to one key. Returns an unsubscribe function. */
export function subscribe(key, listener) {
    if (! listeners.has(key)) {
        listeners.set(key, new Set());
    }

    listeners.get(key).add(listener);

    return () => listeners.get(key)?.delete(listener);
}

/** What is held right now. Never throws, never waits. */
export function read(key) {
    return { ...entry(key) };
}

/**
 * Fetch one key.
 *
 * On failure the previous data is KEPT. A refresh that fails must not blank
 * a screen the person is reading - it is a stale list, not an empty one,
 * and the freshness line already says how old it is.
 */
export async function fetchKey(client, key, path) {
    const current = entry(key);

    current.loading = true;
    announce(key);

    try {
        current.data = await client.get(path ?? WORKING_SET[key]);
        current.fetchedAt = Date.now();
        current.error = null;
    } catch (failure) {
        current.error = failure;
    } finally {
        current.loading = false;
        announce(key);
    }

    return { ...current };
}

/**
 * Fetch the whole working set at once.
 *
 * allSettled, not all: one endpoint failing must not deny the person the
 * other five. Each key carries its own error.
 */
export async function prime(client) {
    await Promise.allSettled(
        Object.entries(WORKING_SET).map(([key, path]) => fetchKey(client, key, path))
    );
}

/** Refresh everything already held, in the background. */
export async function refreshAll(client) {
    await Promise.allSettled(
        [...entries.keys()].map((key) => fetchKey(client, key, paths.get(key) ?? WORKING_SET[key]))
    );
}

/*
 * Paths for keys outside the working set, remembered so refreshAll can
 * reach them too.
 */
const paths = new Map();

/**
 * Cache-on-first-use, for the screens behind More. Returns immediately with
 * whatever is held; fetches only when nothing is.
 */
export async function ensure(client, key, path) {
    paths.set(key, path);

    const current = entry(key);

    if (current.data === null && current.loading === false) {
        await fetchKey(client, key, path);
    }

    return { ...entry(key) };
}

/** The oldest fetch still standing, which is what the shell reports. */
export function oldestFetchedAt() {
    const stamps = [...entries.values()]
        .map((held) => held.fetchedAt)
        .filter((stamp) => stamp !== null);

    return stamps.length === 0 ? null : Math.min(...stamps);
}

export function isLoading() {
    return [...entries.values()].some((held) => held.loading);
}

/* Signing out must leave nothing of the previous person behind. */
export function clear() {
    entries.clear();
    paths.clear();
}
