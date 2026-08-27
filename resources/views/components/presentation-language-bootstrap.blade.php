{{--
|--------------------------------------------------------------------------
| Patrimoine Presentation Language Bootstrap
|--------------------------------------------------------------------------
|
| Runs synchronously in <head>, before the application paints.
|
| The organisation language itself remains authoritative from
| /api/presentation-config. The browser stores only the last successfully
| confirmed language so a new document can avoid briefly painting the
| English Blade fallback while the presentation endpoint is loading.
|
| Public screens additionally honour a VISITOR language choice, so the
| marketing site can hand its language through (?lang=en|fr) and the
| sign-in / sign-up toggles can switch it. The visitor override applies
| only while unauthenticated; once signed in, the organisation language
| wins and the override is cleared (see core.js).
|
--}}

<script>
    (() => {
        const storageKey =
            'patrimoine.presentation.language';

        const overrideStorageKey =
            'patrimoine.presentation.language.override';

        let language =
            'en';

        try {
            /*
             * 1. An explicit ?lang= parameter (handed over by the
             *    marketing site) becomes the visitor override.
             */
            const requestedLanguage =
                new URLSearchParams(
                    window.location.search
                ).get('lang');

            if (
                requestedLanguage === 'en'
                || requestedLanguage === 'fr'
            ) {
                window.localStorage.setItem(
                    overrideStorageKey,
                    requestedLanguage
                );

                window.localStorage.setItem(
                    storageKey,
                    requestedLanguage
                );
            }

            /*
             * 2. A previously chosen visitor override wins over the
             *    cached organisation language on public screens.
             */
            const overrideLanguage =
                window.localStorage.getItem(
                    overrideStorageKey
                );

            const storedLanguage =
                overrideLanguage === 'en'
                || overrideLanguage === 'fr'
                    ? overrideLanguage
                    : window.localStorage.getItem(
                        storageKey
                    );

            if (
                storedLanguage === 'en'
                || storedLanguage === 'fr'
            ) {
                language =
                    storedLanguage;
            }
        } catch (error) {
            /*
             * Storage restrictions must never prevent rendering.
             */
        }

        document.documentElement.lang =
            language;

        document.documentElement.dataset.presentationLanguage =
            language;

        /*
         * Republish the resolved language to the server so the NEXT
         * document is rendered in it directly, instead of painting the
         * English Blade fallback and letting JavaScript translate it
         * afterwards. A ?lang= hand-over therefore takes effect
         * server-side from the following navigation onwards.
         */
        try {
            document.cookie =
                'patrimoine_language='
                + language
                + '; Path=/; Max-Age=31536000; SameSite=Lax'
                + (
                    window.location.protocol === 'https:'
                        ? '; Secure'
                        : ''
                );
        } catch (error) {
            /*
             * Cookie restrictions cost only the server-rendered hint.
             */
        }
    })();
</script>
