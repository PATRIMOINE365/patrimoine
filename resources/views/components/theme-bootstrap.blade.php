{{--
|--------------------------------------------------------------------------
| Patrimoine Theme Bootstrap
|--------------------------------------------------------------------------
|
| Runs synchronously in <head>, before the application paints.
|
| Theme preference is personal browser presentation state rather than an
| organisation-wide setting. Supported preferences:
|
| - light
| - dark
| - system
|
| If no preference has been saved, Patrimoine follows the operating system.
|
--}}

<script>
    (() => {
        const storageKey = 'patrimoine.theme';

        let preference = 'system';

        try {
            const storedPreference =
                window.localStorage.getItem(
                    storageKey
                );

            if (
                storedPreference === 'light'
                || storedPreference === 'dark'
                || storedPreference === 'system'
            ) {
                preference =
                    storedPreference;
            }
        } catch (error) {
            /*
             * Browser privacy/storage restrictions must never prevent
             * Patrimoine from rendering.
             */
        }

        const resolvedTheme =
            preference === 'system'
                ? (
                    window.matchMedia(
                        '(prefers-color-scheme: dark)'
                    ).matches
                        ? 'dark'
                        : 'light'
                )
                : preference;

        document.documentElement.dataset.theme =
            resolvedTheme;

        document.documentElement.dataset.themePreference =
            preference;
    })();
</script>
