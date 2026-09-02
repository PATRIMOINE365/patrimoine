/*
 * Light or dark, decided the way the product decides it.
 *
 * The design system does NOT switch on `prefers-color-scheme`: tokens.css
 * defines light on `:root, html[data-theme="light"]` and dark on
 * `html[data-theme="dark"]`, because the web application lets a person
 * choose a theme rather than inheriting the operating system's. So the
 * attribute has to be set, and a client that only asks the media query
 * renders light on a dark handset - which is what happened here first.
 *
 * The choice is the web's three-way one - Light, Dark, System - kept on the
 * device. System follows iOS and keeps following it: appearance can change
 * while the application is open, on a schedule or by hand, and a theme
 * fixed at launch would be stale by evening.
 */

import { Capacitor } from '@capacitor/core';
import { StatusBar, Style } from '@capacitor/status-bar';
import { Preferences } from '@capacitor/preferences';

const KEY = 'ui.theme';
const query = () => window.matchMedia('(prefers-color-scheme: dark)');

let preference = 'system';

function apply(dark) {
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');

    /*
     * The WebView runs edge to edge (contentInset "never"), so the status
     * bar sits over our own header rather than over a system chrome. Its
     * glyphs therefore have to be told which way to go: Style.Dark means
     * light glyphs for a dark ground, Style.Light means dark glyphs for a
     * light one. Left alone, the clock disappears in one theme or the other.
     */
    if (Capacitor.isNativePlatform()) {
        StatusBar.setStyle({ style: dark ? Style.Dark : Style.Light })
            .catch(() => {
                /* Not fatal: a wrong-coloured clock is not worth a crash. */
            });
    }

    return dark;
}

function resolve() {
    return preference === 'dark' ? true : preference === 'light' ? false : query().matches;
}

export function startTheme() {
    const media = query();

    apply(resolve());

    /*
     * addEventListener on a MediaQueryList is Safari 14+, so it is safe on
     * the 15.8 floor; the deprecated addListener is not needed.
     */
    media.addEventListener('change', () => {
        if (preference === 'system') {
            apply(media.matches);
        }
    });

    /* The stored choice arrives a tick later; the first paint follows the system. */
    Preferences.get({ key: KEY }).then(({ value }) => {
        if (value === 'light' || value === 'dark' || value === 'system') {
            preference = value;
            apply(resolve());
        }
    }).catch(() => {});

    return resolve() ? 'dark' : 'light';
}

export function themePreference() {
    return preference;
}

export function setThemePreference(next) {
    preference = next === 'light' || next === 'dark' ? next : 'system';
    apply(resolve());
    Preferences.set({ key: KEY, value: preference }).catch(() => {});
    document.dispatchEvent(new CustomEvent('patrimoine:theme-changed', { detail: preference }));
}

export function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}
