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

<body class="min-h-screen bg-[var(--pm-page)] font-sans text-[var(--pm-text)]">

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

                        <div class="text-sm text-[var(--pm-text-muted)]">
                            Property Management
                        </div>
                    </div>
                </div>

                <h1
                    class="
                        text-3xl font-semibold tracking-tight
                        text-[var(--pm-text)]
                    "
                >
                    Initial Setup
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--pm-text-muted)]">
                    Create the first Administrator and configure the
                    Managing Organisation. This setup can be completed only
                    once.
                </p>
            </header>

            <div
                id="setup-loading"
                class="
                    pm-card px-6 py-8
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                Checking installation status…
            </div>

            <div
                id="setup-error"
                class="
                    mb-6 hidden rounded-lg
                    border px-4 py-3 text-sm
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    text-[var(--pm-danger-text)]
                "
            ></div>

            <form
                id="setup-form"
                class="
                    pm-card hidden space-y-7
                    p-6 sm:p-8
                "
            >

                {{-- Administrator --}}
                <section>
                    <h2 class="text-lg font-semibold text-[var(--pm-text)]">
                        Administrator
                    </h2>

                    <p class="mt-1 text-sm text-[var(--pm-text-muted)]">
                        This account will be the first Patrimoine Administrator.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">

                        <div>
                            <label
                                for="administrator-name"
                                class="pm-field-label"
                            >
                                Name
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="administrator-name"
                                type="text"
                                required
                                autocomplete="name"
                                class="pm-input"
                            >
                        </div>

                        <div>
                            <label
                                for="administrator-email"
                                class="pm-field-label"
                            >
                                Email Address
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="administrator-email"
                                type="email"
                                required
                                autocomplete="email"
                                class="pm-input"
                            >
                        </div>

                        <div>
                            <label
                                for="administrator-password"
                                class="pm-field-label"
                            >
                                Password
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="administrator-password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="pm-input"
                            >

                            <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                                At least 12 characters including uppercase,
                                lowercase, a number and a symbol.
                            </p>
                        </div>

                        <div>
                            <label
                                for="administrator-password-confirmation"
                                class="pm-field-label"
                            >
                                Confirm Password
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="administrator-password-confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="pm-input"
                            >
                        </div>

                    </div>
                </section>

                {{-- Managing Organisation --}}
                <section class="border-t border-[var(--pm-border-subtle)] pt-7">

                    <h2 class="text-lg font-semibold text-[var(--pm-text)]">
                        Managing Organisation
                    </h2>

                    <p class="mt-1 text-sm text-[var(--pm-text-muted)]">
                        The organisation operating this Patrimoine installation.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">

                        <div class="md:col-span-2">
                            <label
                                for="organisation-legal-name"
                                class="pm-field-label"
                            >
                                Legal Name
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="organisation-legal-name"
                                type="text"
                                required
                                class="pm-input"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="organisation-address"
                                class="pm-field-label"
                            >
                                Address
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <textarea
                                id="organisation-address"
                                required
                                rows="3"
                                class="pm-input"
                            ></textarea>
                        </div>

                        <div>
                            <label
                                for="organisation-phone"
                                class="pm-field-label"
                            >
                                Organisation Phone
                            </label>

                            <input
                                id="organisation-phone"
                                type="text"
                                class="pm-input"
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-email"
                                class="pm-field-label"
                            >
                                Organisation Email
                            </label>

                            <input
                                id="organisation-email"
                                type="email"
                                class="pm-input"
                            >
                        </div>

                        <div>
                            <label
                                for="contact-person-name"
                                class="pm-field-label"
                            >
                                Contact Person
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="contact-person-name"
                                type="text"
                                required
                                class="pm-input"
                            >
                        </div>

                        <div>
                            <label
                                for="contact-person-phone"
                                class="pm-field-label"
                            >
                                Contact Phone
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="contact-person-phone"
                                type="text"
                                required
                                class="pm-input"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="contact-person-email"
                                class="pm-field-label"
                            >
                                Contact Email
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="contact-person-email"
                                type="email"
                                required
                                class="pm-input"
                            >
                        </div>

                    </div>
                </section>

                {{-- Application settings --}}
                <section class="border-t border-[var(--pm-border-subtle)] pt-7">

                    <h2 class="text-lg font-semibold text-[var(--pm-text)]">
                        Application Settings
                    </h2>

                    <p class="mt-1 text-sm text-[var(--pm-text-muted)]">
                        Language and currency apply to the entire Managing
                        Organisation and remain independent.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">

                        <div>
                            <label
                                for="setup-language"
                                class="pm-field-label"
                            >
                                Language
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <select
                                id="setup-language"
                                required
                                class="pm-input"
                            >
                                <option value="en" selected>English</option>
                                <option value="fr">Français</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="setup-currency"
                                class="pm-field-label"
                            >
                                Currency
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <select
                                id="setup-currency"
                                required
                                class="pm-input"
                            >
                                <option value="GHS" selected>GHS</option>
                                <option value="FCFA">FCFA</option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="setup-vat-rate"
                                class="pm-field-label"
                            >
                                Default VAT Rate %
                                <span class="text-[var(--pm-danger-text)]">*</span>
                            </label>

                            <input
                                id="setup-vat-rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value="18"
                                required
                                class="pm-input"
                            >
                        </div>

                    </div>
                </section>

                <section class="border-t border-[var(--pm-border-subtle)] pt-7">

                    <button
                        id="setup-submit"
                        type="submit"
                        class="pm-button-primary"
                    >
                        Complete Setup
                    </button>

                    <p class="mt-3 text-xs leading-5 text-[var(--pm-text-muted)]">
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
