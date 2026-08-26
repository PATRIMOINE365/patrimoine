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
            /*
             * A ?theme= parameter (handed over by the marketing site)
             * becomes the personal preference, exactly as if the visitor
             * had picked it in the app.
             */
            const requestedTheme =
                new URLSearchParams(
                    window.location.search
                ).get('theme');

            if (
                requestedTheme === 'light'
                || requestedTheme === 'dark'
                || requestedTheme === 'system'
            ) {
                window.localStorage.setItem(
                    storageKey,
                    requestedTheme
                );
            }

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

        /*
         * Both hand-over parameters are consumed at this point (language
         * ran just above in its own bootstrap) — drop them from the
         * address bar, keeping every other parameter intact.
         */
        try {
            const url =
                new URL(window.location.href);

            if (
                url.searchParams.has('lang')
                || url.searchParams.has('theme')
            ) {
                url.searchParams.delete('lang');
                url.searchParams.delete('theme');

                window.history.replaceState(
                    null,
                    '',
                    url.toString()
                );
            }
        } catch (error) {
            /*
             * Cosmetic only — never block rendering.
             */
        }
    })();
</script>
