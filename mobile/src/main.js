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

import { App } from '@capacitor/app';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';

import { ApiClient } from './api/client.js';
import { session } from './auth/session.js';
import * as store from './data/store.js';
import { setCurrency } from './ui/money.js';
import { fetchConfig, evaluate, LAUNCH_OK, LAUNCH_UPDATE_REQUIRED, LAUNCH_MAINTENANCE } from './boot/config.js';
import { setLanguage, preferredLanguage, t } from './i18n/index.js';
import { el, mount } from './ui/dom.js';
import { startTheme } from './ui/theme.js';
import { showSplash, hideSplash } from './ui/splash.js';
import { signIn } from './screens/signin.js';
import { appShell } from './screens/app-shell.js';
import { tabletShell } from './screens/tablet-shell.js';
import { isTablet } from './ui/device.js';
import { updateRequired, maintenance, unreachable } from './screens/blocked.js';
import './styles.css';
import './styles-full.css';

/*
 * Set at build time. There is no default: a build that does not know which
 * server it talks to should fail here, loudly, rather than silently point
 * at the wrong one.
 */
const API_BASE = import.meta.env.VITE_API_BASE;

/*
 * The version the forced-update floor is compared against MUST be the one
 * the store and the device agree on, so on a device it is read from the
 * bundle (CFBundleShortVersionString) rather than from a build-time
 * constant. Two numbers that can drift apart would mean a raised floor
 * either locking out current builds or letting stale ones through - and
 * that is discovered only after the floor is raised, when it is too late.
 *
 * The build-time value is the fallback for `npm run dev` in a browser,
 * where there is no bundle to read.
 */
async function appVersion() {
    if (! Capacitor.isNativePlatform()) {
        return import.meta.env.VITE_APP_VERSION ?? '0.0.0';
    }

    try {
        const info = await App.getInfo();

        return info.version;
    } catch {
        return import.meta.env.VITE_APP_VERSION ?? '0.0.0';
    }
}

const root = document.querySelector('#app');

async function boot() {
    /* Before the first paint, so nothing flashes the wrong theme. */
    startTheme();

    /*
     * The launch screen stays up until there is a finished screen beneath
     * it. Nothing half-built is ever shown - no spinner, no heading with
     * rows arriving underneath.
     */
    showSplash();

    try {
        await launch();
    } finally {
        /*
         * In a finally: a launch that fails must reveal the screen saying
         * what went wrong, not leave somebody looking at a logo for ever.
         */
        await hideSplash();
    }
}

async function launch() {

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
    const version = await appVersion();

    const client = new ApiClient({
        baseUrl: API_BASE,
        appVersion: version,
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
    const decision = evaluate(config, { appVersion: version, platform });

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
     *
     * iOS reports the name the owner gave the device, which is very often
     * just "iPhone" - and a Devices list of three identical "iPhone" rows
     * helps nobody decide which one to revoke. So the model identifier is
     * appended only when the name carries nothing of its own. Joining both
     * unconditionally produced "iPhone iPhone11,2".
     */
    const GENERIC = ['iphone', 'ipad', 'ipod touch', 'android', 'phone'];

    const named = (info.name ?? '').trim();
    const model = (info.model ?? '').trim();

    const deviceName = (
        named === ''
            ? model
            : (GENERIC.includes(named.toLowerCase()) && model !== '' && model !== named
                ? `${named} (${model})`
                : named)
    ) || `Patrimoine on ${platform}`;

    function showSignIn(reason) {
        signIn(root, {
            client,
            config: decision.config,
            deviceName,
            onSignedIn: showApp,
            reason,
        });
    }

    /*
     * The organisation's working set is fetched ONCE here, before the shell
     * is drawn, so that moving between tabs afterwards costs nothing. This
     * is the only place a person waits for a list.
     */
    async function showApp() {
        /*
         * No loading line: the splash is still covering the screen, which is
         * the whole point. This await is what it is waiting for.
         */
        await store.prime(client);

        /*
         * Before the first figure is drawn. The currency is a setting on the
         * organisation, not a constant, and a number rendered with the wrong
         * symbol is worse than one rendered with none.
         */
        const organisation = store.read('organisation').data;

        setCurrency((organisation?.data ?? organisation ?? {}).currency);

        /*
         * Two different clients, not one layout at two widths: the tablet is
         * the full product, the phone stays a field tool.
         */
        const shell = isTablet() ? tabletShell : appShell;

        shell(root, { client, config: decision.config, version, apiBase: API_BASE, onSignedOut: () => showSignIn(null) });
    }

    /*
     * AWAITED. showApp fetches the working set, and the splash is lifted in
     * boot()'s finally - so without the await the launch screen would come
     * down before the data it is waiting for had arrived, which is the one
     * thing it exists to prevent.
     */
    if (session.isSignedIn()) {
        await showApp();
    } else {
        showSignIn(null);
    }
}

boot();
