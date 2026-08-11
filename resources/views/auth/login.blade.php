<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sign in — Patrimoine</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="min-h-screen bg-stone-50 font-sans text-slate-900">

    <main class="grid min-h-screen lg:grid-cols-2">

        {{-- Branding panel --}}
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
                    Property Management
                </p>

                <h1
                    class="
                        text-4xl font-semibold leading-tight
                        tracking-tight text-white
                    "
                >
                    Your property portfolio,
                    finances and tenants in one place.
                </h1>

                <p
                    class="
                        mt-6 max-w-md text-base leading-7
                        text-patrimoine-200
                    "
                >
                    Manage buildings, leases, rent collections,
                    owner funds and financial reporting from a
                    single workspace.
                </p>
            </div>

            <div
                class="
                    relative z-10 text-sm
                    text-patrimoine-300
                "
            >
                Patrimoine Property Management
            </div>
        </section>

        {{-- Login panel --}}
        <section
            class="
                flex min-h-screen items-center justify-center
                px-6 py-12 sm:px-10 lg:px-16
            "
        >
            <div class="w-full max-w-md">

                {{-- Mobile branding --}}
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

                <div class="mb-9">
                    <h2
                        class="
                            text-3xl font-semibold tracking-tight
                            text-slate-950
                        "
                    >
                        Welcome back
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Sign in to access the property management workspace.
                    </p>
                </div>

                <div
                    id="login-error"
                    class="
                        mb-5 hidden rounded-lg
                        border border-red-200 bg-red-50
                        px-4 py-3 text-sm text-red-700
                    "
                ></div>

                <form
                    id="login-form"
                    class="space-y-5"
                >
                    <div>
                        <label
                            for="email"
                            class="
                                mb-2 block text-sm font-medium
                                text-slate-700
                            "
                        >
                            Email address
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            autofocus
                            class="
                                block w-full rounded-lg
                                border border-slate-300
                                bg-white px-3.5 py-3
                                text-sm text-slate-900
                                shadow-sm outline-none
                                transition
                                placeholder:text-slate-400
                                focus:border-patrimoine-600
                                focus:ring-3
                                focus:ring-patrimoine-600/10
                            "
                            placeholder="name@example.com"
                        >
                    </div>

                    <div>
                        <div
                            class="
                                mb-2 flex items-center justify-between
                            "
                        >
                            <label
                                for="password"
                                class="
                                    block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Password
                            </label>
                        </div>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="
                                block w-full rounded-lg
                                border border-slate-300
                                bg-white px-3.5 py-3
                                text-sm text-slate-900
                                shadow-sm outline-none
                                transition
                                placeholder:text-slate-400
                                focus:border-patrimoine-600
                                focus:ring-3
                                focus:ring-patrimoine-600/10
                            "
                            placeholder="Enter your password"
                        >
                    </div>

                    <button
                        id="login-button"
                        type="submit"
                        class="
                            flex w-full items-center justify-center
                            rounded-lg bg-patrimoine-950
                            px-4 py-3
                            text-sm font-semibold text-white
                            shadow-sm transition
                            hover:bg-patrimoine-800
                            focus:outline-none
                            focus:ring-4
                            focus:ring-patrimoine-700/20
                            disabled:cursor-not-allowed
                            disabled:opacity-60
                        "
                    >
                        Sign in
                    </button>
                </form>

                <p
                    class="
                        mt-10 text-center text-xs
                        leading-5 text-slate-400
                    "
                >
                    Secure access to Patrimoine Property Management.
                </p>
            </div>
        </section>

    </main>

</body>
</html>
