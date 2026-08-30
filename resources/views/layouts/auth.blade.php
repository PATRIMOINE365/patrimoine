<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title data-i18n-title="@yield('title_key')">
        @yield('title_fallback')
    </title>

    <link
        rel="icon"
        sizes="48x48"
        href="/favicon.ico"
    >
    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="/branding/favicon/favicon-32.png"
    >
    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="/branding/favicon/favicon-16.png"
    >
    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="/branding/favicon/apple-touch-icon.png"
    >

    <link
        rel="manifest"
        href="/branding/site.webmanifest"
    >

    <meta
        name="theme-color"
        content="#123d35"
    >

    <x-presentation-language-bootstrap />

    <x-theme-bootstrap />

    {{--
        V1.0.4 Initial Paint Theme

        Keep authentication on the same semantic page canvas as the
        authenticated application and avoid a white flash before Vite
        finishes loading.
    --}}
    <style>
        html,
        body {
            background-color: #f2f6f4;
        }

        html[data-theme="dark"],
        html[data-theme="dark"] body {
            background-color: #0e1614;
        }
    </style>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body
    class="
        min-h-screen bg-[var(--pm-page)]
        font-sans text-[var(--pm-text)]
    "
>

    <main class="grid min-h-screen lg:grid-cols-2">

        {{--
            The hero is a Patrimoine Green band — the one place in the
            product where the brand is the subject rather than the frame.
            Everything inside it reads --pm-band-*, so nothing here has to
            know it is standing on a dark ground, and dark mode needs no
            second copy of any of it.
        --}}
        <section
            class="
                pm-auth-hero
                relative hidden overflow-hidden
                lg:flex lg:flex-col lg:justify-between
                p-12
            "
        >
            <div
                class="
                    pm-auth-hero-glow
                    absolute -right-32 -top-32
                    h-96 w-96 rounded-full
                "
            ></div>

            <div
                class="
                    pm-auth-hero-glow pm-auth-hero-glow-soft
                    absolute -bottom-40 -left-32
                    h-[30rem] w-[30rem] rounded-full
                "
            ></div>

            <div class="relative z-10">
                {{-- The brand is the way back to the marketing site --}}
                <a
                    href="https://patrimoine365.com/"
                    data-marketing-home
                    class="flex items-center gap-3"
                >
                    <x-logo :size="44" />

                    <span class="pm-auth-hero-wordmark">
                        Patrimoine <span class="pm-auth-hero-365">365</span>
                    </span>
                </a>
            </div>

            <div class="relative z-10 max-w-lg">
                <p
                    class="pm-auth-hero-kicker"
                >
                    <span data-i18n="login.hero_kicker">
                        {{ __('ui.login.hero_kicker') }}
                    </span>
                </p>

                <h1
                    class="pm-auth-hero-title"
                >
                    <span data-i18n="login.hero_title">
                        {{ __('ui.login.hero_title') }}
                    </span>
                </h1>

                <p
                    class="pm-auth-hero-description mt-6 max-w-md"
                >
                    <span data-i18n="login.hero_description">
                        {{ __('ui.login.hero_description') }}
                    </span>
                </p>

                {{--
                    Live product preview. public-presentation.js swaps the
                    edition when the visitor changes language.
                --}}
                <div
                    class="pm-auth-hero-frame mt-10"
                >
                    {{--
                        Server-rendered default matches the default paint
                        (English, light page → dark preview); the module
                        corrects it for the visitor's language and theme.
                    --}}
                    <img
                        src="/branding/auth-preview-en-dark.png"
                        data-auth-preview
                        alt="{{ __('ui.login.hero_image_label') }}"
                        class="w-full rounded-xl"
                        width="2400"
                        height="1500"
                    >
                </div>
            </div>

            <div
                class="pm-auth-hero-footnote relative z-10"
            >
                <span data-i18n="login.product_name">
                    {{ __('ui.login.product_name') }}
                </span>
            </div>
        </section>

        <section
            class="
                relative flex min-h-screen items-center justify-center
                bg-[var(--pm-page)]
                px-6 py-12 sm:px-10 lg:px-16
            "
        >
            {{--
                V1.0.12 — personal presentation controls at the door.
                Theme follows patrimoine.theme (the whole app honours it);
                language is the public-screen visitor override.
            --}}
            <div class="absolute right-6 top-6 flex items-center gap-2">
                <button
                    type="button"
                    id="auth-language-toggle"
                    class="
                        flex h-10 min-w-10 items-center justify-center
                        rounded-lg border border-[var(--pm-border)]
                        bg-[var(--pm-surface)] px-2.5
                        text-sm font-semibold text-[var(--pm-text-muted)]
                        hover:border-[var(--pm-border-strong)]
                        hover:text-[var(--pm-text)]
                    "
                >
                    FR
                </button>

                <button
                    type="button"
                    id="auth-theme-toggle"
                    class="
                        flex h-10 w-10 items-center justify-center
                        rounded-lg border border-[var(--pm-border)]
                        bg-[var(--pm-surface)]
                        text-[var(--pm-text-muted)]
                        hover:border-[var(--pm-border-strong)]
                        hover:text-[var(--pm-text)]
                    "
                >
                    <x-icon name="moon" data-theme-icon="moon" />

                    <x-icon name="sun" data-theme-icon="sun" class="hidden" />
                </button>
            </div>

            <div class="w-full max-w-md">

                <a
                    href="https://patrimoine365.com/"
                    data-marketing-home
                    class="mb-12 flex items-center gap-3 lg:hidden"
                >
                    <x-logo :size="40" />

                    <span class="text-xl font-semibold text-[var(--pm-text)]">
                        Patrimoine <span class="text-[var(--pm-accent)]">365</span>
                    </span>
                </a>

                @yield('content')

            </div>
        </section>

    </main>

</body>
</html>
