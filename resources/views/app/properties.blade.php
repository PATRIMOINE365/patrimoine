@extends('layouts.app')

@section('title', __('ui.properties.title'))
@section('title-i18n', 'properties.title')

@section('content')

<div class="pm-properties-page mx-auto max-w-[1600px]">

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
                    text-[var(--pm-accent)]
                "
            >
                <span data-i18n="properties.portfolio">{{ __('ui.properties.portfolio') }}</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="properties.heading">{{ __('ui.properties.heading') }}</span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="properties.page_description">{{ __('ui.properties.page_description') }}</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button
                id="add-property-button"
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

                <span data-i18n="properties.add_property">{{ __('ui.properties.add_property') }}</span>
            </button>
        </div>
    </div>

    {{-- Page Error --}}
    <div
        id="properties-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- Activity Feedback Banner --}}
    <div
        id="properties-banner"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-success-border)]
            bg-[var(--pm-success-background)] px-4 py-3
            text-sm text-[var(--pm-success-text)]
        "
        role="status"
    ></div>

    {{-- Portfolio Summary --}}
    <div
        class="
            mb-6 grid gap-4
            sm:grid-cols-2 xl:grid-cols-4
        "
    >
        <div class="pm-card p-5 shadow-sm">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="properties.buildings">{{ __('ui.properties.buildings') }}</span>
            </div>

            <div
                id="properties-building-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div class="pm-card p-5 shadow-sm">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="properties.total_units">{{ __('ui.properties.total_units') }}</span>
            </div>

            <div
                id="properties-unit-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div class="pm-card p-5 shadow-sm">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="properties.single_unit_properties">{{ __('ui.properties.single_unit_properties') }}</span>
            </div>

            <div
                id="properties-single-unit-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div class="pm-card p-5 shadow-sm">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="properties.multi_unit_properties">{{ __('ui.properties.multi_unit_properties') }}</span>
            </div>

            <div
                id="properties-multi-unit-count"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- Property Portfolio --}}
    <section class="pm-card shadow-sm">
        <div
            class="
                flex flex-col gap-4
                border-b border-[var(--pm-border-subtle)]
                px-5 py-4
                sm:flex-row sm:items-center
                sm:justify-between
            "
        >
            <div>
                <h2
                    class="
                        text-base font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="properties.property_portfolio">{{ __('ui.properties.property_portfolio') }}</span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="properties.portfolio_description">{{ __('ui.properties.portfolio_description') }}</span>
                </p>
            </div>

            <div
                class="
                    flex w-full flex-col gap-3
                    sm:w-auto sm:flex-row
                    sm:items-center
                "
            >
                <div class="w-full sm:w-72">
                    <label
                        for="property-search"
                        class="sr-only"
                    >
                        <span data-i18n="properties.search">{{ __('ui.properties.search') }}</span>
                    </label>

                    <div class="relative">
                        <svg
                            class="
                                pointer-events-none absolute
                                left-3 top-1/2 h-4 w-4
                                -translate-y-1/2
                                text-[var(--pm-text-subtle)]
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
                            data-i18n-placeholder="properties.search_placeholder"
                            placeholder="{{ __('ui.properties.search_placeholder') }}"
                            class="pm-input pm-input-search"
                        >
                    </div>
                </div>

                <div class="w-full sm:w-48">
                    <label
                        for="property-classification-filter"
                        class="sr-only"
                    >
                        <span data-i18n="properties.filter_units_label">{{ __('ui.properties.filter_units_label') }}</span>
                    </label>

                    <select
                        id="property-classification-filter"
                        class="pm-input"
                    >
                        <option
                            value="all"
                            data-i18n="properties.filter_all_units"
                        >{{ __('ui.properties.filter_all_units') }}</option>

                        <option
                            value="commercial"
                            data-i18n="properties.commercial"
                        >{{ __('ui.properties.commercial') }}</option>

                        <option
                            value="residential"
                            data-i18n="properties.residential"
                        >{{ __('ui.properties.residential') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div
            id="properties-list"
            class="p-5"
        >
            <div class="text-sm text-[var(--pm-text-subtle)]">
                <span data-i18n="properties.loading">{{ __('ui.properties.loading') }}</span>
            </div>
        </div>

        <div
            id="properties-pagination"
            class="
                hidden border-t border-[var(--pm-border-subtle)]
                px-5 py-4
            "
        ></div>
    </section>

</div>


{{-- ================================================================
     ADD / EDIT PROPERTY DRAWER
     ================================================================ --}}

<x-drawer
    id="property-modal"
    backdrop-id="property-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="property-modal-title"
        description-id="property-modal-description"
        close-id="property-modal-close"
        close-label="Close"
        close-label-key="properties.close"
    >
        <x-slot:title>
            <span
                id="property-modal-title-text"
                data-i18n="properties.add_property"
            >
                {{ __('ui.properties.add_property') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span
                id="property-modal-description-text"
                data-i18n="properties.add_property_description"
            >
                {{ __('ui.properties.add_property_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

<form
    id="property-form"
    class="flex min-h-0 flex-1 flex-col"
>
    <div
        class="
            min-h-0 flex-1
            overflow-y-auto
            px-6 py-6
        "
    >
        {{-- Form Error --}}
        <div
            id="property-form-error"
            class="
                mb-5 hidden rounded-lg
                border border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)] px-4 py-3
                text-sm text-[var(--pm-danger-text)]
            "
        ></div>

        {{-- Property Details --}}
        <section>
            <div class="mb-4">
                <h3
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="properties.property_details">{{ __('ui.properties.property_details') }}</span>
                </h3>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="properties.property_details_description">{{ __('ui.properties.property_details_description') }}</span>
                </p>
            </div>

            <div
                class="
                    grid grid-cols-1 gap-4
                "
            >
                <div class="">
                    <label
                        for="property-name"
                        class="pm-field-label"
                    >
                        <span data-i18n="properties.property_name">{{ __('ui.properties.property_name') }}</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="property-name"
                        name="name"
                        type="text"
                        required
                        maxlength="255"
                        data-i18n-placeholder="properties.property_name_placeholder"
                        placeholder="e.g. Airport Residential Apartments"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="property-location"
                        class="pm-field-label"
                    >
                        <span data-i18n="properties.location">{{ __('ui.properties.location') }}</span>
                    </label>

                    <input
                        id="property-location"
                        name="location"
                        type="text"
                        maxlength="255"
                        data-i18n-placeholder="properties.location_placeholder"
                        placeholder="e.g. Airport Residential, Accra"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="property-address"
                        class="pm-field-label"
                    >
                        <span data-i18n="properties.address">{{ __('ui.properties.address') }}</span>
                    </label>

                    <input
                        id="property-address"
                        name="address"
                        type="text"
                        data-i18n-placeholder="properties.address_placeholder"
                        placeholder="Street or property address"
                        class="pm-input"
                    >
                </div>

                <div class="">
                    <label
                        for="property-description"
                        class="pm-field-label"
                    >
                        <span data-i18n="properties.description">{{ __('ui.properties.description') }}</span>
                    </label>

                    <textarea
                        id="property-description"
                        name="description"
                        rows="3"
                        data-i18n-placeholder="properties.optional_property_description"
                        placeholder="Optional property description"
                        class="pm-input resize-y"
                    ></textarea>
                </div>
            </div>
        </section>

        {{-- Ownership --}}
        <section
            class="
                mt-8 border-t
                border-[var(--pm-border-subtle)] pt-7
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
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="properties.ownership">{{ __('ui.properties.ownership') }}</span>
                    </h3>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="properties.ownership_description">{{ __('ui.properties.ownership_description') }}</span>
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
                            bg-[var(--pm-surface-muted)]
                            px-3 py-1.5
                            text-xs font-semibold
                            text-[var(--pm-text-secondary)]
                        "
                    >
                        <span data-i18n="properties.total">{{ __('ui.properties.total') }}</span>: 0%
                    </div>


                </div>
            </div>

            <div
                id="property-owner-rows"
                class="space-y-3"
            ></div>

            <div class="mt-3">
                <button
                                        id="add-owner-button"
                                        type="button"
                                        class="pm-property-section-add-action
                                            rounded-lg
                                            border border-[var(--pm-border)]
                                            bg-[var(--pm-surface)] px-3 py-2
                                            text-xs font-medium
                                            text-[var(--pm-text-secondary)]
                                            transition
                                            hover:bg-[var(--pm-hover)]
                                        "
                                    >
                                        <span data-i18n="properties.add_owner">{{ __('ui.properties.add_owner') }}</span>
                                    </button>
            </div>
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
                border-[var(--pm-border-subtle)] pt-7
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
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="properties.units">{{ __('ui.properties.units') }}</span>
                    </h3>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="properties.units_description">{{ __('ui.properties.units_description') }}</span>
                    </p>
                </div>


            </div>

            <div
                id="property-unit-rows"
                class="space-y-3"
            ></div>

            <div class="mt-3">
                <button
                                    id="add-unit-button"
                                    type="button"
                                    class="pm-property-section-add-action
                                        rounded-lg
                                        border border-[var(--pm-border)]
                                        bg-[var(--pm-surface)] px-3 py-2
                                        text-xs font-medium
                                        text-[var(--pm-text-secondary)]
                                        transition
                                        hover:bg-[var(--pm-hover)]
                                    "
                                >
                                    <span data-i18n="properties.add_unit">{{ __('ui.properties.add_unit') }}</span>
                                </button>
            </div>
        </section>
    </div>

    {{-- Footer --}}
    <x-drawer-footer>
        <button
            id="property-cancel-button"
            type="button"
            class="pm-button-secondary"
        >
                    <span data-i18n="actions.cancel">
                        {{ __('ui.actions.cancel') }}
                    </span>
                </button>

        <button
            id="property-submit-button"
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
     INLINE OWNER CREATION DRAWER
     ================================================================ --}}

<x-drawer
    id="owner-modal"
    backdrop-id="owner-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-modal-close"
        close-label="Close"
        close-label-key="properties.close"
    >
        <x-slot:title>
            <span data-i18n="properties.create_owner">{{ __('ui.properties.create_owner') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="properties.create_owner_description">{{ __('ui.properties.create_owner_description') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="owner-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >

            {{-- Error --}}
            <div
                id="owner-form-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            {{-- Owner Type --}}
            <div class="mb-5">
                <label
                    for="owner-type"
                    class="pm-field-label"
                >
                    <span data-i18n="properties.owner_type">{{ __('ui.properties.owner_type') }}</span>
                    <span class="text-[var(--pm-danger-text)]">*</span>
                </label>

                <select
                    id="owner-type"
                    required
                    class="pm-input"
                >
                    <option
                        value="person"
                        data-i18n="properties.person"
                    >{{ __('ui.properties.person') }}</option>

                    <option
                        value="organisation"
                        data-i18n="properties.organisation"
                    >{{ __('ui.properties.organisation') }}</option>

                    <option
                        value="association"
                        data-i18n="properties.association"
                    >{{ __('ui.properties.association') }}</option>
                </select>
            </div>

            {{-- Person Fields --}}
            <div
                id="owner-person-fields"
                class="space-y-4"
            >
                <div
                    class="
                        grid gap-4
                        sm:grid-cols-2
                    "
                >
                    <div>
                        <label
                            for="owner-given-names"
                            class="pm-field-label"
                        >
                            <span data-i18n="properties.given_names">{{ __('ui.properties.given_names') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="owner-given-names"
                            type="text"
                            maxlength="255"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="owner-surname"
                            class="pm-field-label"
                        >
                            <span data-i18n="properties.surname">{{ __('ui.properties.surname') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="owner-surname"
                            type="text"
                            maxlength="255"
                            class="pm-input"
                        >
                    </div>
                </div>

                <div
                    class="
                        grid gap-4
                        sm:grid-cols-2
                    "
                >
                    <x-phone-field
                        id="owner-phone"
                        label="properties.phone"
                        :required="true"
                    />

                    <div>
                        <label
                            for="owner-email"
                            class="pm-field-label"
                        >
                            <span data-i18n="properties.email">{{ __('ui.properties.email') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="owner-email"
                            type="email"
                            maxlength="255"
                            class="pm-input"
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
                        class="pm-field-label"
                    >
                        <span data-i18n="properties.legal_name">{{ __('ui.properties.legal_name') }}</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-legal-name"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-contact-name"
                        class="pm-field-label"
                    >
                        <span data-i18n="properties.contact_person">{{ __('ui.properties.contact_person') }}</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-contact-name"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div
                    class="
                        grid gap-4
                        sm:grid-cols-2
                    "
                >
                    <x-phone-field
                        id="owner-contact-phone"
                        label="properties.contact_phone"
                        :required="true"
                    />

                    <div>
                        <label
                            for="owner-contact-email"
                            class="pm-field-label"
                        >
                            <span data-i18n="properties.contact_email">{{ __('ui.properties.contact_email') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="owner-contact-email"
                            type="email"
                            maxlength="255"
                            class="pm-input"
                        >
                    </div>
                </div>
            </div>

            {{-- Common Address --}}
            <div class="mt-4">
                <label
                    for="owner-address"
                    class="pm-field-label"
                >
                    <span data-i18n="properties.address">{{ __('ui.properties.address') }}</span>
                </label>

                <input
                    id="owner-address"
                    type="text"
                    class="pm-input"
                >
            </div>
        </div>

        {{-- Footer --}}
        <x-drawer-footer>
            <button
                id="owner-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
            </button>

            <button
                id="owner-submit-button"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="properties.create_owner">{{ __('ui.properties.create_owner') }}</span>
            </button>
        </x-drawer-footer>
    </form>

</x-drawer>


{{-- ================================================================
     ADD / EDIT UNIT DRAWER
     ================================================================ --}}

<x-drawer
    id="existing-unit-modal"
    backdrop-id="existing-unit-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="existing-unit-modal-title"
        description-id="existing-unit-modal-description"
        close-id="existing-unit-modal-close"
        close-label="Close"
        close-label-key="properties.close"
    >
        <x-slot:title>
            <span data-i18n="properties.add_unit">{{ __('ui.properties.add_unit') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="properties.add_unit_description">{{ __('ui.properties.add_unit_description') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="existing-unit-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >

            {{-- Error --}}
            <div
                id="existing-unit-form-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            {{-- Parent Property --}}
            <div
                class="
                    mb-5 rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-selected)]
                    px-4 py-3
                "
            >
                <div
                    class="
                        text-xs font-medium
                        text-[var(--pm-accent)]
                    "
                >
                    <span data-i18n="properties.property">{{ __('ui.properties.property') }}</span>
                </div>

                <div
                    id="existing-unit-building-name"
                    class="
                        mt-1 text-sm font-semibold
                        text-[var(--pm-text)]
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
                    class="pm-field-label"
                >
                    <span data-i18n="properties.unit_name_number">{{ __('ui.properties.unit_name_number') }}</span>
                    <span class="text-[var(--pm-danger-text)]">*</span>
                </label>

                <input
                    id="existing-unit-name"
                    type="text"
                    required
                    maxlength="255"
                    data-i18n-placeholder="properties.existing_unit_name_placeholder"
                    placeholder="e.g. Apartment A2"
                    class="pm-input"
                >
            </div>

            {{-- Description --}}
            <div class="mt-4">
                <label
                    for="existing-unit-description"
                    class="pm-field-label"
                >
                    <span data-i18n="properties.description">{{ __('ui.properties.description') }}</span>
                </label>

                <textarea
                    id="existing-unit-description"
                    rows="3"
                    data-i18n-placeholder="properties.optional_unit_description"
                    placeholder="Optional unit description"
                    class="pm-input resize-y"
                ></textarea>
            </div>

            {{-- Commercial Classification --}}
            <label
                for="existing-unit-commercial"
                class="
                    mt-4 flex cursor-pointer
                    items-start gap-3 rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)]
                    px-4 py-3
                "
            >
                <input
                    id="existing-unit-commercial"
                    type="checkbox"
                    class="
                        mt-0.5 h-4 w-4 rounded
                        border-[var(--pm-border-strong)]
                        text-[var(--pm-accent)]
                        focus:ring-[var(--pm-accent)]
                    "
                >

                <span class="min-w-0">
                    <span
                        class="
                            block text-sm font-medium
                            text-[var(--pm-text)]
                        "
                        data-i18n="properties.commercial_unit"
                    >
                        {{ __('ui.properties.commercial_unit') }}
                    </span>

                    <span
                        class="
                            mt-0.5 block text-xs
                            text-[var(--pm-text-muted)]
                        "
                        data-i18n="properties.commercial_unit_help"
                    >
                        {{ __('ui.properties.commercial_unit_help') }}
                    </span>
                </span>
            </label>
        </div>

        {{-- Footer --}}
        <x-drawer-footer>
            <button
                id="existing-unit-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="existing-unit-submit-button"
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
     DELETE PROPERTY CONFIRMATION DRAWER
     ================================================================ --}}

<x-drawer
    id="delete-property-modal"
    backdrop-id="delete-property-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="delete-property-modal-close"
        close-label="Close"
        close-label-key="properties.close"
    >
        <x-slot:title>
            <span data-i18n="properties.delete_property">{{ __('ui.properties.delete_property') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="properties.delete_property_description">{{ __('ui.properties.delete_property_description') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="delete-property-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >

            {{-- Error / Blocked Reason --}}
            <div
                id="delete-property-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            {{-- Target Property --}}
            <div
                class="
                    rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    px-4 py-3
                "
            >
                <div
                    class="
                        text-xs font-medium
                        text-[var(--pm-danger-text)]
                    "
                >
                    <span data-i18n="properties.property">{{ __('ui.properties.property') }}</span>
                </div>

                <div
                    id="delete-property-name"
                    class="
                        mt-1 text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

            <p
                class="
                    mt-4 text-sm
                    text-[var(--pm-text-secondary)]
                "
            >
                <span data-i18n="properties.delete_property_warning">{{ __('ui.properties.delete_property_warning') }}</span>
            </p>

            {{-- Typed-Name Confirmation --}}
            <div class="mt-4">
                <label
                    for="delete-property-confirmation"
                    class="pm-field-label"
                >
                    <span data-i18n="properties.type_name_to_confirm">{{ __('ui.properties.type_name_to_confirm') }}</span>
                    <span class="text-[var(--pm-danger-text)]">*</span>
                </label>

                <input
                    id="delete-property-confirmation"
                    type="text"
                    autocomplete="off"
                    required
                    class="pm-input"
                >
            </div>
        </div>

        {{-- Footer --}}
        <x-drawer-footer>
            <button
                id="delete-property-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="delete-property-submit-button"
                type="submit"
                disabled
                class="pm-button-danger"
            >
                <span data-i18n="properties.delete_property">
                    {{ __('ui.properties.delete_property') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>

</x-drawer>


{{-- ================================================================
     DELETE UNIT CONFIRMATION DRAWER
     ================================================================ --}}

<x-drawer
    id="delete-unit-modal"
    backdrop-id="delete-unit-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="delete-unit-modal-close"
        close-label="Close"
        close-label-key="properties.close"
    >
        <x-slot:title>
            <span data-i18n="properties.delete_unit">{{ __('ui.properties.delete_unit') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="properties.delete_unit_description">{{ __('ui.properties.delete_unit_description') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="delete-unit-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >

            {{-- Error / Blocked Reason --}}
            <div
                id="delete-unit-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            {{-- Target Unit --}}
            <div
                class="
                    rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    px-4 py-3
                "
            >
                <div
                    class="
                        text-xs font-medium
                        text-[var(--pm-danger-text)]
                    "
                >
                    <span data-i18n="properties.unit">{{ __('ui.properties.unit') }}</span>
                </div>

                <div
                    id="delete-unit-name"
                    class="
                        mt-1 text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    —
                </div>

                <div
                    class="
                        mt-3 text-xs font-medium
                        text-[var(--pm-danger-text)]
                    "
                >
                    <span data-i18n="properties.property">{{ __('ui.properties.property') }}</span>
                </div>

                <div
                    id="delete-unit-building-name"
                    class="
                        mt-1 text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

            <p
                class="
                    mt-4 text-sm
                    text-[var(--pm-text-secondary)]
                "
            >
                <span data-i18n="properties.delete_unit_warning">{{ __('ui.properties.delete_unit_warning') }}</span>
            </p>
        </div>

        {{-- Footer --}}
        <x-drawer-footer>
            <button
                id="delete-unit-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="delete-unit-submit-button"
                type="submit"
                class="pm-button-danger"
            >
                <span data-i18n="properties.delete_unit">
                    {{ __('ui.properties.delete_unit') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>

</x-drawer>

@endsection
