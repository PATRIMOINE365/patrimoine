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
        content="#26744b"
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
            background-color: #f5f7f6;
        }

        html[data-theme="dark"],
        html[data-theme="dark"] body {
            background-color: #0e1311;
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

        <section
            class="
                relative hidden overflow-hidden
                bg-patrimoine-950
                lg:flex lg:flex-col lg:justify-between
                p-12
            "
        >
            <div
                class="
                    absolute -right-32 -top-32
                    h-96 w-96 rounded-full
                    bg-patrimoine-800/50
                "
            ></div>

            <div
                class="
                    absolute -bottom-40 -left-32
                    h-[30rem] w-[30rem] rounded-full
                    bg-patrimoine-800/40
                "
            ></div>

            <div class="relative z-10">
                {{-- The brand is the way back to the marketing site --}}
                <a
                    href="https://patrimoine365.com/"
                    data-marketing-home
                    class="flex items-center gap-3"
                >
                    <img
                        src="/branding/patrimoine-logo.svg"
                        alt="Patrimoine 365"
                        class="h-11 w-11"
                    >

                    <span class="text-xl font-semibold text-white">
                        Patrimoine <span class="text-patrimoine-300">365</span>
                    </span>
                </a>
            </div>

            <div class="relative z-10 max-w-lg">
                <p
                    class="
                        mb-4 text-xs font-semibold uppercase
                        tracking-[0.24em] text-patrimoine-300
                    "
                >
                    <span data-i18n="login.hero_kicker">
                        {{ __('ui.login.hero_kicker') }}
                    </span>
                </p>

                <h1
                    class="
                        text-4xl font-semibold leading-tight
                        tracking-tight text-white
                    "
                >
                    <span data-i18n="login.hero_title">
                        {{ __('ui.login.hero_title') }}
                    </span>
                </h1>

                <p
                    class="
                        mt-6 max-w-md text-base leading-7
                        text-patrimoine-200
                    "
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
                    class="
                        mt-10 overflow-hidden rounded-2xl
                        border border-patrimoine-800
                        bg-patrimoine-900/50 p-1.5
                        shadow-2xl
                    "
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
                class="
                    relative z-10 text-sm
                    text-patrimoine-300
                "
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
                    <svg
                        data-theme-icon="moon"
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M20.5 14.1A8.5 8.5 0 0 1 9.9 3.5a8.5 8.5 0 1 0 10.6 10.6Z" />
                    </svg>

                    <svg
                        data-theme-icon="sun"
                        class="hidden h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                    </svg>
                </button>
            </div>

            <div class="w-full max-w-md">

                <a
                    href="https://patrimoine365.com/"
                    data-marketing-home
                    class="mb-12 flex items-center gap-3 lg:hidden"
                >
                    <img
                        src="/branding/patrimoine-logo.svg"
                        alt="Patrimoine 365"
                        class="h-10 w-10"
                    >

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
