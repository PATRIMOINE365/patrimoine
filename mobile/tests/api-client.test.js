/*
 * The two rules that cannot be enforced screen by screen.
 *
 * Run with: npm test (node --test, no framework).
 */

import test from 'node:test';
import assert from 'node:assert/strict';

import { ApiClient } from '../src/api/client.js';
import { isBelow, evaluate, LAUNCH_OK, LAUNCH_UPDATE_REQUIRED, LAUNCH_MAINTENANCE, LAUNCH_UNREACHABLE } from '../src/boot/config.js';

/* The client reads the token through the session module; stub it out. */
const originalFetch = globalThis.fetch;

function record() {
    const calls = [];

    globalThis.fetch = async (url, init) => {
        calls.push({ url, init });

        return new Response(JSON.stringify({ ok: true }), {
            status: 200,
            headers: { 'content-type': 'application/json' },
        });
    };

    return calls;
}

function client() {
    return new ApiClient({
        baseUrl: 'https://example.test/api/v1',
        appVersion: '1.0.0',
        platform: 'ios',
        language: 'fr',
    });
}

test.afterEach(() => {
    globalThis.fetch = originalFetch;
});

/*
 * Measured on the live API 2026-09-01: a bodyless POST answers 403 from the
 * production WAF while the same call with {} answers correctly. Pre-prod
 * has no such filter, so nothing catches this until the build is on a real
 * phone pointed at production. Hence a test rather than a comment.
 */
test('every POST carries a JSON body, even when there is nothing to send', async () => {
    const calls = record();

    await client().post('/auth/logout');

    assert.equal(calls.length, 1);
    assert.equal(calls[0].init.body, '{}');
    assert.equal(calls[0].init.headers['Content-Type'], 'application/json');
});

test('PATCH, PUT and DELETE carry a body too', async () => {
    const calls = record();
    const api = client();

    await api.delete('/auth/devices/7');
    await api.patch('/auth/me', { name: 'Komla' });
    await api.put('/managing-organisation', {});

    for (const call of calls) {
        assert.ok(call.init.body !== undefined, `${call.init.method} sent no body`);
    }
});

test('GET sends no body and no content-type', async () => {
    const calls = record();

    await client().get('/leases');

    assert.equal(calls[0].init.body, undefined);
    assert.equal(calls[0].init.headers['Content-Type'], undefined);
});

test('the client declares itself on every request', async () => {
    const calls = record();

    await client().get('/config');

    const { headers } = calls[0].init;

    assert.equal(headers['X-Patrimoine-Client'], 'mobile');
    assert.equal(headers['X-Patrimoine-Platform'], 'ios');
    assert.equal(headers['X-App-Version'], '1.0.0');
    assert.equal(headers['X-Patrimoine-Language'], 'fr');
});

/* The forced update: the whole point of shipping /config in build one. */
test('version comparison treats missing segments as zero', () => {
    assert.equal(isBelow('1.0.0', '1.0.1'), true);
    assert.equal(isBelow('1.2', '1.2.1'), true);
    assert.equal(isBelow('1.10.0', '1.9.0'), false);
    assert.equal(isBelow('2.0.0', '2.0.0'), false);
});

test('a build below the floor is refused, and the floor is per platform', () => {
    const config = {
        minimum_version: { ios: '1.2.0', android: '1.0.0' },
        store_url: { ios: 'https://apps.apple.com/x', android: null },
    };

    const ios = evaluate(config, { appVersion: '1.1.9', platform: 'ios' });
    const android = evaluate(config, { appVersion: '1.1.9', platform: 'android' });

    assert.equal(ios.state, LAUNCH_UPDATE_REQUIRED);
    assert.equal(ios.storeUrl, 'https://apps.apple.com/x');
    assert.equal(android.state, LAUNCH_OK);
});

test('an update that is also in maintenance sends the person to the store', () => {
    const decision = evaluate(
        {
            minimum_version: { ios: '2.0.0' },
            maintenance: { active: true, message: 'Back at 18:00' },
        },
        { appVersion: '1.0.0', platform: 'ios' }
    );

    /* The update is the thing they can act on. */
    assert.equal(decision.state, LAUNCH_UPDATE_REQUIRED);
});

test('maintenance shows the message the server wrote', () => {
    const decision = evaluate(
        { maintenance: { active: true, message: 'Back at 18:00' } },
        { appVersion: '1.0.0', platform: 'ios' }
    );

    assert.equal(decision.state, LAUNCH_MAINTENANCE);
    assert.equal(decision.message, 'Back at 18:00');
});

test('an unreachable server is not a sign-out', () => {
    assert.equal(
        evaluate(null, { appVersion: '1.0.0', platform: 'ios' }).state,
        LAUNCH_UNREACHABLE
    );
});
