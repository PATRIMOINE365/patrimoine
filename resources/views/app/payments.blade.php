@extends('layouts.app')

@section('title', 'Payments — Patrimoine')
@section('title-i18n', 'payments.title')

@section('content')

<div class="mx-auto max-w-[1600px]">

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
                <span data-i18n="payments.finance">Finance</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="payments.heading">Payments</span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="payments.page_description">Record and review money received from tenants and property owners.</span>
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

            <span data-i18n="payments.record_payment">Record Payment</span>
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
                <span data-i18n="payments.received_this_month">Received This Month</span>
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
                <span data-i18n="payments.tenant_payments">Tenant Payments</span>
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
                <span data-i18n="payments.owner_deposits">Owner Deposits</span>
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
                <span data-i18n="payments.transactions">Transactions</span>
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
                        <span data-i18n="payments.register">Payment Register</span>
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        <span data-i18n="payments.register_description">Incoming payments recorded in Patrimoine.</span>
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
                            <span data-i18n="payments.payment_source">Payment Source</span>
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
                                <span data-i18n="payments.all_sources">All Sources</span>
                            </option>

                            <option value="tenant">
                                <span data-i18n="payments.tenant_payments">Tenant Payments</span>
                            </option>

                            <option value="owner">
                                <span data-i18n="payments.owner_deposits">Owner Deposits</span>
                            </option>
                        </select>
                    </div>

                    <div class="sm:min-w-44">
                        <label
                            for="payment-method-filter"
                            class="sr-only"
                        >
                            <span data-i18n="payments.payment_method">Payment Method</span>
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
                                <span data-i18n="payments.all_methods">All Methods</span>
                            </option>

                            <option value="cash">
                                <span data-i18n="payments.cash">Cash</span>
                            </option>

                            <option value="bank_transfer">
                                <span data-i18n="payments.bank_transfer">Bank Transfer</span>
                            </option>

                            <option value="momo">
                                <span data-i18n="payments.momo">MoMo</span>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-from-filter"
                            class="sr-only"
                        >
                            <span data-i18n="payments.from_date">From Date</span>
                        </label>

                        <input
                            id="payment-from-filter"
                            type="date"
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
                            <span data-i18n="payments.to_date">To Date</span>
                        </label>

                        <input
                            id="payment-to-filter"
                            type="date"
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
                <span data-i18n="payments.loading">Loading payments…</span>
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
     Record Payment Modal
================================================================ --}}

<div
    id="payment-modal"
    class="
        fixed inset-0 z-50 hidden
        items-center justify-center
        bg-slate-950/50
        px-4 py-6
    "
>
    <div
        class="
            flex max-h-[92vh] w-full max-w-3xl
            flex-col overflow-hidden
            rounded-2xl bg-white
            shadow-2xl
        "
    >
        {{-- Header --}}
        <div
            class="
                flex items-start justify-between
                border-b border-slate-100
                px-6 py-5
            "
        >
            <div>
                <h2
                    class="
                        text-xl font-semibold
                        tracking-tight text-slate-950
                    "
                >
                    <span data-i18n="payments.record_payment">Record Payment</span>
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    <span data-i18n="payments.record_description">Record money received from a Tenant or Property Owner.</span>
                </p>
            </div>

            <button
                id="close-payment-modal-button"
                type="button"
                aria-label="Close" data-i18n-aria-label="payments.close"
                class="
                    rounded-lg p-2
                    text-slate-400 transition
                    hover:bg-slate-100
                    hover:text-slate-700
                "
            >
                <svg
                    class="h-5 w-5"
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

        {{-- Body --}}
        <form
            id="payment-form"
            class="
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
                        <span data-i18n="payments.payment_source">Payment Source</span>
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        <span data-i18n="payments.source_description">Select who provided the money.</span>
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
                                <span data-i18n="payments.tenant_payment">Tenant Payment</span>
                            </span>

                            <span
                                class="
                                    mt-1 block text-xs
                                    leading-5 text-slate-500
                                "
                            >
                                <span data-i18n="payments.tenant_payment_description">Rent, arrears or other Lease-related money
                                received from a Tenant.</span>
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
                                <span data-i18n="payments.property_owner">Property Owner</span>
                            </span>

                            <span
                                class="
                                    mt-1 block text-xs
                                    leading-5 text-slate-500
                                "
                            >
                                <span data-i18n="payments.owner_payment_description">Funds supplied by an Owner for property
                                expenses, repairs or general funding.</span>
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
                        <span data-i18n="payments.tenant">Tenant</span>
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        <span data-i18n="payments.tenant_search_description">Search for the Tenant rather than selecting from a
                        fixed list.</span>
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
                        <span data-i18n="payments.search_tenant">Search Tenant</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="tenant-payment-search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search by name, phone or email..." data-i18n-placeholder="payments.search_party_placeholder"
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
                            <span data-i18n="payments.change">Change</span>
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
                        <span data-i18n="payments.lease_property">Lease / Property</span>
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
                            <span data-i18n="payments.search_select_tenant_first">Search and select a Tenant first</span>
                        </option>
                    </select>

                    <p
                        id="tenant-payment-lease-help"
                        class="mt-1.5 text-xs text-slate-500"
                    >
                        <span data-i18n="payments.lease_fifo_help">Payments are recorded against the applicable Lease
                        so rent can be allocated FIFO.</span>
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
                        <span data-i18n="payments.property_owner">Property Owner</span>
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        <span data-i18n="payments.owner_search_description">Search for the Owner whose account should receive
                        the deposit.</span>
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
                        <span data-i18n="payments.search_owner">Search Owner</span>
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="owner-payment-search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search by name, phone or email..." data-i18n-placeholder="payments.search_party_placeholder"
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
                                <span data-i18n="payments.current_owner_balance">Current Owner Balance:</span>
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
                            <span data-i18n="payments.change">Change</span>
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
                            <span data-i18n="payments.deposit_purpose">Deposit Purpose</span>
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
                                <span data-i18n="payments.general_funding">General Funding</span>
                            </option>

                            <option value="property_expense">
                                <span data-i18n="payments.property_expense">Property Expense</span>
                            </option>

                            <option value="repair_maintenance">
                                <span data-i18n="payments.repair_maintenance">Repair & Maintenance</span>
                            </option>

                            <option value="other">
                                <span data-i18n="payments.other">Other</span>
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
                            <span data-i18n="payments.building">Building</span>
                            <span class="text-xs text-slate-400">
                                <span data-i18n="payments.optional">(Optional)</span>
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
                                <span data-i18n="payments.no_specific_building">No specific Building</span>
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
                            <span data-i18n="payments.unit">Unit</span>
                            <span class="text-xs text-slate-400">
                                <span data-i18n="payments.optional">(Optional)</span>
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
                                <span data-i18n="payments.select_building_first">Select a Building first</span>
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
                        <span data-i18n="payments.payment_details">Payment Details</span>
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
                            <span data-i18n="payments.amount">Amount</span>
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
                                GHS
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
                            <span data-i18n="payments.payment_date">Payment Date</span>
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="payment-date"
                            type="date"
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
                            for="payment-method"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            <span data-i18n="payments.payment_method">Payment Method</span>
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
                                <span data-i18n="payments.bank_transfer">Bank Transfer</span>
                            </option>

                            <option value="momo">
                                <span data-i18n="payments.momo">MoMo</span>
                            </option>

                            <option value="cash">
                                <span data-i18n="payments.cash">Cash</span>
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
                            <span data-i18n="payments.reference">Reference</span>
                            <span class="text-xs text-slate-400">
                                <span data-i18n="payments.optional">(Optional)</span>
                            </span>
                        </label>

                        <input
                            id="payment-reference"
                            type="text"
                            maxlength="255"
                            placeholder="Transaction or deposit reference" data-i18n-placeholder="payments.reference_placeholder"
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
                            <span data-i18n="payments.collector">Collector</span>
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="payment-collector"
                            type="text"
                            maxlength="255"
                            placeholder="Name of person who received the cash" data-i18n-placeholder="payments.collector_placeholder"
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
                            <span data-i18n="payments.collector_help">Required for cash payments for accountability.</span>
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
                            <span data-i18n="payments.notes">Notes</span>
                            <span class="text-xs text-slate-400">
                                <span data-i18n="payments.optional">(Optional)</span>
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
        </form>

        {{-- Footer --}}
        <div
            class="
                flex items-center justify-end gap-3
                border-t border-slate-100
                bg-slate-50/70
                px-6 py-4
            "
        >
            <button
                id="cancel-payment-button"
                type="button"
                class="
                    rounded-lg border border-slate-200
                    bg-white px-4 py-2.5
                    text-sm font-medium
                    text-slate-700
                    transition
                    hover:bg-slate-50
                "
            >
                <span data-i18n="payments.cancel">Cancel</span>
            </button>

            <button
                id="submit-payment-button"
                type="submit"
                form="payment-form"
                class="
                    rounded-lg bg-patrimoine-950
                    px-4 py-2.5
                    text-sm font-medium text-white
                    shadow-sm transition
                    hover:bg-patrimoine-900
                    disabled:cursor-not-allowed
                    disabled:opacity-60
                "
            >
                <span data-i18n="payments.record_payment">Record Payment</span>
            </button>
        </div>
    </div>
</div>

{{-- ================================================================
     Tenant Fund Classification Modal
================================================================ --}}

<div
    id="tenant-fund-modal"
    class="
        fixed inset-0 z-[70] hidden
        items-center justify-center
        bg-slate-950/50
        px-4 py-6
    "
    aria-hidden="true"
>
    <div
        class="
            flex max-h-[92vh] w-full max-w-3xl
            flex-col overflow-hidden
            rounded-2xl bg-white
            shadow-2xl
        "
    >
        {{-- Header --}}

        <div
            class="
                flex items-start justify-between
                border-b border-slate-100
                px-6 py-5
            "
        >
            <div>
                <p
                    class="
                        text-xs font-medium uppercase
                        tracking-wide text-patrimoine-700
                    "
                >
                    <span data-i18n="payments.tenant_payment">Tenant Payment</span>
                </p>

                <h2
                    class="
                        mt-1 text-xl font-semibold
                        tracking-tight text-slate-950
                    "
                >
                    <span data-i18n="payments.manage_funds">Manage Funds</span>
                </h2>

                <p
                    id="tenant-fund-modal-description"
                    class="mt-1 text-sm text-slate-500"
                >
                    <span data-i18n="payments.manage_funds_description">Classify unapplied tenant money into held funds.</span>
                </p>
            </div>

            <button
                id="close-tenant-fund-modal-button"
                type="button"
                aria-label="Close" data-i18n-aria-label="payments.close"
                class="
                    rounded-lg p-2
                    text-slate-400 transition
                    hover:bg-slate-100
                    hover:text-slate-700
                "
            >
                <svg
                    class="h-5 w-5"
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

        {{-- Body --}}

        <div class="overflow-y-auto px-6 py-6">

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
                    text-sm text-slate-400
                "
            >
                <span data-i18n="payments.loading_position">Loading Payment position…</span>
            </div>

            <div
                id="tenant-fund-content"
                class="hidden"
            >
                {{-- Payment position --}}

                <section>
                    <div
                        class="
                            grid gap-3
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
                                <span data-i18n="payments.received">Received</span>
                            </div>

                            <div
                                id="tenant-fund-payment-amount"
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
                                <span data-i18n="payments.allocated_to_invoices">Allocated to Invoices</span>
                            </div>

                            <div
                                id="tenant-fund-allocated"
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
                                <span data-i18n="payments.unapplied">Unapplied</span>
                            </div>

                            <div
                                id="tenant-fund-unallocated"
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
                                rounded-xl border border-blue-200
                                bg-blue-50 p-4
                            "
                        >
                            <div class="text-xs text-blue-700">
                                <span data-i18n="payments.classified">Classified</span>
                            </div>

                            <div
                                id="tenant-fund-classified"
                                class="
                                    mt-2 text-lg font-semibold
                                    text-blue-900
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
                                <span data-i18n="payments.available">Available</span>
                            </div>

                            <div
                                id="tenant-fund-remaining"
                                class="
                                    mt-2 text-lg font-semibold
                                    text-green-900
                                "
                            >
                                —
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Nothing remaining --}}

                <div
                    id="tenant-fund-complete-message"
                    class="
                        mt-5 hidden rounded-xl
                        border border-slate-200
                        bg-slate-50 px-4 py-4
                        text-sm text-slate-600
                    "
                >
                    <span data-i18n="payments.no_money_remaining">This Payment has no money remaining to classify.</span>
                </div>

                {{-- Allocation form --}}

                <form
                    id="tenant-fund-form"
                    class="
                        mt-6 hidden border-t
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
                            <span data-i18n="payments.classify_remaining_money">Classify Remaining Money</span>
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            <span data-i18n="payments.classify_description">Move unapplied Payment money into a dedicated
                            tenant-held fund.</span>
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
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="payments.fund">Fund</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="tenant-fund-type"
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
                                <option value="">
                                    <span data-i18n="payments.select_fund">Select fund…</span>
                                </option>

                                <option value="rent_reserve">
                                    <span data-i18n="payments.rent_reserve">Rent Reserve</span>
                                </option>

                                <option value="consumable_advance">
                                    <span data-i18n="payments.consumable_advance">Consumable Advance</span>
                                </option>

                                <option value="security_deposit">
                                    <span data-i18n="payments.security_deposit">Security Deposit</span>
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="tenant-fund-amount"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="payments.amount">Amount</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="tenant-fund-amount"
                                type="number"
                                min="1"
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

                            <p
                                id="tenant-fund-amount-help"
                                class="mt-1.5 text-xs text-slate-500"
                            ></p>
                        </div>

                        <div>
                            <label
                                for="tenant-fund-date"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="payments.transaction_date">Transaction Date</span>
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="tenant-fund-date"
                                type="date"
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
                                for="tenant-fund-reference"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="payments.reference">Reference</span>
                            </label>

                            <input
                                id="tenant-fund-reference"
                                type="text"
                                maxlength="255"
                                placeholder="Optional" data-i18n-placeholder="payments.optional_placeholder"
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
                                for="tenant-fund-notes"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                <span data-i18n="payments.notes">Notes</span>
                            </label>

                            <textarea
                                id="tenant-fund-notes"
                                rows="3"
                                placeholder="Optional classification notes" data-i18n-placeholder="payments.classification_notes_placeholder"
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

                    <div
                        class="
                            mt-5 flex justify-end
                        "
                    >
                        <button
                            id="tenant-fund-submit-button"
                            type="submit"
                            class="
                                rounded-lg bg-patrimoine-950
                                px-4 py-2.5
                                text-sm font-medium text-white
                                shadow-sm transition
                                hover:bg-patrimoine-900
                                disabled:cursor-not-allowed
                                disabled:opacity-60
                            "
                        >
                            <span data-i18n="payments.allocate_funds">Allocate Funds</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
