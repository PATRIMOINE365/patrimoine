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

    <x-theme-bootstrap />

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="min-h-screen bg-stone-50 font-sans text-slate-900">

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
                <div class="flex items-center gap-3">
                    <div
                        class="
                            flex h-11 w-11 items-center justify-center
                            rounded-xl bg-white
                            font-semibold text-patrimoine-950
                        "
                    >
                        P
                    </div>

                    <span class="text-xl font-semibold text-white">
                        Patrimoine
                    </span>
                </div>
            </div>

            <div class="relative z-10 max-w-lg">
                <p
                    class="
                        mb-4 text-xs font-semibold uppercase
                        tracking-[0.24em] text-patrimoine-300
                    "
                >
                    <span data-i18n="product.property_management">
                        Property Management
                    </span>
                </p>

                <h1
                    class="
                        text-4xl font-semibold leading-tight
                        tracking-tight text-white
                    "
                >
                    <span data-i18n="login.hero_title">
                        Your property portfolio, finances and tenants in one place.
                    </span>
                </h1>

                <p
                    class="
                        mt-6 max-w-md text-base leading-7
                        text-patrimoine-200
                    "
                >
                    <span data-i18n="login.hero_description">
                        Manage buildings, leases, rent collections,
                        owner funds and financial reporting from a
                        single workspace.
                    </span>
                </p>
            </div>

            <div
                class="
                    relative z-10 text-sm
                    text-patrimoine-300
                "
            >
                <span data-i18n="login.product_name">
                    Patrimoine Property Management
                </span>
            </div>
        </section>

        <section
            class="
                flex min-h-screen items-center justify-center
                px-6 py-12 sm:px-10 lg:px-16
            "
        >
            <div class="w-full max-w-md">

                <div class="mb-12 flex items-center gap-3 lg:hidden">
                    <div
                        class="
                            flex h-10 w-10 items-center justify-center
                            rounded-xl bg-patrimoine-950
                            font-semibold text-white
                        "
                    >
                        P
                    </div>

                    <span class="text-xl font-semibold">
                        Patrimoine
                    </span>
                </div>

                @yield('content')

            </div>
        </section>

    </main>

</body>
</html>
