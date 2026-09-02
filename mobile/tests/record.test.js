/*
 * What a record is called.
 *
 * Both shells read this, so a mistake here renames the same thing in two
 * applications at once - and one of these cases shipped to a device, where
 * the whole Finance tab read "#4, #3, #2, #1".
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import { titleOf, subtitleOf, filterRecords } from '../src/data/record.js';

test('an owner account is named for its party, not its id', () => {
    /* The bug: this returned "#4". */
    const account = { id: 4, party_id: 7, status: 'active', party: { id: 7, name: 'Igor Kutsienyo' } };

    assert.equal(titleOf(account), 'Igor Kutsienyo');
});

test('a lease is named for its tenant', () => {
    assert.equal(
        titleOf({ id: 9, tenant: { name: 'Lina Akweley Kutsienyo' } }),
        'Lina Akweley Kutsienyo'
    );
});

test('a property is named for itself', () => {
    assert.equal(titleOf({ id: 1, name: '25D GolF Hills, Achimota' }), '25D GolF Hills, Achimota');
});

test('an id is the last resort, never the first choice', () => {
    assert.equal(titleOf({ id: 12 }), '#12');
});

test('a subtitle never repeats itself', () => {
    /* A single-unit property is commonly named after its building. */
    const lease = {
        unit: { name: '6 Osekere Rd House', building: { name: '6 Osekere Rd House' } },
    };

    assert.equal(subtitleOf(lease), '6 Osekere Rd House');
});

test('search matches every word, in any order, across both lines', () => {
    const records = [
        { id: 1, tenant: { name: 'Lina Akweley Kutsienyo' }, unit: { building: { name: 'Golf Hills' } } },
        { id: 2, tenant: { name: 'Wizard QA Tenant' }, unit: { building: { name: 'Golf Hills' } } },
        { id: 3, tenant: { name: 'Lina Akweley Kutsienyo' }, unit: { building: { name: 'Airport West' } } },
    ];

    assert.deepEqual(filterRecords(records, 'lina golf').map((r) => r.id), [1]);
    assert.deepEqual(filterRecords(records, 'golf lina').map((r) => r.id), [1]);
    assert.deepEqual(filterRecords(records, 'lina').map((r) => r.id), [1, 3]);
    assert.equal(filterRecords(records, '').length, 3);
});

test('search ignores case and stray spaces', () => {
    const records = [{ id: 1, name: '25D GolF Hills, Achimota' }];

    assert.equal(filterRecords(records, '  ACHIMOTA  ').length, 1);
});
