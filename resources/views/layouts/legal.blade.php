<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>@yield('page_title') — {{ config('legal.product.name') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/branding/favicon/favicon-16.png">

    <x-theme-bootstrap />

    <style>
        html, body { background-color: #f5f7f6; }
        html[data-theme="dark"],
        html[data-theme="dark"] body { background-color: #0e1311; }
    </style>

    @vite([
        'resources/css/app.css',
    ])
</head>

<body class="min-h-screen bg-[var(--pm-page)] font-sans text-[var(--pm-text)]">

    <header class="border-b border-[var(--pm-border)] bg-[var(--pm-surface)]">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-5">
            <a href="/login" class="flex items-center gap-3">
                <span
                    class="
                        flex h-9 w-9 items-center justify-center
                        rounded-xl bg-patrimoine-950
                        font-semibold text-white
                    "
                >P</span>
                <span class="text-lg font-semibold">
                    {{ config('legal.product.name') }}
                </span>
            </a>

            <button
                type="button"
                id="legal-language-toggle"
                class="
                    rounded-lg border border-[var(--pm-border)]
                    px-3 py-1.5 text-sm font-medium
                "
            >FR</button>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-10">
        <article
            data-legal-language="en"
            class="pm-legal-document"
        >
            @yield('content_en')
        </article>

        <article
            data-legal-language="fr"
            class="pm-legal-document hidden"
        >
            @yield('content_fr')
        </article>
    </main>

    <footer class="border-t border-[var(--pm-border)]">
        <div class="mx-auto max-w-3xl px-6 py-8 text-sm text-[var(--pm-text-muted,#6d7d75)]">
            <p>
                {{ config('legal.product.name') }} ·
                {{ config('legal.company.name') }},
                {{ config('legal.company.address') }} ·
                <a class="underline" href="mailto:{{ config('legal.mailboxes.support') }}">{{ config('legal.mailboxes.support') }}</a>
            </p>
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('legal-language-toggle');
            var current = 'en';

            try {
                var stored = window.localStorage.getItem('patrimoine.language');
                if (stored === 'fr' || stored === 'en') {
                    current = stored;
                }
            } catch (e) { /* storage restrictions never break the page */ }

            function apply() {
                document.querySelectorAll('[data-legal-language]').forEach(function (el) {
                    el.classList.toggle('hidden', el.dataset.legalLanguage !== current);
                });
                document.documentElement.lang = current;
                toggle.textContent = current === 'en' ? 'FR' : 'EN';
            }

            toggle.addEventListener('click', function () {
                current = current === 'en' ? 'fr' : 'en';
                try {
                    window.localStorage.setItem('patrimoine.language', current);
                } catch (e) { /* ignore */ }
                apply();
            });

            apply();
        })();
    </script>

</body>
</html>
