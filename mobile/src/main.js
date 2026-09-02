/*
 * Launch order, and the reason for it.
 *
 *   1. Work out the language before anything is rendered, so the first
 *      screen is not English-then-French.
 *   2. Restore any stored token.
 *   3. Call GET /config - public, no token - and obey it: below the floor
 *      the application does not open, and in maintenance it does not open.
 *   4. Only then choose between sign-in and the signed-in shell.
 *
 * Step 3 runs before step 4 on purpose. A forced update that only fired
 * after sign-in would be a forced update nobody locked out could reach.
 */

import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';

import { ApiClient } from './api/client.js';
import { session } from './auth/session.js';
import { fetchConfig, evaluate, LAUNCH_OK, LAUNCH_UPDATE_REQUIRED, LAUNCH_MAINTENANCE } from './boot/config.js';
import { setLanguage, preferredLanguage, t } from './i18n/index.js';
import { el, mount } from './ui/dom.js';
import { signIn } from './screens/signin.js';
import { appShell } from './screens/app-shell.js';
import { updateRequired, maintenance, unreachable } from './screens/blocked.js';
import './styles.css';

/*
 * Set at build time. There is no default: a build that does not know which
 * server it talks to should fail here, loudly, rather than silently point
 * at the wrong one.
 */
const API_BASE = import.meta.env.VITE_API_BASE;
const APP_VERSION = import.meta.env.VITE_APP_VERSION ?? '0.1.0';

const root = document.querySelector('#app');

async function boot() {
    mount(root, el('p', { class: 'muted centred', text: t('launch.checking') }));

    if (API_BASE === undefined || API_BASE === '') {
        mount(root, el('p', {
            class: 'error',
            text: 'VITE_API_BASE is not set. See mobile/README.md.',
        }));

        return;
    }

    const info = await Device.getInfo().catch(() => ({ platform: 'web', model: null }));
    const deviceLanguages = await Device.getLanguageTag()
        .then((tag) => [tag.value])
        .catch(() => navigator.languages ?? ['en']);

    const language = setLanguage(preferredLanguage(deviceLanguages));

    const platform = Capacitor.getPlatform();

    const client = new ApiClient({
        baseUrl: API_BASE,
        appVersion: APP_VERSION,
        /* ios | android | web - recorded on the device row at mint time. */
        platform,
        language,
        tokenProvider: () => session.token(),
    });

    /* Any 401, anywhere, returns to sign-in. There is no refresh flow. */
    client.onUnauthenticated = async () => {
        await session.clear();
        showSignIn(null);
    };

    await session.restore();

    const config = await fetchConfig(client);
    const decision = evaluate(config, { appVersion: APP_VERSION, platform });

    if (decision.state === LAUNCH_UPDATE_REQUIRED) {
        updateRequired(root, decision);

        return;
    }

    if (decision.state === LAUNCH_MAINTENANCE) {
        maintenance(root, decision);

        return;
    }

    if (decision.state !== LAUNCH_OK) {
        unreachable(root, { onRetry: boot });

        return;
    }

    /*
     * The name shown in Settings -> Devices. The handset knows which
     * handset it is; the server, left to guess from a user-agent, would
     * call every one of them "Safari on iOS".
     */
    const deviceName = [info.name, info.model].filter(Boolean).join(' ')
        || `Patrimoine on ${platform}`;

    function showSignIn(reason) {
        signIn(root, {
            client,
            config: decision.config,
            deviceName,
            onSignedIn: showApp,
            reason,
        });
    }

    function showApp() {
        appShell(root, { client, onSignedOut: () => showSignIn(null) });
    }

    if (session.isSignedIn()) {
        showApp();
    } else {
        showSignIn(null);
    }
}

boot();
