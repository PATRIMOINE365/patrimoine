@extends('layouts.app')

@section('title', 'Parties — Patrimoine')

@section('content')

<div class="mx-auto max-w-[1600px]">

    {{-- Page header --}}
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
                Contacts & Stakeholders
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                Parties
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Manage owners, tenants, agents, organisations and associations.
            </p>
        </div>

        <button
            id="add-party-button"
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

            Add Party
        </button>
    </div>

    {{-- Page-level error --}}
    <div
        id="parties-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
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
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                Total Parties
            </div>

            <div
                id="parties-total-count"
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
                People
            </div>

            <div
                id="parties-person-count"
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
                Organisations
            </div>

            <div
                id="parties-organisation-count"
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
                Multiple Roles
            </div>

            <div
                id="parties-multi-role-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- Party portfolio --}}
    <section
        class="
            rounded-xl border border-slate-200
            bg-white shadow-sm
        "
    >

        {{-- Filters --}}
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
                        Party Directory
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        People and entities participating in property operations.
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
                            Search Parties
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
                                id="party-search"
                                type="search"
                                placeholder="Search name, email, phone..."
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    bg-white py-2.5
                                    pl-9 pr-3
                                    text-sm
                                    outline-none transition
                                    placeholder:text-slate-400
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label
                            for="party-type-filter"
                            class="sr-only"
                        >
                            Party Type
                        </label>

                        <select
                            id="party-type-filter"
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
                                All Types
                            </option>

                            <option value="person">
                                People
                            </option>

                            <option value="organisation">
                                Organisations
                            </option>

                            <option value="association">
                                Associations
                            </option>
                        </select>
                    </div>

                    {{-- Role --}}
                    <div>
                        <label
                            for="party-role-filter"
                            class="sr-only"
                        >
                            Party Role
                        </label>

                        <select
                            id="party-role-filter"
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
                                All Roles
                            </option>

                            <option value="owner">
                                Owners
                            </option>

                            <option value="tenant">
                                Tenants
                            </option>

                            <option value="agent">
                                Agents
                            </option>

                            <option value="managing_organisation">
                                Managing Organisation
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
            <div class="text-sm text-slate-400">
                Loading parties…
            </div>
        </div>

        {{-- Pagination --}}
        <div
            id="parties-pagination"
            class="
                hidden border-t
                border-slate-100
                px-5 py-4
            "
        ></div>

    </section>
</div>

{{-- ================================================================
     Add / Edit Party Modal
================================================================ --}}

<div
    id="party-modal"
    class="
        fixed inset-0 z-[70]
        hidden overflow-y-auto
    "
    aria-hidden="true"
>
    <div
        id="party-modal-backdrop"
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
                    flex items-start
                    justify-between gap-5
                    border-b border-slate-100
                    px-6 py-5
                "
            >
                <div>
                    <h2
                        id="party-modal-title"
                        class="
                            text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        Add Party
                    </h2>

                    <p
                        id="party-modal-description"
                        class="mt-1 text-sm text-slate-500"
                    >
                        Create a person, organisation or association.
                    </p>
                </div>

                <button
                    id="party-modal-close"
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

            <form id="party-form">

                <div
                    class="
                        max-h-[calc(100vh-12rem)]
                        overflow-y-auto
                        px-6 py-6
                    "
                >

                    {{-- Validation errors --}}
                    <div
                        id="party-form-error"
                        class="
                            mb-5 hidden rounded-lg
                            border border-red-200
                            bg-red-50 px-4 py-3
                            text-sm text-red-700
                        "
                    ></div>

                    {{-- Type --}}
                    <section>
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                Party Type
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Select the legal nature of this Party.
                            </p>
                        </div>

                        <select
                            id="party-type"
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
                            <option value="person">
                                Person
                            </option>

                            <option value="organisation">
                                Organisation
                            </option>

                            <option value="association">
                                Association
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
                                Personal Details
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
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Full Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-name"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-phone"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Phone
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-phone"
                                    type="text"
                                    maxlength="50"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-email"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Email
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-email"
                                    type="email"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                            border-t border-slate-100
                            pt-7
                        "
                    >
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                Organisation Details
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
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Legal Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-legal-name"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    for="party-contact-name"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Contact Person
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-contact-name"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-contact-phone"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Contact Phone
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-contact-phone"
                                    type="text"
                                    maxlength="50"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-contact-email"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Contact Email
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="party-contact-email"
                                    type="email"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                                Contact & Identification
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Optional secondary contact and identification information.
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
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Alternate Phone
                                </label>

                                <input
                                    id="party-alternate-phone"
                                    type="text"
                                    maxlength="50"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-id-number"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    ID Number
                                </label>

                                <input
                                    id="party-id-number"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-registration-number"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Registration Number
                                </label>

                                <input
                                    id="party-registration-number"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-vat-tin"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    VAT / TIN
                                </label>

                                <input
                                    id="party-vat-tin"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    for="party-address"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Address
                                </label>

                                <textarea
                                    id="party-address"
                                    rows="2"
                                    class="
                                        w-full resize-y rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
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
                                Roles
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                A Party may perform several functions at the same time.
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
                                    border-slate-200
                                    px-4 py-3
                                "
                            >
                                <input
                                    id="party-role-owner"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-slate-300
                                        text-patrimoine-700
                                        focus:ring-patrimoine-500
                                    "
                                >

                                <span
                                    class="
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Owner
                                </span>
                            </label>

                            <label
                                class="
                                    flex cursor-pointer
                                    items-center gap-3
                                    rounded-xl border
                                    border-slate-200
                                    px-4 py-3
                                "
                            >
                                <input
                                    id="party-role-tenant"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-slate-300
                                        text-patrimoine-700
                                        focus:ring-patrimoine-500
                                    "
                                >

                                <span
                                    class="
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Tenant
                                </span>
                            </label>

                            <label
                                class="
                                    flex cursor-pointer
                                    items-center gap-3
                                    rounded-xl border
                                    border-slate-200
                                    px-4 py-3
                                "
                            >
                                <input
                                    id="party-role-agent"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-slate-300
                                        text-patrimoine-700
                                        focus:ring-patrimoine-500
                                    "
                                >

                                <span
                                    class="
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Agent
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
                                Banking Details
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Optional. Primarily used for Owners and Agents.
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
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Bank Name
                                </label>

                                <input
                                    id="party-bank-name"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-bank-branch"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Bank Branch
                                </label>

                                <input
                                    id="party-bank-branch"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-bank-account-name"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Account Name
                                </label>

                                <input
                                    id="party-bank-account-name"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>

                            <div>
                                <label
                                    for="party-bank-account-number"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Account Number
                                </label>

                                <input
                                    id="party-bank-account-number"
                                    type="text"
                                    maxlength="255"
                                    class="
                                        w-full rounded-lg
                                        border border-slate-200
                                        px-3.5 py-2.5
                                        text-sm
                                        outline-none transition
                                        focus:border-patrimoine-500
                                        focus:ring-2
                                        focus:ring-patrimoine-100
                                    "
                                >
                            </div>
                        </div>
                    </section>

                    {{-- Notes --}}
                    <section
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <label
                            for="party-notes"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            Notes
                        </label>

                        <textarea
                            id="party-notes"
                            rows="4"
                            placeholder="Optional internal notes"
                            class="
                                w-full resize-y rounded-lg
                                border border-slate-200
                                px-3.5 py-2.5
                                text-sm
                                outline-none transition
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
                        id="party-cancel-button"
                        type="button"
                        class="
                            rounded-lg
                            border border-slate-200
                            bg-white px-4 py-2.5
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        Cancel
                    </button>

                    <button
                        id="party-submit-button"
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
                        Create Party
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
