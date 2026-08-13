@extends('layouts.app')

@section('title', 'Leases — Patrimoine')

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
                Tenancy
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                Leases
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Manage tenancy agreements, rent terms and lease lifecycle.
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

            Add Lease
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
                Total Leases
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
                Active
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
                In Notice
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
                Draft
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
                        Lease Register
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Current and historical tenancy agreements.
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
                            Lease Status
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
                            <option value="">
                                All Statuses
                            </option>

                            <option value="draft">
                                Draft
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="notice">
                                Notice
                            </option>

                            <option value="terminated">
                                Terminated
                            </option>
                        </select>
                    </div>

                    <div class="sm:min-w-64">
                        <label
                            for="lease-tenant-filter"
                            class="sr-only"
                        >
                            Tenant
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
                            <option value="">
                                All Tenants
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
                Loading leases…
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
     Add / Edit Lease Modal
================================================================ --}}

<div
    id="lease-modal"
    class="
        fixed inset-0 z-[70]
        hidden overflow-y-auto
    "
    aria-hidden="true"
>
    <div
        id="lease-modal-backdrop"
        class="
            fixed inset-0
            bg-slate-950/50
            backdrop-blur-[1px]
        "
    ></div>

    <div
        class="
            relative flex min-h-full
            items-start justify-center
            p-4 sm:p-6 lg:p-10
        "
    >
        <div
            class="
                relative w-full max-w-5xl
                overflow-hidden rounded-2xl
                bg-white shadow-2xl
            "
        >

            {{-- Header --}}

            <div
                class="
                    flex items-start justify-between gap-5
                    border-b border-slate-100
                    px-6 py-5
                "
            >
                <div>
                    <h2
                        id="lease-modal-title"
                        class="
                            text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        Add Lease
                    </h2>

                    <p
                        id="lease-modal-description"
                        class="
                            mt-1 text-sm
                            text-slate-500
                        "
                    >
                        Create a tenancy agreement for a property unit.
                    </p>
                </div>

                <button
                    id="lease-modal-close"
                    type="button"
                    aria-label="Close"
                    class="
                        inline-flex h-9 w-9
                        shrink-0 items-center
                        justify-center rounded-lg
                        text-slate-400
                        transition
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

            <form id="lease-form">

                <div
                    class="
                        max-h-[calc(100vh-12rem)]
                        overflow-y-auto
                        px-6 py-6
                    "
                >

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
                                Property & Tenant
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Select the leased unit and parties to the agreement.
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
        Property / Unit

        <x-field-help label="About Property and Unit">
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
                placeholder="Search property, location, unit or owner…"
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
            >
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
            Selected Unit
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
            Ownership
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
                                    Tenant

                                    <x-field-help label="About Tenant">
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
                                    <option value="">
                                        Select tenant…
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
                                    Agent

                                    <x-field-help label="About Agent">
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
                                    <option value="">
                                        No Agent
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
                                Lease Period
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Define when the agreement takes effect and its current lifecycle state.
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
                                    Start Date

                                    <x-field-help label="About Start Date">
                                        The date the lease begins. Unless a Due Day Override is specified,
                                        Patrimoine uses the day of this date as the recurring rent due day.
                                    </x-field-help>

                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="lease-start-date"
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
                                    for="lease-end-date"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    End Date

                                    <x-field-help label="About End Date">
                                        Optional contractual end date. Leave this blank for a lease without
                                        a predetermined termination date.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-end-date"
                                    type="date"
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
                                    for="lease-status"
                                    class="
                                        mb-1.5 flex items-center gap-1.5
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Status

                                    <x-field-help label="About Lease Status">
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
                                    <option value="draft">
                                        Draft
                                    </option>

                                    <option value="active">
                                        Active
                                    </option>

                                    <option value="notice">
                                        Notice
                                    </option>

                                    <option value="terminated">
                                        Terminated
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
                                    Notice Date

                                    <x-field-help label="About Notice Date">
                                        The date termination notice was received or issued.
                                        This field becomes required when the Lease Status is Notice and
                                        will later control when Rent Reserve consumption begins.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-notice-date"
                                    type="date"
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
                                Rent Terms
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Amounts are VAT inclusive and stored as whole Ghana cedis.
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
                                    Monthly Rent

                                    <x-field-help label="About Monthly Rent">
                                    The VAT-inclusive monthly contractual rent for the Unit.
                                    Payment Frequency determines how many months are invoiced together.
                                    For example, GHS 5,000 Monthly Rent with Quarterly frequency creates
                                    a GHS 15,000 rent obligation for each quarterly billing period.
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
                                    Payment Frequency

                                    <x-field-help label="About Payment Frequency">
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
                                    <option value="monthly">
                                        Monthly
                                    </option>

                                    <option value="quarterly">
                                        Quarterly
                                    </option>

                                    <option value="bi_yearly">
                                        Bi-Yearly
                                    </option>

                                    <option value="yearly">
                                        Yearly
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
                                    Due Day Override

                                    <x-field-help label="About Due Day Override">
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
                                    placeholder="From start date"
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
                                    VAT Rate %

                                    <x-field-help label="About VAT Rate">
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
                                    Proration Override

                                    <x-field-help label="About Proration Override">
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
                                    placeholder="Automatic"
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
                                    Security Deposit

                                    <x-field-help label="About Security Deposit">
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
                                Advance Payment
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Record the contractual advance and how much should remain protected as Rent Reserve.
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
                                    Total Advance Payment

                                    <x-field-help label="About Advance Payment">
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
                                    Rent Reserve

                                    <x-field-help label="About Rent Reserve">
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
                                    Consumable Advance

                                    <x-field-help label="About Consumable Advance">
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
                                        GHS 0
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
                                        Advance already received

                                        <x-field-help label="About Advance already received">
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
                                        Use this when entering an existing or backdated Lease
                                        for which the tenant already paid the advance.
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
                                        Date Received
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="lease-advance-received-date"
                                        type="date"
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
                                        for="lease-advance-received-method"
                                        class="
                                            mb-1.5 flex items-center gap-1.5
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        Payment Method
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
                                        <option value="">
                                            Select method...
                                        </option>

                                        <option value="bank_transfer">
                                            Bank Transfer
                                        </option>

                                        <option value="momo">
                                            Mobile Money
                                        </option>

                                        <option value="cash">
                                            Cash
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
                                        Reference
                                    </label>

                                    <input
                                        id="lease-advance-received-reference"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Optional"
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
                                        Cash Collector
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="lease-advance-received-collector"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Person who received the cash"
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
                                Rent Increment
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Configure the next contractual rent increase where applicable.
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
                                    Increment Type

                                    <x-field-help label="About Rent Increment Type">
                                        Choose how the next rent increase is defined.
                                        Percentage increases the existing Monthly Rent by a rate.
                                        Fixed Amount adds a specific Ghana cedi amount.
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
                                    <option value="none">
                                        None
                                    </option>

                                    <option value="percentage">
                                        Percentage
                                    </option>

                                    <option value="fixed">
                                        Fixed Amount
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
                                    Increment Value

                                    <x-field-help label="About Rent Increment Value">
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
                                    Next Increment Date

                                    <x-field-help label="About Next Rent Increment Date">
                                        Date on which the configured increase should first take
                                        effect. Patrimoine V1 stores this contractual date but
                                        does not infer future recurring increases beyond it.
                                    </x-field-help>
                                </label>

                                <input
                                    id="lease-next-rent-increment-date"
                                    type="date"
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
                                Fees & Commission
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Configure the managing organisation fee and one-time Agent commission applicable to this Lease.
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
                                    Managing Organisation Fee

                                <x-field-help label="About Managing Organisation Fee">
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
                                    <option value="none">
                                        None
                                    </option>

                                    <option value="percentage">
                                        Percentage
                                    </option>

                                    <option value="fixed">
                                        Fixed Amount
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
                                    Fee Value

                                    <x-field-help label="About Managing Organisation Fee Value">
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
                                    Agent Commission

                                    <x-field-help label="About Agent Commission">
                                        One-time commission agreed with the Agent for this lease.
                                        Enter the total commission amount in whole Ghana cedis.
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
                            Notes

                            <x-field-help label="About Lease Notes">
                                Optional internal information about the agreement that does not
                                form part of Patrimoine's automated financial calculations.
                            </x-field-help>
                        </label>

                        <textarea
                            id="lease-notes"
                            rows="4"
                            placeholder="Optional lease notes"
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

                {{-- Footer --}}

                <div
                    class="
                        flex flex-col-reverse gap-3
                        border-t border-slate-100
                        bg-slate-50/70
                        px-6 py-4
                        sm:flex-row sm:justify-end
                    "
                >
                    <button
                        id="lease-cancel-button"
                        type="button"
                        class="
                            rounded-lg
                            border border-slate-200
                            bg-white px-4 py-2.5
                            text-sm font-medium
                            text-slate-700
                            hover:bg-slate-50
                        "
                    >
                        Cancel
                    </button>

                    <button
                        id="lease-submit-button"
                        type="submit"
                        class="
                            rounded-lg
                            bg-patrimoine-950
                            px-5 py-2.5
                            text-sm font-medium
                            text-white
                            shadow-sm transition
                            hover:bg-patrimoine-900
                            disabled:cursor-not-allowed
                            disabled:opacity-60
                        "
                    >
                        Create Lease
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
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

<div
    id="security-deposit-modal"
    class="
        fixed inset-0 z-[80]
        hidden overflow-y-auto
    "
    aria-hidden="true"
>
    <div
        id="security-deposit-modal-backdrop"
        class="
            fixed inset-0
            bg-slate-950/50
            backdrop-blur-[1px]
        "
    ></div>

    <div
        class="
            relative flex min-h-full
            items-start justify-center
            p-4 sm:p-6 lg:p-10
        "
    >
        <div
            class="
                relative w-full max-w-4xl
                overflow-hidden rounded-2xl
                bg-white shadow-2xl
            "
        >
            {{-- Header --}}

            <div
                class="
                    flex items-start justify-between gap-5
                    border-b border-slate-100
                    px-6 py-5
                "
            >
                <div>
                    <div
                        class="
                            text-xs font-medium uppercase
                            tracking-wide text-patrimoine-700
                        "
                    >
                        Lease Close-out
                    </div>

                    <h2
                        class="
                            mt-1 text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        Security Deposit
                    </h2>

                    <p
                        id="security-deposit-modal-description"
                        class="mt-1 text-sm text-slate-500"
                    >
                        Review held funds, itemized deductions and final settlement.
                    </p>
                </div>

                <button
                    id="security-deposit-modal-close"
                    type="button"
                    aria-label="Close"
                    class="
                        inline-flex h-9 w-9
                        shrink-0 items-center
                        justify-center rounded-lg
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

            <div
                class="
                    max-h-[calc(100vh-11rem)]
                    overflow-y-auto
                    px-6 py-6
                "
            >
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
                    Loading Security Deposit…
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
                                    Contractual Deposit
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
                                    Held Balance
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
                                    Deductions
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
                                    Refund
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
                                    Tenant Debt
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
                                    Itemized Deductions
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Charges retained from the tenant's Security Deposit.
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
                                        Description
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="security-deduction-description"
                                        type="text"
                                        maxlength="255"
                                        required
                                        placeholder="e.g. Damaged lock"
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
                                        Amount
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
                                        Deduction Date
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="security-deduction-date"
                                        type="date"
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
                                        for="security-deduction-reference"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        Reference
                                    </label>

                                    <input
                                        id="security-deduction-reference"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Inspection / work order reference"
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
                                        Notes
                                    </label>

                                    <input
                                        id="security-deduction-notes"
                                        type="text"
                                        placeholder="Optional details"
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
                                    Add Deduction
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
                                Final Settlement
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Finalize the Security Deposit and create the formal settlement voucher.
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
                                        Settlement Date
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="security-settlement-date"
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
                                        for="security-settlement-notes"
                                        class="
                                            mb-1.5 block
                                            text-sm font-medium
                                            text-slate-700
                                        "
                                    >
                                        Notes
                                    </label>

                                    <input
                                        id="security-settlement-notes"
                                        type="text"
                                        placeholder="Optional close-out notes"
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
                                Final settlement is irreversible. Once confirmed,
                                no additional Security Deposit deductions can be added.
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
                                    Finalize Settlement
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
                                        Security Deposit Settled
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

                                <a
                                    id="security-deposit-voucher-link"
                                    href="#"
                                    target="_blank"
                                    rel="noopener"
                                    class="
                                        inline-flex items-center
                                        justify-center rounded-lg
                                        border border-green-300
                                        bg-white px-4 py-2.5
                                        text-sm font-medium
                                        text-green-800
                                        transition
                                        hover:bg-green-100
                                    "
                                >
                                    Download Voucher
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
