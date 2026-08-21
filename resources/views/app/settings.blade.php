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

    {{-- V1.0.7 About --}}
    <section class="pm-card mb-7">
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
                    <span data-i18n="settings.about">About</span>
                </h2>

                <p
                    class="
                        mt-1 text-sm
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="settings.application_version">Application version</span>:
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
                class="
                    text-sm font-medium
                    text-[var(--pm-accent)]
                    hover:underline
                "
            >
                <span data-i18n="settings.view_update_log">View update log</span>
            </a>
        </div>
    </section>

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
                <section class="pm-card">
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
                <section class="pm-card">
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
                <section class="pm-card">
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

                {{-- Language and currency --}}
                <section class="pm-card">
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
                                    <option value="{{ $code }}">
                                        {{ $definition['name'] ?? $code }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="settings.currency_help">{{ __('ui.settings.currency_help') }}</span>
                            </p>
                        </div>
                    </div>
                </section>


                {{-- Financial defaults --}}
                <section class="pm-card">
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

                            <input
                                id="organisation-default-vat-rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                required
                                value="18"
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





                {{-- Banking --}}
                <section class="pm-card">
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
                <section class="pm-card pm-settings-notes-card">
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

    {{--
        V1.0.7 Registry backup & restore.

        Administrator-only, like the rest of Settings. Only Registry data
        (parties, buildings, units, leases) is portable — financial
        history is immutable and is never part of a backup.
    --}}
    <section
        id="settings-backup-section"
        data-requires-capability="manage_settings"
        class="rbac-hidden mt-8"
    >
        <div class="pm-card">
            <h2
                class="
                    text-base font-semibold
                    text-[var(--pm-text)]
                "
            >
                <span data-i18n="settings.backup_restore">Data backup &amp; restore</span>
            </h2>

            <p
                class="
                    mt-1 text-sm leading-6
                    text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="settings.backup_restore_description">Export the Registry as restorable backup files, or restore a previous backup.</span>
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
                <span data-i18n="settings.backup_financial_note">Financial history (payments, invoices, journal and funds) is not part of backups. It cannot be exported or restored here.</span>
            </div>

            {{-- Export --}}
            <div class="mt-6">
                <h3
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="settings.export_heading">Export</span>
                </h3>

                <div
                    class="
                        mt-3 grid gap-3
                        sm:grid-cols-2
                    "
                >
                    @foreach([
                        'parties' => 'Parties',
                        'buildings' => 'Buildings',
                        'units' => 'Units',
                        'leases' => 'Leases',
                    ] as $entity => $label)
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
                            >{{ $label }}</span>

                            <span class="flex shrink-0 gap-2">
                                <button
                                    type="button"
                                    data-registry-export
                                    data-entity="{{ $entity }}"
                                    data-format="csv"
                                    class="pm-button-secondary px-3 py-2 text-xs"
                                >
                                    CSV
                                </button>

                                <button
                                    type="button"
                                    data-registry-export
                                    data-entity="{{ $entity }}"
                                    data-format="xlsx"
                                    class="pm-button-secondary px-3 py-2 text-xs"
                                >
                                    XLSX
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
                        <span data-i18n="settings.export_full">Full backup (XLSX)</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <label
                            for="settings-export-pdf-entity"
                            class="pm-field-label mb-0"
                        >
                            <span data-i18n="settings.export_pdf_review">PDF review</span>
                        </label>

                        <select
                            id="settings-export-pdf-entity"
                            class="pm-input w-auto"
                        >
                            <option value="parties" data-i18n="settings.entity_parties">Parties</option>
                            <option value="buildings" data-i18n="settings.entity_buildings">Buildings</option>
                            <option value="units" data-i18n="settings.entity_units">Units</option>
                            <option value="leases" data-i18n="settings.entity_leases">Leases</option>
                        </select>

                        <button
                            id="settings-export-pdf"
                            type="button"
                            class="pm-button-secondary"
                        >
                            PDF
                        </button>
                    </div>
                </div>
            </div>

            {{-- Import / restore --}}
            <div
                class="
                    mt-7 border-t
                    border-[var(--pm-border)]
                    pt-6
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="settings.import_heading">Import / restore</span>
                </h3>

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
                            <span data-i18n="settings.import_file">Backup file</span>
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
                            <span data-i18n="settings.import_entity">Data set</span>
                        </label>

                        <select
                            id="settings-import-entity"
                            class="pm-input"
                        >
                            <option value="parties" data-i18n="settings.entity_parties">Parties</option>
                            <option value="buildings" data-i18n="settings.entity_buildings">Buildings</option>
                            <option value="units" data-i18n="settings.entity_units">Units</option>
                            <option value="leases" data-i18n="settings.entity_leases">Leases</option>
                            <option value="full" data-i18n="settings.entity_full">Full backup (all entities)</option>
                        </select>
                    </div>
                </div>

                <div
                    class="
                        mt-4 flex flex-col gap-3
                        sm:flex-row sm:items-center
                        sm:justify-between
                    "
                >
                    <label
                        class="
                            flex items-center gap-3
                            rounded-lg border
                            border-[var(--pm-border)]
                            bg-[var(--pm-surface-subtle)]
                            px-4 py-3
                        "
                    >
                        <input
                            id="settings-import-dry-run"
                            type="checkbox"
                            checked
                            class="
                                h-4 w-4 rounded
                                border-[var(--pm-border-strong)]
                            "
                        >

                        <span>
                            <span
                                class="
                                    block text-sm font-medium
                                    text-[var(--pm-text)]
                                "
                                data-i18n="settings.dry_run"
                            >
                                Dry run (validate without saving)
                            </span>

                            <span
                                class="
                                    mt-0.5 block text-xs
                                    text-[var(--pm-text-muted)]
                                "
                                data-i18n="settings.dry_run_help"
                            >
                                Runs the full import and reports the result without changing any data.
                            </span>
                        </span>
                    </label>

                    <button
                        id="settings-import-run"
                        type="button"
                        class="pm-button-primary shrink-0"
                    >
                        <span data-i18n="settings.run_import">Run import</span>
                    </button>
                </div>

                <div
                    id="settings-import-result"
                    class="
                        mt-4 hidden rounded-lg
                        border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)]
                        px-4 py-4 text-sm
                    "
                ></div>
            </div>
        </div>
    </section>

</div>

@endsection
