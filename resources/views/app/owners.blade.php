@extends('layouts.app')

@section('title', __('ui.owners.title'))
@section('title-i18n', 'owners.title')

@section('content')

<div class="pm-owners-page mx-auto max-w-[1600px]">

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
                <span data-i18n="owners.finance">
    {{ __('ui.owners.finance') }}
</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="owners.heading">
    {{ __('ui.owners.heading') }}
</span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="owners.page_description">
    {{ __('ui.owners.page_description') }}
</span>
            </p>
        </div>
    </div>

    {{-- Page-level API Error --}}

    <div
        id="owners-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    {{-- ============================================================
         Owner Workspace
    ============================================================ --}}

    <div
        class="
            pm-owners-workspace
            grid gap-6
            xl:grid-cols-[380px_minmax(0,1fr)]
        "
    >

        {{-- ========================================================
             Owner Search / Directory
        ======================================================== --}}

        <section
            class="
                pm-owner-directory
                overflow-hidden rounded-xl
                border border-slate-200
                bg-white shadow-sm
            "
        >
            <div
                class="
                    border-b border-slate-100
                    px-5 py-4
                "
            >
                <h2
                    class="
                        text-base font-semibold
                        text-slate-950
                    "
                >
                    <span data-i18n="owners.property_owners">
    {{ __('ui.owners.property_owners') }}
</span>
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    <span data-i18n="owners.search_description">
    {{ __('ui.owners.search_description') }}
</span>
                </p>

                <div class="mt-4">
                    <label
                        for="owners-search"
                        class="sr-only"
                    >
                        <span data-i18n="owners.search_property_owners">
    {{ __('ui.owners.search_property_owners') }}
</span>
                    </label>

                    <div class="relative">
                        <svg
                            class="
                                pointer-events-none
                                absolute left-3 top-1/2
                                h-4 w-4
                                -translate-y-1/2
                                text-slate-400
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
                            id="owners-search"
                            type="search"
                            autocomplete="off"
                            placeholder="{{ __('ui.owners.search_placeholder') }}" data-i18n-placeholder="owners.search_placeholder"
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                py-2.5 pl-9 pr-3
                                text-sm outline-none
                                transition
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        >
                    </div>
                </div>
            </div>

            <div
                id="owners-list"
                class="
                    max-h-[calc(100vh-270px)]
                    min-h-[420px]
                    overflow-y-auto
                "
            >
                <div
                    class="
                        px-5 py-8 text-center
                        text-sm text-slate-400
                    "
                >
                    <span data-i18n="owners.loading">
    {{ __('ui.owners.loading') }}
</span>
                </div>
            </div>

            <div
                id="owners-list-pagination"
                class="
                    hidden border-t
                    border-slate-100
                    px-4 py-3
                "
            ></div>
        </section>

        {{-- ========================================================
             Owner Detail
        ======================================================== --}}

        <section
            id="owner-detail-panel"
            class="
                pm-owner-detail-shell
                min-w-0 overflow-hidden
                rounded-xl
                border border-slate-200
                bg-white shadow-sm
            "
        >

            {{-- Empty state before an owner is selected --}}

            <div
                id="owner-detail-empty"
                class="
                    flex min-h-[620px]
                    items-center justify-center
                    px-6 py-16
                "
            >
                <div
                    class="
                        max-w-md text-center
                    "
                >
                    <div
                        class="
                            mx-auto flex h-12 w-12
                            items-center justify-center
                            rounded-full bg-slate-100
                            text-slate-500
                        "
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        </svg>
                    </div>

                    <h2
                        class="
                            mt-4 text-base font-semibold
                            text-slate-900
                        "
                    >
                        <span data-i18n="owners.select_property_owner">
    {{ __('ui.owners.select_property_owner') }}
</span>
                    </h2>

                    <p
                        class="
                            mt-2 text-sm leading-6
                            text-slate-500
                        "
                    >
                        <span data-i18n="owners.select_owner_description">
    {{ __('ui.owners.select_owner_description') }}
</span>
                    </p>
                </div>
            </div>

            {{-- Actual owner content --}}

            <div
                id="owner-detail-content"
                class="hidden"
            >

                {{-- ====================================================
                     Owner Header
                ==================================================== --}}

                <div
                    class="
                        border-b border-slate-100
                        px-6 py-5
                    "
                >
                    <div
                        class="
                            flex flex-col gap-5
                            lg:flex-row
                            lg:items-start
                            lg:justify-between
                        "
                    >
                        <div class="min-w-0">
                            <div
                                class="
                                    flex flex-wrap
                                    items-center gap-2
                                "
                            >
                                <h2
                                    id="owner-detail-name"
                                    class="
                                        truncate
                                        text-xl font-semibold
                                        tracking-tight
                                        text-slate-950
                                    "
                                >
                                    —
                                </h2>

                                <span
                                    id="owner-detail-status"
                                    class="
                                        inline-flex items-center
                                        rounded-full
                                        bg-emerald-50
                                        px-2.5 py-1
                                        text-xs font-medium
                                        text-emerald-700
                                    "
                                >
                                    <span data-i18n="owners.active">
    {{ __('ui.owners.active') }}
</span>
                                </span>
                            </div>

                            <div
                                id="owner-detail-contact"
                                class="
                                    mt-2 text-sm
                                    text-slate-500
                                "
                            >
                                —
                            </div>
                        </div>

                        <div
                            class="
                                flex flex-wrap gap-2
                            "
                        >
                            <button
                                id="owner-record-deposit-button"
                                type="button"
                                class="
                                    inline-flex items-center gap-2
                                    rounded-lg border
                                    border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm font-medium
                                    text-slate-700
                                    transition
                                    hover:border-slate-300
                                    hover:bg-slate-50
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

                                <span data-i18n="owners.deposit">
    {{ __('ui.owners.deposit') }}
</span>
                            </button>

                            <button
                                id="owner-record-expense-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm font-medium
                                    text-slate-700
                                    transition
                                    hover:border-slate-300
                                    hover:bg-slate-50
                                "
                            >
                                <span data-i18n="owners.expense">
    {{ __('ui.owners.expense') }}
</span>
                            </button>

                            <button
                                id="owner-record-payout-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm font-medium
                                    text-slate-700
                                    transition
                                    hover:border-slate-300
                                    hover:bg-slate-50
                                    disabled:cursor-not-allowed
                                    disabled:opacity-50
                                "
                            >
                                <span data-i18n="owners.payout">
    {{ __('ui.owners.payout') }}
</span>
                            </button>

                            <button
                                id="owner-record-adjustment-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm font-medium
                                    text-slate-700
                                    transition
                                    hover:border-slate-300
                                    hover:bg-slate-50
                                "
                            >
                                <span data-i18n="owners.adjustment">
    {{ __('ui.owners.adjustment') }}
</span>
                            </button>

                        </div>
                    </div>
                </div>

                {{-- ====================================================
                     Account Summary
                ==================================================== --}}

                <div
                    class="
                        grid gap-4
                        border-b border-slate-100
                        bg-slate-50/50
                        px-6 py-5
                        sm:grid-cols-2
                        xl:grid-cols-4
                    "
                >
                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-slate-500
                            "
                        >
                            <span data-i18n="owners.current_balance">
    {{ __('ui.owners.current_balance') }}
</span>
                        </div>

                        <div
                            id="owner-detail-balance"
                            class="
                                mt-2 text-2xl font-semibold
                                tracking-tight text-slate-950
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-slate-500
                            "
                        >
                            <span data-i18n="owners.total_credits">
    {{ __('ui.owners.total_credits') }}
</span>
                        </div>

                        <div
                            id="owner-detail-credits"
                            class="
                                mt-2 text-xl font-semibold
                                text-slate-900
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-slate-500
                            "
                        >
                            <span data-i18n="owners.total_debits">
    {{ __('ui.owners.total_debits') }}
</span>
                        </div>

                        <div
                            id="owner-detail-debits"
                            class="
                                mt-2 text-xl font-semibold
                                text-slate-900
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-slate-500
                            "
                        >
                            <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                        </div>

                        <div
                            id="owner-detail-property-count"
                            class="
                                mt-2 text-xl font-semibold
                                text-slate-900
                            "
                        >
                            —
                        </div>
                    </div>
                </div>

                {{-- ====================================================
                     <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                ==================================================== --}}

                <div
                    class="
                        border-b border-slate-100
                        px-6 py-6
                    "
                >
                    <div
                        class="
                            flex items-start
                            justify-between gap-4
                        "
                    >
                        <div>
                            <h3
                                class="
                                    text-base font-semibold
                                    text-slate-950
                                "
                            >
                                <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                            </h3>

                            <p
                                class="
                                    mt-1 text-xs
                                    text-slate-500
                                "
                            >
                                <span data-i18n="owners.properties_description">
    {{ __('ui.owners.properties_description') }}
</span>
                            </p>
                        </div>
                    </div>

                    <div
                        id="owner-properties-list"
                        class="
                            mt-4 grid gap-3
                            lg:grid-cols-2
                        "
                    ></div>
                </div>

                {{-- ====================================================
                     Ledger
                ==================================================== --}}

                <div
                    class="
                        border-b border-slate-100
                        px-6 py-6
                    "
                >
                    <div>
                        <h3
                            class="
                                text-base font-semibold
                                text-slate-950
                            "
                        >
                            <span data-i18n="owners.owner_ledger">
    {{ __('ui.owners.owner_ledger') }}
</span>
                        </h3>

                        <p
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            <span data-i18n="owners.ledger_description">
    {{ __('ui.owners.ledger_description') }}
</span>
                        </p>
                    </div>

                    <div
                        id="owner-ledger-list"
                        class="mt-4"
                    ></div>

                    <div
                        id="owner-ledger-pagination"
                        class="
                            mt-4 hidden
                            border-t border-slate-100
                            pt-4
                        "
                    ></div>
                </div>

                {{-- ====================================================
                     <span data-i18n="owners.payout_history">
    {{ __('ui.owners.payout_history') }}
</span>
                ==================================================== --}}

                <div
                    class="
                        px-6 py-6
                    "
                >
                    <div>
                        <h3
                            class="
                                text-base font-semibold
                                text-slate-950
                            "
                        >
                            <span data-i18n="owners.payout_history">
    {{ __('ui.owners.payout_history') }}
</span>
                        </h3>

                        <p
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            <span data-i18n="owners.payout_history_description">
    {{ __('ui.owners.payout_history_description') }}
</span>
                        </p>
                    </div>

                    <div
                        id="owner-payouts-list"
                        class="mt-4"
                    ></div>
                </div>

            </div>
        </section>
    </div>
</div>
{{-- ================================================================
     Owner Deposit Drawer
================================================================ --}}

<x-drawer
    id="owner-deposit-modal"
    backdrop-id="owner-deposit-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-deposit-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.record_owner_deposit">
    {{ __('ui.owners.record_owner_deposit') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.deposit_description">
    {{ __('ui.owners.deposit_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-deposit-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-deposit-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700
                "
            ></div>

            <div
                class="
                    grid gap-4
                    md:grid-cols-2
                "
            >
                <div>
                    <label
                        for="owner-deposit-amount"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <span
                            class="
                                pointer-events-none
                                absolute inset-y-0 left-0
                                flex items-center pl-3.5
                                text-sm text-slate-500
                            "
                         data-currency-display>
                        </span>

                        <input
                            id="owner-deposit-amount"
                            type="number"
                            min="1"
                            step="1"
                            required
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                py-2.5 pl-14 pr-3.5
                                text-sm outline-none
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        >
                    </div>
                </div>

                <div>
                    <label
                        for="owner-deposit-date"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.deposit_date">
    {{ __('ui.owners.deposit_date') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-deposit-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
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
                        for="owner-deposit-method"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.payment_method">
    {{ __('ui.owners.payment_method') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="owner-deposit-method"
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
                        <option value="bank_transfer" data-i18n="owners.bank_transfer">
    {{ __('ui.owners.bank_transfer') }}
</option>

                        <option value="momo" data-i18n="owners.momo">
    {{ __('ui.owners.momo') }}
</option>

                        <option value="cash" data-i18n="owners.cash">
    {{ __('ui.owners.cash') }}
</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-purpose"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.deposit_purpose">
    {{ __('ui.owners.deposit_purpose') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="owner-deposit-purpose"
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
                        <option value="general_funding" data-i18n="owners.general_funding">
    {{ __('ui.owners.general_funding') }}
</option>

                        <option value="property_expense" data-i18n="owners.property_expense">
    {{ __('ui.owners.property_expense') }}
</option>

                        <option value="repair_maintenance" data-i18n="owners.repair_maintenance_static">
    {{ __('ui.owners.repair_maintenance_static') }}
</option>

                        <option value="other" data-i18n="owners.other">
    {{ __('ui.owners.other') }}
</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-building"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.building">
    {{ __('ui.owners.building') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <select
                        id="owner-deposit-building"
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
                        <option value="" data-i18n="owners.no_specific_building">
    {{ __('ui.owners.no_specific_building') }}
</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-unit"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.unit">
    {{ __('ui.owners.unit') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <select
                        id="owner-deposit-unit"
                        disabled
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm outline-none
                            disabled:bg-slate-50
                            disabled:text-slate-400
                            focus:border-patrimoine-500
                            focus:ring-2
                            focus:ring-patrimoine-100
                        "
                    >
                        <option value="" data-i18n="owners.select_building_first">
    {{ __('ui.owners.select_building_first') }}
</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-reference"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-deposit-reference"
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

                <div
                    id="owner-deposit-collector-wrapper"
                    class="hidden"
                >
                    <label
                        for="owner-deposit-collector"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.collector">
    {{ __('ui.owners.collector') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-deposit-collector"
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

                <div class="md:col-span-2">
                    <label
                        for="owner-deposit-notes"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="owners.notes">
    {{ __('ui.owners.notes') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <textarea
                        id="owner-deposit-notes"
                        rows="3"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                            focus:border-patrimoine-500
                            focus:ring-2
                            focus:ring-patrimoine-100
                        "
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-deposit-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="owners.cancel">
    {{ __('ui.owners.cancel') }}
</span>
            </button>

            <button
                id="owner-deposit-submit"
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
     Owner Expense Drawer
================================================================ --}}

<x-drawer
    id="owner-expense-modal"
    backdrop-id="owner-expense-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-expense-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.record_property_expense">
    {{ __('ui.owners.record_property_expense') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.expense_description">
    {{ __('ui.owners.expense_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-expense-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-expense-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700
                "
            ></div>

            <div
                id="owner-expense-sharing-warning"
                class="
                    mb-5 hidden rounded-xl
                    border border-amber-200
                    bg-amber-50 px-4 py-3
                    text-sm leading-6 text-amber-800
                "
            ></div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label
                        for="owner-expense-building"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.building">
    {{ __('ui.owners.building') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="owner-expense-building"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm outline-none
                            focus:border-patrimoine-500
                            focus:ring-2 focus:ring-patrimoine-100
                        "
                    >
                        <option value="" data-i18n="owners.select_building">
    {{ __('ui.owners.select_building') }}
</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-expense-unit"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.unit">
    {{ __('ui.owners.unit') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <select
                        id="owner-expense-unit"
                        disabled
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm outline-none
                            disabled:bg-slate-50
                            disabled:text-slate-400
                        "
                    >
                        <option value="" data-i18n="owners.select_building_first">
    {{ __('ui.owners.select_building_first') }}
</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-expense-description"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.description">
    {{ __('ui.owners.description') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-expense-description"
                        type="text"
                        maxlength="255"
                        required
                        placeholder="{{ __('ui.owners.expense_description_placeholder') }}" data-i18n-placeholder="owners.expense_description_placeholder"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                            focus:border-patrimoine-500
                            focus:ring-2 focus:ring-patrimoine-100
                        "
                    >
                </div>

                <div>
                    <label
                        for="owner-expense-amount"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <span
                            class="
                                absolute inset-y-0 left-0
                                flex items-center pl-3.5
                                text-sm text-slate-500
                            "
                         data-currency-display>
                        </span>

                        <input
                            id="owner-expense-amount"
                            type="number"
                            min="1"
                            step="1"
                            required
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                py-2.5 pl-14 pr-3.5
                                text-sm outline-none
                            "
                        >
                    </div>
                </div>

                <div>
                    <label
                        for="owner-expense-date"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.expense_date">
    {{ __('ui.owners.expense_date') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-expense-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-expense-reference"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-expense-reference"
                        type="text"
                        maxlength="255"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-expense-notes"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.notes">
    {{ __('ui.owners.notes') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <textarea
                        id="owner-expense-notes"
                        rows="3"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-expense-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="owners.cancel">
    {{ __('ui.owners.cancel') }}
</span>
            </button>

            <button
                id="owner-expense-submit"
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
     Owner Payout Drawer
================================================================ --}}

<x-drawer
    id="owner-payout-modal"
    backdrop-id="owner-payout-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-payout-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.make_owner_payout">
    {{ __('ui.owners.make_owner_payout') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.payout_description">
    {{ __('ui.owners.payout_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-payout-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-payout-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700
                "
            ></div>

            <div
                class="
                    mb-5 rounded-xl
                    border border-slate-200
                    bg-slate-50 p-4
                "
            >
                <div class="text-xs font-medium uppercase text-slate-500">
                    <span data-i18n="owners.available_owner_balance">
    {{ __('ui.owners.available_owner_balance') }}
</span>
                </div>

                <div
                    id="owner-payout-available-balance"
                    class="
                        mt-2 text-2xl font-semibold
                        text-slate-950
                    "
                >
                    —
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="owner-payout-amount"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-payout-amount"
                        type="number"
                        min="1"
                        step="1"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div>
                    <label
                        for="owner-payout-date"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.payout_date">
    {{ __('ui.owners.payout_date') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-payout-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div>
                    <label
                        for="owner-payout-method"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.payment_method">
    {{ __('ui.owners.payment_method') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="owner-payout-method"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                        <option value="bank_transfer" data-i18n="owners.bank_transfer">
    {{ __('ui.owners.bank_transfer') }}
</option>

                        <option value="momo" data-i18n="owners.momo">
    {{ __('ui.owners.momo') }}
</option>

                        <option value="cash" data-i18n="owners.cash">
    {{ __('ui.owners.cash') }}
</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-payout-reference"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-payout-reference"
                        type="text"
                        maxlength="255"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-payout-notes"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.notes">
    {{ __('ui.owners.notes') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <textarea
                        id="owner-payout-notes"
                        rows="3"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-payout-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="owners.cancel">
    {{ __('ui.owners.cancel') }}
</span>
            </button>

            <button
                id="owner-payout-submit"
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
     Owner Adjustment Drawer
================================================================ --}}

<x-drawer
    id="owner-adjustment-modal"
    backdrop-id="owner-adjustment-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-adjustment-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.owner_account_adjustment">
    {{ __('ui.owners.owner_account_adjustment') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.adjustment_description">
    {{ __('ui.owners.adjustment_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-adjustment-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-adjustment-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700
                "
            ></div>

            <div
                class="
                    mb-5 rounded-xl
                    border border-amber-200
                    bg-amber-50 px-4 py-3
                    text-sm leading-6 text-amber-800
                "
            >
                <span data-i18n="owners.adjustment_warning">
    {{ __('ui.owners.adjustment_warning') }}
</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="owner-adjustment-direction"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.direction">
    {{ __('ui.owners.direction') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="owner-adjustment-direction"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                        <option value="credit" data-i18n="owners.credit_increase_balance">
    {{ __('ui.owners.credit_increase_balance') }}
</option>

                        <option value="debit" data-i18n="owners.debit_reduce_balance">
    {{ __('ui.owners.debit_reduce_balance') }}
</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-adjustment-amount"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-adjustment-amount"
                        type="number"
                        min="1"
                        step="1"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div>
                    <label
                        for="owner-adjustment-date"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.adjustment_date">
    {{ __('ui.owners.adjustment_date') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-adjustment-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div>
                    <label
                        for="owner-adjustment-reference"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-slate-400">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-adjustment-reference"
                        type="text"
                        maxlength="255"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-adjustment-reason"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        <span data-i18n="owners.reason">
    {{ __('ui.owners.reason') }}
</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        id="owner-adjustment-reason"
                        rows="4"
                        maxlength="1000"
                        required
                        placeholder="{{ __('ui.owners.adjustment_reason_placeholder') }}" data-i18n-placeholder="owners.adjustment_reason_placeholder"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                        "
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-adjustment-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="owners.cancel">
    {{ __('ui.owners.cancel') }}
</span>
            </button>

            <button
                id="owner-adjustment-submit"
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
