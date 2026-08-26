/*
|--------------------------------------------------------------------------
| Public Screen Presentation Controls (V1.0.12)
|--------------------------------------------------------------------------
|
| Sign in, sign up and the password-ownership screens carry a theme toggle
| and a language toggle so a visitor's choice on the marketing site — or
| right here at the door — follows them through. The controls live in the
| auth layout only; on every other page this module finds nothing and
| does nothing.
|
| Theme is the personal patrimoine.theme preference the whole application
| already honours. Language is the public-screen visitor override from
| core.js — retired automatically once an organisation's own language
| becomes authoritative after sign-in.
|
*/

import {
    applyTranslations,
    getPresentationConfiguration,
    setPublicLanguageOverride,
    translate,
} from './core.js';

import {
    resolveTheme,
    getThemePreference,
    setThemePreference,
} from './theme.js';

/**
 * Wire the auth-layout theme and language toggles, when present.
 */
export function initializePublicPresentationControls() {
    initializeThemeToggle();
    initializeLanguageToggle();
}

function initializeThemeToggle() {
    const toggle =
        document.getElementById(
            'auth-theme-toggle'
        );

    if (! toggle) {
        return;
    }

    const reflect = () => {
        const dark =
            document.documentElement.dataset.theme
            === 'dark';

        toggle
            .querySelector('[data-theme-icon="moon"]')
            ?.classList.toggle('hidden', dark);

        toggle
            .querySelector('[data-theme-icon="sun"]')
            ?.classList.toggle('hidden', ! dark);

        toggle.setAttribute(
            'aria-label',
            translate(
                dark
                    ? 'login.switch_to_light'
                    : 'login.switch_to_dark'
            )
        );
    };

    toggle.addEventListener(
        'click',
        () => {
            const dark =
                resolveTheme(
                    getThemePreference()
                ) === 'dark';

            setThemePreference(
                dark
                    ? 'light'
                    : 'dark'
            );

            reflect();
        }
    );

    document.addEventListener(
        'patrimoine:theme-changed',
        reflect
    );

    reflect();
}

function initializeLanguageToggle() {
    const toggle =
        document.getElementById(
            'auth-language-toggle'
        );

    if (! toggle) {
        return;
    }

    const reflect = () => {
        const language =
            getPresentationConfiguration()
                .language
            || 'en';

        /*
         * The button advertises the language you would switch TO,
         * matching the marketing site's toggle.
         */
        toggle.textContent =
            language === 'fr'
                ? 'EN'
                : 'FR';

        toggle.setAttribute(
            'aria-label',
            translate('login.switch_language')
        );

        /*
         * The brand links back to the marketing site — keep it pointing
         * at the matching language edition.
         */
        document
            .querySelectorAll('[data-marketing-home]')
            .forEach((link) => {
                link.href =
                    language === 'fr'
                        ? 'https://patrimoine365.com/fr/'
                        : 'https://patrimoine365.com/';
            });

        /*
         * The panel's product preview has an edition per language.
         */
        document
            .querySelectorAll('[data-auth-preview]')
            .forEach((image) => {
                image.src =
                    language === 'fr'
                        ? '/branding/auth-preview-fr.png'
                        : '/branding/auth-preview-en.png';
            });
    };

    toggle.addEventListener(
        'click',
        () => {
            const current =
                getPresentationConfiguration()
                    .language
                || 'en';

            setPublicLanguageOverride(
                current === 'fr'
                    ? 'en'
                    : 'fr'
            );

            applyTranslations();

            reflect();
        }
    );

    reflect();
}
