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

        <div class="flex flex-wrap items-center gap-3">

        {{--
            V1.0.45: one way in. Add lease opens the assistant.

            There were two ways to create a letting and they had drifted
            apart - the assistant could create the property, the unit and
            the people as it went, the drawer could only pick ones that
            already existed, and the two had to be kept in step by hand
            every time a field changed. The drawer is gone; this is the
            only door.
        --}}
        <a
            id="add-lease-button"
            href="/leases/wizard"
            class="pm-button-primary gap-2"
        >
            <x-icon name="plus" :size="16" />

            <span data-i18n="leases.add_lease">
                {{ __('ui.leases.add_lease') }}
            </span>
        </a>

        </div>
    </div>

    {{--
        V1.0.31: assistants somebody started and did not finish. A lease
        cannot be saved half-made, so these are the assistants themselves;
        they live here because this is where a person looks for a letting
        they remember beginning. Hidden entirely when there are none.
    --}}
    <section id="lease-drafts" class="hidden mb-6"></section>

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
                                    >{{ __('ui.leases.all_statuses') }}</option>

                            <option
                                        value="draft"
                                        data-i18n="leases.status_draft"
                                    >{{ __('ui.leases.status_draft') }}</option>

                            <option
                                        value="active"
                                        data-i18n="leases.status_active"
                                    >{{ __('ui.leases.status_active') }}</option>

                            <option
                                        value="notice"
                                        data-i18n="leases.status_notice"
                                    >{{ __('ui.leases.status_notice') }}</option>

                            <option
                                        value="terminated"
                                        data-i18n="leases.status_terminated"
                                    >{{ __('ui.leases.status_terminated') }}</option>
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
                                    >{{ __('ui.leases.all_tenants') }}</option>
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
                            >All buildings</option>
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
                            >All frequencies</option>

                            <option
                                value="monthly"
                                data-i18n="leases.monthly"
                            >{{ __('ui.leases.monthly') }}</option>

                            <option
                                value="quarterly"
                                data-i18n="leases.quarterly"
                            >{{ __('ui.leases.quarterly') }}</option>

                            <option
                                value="bi_yearly"
                                data-i18n="leases.bi_yearly"
                            >{{ __('ui.leases.bi_yearly') }}</option>

                            <option
                                value="yearly"
                                data-i18n="leases.yearly"
                            >{{ __('ui.leases.yearly') }}</option>
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
                                <x-icon name="calendar" />
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
                        panel-sm:grid-cols-2
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

                <div class="mt-4 grid gap-4 panel-sm:grid-cols-2">
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
                        panel-sm:grid-cols-2
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

                <div class="mt-4 grid gap-4 panel-sm:grid-cols-2">
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
                            <option value="monthly" data-i18n="leases.monthly">Monthly</option>
                            <option value="quarterly" data-i18n="leases.quarterly">Quarterly</option>
                            <option value="bi_yearly" data-i18n="leases.bi_yearly">Bi-yearly</option>
                            <option value="yearly" data-i18n="leases.yearly">Yearly</option>
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

                <div class="mt-4 grid gap-4 panel-sm:grid-cols-3">
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
                            <option value="none" data-i18n="leases.none">None</option>
                            <option value="percentage" data-i18n="leases.percentage">Percentage</option>
                            <option value="fixed" data-i18n="leases.fixed_amount">Fixed Amount</option>
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
                        panel-sm:grid-cols-2
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
                            panel-sm:grid-cols-2
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
                <div class="grid gap-4 panel-sm:grid-cols-2">
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
                        panel-sm:grid-cols-2
                        panel-md:grid-cols-4
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
                        panel-sm:grid-cols-2
                    "
                >
                    <div class="panel-sm:col-span-2">
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
     V1.0.41 The whole letting, on one screen

     Everything a lease is made of is spread across the pages that made
     it: the property and unit, who owns it and in what shares, the
     tenant, the agent and their commission, the dates, the rent terms,
     what is held, the increases and the fee. This reads them back in one
     place, which is the only view that answers "how was this letting put
     together" without opening five drawers.

     It is read-only by design. Every one of these values has its own
     place to be changed, and a second way to edit them is a second way
     for them to disagree.
================================================================ --}}

<x-drawer
    id="lease-composition-modal"
    backdrop-id="lease-composition-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="lease-composition-modal-title"
        description-id="lease-composition-modal-description"
        close-id="lease-composition-modal-close"
        close-label="Close"
        close-label-key="leases.close"
    >
        <x-slot:title>
            <span data-i18n="leases.composition">
                {{ __('ui.leases.composition') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="leases.composition_description">
                {{ __('ui.leases.composition_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
        <div
            id="lease-composition-error"
            class="
                mb-5 hidden rounded-lg
                border border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)] px-4 py-3
                text-sm text-[var(--pm-danger-text)]
            "
        ></div>

        <div
            id="lease-composition-loading"
            class="py-12 text-center text-sm text-[var(--pm-text-subtle)]"
        >
            <span data-i18n="leases.composition_loading">
                {{ __('ui.leases.composition_loading') }}
            </span>
        </div>

        <div
            id="lease-composition-content"
            class="hidden space-y-6"
        ></div>
    </div>

    <x-drawer-footer>
        <button
            id="lease-composition-close-footer"
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
     Lease Financial History
================================================================ --}}

{{--
    V1.0.45: the large drawer, like Extend beside it.

    The history is a ledger - one row per movement, eight columns wide -
    and it was being read as a column of cards in a panel narrow enough
    that every record took four lines. A letting of any age scrolled for
    a minute and could not be scanned down a date or an amount.
--}}
<x-drawer
    id="lease-financial-history-modal"
    backdrop-id="lease-financial-history-modal-backdrop"
    width="lg"
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

        {{--
            The shared pagination control: how many rows out of how many,
            25 / 50 / 100, and every page one press away. The same one
            every other list in Patrimoine uses.
        --}}
        <div
            id="lease-financial-history-pagination"
            class="mt-4 hidden"
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
                        panel-sm:grid-cols-2
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
                            >{{ __('ui.leases.percentage') }}</option>

                            <option
                                value="fixed"
                                data-i18n="leases.fixed_amount"
                            >{{ __('ui.leases.fixed_amount') }}</option>
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

                    <div class="panel-sm:col-span-2">
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
                                <x-icon name="calendar" />
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
