@extends('layouts.app')

@section('title', __('ui.settings.title'))
@section('title-i18n', 'settings.title')

@section('content')

{{--
    V1.0.9 Settings.

    Tabbed administrator workspace following the Help page tablist
    pattern: a sticky pm-card toolbar with role="tablist" pills, hash
    deep-linking (/settings#data) and four panels — Organisation,
    Preferences, Data, About.

    The Organisation and Preferences tabs edit the same Managing
    Organisation record: resources/js/settings.js loads it once and
    every save sends ONE merged payload from both tabs' fields to
    PUT /api/managing-organisation.

    The whole page is Administrator-only (server middleware), so no
    element here needs its own data-requires-capability gate.
--}}
<div
    id="settings-workspace"
    class="mx-auto max-w-[1600px]"
>

    {{-- Page heading --}}
    <div>
        <p
            class="
                text-xs font-semibold uppercase
                tracking-[0.14em]
                text-[var(--pm-accent)]
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

    {{-- Sticky toolbar: the tablist --}}
    <div
        id="settings-toolbar"
        class="
            sticky top-20 z-20
            mt-6 py-3
            bg-[var(--pm-page)]
        "
    >
        <div class="pm-card p-4">
            <div
                role="tablist"
                class="
                    inline-flex max-w-full
                    overflow-x-auto rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)]
                    p-1
                "
            >
                <button
                    id="settings-tab-organisation"
                    type="button"
                    role="tab"
                    aria-selected="true"
                    aria-controls="settings-organisation-panel"
                    class="
                        rounded-lg px-4 py-2
                        text-sm font-medium
                        transition
                    "
                >
                    <span data-i18n="settings.tab_organisation">{{ __('ui.settings.tab_organisation') }}</span>
                </button>

                <button
                    id="settings-tab-users"
                    type="button"
                    role="tab"
                    data-requires-capability="manage_users"
                    aria-selected="false"
                    aria-controls="settings-users-panel"
                    class="
                        rounded-lg px-4 py-2
                        text-sm font-medium
                        transition
                    "
                >
                    <span data-i18n="navigation.users">{{ __('ui.navigation.users') }}</span>
                </button>

                <button
                    id="settings-tab-license"
                    type="button"
                    role="tab"
                    data-requires-capability="manage_settings"
                    aria-selected="false"
                    aria-controls="settings-license-panel"
                    class="
                        rounded-lg px-4 py-2
                        text-sm font-medium
                        transition
                    "
                >
                    <span data-i18n="navigation.license">{{ __('ui.navigation.license') }}</span>
                </button>

                <button
                    id="settings-tab-preferences"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="settings-preferences-panel"
                    class="
                        rounded-lg px-4 py-2
                        text-sm font-medium
                        transition
                    "
                >
                    <span data-i18n="settings.tab_preferences">{{ __('ui.settings.tab_preferences') }}</span>
                </button>

                <button
                    id="settings-tab-data"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="settings-data-panel"
                    class="
                        rounded-lg px-4 py-2
                        text-sm font-medium
                        transition
                    "
                >
                    <span data-i18n="settings.tab_data">{{ __('ui.settings.tab_data') }}</span>
                </button>

                <button
                    id="settings-tab-about"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="settings-about-panel"
                    class="
                        rounded-lg px-4 py-2
                        text-sm font-medium
                        transition
                    "
                >
                    <span data-i18n="settings.about">{{ __('ui.settings.about') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{--
        Organisation tab: identity, primary contact, registration,
        banking and notes for the Managing Organisation.
    --}}
    <section
        id="settings-organisation-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-organisation"
        class="mt-4"
    >
        <div
            class="
                grid items-start gap-6
                xl:grid-cols-[minmax(0,1fr)_20rem]
            "
        >
        <div class="max-w-[880px]">

            {{-- Inline tab feedback --}}
            <div
                id="settings-organisation-error"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    text-[var(--pm-danger-text)]
                "
                role="alert"
            ></div>

            <div
                id="settings-organisation-success"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-success-border)]
                    bg-[var(--pm-success-background)]
                    text-[var(--pm-success-text)]
                "
                role="status"
            ></div>

            {{-- Fresh installation: no organisation configured yet --}}
            <div
                id="settings-not-configured"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-info-border)]
                    bg-[var(--pm-info-background)]
                    text-[var(--pm-info-text)]
                "
                role="status"
            >
                <span data-i18n="settings.not_configured">{{ __('ui.settings.not_configured') }}</span>
            </div>

            <form id="managing-organisation-form">

                {{--
                    Disabled until the GET /api/managing-organisation
                    request resolves (settings.js re-enables it).
                --}}
                <fieldset
                    id="managing-organisation-fieldset"
                    disabled
                    class="grid gap-4"
                >

                    {{-- Organisation identity --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-4 text-sm font-semibold
                                text-[var(--pm-text)]
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
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.legal_name">{{ __('ui.settings.legal_name') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="organisation-legal-name"
                                    type="text"
                                    required
                                    maxlength="255"
                                    data-i18n-placeholder="settings.legal_name_placeholder"
                                    placeholder="{{ __('ui.settings.legal_name_placeholder') }}"
                                    class="pm-input"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    for="organisation-address"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.address">{{ __('ui.settings.address') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <textarea
                                    id="organisation-address"
                                    rows="2"
                                    required
                                    data-i18n-placeholder="settings.address_placeholder"
                                    placeholder="{{ __('ui.settings.address_placeholder') }}"
                                    class="pm-input resize-y"
                                ></textarea>
                            </div>

                            <x-phone-field
                                id="organisation-phone"
                                label="settings.phone"
                            />

                            <x-phone-field
                                id="organisation-alternate-phone"
                                label="settings.alternate_phone"
                            />

                            <div class="md:col-span-2">
                                <label
                                    for="organisation-email"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.general_email">{{ __('ui.settings.general_email') }}</span>
                                </label>

                                <input
                                    id="organisation-email"
                                    type="email"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- Contact person --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-4 text-sm font-semibold
                                text-[var(--pm-text)]
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
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.contact_person">{{ __('ui.settings.contact_person') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="organisation-contact-name"
                                    type="text"
                                    required
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <x-phone-field
                                id="organisation-contact-phone"
                                label="settings.contact_phone"
                                :required="true"
                            />

                            <div>
                                <label
                                    for="organisation-contact-email"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.contact_email">{{ __('ui.settings.contact_email') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="organisation-contact-email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- Registration --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-4 text-sm font-semibold
                                text-[var(--pm-text)]
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
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.registration_number">{{ __('ui.settings.registration_number') }}</span>
                                </label>

                                <input
                                    id="organisation-registration-number"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="organisation-vat-tin"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.vat_tin">{{ __('ui.settings.vat_tin') }}</span>
                                </label>

                                <input
                                    id="organisation-vat-tin"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- Banking --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="settings.banking_details">{{ __('ui.settings.banking_details') }}</span>
                        </h3>

                        <p class="mb-4 text-xs text-[var(--pm-text-muted)]">
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
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.bank_name">{{ __('ui.settings.bank_name') }}</span>
                                </label>

                                <input
                                    id="organisation-bank-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="organisation-bank-branch"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.bank_branch">{{ __('ui.settings.bank_branch') }}</span>
                                </label>

                                <input
                                    id="organisation-bank-branch"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="organisation-bank-account-name"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.account_name">{{ __('ui.settings.account_name') }}</span>
                                </label>

                                <input
                                    id="organisation-bank-account-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="organisation-bank-account-number"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.account_number">{{ __('ui.settings.account_number') }}</span>
                                </label>

                                <input
                                    id="organisation-bank-account-number"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- Notes --}}
                    <section class="pm-card p-5">
                        <label
                            for="organisation-notes"
                            class="pm-field-label"
                        >
                            <span data-i18n="settings.notes">{{ __('ui.settings.notes') }}</span>
                        </label>

                        <textarea
                            id="organisation-notes"
                            rows="3"
                            class="pm-input resize-y"
                        ></textarea>
                    </section>

                    {{-- Save --}}
                    <div class="flex justify-end">
                        <button
                            id="managing-organisation-submit-button"
                            type="submit"
                            class="pm-button-primary"
                        >
                            <span data-i18n="settings.save">{{ __('ui.settings.save') }}</span>
                        </button>
                    </div>

                </fieldset>

            </form>

            {{--
                Closing the account. Last on the page, and the only control
                in Patrimoine that destroys an organisation. The drawer at
                the foot of this file is what actually asks for it.
            --}}
            <section
                class="
                    mt-8 overflow-hidden rounded-xl border
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                "
            >
                <div
                    class="
                        flex flex-col gap-4 p-5
                        sm:flex-row sm:items-center
                        sm:justify-between
                    "
                >
                    <div>
                        <h3
                            class="
                                text-sm font-semibold
                                text-[var(--pm-danger-text)]
                            "
                        >
                            <span data-i18n="settings.close_account">{{ __('ui.settings.close_account') }}</span>
                        </h3>

                        <p
                            class="
                                mt-1 max-w-xl text-sm leading-6
                                text-[var(--pm-danger-text)]
                            "
                        >
                            <span data-i18n="settings.close_account_description">{{ __('ui.settings.close_account_description') }}</span>
                        </p>
                    </div>

                    <button
                        id="settings-close-account"
                        type="button"
                        class="pm-button-danger shrink-0"
                    >
                        <span data-i18n="settings.close_account_action">{{ __('ui.settings.close_account_action') }}</span>
                    </button>
                </div>
            </section>
        </div>

        {{--
            What this account is, in five lines. Filled by settings.js from
            GET /api/license, which already knows the plan, the usage and
            the organisation behind them.
        --}}
        <aside class="grid gap-4">
            <div class="pm-card p-5">
                <h3 class="text-sm font-semibold text-[var(--pm-text)]">
                    <span data-i18n="settings.summary">{{ __('ui.settings.summary') }}</span>
                </h3>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="settings.summary_description">{{ __('ui.settings.summary_description') }}</span>
                </p>

                <dl id="settings-summary" class="mt-4 grid gap-3 text-sm"></dl>
            </div>

            <div class="pm-card p-5">
                <h3 class="text-sm font-semibold text-[var(--pm-text)]">
                    <span data-i18n="settings.need_help">{{ __('ui.settings.need_help') }}</span>
                </h3>

                <p class="mt-2 text-sm leading-6 text-[var(--pm-text-muted)]">
                    <span data-i18n="settings.need_help_description">{{ __('ui.settings.need_help_description') }}</span>
                </p>

                <a href="/help" class="pm-button-secondary mt-4 w-full">
                    <span data-i18n="settings.open_guide">{{ __('ui.settings.open_guide') }}</span>
                </a>
            </div>
        </aside>
        </div>
    </section>

    {{-- Users tab: the people who can sign in to this organisation. --}}
    <section
        id="settings-users-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-users"
        class="mt-4 hidden"
    >
        @include('app.panels.users')
    </section>

    {{-- Licence tab: the plan, what it allows, and what is being used. --}}
    <section
        id="settings-license-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-license"
        class="mt-4 hidden"
    >
        @include('app.panels.license')
    </section>

    {{--
        Preferences tab: organisation-wide language, currency and the
        default VAT rate. Saved through the same merged PUT payload as
        the Organisation tab.
    --}}
    <section
        id="settings-preferences-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-preferences"
        class="mt-4 hidden"
    >
        <div class="max-w-[880px]">

            {{-- Inline tab feedback --}}
            <div
                id="settings-preferences-error"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    text-[var(--pm-danger-text)]
                "
                role="alert"
            ></div>

            <div
                id="settings-preferences-success"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-success-border)]
                    bg-[var(--pm-success-background)]
                    text-[var(--pm-success-text)]
                "
                role="status"
            ></div>

            <form id="settings-preferences-form">

                {{-- Disabled until the organisation GET resolves --}}
                <fieldset
                    id="settings-preferences-fieldset"
                    disabled
                    class="grid gap-4"
                >

                    {{-- Language and currency --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="settings.language_currency">{{ __('ui.settings.language_currency') }}</span>
                        </h3>

                        <p class="mb-4 text-xs text-[var(--pm-text-muted)]">
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
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.language">{{ __('ui.settings.language') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <select
                                    id="organisation-language"
                                    required
                                    class="pm-input"
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

                                <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                                    <span data-i18n="settings.language_help">{{ __('ui.settings.language_help') }}</span>
                                </p>
                            </div>

                            <div>
                                <label
                                    for="organisation-currency"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.currency">{{ __('ui.settings.currency') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <select
                                    id="organisation-currency"
                                    required
                                    class="pm-input"
                                >
                                    @foreach(
                                        config('patrimoine.currencies', [])
                                        as $code => $definition
                                    )
                                        <option
                                            value="{{ $code }}"
                                            data-i18n="currency.{{ $code }}"
                                        >{{ __('ui.currency.' . $code) }}</option>
                                    @endforeach
                                </select>

                                <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                                    <span data-i18n="settings.currency_help">{{ __('ui.settings.currency_help') }}</span>
                                </p>
                            </div>
                        </div>
                    </section>

                    {{-- Financial defaults --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="settings.financial_defaults">{{ __('ui.settings.financial_defaults') }}</span>
                        </h3>

                        <p class="mb-4 text-xs text-[var(--pm-text-muted)]">
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
                                    class="pm-field-label"
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

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                {{--
                                    No hardcoded default value: settings.js
                                    fills the field from the loaded
                                    organisation or, on a fresh install,
                                    from the presentation configuration —
                                    the single source of truth.
                                --}}
                                <input
                                    id="organisation-default-vat-rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    class="pm-input"
                                >

                                <p
                                    class="
                                        mt-1.5 text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    <span data-i18n="settings.vat_starting_rate">{{ __('ui.settings.vat_starting_rate') }}</span>
                                </p>
                            </div>
                        </div>
                    </section>

                    {{-- Communications (V1.0.29) --}}
                    <section class="pm-card p-5">
                        <h3
                            class="
                                mb-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="settings.communications">{{ __('ui.settings.communications') }}</span>
                        </h3>

                        <p class="mb-4 text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="settings.communications_description">{{ __('ui.settings.communications_description') }}</span>
                        </p>

                        <label
                            class="
                                flex cursor-pointer
                                items-start gap-3
                                rounded-xl border
                                border-[var(--pm-border)]
                                px-4 py-3
                            "
                        >
                            <input
                                id="organisation-party-emails-enabled"
                                type="checkbox"
                                class="
                                    mt-0.5 h-4 w-4 rounded
                                    border-[var(--pm-border-strong)]
                                    text-[var(--pm-accent)]
                                    focus:ring-[var(--pm-accent)]
                                "
                            >

                            <span class="min-w-0">
                                <span
                                    class="
                                        block text-sm font-medium
                                        text-[var(--pm-text-secondary)]
                                    "
                                >
                                    <span data-i18n="settings.party_emails_enabled">{{ __('ui.settings.party_emails_enabled') }}</span>
                                </span>

                                <span
                                    class="
                                        mt-1 block text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    <span data-i18n="settings.party_emails_help">{{ __('ui.settings.party_emails_help') }}</span>
                                </span>
                            </span>
                        </label>
                    </section>

                    {{-- Save --}}
                    <div class="flex justify-end">
                        <button
                            id="settings-preferences-submit-button"
                            type="submit"
                            class="pm-button-primary"
                        >
                            <span data-i18n="settings.save_preferences">{{ __('ui.settings.save_preferences') }}</span>
                        </button>
                    </div>

                </fieldset>

            </form>
        </div>
    </section>

    {{--
        Data tab: V1.0.7 Registry backup & restore. Only Registry data
        (parties, buildings, units, leases) is portable — financial
        history is immutable and is never part of a backup.
    --}}
    <section
        id="settings-data-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-data"
        class="mt-4 hidden"
    >
        <div
            id="settings-backup-section"
            class="max-w-[880px]"
        >

            {{-- Inline tab feedback --}}
            <div
                id="settings-data-error"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    text-[var(--pm-danger-text)]
                "
                role="alert"
            ></div>

            <div
                id="settings-data-success"
                class="
                    mb-4 hidden rounded-xl
                    border px-4 py-3 text-sm
                    border-[var(--pm-success-border)]
                    bg-[var(--pm-success-background)]
                    text-[var(--pm-success-text)]
                "
                role="status"
            ></div>

            <div class="grid gap-4">

                {{-- Export --}}
                <section class="pm-card p-5">
                    <h2
                        class="
                            text-base font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="settings.backup_restore">{{ __('ui.settings.backup_restore') }}</span>
                    </h2>

                    <p
                        class="
                            mt-1 text-sm leading-6
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span data-i18n="settings.backup_restore_description">{{ __('ui.settings.backup_restore_description') }}</span>
                    </p>

                    <div
                        class="
                            mt-4 rounded-lg border
                            border-[var(--pm-warning-border)]
                            bg-[var(--pm-warning-background)]
                            px-4 py-3 text-sm
                            text-[var(--pm-warning-text)]
                        "
                    >
                        <span data-i18n="settings.backup_financial_note">{{ __('ui.settings.backup_financial_note') }}</span>
                    </div>

                    <div class="mt-6">
                        <h3
                            class="
                                text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="settings.export_heading">{{ __('ui.settings.export_heading') }}</span>
                        </h3>

                        <div
                            class="
                                mt-3 grid gap-3
                                sm:grid-cols-2
                            "
                        >
                            @foreach([
                                'parties',
                                'buildings',
                                'units',
                                'leases',
                            ] as $entity)
                                <div
                                    class="
                                        flex items-center
                                        justify-between gap-3
                                        rounded-lg border
                                        border-[var(--pm-border)]
                                        bg-[var(--pm-surface-subtle)]
                                        px-4 py-3
                                    "
                                >
                                    <span
                                        class="
                                            text-sm font-medium
                                            text-[var(--pm-text)]
                                        "
                                        data-i18n="settings.entity_{{ $entity }}"
                                    >{{ __('ui.settings.entity_' . $entity) }}</span>

                                    <span class="flex shrink-0 gap-2">
                                        <button
                                            type="button"
                                            data-registry-export
                                            data-entity="{{ $entity }}"
                                            data-format="pdf"
                                            class="pm-button-secondary px-3 py-2 text-xs"
                                        >
                                            <span data-i18n="settings.format_pdf">{{ __('ui.settings.format_pdf') }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            data-registry-export
                                            data-entity="{{ $entity }}"
                                            data-format="xlsx"
                                            class="pm-button-secondary px-3 py-2 text-xs"
                                        >
                                            <span data-i18n="settings.format_xlsx">{{ __('ui.settings.format_xlsx') }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            data-registry-export
                                            data-entity="{{ $entity }}"
                                            data-format="csv"
                                            class="pm-button-secondary px-3 py-2 text-xs"
                                        >
                                            <span data-i18n="settings.format_csv">{{ __('ui.settings.format_csv') }}</span>
                                        </button>
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        {{--
                            V1.0.34: the whole organisation, for a person who
                            asks for it. Wider than the registry export above
                            — that one is the portable registry, this one is
                            everything held, financial history included.
                        --}}
                        <div
                            id="settings-everything"
                            class="
                                mt-6 rounded-xl border
                                border-[var(--pm-border)]
                                bg-[var(--pm-surface-subtle)] p-5
                            "
                        >
                            <h3 class="text-sm font-semibold text-[var(--pm-text)]">
                                <span data-i18n="settings.everything_title">{{ __('ui.settings.everything_title') }}</span>
                            </h3>

                            <p class="mt-1 max-w-2xl text-sm text-[var(--pm-text-muted)]">
                                <span data-i18n="settings.everything_description">{{ __('ui.settings.everything_description') }}</span>
                            </p>

                            <button
                                id="settings-export-everything"
                                type="button"
                                class="pm-button-secondary mt-4"
                            >
                                <span data-i18n="settings.everything_action">{{ __('ui.settings.everything_action') }}</span>
                            </button>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button
                                id="settings-export-full"
                                type="button"
                                class="pm-button-primary"
                            >
                                <span data-i18n="settings.export_full">{{ __('ui.settings.export_full') }}</span>
                            </button>
                        </div>
                    </div>
                </section>

                {{--
                    Import / restore.

                    The dry run is no longer optional: the operator always
                    validates the file first, then applies the restore
                    through a confirmation drawer.
                --}}
                <section class="pm-card p-5">
                    <h3
                        class="
                            text-sm font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="settings.import_heading">{{ __('ui.settings.import_heading') }}</span>
                    </h3>

                    <p
                        class="
                            mt-1 text-xs leading-5
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span data-i18n="settings.dry_run_help">{{ __('ui.settings.dry_run_help') }}</span>
                    </p>

                    <div
                        class="
                            mt-3 grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="settings-import-file"
                                class="pm-field-label"
                            >
                                <span data-i18n="settings.import_file">{{ __('ui.settings.import_file') }}</span>
                            </label>

                            {{--
                                The native file control keeps UA chrome that
                                ignores the theme, so the real input is
                                visually hidden behind a tokenized button.
                            --}}
                            <input
                                id="settings-import-file"
                                type="file"
                                accept=".csv,.xlsx"
                                class="sr-only"
                            >

                            <div class="flex items-center gap-3">
                                <label
                                    for="settings-import-file"
                                    class="pm-button-secondary cursor-pointer"
                                >
                                    <span data-i18n="settings.choose_file">{{ __('ui.settings.choose_file') }}</span>
                                </label>

                                <span
                                    id="settings-import-file-name"
                                    class="
                                        min-w-0 truncate text-sm
                                        text-[var(--pm-text-muted)]
                                    "
                                    data-i18n="settings.no_file_selected"
                                >{{ __('ui.settings.no_file_selected') }}</span>
                            </div>
                        </div>

                        <div>
                            <label
                                for="settings-import-entity"
                                class="pm-field-label"
                            >
                                <span data-i18n="settings.import_entity">{{ __('ui.settings.import_entity') }}</span>
                            </label>

                            <select
                                id="settings-import-entity"
                                class="pm-input"
                            >
                                <option value="parties" data-i18n="settings.entity_parties">{{ __('ui.settings.entity_parties') }}</option>
                                <option value="buildings" data-i18n="settings.entity_buildings">{{ __('ui.settings.entity_buildings') }}</option>
                                <option value="units" data-i18n="settings.entity_units">{{ __('ui.settings.entity_units') }}</option>
                                <option value="leases" data-i18n="settings.entity_leases">{{ __('ui.settings.entity_leases') }}</option>
                                <option value="full" data-i18n="settings.entity_full">{{ __('ui.settings.entity_full') }}</option>
                            </select>
                        </div>
                    </div>

                    <div
                        class="
                            mt-4 flex flex-col gap-3
                            sm:flex-row sm:items-center
                            sm:justify-end
                        "
                    >
                        <button
                            id="settings-import-run"
                            type="button"
                            class="pm-button-primary shrink-0"
                        >
                            <span data-i18n="settings.run_dry_run">{{ __('ui.settings.run_dry_run') }}</span>
                        </button>
                    </div>

                    {{-- Dry-run / restore result --}}
                    <div
                        id="settings-import-result"
                        class="
                            mt-4 hidden rounded-lg
                            border border-[var(--pm-border)]
                            bg-[var(--pm-surface-subtle)]
                            px-4 py-4 text-sm
                        "
                    ></div>

                    {{-- Shown only after a successful dry run --}}
                    <div
                        id="settings-import-apply-row"
                        class="mt-4 hidden"
                    >
                        <div class="flex justify-end">
                            <button
                                id="settings-import-apply"
                                type="button"
                                class="pm-button-danger shrink-0"
                            >
                                <span data-i18n="settings.apply_restore">{{ __('ui.settings.apply_restore') }}</span>
                            </button>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </section>

    {{-- About tab: running release + link to the update log --}}
    <section
        id="settings-about-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-about"
        class="mt-4 hidden"
    >
        <div class="max-w-[880px]">
            <section class="pm-card p-5">
                <div
                    class="
                        flex flex-col gap-3
                        sm:flex-row sm:items-center
                        sm:justify-between
                    "
                >
                    <div>
                        <h2
                            class="
                                text-base font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="settings.about">{{ __('ui.settings.about') }}</span>
                        </h2>

                        <p
                            class="
                                mt-1 text-sm
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="settings.application_version">{{ __('ui.settings.application_version') }}</span>:
                            <span
                                id="settings-app-version"
                                class="
                                    font-medium
                                    text-[var(--pm-text)]
                                "
                            >—</span>
                        </p>
                    </div>

                    <a
                        href="/help#updates"
                        class="pm-button-secondary shrink-0"
                    >
                        <span data-i18n="settings.view_update_log">{{ __('ui.settings.view_update_log') }}</span>
                    </a>
                </div>
            </section>
        </div>
    </section>

</div>

{{--
    Restore confirmation drawer: the destructive gate between a
    successful dry run and the real import. settings.js fills the
    summary from the pending dry-run result.
--}}
<x-drawer
    id="settings-restore-drawer"
    backdrop-id="settings-restore-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="settings-restore-drawer-title"
        description-id="settings-restore-drawer-description"
        close-id="settings-restore-close"
        close-label="Close"
        close-label-key="actions.close"
    >
        <x-slot:title>
            <span data-i18n="settings.confirm_restore_title">{{ __('ui.settings.confirm_restore_title') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="settings.confirm_restore_description">{{ __('ui.settings.confirm_restore_description') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <div
        class="
            min-h-0 flex-1
            overflow-y-auto
            px-6 py-6
        "
    >
        {{-- Dry-run summary rendered by settings.js --}}
        <div id="settings-restore-summary"></div>

        <div
            class="
                mt-5 rounded-lg border
                border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)]
                px-4 py-3 text-sm
                text-[var(--pm-danger-text)]
            "
        >
            <span data-i18n="settings.confirm_restore_warning">{{ __('ui.settings.confirm_restore_warning') }}</span>
        </div>
    </div>

    <x-drawer-footer>
        <button
            id="settings-restore-cancel"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
        </button>

        <button
            id="settings-restore-confirm"
            type="button"
            class="pm-button-danger"
        >
            <span data-i18n="settings.confirm_restore_apply">{{ __('ui.settings.confirm_restore_apply') }}</span>
        </button>
    </x-drawer-footer>
</x-drawer>

{{--
    Closing the account.

    Two things are asked for, and both are asked for deliberately: the
    organisation's name typed back, and the administrator's own password.
    Neither is a formality — between them they are the difference between
    a decision and a mis-click on the most destructive control in the
    application.
--}}
<x-drawer
    id="settings-close-drawer"
    backdrop-id="settings-close-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="settings-close-drawer-title"
        description-id="settings-close-drawer-description"
        close-id="settings-close-drawer-dismiss"
        close-label="Close"
        close-label-key="actions.close"
    >
        <x-slot:title>
            <span data-i18n="settings.close_account">{{ __('ui.settings.close_account') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="settings.close_account_drawer">{{ __('ui.settings.close_account_drawer') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <div
        class="
            min-h-0 flex-1
            overflow-y-auto
            px-6 py-6
        "
    >
        <div
            class="
                rounded-lg border
                border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)]
                px-4 py-3 text-sm
                text-[var(--pm-danger-text)]
            "
        >
            <span data-i18n="settings.close_account_warning">{{ __('ui.settings.close_account_warning') }}</span>
        </div>

        {{-- What is about to be destroyed, counted by settings.js. --}}
        <ul
            id="settings-close-inventory"
            class="mt-4 grid gap-2 text-sm text-[var(--pm-text-muted)]"
        ></ul>

        <div
            id="settings-close-error"
            class="
                mt-4 hidden rounded-xl border px-4 py-3 text-sm
                border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)]
                text-[var(--pm-danger-text)]
            "
            role="alert"
        ></div>

        <div class="mt-5">
            <label for="settings-close-name" class="pm-field-label">
                <span data-i18n="settings.close_account_name_label">{{ __('ui.settings.close_account_name_label') }}</span>
            </label>

            <p
                id="settings-close-name-hint"
                class="mb-2 text-xs text-[var(--pm-text-muted)]"
            ></p>

            <input
                id="settings-close-name"
                type="text"
                autocomplete="off"
                class="pm-input"
            >
        </div>

        <div class="mt-4">
            <label for="settings-close-password" class="pm-field-label">
                <span data-i18n="settings.close_account_password_label">{{ __('ui.settings.close_account_password_label') }}</span>
            </label>

            <input
                id="settings-close-password"
                type="password"
                autocomplete="current-password"
                class="pm-input"
            >
        </div>
    </div>

    <x-drawer-footer>
        <button
            id="settings-close-cancel"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
        </button>

        <button
            id="settings-close-confirm"
            type="button"
            class="pm-button-danger"
        >
            <span data-i18n="settings.close_account_confirm">{{ __('ui.settings.close_account_confirm') }}</span>
        </button>
    </x-drawer-footer>
</x-drawer>

@endsection
