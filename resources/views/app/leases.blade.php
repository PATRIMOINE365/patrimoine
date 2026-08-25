@extends('layouts.app')

@section('title', __('ui.leases.title'))
@section('title-i18n', 'leases.title')

@section('content')

<div class="pm-leases-page pm-page mx-auto max-w-[1600px]">

    {{-- ============================================================
         Page Header
    ============================================================ --}}

    <div
        class="
            mb-8 flex flex-col gap-5
            lg:flex-row lg:items-end lg:justify-between
        "
    >
        <div>
            <p
                class="
                    text-sm font-medium
                    text-[var(--pm-accent)]
                "
            >
                <span data-i18n="leases.tenancy">
                    {{ __('ui.leases.tenancy') }}
                </span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="leases.heading">
                    {{ __('ui.leases.heading') }}
                </span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="leases.page_description">
                    {{ __('ui.leases.page_description') }}
                </span>
            </p>
        </div>

        <button
            id="add-lease-button"
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

            <span data-i18n="leases.add_lease">
                {{ __('ui.leases.add_lease') }}
            </span>
        </button>
    </div>

    {{-- Page-level API Error --}}

    <div
        id="leases-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- ============================================================
         Summary
    ============================================================ --}}

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
                <span data-i18n="leases.total_leases">
                    {{ __('ui.leases.total_leases') }}
                </span>
            </div>

            <div
                id="leases-total-count"
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
                <span data-i18n="leases.status_active">
                    {{ __('ui.leases.status_active') }}
                </span>
            </div>

            <div
                id="leases-active-count"
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
                <span data-i18n="leases.in_notice">
                    {{ __('ui.leases.in_notice') }}
                </span>
            </div>

            <div
                id="leases-notice-count"
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
                <span data-i18n="leases.status_draft">
                    {{ __('ui.leases.status_draft') }}
                </span>
            </div>

            <div
                id="leases-draft-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- ============================================================
         Lease Register
    ============================================================ --}}

    <section
        class="pm-card"
    >
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
                        <span data-i18n="leases.register">
                            {{ __('ui.leases.register') }}
                        </span>
                    </h2>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="leases.register_description">
                            {{ __('ui.leases.register_description') }}
                        </span>
                    </p>
                </div>

                <div
                    class="
                        grid w-full gap-3
                        sm:grid-cols-2
                        lg:grid-cols-3
                        xl:grid-cols-5
                        xl:w-auto
                    "
                >
                    <div class="sm:min-w-40">
                        <label
                            for="lease-status-filter"
                            class="sr-only"
                        >
                            <span data-i18n="leases.lease_status">
                                {{ __('ui.leases.lease_status') }}
                            </span>
                        </label>

                        <select
                            id="lease-status-filter"
                            class="pm-input"
                        >
                            <option
                                        value=""
                                        data-i18n="leases.all_statuses"
                                    >
                                        {{ __('ui.leases.all_statuses') }}
                                    </option>

                            <option
                                        value="draft"
                                        data-i18n="leases.status_draft"
                                    >
                                        {{ __('ui.leases.status_draft') }}
                                    </option>

                            <option
                                        value="active"
                                        data-i18n="leases.status_active"
                                    >
                                        {{ __('ui.leases.status_active') }}
                                    </option>

                            <option
                                        value="notice"
                                        data-i18n="leases.status_notice"
                                    >
                                        {{ __('ui.leases.status_notice') }}
                                    </option>

                            <option
                                        value="terminated"
                                        data-i18n="leases.status_terminated"
                                    >
                                        {{ __('ui.leases.status_terminated') }}
                                    </option>
                        </select>
                    </div>

                    <div class="sm:min-w-48">
                        <label
                            for="lease-tenant-filter"
                            class="sr-only"
                        >
                            <span data-i18n="leases.tenant">
                                {{ __('ui.leases.tenant') }}
                            </span>
                        </label>

                        <select
                            id="lease-tenant-filter"
                            class="pm-input"
                        >
                            <option
                                        value=""
                                        data-i18n="leases.all_tenants"
                                    >
                                        {{ __('ui.leases.all_tenants') }}
                                    </option>
                        </select>
                    </div>

                    <div class="sm:min-w-48">
                        <label
                            for="lease-building-filter"
                            class="sr-only"
                        >
                            <span data-i18n="leases.building">
                                Building
                            </span>
                        </label>

                        <select
                            id="lease-building-filter"
                            class="pm-input"
                        >
                            <option
                                value=""
                                data-i18n="leases.all_buildings"
                            >
                                All buildings
                            </option>
                        </select>
                    </div>

                    <div class="sm:min-w-40">
                        <label
                            for="lease-frequency-filter"
                            class="sr-only"
                        >
                            <span data-i18n="leases.payment_frequency">
                                {{ __('ui.leases.payment_frequency') }}
                            </span>
                        </label>

                        <select
                            id="lease-frequency-filter"
                            class="pm-input"
                        >
                            <option
                                value=""
                                data-i18n="leases.all_frequencies"
                            >
                                All frequencies
                            </option>

                            <option
                                value="monthly"
                                data-i18n="leases.monthly"
                            >
                                {{ __('ui.leases.monthly') }}
                            </option>

                            <option
                                value="quarterly"
                                data-i18n="leases.quarterly"
                            >
                                {{ __('ui.leases.quarterly') }}
                            </option>

                            <option
                                value="bi_yearly"
                                data-i18n="leases.bi_yearly"
                            >
                                {{ __('ui.leases.bi_yearly') }}
                            </option>

                            <option
                                value="yearly"
                                data-i18n="leases.yearly"
                            >
                                {{ __('ui.leases.yearly') }}
                            </option>
                        </select>
                    </div>

                    <div class="sm:min-w-40">
                        <label
                            for="lease-ending-before-filter"
                            class="sr-only"
                        >
                            <span data-i18n="leases.expiring_before">
                                Expiring before
                            </span>
                        </label>

                        <div class="pm-lease-date-control">
                            <input
                                id="lease-ending-before-filter"
                                data-lease-date-input
                                data-pm-date-input
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                type="text"
                                class="pm-input"
                            >

                            <button
                                type="button"
                                class="pm-lease-date-picker-button"
                                data-lease-date-picker="lease-ending-before-filter"
                                aria-label="Choose date"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="16"
                                        rx="2"
                                    />
                                    <path d="M16 3v4"/>
                                    <path d="M8 3v4"/>
                                    <path d="M3 11h18"/>
                                </svg>
                            </button>

                            <input
                                id="lease-ending-before-filter-picker"
                                type="date"
                                class="pm-lease-native-date-picker"
                                tabindex="-1"
                                aria-hidden="true"
                                data-lease-native-date-picker="lease-ending-before-filter"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="leases-list"
            class="p-5"
        >
            <div class="text-sm text-[var(--pm-text-subtle)]">
                <span data-i18n="leases.loading">
                    {{ __('ui.leases.loading') }}
                </span>
            </div>
        </div>

        <div
            id="leases-pagination"
            class="
                hidden border-t
                border-[var(--pm-border-subtle)]
                px-5 py-4
            "
        ></div>
    </section>

</div>


{{-- ================================================================
     Add / Edit Lease Drawer
================================================================ --}}

<x-drawer
    id="lease-modal"
    backdrop-id="lease-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="lease-modal-title"
        description-id="lease-modal-description"
        close-id="lease-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.add_lease">
                {{ __('ui.leases.add_lease') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.add_description">
                {{ __('ui.leases.add_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="lease-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="pm-lease-drawer-body">


                    <div
                        id="lease-form-error"
                        class="
                            mb-5 hidden rounded-lg
                            border border-[var(--pm-danger-border)]
                            bg-[var(--pm-danger-background)] px-4 py-3
                            text-sm text-[var(--pm-danger-text)]
                        "
                    ></div>

                    {{-- =================================================
                         Property / Tenant
                    ================================================= --}}

                    <section>
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="leases.property_tenant">
                                    {{ __('ui.leases.property_tenant') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="leases.property_tenant_description">
                                    {{ __('ui.leases.property_tenant_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2
                            "
                        >


<div class="md:col-span-2">
    <label
        for="lease-unit-search"
        class="pm-field-label flex items-center gap-1.5"
    >
        <span data-i18n="leases.property_unit">
            {{ __('ui.leases.property_unit') }}
        </span>

        <x-field-help
                                        label="About Property and Unit"
                                        text-key="leases.property_unit_help_text"
                                        data-i18n-aria-label="leases.property_unit_help_label"
                                    >
            Search for the specific leasable Unit covered by this agreement.
            A Unit inherits the ownership of its Building and cannot have
            more than one Active or Notice Lease at the same time.
        </x-field-help>

        <span class="text-[var(--pm-danger-text)]">*</span>
    </label>

    <div
        id="lease-unit-picker"
        class="relative"
    >
        {{-- Actual Unit ID submitted to the API. --}}
        <input
            id="lease-unit"
            type="hidden"
        >

        <div class="relative">
            <svg
                class="
                    pointer-events-none
                    absolute left-3.5 top-1/2
                    h-4 w-4
                    -translate-y-1/2
                    text-[var(--pm-text-subtle)]
                "
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>

            <input
                id="lease-unit-search"
                type="search"
                autocomplete="off"
                data-i18n-placeholder="leases.unit_search_placeholder"
                placeholder="{{ __('ui.leases.unit_search_placeholder') }}"
                class="pm-input pm-input-search pm-input-search-clearable"
            >

            <button
                id="lease-unit-clear"
                type="button"
                aria-label="Clear selected Unit"
                class="
                    absolute right-2 top-1/2
                    inline-flex h-7 w-7
                    -translate-y-1/2
                    items-center justify-center
                    rounded-md
                    text-[var(--pm-text-subtle)]
                    transition
                    hover:bg-[var(--pm-hover)]
                    hover:text-[var(--pm-text-secondary)]
                "

                    data-i18n-aria-label="leases.clear_selected_unit">
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>

        <div
            id="lease-unit-results"
            class="pm-card absolute z-50 mt-1 hidden max-h-80 w-full overflow-y-auto shadow-xl"
        ></div>
    </div>

    {{-- Visual confirmation after Unit selection. --}}
    <div
        id="lease-unit-selection"
        class="
            mt-3 hidden
            rounded-xl border
            border-[var(--pm-border)]
            bg-[var(--pm-selected)]
            p-4
        "
    >
        <div
            class="
                text-[11px] font-semibold
                uppercase tracking-[0.12em]
                text-[var(--pm-accent)]
            "
        >
            <span data-i18n="leases.selected_unit">
                {{ __('ui.leases.selected_unit') }}
            </span>
        </div>

        <div
            id="lease-selected-unit-name"
            class="
                mt-1 text-sm font-semibold
                text-[var(--pm-text)]
            "
        ></div>

        <div
            id="lease-selected-unit-location"
            class="
                mt-1 text-xs
                text-[var(--pm-text-muted)]
            "
        ></div>

        <div
            class="
                mt-4 text-[11px]
                font-semibold uppercase
                tracking-[0.12em]
                text-[var(--pm-text-muted)]
            "
        >
            <span data-i18n="leases.ownership">
                {{ __('ui.leases.ownership') }}
            </span>
        </div>

        <div
            id="lease-unit-owners"
            class="
                mt-2 flex flex-wrap
                gap-2
            "
        ></div>
    </div>
</div>


                            <div>
                                <label
                                    for="lease-tenant"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.tenant">
                                        {{ __('ui.leases.tenant') }}
                                    </span>

                                    <x-field-help
                                        label="About Tenant"
                                        text-key="leases.tenant_help_text"
                                        data-i18n-aria-label="leases.tenant_help_label"
                                    >
                                        The Party renting this unit. Patrimoine V1 supports exactly one
                                        tenant per lease. The selected Party must have the Tenant role.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <div
                                    id="lease-tenant-picker"
                                    class="relative"
                                >
                                    {{-- Selected Party ID submitted to the API. --}}
                                    <input
                                        id="lease-tenant"
                                        type="hidden"
                                    >

                                    <div class="relative">
                                        <svg
                                            class="
                                                pointer-events-none
                                                absolute left-3.5 top-1/2
                                                h-4 w-4
                                                -translate-y-1/2
                                                text-[var(--pm-text-subtle)]
                                            "
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="11" cy="11" r="7"/>
                                            <path d="m20 20-3.5-3.5"/>
                                        </svg>

                                        <input
                                            id="lease-tenant-search"
                                            type="search"
                                            autocomplete="off"
                                            data-i18n-placeholder="leases.tenant_search_placeholder"
                                            placeholder="{{ __('ui.leases.tenant_search_placeholder') }}"
                                            class="pm-input pm-input-search pm-input-search-clearable"
                                        >

                                        <button
                                            id="lease-tenant-clear"
                                            type="button"
                                            aria-label="Clear selected Tenant"
                                            class="
                                                absolute right-2 top-1/2
                                                inline-flex h-7 w-7
                                                -translate-y-1/2
                                                items-center justify-center
                                                rounded-md
                                                text-[var(--pm-text-subtle)]
                                                transition
                                                hover:bg-[var(--pm-hover)]
                                                hover:text-[var(--pm-text-secondary)]
                                            "
                                            data-i18n-aria-label="leases.clear_selected_tenant">
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M18 6 6 18"/>
                                                <path d="m6 6 12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        id="lease-tenant-results"
                                        class="pm-card absolute z-50 mt-1 hidden max-h-80 w-full overflow-y-auto shadow-xl"
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <label
                                    for="lease-agent"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.agent">
                                        {{ __('ui.leases.agent') }}
                                    </span>

                                    <x-field-help
                                        label="About Agent"
                                        text-key="leases.agent_help_text"
                                        data-i18n-aria-label="leases.agent_help_label"
                                    >
                                        Optional Party that facilitated or manages this lease transaction.
                                        If an Agent Commission is greater than zero, an Agent must be selected.
                                        The selected Party must have the Agent role.
                                    </x-field-help>
                                </label>

                                <div
                                    id="lease-agent-picker"
                                    class="relative"
                                >
                                    {{-- Selected Party ID submitted to the API. --}}
                                    <input
                                        id="lease-agent"
                                        type="hidden"
                                    >

                                    <div class="relative">
                                        <svg
                                            class="
                                                pointer-events-none
                                                absolute left-3.5 top-1/2
                                                h-4 w-4
                                                -translate-y-1/2
                                                text-[var(--pm-text-subtle)]
                                            "
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="11" cy="11" r="7"/>
                                            <path d="m20 20-3.5-3.5"/>
                                        </svg>

                                        <input
                                            id="lease-agent-search"
                                            type="search"
                                            autocomplete="off"
                                            data-i18n-placeholder="leases.agent_search_placeholder"
                                            placeholder="{{ __('ui.leases.agent_search_placeholder') }}"
                                            class="pm-input pm-input-search pm-input-search-clearable"
                                        >

                                        <button
                                            id="lease-agent-clear"
                                            type="button"
                                            aria-label="Clear selected Agent"
                                            class="
                                                absolute right-2 top-1/2
                                                inline-flex h-7 w-7
                                                -translate-y-1/2
                                                items-center justify-center
                                                rounded-md
                                                text-[var(--pm-text-subtle)]
                                                transition
                                                hover:bg-[var(--pm-hover)]
                                                hover:text-[var(--pm-text-secondary)]
                                            "
                                            data-i18n-aria-label="leases.clear_selected_agent">
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M18 6 6 18"/>
                                                <path d="m6 6 12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        id="lease-agent-results"
                                        class="pm-card absolute z-50 mt-1 hidden max-h-80 w-full overflow-y-auto shadow-xl"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- =================================================
                         Lease Period
                    ================================================= --}}

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
                                <span data-i18n="leases.lease_period">
                                    {{ __('ui.leases.lease_period') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="leases.lease_period_description">
                                    {{ __('ui.leases.lease_period_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2 xl:grid-cols-4
                            "
                        >
                            <div>
                                <label
                                    for="lease-start-date"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.start_date">
                                        {{ __('ui.leases.start_date') }}
                                    </span>

                                    <x-field-help
                                        label="About Start Date"
                                        text-key="leases.start_date_help_text"
                                        data-i18n-aria-label="leases.start_date_help_label"
                                    >
                                        The date the lease begins. Unless a Due Day Override is specified,
                                        Patrimoine uses the day of this date as the recurring rent due day.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <div class="pm-lease-date-control">
<input
                                    id="lease-start-date"
                                    data-lease-date-input
                                    data-pm-date-input
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
                                    required
                                    class="pm-input"
                                >
    <button
        type="button"
        class="pm-lease-date-picker-button"
        data-lease-date-picker="lease-start-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect
                x="3"
                y="5"
                width="18"
                height="16"
                rx="2"
            />
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        id="lease-start-date-picker"
        type="date"
        class="pm-lease-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-lease-native-date-picker="lease-start-date"
    >
</div>
                            </div>


                            <div class="md:col-span-2 xl:col-span-4">
                                <span class="pm-field-label">
                                    <span data-i18n="leases.duration">
                                        {{ __('ui.leases.duration') }}
                                    </span>
                                </span>

                                <div
                                    id="lease-duration-chips"
                                    class="mt-1 flex flex-wrap gap-2"
                                >
                                    @foreach ([
                                        '3m' => 'duration_3m',
                                        '6m' => 'duration_6m',
                                        '1y' => 'duration_1y',
                                        '2y' => 'duration_2y',
                                        '3y' => 'duration_3y',
                                        '4y' => 'duration_4y',
                                        '5y' => 'duration_5y',
                                        'custom' => 'duration_custom',
                                    ] as $value => $key)
                                        <button
                                            type="button"
                                            data-lease-duration="{{ $value }}"
                                            aria-pressed="false"
                                            class="pm-duration-chip"
                                            data-i18n="leases.{{ $key }}"
                                        >
                                            {{ __('ui.leases.' . $key) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label
                                    for="lease-end-date"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.end_date">
                                        {{ __('ui.leases.end_date') }}
                                    </span>

                                    <x-field-help
                                        label="About End Date"
                                        text-key="leases.end_date_help_text"
                                        data-i18n-aria-label="leases.end_date_help_label"
                                    >
                                        Optional contractual end date. Leave this blank for a lease without
                                        a predetermined termination date.
                                    </x-field-help>
                                </label>

                                <div class="pm-lease-date-control">
<input
                                    id="lease-end-date"
                                    data-lease-date-input
                                    data-pm-date-input
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
                                    class="pm-input"
                                >
    <button
        type="button"
        class="pm-lease-date-picker-button"
        data-lease-date-picker="lease-end-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect
                x="3"
                y="5"
                width="18"
                height="16"
                rx="2"
            />
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        id="lease-end-date-picker"
        type="date"
        class="pm-lease-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-lease-native-date-picker="lease-end-date"
    >
</div>
                            </div>

                            <div>
                                <label
                                    for="lease-status"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.status">
                                        {{ __('ui.leases.status') }}
                                    </span>

                                    <x-field-help
                                        label="About Lease Status"
                                        text-key="leases.status_help_text"
                                        data-i18n-aria-label="leases.status_help_label"
                                    >
                                        Draft means the lease is prepared but not yet in force.
                                        Active means the tenancy is currently running.
                                        Notice means termination notice has been recorded.
                                        Terminated means the lease has ended.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <select
                                    id="lease-status"
                                    required
                                    class="pm-input"
                                >
                                    <option
                                        value="draft"
                                        data-i18n="leases.status_draft"
                                    >
                                        {{ __('ui.leases.status_draft') }}
                                    </option>

                                    <option
                                        value="active"
                                        data-i18n="leases.status_active"
                                    >
                                        {{ __('ui.leases.status_active') }}
                                    </option>

                                    <option
                                        value="notice"
                                        data-i18n="leases.status_notice"
                                    >
                                        {{ __('ui.leases.status_notice') }}
                                    </option>

                                    <option
                                        value="terminated"
                                        data-i18n="leases.status_terminated"
                                    >
                                        {{ __('ui.leases.status_terminated') }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    for="lease-notice-date"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.notice_date">
                                        {{ __('ui.leases.notice_date') }}
                                    </span>

                                    <x-field-help
                                        label="About Notice Date"
                                        text-key="leases.notice_date_help_text"
                                        data-i18n-aria-label="leases.notice_date_help_label"
                                    >
                                        The date termination notice was received or issued.
                                        This field becomes required when the Lease Status is Notice and
                                        will later control when Rent Reserve consumption begins.
                                    </x-field-help>
                                </label>

                                <div
                                    id="lease-notice-chips"
                                    class="mb-2 flex flex-wrap gap-2"
                                >
                                    @foreach ([
                                        '1m' => 'notice_1m',
                                        '3m' => 'notice_3m',
                                        '6m' => 'notice_6m',
                                        'custom' => 'duration_custom',
                                    ] as $value => $key)
                                        <button
                                            type="button"
                                            data-lease-notice="{{ $value }}"
                                            aria-pressed="false"
                                            class="pm-duration-chip"
                                            data-i18n="leases.{{ $key }}"
                                        >
                                            {{ __('ui.leases.' . $key) }}
                                        </button>
                                    @endforeach
                                </div>

                                <div class="pm-lease-date-control">
<input
                                    id="lease-notice-date"
                                    data-lease-date-input
                                    data-pm-date-input
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
                                    class="pm-input"
                                >
    <button
        type="button"
        class="pm-lease-date-picker-button"
        data-lease-date-picker="lease-notice-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect
                x="3"
                y="5"
                width="18"
                height="16"
                rx="2"
            />
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        id="lease-notice-date-picker"
        type="date"
        class="pm-lease-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-lease-native-date-picker="lease-notice-date"
    >
</div>
                            </div>
                        </div>
                    </section>

                    {{-- =================================================
                         Rent Terms
                    ================================================= --}}

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
                                <span data-i18n="leases.rent_terms">
                                    {{ __('ui.leases.rent_terms') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="leases.rent_terms_description">
                                    {{ __('ui.leases.rent_terms_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-2 xl:grid-cols-4
                            "
                        >
                            <div>
                                <label
                                    for="lease-rent-amount"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.monthly_rent">
                                        {{ __('ui.leases.monthly_rent') }}
                                    </span>

                                    <x-field-help
                                        label="About Monthly Rent"
                                        text-key="leases.monthly_rent_help_text"
                                        data-i18n-aria-label="leases.monthly_rent_help_label"
                                    >
                                    The VAT-inclusive monthly contractual rent for the Unit.
                                    Payment Frequency determines how many months are invoiced together.
                                    For example, a Monthly Rent of 5,000 with Quarterly frequency creates
                                    a 15,000 rent obligation for each quarterly billing period.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="lease-rent-amount"
                                    type="text"
                                        inputmode="numeric"
                                        data-money-input
                                    required
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="lease-frequency"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.payment_frequency">
                                        {{ __('ui.leases.payment_frequency') }}
                                    </span>

                                    <x-field-help
                                        label="About Payment Frequency"
                                        text-key="leases.payment_frequency_help_text"
                                        data-i18n-aria-label="leases.payment_frequency_help_label"
                                    >
                                        Controls how often the Monthly Rent becomes due:
                                        Monthly, Quarterly, every six months, or Yearly.
                                        The Monthly Rent represents one complete period of the selected frequency.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <select
                                    id="lease-frequency"
                                    required
                                    class="pm-input"
                                >
                                    <option
                                        value="monthly"
                                        data-i18n="leases.monthly"
                                    >
                                        {{ __('ui.leases.monthly') }}
                                    </option>

                                    <option
                                        value="quarterly"
                                        data-i18n="leases.quarterly"
                                    >
                                        {{ __('ui.leases.quarterly') }}
                                    </option>

                                    <option
                                        value="bi_yearly"
                                        data-i18n="leases.bi_yearly"
                                    >
                                        {{ __('ui.leases.bi_yearly') }}
                                    </option>

                                    <option
                                        value="yearly"
                                        data-i18n="leases.yearly"
                                    >
                                        {{ __('ui.leases.yearly') }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    for="lease-due-day"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.due_day_override">
                                        {{ __('ui.leases.due_day_override') }}
                                    </span>

                                    <x-field-help
                                        label="About Due Day Override"
                                        text-key="leases.due_day_help_text"
                                        data-i18n-aria-label="leases.due_day_help_label"
                                    >
                                        Leave blank to use the day of the Lease Start Date as the rent due day.
                                        For example, a lease starting on the 15th will normally be due on
                                        the 15th. Enter another day here to override that rule.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-due-day"
                                    type="number"
                                    min="1"
                                    max="31"
                                    step="1"
                                    data-i18n-placeholder="leases.from_start_date"
                                    placeholder="{{ __('ui.leases.from_start_date') }}"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="lease-vat-rate"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.vat_rate">
                                        {{ __('ui.leases.vat_rate') }}
                                    </span>

                                    <x-field-help
                                        label="About VAT Rate"
                                        text-key="leases.vat_rate_help_text"
                                        data-i18n-aria-label="leases.vat_rate_help_label"
                                    >
                                        Patrimoine treats configured Monthly Rent as VAT inclusive.
                                        The default rate is 18%, but this lease may use another rate,
                                        including 0% where applicable.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="lease-vat-rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="18"
                                    required
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="lease-proration"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.proration_override">
                                        {{ __('ui.leases.proration_override') }}
                                    </span>

                                    <x-field-help
                                        label="About Proration Override"
                                        text-key="leases.proration_help_text"
                                        data-i18n-aria-label="leases.proration_help_label"
                                    >
                                        Leave blank to let Patrimoine calculate the prorated amount
                                        automatically for a partial billing period.
                                        Enter 0 to deliberately charge no proration.
                                        Any other amount replaces the automatic calculation.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-proration"
                                    type="number"
                                    min="0"
                                    step="1"
                                    data-i18n-placeholder="leases.automatic"
                                    placeholder="{{ __('ui.leases.automatic') }}"
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="lease-security-deposit"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.security_deposit">
                                        {{ __('ui.leases.security_deposit') }}
                                    </span>

                                    <x-field-help
                                        label="About Security Deposit"
                                        text-key="leases.security_deposit_help_text"
                                        data-i18n-aria-label="leases.security_deposit_help_label"
                                    >
                                        The contractual security deposit required from the tenant.
                                        It is held separately from rent and may later be reduced by
                                        itemized deductions before any remaining balance is refunded.
                                    </x-field-help>

                                    <span class="text-[var(--pm-danger-text)]">*</span>
                                </label>

                                <input
                                    id="lease-security-deposit"
                                    type="text"
                                        inputmode="numeric"
                                        data-money-input
                                    value="0"
                                    required
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>


                    {{-- =================================================
                        Advance Payment
                    ================================================= --}}

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
                                <span data-i18n="leases.advance_payment">
                                    {{ __('ui.leases.advance_payment') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="leases.advance_payment_description">
                                    {{ __('ui.leases.advance_payment_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-3
                            "
                        >
                            <div>
                                <label
                                    for="lease-advance-payment"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.total_advance_payment">
                                        {{ __('ui.leases.total_advance_payment') }}
                                    </span>

                                    <x-field-help
                                        label="About Advance Payment"
                                        text-key="leases.advance_payment_help_text"
                                        data-i18n-aria-label="leases.advance_payment_help_label"
                                    >
                                        Total advance rent contractually expected from the Tenant.
                                        This records the Lease agreement only. It does not mean
                                        Patrimoine has actually received the money. Actual funds
                                        are recorded later through Payments.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-advance-payment"
                                    type="text"
                                        inputmode="numeric"
                                        data-money-input
                                    value="0"
                                    required
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    for="lease-rent-reserve"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.rent_reserve">
                                        {{ __('ui.leases.rent_reserve') }}
                                    </span>

                                    <x-field-help
                                        label="About Rent Reserve"
                                        text-key="leases.rent_reserve_help_text"
                                        data-i18n-aria-label="leases.rent_reserve_help_label"
                                    >
                                        Portion of the contractual Advance Payment that should
                                        remain protected while the Lease is running. After
                                        termination notice, Rent Reserve may be consumed against
                                        rent according to Patrimoine's reserve rules.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-rent-reserve"
                                    type="text"
                                        inputmode="numeric"
                                        data-money-input
                                    value="0"
                                    required
                                    class="pm-input"
                                >
                            </div>

                            <div>
                                <label
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.consumable_advance">
                                        {{ __('ui.leases.consumable_advance') }}
                                    </span>

                                    <x-field-help
                                        label="About Consumable Advance"
                                        text-key="leases.consumable_advance_help_text"
                                        data-i18n-aria-label="leases.consumable_advance_help_label"
                                    >
                                        The contractual portion of Advance Payment that is not
                                        reserved. Patrimoine calculates this as Total Advance
                                        Payment minus Rent Reserve. Actual available money still
                                        comes from the tenant-fund ledger.
                                    </x-field-help>
                                </label>

                                <div
                                    class="
                                        flex h-[42px] items-center
                                        rounded-lg border
                                        border-[var(--pm-border)]
                                        bg-[var(--pm-surface-subtle)]
                                        px-3.5
                                    "
                                >
                                    <span
                                        id="lease-consumable-advance"
                                        class="
                                            text-sm font-semibold
                                            text-[var(--pm-text)]
                                        "
                                    >
                                        —
                                    </span>
                                </div>
                            </div>
                        </div>
                                                <div
                            class="
                                mt-5 rounded-xl
                                border border-[var(--pm-border)]
                                bg-[var(--pm-surface-subtle)]
                                p-4
                            "
                        >
                            <label
                                class="
                                    flex cursor-pointer
                                    items-start gap-3
                                "
                            >
                                <input
                                    id="lease-advance-received"
                                    type="checkbox"
                                    class="
                                        mt-1 h-4 w-4
                                        rounded border-[var(--pm-border-strong)]
                                        text-patrimoine-700
                                        focus:ring-patrimoine-200
                                    "
                                >

                                <span>
                                    <span
                                        class="
                                            flex items-center gap-1.5
                                            text-sm font-semibold
                                            text-[var(--pm-text)]
                                        "
                                    >
                                        <span data-i18n="leases.advance_already_received">
                                            {{ __('ui.leases.advance_already_received') }}
                                        </span>

                                        <x-field-help
                                        label="About Advance already received"
                                        text-key="leases.advance_received_help_text"
                                        data-i18n-aria-label="leases.advance_received_help_label"
                                    >
                                            Select this only when the contractual Advance Payment
                                            was actually received before this Lease was entered into
                                            Patrimoine. Patrimoine will reconstruct the historical
                                            payment, protect the Rent Reserve portion, allocate the
                                            remaining advance against outstanding rent and create the
                                            corresponding owner accounting entries.
                                        </x-field-help>
                                    </span>

                                    <span
                                        class="
                                            mt-1 block text-xs
                                            leading-5 text-[var(--pm-text-muted)]
                                        "
                                    >
                                        <span data-i18n="leases.advance_received_description">
                                            {{ __('ui.leases.advance_received_description') }}
                                        </span>
                                    </span>
                                </span>
                            </label>

                            <div
                                id="lease-advance-received-details"
                                class="
                                    mt-5 hidden
                                    grid gap-4
                                    md:grid-cols-2
                                    xl:grid-cols-4
                                "
                            >
                                <div>
                                    <label
                                        for="lease-advance-received-date"
                                        class="pm-field-label flex items-center gap-1.5"
                                    >
                                        <span data-i18n="leases.date_received">
                                            {{ __('ui.leases.date_received') }}
                                        </span>
                                        <span class="text-[var(--pm-danger-text)]">*</span>
                                    </label>

                                    <div class="pm-lease-date-control">
<input
                                        id="lease-advance-received-date"
                                    data-lease-date-input
                                    data-pm-date-input
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                        type="text"
                                        class="pm-input"
                                    >
    <button
        type="button"
        class="pm-lease-date-picker-button"
        data-lease-date-picker="lease-advance-received-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect
                x="3"
                y="5"
                width="18"
                height="16"
                rx="2"
            />
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        id="lease-advance-received-date-picker"
        type="date"
        class="pm-lease-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-lease-native-date-picker="lease-advance-received-date"
    >
</div>
                                </div>

                                <div>
                                    <label
                                        for="lease-advance-received-method"
                                        class="pm-field-label flex items-center gap-1.5"
                                    >
                                        <span data-i18n="leases.payment_method">
                                            {{ __('ui.leases.payment_method') }}
                                        </span>
                                        <span class="text-[var(--pm-danger-text)]">*</span>
                                    </label>

                                    <select
                                        id="lease-advance-received-method"
                                        class="pm-input"
                                    >
                                        <option
                                        value=""
                                        data-i18n="leases.select_method"
                                    >
                                        {{ __('ui.leases.select_method') }}
                                    </option>

                                        <option
                                        value="bank_transfer"
                                        data-i18n="leases.bank_transfer"
                                    >
                                        {{ __('ui.leases.bank_transfer') }}
                                    </option>

                                        <option
                                        value="momo"
                                        data-i18n="leases.mobile_money"
                                    >
                                        {{ __('ui.leases.mobile_money') }}
                                    </option>

                                        <option
                                        value="cash"
                                        data-i18n="leases.cash"
                                    >
                                        {{ __('ui.leases.cash') }}
                                    </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="lease-advance-received-reference"
                                        class="pm-field-label flex items-center gap-1.5"
                                    >
                                        <span data-i18n="leases.reference">
                                            {{ __('ui.leases.reference') }}
                                        </span>
                                    </label>

                                    <input
                                        id="lease-advance-received-reference"
                                        type="text"
                                        maxlength="255"
                                        data-i18n-placeholder="leases.optional"
                                        placeholder="{{ __('ui.leases.optional') }}"
                                        class="pm-input"
                                    >
                                </div>

                                <div
                                    id="lease-advance-received-collector-wrapper"
                                    class="hidden"
                                >
                                    <label
                                        for="lease-advance-received-collector"
                                        class="pm-field-label flex items-center gap-1.5"
                                    >
                                        <span data-i18n="leases.cash_collector">
                                            {{ __('ui.leases.cash_collector') }}
                                        </span>
                                        <span class="text-[var(--pm-danger-text)]">*</span>
                                    </label>

                                    <input
                                        id="lease-advance-received-collector"
                                        type="text"
                                        readonly
                                        data-i18n-placeholder="leases.cash_collector_placeholder"
                                        placeholder="{{ __('ui.leases.cash_collector_placeholder') }}"
                                        class="pm-input cursor-not-allowed bg-[var(--pm-input-disabled)]"
                                    >
                                </div>
                            </div>
                        </div>
                    </section>


                    {{-- =================================================
                        Rent Increment
                    ================================================= --}}

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
                                <span data-i18n="leases.rent_increment">
                                    {{ __('ui.leases.rent_increment') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="leases.rent_increment_description">
                                    {{ __('ui.leases.rent_increment_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-3
                            "
                        >
                            <div>
                                <label
                                    for="lease-rent-increment-type"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.increment_type">
                                        {{ __('ui.leases.increment_type') }}
                                    </span>

                                    <x-field-help
                                        label="About Rent Increment Type"
                                        text-key="leases.increment_type_help_text"
                                        data-i18n-aria-label="leases.increment_type_help_label"
                                    >
                                        Choose how the next rent increase is defined.
                                        Percentage increases the existing Monthly Rent by a rate.
                                        Fixed Amount adds a specific monetary amount.
                                        Choose None when no increase has been agreed.
                                    </x-field-help>
                                </label>

                                <select
                                    id="lease-rent-increment-type"
                                    required
                                    class="pm-input"
                                >
                                    <option
                                        value="none"
                                        data-i18n="leases.none"
                                    >
                                        {{ __('ui.leases.none') }}
                                    </option>

                                    <option
                                        value="percentage"
                                        data-i18n="leases.percentage"
                                    >
                                        {{ __('ui.leases.percentage') }}
                                    </option>

                                    <option
                                        value="fixed"
                                        data-i18n="leases.fixed_amount"
                                    >
                                        {{ __('ui.leases.fixed_amount') }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    for="lease-rent-increment-value"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.increment_value">
                                        {{ __('ui.leases.increment_value') }}
                                    </span>

                                    <x-field-help
                                        label="About Rent Increment Value"
                                        text-key="leases.increment_value_help_text"
                                        data-i18n-aria-label="leases.increment_value_help_label"
                                    >
                                        Enter the rate or amount of the next rent increase.
                                        Its meaning depends on the selected Increment Type.
                                    </x-field-help>
                                </label>

                                <div class="relative">
                                    <input
                                        id="lease-rent-increment-value"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="0"
                                        disabled
                                        required
                                        class="pm-input pr-14"
                                    >

                                    <span
                                        id="lease-rent-increment-unit"
                                        class="
                                            absolute right-3.5
                                            top-1/2
                                            -translate-y-1/2
                                            text-xs font-medium
                                            text-[var(--pm-text-subtle)]
                                        "
                                    >
                                        —
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label
                                    for="lease-next-rent-increment-date"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.next_increment_date">
                                        {{ __('ui.leases.next_increment_date') }}
                                    </span>

                                    <x-field-help
                                        label="About Next Rent Increment Date"
                                        text-key="leases.increment_date_help_text"
                                        data-i18n-aria-label="leases.increment_date_help_label"
                                    >
                                        Date on which the configured increase should first take
                                        effect. Patrimoine V1 stores this contractual date but
                                        does not infer future recurring increases beyond it.
                                    </x-field-help>
                                </label>

                                <div class="pm-lease-date-control">
<input
                                    id="lease-next-rent-increment-date"
                                    data-lease-date-input
                                    data-pm-date-input
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
                                    disabled
                                    class="pm-input"
                                >
    <button
        type="button"
        class="pm-lease-date-picker-button"
        data-lease-date-picker="lease-next-rent-increment-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect
                x="3"
                y="5"
                width="18"
                height="16"
                rx="2"
            />
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        id="lease-next-rent-increment-date-picker"
        type="date"
        class="pm-lease-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-lease-native-date-picker="lease-next-rent-increment-date"
    >
</div>
                            </div>
                        </div>
                    </section>


                    {{-- =================================================
                         Fees & Commission
                    ================================================= --}}

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
                                <span data-i18n="leases.fees_commission">
                                    {{ __('ui.leases.fees_commission') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="leases.fees_commission_description">
                                    {{ __('ui.leases.fees_commission_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="
                                grid gap-4
                                md:grid-cols-3
                            "
                        >
                            <div>
                                <label
                                    for="lease-management-fee-type"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.management_fee">
                                        {{ __('ui.leases.management_fee') }}
                                    </span>

                                <x-field-help
                                        label="About Managing Organisation Fee"
                                        text-key="leases.management_fee_help_text"
                                        data-i18n-aria-label="leases.management_fee_help_label"
                                    >
                                    Defines the fee earned by the Managing Organisation for managing
                                    rent under this Lease. Choose None, Percentage of rent, or Fixed Amount.
                                    The amount is ultimately deducted from Owner entitlement.
                                </x-field-help>
                                </label>

                                <select
                                    id="lease-management-fee-type"
                                    required
                                    class="pm-input"
                                >
                                    <option
                                        value="none"
                                        data-i18n="leases.none"
                                    >
                                        {{ __('ui.leases.none') }}
                                    </option>

                                    <option
                                        value="percentage"
                                        data-i18n="leases.percentage"
                                    >
                                        {{ __('ui.leases.percentage') }}
                                    </option>

                                    <option
                                        value="fixed"
                                        data-i18n="leases.fixed_amount"
                                    >
                                        {{ __('ui.leases.fixed_amount') }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    for="lease-management-fee-value"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.fee_value">
                                        {{ __('ui.leases.fee_value') }}
                                    </span>

                                    <x-field-help
                                        label="About Managing Organisation Fee Value"
                                        text-key="leases.management_fee_value_help_text"
                                        data-i18n-aria-label="leases.management_fee_value_help_label"
                                    >
                                        The meaning depends on the Managing Organisation Fee type.
                                        For Percentage, enter the percentage rate.
                                        For Fixed Amount, enter the monetary amount.
                                        When Managing Organisation Fee is None, this must remain 0.
                                    </x-field-help>
                                </label>
                                <div class="relative">
                                    <input
                                        id="lease-management-fee-value"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="0"
                                        required
                                        class="pm-input pr-14"
                                    >

                                    <span
                                        id="lease-management-fee-unit"
                                        class="
                                            absolute right-3.5
                                            top-1/2
                                            -translate-y-1/2
                                            text-xs font-medium
                                            text-[var(--pm-text-subtle)]
                                        "
                                    >
                                        —
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label
                                    for="lease-agent-commission"
                                    class="pm-field-label flex items-center gap-1.5"
                                >
                                    <span data-i18n="leases.agent_commission">
                                        {{ __('ui.leases.agent_commission') }}
                                    </span>

                                    <x-field-help
                                        label="About Agent Commission"
                                        text-key="leases.agent_commission_help_text"
                                        data-i18n-aria-label="leases.agent_commission_help_label"
                                    >
                                        One-time commission agreed with the Agent for this lease.
                                        Enter the total commission amount in whole currency units.
                                        A non-zero commission requires an Agent to be selected.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-agent-commission"
                                    type="text"
                                        inputmode="numeric"
                                        data-money-input
                                    value="0"
                                    required
                                    class="pm-input"
                                >
                            </div>
                        </div>
                    </section>

                    {{-- =================================================
                         Notes
                    ================================================= --}}

                    <section
                        class="
                            mt-8 border-t
                            border-[var(--pm-border-subtle)] pt-7
                        "
                    >
                        <label
                            for="lease-notes"
                            class="pm-field-label flex items-center gap-1.5"
                        >
                            <span data-i18n="leases.notes">
                                {{ __('ui.leases.notes') }}
                            </span>

                            <x-field-help
                                        label="About Lease Notes"
                                        text-key="leases.notes_help_text"
                                        data-i18n-aria-label="leases.notes_help_label"
                                    >
                                Optional internal information about the agreement that does not
                                form part of Patrimoine's automated financial calculations.
                            </x-field-help>
                        </label>

                        <textarea
                            id="lease-notes"
                            rows="4"
                            data-i18n-placeholder="leases.notes_placeholder"
                            placeholder="{{ __('ui.leases.notes_placeholder') }}"
                            class="pm-input resize-y"
                        ></textarea>
                    </section>
        </div>

        <x-drawer-footer>
            <button
                id="lease-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="lease-submit-button"
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


{{-- ================================================================
     Controlled Lease Termination Drawer
================================================================ --}}

<x-drawer
    id="lease-termination-modal"
    backdrop-id="lease-termination-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="lease-termination-modal-title"
        description-id="lease-termination-modal-description"
        close-id="lease-termination-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.terminate_lease">
                Terminate Lease
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.termination_description">
                Record notice, define the vacate date and choose the final rental treatment.
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="lease-termination-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="flex-1 overflow-y-auto px-6 py-6">
            <div
                id="lease-termination-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <section>
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.lease_context"
                >
                    Lease Context
                </h3>

                <div
                    class="
                        mt-4 grid gap-4
                        rounded-xl border
                        border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)] p-4
                        sm:grid-cols-2
                    "
                >
                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.lease">
                                Lease
                            </span>
                        </div>
                        <div
                            id="lease-termination-context-reference"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="leases.tenant"
                        >
                            Tenant
                        </div>
                        <div
                            id="lease-termination-context-tenant"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="leases.property"
                        >
                            Property
                        </div>
                        <div
                            id="lease-termination-context-building"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="leases.unit"
                        >
                            Unit
                        </div>
                        <div
                            id="lease-termination-context-unit"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="leases.status"
                        >
                            Status
                        </div>
                        <div
                            id="lease-termination-context-status"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="
                    mt-8 border-t
                    border-[var(--pm-border-subtle)] pt-7
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.termination_details"
                >
                    Termination Details
                </h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="lease-termination-notice-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.notice_date">
                                Notice Date
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="lease-termination-notice-date"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            type="text"
                            required
                            placeholder="DD-MM-YYYY"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="lease-termination-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.termination_date">
                                Termination / Vacate Date
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="lease-termination-date"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            type="text"
                            required
                            placeholder="DD-MM-YYYY"
                            class="pm-input"
                        >
                    </div>
                </div>
            </section>

            <section
                class="
                    mt-8 border-t
                    border-[var(--pm-border-subtle)] pt-7
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.final_rent_treatment"
                >
                    Final Rental Period
                </h3>

                <div class="mt-4 space-y-3">
                    <label
                        class="
                            flex items-start gap-3
                            rounded-xl border border-[var(--pm-border)]
                            p-4
                        "
                    >
                        <input
                            type="radio"
                            name="lease-termination-final-rent-mode"
                            value="prorate"
                            checked
                            class="mt-1"
                        >

                        <span>
                            <span
                                class="block text-sm font-medium text-[var(--pm-text)]"
                                data-i18n="leases.final_rent_prorate"
                            >
                                Prorate final period
                            </span>

                            <span
                                class="mt-1 block text-xs text-[var(--pm-text-muted)]"
                                data-i18n="leases.final_rent_prorate_help"
                            >
                                Charge rent only through the selected termination date.
                            </span>
                        </span>
                    </label>

                    <label
                        class="
                            flex items-start gap-3
                            rounded-xl border border-[var(--pm-border)]
                            p-4
                        "
                    >
                        <input
                            type="radio"
                            name="lease-termination-final-rent-mode"
                            value="full"
                            class="mt-1"
                        >

                        <span>
                            <span
                                class="block text-sm font-medium text-[var(--pm-text)]"
                                data-i18n="leases.final_rent_full"
                            >
                                Charge full period
                            </span>

                            <span
                                class="mt-1 block text-xs text-[var(--pm-text-muted)]"
                                data-i18n="leases.final_rent_full_help"
                            >
                                Charge the full contractual billing period containing the termination date.
                            </span>
                        </span>
                    </label>

                    <label
                        class="
                            flex items-start gap-3
                            rounded-xl border border-[var(--pm-border)]
                            p-4
                        "
                    >
                        <input
                            type="radio"
                            name="lease-termination-final-rent-mode"
                            value="none"
                            class="mt-1"
                        >

                        <span>
                            <span
                                class="block text-sm font-medium text-[var(--pm-text)]"
                                data-i18n="leases.final_rent_none"
                            >
                                No final rent
                            </span>

                            <span
                                class="mt-1 block text-xs text-[var(--pm-text-muted)]"
                                data-i18n="leases.final_rent_none_help"
                            >
                                Do not charge rent for the final partial billing period.
                            </span>
                        </span>
                    </label>
                </div>
            </section>

            <section
                id="lease-termination-notice-actions"
                class="
                    mt-8 hidden
                    border-t border-[var(--pm-border-subtle)] pt-7
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.termination_notice"
                >
                    Termination Notice
                </h3>

                <p
                    class="mt-2 text-sm text-[var(--pm-text-secondary)]"
                    data-i18n="leases.termination_notice_ready"
                >
                    The Termination Notice has been generated and is ready to open.
                </p>

                <button
                    id="lease-termination-open-notice"
                    type="button"
                    class="pm-button-secondary mt-4"
                >
                    <span data-i18n="leases.open_termination_notice">
                        Open Termination Notice
                    </span>
                </button>
            </section>
        </div>

        <x-drawer-footer>
            <button
                id="lease-termination-cancel"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="lease-termination-submit"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="leases.initiate_termination">
                    Initiate Termination
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ================================================================
     Controlled Lease Extend Drawer
================================================================ --}}

<x-drawer
    id="lease-extend-modal"
    backdrop-id="lease-extend-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="lease-extend-modal-title"
        description-id="lease-extend-modal-description"
        close-id="lease-extend-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.extend_lease">
                Extend Lease
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.extend_description">
                Create a new contractual term period while preserving the Lease and its history.
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="lease-extend-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="flex-1 overflow-y-auto px-6 py-6">
            <div
                id="lease-extend-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <section>
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.current_terms"
                >
                    Current Terms
                </h3>

                <div
                    class="
                        mt-4 grid gap-4
                        sm:grid-cols-2
                    "
                >
                    <div>
                        <div
                            class="text-xs font-medium text-[var(--pm-text-muted)]"
                            data-i18n="leases.monthly_rent"
                        >
                            Monthly Rent
                        </div>
                        <div
                            id="lease-extend-current-rent"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs font-medium text-[var(--pm-text-muted)]"
                            data-i18n="leases.payment_frequency"
                        >
                            Payment Frequency
                        </div>
                        <div
                            id="lease-extend-current-frequency"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs font-medium text-[var(--pm-text-muted)]"
                            data-i18n="leases.end_date"
                        >
                            End Date
                        </div>
                        <div
                            id="lease-extend-current-end-date"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs font-medium text-[var(--pm-text-muted)]"
                            data-i18n="leases.due_day"
                        >
                            Due Day
                        </div>
                        <div
                            id="lease-extend-current-due-day"
                            class="mt-1 text-sm font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="
                    mt-8 border-t
                    border-[var(--pm-border-subtle)] pt-7
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.new_terms"
                >
                    New Terms
                </h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="lease-extend-effective-from"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.effective_from">
                                Effective From
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="lease-extend-effective-from"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            type="text"
                            required
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="lease-extend-end-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.end_date">
                                End Date
                            </span>
                        </label>

                        <input
                            id="lease-extend-end-date"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            type="text"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="lease-extend-rent"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.monthly_rent">
                                Monthly Rent
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="lease-extend-rent"
                            type="text"
                                        inputmode="numeric"
                                        data-money-input
                            required
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="lease-extend-frequency"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.payment_frequency">
                                Payment Frequency
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <select
                            id="lease-extend-frequency"
                            required
                            class="pm-input"
                        >
                            <option value="monthly" data-i18n="leases.monthly">
                                Monthly
                            </option>
                            <option value="quarterly" data-i18n="leases.quarterly">
                                Quarterly
                            </option>
                            <option value="bi_yearly" data-i18n="leases.bi_yearly">
                                Bi-yearly
                            </option>
                            <option value="yearly" data-i18n="leases.yearly">
                                Yearly
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="lease-extend-due-day"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.due_day">
                                Due Day
                            </span>
                        </label>

                        <input
                            id="lease-extend-due-day"
                            type="number"
                            min="1"
                            max="31"
                            step="1"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="lease-extend-vat-rate"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.vat_rate">
                                VAT Rate
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="lease-extend-vat-rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                            class="pm-input"
                        >
                    </div>
                </div>
            </section>

            <section
                class="
                    mt-8 border-t
                    border-[var(--pm-border-subtle)] pt-7
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.rent_increment"
                >
                    Rent Increment
                </h3>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label
                            for="lease-extend-increment-type"
                            class="pm-field-label"
                            data-i18n="leases.increment_type"
                        >
                            Increment Type
                        </label>

                        <select
                            id="lease-extend-increment-type"
                            required
                            class="pm-input"
                        >
                            <option value="none" data-i18n="leases.none">
                                None
                            </option>
                            <option value="percentage" data-i18n="leases.percentage">
                                Percentage
                            </option>
                            <option value="fixed" data-i18n="leases.fixed_amount">
                                Fixed Amount
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="lease-extend-increment-value"
                            class="pm-field-label"
                            data-i18n="leases.increment_value"
                        >
                            Increment Value
                        </label>

                        <input
                            id="lease-extend-increment-value"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="lease-extend-next-increment-date"
                            class="pm-field-label"
                            data-i18n="leases.next_increment_date"
                        >
                            Next Increment Date
                        </label>

                        <input
                            id="lease-extend-next-increment-date"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            type="text"
                            class="pm-input"
                        >
                    </div>
                </div>
            </section>

            <section
                class="
                    mt-8 border-t
                    border-[var(--pm-border-subtle)] pt-7
                "
            >
                <label
                    for="lease-extend-notes"
                    class="pm-field-label"
                    data-i18n="leases.notes"
                >
                    Notes
                </label>

                <textarea
                    id="lease-extend-notes"
                    rows="4"
                    class="pm-input resize-y"
                ></textarea>
            </section>
        </div>

        <x-drawer-footer>
            <button
                id="lease-extend-cancel"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="lease-extend-submit"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="leases.extend_lease">
                    Extend Lease
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>


{{-- ================================================================
     Destructive Lease Delete Drawer
================================================================ --}}

<x-drawer
    id="lease-delete-modal"
    backdrop-id="lease-delete-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="lease-delete-modal-title"
        description-id="lease-delete-modal-description"
        close-id="lease-delete-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span
                class="text-[var(--pm-danger-text)]"
                data-i18n="leases.delete_lease"
            >
                Delete Lease
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.delete_destructive_action">
                Destructive action
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="lease-delete-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div
            class="
                min-h-0 flex-1 space-y-6
                overflow-y-auto px-6 py-6
                text-[var(--pm-text)]
            "
        >
            <div
                id="lease-delete-error"
                class="
                    hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
                role="alert"
            ></div>

            <section
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-muted)] p-4
                "
            >
                <h3
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                    data-i18n="leases.delete_context"
                >
                    Lease being deleted
                </h3>

                <dl
                    class="
                        mt-4 grid grid-cols-1
                        gap-3 text-sm
                        sm:grid-cols-2
                    "
                >
                    <div>
                        <dt
                            class="text-[var(--pm-text-muted)]"
                            data-i18n="leases.lease"
                        >
                            Lease
                        </dt>
                        <dd
                            id="lease-delete-context-reference"
                            class="font-medium text-[var(--pm-text)]"
                        ></dd>
                    </div>

                    <div>
                        <dt
                            class="text-[var(--pm-text-muted)]"
                            data-i18n="leases.tenant"
                        >
                            Tenant
                        </dt>
                        <dd
                            id="lease-delete-context-tenant"
                            class="font-medium text-[var(--pm-text)]"
                        ></dd>
                    </div>

                    <div>
                        <dt
                            class="text-[var(--pm-text-muted)]"
                            data-i18n="leases.property"
                        >
                            Property
                        </dt>
                        <dd
                            id="lease-delete-context-building"
                            class="font-medium text-[var(--pm-text)]"
                        ></dd>
                    </div>

                    <div>
                        <dt
                            class="text-[var(--pm-text-muted)]"
                            data-i18n="leases.unit"
                        >
                            Unit
                        </dt>
                        <dd
                            id="lease-delete-context-unit"
                            class="font-medium text-[var(--pm-text)]"
                        ></dd>
                    </div>
                </dl>
            </section>

            <section>
                <h3
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                    data-i18n="leases.delete_impact_title"
                >
                    Deletion impact
                </h3>

                <p
                    class="
                        mt-1 text-sm leading-6
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.delete_impact_description"
                >
                    Patrimoine will permanently remove the Lease and
                    its operational financial history while preserving
                    the required accounting and audit evidence.
                </p>

                <div
                    id="lease-delete-loading"
                    class="
                        mt-4 rounded-xl border
                        border-[var(--pm-border)]
                        bg-[var(--pm-surface-muted)]
                        px-4 py-5 text-sm
                        text-[var(--pm-text-muted)]
                    "
                    data-i18n="leases.delete_impact_loading"
                >
                    Calculating deletion impact…
                </div>

                <div
                    id="lease-delete-impact"
                    class="mt-4 hidden"
                >
                    <dl
                        class="
                            grid grid-cols-1 gap-3
                            sm:grid-cols-2
                        "
                    >
                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_invoices">Invoices</dt>
                            <dd id="lease-delete-impact-invoices" class="mt-1 text-lg font-semibold">0</dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_payments">Payments</dt>
                            <dd id="lease-delete-impact-payments" class="mt-1 text-lg font-semibold">0</dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_allocations">Allocations</dt>
                            <dd id="lease-delete-impact-allocations" class="mt-1 text-lg font-semibold">0</dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_receipts">Withdrawal receipts</dt>
                            <dd id="lease-delete-impact-receipts" class="mt-1 text-lg font-semibold">0</dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_security">Security Deposit balance</dt>
                            <dd id="lease-delete-impact-security" class="mt-1 font-semibold"></dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_reserve">Rent Reserve balance</dt>
                            <dd id="lease-delete-impact-rent-reserve" class="mt-1 font-semibold"></dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_consumable">Consumable Advance balance</dt>
                            <dd id="lease-delete-impact-consumable" class="mt-1 font-semibold"></dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_outstanding">Invoice outstanding</dt>
                            <dd id="lease-delete-impact-total" class="mt-1 font-semibold"></dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_reversals">Journal reversals</dt>
                            <dd id="lease-delete-impact-reversals" class="mt-1 text-lg font-semibold">0</dd>
                        </div>

                        <div class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4">
                            <dt data-i18n="leases.delete_impact_owner">Owner Lease effect</dt>
                            <dd id="lease-delete-impact-owner" class="mt-1 font-semibold"></dd>
                        </div>
                    </dl>

                    <div
                        id="lease-delete-blockers"
                        class="
                            mt-4 hidden rounded-xl
                            border border-[var(--pm-danger-border)]
                            bg-[var(--pm-danger-background)] px-4 py-3
                            text-sm text-[var(--pm-danger-text)]
                        "
                    ></div>
                </div>
            </section>

            <section
                class="
                    space-y-4 rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] p-4
                "
            >
                <div>
                    <label
                        for="lease-delete-reason"
                        class="
                            block text-sm font-medium
                            text-[var(--pm-text)]
                        "
                        data-i18n="leases.delete_reason"
                    >
                        Deletion reason
                    </label>

                    <textarea
                        id="lease-delete-reason"
                        rows="4"
                        maxlength="2000"
                        required
                        class="pm-input mt-2 resize-y"
                    ></textarea>
                </div>

                <div>
                    <label
                        for="lease-delete-confirmation"
                        class="
                            block text-sm font-medium
                            text-[var(--pm-text)]
                        "
                        data-i18n="leases.delete_confirmation_label"
                    >
                        Type DELETE to confirm
                    </label>

                    <input
                        id="lease-delete-confirmation"
                        type="text"
                        autocomplete="off"
                        required
                        class="pm-input mt-2"
                    >
                </div>

                <div>
                    <label
                        for="lease-delete-password"
                        class="
                            block text-sm font-medium
                            text-[var(--pm-text)]
                        "
                        data-i18n="leases.delete_password"
                    >
                        Current password
                    </label>

                    <input
                        id="lease-delete-password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="pm-input mt-2"
                    >
                </div>
            </section>
        </div>

        <x-drawer-footer>
            <button
                id="lease-delete-cancel"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="lease-delete-submit"
                type="submit"
                disabled
                class="pm-button-danger"
            >
                <span data-i18n="leases.delete_permanently">
                    Delete permanently
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ================================================================
     Global Lease Field Help Tooltip
================================================================ --}}

{{--
    Inverse-surface tooltip: --pm-text on --pm-page reads as dark-on-light
    in light theme and light-on-dark in dark theme without extra CSS.
--}}
<div
    id="lease-field-tooltip"
    role="tooltip"
    class="
        pointer-events-none fixed z-[120]
        hidden
        max-w-sm rounded-xl
        bg-[var(--pm-text)]
        px-4 py-3
        text-sm leading-6
        text-[var(--pm-page)]
        shadow-xl
    "
></div>
@endsection


{{-- ================================================================
     V1.0.5 Lease Termination Settlement
================================================================ --}}

<x-drawer
    id="termination-settlement-modal"
    backdrop-id="termination-settlement-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="termination-settlement-modal-title"
        description-id="termination-settlement-modal-description"
        close-id="termination-settlement-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.termination_settlement">
                Termination Settlement
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.termination_settlement_description">
                Review the financial position and resolve every blocker before completing termination.
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
        <div
            id="termination-settlement-error"
            class="
                mb-5 hidden rounded-lg
                border border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)] px-4 py-3
                text-sm text-[var(--pm-danger-text)]
            "
        ></div>

        <div
            id="termination-settlement-loading"
            class="
                py-12 text-center
                text-sm text-[var(--pm-text-subtle)]
            "
        >
            <span data-i18n="leases.termination_settlement_loading">
                Loading settlement…
            </span>
        </div>

        <div
            id="termination-settlement-content"
            class="hidden space-y-6"
        >
            {{-- Lease Context --}}
            <section
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-5
                "
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.lease">
                                Lease
                            </span>
                        </div>

                        <div
                            id="termination-settlement-lease"
                            class="
                                mt-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.tenant">
                                Tenant
                            </span>
                        </div>

                        <div
                            id="termination-settlement-tenant"
                            class="
                                mt-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.property">
                                Property
                            </span>
                        </div>

                        <div
                            id="termination-settlement-building"
                            class="
                                mt-1 text-sm font-medium
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.unit">
                                Unit
                            </span>
                        </div>

                        <div
                            id="termination-settlement-unit"
                            class="
                                mt-1 text-sm font-medium
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.notice_date">
                                Notice Date
                            </span>
                        </div>

                        <div
                            id="termination-settlement-notice-date"
                            class="
                                mt-1 text-sm font-medium
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-[var(--pm-text-muted)]">
                            <span data-i18n="leases.termination_date">
                                Termination / Vacate Date
                            </span>
                        </div>

                        <div
                            id="termination-settlement-date"
                            class="
                                mt-1 text-sm font-medium
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>
                </div>
            </section>

            {{-- Financial Position --}}
            <section>
                <h3
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="leases.termination_financial_position">
                        Financial Position
                    </span>
                </h3>

                <div
                    class="
                        mt-4 grid gap-3
                        sm:grid-cols-2
                        lg:grid-cols-4
                    "
                >
                    @foreach ([
                        [
                            'termination-settlement-debt',
                            'leases.outstanding_debt',
                            'Outstanding Debt',
                        ],
                        [
                            'termination-settlement-rent-reserve',
                            'leases.rent_reserve',
                            'Rent Reserve',
                        ],
                        [
                            'termination-settlement-consumable-advance',
                            'leases.consumable_advance',
                            'Consumable Advance',
                        ],
                        [
                            'termination-settlement-security',
                            'leases.security_deposit',
                            'Security Deposit',
                        ],
                        [
                            'termination-settlement-deductions',
                            'leases.security_deposit_deductions',
                            'Security Deposit Deductions',
                        ],
                        [
                            'termination-settlement-other-funds',
                            'leases.other_tenant_funds',
                            'Other Tenant Funds',
                        ],
                        [
                            'termination-settlement-owed',
                            'leases.amount_still_owed',
                            'Amount Still Owed',
                        ],
                        [
                            'termination-settlement-refund',
                            'leases.final_refundable_amount',
                            'Potential Refundable Amount',
                        ],
                    ] as [$id, $key, $label])
                        <div
                            class="pm-card p-4"
                        >
                            <div class="text-xs text-[var(--pm-text-muted)]">
                                <span data-i18n="{{ $key }}">
                                    {{ $label }}
                                </span>
                            </div>

                            <div
                                id="{{ $id }}"
                                class="
                                    mt-2 text-lg font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                —
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{--
                V1.0.7: Security Deposit deductions are recorded HERE, inside
                the termination workflow they belong to (the standalone lease
                security-deposit drawer was retired). The API allows
                deductions while the Lease is in notice or terminated and no
                settlement exists yet.
            --}}
            <section
                id="termination-deduction-section"
                data-requires-capability="manage_finance"
                class="rbac-hidden pm-card p-4"
            >
                <div class="text-sm font-semibold text-[var(--pm-text)]">
                    <span data-i18n="leases.record_deduction">
                        Record a security deposit deduction
                    </span>
                </div>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="leases.record_deduction_description">
                        Itemized deductions reduce the refundable deposit before settlement.
                    </span>
                </p>

                <div
                    id="termination-deduction-error"
                    class="
                        mt-3 hidden rounded-lg
                        border border-[var(--pm-danger-border)]
                        bg-[var(--pm-danger-background)] px-3 py-2
                        text-xs text-[var(--pm-danger-text)]
                    "
                ></div>

                <div
                    class="
                        mt-3 grid gap-3
                        sm:grid-cols-2
                    "
                >
                    <div class="sm:col-span-2">
                        <label
                            for="termination-deduction-description"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.deduction_description">
                                Description
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="termination-deduction-description"
                            type="text"
                            maxlength="255"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="termination-deduction-amount"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.deduction_amount">
                                Amount
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="termination-deduction-amount"
                            type="text"
                                        inputmode="numeric"
                                        data-money-input
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="termination-deduction-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.deduction_date">
                                Deduction date
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="termination-deduction-date"
                            type="date"
                            class="pm-input"
                        >
                    </div>
                </div>

                <div class="mt-3 flex justify-end">
                    <button
                        id="termination-deduction-submit"
                        type="button"
                        class="pm-button-primary"
                    >
                        <span data-i18n="leases.add_deduction">
                            Add deduction
                        </span>
                    </button>
                </div>
            </section>

            {{-- Completion Blockers --}}
            <section>
                <div
                    id="termination-settlement-blockers"
                ></div>
            </section>

            {{-- Operational hand-off --}}
            <section
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <p
                    class="
                        text-sm leading-6
                        text-[var(--pm-text-secondary)]
                    "
                >
                    <span data-i18n="leases.termination_resolve_from_tenant">
                        Resolve debt, held funds and refunds from the Tenant workspace. Financial operations are not duplicated on the Lease page.
                    </span>
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <button
                        id="termination-settlement-tenant-link"
                        type="button"
                        class="pm-button-primary"
                    >
                        <span data-i18n="leases.go_to_tenant">
                            Go to Tenant
                        </span>
                    </button>

                    <button
                        id="termination-settlement-notice"
                        type="button"
                        class="
                            pm-button-secondary
                            disabled:cursor-not-allowed
                            disabled:opacity-50
                        "
                    >
                        <span data-i18n="leases.open_termination_notice">
                            Open Termination Notice
                        </span>
                    </button>
                </div>
            </section>

        </div>
    </div>

    <x-drawer-footer>
        <button
            id="termination-settlement-cancel"
            type="button"
            class="pm-button-danger"
        >
            <span data-i18n="leases.cancel_termination">
                Cancel Termination
            </span>
        </button>

        <button
            id="termination-settlement-complete"
            type="button"
            disabled
            class="pm-button-primary"
        >
            <span data-i18n="leases.complete_termination">
                Complete Termination
            </span>
        </button>
    </x-drawer-footer>
</x-drawer>


{{-- ================================================================
     Lease Financial History
================================================================ --}}

<x-drawer
    id="lease-financial-history-modal"
    backdrop-id="lease-financial-history-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="lease-financial-history-modal-title"
        description-id="lease-financial-history-modal-description"
        close-id="lease-financial-history-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.financial_history">
                {{ __('ui.leases.financial_history') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.financial_history_description">
                {{ __('ui.leases.financial_history_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div class="pm-lease-financial-history-drawer-body min-h-0 flex-1 overflow-y-auto px-5 py-5">
        <div
            id="lease-financial-history-error"
            class="
                mb-5 hidden rounded-lg
                border border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)] px-4 py-3
                text-sm text-[var(--pm-danger-text)]
            "
        ></div>

        <div
            id="lease-financial-history-loading"
            class="py-12 text-center text-sm text-[var(--pm-text-subtle)]"
        >
            <span data-i18n="leases.financial_history_loading">
                {{ __('ui.leases.financial_history_loading') }}
            </span>
        </div>

        <div
            id="lease-financial-history-content"
            class="hidden space-y-4"
        ></div>
    </div>

    <x-drawer-footer>
        <button
            id="lease-financial-history-close-footer"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="actions.close">
                {{ __('ui.actions.close') }}
            </span>
        </button>
    </x-drawer-footer>
</x-drawer>


{{-- ================================================================
     V1.0.7 Lease Rent Increments
================================================================ --}}

<x-drawer
    id="rent-increments-modal"
    backdrop-id="rent-increments-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="rent-increments-modal-title"
        description-id="rent-increments-modal-description"
        close-id="rent-increments-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.rent_increments">
                Rent increments
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.rent_increments_description">
                Review scheduled, applied and cancelled rent increases for this Lease.
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="rent-increment-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div
                id="rent-increments-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                id="rent-increments-loading"
                class="
                    py-12 text-center
                    text-sm text-[var(--pm-text-subtle)]
                "
            >
                <span data-i18n="leases.rent_increments_loading">
                    Loading rent increments…
                </span>
            </div>

            <div
                id="rent-increments-list"
                class="hidden space-y-3"
            ></div>

            <section
                id="rent-increment-schedule"
                data-requires-capability="manage_operations"
                class="
                    mt-7 hidden border-t
                    border-[var(--pm-border-subtle)] pt-6
                "
            >
                <div class="mb-4">
                    <h3
                        class="
                            text-sm font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="leases.schedule_increment">
                            Schedule increment
                        </span>
                    </h3>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="leases.schedule_increment_description">
                            The new rent takes effect automatically on the effective date.
                        </span>
                    </p>
                </div>

                <div
                    class="
                        grid gap-4
                        sm:grid-cols-2
                    "
                >
                    <div>
                        <label
                            for="rent-increment-type"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.increment_type">
                                {{ __('ui.leases.increment_type') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <select
                            id="rent-increment-type"
                            required
                            class="pm-input"
                        >
                            <option
                                value="percentage"
                                data-i18n="leases.percentage"
                            >
                                {{ __('ui.leases.percentage') }}
                            </option>

                            <option
                                value="fixed"
                                data-i18n="leases.fixed_amount"
                            >
                                {{ __('ui.leases.fixed_amount') }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="rent-increment-value"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.increment_value">
                                {{ __('ui.leases.increment_value') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <div class="relative">
                            <input
                                id="rent-increment-value"
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                                class="pm-input pr-14"
                            >

                            <span
                                id="rent-increment-unit"
                                class="
                                    absolute right-3.5
                                    top-1/2
                                    -translate-y-1/2
                                    text-xs font-medium
                                    text-[var(--pm-text-subtle)]
                                "
                            >
                                %
                            </span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            for="rent-increment-effective-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="leases.effective_date">
                                Effective date
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <div class="pm-lease-date-control">
                            <input
                                id="rent-increment-effective-date"
                                data-lease-date-input
                                data-pm-date-input
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                type="text"
                                required
                                class="pm-input"
                            >

                            <button
                                type="button"
                                class="pm-lease-date-picker-button"
                                data-lease-date-picker="rent-increment-effective-date"
                                aria-label="Choose date"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="16"
                                        rx="2"
                                    />
                                    <path d="M16 3v4"/>
                                    <path d="M8 3v4"/>
                                    <path d="M3 11h18"/>
                                </svg>
                            </button>

                            <input
                                id="rent-increment-effective-date-picker"
                                type="date"
                                class="pm-lease-native-date-picker"
                                tabindex="-1"
                                aria-hidden="true"
                                data-lease-native-date-picker="rent-increment-effective-date"
                            >
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <x-drawer-footer>
            <button
                id="rent-increments-close-footer"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.close">
                    {{ __('ui.actions.close') }}
                </span>
            </button>

            <button
                id="rent-increment-submit"
                type="submit"
                data-requires-capability="manage_operations"
                class="hidden pm-button-primary"
            >
                <span data-i18n="leases.schedule_increment">
                    Schedule increment
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>
