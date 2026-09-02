/*
 * The store exists so that moving between screens costs nothing, and so
 * that what it shows is never silently old. Both of those are behaviours,
 * not implementation details, so both are pinned here.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import * as store from '../src/data/store.js';

function recordingClient(responses = {}, { delay = 0 } = {}) {
    const calls = [];

    return {
        calls,
        async get(path) {
            calls.push(path);

            if (delay > 0) {
                await new Promise((resolve) => setTimeout(resolve, delay));
            }

            if (responses[path] instanceof Error) {
                throw responses[path];
            }

            return responses[path] ?? { data: [] };
        },
    };
}

test.beforeEach(() => store.clear());

test('the working set is fetched once, and reading it afterwards fetches nothing', async () => {
    const client = recordingClient();

    await store.prime(client);

    const afterPrime = client.calls.length;

    /* What every tab switch does: read, never fetch. */
    for (const key of Object.keys(store.WORKING_SET)) {
        store.read(key);
        store.read(key);
    }

    assert.equal(afterPrime, Object.keys(store.WORKING_SET).length);
    assert.equal(client.calls.length, afterPrime, 'reading the store must not fetch');
});

test('one endpoint failing does not deny the others', async () => {
    const client = recordingClient({ '/leases': new Error('boom') });

    await store.prime(client);

    assert.equal(store.read('leases').data, null);
    assert.ok(store.read('leases').error instanceof Error);
    assert.notEqual(store.read('buildings').data, null, 'a sibling key must still load');
});

/*
 * A refresh that fails must not blank a screen somebody is reading. It is a
 * stale list, not an empty one - and the freshness line already says so.
 */
test('a failed refresh keeps the data it already had', async () => {
    const good = recordingClient({ '/leases': { data: [{ id: 1 }] } });

    await store.prime(good);

    const before = store.read('leases').data;

    const bad = recordingClient({ '/leases': new Error('offline') });

    await store.fetchKey(bad, 'leases', '/leases');

    assert.deepEqual(store.read('leases').data, before);
    assert.ok(store.read('leases').error instanceof Error);
});

test('cache-on-first-use fetches once and then holds', async () => {
    const client = recordingClient({ '/archive': { data: [{ id: 7 }] } });

    await store.ensure(client, 'archive', '/archive');
    await store.ensure(client, 'archive', '/archive');
    await store.ensure(client, 'archive', '/archive');

    assert.equal(client.calls.length, 1);
    assert.deepEqual(store.read('archive').data, { data: [{ id: 7 }] });
});

test('refreshAll reaches keys that were cached on first use, not just the working set', async () => {
    const client = recordingClient();

    await store.prime(client);
    await store.ensure(client, 'archive', '/archive');

    client.calls.length = 0;

    await store.refreshAll(client);

    assert.ok(client.calls.includes('/archive'), 'a lazily cached key must refresh too');
});

/*
 * The bug this pins: the freshness line reports on the WHOLE store, so it
 * has to hear about every key. Hung off one key's subscription it read
 * "Updating" forever whenever that key finished before the others.
 */
test('subscribeAny hears every key, so global state can be reported', async () => {
    const heard = [];
    const off = store.subscribeAny((key) => heard.push(key));

    await store.prime(recordingClient());

    off();

    for (const key of Object.keys(store.WORKING_SET)) {
        assert.ok(heard.includes(key), `no announcement for ${key}`);
    }
});

test('nothing is loading once a refresh has settled', async () => {
    /* Staggered completion is the case that left the label stuck. */
    const client = {
        calls: [],
        async get(path) {
            this.calls.push(path);
            await new Promise((resolve) => setTimeout(resolve, path === '/buildings' ? 1 : 20));

            return { data: [] };
        },
    };

    await store.prime(client);

    assert.equal(store.isLoading(), false);
    assert.ok(store.oldestFetchedAt() !== null);
});

test('signing out leaves nothing of the previous person', async () => {
    await store.prime(recordingClient({ '/leases': { data: [{ id: 1 }] } }));

    store.clear();

    assert.equal(store.read('leases').data, null);
    assert.equal(store.oldestFetchedAt(), null);
});
