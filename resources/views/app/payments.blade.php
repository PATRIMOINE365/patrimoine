@extends('layouts.app')

@section('title', __('ui.payments.title'))
@section('title-i18n', 'payments.title')

@section('content')

<div class="pm-payments-page pm-page mx-auto max-w-[1600px]">

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
                <span data-i18n="payments.finance">{{ __('ui.payments.finance') }}</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="payments.heading">{{ __('ui.payments.heading') }}</span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="payments.page_description">{{ __('ui.payments.page_description') }}</span>
            </p>
        </div>

        <button
            id="record-payment-button"
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

            <span data-i18n="payments.record_payment">{{ __('ui.payments.record_payment') }}</span>
        </button>
    </div>

    {{-- Page-level API Error --}}

    <div
        id="payments-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    {{-- ============================================================
         Payment Summary
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
                <span data-i18n="payments.received_this_month">{{ __('ui.payments.received_this_month') }}</span>
            </div>

            <div
                id="payments-received-month"
                class="
                    mt-3 text-2xl font-semibold
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
                <span data-i18n="payments.tenant_payments">{{ __('ui.payments.tenant_payments') }}</span>
            </div>

            <div
                id="payments-tenant-total"
                class="
                    mt-3 text-2xl font-semibold
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
                <span data-i18n="payments.owner_deposits">{{ __('ui.payments.owner_deposits') }}</span>
            </div>

            <div
                id="payments-owner-total"
                class="
                    mt-3 text-2xl font-semibold
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
                <span data-i18n="payments.transactions">{{ __('ui.payments.transactions') }}</span>
            </div>

            <div
                id="payments-transaction-count"
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
         Payment Register
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
                        <span data-i18n="payments.register">{{ __('ui.payments.register') }}</span>
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        <span data-i18n="payments.register_description">{{ __('ui.payments.register_description') }}</span>
                    </p>
                </div>

                <div
                    class="
                        grid w-full gap-3
                        sm:grid-cols-2 xl:grid-cols-4
                        xl:w-auto
                    "
                >
                    <div class="sm:min-w-44">
                        <label
                            for="payment-source-filter"
                            class="sr-only"
                        >
                            <span data-i18n="payments.payment_source">{{ __('ui.payments.payment_source') }}</span>
                        </label>

                        <select
                            id="payment-source-filter"
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
                            <option value="">
                                <span data-i18n="payments.all_sources">{{ __('ui.payments.all_sources') }}</span>
                            </option>

                            <option value="tenant">
                                <span data-i18n="payments.tenant_payments">{{ __('ui.payments.tenant_payments') }}</span>
                            </option>

                            <option value="owner">
                                <span data-i18n="payments.owner_deposits">{{ __('ui.payments.owner_deposits') }}</span>
                            </option>
                        </select>
                    </div>

                    <div class="sm:min-w-44">
                        <label
                            for="payment-method-filter"
                            class="sr-only"
                        >
                            <span data-i18n="payments.payment_method">{{ __('ui.payments.payment_method') }}</span>
                        </label>

                        <select
                            id="payment-method-filter"
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
                            <option value="">
                                <span data-i18n="payments.all_methods">{{ __('ui.payments.all_methods') }}</span>
                            </option>

                            <option value="cash">
                                <span data-i18n="payments.cash">{{ __('ui.payments.cash') }}</span>
                            </option>

                            <option value="bank_transfer">
                                <span data-i18n="payments.bank_transfer">{{ __('ui.payments.bank_transfer') }}</span>
                            </option>

                            <option value="momo">
                                <span data-i18n="payments.momo">{{ __('ui.payments.momo') }}</span>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-from-filter"
                            class="sr-only"
                        >
                            <span data-i18n="payments.from_date">{{ __('ui.payments.from_date') }}</span>
                        </label>

                        <input
                            id="payment-from-filter"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            autocomplete="off"
                            data-pm-date-input
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
                    </div>

                    <div>
                        <label
                            for="payment-to-filter"
                            class="sr-only"
                        >
                            <span data-i18n="payments.to_date">{{ __('ui.payments.to_date') }}</span>
                        </label>

                        <input
                            id="payment-to-filter"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            autocomplete="off"
                            data-pm-date-input
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
                    </div>
                </div>
            </div>
        </div>

        <div
            id="payments-list"
            class="p-5"
        >
            <div class="text-sm text-slate-400">
                <span data-i18n="payments.loading">{{ __('ui.payments.loading') }}</span>
            </div>
        </div>

        <div
            id="payments-pagination"
            class="
                hidden border-t
                border-slate-100
                px-5 py-4
            "
        ></div>
    </section>

</div>
{{-- ================================================================
     Record Payment Drawer
================================================================ --}}

<x-drawer
    id="payment-modal"
    backdrop-id="payment-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        close-id="close-payment-modal-button"
        close-label="Close"
        close-label-key="payments.close"
    >
        <x-slot:title>
            <span data-i18n="payments.record_payment">
                {{ __('ui.payments.record_payment') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="payments.record_description">
                {{ __('ui.payments.record_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="payment-form"
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
                            id="payment-form-error"
                            class="
                                mb-5 hidden rounded-xl
                                border border-red-200
                                bg-red-50 px-4 py-3
                                text-sm text-red-700
                            "
                        ></div>

                        {{-- ====================================================
                             Payment Source
                        ==================================================== --}}

                        <section>
                            <div class="mb-4">
                                <h3
                                    class="
                                        text-sm font-semibold
                                        text-slate-950
                                    "
                                >
                                    <span data-i18n="payments.payment_source">{{ __('ui.payments.payment_source') }}</span>
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    <span data-i18n="payments.source_description">{{ __('ui.payments.source_description') }}</span>
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label
                                    class="
                                        flex cursor-pointer items-start gap-3
                                        rounded-xl border border-slate-200
                                        p-4 transition
                                        hover:border-patrimoine-300
                                        hover:bg-patrimoine-50/40
                                    "
                                >
                                    <input
                                        id="payment-source-tenant"
                                        type="radio"
                                        name="payment_source"
                                        value="tenant"
                                        checked
                                        class="
                                            mt-1 h-4 w-4
                                            border-slate-300
                                            text-patrimoine-950
                                            focus:ring-patrimoine-500
                                        "
                                    >

                                    <span>
                                        <span
                                            class="
                                                block text-sm font-semibold
                                                text-slate-900
                                            "
                                        >
                                            <span data-i18n="payments.tenant_payment">{{ __('ui.payments.tenant_payment') }}</span>
                                        </span>

                                        <span
                                            class="
                                                mt-1 block text-xs
                                                leading-5 text-slate-500
                                            "
                                        >
                                            <span data-i18n="payments.tenant_payment_description">{{ __('ui.payments.tenant_payment_description') }}</span>
                                        </span>
                                    </span>
                                </label>

                                <label
                                    class="
                                        flex cursor-pointer items-start gap-3
                                        rounded-xl border border-slate-200
                                        p-4 transition
                                        hover:border-patrimoine-300
                                        hover:bg-patrimoine-50/40
                                    "
                                >
                                    <input
                                        id="payment-source-owner"
                                        type="radio"
                                        name="payment_source"
                                        value="owner"
                                        class="
                                            mt-1 h-4 w-4
                                            border-slate-300
                                            text-patrimoine-950
                                            focus:ring-patrimoine-500
                                        "
                                    >

                                    <span>
                                        <span
                                            class="
                                                block text-sm font-semibold
                                                text-slate-900
                                            "
                                        >
                                            <span data-i18n="payments.property_owner">{{ __('ui.payments.property_owner') }}</span>
                                        </span>

                                        <span
                                            class="
                                                mt-1 block text-xs
                                                leading-5 text-slate-500
                                            "
                                        >
                                            <span data-i18n="payments.owner_payment_description">{{ __('ui.payments.owner_payment_description') }}</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </section>

                        {{-- ====================================================
                             Tenant Payment
                        ==================================================== --}}

                        <section
                            id="tenant-payment-section"
                            class="
                                mt-7 border-t
                                border-slate-100 pt-6
                            "
                        >
                            <div class="mb-4">
                                <h3
                                    class="
                                        text-sm font-semibold
                                        text-slate-950
                                    "
                                >
                                    <span data-i18n="payments.tenant">{{ __('ui.payments.tenant') }}</span>
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    <span data-i18n="payments.tenant_search_description">{{ __('ui.payments.tenant_search_description') }}</span>
                                </p>
                            </div>

                            <div class="relative">
                                <label
                                    for="tenant-payment-search"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="payments.search_tenant">{{ __('ui.payments.search_tenant') }}</span>
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="tenant-payment-search"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="{{ __('ui.payments.search_party_placeholder') }}" data-i18n-placeholder="payments.search_party_placeholder"
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

                                <div
                                    id="tenant-payment-search-results"
                                    class="
                                        absolute z-20 mt-1 hidden
                                        max-h-64 w-full overflow-y-auto
                                        rounded-xl border
                                        border-slate-200
                                        bg-white shadow-lg
                                    "
                                ></div>
                            </div>

                            <input
                                id="tenant-payment-party-id"
                                type="hidden"
                            >

                            <div
                                id="tenant-payment-selected"
                                class="
                                    mt-3 hidden rounded-xl
                                    border border-patrimoine-200
                                    bg-patrimoine-50/50
                                    p-4
                                "
                            >
                                <div
                                    class="
                                        flex items-start
                                        justify-between gap-4
                                    "
                                >
                                    <div>
                                        <div
                                            id="tenant-payment-selected-name"
                                            class="
                                                text-sm font-semibold
                                                text-slate-950
                                            "
                                        ></div>

                                        <div
                                            id="tenant-payment-selected-meta"
                                            class="
                                                mt-1 text-xs
                                                text-slate-500
                                            "
                                        ></div>
                                    </div>

                                    <button
                                        id="clear-tenant-payment-button"
                                        type="button"
                                        class="
                                            text-xs font-medium
                                            text-patrimoine-700
                                            hover:text-patrimoine-950
                                        "
                                    >
                                        <span data-i18n="payments.change">{{ __('ui.payments.change') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label
                                    for="tenant-payment-lease"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="payments.lease_property">{{ __('ui.payments.lease_property') }}</span>
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="tenant-payment-lease"
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
                                    <option value="">
                                        <span data-i18n="payments.search_select_tenant_first">{{ __('ui.payments.search_select_tenant_first') }}</span>
                                    </option>
                                </select>

                                <p
                                    id="tenant-payment-lease-help"
                                    class="mt-1.5 text-xs text-slate-500"
                                >
                                    <span data-i18n="payments.lease_fifo_help">{{ __('ui.payments.lease_fifo_help') }}</span>
                                </p>
                            </div>
                        </section>

                        {{-- ====================================================
                             Owner Deposit
                        ==================================================== --}}

                        <section
                            id="owner-payment-section"
                            class="
                                mt-7 hidden border-t
                                border-slate-100 pt-6
                            "
                        >
                            <div class="mb-4">
                                <h3
                                    class="
                                        text-sm font-semibold
                                        text-slate-950
                                    "
                                >
                                    <span data-i18n="payments.property_owner">{{ __('ui.payments.property_owner') }}</span>
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    <span data-i18n="payments.owner_search_description">{{ __('ui.payments.owner_search_description') }}</span>
                                </p>
                            </div>

                            <div class="relative">
                                <label
                                    for="owner-payment-search"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    <span data-i18n="payments.search_owner">{{ __('ui.payments.search_owner') }}</span>
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="owner-payment-search"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="{{ __('ui.payments.search_party_placeholder') }}" data-i18n-placeholder="payments.search_party_placeholder"
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

                                <div
                                    id="owner-payment-search-results"
                                    class="
                                        absolute z-20 mt-1 hidden
                                        max-h-64 w-full overflow-y-auto
                                        rounded-xl border
                                        border-slate-200
                                        bg-white shadow-lg
                                    "
                                ></div>
                            </div>

                            <input
                                id="owner-payment-account-id"
                                type="hidden"
                            >

                            <div
                                id="owner-payment-selected"
                                class="
                                    mt-3 hidden rounded-xl
                                    border border-patrimoine-200
                                    bg-patrimoine-50/50
                                    p-4
                                "
                            >
                                <div
                                    class="
                                        flex items-start
                                        justify-between gap-4
                                    "
                                >
                                    <div>
                                        <div
                                            id="owner-payment-selected-name"
                                            class="
                                                text-sm font-semibold
                                                text-slate-950
                                            "
                                        ></div>

                                        <div
                                            id="owner-payment-selected-meta"
                                            class="
                                                mt-1 text-xs
                                                text-slate-500
                                            "
                                        ></div>

                                        <div class="mt-2 text-xs text-slate-500">
                                            <span data-i18n="payments.current_owner_balance">{{ __('ui.payments.current_owner_balance') }}</span>
                                            <strong
                                                id="owner-payment-selected-balance"
                                                class="text-slate-800"
                                            >
                                                —
                                            </strong>
                                        </div>
                                    </div>

                                    <button
                                        id="clear-owner-payment-button"
                                        type="button"
                                        class="
                                            text-xs font-medium
                                            text-patrimoine-700
                                            hover:text-patrimoine-950
                                        "
                                    >
                                        <span data-i18n="payments.change">{{ __('ui.payments.change') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div
                                class="
                                    mt-4 grid gap-4
                                    md:grid-cols-2
                                "
                            >
                                <div>
                                    <label
                                        for="owner-payment-purpose"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.deposit_purpose">{{ __('ui.payments.deposit_purpose') }}</span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        id="owner-payment-purpose"
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
                                        <option value="general_funding">
                                            <span data-i18n="payments.general_funding">{{ __('ui.payments.general_funding') }}</span>
                                        </option>

                                        <option value="property_expense">
                                            <span data-i18n="payments.property_expense">{{ __('ui.payments.property_expense') }}</span>
                                        </option>

                                        <option value="repair_maintenance">
                                            <span data-i18n="payments.repair_maintenance">{{ __('ui.payments.repair_maintenance') }}</span>
                                        </option>

                                        <option value="other">
                                            <span data-i18n="payments.other">{{ __('ui.payments.other') }}</span>
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="owner-payment-building"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.building">{{ __('ui.payments.building') }}</span>
                                        <span class="text-xs text-slate-400">
                                            <span data-i18n="payments.optional">{{ __('ui.payments.optional') }}</span>
                                        </span>
                                    </label>

                                    <select
                                        id="owner-payment-building"
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
                                        <option value="">
                                            <span data-i18n="payments.no_specific_building">{{ __('ui.payments.no_specific_building') }}</span>
                                        </option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label
                                        for="owner-payment-unit"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.unit">{{ __('ui.payments.unit') }}</span>
                                        <span class="text-xs text-slate-400">
                                            <span data-i18n="payments.optional">{{ __('ui.payments.optional') }}</span>
                                        </span>
                                    </label>

                                    <select
                                        id="owner-payment-unit"
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
                                        <option value="">
                                            <span data-i18n="payments.select_building_first">{{ __('ui.payments.select_building_first') }}</span>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        {{-- ====================================================
                             Payment Details
                        ==================================================== --}}

                        <section
                            class="
                                mt-7 border-t
                                border-slate-100 pt-6
                            "
                        >
                            <div class="mb-4">
                                <h3
                                    class="
                                        text-sm font-semibold
                                        text-slate-950
                                    "
                                >
                                    <span data-i18n="payments.payment_details">{{ __('ui.payments.payment_details') }}</span>
                                </h3>
                            </div>

                            <div
                                class="
                                    grid gap-4
                                    md:grid-cols-2
                                "
                            >
                                <div>
                                    <label
                                        for="payment-amount"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.amount">{{ __('ui.payments.amount') }}</span>
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
                                            —
                                        </span>

                                        <input
                                            id="payment-amount"
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
                                        for="payment-date"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.payment_date">{{ __('ui.payments.payment_date') }}</span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <div class="pm-payment-date-control">
                                        <input
                                            id="payment-date"
                                            type="text"
                                            data-payment-date-input
                                            inputmode="numeric"
                                            maxlength="10"
                                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                            required
                                            class="
                                                w-full rounded-lg
                                                border border-slate-200
                                                px-3.5 py-2.5
                                                pr-11
                                                text-sm outline-none
                                                focus:border-patrimoine-500
                                                focus:ring-2
                                                focus:ring-patrimoine-100
                                            "
                                        >

                                        <button
                                            type="button"
                                            class="pm-payment-date-picker-button"
                                            data-payment-date-picker="payment-date"
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
                                            class="pm-payment-native-date-picker"
                                            tabindex="-1"
                                            aria-hidden="true"
                                            data-payment-native-date-picker="payment-date"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="payment-method"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.payment_method">{{ __('ui.payments.payment_method') }}</span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        id="payment-method"
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
                                        <option value="bank_transfer">
                                            <span data-i18n="payments.bank_transfer">{{ __('ui.payments.bank_transfer') }}</span>
                                        </option>

                                        <option value="momo">
                                            <span data-i18n="payments.momo">{{ __('ui.payments.momo') }}</span>
                                        </option>

                                        <option value="cash">
                                            <span data-i18n="payments.cash">{{ __('ui.payments.cash') }}</span>
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="payment-reference"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.reference">{{ __('ui.payments.reference') }}</span>
                                        <span class="text-xs text-slate-400">
                                            <span data-i18n="payments.optional">{{ __('ui.payments.optional') }}</span>
                                        </span>
                                    </label>

                                    <input
                                        id="payment-reference"
                                        type="text"
                                        maxlength="255"
                                        placeholder="{{ __('ui.payments.reference_placeholder') }}" data-i18n-placeholder="payments.reference_placeholder"
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
                                    id="payment-collector-wrapper"
                                    class="hidden md:col-span-2"
                                >
                                    <label
                                        for="payment-collector"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.collector">{{ __('ui.payments.collector') }}</span>
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="payment-collector"
                                        type="text"
                                        maxlength="255"
                                        placeholder="{{ __('ui.payments.collector_placeholder') }}" data-i18n-placeholder="payments.collector_placeholder"
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

                                    <p class="mt-1.5 text-xs text-slate-500">
                                        <span data-i18n="payments.collector_help">{{ __('ui.payments.collector_help') }}</span>
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label
                                        for="payment-notes"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        <span data-i18n="payments.notes">{{ __('ui.payments.notes') }}</span>
                                        <span class="text-xs text-slate-400">
                                            <span data-i18n="payments.optional">{{ __('ui.payments.optional') }}</span>
                                        </span>
                                    </label>

                                    <textarea
                                        id="payment-notes"
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
                        </section>
        </div>

        <x-drawer-footer>
            <button
                id="cancel-payment-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="payments.cancel">
                    {{ __('ui.payments.cancel') }}
                </span>
            </button>

            <button
                id="submit-payment-button"
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
     Tenant Fund Classification Drawer
================================================================ --}}

<x-drawer
    id="tenant-fund-modal"
    backdrop-id="tenant-fund-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        description-id="tenant-fund-modal-description"
        close-id="close-tenant-fund-modal-button"
        close-label="Close"
        close-label-key="payments.close"
    >
        <x-slot:title>
            <span data-i18n="payments.manage_funds">
                {{ __('ui.payments.manage_funds') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="payments.manage_funds_description">
                {{ __('ui.payments.manage_funds_description') }}
            </span>
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
            id="tenant-fund-error"
            class="
                mb-5 hidden rounded-xl
                border border-red-200
                bg-red-50 px-4 py-3
                text-sm text-red-700
            "
        ></div>

        <div
            id="tenant-fund-loading"
            class="
                py-12 text-center
                text-sm text-[var(--pm-text-muted)]
            "
        >
            <span data-i18n="payments.loading_position">
                {{ __('ui.payments.loading_position') }}
            </span>
        </div>

        <div
            id="tenant-fund-content"
            class="hidden"
        >
            {{-- Payment position --}}

            <section>
                <div class="pm-tenant-fund-position-grid">
                    <div class="pm-tenant-fund-stat">
                        <div class="pm-tenant-fund-stat-label">
                            <span data-i18n="payments.received">
                                {{ __('ui.payments.received') }}
                            </span>
                        </div>

                        <div
                            id="tenant-fund-payment-amount"
                            class="pm-tenant-fund-stat-value"
                        >
                            —
                        </div>
                    </div>

                    <div class="pm-tenant-fund-stat">
                        <div class="pm-tenant-fund-stat-label">
                            <span data-i18n="payments.allocated_to_invoices">
                                {{ __('ui.payments.allocated_to_invoices') }}
                            </span>
                        </div>

                        <div
                            id="tenant-fund-allocated"
                            class="pm-tenant-fund-stat-value"
                        >
                            —
                        </div>
                    </div>

                    <div class="pm-tenant-fund-stat">
                        <div class="pm-tenant-fund-stat-label">
                            <span data-i18n="payments.unapplied">
                                {{ __('ui.payments.unapplied') }}
                            </span>
                        </div>

                        <div
                            id="tenant-fund-unallocated"
                            class="pm-tenant-fund-stat-value"
                        >
                            —
                        </div>
                    </div>

                    <div class="pm-tenant-fund-stat">
                        <div class="pm-tenant-fund-stat-label">
                            <span data-i18n="payments.classified">
                                {{ __('ui.payments.classified') }}
                            </span>
                        </div>

                        <div
                            id="tenant-fund-classified"
                            class="pm-tenant-fund-stat-value"
                        >
                            —
                        </div>
                    </div>

                    <div
                        class="
                            pm-tenant-fund-stat
                            pm-tenant-fund-stat-emphasis
                        "
                    >
                        <div class="pm-tenant-fund-stat-label">
                            <span data-i18n="payments.available">
                                {{ __('ui.payments.available') }}
                            </span>
                        </div>

                        <div
                            id="tenant-fund-remaining"
                            class="pm-tenant-fund-stat-value"
                        >
                            —
                        </div>
                    </div>
                </div>
            </section>

            <div
                id="tenant-fund-complete-message"
                class="
                    mt-5 hidden rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)]
                    px-4 py-4
                    text-sm text-[var(--pm-text-secondary)]
                "
            >
                <span data-i18n="payments.no_money_remaining">
                    {{ __('ui.payments.no_money_remaining') }}
                </span>
            </div>

            <form
                id="tenant-fund-form"
                class="
                    mt-6 hidden
                    border-t border-[var(--pm-border)]
                    pt-6
                "
            >
                <div>
                    <h3
                        class="
                            text-sm font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="payments.classify_remaining_money">
                            {{ __('ui.payments.classify_remaining_money') }}
                        </span>
                    </h3>

                    <p
                        class="
                            mt-1 text-xs
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span data-i18n="payments.classify_description">
                            {{ __('ui.payments.classify_description') }}
                        </span>
                    </p>
                </div>

                <div
                    class="
                        mt-5 grid gap-4
                        md:grid-cols-2
                    "
                >
                    <div>
                        <label
                            for="tenant-fund-type"
                            class="pm-field-label"
                        >
                            <span data-i18n="payments.fund">
                                {{ __('ui.payments.fund') }}
                            </span>
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="tenant-fund-type"
                            required
                            class="pm-input"
                        >
                            <option value="">
                                <span data-i18n="payments.select_fund">
                                    {{ __('ui.payments.select_fund') }}
                                </span>
                            </option>

                            <option value="rent_reserve">
                                <span data-i18n="payments.rent_reserve">
                                    {{ __('ui.payments.rent_reserve') }}
                                </span>
                            </option>

                            <option value="consumable_advance">
                                <span data-i18n="payments.consumable_advance">
                                    {{ __('ui.payments.consumable_advance') }}
                                </span>
                            </option>

                            <option value="security_deposit">
                                <span data-i18n="payments.security_deposit">
                                    {{ __('ui.payments.security_deposit') }}
                                </span>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="tenant-fund-amount"
                            class="pm-field-label"
                        >
                            <span data-i18n="payments.amount">
                                {{ __('ui.payments.amount') }}
                            </span>
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="tenant-fund-amount"
                            type="number"
                            min="1"
                            step="1"
                            required
                            class="pm-input"
                        >

                        <p
                            id="tenant-fund-amount-help"
                            class="
                                mt-1.5 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        ></p>
                    </div>

                    <div>
                        <label
                            for="tenant-fund-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="payments.transaction_date">
                                {{ __('ui.payments.transaction_date') }}
                            </span>
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="tenant-fund-date"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            autocomplete="off"
                            data-pm-date-input
                            required
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="tenant-fund-reference"
                            class="pm-field-label"
                        >
                            <span data-i18n="payments.reference">
                                {{ __('ui.payments.reference') }}
                            </span>
                        </label>

                        <input
                            id="tenant-fund-reference"
                            type="text"
                            maxlength="255"
                            placeholder="{{ __('ui.payments.optional_placeholder') }}"
                            data-i18n-placeholder="payments.optional_placeholder"
                            class="pm-input"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label
                            for="tenant-fund-notes"
                            class="pm-field-label"
                        >
                            <span data-i18n="payments.notes">
                                {{ __('ui.payments.notes') }}
                            </span>
                        </label>

                        <textarea
                            id="tenant-fund-notes"
                            rows="3"
                            placeholder="{{ __('ui.payments.classification_notes_placeholder') }}"
                            data-i18n-placeholder="payments.classification_notes_placeholder"
                            class="pm-input"
                        ></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-drawer-footer id="tenant-fund-footer">
        <button
            id="cancel-tenant-fund-button"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="payments.cancel">
                {{ __('ui.payments.cancel') }}
            </span>
        </button>

        <button
            id="tenant-fund-submit-button"
            type="submit"
            form="tenant-fund-form"
            class="pm-button-primary"
        >
            <span data-i18n="actions.save">
                {{ __('ui.actions.save') }}
            </span>
        </button>
    </x-drawer-footer>
</x-drawer>

@endsection
