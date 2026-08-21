@extends('layouts.app')

@section('title', __('ui.parties.title'))
@section('title-i18n', 'parties.title')

@section('content')

<div class="pm-page pm-parties-page mx-auto max-w-[1600px]">

    {{-- Page header --}}
    <div
        class="
            mb-8 flex flex-col gap-5
            lg:flex-row lg:items-end lg:justify-between
        "
    >
        <div>
            <p class="text-sm font-medium text-[var(--pm-accent)]">
                <span data-i18n="parties.contacts_stakeholders">{{ __('ui.parties.contacts_stakeholders') }}</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="parties.heading">{{ __('ui.parties.heading') }}</span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="parties.page_description">{{ __('ui.parties.page_description') }}</span>
            </p>
        </div>

        <button
            id="add-party-button"
            type="button"
            class="pm-button-primary gap-2"
        >
            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
            </svg>

            <span data-i18n="parties.add_party">{{ __('ui.parties.add_party') }}</span>
        </button>
    </div>

    {{-- Page-level error --}}
    <div
        id="parties-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- Summary cards --}}
    <div
        class="
            mb-6 grid gap-4
            sm:grid-cols-2 xl:grid-cols-4
        "
    >
        <div
            class="pm-card p-5"
        >
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="parties.total_parties">{{ __('ui.parties.total_parties') }}</span>
            </div>

            <div
                id="parties-total-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div
            class="pm-card p-5"
        >
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="parties.people">{{ __('ui.parties.people') }}</span>
            </div>

            <div
                id="parties-person-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div
            class="pm-card p-5"
        >
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="parties.organisations">{{ __('ui.parties.organisations') }}</span>
            </div>

            <div
                id="parties-organisation-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div
            class="pm-card p-5"
        >
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="parties.multiple_roles">{{ __('ui.parties.multiple_roles') }}</span>
            </div>

            <div
                id="parties-multi-role-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- Party portfolio --}}
    <section
        class="pm-card overflow-hidden"
    >

        {{-- Filters --}}
        <div
            class="
                border-b border-[var(--pm-border-subtle)]
                px-5 py-4
            "
        >
            <div
                class="
                    flex flex-col gap-4
                    xl:flex-row
                    xl:items-end
                    xl:justify-between
                "
            >
                <div>
                    <h2
                        class="
                            text-base font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="parties.directory">{{ __('ui.parties.directory') }}</span>
                    </h2>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="parties.directory_description">{{ __('ui.parties.directory_description') }}</span>
                    </p>
                </div>

                <div
                    class="
                        grid w-full gap-3
                        sm:grid-cols-3
                        xl:w-auto
                    "
                >

                    {{-- Search --}}
                    <div class="sm:min-w-72">
                        <label
                            for="party-search"
                            class="sr-only"
                        >
                            <span data-i18n="parties.search">{{ __('ui.parties.search') }}</span>
                        </label>

                        <div class="relative">
                            <svg
                                class="
                                    pointer-events-none
                                    absolute left-3 top-1/2
                                    h-4 w-4
                                    -translate-y-1/2
                                    text-[var(--pm-text-subtle)]
                                "
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>

                            <input
                                id="party-search"
                                type="search"
                                data-i18n-placeholder="parties.search_placeholder"
                                placeholder="{{ __('ui.parties.search_placeholder') }}"
                                class="pm-input pl-9"
                            >
                        </div>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label
                            for="party-type-filter"
                            class="sr-only"
                        >
                            <span data-i18n="parties.party_type">{{ __('ui.parties.party_type') }}</span>
                        </label>

                        <select
                            id="party-type-filter"
                            class="pm-input"
                        >
                            <option
                                value=""
                                data-i18n="parties.all_types"
                            >
                                {{ __('ui.parties.all_types') }}
</option>

                            <option
                                value="person"
                                data-i18n="parties.people"
                            >
                                {{ __('ui.parties.people') }}
</option>

                            <option
                                value="organisation"
                                data-i18n="parties.organisations"
                            >
                                {{ __('ui.parties.organisations') }}
</option>

                            <option
                                value="association"
                                data-i18n="parties.associations"
                            >
                                {{ __('ui.parties.associations') }}
</option>
                        </select>
                    </div>

                    {{-- Role --}}
                    <div>
                        <label
                            for="party-role-filter"
                            class="sr-only"
                        >
                            <span data-i18n="parties.party_role">{{ __('ui.parties.party_role') }}</span>
                        </label>

                        <select
                            id="party-role-filter"
                            class="pm-input"
                        >
                            <option
                                value=""
                                data-i18n="parties.all_roles"
                            >
                                {{ __('ui.parties.all_roles') }}
</option>

                            <option
                                value="owner"
                                data-i18n="parties.owners"
                            >
                                {{ __('ui.parties.owners') }}
</option>

                            <option
                                value="tenant"
                                data-i18n="parties.tenants"
                            >
                                {{ __('ui.parties.tenants') }}
</option>

                            <option
                                value="agent"
                                data-i18n="parties.agents"
                            >
                                {{ __('ui.parties.agents') }}
</option>

                            <option
                                value="managing_organisation"
                                data-i18n="parties.managing_organisation"
                            >
                                {{ __('ui.parties.managing_organisation') }}
</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- Records --}}
        <div
            id="parties-list"
            class="p-5"
        >
            <div class="text-sm text-[var(--pm-text-subtle)]">
                <span data-i18n="parties.loading">{{ __('ui.parties.loading') }}</span>
            </div>
        </div>

        {{-- Pagination --}}
        <div
            id="parties-pagination"
            class="
                hidden border-t
                border-[var(--pm-border-subtle)]
                px-5 py-4
            "
        ></div>

    </section>
</div>

{{-- ================================================================
     Add / Edit Party Drawer
================================================================ --}}

<x-drawer
    id="party-modal"
    backdrop-id="party-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="party-modal-title"
        description-id="party-modal-description"
        close-id="party-modal-close"
        close-label="Close"
        close-label-key="parties.close"
    >
        <x-slot:title>
            <span data-i18n="parties.add_party">{{ __('ui.parties.add_party') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="parties.add_party_description">
                {{ __('ui.parties.add_party_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="party-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="pm-drawer-body">


                    {{-- Validation errors --}}
                    <div
                        id="party-form-error"
                        class="
                            mb-5 hidden rounded-lg
                            border border-[var(--pm-danger-border)]
                            bg-[var(--pm-danger-background)] px-4 py-3
                            text-sm text-[var(--pm-danger-text)]
                        "
                    ></div>

                    {{-- Type --}}
                    <section>
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="parties.party_type">{{ __('ui.parties.party_type') }}</span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="parties.party_type_description">{{ __('ui.parties.party_type_description') }}</span>
                            </p>
                        </div>

                        <select
                            id="party-type"
                            required
                            class="pm-input"
                        >
                            <option
                                value="person"
                                data-i18n="parties.person"
                            >
                                {{ __('ui.parties.person') }}
</option>

                            <option
                                value="organisation"
                                data-i18n="parties.organisation"
                            >
                                {{ __('ui.parties.organisation') }}
</option>

                            <option
                                value="association"
                                data-i18n="parties.association"
                            >
                                {{ __('ui.parties.association') }}
</option>
                        </select>
                    </section>

                    {{-- ===================================================
                         Person fields
                    ==================================================== --}}

                    <section
                        id="party-person-fields"
                        class="
                            mt-8 border-t
                            border-[var(--pm-border-subtle)] pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="parties.personal_details">{{ __('ui.parties.personal_details') }}</span>
                            </h3>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2
                            "
                        >
                            <div class="md:col-span-2">
                                <label
                                    for="party-name"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.full_name">{{ __('ui.parties.full_name') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-phone"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.phone">{{ __('ui.parties.phone') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-phone"
                                    type="text"
                                    maxlength="50"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-email"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.email">{{ __('ui.parties.email') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-email"
                                    type="email"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- ===================================================
                         Organisation / Association
                    ==================================================== --}}

                    <section
                        id="party-organisation-fields"
                        class="
                            mt-8 hidden
                            border-t border-[var(--pm-border-subtle)]
                            pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="parties.organisation_details">{{ __('ui.parties.organisation_details') }}</span>
                            </h3>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2
                            "
                        >
                            <div class="md:col-span-2">
                                <label
                                    for="party-legal-name"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.legal_name">{{ __('ui.parties.legal_name') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-legal-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    for="party-contact-name"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.contact_person">{{ __('ui.parties.contact_person') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-contact-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-contact-phone"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.contact_phone">{{ __('ui.parties.contact_phone') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-contact-phone"
                                    type="text"
                                    maxlength="50"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-contact-email"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.contact_email">{{ __('ui.parties.contact_email') }}</span>
                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="party-contact-email"
                                    type="email"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- ===================================================
                         Shared contact information
                    ==================================================== --}}

                    <section
                        class="
                            mt-8 border-t
                            border-[var(--pm-border-subtle)] pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="parties.contact_identification">{{ __('ui.parties.contact_identification') }}</span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="parties.contact_identification_description">{{ __('ui.parties.contact_identification_description') }}</span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2
                            "
                        >
                            <div>
                                <label
                                    for="party-alternate-phone"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.alternate_phone">{{ __('ui.parties.alternate_phone') }}</span>
                                </label>

                                <input
                                    id="party-alternate-phone"
                                    type="text"
                                    maxlength="50"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-id-number"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.id_number">{{ __('ui.parties.id_number') }}</span>
                                </label>

                                <input
                                    id="party-id-number"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-registration-number"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.registration_number">{{ __('ui.parties.registration_number') }}</span>
                                </label>

                                <input
                                    id="party-registration-number"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-vat-tin"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.vat_tin">{{ __('ui.parties.vat_tin') }}</span>
                                </label>

                                <input
                                    id="party-vat-tin"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    for="party-address"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.address">{{ __('ui.parties.address') }}</span>
                                </label>

                                <textarea
                                    id="party-address"
                                    rows="2"
                                    class="pm-input resize-y"
                                ></textarea>
                            </div>
                        </div>
                    </section>

                    {{-- ===================================================
                         Roles
                    ==================================================== --}}

                    <section
                        class="
                            mt-8 border-t
                            border-[var(--pm-border-subtle)] pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="parties.roles">{{ __('ui.parties.roles') }}</span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="parties.roles_description">{{ __('ui.parties.roles_description') }}</span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-3
                                sm:grid-cols-3
                            "
                        >
                            <label
                                class="
                                    flex cursor-pointer
                                    items-center gap-3
                                    rounded-xl border
                                    border-[var(--pm-border)]
                                    px-4 py-3
                                "
                            >
                                <input
                                    id="party-role-owner"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-[var(--pm-border-strong)]
                                        text-[var(--pm-accent)]
                                        focus:ring-patrimoine-500
                                    "
                                >

                                <span
                                    class="
                                        text-sm font-medium
                                        text-[var(--pm-text-secondary)]
                                    "
                                >
                                    <span data-i18n="parties.owner">{{ __('ui.parties.owner') }}</span>
                                </span>
                            </label>

                            <label
                                class="
                                    flex cursor-pointer
                                    items-center gap-3
                                    rounded-xl border
                                    border-[var(--pm-border)]
                                    px-4 py-3
                                "
                            >
                                <input
                                    id="party-role-tenant"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-[var(--pm-border-strong)]
                                        text-[var(--pm-accent)]
                                        focus:ring-patrimoine-500
                                    "
                                >

                                <span
                                    class="
                                        text-sm font-medium
                                        text-[var(--pm-text-secondary)]
                                    "
                                >
                                    <span data-i18n="parties.tenant">{{ __('ui.parties.tenant') }}</span>
                                </span>
                            </label>

                            <label
                                class="
                                    flex cursor-pointer
                                    items-center gap-3
                                    rounded-xl border
                                    border-[var(--pm-border)]
                                    px-4 py-3
                                "
                            >
                                <input
                                    id="party-role-agent"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-[var(--pm-border-strong)]
                                        text-[var(--pm-accent)]
                                        focus:ring-patrimoine-500
                                    "
                                >

                                <span
                                    class="
                                        text-sm font-medium
                                        text-[var(--pm-text-secondary)]
                                    "
                                >
                                    <span data-i18n="parties.agent">{{ __('ui.parties.agent') }}</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    {{-- ===================================================
                         Banking details
                    ==================================================== --}}

                    <section
                        class="
                            mt-8 border-t
                            border-[var(--pm-border-subtle)] pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="parties.banking_details">{{ __('ui.parties.banking_details') }}</span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="parties.banking_description">{{ __('ui.parties.banking_description') }}</span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2
                            "
                        >
                            <div>
                                <label
                                    for="party-bank-name"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.bank_name">{{ __('ui.parties.bank_name') }}</span>
                                </label>

                                <input
                                    id="party-bank-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-bank-branch"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.bank_branch">{{ __('ui.parties.bank_branch') }}</span>
                                </label>

                                <input
                                    id="party-bank-branch"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-bank-account-name"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.account_name">{{ __('ui.parties.account_name') }}</span>
                                </label>

                                <input
                                    id="party-bank-account-name"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="party-bank-account-number"
                                    class="pm-field-label"
                                >
                                    <span data-i18n="parties.account_number">{{ __('ui.parties.account_number') }}</span>
                                </label>

                                <input
                                    id="party-bank-account-number"
                                    type="text"
                                    maxlength="255"
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- Notes --}}
                    <section
                        class="
                            mt-8 border-t
                            border-[var(--pm-border-subtle)] pt-7
                        "
                    >
                        <label
                            for="party-notes"
                            class="pm-field-label"
                        >
                            <span data-i18n="parties.notes">{{ __('ui.parties.notes') }}</span>
                        </label>

                        <textarea
                            id="party-notes"
                            rows="4"
                            data-i18n-placeholder="parties.notes_placeholder"
                            placeholder="{{ __('ui.parties.notes_placeholder') }}"
                            class="pm-input resize-y"
                        ></textarea>
                    </section>
        </div>

        <x-drawer-footer>
            <button
                id="party-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
            </button>

            <button
                id="party-submit-button"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="actions.save">
                    {{ __('ui.actions.save') }}
</span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

@endsection
