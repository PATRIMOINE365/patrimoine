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
 * On a phone the choice is the system's until there is a setting to
 * override it, so this follows the device and keeps following it: iOS can
 * change appearance while the application is open, on a schedule or by
 * hand, and a theme fixed at launch would be stale by evening.
 */

const query = () => window.matchMedia('(prefers-color-scheme: dark)');

function apply(dark) {
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');

    return dark;
}

export function startTheme() {
    const media = query();

    apply(media.matches);

    /*
     * addEventListener on a MediaQueryList is Safari 14+, so it is safe on
     * the 15.8 floor; the deprecated addListener is not needed.
     */
    media.addEventListener('change', (event) => apply(event.matches));

    return media.matches ? 'dark' : 'light';
}

export function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}
