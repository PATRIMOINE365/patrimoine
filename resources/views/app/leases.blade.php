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
                    text-patrimoine-700
                "
            >
                <span data-i18n="leases.tenancy">
                    {{ __('ui.leases.tenancy') }}
                </span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="leases.heading">
                    {{ __('ui.leases.heading') }}
                </span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="leases.page_description">
                    {{ __('ui.leases.page_description') }}
                </span>
            </p>
        </div>

        <button
            id="add-lease-button"
            type="button"
            class="
                inline-flex items-center gap-2
                rounded-lg bg-patrimoine-950
                px-4 py-2.5
                text-sm font-medium text-white
                shadow-sm transition
                hover:bg-patrimoine-900
            "
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
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
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
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="leases.total_leases">
                    {{ __('ui.leases.total_leases') }}
                </span>
            </div>

            <div
                id="leases-total-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="leases.status_active">
                    {{ __('ui.leases.status_active') }}
                </span>
            </div>

            <div
                id="leases-active-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="leases.in_notice">
                    {{ __('ui.leases.in_notice') }}
                </span>
            </div>

            <div
                id="leases-notice-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="leases.status_draft">
                    {{ __('ui.leases.status_draft') }}
                </span>
            </div>

            <div
                id="leases-draft-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
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
        class="
            rounded-xl border border-slate-200
            bg-white shadow-sm
        "
    >
        <div
            class="
                border-b border-slate-100
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
                            text-slate-950
                        "
                    >
                        <span data-i18n="leases.register">
                            {{ __('ui.leases.register') }}
                        </span>
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        <span data-i18n="leases.register_description">
                            {{ __('ui.leases.register_description') }}
                        </span>
                    </p>
                </div>

                <div
                    class="
                        grid w-full gap-3
                        sm:grid-cols-2
                        xl:w-auto
                    "
                >
                    <div class="sm:min-w-44">
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
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                bg-white px-3 py-2.5
                                text-sm text-slate-700
                                outline-none transition
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
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

                    <div class="sm:min-w-64">
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
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                bg-white px-3 py-2.5
                                text-sm text-slate-700
                                outline-none transition
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        >
                            <option
                                        value=""
                                        data-i18n="leases.all_tenants"
                                    >
                                        {{ __('ui.leases.all_tenants') }}
                                    </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="leases-list"
            class="p-5"
        >
            <div class="text-sm text-slate-400">
                <span data-i18n="leases.loading">
                    {{ __('ui.leases.loading') }}
                </span>
            </div>
        </div>

        <div
            id="leases-pagination"
            class="
                hidden border-t
                border-slate-100
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
                            border border-red-200
                            bg-red-50 px-4 py-3
                            text-sm text-red-700
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
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.property_tenant">
                                    {{ __('ui.leases.property_tenant') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
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
        class="
            mb-1.5 flex items-center gap-1.5
            text-sm font-medium
            text-slate-700
        "
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

        <span class="text-red-500">*</span>
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
                    text-slate-400
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
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white
                    py-2.5 pl-10 pr-11
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
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
                    text-slate-400
                    transition
                    hover:bg-slate-100
                    hover:text-slate-700
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
            class="
                absolute z-50 mt-1
                hidden max-h-80 w-full
                overflow-y-auto
                rounded-xl
                border border-slate-200
                bg-white
                shadow-xl
            "
        ></div>
    </div>

    {{-- Visual confirmation after Unit selection. --}}
    <div
        id="lease-unit-selection"
        class="
            mt-3 hidden
            rounded-xl border
            border-patrimoine-100
            bg-patrimoine-50/60
            p-4
        "
    >
        <div
            class="
                text-[11px] font-semibold
                uppercase tracking-[0.12em]
                text-patrimoine-700
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
                text-slate-950
            "
        ></div>

        <div
            id="lease-selected-unit-location"
            class="
                mt-1 text-xs
                text-slate-500
            "
        ></div>

        <div
            class="
                mt-4 text-[11px]
                font-semibold uppercase
                tracking-[0.12em]
                text-slate-500
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="lease-tenant"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                                    <option
                                        value=""
                                        data-i18n="leases.select_tenant"
                                    >
                                        {{ __('ui.leases.select_tenant') }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    for="lease-agent"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                <select
                                    id="lease-agent"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                                    <option
                                        value=""
                                        data-i18n="leases.no_agent"
                                    >
                                        {{ __('ui.leases.no_agent') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </section>

                    {{-- =================================================
                         Lease Period
                    ================================================= --}}

                    <section
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.lease_period">
                                    {{ __('ui.leases.lease_period') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
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

                            <div>
                                <label
                                    for="lease-end-date"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="lease-status"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm outline-none
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                <div class="pm-lease-date-control">
<input
                                    id="lease-notice-date"
                                    data-lease-date-input
                                    data-pm-date-input
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
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
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.rent_terms">
                                    {{ __('ui.leases.rent_terms') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="lease-rent-amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    required
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
                                    for="lease-frequency"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="lease-frequency"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm outline-none
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    for="lease-vat-rate"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="lease-vat-rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="18"
                                    required
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
                                    for="lease-proration"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    for="lease-security-deposit"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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

                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="lease-security-deposit"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="0"
                                    required
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


                    {{-- =================================================
                        Advance Payment
                    ================================================= --}}

                    <section
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.advance_payment">
                                    {{ __('ui.leases.advance_payment') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="0"
                                    required
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
                                    for="lease-rent-reserve"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="0"
                                    required
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                        border-slate-200
                                        bg-slate-50
                                        px-3.5
                                    "
                                >
                                    <span
                                        id="lease-consumable-advance"
                                        class="
                                            text-sm font-semibold
                                            text-slate-800
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
                                border border-slate-200
                                bg-slate-50
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
                                        rounded border-slate-300
                                        text-patrimoine-700
                                        focus:ring-patrimoine-200
                                    "
                                >

                                <span>
                                    <span
                                        class="
                                            flex items-center gap-1.5
                                            text-sm font-semibold
                                            text-slate-900
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
                                            leading-5 text-slate-500
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
                                        class="
                                            mb-1.5 flex items-center gap-1.5
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.date_received">
                                            {{ __('ui.leases.date_received') }}
                                        </span>
                                        <span class="text-red-500">*</span>
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
                                        class="
                                            mb-1.5 flex items-center gap-1.5
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.payment_method">
                                            {{ __('ui.leases.payment_method') }}
                                        </span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        id="lease-advance-received-method"
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            bg-white px-3.5 py-2.5
                                            text-sm outline-none
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
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
                                        class="
                                            mb-1.5 flex items-center gap-1.5
                                            text-sm font-medium
                                            text-slate-700
                                        "
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

                                <div
                                    id="lease-advance-received-collector-wrapper"
                                    class="hidden"
                                >
                                    <label
                                        for="lease-advance-received-collector"
                                        class="
                                            mb-1.5 flex items-center gap-1.5
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.cash_collector">
                                            {{ __('ui.leases.cash_collector') }}
                                        </span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="lease-advance-received-collector"
                                        type="text"
                                        maxlength="255"
                                        data-i18n-placeholder="leases.cash_collector_placeholder"
                                        placeholder="{{ __('ui.leases.cash_collector_placeholder') }}"
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
                        </div>
                    </section>


                    {{-- =================================================
                        Rent Increment
                    ================================================= --}}

                    <section
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.rent_increment">
                                    {{ __('ui.leases.rent_increment') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm outline-none
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            px-3.5 py-2.5 pr-14
                                            text-sm outline-none
                                            disabled:bg-slate-50
                                            disabled:text-slate-400
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
                                    >

                                    <span
                                        id="lease-rent-increment-unit"
                                        class="
                                            absolute right-3.5
                                            top-1/2
                                            -translate-y-1/2
                                            text-xs font-medium
                                            text-slate-400
                                        "
                                    >
                                        —
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label
                                    for="lease-next-rent-increment-date"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm outline-none
                                        disabled:bg-slate-50
                                        disabled:text-slate-400
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.fees_commission">
                                    {{ __('ui.leases.fees_commission') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm outline-none
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            px-3.5 py-2.5 pr-14
                                            text-sm outline-none
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
                                    >

                                    <span
                                        id="lease-management-fee-unit"
                                        class="
                                            absolute right-3.5
                                            top-1/2
                                            -translate-y-1/2
                                            text-xs font-medium
                                            text-slate-400
                                        "
                                    >
                                        —
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label
                                    for="lease-agent-commission"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
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
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="0"
                                    required
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

                    {{-- =================================================
                         Notes
                    ================================================= --}}

                    <section
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <label
                            for="lease-notes"
                            class="
                                mb-1.5 flex items-center gap-1.5
                                text-sm font-medium
                                text-slate-700
                            "
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

        <x-drawer-footer>
            <button
                id="lease-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="leases.cancel">
                    {{ __('ui.leases.cancel') }}
                </span>
            </button>

            <button
                id="lease-submit-button"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="leases.save">
                    {{ __('ui.leases.save') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ================================================================
     Global Lease Field Help Tooltip
================================================================ --}}

<div
    id="lease-field-tooltip"
    role="tooltip"
    class="
        pointer-events-none fixed z-[120]
        hidden
        max-w-sm rounded-xl
        bg-slate-950
        px-4 py-3
        text-sm leading-6
        text-white
        shadow-xl
    "
></div>
@endsection

{{-- ================================================================
     Security Deposit Operational Modal
================================================================ --}}

<x-drawer
    id="security-deposit-modal"
    backdrop-id="security-deposit-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="security-deposit-modal-title"
        description-id="security-deposit-modal-description"
        close-id="security-deposit-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.security_deposit">
                {{ __('ui.leases.security_deposit') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.security_modal_description">
                {{ __('ui.leases.security_modal_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div class="pm-security-deposit-drawer-body">
                <div
                    id="security-deposit-error"
                    class="
                        mb-5 hidden rounded-lg
                        border border-red-200
                        bg-red-50 px-4 py-3
                        text-sm text-red-700
                    "
                ></div>

                <div
                    id="security-deposit-loading"
                    class="
                        py-12 text-center
                        text-sm text-slate-400
                    "
                >
                    <span data-i18n="leases.loading_security_deposit">
                        {{ __('ui.leases.loading_security_deposit') }}
                    </span>
                </div>

                <div
                    id="security-deposit-content"
                    class="hidden"
                >
                    {{-- Position Summary --}}

                    <section>
                        <div
                            class="
                                grid gap-4
                                sm:grid-cols-2
                                xl:grid-cols-5
                            "
                        >
                            <div
                                class="
                                    rounded-xl border border-slate-200
                                    bg-slate-50 p-4
                                "
                            >
                                <div class="text-xs text-slate-500">
                                    <span data-i18n="leases.contractual_deposit">
                                        {{ __('ui.leases.contractual_deposit') }}
                                    </span>
                                </div>

                                <div
                                    id="security-deposit-contractual"
                                    class="
                                        mt-2 text-lg font-semibold
                                        text-slate-950
                                    "
                                >
                                    —
                                </div>
                            </div>

                            <div
                                class="
                                    rounded-xl border border-slate-200
                                    bg-slate-50 p-4
                                "
                            >
                                <div class="text-xs text-slate-500">
                                    <span data-i18n="leases.held_balance">
                                        {{ __('ui.leases.held_balance') }}
                                    </span>
                                </div>

                                <div
                                    id="security-deposit-held"
                                    class="
                                        mt-2 text-lg font-semibold
                                        text-slate-950
                                    "
                                >
                                    —
                                </div>
                            </div>

                            <div
                                class="
                                    rounded-xl border border-slate-200
                                    bg-slate-50 p-4
                                "
                            >
                                <div class="text-xs text-slate-500">
                                    <span data-i18n="leases.deductions">
                                        {{ __('ui.leases.deductions') }}
                                    </span>
                                </div>

                                <div
                                    id="security-deposit-deduction-total"
                                    class="
                                        mt-2 text-lg font-semibold
                                        text-slate-950
                                    "
                                >
                                    —
                                </div>
                            </div>

                            <div
                                class="
                                    rounded-xl border border-green-200
                                    bg-green-50 p-4
                                "
                            >
                                <div class="text-xs text-green-700">
                                    <span data-i18n="leases.refund">
                                        {{ __('ui.leases.refund') }}
                                    </span>
                                </div>

                                <div
                                    id="security-deposit-refund"
                                    class="
                                        mt-2 text-lg font-semibold
                                        text-green-800
                                    "
                                >
                                    —
                                </div>
                            </div>

                            <div
                                class="
                                    rounded-xl border border-red-200
                                    bg-red-50 p-4
                                "
                            >
                                <div class="text-xs text-red-700">
                                    <span data-i18n="leases.tenant_debt">
                                        {{ __('ui.leases.tenant_debt') }}
                                    </span>
                                </div>

                                <div
                                    id="security-deposit-debt"
                                    class="
                                        mt-2 text-lg font-semibold
                                        text-red-800
                                    "
                                >
                                    —
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Lifecycle Notice --}}

                    <div
                        id="security-deposit-lifecycle-message"
                        class="
                            mt-5 hidden rounded-xl
                            border border-amber-200
                            bg-amber-50 px-4 py-3
                            text-sm text-amber-800
                        "
                    ></div>

                    {{-- Itemized Deductions --}}

                    <section
                        class="
                            mt-7 border-t
                            border-slate-100 pt-6
                        "
                    >
                        <div
                            class="
                                flex flex-col gap-3
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            "
                        >
                            <div>
                                <h3
                                    class="
                                        text-sm font-semibold
                                        text-slate-950
                                    "
                                >
                                    <span data-i18n="leases.itemized_deductions">
                                        {{ __('ui.leases.itemized_deductions') }}
                                    </span>
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    <span data-i18n="leases.itemized_deductions_description">
                                        {{ __('ui.leases.itemized_deductions_description') }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div
                            id="security-deposit-deductions"
                            class="mt-4"
                        ></div>

                        <form
                            id="security-deposit-deduction-form"
                            class="
                                mt-5 hidden rounded-xl
                                border border-slate-200
                                bg-slate-50 p-4
                            "
                        >
                            <div
                                class="
                                    grid gap-4
                                    md:grid-cols-2
                                "
                            >
                                <div class="md:col-span-2">
                                    <label
                                        for="security-deduction-description"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.description">
                                            {{ __('ui.leases.description') }}
                                        </span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="security-deduction-description"
                                        type="text"
                                        maxlength="255"
                                        required
                                        data-i18n-placeholder="leases.deduction_description_placeholder"
                                        placeholder="{{ __('ui.leases.deduction_description_placeholder') }}"
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            bg-white px-3.5 py-2.5
                                            text-sm outline-none
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
                                    >
                                </div>

                                <div>
                                    <label
                                        for="security-deduction-amount"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.amount">
                                            {{ __('ui.leases.amount') }}
                                        </span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="security-deduction-amount"
                                        type="number"
                                        min="1"
                                        step="1"
                                        required
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            bg-white px-3.5 py-2.5
                                            text-sm outline-none
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
                                    >
                                </div>

                                <div>
                                    <label
                                        for="security-deduction-date"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.deduction_date">
                                            {{ __('ui.leases.deduction_date') }}
                                        </span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <div class="pm-security-date-control">
                                        <input
                                            id="security-deduction-date"
                                            type="text"
                                            data-security-date-input
                                            inputmode="numeric"
                                            maxlength="10"
                                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                            required
                                            class="
                                                w-full rounded-lg
                                                border border-slate-200
                                                bg-white px-3.5 py-2.5
                                                text-sm outline-none
                                                focus:border-patrimoine-500
                                                focus:ring-2
                                                focus:ring-patrimoine-100
                                            "
                                        >

                                        <button
                                            type="button"
                                            class="pm-security-date-picker-button"
                                            data-security-date-picker="security-deduction-date"
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
                                            type="date"
                                            class="pm-security-native-date-picker"
                                            tabindex="-1"
                                            aria-hidden="true"
                                            data-security-native-date-picker="security-deduction-date"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="security-deduction-reference"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.reference">
                                            {{ __('ui.leases.reference') }}
                                        </span>
                                    </label>

                                    <input
                                        id="security-deduction-reference"
                                        type="text"
                                        maxlength="255"
                                        data-i18n-placeholder="leases.deduction_reference_placeholder"
                                        placeholder="{{ __('ui.leases.deduction_reference_placeholder') }}"
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            bg-white px-3.5 py-2.5
                                            text-sm outline-none
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
                                    >
                                </div>

                                <div>
                                    <label
                                        for="security-deduction-notes"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.notes">
                                            {{ __('ui.leases.notes') }}
                                        </span>
                                    </label>

                                    <input
                                        id="security-deduction-notes"
                                        type="text"
                                        data-i18n-placeholder="leases.optional_details"
                                        placeholder="{{ __('ui.leases.optional_details') }}"
                                        class="
                                            w-full rounded-lg
                                            border border-slate-200
                                            bg-white px-3.5 py-2.5
                                            text-sm outline-none
                                            focus:border-patrimoine-500
                                            focus:ring-2
                                            focus:ring-patrimoine-100
                                        "
                                    >
                                </div>
                            </div>

                            <div
                                class="
                                    mt-4 flex justify-end
                                "
                            >
                                <button
                                    id="security-deduction-submit"
                                    type="submit"
                                    class="
                                        rounded-lg bg-slate-900
                                        px-4 py-2.5
                                        text-sm font-medium text-white
                                        transition
                                        hover:bg-slate-800
                                    "
                                >
                                    <span data-i18n="leases.add_deduction">
                                        {{ __('ui.leases.add_deduction') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </section>

                    {{-- Final Settlement --}}

                    <section
                        id="security-deposit-settlement-section"
                        class="
                            mt-7 border-t
                            border-slate-100 pt-6
                        "
                    >
                        <div>
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.final_settlement">
                                    {{ __('ui.leases.final_settlement') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                <span data-i18n="leases.final_settlement_description">
                                    {{ __('ui.leases.final_settlement_description') }}
                                </span>
                            </p>
                        </div>

                        <form
                            id="security-deposit-settlement-form"
                            class="
                                mt-4 hidden rounded-xl
                                border border-slate-200
                                p-4
                            "
                        >
                            <div
                                class="
                                    grid gap-4
                                    md:grid-cols-2
                                "
                            >
                                <div>
                                    <label
                                        for="security-settlement-date"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.settlement_date">
                                            {{ __('ui.leases.settlement_date') }}
                                        </span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <div class="pm-security-date-control">
                                        <input
                                            id="security-settlement-date"
                                            type="text"
                                            data-security-date-input
                                            inputmode="numeric"
                                            maxlength="10"
                                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                            required
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

                                        <button
                                            type="button"
                                            class="pm-security-date-picker-button"
                                            data-security-date-picker="security-settlement-date"
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
                                            type="date"
                                            class="pm-security-native-date-picker"
                                            tabindex="-1"
                                            aria-hidden="true"
                                            data-security-native-date-picker="security-settlement-date"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="security-settlement-notes"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="leases.notes">
                                            {{ __('ui.leases.notes') }}
                                        </span>
                                    </label>

                                    <input
                                        id="security-settlement-notes"
                                        type="text"
                                        data-i18n-placeholder="leases.closeout_notes_placeholder"
                                        placeholder="{{ __('ui.leases.closeout_notes_placeholder') }}"
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

                            <div
                                class="
                                    mt-4 rounded-lg
                                    border border-amber-200
                                    bg-amber-50 px-4 py-3
                                    text-xs leading-5
                                    text-amber-800
                                "
                            >
                                <span data-i18n="leases.final_settlement_warning">
                                    {{ __('ui.leases.final_settlement_warning') }}
                                </span>
                            </div>

                            <div
                                class="
                                    mt-4 flex justify-end
                                "
                            >
                                <button
                                    id="security-settlement-submit"
                                    type="submit"
                                    class="
                                        rounded-lg bg-patrimoine-950
                                        px-4 py-2.5
                                        text-sm font-medium text-white
                                        transition
                                        hover:bg-patrimoine-900
                                    "
                                >
                                    <span data-i18n="leases.finalize_settlement">
                                        {{ __('ui.leases.finalize_settlement') }}
                                    </span>
                                </button>
                            </div>
                        </form>

                        <div
                            id="security-deposit-settled"
                            class="
                                mt-4 hidden rounded-xl
                                border border-green-200
                                bg-green-50 p-5
                            "
                        >
                            <div
                                class="
                                    flex flex-col gap-4
                                    sm:flex-row
                                    sm:items-center
                                    sm:justify-between
                                "
                            >
                                <div>
                                    <div
                                        class="
                                            text-sm font-semibold
                                            text-green-900
                                        "
                                    >
                                        <span data-i18n="leases.security_deposit_settled">
                                            {{ __('ui.leases.security_deposit_settled') }}
                                        </span>
                                    </div>

                                    <div
                                        id="security-deposit-voucher-number"
                                        class="
                                            mt-1 text-xs
                                            text-green-700
                                        "
                                    >
                                        —
                                    </div>
                                </div>

                                <button
                                    id="security-deposit-voucher-link"
                                    type="button"
                                    class="
                                        inline-flex items-center
                                        justify-center rounded-lg
                                        border border-green-300
                                        bg-white px-4 py-2.5
                                        text-sm font-medium
                                        text-green-800
                                        transition
                                        hover:bg-green-100
                                        disabled:cursor-not-allowed
                                        disabled:opacity-60
                                    "
                                >
                                    <span data-i18n="leases.download_voucher">
                                        {{ __('ui.leases.download_voucher') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
    </div>
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

    <div class="pm-lease-financial-history-drawer-body overflow-y-auto px-5 py-5">
        <div
            id="lease-financial-history-error"
            class="
                mb-5 hidden rounded-lg
                border border-red-200
                bg-red-50 px-4 py-3
                text-sm text-red-700
            "
        ></div>

        <div
            id="lease-financial-history-loading"
            class="py-12 text-center text-sm text-slate-400"
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
</x-drawer>


{{-- ================================================================
     Tenant Funds Operational Modal
================================================================ --}}

<x-drawer
    id="tenant-funds-modal"
    backdrop-id="tenant-funds-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        title-id="tenant-funds-modal-title"
        description-id="tenant-funds-modal-description"
        close-id="tenant-funds-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.tenant_funds">
                {{ __('ui.leases.tenant_funds') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.tenant_funds_modal_description">
                {{ __('ui.leases.tenant_funds_modal_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div class="pm-tenant-funds-drawer-body">
                <div
                    id="tenant-funds-error"
                    class="
                        mb-5 hidden rounded-lg
                        border border-red-200
                        bg-red-50 px-4 py-3
                        text-sm text-red-700
                    "
                ></div>

                <div
                    id="tenant-funds-loading"
                    class="
                        py-12 text-center
                        text-sm text-slate-400
                    "
                >
                    <span data-i18n="leases.loading_tenant_funds">
                        {{ __('ui.leases.loading_tenant_funds') }}
                    </span>
                </div>

                <div
                    id="tenant-funds-content"
                    class="hidden"
                >
                    {{-- Actual Balances --}}

                    <section>
                        <div
                            class="
                                grid gap-4
                                md:grid-cols-3
                            "
                        >
                            <div
                                class="
                                    rounded-xl border
                                    border-slate-200
                                    bg-slate-50 p-5
                                "
                            >
                                <div class="text-xs text-slate-500">
                                    <span data-i18n="leases.rent_reserve">
                                        {{ __('ui.leases.rent_reserve') }}
                                    </span>
                                </div>

                                <div
                                    id="tenant-funds-reserve-balance"
                                    class="
                                        mt-2 text-xl font-semibold
                                        text-slate-950
                                    "
                                >
                                    —
                                </div>

                                <p
                                    id="tenant-funds-reserve-help"
                                    class="
                                        mt-2 text-xs
                                        leading-5 text-slate-500
                                    "
                                >
                                    <span data-i18n="leases.reserve_protected_short">
                                        {{ __('ui.leases.reserve_protected_short') }}
                                    </span>
                                </p>
                            </div>

                            <div
                                class="
                                    rounded-xl border
                                    border-slate-200
                                    bg-slate-50 p-5
                                "
                            >
                                <div class="text-xs text-slate-500">
                                    <span data-i18n="leases.consumable_advance">
                                        {{ __('ui.leases.consumable_advance') }}
                                    </span>
                                </div>

                                <div
                                    id="tenant-funds-advance-balance"
                                    class="
                                        mt-2 text-xl font-semibold
                                        text-slate-950
                                    "
                                >
                                    —
                                </div>

                                <p
                                    class="
                                        mt-2 text-xs
                                        leading-5 text-slate-500
                                    "
                                >
                                    <span data-i18n="leases.consumable_advance_description">
                                        {{ __('ui.leases.consumable_advance_description') }}
                                    </span>
                                </p>
                            </div>

                            <div
                                class="
                                    rounded-xl border
                                    border-slate-200
                                    bg-slate-50 p-5
                                "
                            >
                                <div class="text-xs text-slate-500">
                                    <span data-i18n="leases.security_deposit">
                                        {{ __('ui.leases.security_deposit') }}
                                    </span>
                                </div>

                                <div
                                    id="tenant-funds-security-balance"
                                    class="
                                        mt-2 text-xl font-semibold
                                        text-slate-950
                                    "
                                >
                                    —
                                </div>


                            </div>
                        </div>
                    </section>

                    {{-- Rent Reserve --}}

                    <section
                        class="
                            mt-7 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.apply_rent_reserve">
                                    {{ __('ui.leases.apply_rent_reserve') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                <span data-i18n="leases.apply_reserve_description">
                                    {{ __('ui.leases.apply_reserve_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            id="tenant-funds-reserve-unavailable"
                            class="
                                hidden rounded-lg
                                border border-amber-200
                                bg-amber-50 px-4 py-3
                                text-sm text-amber-800
                            "
                        ></div>

                        <form
                            id="tenant-funds-reserve-form"
                            class="
                                grid gap-4
                                md:grid-cols-4
                            "
                        >
                            <div class="md:col-span-2">
                                <label
                                    for="tenant-funds-reserve-invoice"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="leases.outstanding_invoice">
                                        {{ __('ui.leases.outstanding_invoice') }}
                                    </span>
                                </label>

                                <select
                                    id="tenant-funds-reserve-invoice"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm
                                    "
                                ></select>
                            </div>

                            <div>
                                <label
                                    for="tenant-funds-reserve-amount"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="leases.amount">
                                        {{ __('ui.leases.amount') }}
                                    </span>
                                </label>

                                <input
                                    id="tenant-funds-reserve-amount"
                                    type="number"
                                    min="1"
                                    step="1"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="tenant-funds-reserve-date"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="leases.date">
                                        {{ __('ui.leases.date') }}
                                    </span>
                                </label>

                                <div class="pm-tenant-funds-date-control">
<input
                                    id="tenant-funds-reserve-date"
                            data-tenant-funds-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                    "
                                >

    <button
        type="button"
        class="pm-tenant-funds-date-picker-button"
        data-tenant-funds-date-picker="tenant-funds-reserve-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect x="3" y="5" width="18" height="16" rx="2"/>
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        type="date"
        class="pm-tenant-funds-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-tenant-funds-native-date-picker="tenant-funds-reserve-date"
    >
</div>
                            </div>

                            <div class="md:col-span-4">
                                <button
                                    id="tenant-funds-reserve-submit"
                                    type="submit"
                                    class="
                                        rounded-lg bg-patrimoine-950
                                        px-4 py-2.5
                                        text-sm font-medium text-white
                                        disabled:cursor-not-allowed
                                        disabled:opacity-50
                                    "
                                >
                                    <span data-i18n="leases.apply_rent_reserve">
                                        {{ __('ui.leases.apply_rent_reserve') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </section>

                    {{-- Consumable Advance --}}

                    <section
                        class="
                            mt-7 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="leases.apply_consumable_advance">
                                    {{ __('ui.leases.apply_consumable_advance') }}
                                </span>
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                <span data-i18n="leases.apply_advance_description">
                                    {{ __('ui.leases.apply_advance_description') }}
                                </span>
                            </p>
                        </div>

                        <div
                            id="tenant-funds-advance-unavailable"
                            class="
                                hidden rounded-lg
                                border border-slate-200
                                bg-slate-50 px-4 py-3
                                text-sm text-slate-600
                            "
                        ></div>

                        <form
                            id="tenant-funds-advance-form"
                            class="
                                grid gap-4
                                md:grid-cols-4
                            "
                        >
                            <div class="md:col-span-2">
                                <label
                                    for="tenant-funds-advance-invoice"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="leases.outstanding_invoice">
                                        {{ __('ui.leases.outstanding_invoice') }}
                                    </span>
                                </label>

                                <select
                                    id="tenant-funds-advance-invoice"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        bg-white px-3.5 py-2.5
                                        text-sm
                                    "
                                ></select>
                            </div>

                            <div>
                                <label
                                    for="tenant-funds-advance-amount"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="leases.amount">
                                        {{ __('ui.leases.amount') }}
                                    </span>
                                </label>

                                <input
                                    id="tenant-funds-advance-amount"
                                    type="number"
                                    min="1"
                                    step="1"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="tenant-funds-advance-date"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="leases.date">
                                        {{ __('ui.leases.date') }}
                                    </span>
                                </label>

                                <div class="pm-tenant-funds-date-control">
<input
                                    id="tenant-funds-advance-date"
                            data-tenant-funds-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                    type="text"
                                    required
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                    "
                                >

    <button
        type="button"
        class="pm-tenant-funds-date-picker-button"
        data-tenant-funds-date-picker="tenant-funds-advance-date"
        aria-label="Choose date"
    >
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <rect x="3" y="5" width="18" height="16" rx="2"/>
            <path d="M16 3v4"/>
            <path d="M8 3v4"/>
            <path d="M3 11h18"/>
        </svg>
    </button>

    <input
        type="date"
        class="pm-tenant-funds-native-date-picker"
        tabindex="-1"
        aria-hidden="true"
        data-tenant-funds-native-date-picker="tenant-funds-advance-date"
    >
</div>
                            </div>

                            <div class="md:col-span-4">
                                <button
                                    id="tenant-funds-advance-submit"
                                    type="submit"
                                    class="
                                        rounded-lg bg-patrimoine-950
                                        px-4 py-2.5
                                        text-sm font-medium text-white
                                        disabled:cursor-not-allowed
                                        disabled:opacity-50
                                    "
                                >
                                    <span data-i18n="leases.apply_consumable_advance">
                                        {{ __('ui.leases.apply_consumable_advance') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
    </div>
</x-drawer>
