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
--}}

<script>
    (() => {
        const storageKey =
            'patrimoine.presentation.language';

        let language =
            'en';

        try {
            const storedLanguage =
                window.localStorage.getItem(
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
    })();
</script>


