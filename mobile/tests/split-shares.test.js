/*
 * The split preview must match the server's proration exactly: a
 * co-owner reading "Share per owner" before confirming an expense bill is
 * reading the figure that will be recorded.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import { splitShares } from '../src/data/split.js';

const owners = (...shares) => shares.map((ownership_percentage) => ({ ownership_percentage }));

test('an even split of a divisible amount needs no remainder', () => {
    assert.deepEqual(splitShares([{ amount: 100 }], owners(50, 50)), [50, 50]);
});

test('units lost to flooring go to the largest fractional parts', () => {
    /* 100 across thirds: 33.33 each, one unit left over for the first. */
    assert.deepEqual(splitShares([{ amount: 100 }], owners(33.34, 33.33, 33.33)), [34, 33, 33]);
});

test('every line adds up to itself, so the bill adds up to its total', () => {
    const lines = [{ amount: 1001 }, { amount: 7 }, { amount: 250 }];
    const shares = splitShares(lines, owners(60, 25, 15));

    assert.equal(shares.reduce((s, v) => s + v, 0), 1258);
});

test('shares that do not total 100 are still shared proportionally', () => {
    assert.deepEqual(splitShares([{ amount: 90 }], owners(20, 10)), [60, 30]);
});
