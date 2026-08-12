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
                            for="lease-unit"
                            class="
                                mb-1.5 flex items-center gap-1.5
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            Property / Unit

                            <x-field-help label="About Property and Unit">
                                Select the specific leasable unit covered by this agreement.
                                A unit can have historical leases, but it cannot have more than
                                one Active or Notice lease at the same time.
                            </x-field-help>

                            <span class="text-red-500">*</span>
                        </label>

                                <select
                                    id="lease-unit"
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
                                        Select unit…
                                    </option>
                                </select>
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
                                    Rent Amount

                                    <x-field-help label="About Rent Amount">
                                        The VAT-inclusive rent charged for one payment period.
                                        For example, with Monthly frequency this is the monthly rent;
                                        with Yearly frequency this is the yearly rent.
                                        Patrimoine stores money as whole Ghana cedis.
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
                                        Controls how often the Rent Amount becomes due:
                                        Monthly, Quarterly, every six months, or Yearly.
                                        The Rent Amount represents one complete period of the selected frequency.
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
                                        Patrimoine treats configured rent amounts as VAT inclusive.
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
                                Configure management fees and one-time Agent commission applicable to this Lease.
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
                                    Management Fee

                                    <x-field-help label="About Management Fee">
                                        Defines how the managing organisation earns its management fee
                                        from this lease. Choose None, a Percentage of rent, or a Fixed Amount.
                                        The actual value is entered in Fee Value.
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

                                    <x-field-help label="About Management Fee Value">
                                        The meaning depends on the Management Fee type.
                                        For Percentage, enter the percentage rate.
                                        For Fixed Amount, enter the monetary amount.
                                        When Management Fee is None, this must remain 0.
                                    </x-field-help>
                                </label>

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
