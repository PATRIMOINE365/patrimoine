/*
 * The one door to the API.
 *
 * Nothing else in the application may call fetch(). Three of the rules
 * below cannot be enforced screen by screen and are the reason this file
 * exists at all.
 */

import { ApiError } from './errors.js';

/*
 * RULE 1 — EVERY POST/PUT/PATCH/DELETE CARRIES A JSON BODY, EVEN AN EMPTY ONE.
 *
 * Measured on the live API 2026-09-01: POST /api/v1/auth/logout with no body
 * answers 403 from the production host's WAF, while the same call with `{}`
 * answers correctly. Pre-prod sits behind Traefik and has no such filter, so
 * a client that sends bodyless POSTs — logout, mfa/resend, device revoke, and
 * the default of most HTTP libraries — works through the whole of development
 * and fails only once installed on a real phone against production.
 *
 * Enforced here, once, and covered by tests/api-client.test.js.
 */
const BODYLESS_METHODS = new Set(['GET', 'HEAD']);

export class ApiClient {
    /*
     * The token arrives through `tokenProvider` rather than by importing
     * the session module, so this file never reaches storage and can be
     * exercised off-device by tests that have no Capacitor runtime.
     */
    constructor({ baseUrl, appVersion, platform, language = 'en', tokenProvider = () => null }) {
        this.baseUrl = baseUrl.replace(/\/+$/, '');
        this.appVersion = appVersion;
        this.platform = platform;
        this.language = language;
        this.tokenProvider = tokenProvider;
        this.onUnauthenticated = null;
    }

    setLanguage(language) {
        this.language = language;
    }

    /*
     * RULE 2 — the client declares itself on every request.
     *
     * A token's name is fixed at mint time and can never be recovered, so
     * the lifetime policy (mobile: 60-day idle, 180-day ceiling) is chosen
     * from X-Patrimoine-Client at that moment. Sending these only on
     * sign-in would leave every later request guessed from the user-agent.
     */
    headers(extra = {}) {
        const headers = {
            Accept: 'application/json',
            'X-Patrimoine-Client': 'mobile',
            'X-Patrimoine-Platform': this.platform,
            'X-App-Version': this.appVersion,
            /*
             * RULE 3 — state the language the reply will be rendered in.
             * Error codes are recovered by matching the rendered sentence
             * against a per-language catalogue, so a reply written in a
             * language the screen is not in cannot be matched back.
             *
             * The organisation's own setting still outranks this server-side
             * unless CLIENT_LANGUAGE_OVERRIDES_ORGANISATION is on. That is
             * deliberate: an English handset inside a French organisation
             * reads French.
             */
            'X-Patrimoine-Language': this.language,
            ...extra,
        };

        const token = this.tokenProvider();

        if (token !== null && token !== undefined) {
            headers.Authorization = `Bearer ${token}`;
        }

        return headers;
    }

    async request(method, path, body = undefined, options = {}) {
        const verb = method.toUpperCase();
        const url = `${this.baseUrl}${path.startsWith('/') ? path : `/${path}`}`;

        const init = {
            method: verb,
            headers: this.headers(options.headers),
        };

        if (! BODYLESS_METHODS.has(verb)) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(body ?? {});
        }

        let response;

        try {
            response = await fetch(url, init);
        } catch (cause) {
            /*
             * A handset loses signal mid-request far more often than a
             * browser does. This is the normal case, not an exception.
             */
            throw ApiError.unreachable(cause);
        }

        if (response.status === 204) {
            return null;
        }

        const payload = await this.decode(response);

        if (response.ok) {
            return payload;
        }

        /*
         * An expired or revoked token is refused at the same door as any
         * other invalid one: 401. There is no refresh flow by design — the
         * client returns to sign-in.
         */
        if (response.status === 401 && this.onUnauthenticated !== null) {
            this.onUnauthenticated();
        }

        throw ApiError.fromResponse(response, payload);
    }

    async decode(response) {
        const type = response.headers.get('content-type') ?? '';

        if (! type.includes('json')) {
            return null;
        }

        try {
            return await response.json();
        } catch {
            return null;
        }
    }

    get(path, options) {
        return this.request('GET', path, undefined, options);
    }

    /**
     * A multipart upload: the profile photograph, a registry backup. The
     * browser sets the boundary itself, so no Content-Type is written; the
     * JSON-body rule does not apply because the body is never empty.
     */
    async upload(path, formData) {
        const url = `${this.baseUrl}${path.startsWith('/') ? path : `/${path}`}`;
        let response;

        try {
            response = await fetch(url, { method: 'POST', headers: this.headers(), body: formData });
        } catch (cause) {
            throw ApiError.unreachable(cause);
        }

        if (response.status === 204) {
            return null;
        }

        const payload = await this.decode(response);

        if (response.ok) {
            return payload;
        }

        if (response.status === 401 && this.onUnauthenticated !== null) {
            this.onUnauthenticated();
        }

        throw ApiError.fromResponse(response, payload);
    }

    /**
     * Bytes, for the downloads the browser application saves as files: a
     * CSV, a workbook, a JSON copy of somebody's data. Returns the blob and
     * the filename the server proposed.
     */
    async download(path) {
        const url = `${this.baseUrl}${path.startsWith('/') ? path : `/${path}`}`;
        let response;

        try {
            response = await fetch(url, { method: 'GET', headers: this.headers({ Accept: '*/*' }) });
        } catch (cause) {
            throw ApiError.unreachable(cause);
        }

        if (! response.ok) {
            if (response.status === 401 && this.onUnauthenticated !== null) {
                this.onUnauthenticated();
            }

            throw ApiError.fromResponse(response, await this.decode(response));
        }

        const disposition = response.headers.get('content-disposition') ?? '';
        const utf8 = /filename\*=UTF-8''([^;]+)/i.exec(disposition);
        const plain = /filename="?([^";]+)"?/i.exec(disposition);
        const filename = utf8 ? decodeURIComponent(utf8[1]) : (plain ? plain[1] : null);

        return { blob: await response.blob(), filename };
    }

    post(path, body, options) {
        return this.request('POST', path, body, options);
    }

    patch(path, body, options) {
        return this.request('PATCH', path, body, options);
    }

    put(path, body, options) {
        return this.request('PUT', path, body, options);
    }

    delete(path, body, options) {
        return this.request('DELETE', path, body, options);
    }
}
