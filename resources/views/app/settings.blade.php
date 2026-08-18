@extends('layouts.app')

@section('title', __('ui.settings.title'))
@section('title-i18n', 'settings.title')

@section('content')

<div
    id="settings-workspace"
    class="pm-settings-page mx-auto max-w-6xl"
>

    {{-- Page heading --}}
    <div
        class="
            mb-7 flex flex-col gap-4
            sm:flex-row sm:items-end
            sm:justify-between
        "
    >
        <div>
            <p
                class="
                    text-xs font-semibold uppercase
                    tracking-[0.14em]
                    text-patrimoine-700
                "
            >
                <span data-i18n="settings.administration">{{ __('ui.settings.administration') }}</span>
            </p>

            <h1
                class="
                    mt-2 text-2xl font-semibold
                    tracking-tight
                    text-[var(--pm-text)]
                "
            >
                <span data-i18n="settings.heading">{{ __('ui.settings.heading') }}</span>
            </h1>

            <p
                class="
                    mt-2 max-w-3xl
                    text-sm leading-6
                    text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="settings.description">{{ __('ui.settings.description') }}</span>
            </p>
        </div>
    </div>

    {{-- Page-level error --}}
    <div
        id="settings-error"
        class="
            mb-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- Page-level success --}}
    <div
        id="settings-success"
        class="
            mb-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-success-border)]
            bg-[var(--pm-success-background)]
            text-[var(--pm-success-text)]
        "
    ></div>

    <section class="pm-settings-shell">
        <div class="pm-settings-intro">
            <h2
                class="
                    text-base font-semibold
                    text-[var(--pm-text)]
                "
            >
                <span data-i18n="settings.managing_organisation">{{ __('ui.settings.managing_organisation') }}</span>
            </h2>

            <p class="mt-1 text-sm leading-6 text-[var(--pm-text-muted)]">
                <span data-i18n="settings.managing_organisation_description">{{ __('ui.settings.managing_organisation_description') }}</span>
            </p>
        </div>

        <form id="managing-organisation-form">

            <div class="pm-settings-form-grid">

                {{-- Organisation identity --}}
                <section>
                    <h3
                        class="
                            mb-4 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        <span data-i18n="settings.organisation_details">{{ __('ui.settings.organisation_details') }}</span>
                    </h3>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div class="md:col-span-2">
                            <label
                                for="organisation-legal-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.legal_name">{{ __('ui.settings.legal_name') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-legal-name"
                                type="text"
                                required
                                maxlength="255"
                                data-i18n-placeholder="settings.legal_name_placeholder"
                                placeholder="{{ __('ui.settings.legal_name_placeholder') }}"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="organisation-address"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.address">{{ __('ui.settings.address') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                id="organisation-address"
                                rows="2"
                                required
                                data-i18n-placeholder="settings.address_placeholder"
                                placeholder="{{ __('ui.settings.address_placeholder') }}"
                                class="
                                    w-full resize-y rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            ></textarea>
                        </div>

                        <div>
                            <label
                                for="organisation-phone"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.phone">{{ __('ui.settings.phone') }}</span>
                            </label>

                            <input
                                id="organisation-phone"
                                type="text"
                                maxlength="50"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-alternate-phone"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.alternate_phone">{{ __('ui.settings.alternate_phone') }}</span>
                            </label>

                            <input
                                id="organisation-alternate-phone"
                                type="text"
                                maxlength="50"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="organisation-email"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.general_email">{{ __('ui.settings.general_email') }}</span>
                            </label>

                            <input
                                id="organisation-email"
                                type="email"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Contact person --}}
                <section>
                    <h3
                        class="
                            mb-4 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        <span data-i18n="settings.primary_contact">{{ __('ui.settings.primary_contact') }}</span>
                    </h3>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div class="md:col-span-2">
                            <label
                                for="organisation-contact-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.contact_person">{{ __('ui.settings.contact_person') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-contact-name"
                                type="text"
                                required
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-contact-phone"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.contact_phone">{{ __('ui.settings.contact_phone') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-contact-phone"
                                type="text"
                                required
                                maxlength="50"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-contact-email"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.contact_email">{{ __('ui.settings.contact_email') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-contact-email"
                                type="email"
                                required
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Registration --}}
                <section>
                    <h3
                        class="
                            mb-4 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        <span data-i18n="settings.registration">{{ __('ui.settings.registration') }}</span>
                    </h3>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-registration-number"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.registration_number">{{ __('ui.settings.registration_number') }}</span>
                            </label>

                            <input
                                id="organisation-registration-number"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-vat-tin"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.vat_tin">{{ __('ui.settings.vat_tin') }}</span>
                            </label>

                            <input
                                id="organisation-vat-tin"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Language and currency --}}
                <section>
                    <h3
                        class="
                            mb-1 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        <span data-i18n="settings.language_currency">{{ __('ui.settings.language_currency') }}</span>
                    </h3>

                    <p class="mb-4 text-xs text-slate-500">
                        <span data-i18n="settings.language_currency_description">{{ __('ui.settings.language_currency_description') }}</span>
                    </p>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-language"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.language">{{ __('ui.settings.language') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="organisation-language"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                                @foreach(
                                    config('patrimoine.languages', [])
                                    as $code => $definition
                                )
                                    <option
                                        value="{{ $code }}"
                                        data-i18n="language.{{ $code }}"
                                    >{{ __('ui.language.' . $code) }}</option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-slate-500">
                                <span data-i18n="settings.language_help">{{ __('ui.settings.language_help') }}</span>
                            </p>
                        </div>

                        <div>
                            <label
                                for="organisation-currency"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.currency">{{ __('ui.settings.currency') }}</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="organisation-currency"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                                @foreach(
                                    config('patrimoine.currencies', [])
                                    as $code => $definition
                                )
                                    <option value="{{ $code }}">
                                        {{ $definition['name'] ?? $code }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-slate-500">
                                <span data-i18n="settings.currency_help">{{ __('ui.settings.currency_help') }}</span>
                            </p>
                        </div>
                    </div>
                </section>


                {{-- Financial defaults --}}
                <section>
                    <h3
                        class="
                            mb-1 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        <span data-i18n="settings.financial_defaults">{{ __('ui.settings.financial_defaults') }}</span>
                    </h3>

                    <p class="mb-4 text-xs text-slate-500">
                        <span data-i18n="settings.financial_defaults_description">{{ __('ui.settings.financial_defaults_description') }}</span>
                    </p>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-default-vat-rate"
                                class="
                                    mb-1.5 flex items-center gap-1.5
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.default_vat_rate">{{ __('ui.settings.default_vat_rate') }}</span>

                                <x-field-help
                                    label="About Default VAT Rate"
                                    label-key="settings.vat_help_label"
                                    text-key="settings.vat_help_text"
                                >
                                    This rate is pre-filled when creating a new Lease.
                                    Individual Leases may still override the value,
                                    including using 0% where applicable.
                                    Changing this setting does not alter existing
                                    Leases or historical Invoices.
                                </x-field-help>

                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-default-vat-rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                required
                                value="18"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >

                            <p
                                class="
                                    mt-1.5 text-xs
                                    text-slate-500
                                "
                            >
                                <span data-i18n="settings.vat_starting_rate">{{ __('ui.settings.vat_starting_rate') }}</span>
                            </p>
                        </div>
                    </div>
                </section>





                {{-- Banking --}}
                <section>
                    <h3
                        class="
                            mb-1 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        <span data-i18n="settings.banking_details">{{ __('ui.settings.banking_details') }}</span>
                    </h3>

                    <p class="mb-4 text-xs text-slate-500">
                        <span data-i18n="settings.optional">{{ __('ui.settings.optional') }}</span>
                    </p>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-bank-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.bank_name">{{ __('ui.settings.bank_name') }}</span>
                            </label>

                            <input
                                id="organisation-bank-name"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-bank-branch"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.bank_branch">{{ __('ui.settings.bank_branch') }}</span>
                            </label>

                            <input
                                id="organisation-bank-branch"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-bank-account-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.account_name">{{ __('ui.settings.account_name') }}</span>
                            </label>

                            <input
                                id="organisation-bank-account-name"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-bank-account-number"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="settings.account_number">{{ __('ui.settings.account_number') }}</span>
                            </label>

                            <input
                                id="organisation-bank-account-number"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Notes --}}
                <section class="pm-settings-notes-card">
                    <label
                        for="organisation-notes"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="settings.notes">{{ __('ui.settings.notes') }}</span>
                    </label>

                    <textarea
                        id="organisation-notes"
                        rows="3"
                        class="
                            w-full resize-y rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                            focus:border-patrimoine-500
                            focus:ring-2
                            focus:ring-patrimoine-100
                        "
                    ></textarea>
                </section>

            </div>

            <div class="pm-settings-save-bar">
                <div
                    class="
                        text-xs leading-5
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="settings.managing_organisation">{{ __('ui.settings.managing_organisation') }}</span>
                </div>

                <button
                    id="managing-organisation-submit-button"
                    type="submit"
                    class="pm-button-primary"
                >
                    <span data-i18n="settings.save">{{ __('ui.settings.save') }}</span>
                </button>
            </div>

        </form>
    </section>

</div>

@endsection
