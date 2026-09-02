/*
 * Money entry.
 *
 * These exist because the first version of this shipped a hundredfold error:
 * stripping every non-digit turned a typed "1500.50" into 150050. On a
 * screen where somebody has just counted cash and is recording it against a
 * lease, that is the worst defect the application could have.
 *
 * Every case below is a way somebody actually writes an amount.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import { wholeUnits } from '../src/ui/sheet.js';

test('a decimal amount is truncated, never concatenated', () => {
    /* The bug: this produced "150050". */
    assert.equal(wholeUnits('1500.50'), '1500');
    assert.equal(wholeUnits('99.99'), '99');
    assert.equal(wholeUnits('0.75'), '0');
});

test('thousands separators are removed, not treated as decimals', () => {
    /* Truncating at the first separator would give "1". */
    assert.equal(wholeUnits('1,500'), '1500');
    assert.equal(wholeUnits('12,500'), '12500');
    assert.equal(wholeUnits('1,234,567'), '1234567');
});

test('both at once, in either convention', () => {
    assert.equal(wholeUnits('1,500.50'), '1500');
    /* French: space groups, comma is the decimal point. */
    assert.equal(wholeUnits('1 500,50'), '1500');
    /* And the convention that inverts both. */
    assert.equal(wholeUnits('1.500,50'), '1500');
});

test('plain input is left alone', () => {
    assert.equal(wholeUnits('1500'), '1500');
    assert.equal(wholeUnits('0'), '0');
    assert.equal(wholeUnits(''), '');
});

test('anything that is not a number yields nothing, never a partial amount', () => {
    assert.equal(wholeUnits('abc'), '');
    assert.equal(wholeUnits('GHS'), '');
    /* A currency symbol in front must not shift the digits. */
    assert.equal(wholeUnits('GHS 1,500'), '1500');
});

test('a lone separator does not become a number', () => {
    assert.equal(wholeUnits('.'), '');
    assert.equal(wholeUnits(','), '');
    assert.equal(wholeUnits('.50'), '');
});

/*
 * Rounding is always DOWN. The API takes whole units, so a fraction cannot
 * be sent at all - and inventing a unit nobody typed would be worse than
 * losing one they cannot pay.
 */
test('rounding is down, never up', () => {
    assert.equal(wholeUnits('1500.99'), '1500');
    assert.equal(wholeUnits('1.99'), '1');
});
