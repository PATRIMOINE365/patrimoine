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

                            <div>
                                <label
                                    for="organisation-phone"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.phone">{{ __('ui.settings.phone') }}</span>
                                </label>

                                <input
                                    id="organisation-phone"
                                    type="text"
                                    maxlength="50"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="organisation-alternate-phone"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.alternate_phone">{{ __('ui.settings.alternate_phone') }}</span>
                                </label>

                                <input
                                    id="organisation-alternate-phone"
                                    type="text"
                                    maxlength="50"
                                    class="pm-input"
                                >
                            </div>

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

                            <div>
                                <label
                                    for="organisation-contact-phone"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="settings.contact_phone">{{ __('ui.settings.contact_phone') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="organisation-contact-phone"
                                    type="text"
                                    required
                                    maxlength="50"
                                    class="pm-input"
                                >
                            </div>

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
        </div>
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
                                            data-format="csv"
                                            class="pm-button-secondary px-3 py-2 text-xs"
                                        >
                                            <span data-i18n="settings.format_csv">{{ __('ui.settings.format_csv') }}</span>
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
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div
                            class="
                                mt-4 flex flex-col gap-3
                                sm:flex-row sm:items-center
                                sm:justify-between
                            "
                        >
                            <button
                                id="settings-export-full"
                                type="button"
                                class="pm-button-primary"
                            >
                                <span data-i18n="settings.export_full">{{ __('ui.settings.export_full') }}</span>
                            </button>

                            <div class="flex items-center gap-2">
                                <label
                                    for="settings-export-pdf-entity"
                                    class="pm-field-label mb-0"
                                >
                                    <span data-i18n="settings.export_pdf_review">{{ __('ui.settings.export_pdf_review') }}</span>
                                </label>

                                <select
                                    id="settings-export-pdf-entity"
                                    class="pm-input w-auto"
                                >
                                    <option value="parties" data-i18n="settings.entity_parties">{{ __('ui.settings.entity_parties') }}</option>
                                    <option value="buildings" data-i18n="settings.entity_buildings">{{ __('ui.settings.entity_buildings') }}</option>
                                    <option value="units" data-i18n="settings.entity_units">{{ __('ui.settings.entity_units') }}</option>
                                    <option value="leases" data-i18n="settings.entity_leases">{{ __('ui.settings.entity_leases') }}</option>
                                </select>

                                <button
                                    id="settings-export-pdf"
                                    type="button"
                                    class="pm-button-secondary"
                                >
                                    <span data-i18n="settings.format_pdf">{{ __('ui.settings.format_pdf') }}</span>
                                </button>
                            </div>
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

                            <input
                                id="settings-import-file"
                                type="file"
                                accept=".csv,.xlsx"
                                class="pm-input"
                            >
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

@endsection
