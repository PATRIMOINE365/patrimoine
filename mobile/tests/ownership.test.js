/*
 * The owners an update sends back.
 *
 * This is the one mapping in the application that can strip the ownership
 * of a property. UpdateBuildingRequest requires `owners` with at least one
 * entry, and Laravel reads absent or empty as invalid input, not as "leave
 * it as it was" - so an edit form that forgot to resend them, or resent
 * them wrongly, would rewrite who owns a building.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import { ownersFor } from '../src/screens/tablet-records.js';

test('ownerships are carried through unchanged', () => {
    const building = {
        ownerships: [
            { party_id: 5, ownership_percentage: 60 },
            { party_id: 8, ownership_percentage: 40 },
        ],
    };

    assert.deepEqual(ownersFor(building), [
        { party_id: 5, ownership_percentage: 60 },
        { party_id: 8, ownership_percentage: 40 },
    ]);
});

test('a nested party object still yields its id', () => {
    const building = {
        ownerships: [{ party: { id: 5, name: 'Igor Kutsienyo' }, ownership_percentage: 100 }],
    };

    assert.deepEqual(ownersFor(building), [{ party_id: 5, ownership_percentage: 100 }]);
});

/*
 * Dropping an incomplete entry is deliberate: sending an owner without a
 * share is a 422, and guessing a share invents a division of a property.
 */
test('an entry that cannot be mapped completely is dropped, not guessed', () => {
    const building = {
        ownerships: [
            { party_id: 5, ownership_percentage: 100 },
            { party_id: 9 },
        ],
    };

    assert.deepEqual(ownersFor(building), [{ party_id: 5, ownership_percentage: 100 }]);
});

/*
 * The caller checks for this and refuses to save. An empty array would be
 * rejected by the API - which is the safe outcome - but the point is that
 * it must never be sent as though it were the truth.
 */
test('a building with no usable ownerships yields nothing', () => {
    assert.deepEqual(ownersFor({}), []);
    assert.deepEqual(ownersFor({ ownerships: [] }), []);
    assert.deepEqual(ownersFor({ ownerships: [{ ownership_percentage: 50 }] }), []);
});

test('a zero share is kept - it is a real value, not a missing one', () => {
    assert.deepEqual(
        ownersFor({ ownerships: [{ party_id: 5, ownership_percentage: 0 }] }),
        [{ party_id: 5, ownership_percentage: 0 }]
    );
});
