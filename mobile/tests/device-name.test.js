/*
 * The name a handset gives itself when it signs in.
 *
 * A token's name is fixed at mint time and can never be recovered, so this
 * is the only chance to get it right - and it is what somebody reads in
 * Settings -> Devices when deciding which session to revoke.
 *
 * The logic lives in main.js, which cannot be imported here: it boots the
 * application on import and reaches for Capacitor. It is small enough to
 * mirror, and this pins the behaviour rather than the wiring.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

const GENERIC = ['iphone', 'ipad', 'ipod touch', 'android', 'phone'];

function deviceName(info, platform) {
    const named = (info.name ?? '').trim();
    const model = (info.model ?? '').trim();

    return (
        named === ''
            ? model
            : (GENERIC.includes(named.toLowerCase()) && model !== '' && model !== named
                ? `${named} (${model})`
                : named)
    ) || `Patrimoine on ${platform}`;
}

/*
 * Measured on the real handset: iOS returned name "iPhone" and model
 * "iPhone11,2", and joining them gave "iPhone iPhone11,2".
 */
test('a generic name is qualified by the model, not concatenated with it', () => {
    assert.equal(
        deviceName({ name: 'iPhone', model: 'iPhone11,2' }, 'ios'),
        'iPhone (iPhone11,2)'
    );
});

test('a name the owner chose is left alone', () => {
    assert.equal(
        deviceName({ name: "Komla's iPhone", model: 'iPhone11,2' }, 'ios'),
        "Komla's iPhone"
    );
});

test('no name falls back to the model', () => {
    assert.equal(deviceName({ name: '', model: 'iPad13,1' }, 'ios'), 'iPad13,1');
});

test('nothing at all still names the platform, never an empty row', () => {
    assert.equal(deviceName({}, 'ios'), 'Patrimoine on ios');
});

test('a name identical to the model is not doubled', () => {
    assert.equal(
        deviceName({ name: 'iPhone11,2', model: 'iPhone11,2' }, 'ios'),
        'iPhone11,2'
    );
});
