/*
 * Sign-in: email, password, then the six-digit code. Both steps live here
 * because they are one journey and the challenge token only survives it.
 *
 * The application is sign-in only. "Create an organisation" and "Forgotten
 * your password" leave for the browser - never a WebView, never a form
 * here. features.signup_in_app in /config is the machine-readable form of
 * that decision and is read rather than assumed.
 */

import { el, mount, field, errorLine } from '../ui/dom.js';
import { t } from '../i18n/index.js';
import { login, verifyMfa, resendMfa, endpoints } from '../api/endpoints.js';
import { session } from '../auth/session.js';

function outboundLink(label, url) {
    if (url === undefined || url === null) {
        return null;
    }

    return el('button', {
        class: 'link',
        text: label,
        onclick: () => window.open(url, '_blank'),
    });
}

export function signIn(root, { client, config, deviceName, onSignedIn }) {
    const links = config?.links ?? {};

    function renderCredentials(prefill = {}, error = null) {
        const email = field({
            id: 'email',
            label: t('signin.email'),
            type: 'email',
            value: prefill.email ?? '',
            /* iOS shows the right keyboard and offers the saved address. */
            inputmode: 'email',
            autocomplete: 'username',
            autocapitalize: 'none',
            autocorrect: 'off',
        });

        const password = field({
            id: 'password',
            label: t('signin.password'),
            type: 'password',
            autocomplete: 'current-password',
        });

        const button = el('button', {
            class: 'button',
            type: 'submit',
            text: t('signin.action'),
        });

        const form = el('form', {
            class: 'form',
            onsubmit: async (event) => {
                event.preventDefault();

                button.disabled = true;

                try {
                    const started = await login(client, {
                        email: email.input.value.trim(),
                        password: password.input.value,
                        deviceName,
                    });

                    renderCode(started, { email: email.input.value.trim() });
                } catch (failure) {
                    button.disabled = false;

                    renderCredentials(
                        { email: email.input.value },
                        failure
                    );
                }
            },
        }, [
            el('h1', { class: 'screen-title', text: t('signin.title') }),
            error === null ? null : errorLine(error, t('signin.offline')),
            email.node,
            password.node,
            button,
            el('div', { class: 'links' }, [
                outboundLink(t('signin.forgot'), links.forgot_password),
                /*
                 * Only offered when the server says signup is a web journey
                 * and gives somewhere to send them.
                 */
                config?.features?.signup_in_app === false
                    ? outboundLink(t('signin.signup'), links.signup)
                    : null,
            ]),
        ]);

        mount(root, form);
        email.input.focus();
    }

    function renderCode(challenge, { email }, error = null, notice = null) {
        const code = field({
            id: 'code',
            label: t('mfa.code'),
            /*
             * type=text with a numeric inputmode, not type=number: a
             * six-digit code is a string of digits, and type=number strips
             * leading zeros and shows a spinner.
             */
            inputmode: 'numeric',
            autocomplete: 'one-time-code',
            maxlength: 6,
            pattern: '[0-9]*',
        });

        const button = el('button', {
            class: 'button',
            type: 'submit',
            text: t('mfa.action'),
        });

        const form = el('form', {
            class: 'form',
            onsubmit: async (event) => {
                event.preventDefault();

                button.disabled = true;

                try {
                    const issued = await verifyMfa(client, {
                        challengeToken: challenge.challenge_token,
                        code: code.input.value.trim(),
                    });

                    await session.start(issued.access_token);

                    /*
                     * The token is live from here, so /auth/me is the first
                     * authenticated call and confirms it end to end.
                     */
                    const me = await client.get(endpoints.auth.me);

                    await session.setUser(me?.user ?? me ?? null);

                    onSignedIn();
                } catch (failure) {
                    button.disabled = false;

                    renderCode(challenge, { email }, failure);
                }
            },
        }, [
            el('h1', { class: 'screen-title', text: t('mfa.title') }),
            el('p', {
                class: 'screen-body',
                /* The server masks the address; it is shown as received. */
                text: t('mfa.body', { email: challenge.email_hint ?? email }),
            }),
            notice === null ? null : el('p', { class: 'notice', text: notice }),
            error === null ? null : errorLine(error, t('signin.offline')),
            code.node,
            button,
            el('div', { class: 'links' }, [
                el('button', {
                    class: 'link',
                    type: 'button',
                    text: t('mfa.resend'),
                    onclick: async () => {
                        try {
                            await resendMfa(client, {
                                challengeToken: challenge.challenge_token,
                            });

                            renderCode(challenge, { email }, null, t('mfa.resent'));
                        } catch (failure) {
                            renderCode(challenge, { email }, failure);
                        }
                    },
                }),
                el('button', {
                    class: 'link',
                    type: 'button',
                    text: t('mfa.back'),
                    onclick: () => renderCredentials({ email }),
                }),
            ]),
        ]);

        mount(root, form);
        code.input.focus();
    }

    renderCredentials();
}
