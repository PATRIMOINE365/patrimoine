<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Initial Setup — Patrimoine</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="min-h-screen bg-stone-50 font-sans text-slate-900">

    <main class="min-h-screen px-6 py-10 sm:px-10 lg:px-16">

        <div class="mx-auto max-w-4xl">

            <header class="mb-8">
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="
                            flex h-11 w-11 items-center justify-center
                            rounded-xl bg-patrimoine-950
                            font-semibold text-white
                        "
                    >
                        P
                    </div>

                    <div>
                        <div class="text-xl font-semibold">
                            Patrimoine
                        </div>

                        <div class="text-sm text-slate-500">
                            Property Management
                        </div>
                    </div>
                </div>

                <h1
                    class="
                        text-3xl font-semibold tracking-tight
                        text-slate-950
                    "
                >
                    Initial Setup
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Create the first Administrator and configure the
                    Managing Organisation. This setup can be completed only
                    once.
                </p>
            </header>

            <div
                id="setup-loading"
                class="
                    rounded-xl border border-slate-200 bg-white
                    px-6 py-8 text-sm text-slate-500 shadow-sm
                "
            >
                Checking installation status…
            </div>

            <div
                id="setup-error"
                class="
                    mb-6 hidden rounded-lg
                    border border-red-200 bg-red-50
                    px-4 py-3 text-sm text-red-700
                "
            ></div>

            <form
                id="setup-form"
                class="
                    hidden space-y-7 rounded-xl
                    border border-slate-200 bg-white
                    p-6 shadow-sm sm:p-8
                "
            >

                {{-- Administrator --}}
                <section>
                    <h2 class="text-lg font-semibold text-slate-950">
                        Administrator
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        This account will be the first Patrimoine Administrator.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">

                        <div>
                            <label
                                for="administrator-name"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="administrator-name"
                                type="text"
                                required
                                autocomplete="name"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="administrator-email"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Email Address
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="administrator-email"
                                type="email"
                                required
                                autocomplete="email"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="administrator-password"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Password
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="administrator-password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >

                            <p class="mt-1.5 text-xs text-slate-500">
                                At least 12 characters including uppercase,
                                lowercase, a number and a symbol.
                            </p>
                        </div>

                        <div>
                            <label
                                for="administrator-password-confirmation"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Confirm Password
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="administrator-password-confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                    </div>
                </section>

                {{-- Managing Organisation --}}
                <section class="border-t border-slate-100 pt-7">

                    <h2 class="text-lg font-semibold text-slate-950">
                        Managing Organisation
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        The organisation operating this Patrimoine installation.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">

                        <div class="md:col-span-2">
                            <label
                                for="organisation-legal-name"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Legal Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-legal-name"
                                type="text"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="organisation-address"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Address
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                id="organisation-address"
                                required
                                rows="3"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            ></textarea>
                        </div>

                        <div>
                            <label
                                for="organisation-phone"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Organisation Phone
                            </label>

                            <input
                                id="organisation-phone"
                                type="text"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-email"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Organisation Email
                            </label>

                            <input
                                id="organisation-email"
                                type="email"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="contact-person-name"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Contact Person
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="contact-person-name"
                                type="text"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="contact-person-phone"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Contact Phone
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="contact-person-phone"
                                type="text"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="contact-person-email"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Contact Email
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="contact-person-email"
                                type="email"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                    </div>
                </section>

                {{-- Application settings --}}
                <section class="border-t border-slate-100 pt-7">

                    <h2 class="text-lg font-semibold text-slate-950">
                        Application Settings
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Language and currency apply to the entire Managing
                        Organisation and remain independent.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">

                        <div>
                            <label
                                for="setup-language"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Language
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="setup-language"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200 bg-white
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                                <option value="en" selected>English</option>
                                <option value="fr">Français</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="setup-currency"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Currency
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="setup-currency"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200 bg-white
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                                <option value="GHS" selected>GHS</option>
                                <option value="FCFA">FCFA</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="setup-vat-rate"
                                class="
                                    mb-1.5 block text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Default VAT Rate %
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="setup-vat-rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value="18"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5 text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2 focus:ring-patrimoine-100
                                "
                            >
                        </div>

                    </div>
                </section>

                <section class="border-t border-slate-100 pt-7">

                    <button
                        id="setup-submit"
                        type="submit"
                        class="
                            inline-flex items-center justify-center
                            rounded-lg bg-patrimoine-950
                            px-5 py-3 text-sm font-semibold text-white
                            shadow-sm transition
                            hover:bg-patrimoine-800
                            focus:outline-none
                            focus:ring-4 focus:ring-patrimoine-700/20
                            disabled:cursor-not-allowed
                            disabled:opacity-60
                        "
                    >
                        Complete Setup
                    </button>

                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        After setup, use the administrator account above to
                        sign in. The login screen will follow the selected
                        organisation language.
                    </p>

                </section>

            </form>

        </div>

    </main>

</body>
</html>
