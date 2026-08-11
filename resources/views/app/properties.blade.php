@extends('layouts.app')

@section('title', 'Properties — Patrimoine')

@section('content')

<div class="mx-auto max-w-[1600px]">

    {{-- Page Header --}}
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
                Portfolio
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                Properties
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Manage buildings, ownership and individual units.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button
                id="add-property-button"
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

                Add Property
            </button>
        </div>
    </div>

    {{-- Page Error --}}
    <div
        id="properties-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    {{-- Portfolio Summary --}}
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
                Buildings
            </div>

            <div
                id="properties-building-count"
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
                Total Units
            </div>

            <div
                id="properties-unit-count"
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
                Single-Unit Properties
            </div>

            <div
                id="properties-single-unit-count"
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
                Multi-Unit Properties
            </div>

            <div
                id="properties-multi-unit-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- Property Portfolio --}}
    <section
        class="
            rounded-xl border border-slate-200
            bg-white shadow-sm
        "
    >
        <div
            class="
                flex flex-col gap-4
                border-b border-slate-100
                px-5 py-4
                sm:flex-row sm:items-center
                sm:justify-between
            "
        >
            <div>
                <h2
                    class="
                        text-base font-semibold
                        text-slate-950
                    "
                >
                    Property Portfolio
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Buildings and their associated units.
                </p>
            </div>

            <div class="w-full sm:w-80">
                <label
                    for="property-search"
                    class="sr-only"
                >
                    Search properties
                </label>

                <div class="relative">
                    <svg
                        class="
                            pointer-events-none absolute
                            left-3 top-1/2 h-4 w-4
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
                        id="property-search"
                        type="search"
                        placeholder="Search buildings or locations..."
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white py-2.5
                            pl-9 pr-3
                            text-sm text-slate-900
                            outline-none transition
                            placeholder:text-slate-400
                            focus:border-patrimoine-500
                            focus:ring-2
                            focus:ring-patrimoine-100
                        "
                    >
                </div>
            </div>
        </div>

        <div
            id="properties-list"
            class="p-5"
        >
            <div class="text-sm text-slate-400">
                Loading properties…
            </div>
        </div>

        <div
            id="properties-pagination"
            class="
                hidden border-t border-slate-100
                px-5 py-4
            "
        ></div>
    </section>

</div>


{{-- ================================================================
     ADD / EDIT PROPERTY MODAL
     ================================================================ --}}

<div
    id="property-modal"
    class="
        fixed inset-0 z-[70] hidden
        overflow-y-auto
    "
    aria-hidden="true"
>
    <div
        id="property-modal-backdrop"
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
                    <h2
                        id="property-modal-title"
                        class="
                            text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        Add Property
                    </h2>

                    <p
                        id="property-modal-description"
                        class="
                            mt-1 text-sm
                            text-slate-500
                        "
                    >
                        Create a building, define its ownership and add its units.
                    </p>
                </div>

                <button
                    id="property-modal-close"
                    type="button"
                    class="
                        inline-flex h-9 w-9
                        shrink-0 items-center justify-center
                        rounded-lg
                        text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                    aria-label="Close"
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

            <form id="property-form">
                <div
                    class="
                        max-h-[calc(100vh-12rem)]
                        overflow-y-auto
                        px-6 py-6
                    "
                >
                    {{-- Form Error --}}
                    <div
                        id="property-form-error"
                        class="
                            mb-5 hidden rounded-lg
                            border border-red-200
                            bg-red-50 px-4 py-3
                            text-sm text-red-700
                        "
                    ></div>

                    {{-- Property Details --}}
                    <section>
                        <div class="mb-4">
                            <h3
                                class="
                                    text-sm font-semibold
                                    text-slate-950
                                "
                            >
                                Property Details
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Basic information identifying the building.
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
                                    for="property-name"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Property Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="property-name"
                                    name="name"
                                    type="text"
                                    required
                                    maxlength="255"
                                    placeholder="e.g. Airport Residential Apartments"
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
                                    for="property-location"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Location
                                </label>

                                <input
                                    id="property-location"
                                    name="location"
                                    type="text"
                                    maxlength="255"
                                    placeholder="e.g. Airport Residential, Accra"
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
                                    for="property-address"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Address
                                </label>

                                <input
                                    id="property-address"
                                    name="address"
                                    type="text"
                                    placeholder="Street or property address"
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
                                    for="property-description"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-slate-700
                                    "
                                >
                                    Description
                                </label>

                                <textarea
                                    id="property-description"
                                    name="description"
                                    rows="3"
                                    placeholder="Optional property description"
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

                    {{-- Ownership --}}
                    <section
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div
                            class="
                                mb-4 flex flex-col gap-3
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
                                    Ownership
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Ownership must total exactly 100%.
                                </p>
                            </div>

                            <div
                                class="
                                    flex flex-wrap
                                    items-center gap-2
                                "
                            >
                                <div
                                    id="ownership-total"
                                    class="
                                        rounded-full
                                        bg-slate-100
                                        px-3 py-1.5
                                        text-xs font-semibold
                                        text-slate-600
                                    "
                                >
                                    Total: 0%
                                </div>

                                <button
                                    id="add-owner-button"
                                    type="button"
                                    class="
                                        rounded-lg
                                        border border-slate-200
                                        bg-white px-3 py-2
                                        text-xs font-medium
                                        text-slate-700
                                        transition
                                        hover:bg-slate-50
                                    "
                                >
                                    + Add Owner
                                </button>
                            </div>
                        </div>

                        <div
                            id="property-owner-rows"
                            class="space-y-3"
                        ></div>
                    </section>

                    {{-- Units

                         This section is shown when creating a Property.

                         When editing a Property, app.js hides it because
                         existing Units are managed independently through
                         their own Add/Edit workflow.
                    --}}
                    <section
                        id="property-units-section"
                        class="
                            mt-8 border-t
                            border-slate-100 pt-7
                        "
                    >
                        <div
                            class="
                                mb-4 flex flex-col gap-3
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
                                    Units
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Every property must contain at least one leasable unit.
                                </p>
                            </div>

                            <button
                                id="add-unit-button"
                                type="button"
                                class="
                                    rounded-lg
                                    border border-slate-200
                                    bg-white px-3 py-2
                                    text-xs font-medium
                                    text-slate-700
                                    transition
                                    hover:bg-slate-50
                                "
                            >
                                + Add Unit
                            </button>
                        </div>

                        <div
                            id="property-unit-rows"
                            class="space-y-3"
                        ></div>
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
                        id="property-cancel-button"
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
                        id="property-submit-button"
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
                        Create Property
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================================================================
     INLINE OWNER CREATION MODAL
     ================================================================ --}}

<div
    id="owner-modal"
    class="
        fixed inset-0 z-[80] hidden
        overflow-y-auto
    "
    aria-hidden="true"
>
    <div
        id="owner-modal-backdrop"
        class="
            fixed inset-0
            bg-slate-950/60
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
                relative w-full max-w-2xl
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
                        class="
                            text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        Create Owner
                    </h2>

                    <p
                        class="
                            mt-1 text-sm
                            text-slate-500
                        "
                    >
                        Create an Owner Party and assign it to this property.
                    </p>
                </div>

                <button
                    id="owner-modal-close"
                    type="button"
                    class="
                        inline-flex h-9 w-9
                        items-center justify-center
                        rounded-lg text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                    aria-label="Close"
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

            <form id="owner-form">
                <div class="px-6 py-6">

                    {{-- Error --}}
                    <div
                        id="owner-form-error"
                        class="
                            mb-5 hidden rounded-lg
                            border border-red-200
                            bg-red-50 px-4 py-3
                            text-sm text-red-700
                        "
                    ></div>

                    {{-- Owner Type --}}
                    <div class="mb-5">
                        <label
                            for="owner-type"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            Owner Type
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="owner-type"
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
                    </div>

                    {{-- Person Fields --}}
                    <div
                        id="owner-person-fields"
                        class="space-y-4"
                    >
                        <div>
                            <label
                                for="owner-name"
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
                                id="owner-name"
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
                            class="
                                grid gap-4
                                sm:grid-cols-2
                            "
                        >
                            <div>
                                <label
                                    for="owner-phone"
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
                                    id="owner-phone"
                                    type="text"
                                    maxlength="50"
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
                                    for="owner-email"
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
                                    id="owner-email"
                                    type="email"
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
                        </div>
                    </div>

                    {{-- Organisation / Association Fields --}}
                    <div
                        id="owner-organisation-fields"
                        class="hidden space-y-4"
                    >
                        <div>
                            <label
                                for="owner-legal-name"
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
                                id="owner-legal-name"
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

                        <div>
                            <label
                                for="owner-contact-name"
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
                                id="owner-contact-name"
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
                            class="
                                grid gap-4
                                sm:grid-cols-2
                            "
                        >
                            <div>
                                <label
                                    for="owner-contact-phone"
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
                                    id="owner-contact-phone"
                                    type="text"
                                    maxlength="50"
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
                                    for="owner-contact-email"
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
                                    id="owner-contact-email"
                                    type="email"
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
                        </div>
                    </div>

                    {{-- Common Address --}}
                    <div class="mt-4">
                        <label
                            for="owner-address"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            Address
                        </label>

                        <input
                            id="owner-address"
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
                    </div>
                </div>

                {{-- Footer --}}
                <div
                    class="
                        flex items-center
                        justify-end gap-3
                        border-t border-slate-100
                        bg-slate-50
                        px-6 py-4
                    "
                >
                    <button
                        id="owner-cancel-button"
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
                        id="owner-submit-button"
                        type="submit"
                        class="
                            rounded-lg
                            bg-patrimoine-950
                            px-5 py-2.5
                            text-sm font-medium
                            text-white shadow-sm
                            transition
                            hover:bg-patrimoine-900
                            disabled:cursor-not-allowed
                            disabled:opacity-60
                        "
                    >
                        Create Owner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================================================================
     ADD / EDIT UNIT MODAL
     ================================================================ --}}

<div
    id="existing-unit-modal"
    class="
        fixed inset-0 z-[80] hidden
        overflow-y-auto
    "
    aria-hidden="true"
>
    <div
        id="existing-unit-modal-backdrop"
        class="
            fixed inset-0
            bg-slate-950/60
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
                relative w-full max-w-xl
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
                        id="existing-unit-modal-title"
                        class="
                            text-xl font-semibold
                            tracking-tight text-slate-950
                        "
                    >
                        Add Unit
                    </h2>

                    <p
                        id="existing-unit-modal-description"
                        class="
                            mt-1 text-sm
                            text-slate-500
                        "
                    >
                        Add a leasable unit to an existing property.
                    </p>
                </div>

                <button
                    id="existing-unit-modal-close"
                    type="button"
                    class="
                        inline-flex h-9 w-9
                        items-center justify-center
                        rounded-lg text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                    aria-label="Close"
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

            <form id="existing-unit-form">
                <div class="px-6 py-6">

                    {{-- Error --}}
                    <div
                        id="existing-unit-form-error"
                        class="
                            mb-5 hidden rounded-lg
                            border border-red-200
                            bg-red-50 px-4 py-3
                            text-sm text-red-700
                        "
                    ></div>

                    {{-- Parent Property --}}
                    <div
                        class="
                            mb-5 rounded-xl
                            border border-patrimoine-100
                            bg-patrimoine-50
                            px-4 py-3
                        "
                    >
                        <div
                            class="
                                text-xs font-medium
                                text-patrimoine-600
                            "
                        >
                            Property
                        </div>

                        <div
                            id="existing-unit-building-name"
                            class="
                                mt-1 text-sm font-semibold
                                text-patrimoine-950
                            "
                        >
                            —
                        </div>
                    </div>

                    <input
                        id="existing-unit-building-id"
                        type="hidden"
                    >

                    {{-- Unit Name --}}
                    <div>
                        <label
                            for="existing-unit-name"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            Unit Name / Number
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="existing-unit-name"
                            type="text"
                            required
                            maxlength="255"
                            placeholder="e.g. Apartment A2"
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                px-3.5 py-2.5
                                text-sm outline-none
                                transition
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        >
                    </div>

                    {{-- Description --}}
                    <div class="mt-4">
                        <label
                            for="existing-unit-description"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-slate-700
                            "
                        >
                            Description
                        </label>

                        <textarea
                            id="existing-unit-description"
                            rows="3"
                            placeholder="Optional unit description"
                            class="
                                w-full resize-y rounded-lg
                                border border-slate-200
                                px-3.5 py-2.5
                                text-sm outline-none
                                transition
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        ></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div
                    class="
                        flex items-center
                        justify-end gap-3
                        border-t border-slate-100
                        bg-slate-50
                        px-6 py-4
                    "
                >
                    <button
                        id="existing-unit-cancel-button"
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
                        id="existing-unit-submit-button"
                        type="submit"
                        class="
                            rounded-lg
                            bg-patrimoine-950
                            px-5 py-2.5
                            text-sm font-medium
                            text-white shadow-sm
                            transition
                            hover:bg-patrimoine-900
                            disabled:cursor-not-allowed
                            disabled:opacity-60
                        "
                    >
                        Add Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
