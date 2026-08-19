/*
|--------------------------------------------------------------------------
| Patrimoine Theme Presentation
|--------------------------------------------------------------------------
|
| Theme is a personal browser preference and deliberately remains separate
| from organisation-wide language and currency configuration.
|
| The synchronous Blade bootstrap applies the initial theme before first
| paint. This module owns runtime changes and System-theme observation.
|
*/

const THEME_STORAGE_KEY =
    'patrimoine.theme';

const SUPPORTED_THEME_PREFERENCES =
    new Set([
        'light',
        'dark',
        'system',
    ]);

const systemDarkMode =
    window.matchMedia(
        '(prefers-color-scheme: dark)'
    );

/**
 * Return the user's stored theme preference.
 *
 * @returns {'light'|'dark'|'system'}
 */
export function getThemePreference() {
    try {
        const stored =
            window.localStorage.getItem(
                THEME_STORAGE_KEY
            );

        if (
            SUPPORTED_THEME_PREFERENCES.has(
                stored
            )
        ) {
            return stored;
        }
    } catch (error) {
        /*
         * Storage restrictions should degrade safely to System theme.
         */
    }

    return 'system';
}

/**
 * Resolve Light/Dark from the selected preference.
 *
 * @param {'light'|'dark'|'system'} preference
 * @returns {'light'|'dark'}
 */
export function resolveTheme(
    preference
) {
    if (preference === 'dark') {
        return 'dark';
    }

    if (preference === 'light') {
        return 'light';
    }

    return systemDarkMode.matches
        ? 'dark'
        : 'light';
}

/**
 * Apply one theme preference to the current document.
 *
 * @param {'light'|'dark'|'system'} preference
 */
export function applyThemePreference(
    preference
) {
    const normalizedPreference =
        SUPPORTED_THEME_PREFERENCES.has(
            preference
        )
            ? preference
            : 'system';

    document.documentElement.dataset
        .themePreference =
        normalizedPreference;

    document.documentElement.dataset.theme =
        resolveTheme(
            normalizedPreference
        );
}

/**
 * Persist and apply a user-selected theme preference.
 *
 * @param {'light'|'dark'|'system'} preference
 */
export function setThemePreference(
    preference
) {
    if (
        ! SUPPORTED_THEME_PREFERENCES.has(
            preference
        )
    ) {
        throw new Error(
            `Unsupported Patrimoine theme: ${preference}`
        );
    }

    try {
        window.localStorage.setItem(
            THEME_STORAGE_KEY,
            preference
        );
    } catch (error) {
        /*
         * The current page can still use the selected theme even when
         * browser storage is unavailable.
         */
    }

    applyThemePreference(
        preference
    );

    document.dispatchEvent(
        new CustomEvent(
            'patrimoine:theme-changed',
            {
                detail: {
                    preference,
                    theme:
                        resolveTheme(
                            preference
                        ),
                },
            }
        )
    );
}

/**
 * Initialize runtime theme behaviour.
 */
export function initializeTheme() {
    applyThemePreference(
        getThemePreference()
    );

    systemDarkMode.addEventListener(
        'change',
        () => {
            if (
                getThemePreference()
                !== 'system'
            ) {
                return;
            }

            applyThemePreference(
                'system'
            );
        }
    );
}
