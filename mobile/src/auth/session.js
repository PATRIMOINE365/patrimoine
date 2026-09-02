/*
 * Where the access token lives.
 *
 * ⚠️ THE STORAGE BACKEND IS NOT SETTLED. Komla listed "secure-storage choice"
 * as an open architecture decision and it has not been made, so this file
 * deliberately puts the decision behind one interface rather than scattering
 * a guess through the application.
 *
 * The default below is @capacitor/preferences, which on iOS is UserDefaults:
 * a plain plist inside the app sandbox. It is NOT encrypted at rest beyond
 * the device's own file protection, and it is included in unencrypted iTunes
 * backups. A mobile token has a 60-day idle window and a 180-day ceiling, so
 * this is a weak home for it — acceptable while the app only runs on our own
 * handsets, not acceptable for a release.
 *
 * Replacing it means implementing this same four-method shape against a
 * Keychain-backed plugin and changing the one line at the bottom. Nothing
 * else in the application touches storage.
 */

import { Preferences } from '@capacitor/preferences';

const TOKEN_KEY = 'auth.token';
const USER_KEY = 'auth.user';

class PreferencesBackend {
    async get(key) {
        const { value } = await Preferences.get({ key });

        return value ?? null;
    }

    async set(key, value) {
        await Preferences.set({ key, value });
    }

    async remove(key) {
        await Preferences.remove({ key });
    }
}

class Session {
    constructor(backend) {
        this.backend = backend;

        /*
         * Held in memory as well as in storage: the API client asks for the
         * token on every request and storage access is asynchronous, which
         * would otherwise make every call in the application await a plist
         * read it does not need.
         */
        this.accessToken = null;
        this.user = null;
    }

    /** Called once at launch, before the first screen is chosen. */
    async restore() {
        this.accessToken = await this.backend.get(TOKEN_KEY);

        const user = await this.backend.get(USER_KEY);

        this.user = user === null ? null : JSON.parse(user);

        return this.accessToken !== null;
    }

    token() {
        return this.accessToken;
    }

    isSignedIn() {
        return this.accessToken !== null;
    }

    async start(accessToken, user = null) {
        this.accessToken = accessToken;
        this.user = user;

        await this.backend.set(TOKEN_KEY, accessToken);

        if (user !== null) {
            await this.backend.set(USER_KEY, JSON.stringify(user));
        }
    }

    async setUser(user) {
        this.user = user;
        await this.backend.set(USER_KEY, JSON.stringify(user));
    }

    /*
     * Local only. Clearing the token here does not revoke it server-side —
     * POST /auth/logout does that, and it must be attempted first while the
     * token is still valid.
     */
    async clear() {
        this.accessToken = null;
        this.user = null;

        await this.backend.remove(TOKEN_KEY);
        await this.backend.remove(USER_KEY);
    }
}

/* The one line that changes when the storage decision is made. */
export const session = new Session(new PreferencesBackend());
export { Session, PreferencesBackend };
