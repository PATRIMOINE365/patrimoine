@extends('layouts.app')

@section('title', __('ui.reports.title'))
@section('title-i18n', 'reports.title')

@section('content')

<div class="pm-reports-page mx-auto max-w-[1600px] text-[var(--pm-text)]">

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
                <span data-i18n="reports.finance">{{ __('ui.reports.finance') }}</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="reports.heading">{{ __('ui.reports.heading') }}</span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="reports.page_description">{{ __('ui.reports.page_description') }}</span>
            </p>
        </div>
    </div>

    {{-- Page Error --}}

    <div
        id="reports-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    <div
        class="
            grid gap-6
            xl:grid-cols-[320px_minmax(0,1fr)]
        "
    >

        {{-- ========================================================
             Report Controls
        ======================================================== --}}

        <aside
            class="
                pm-reports-controls
                pm-card
                self-start shadow-sm
                xl:sticky xl:top-[5.75rem]
            "
        >
            <div
                class="
                    border-b border-[var(--pm-border-subtle)]
                    px-5 py-4
                "
            >
                <h2
                    class="
                        text-base font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="reports.report_type">{{ __('ui.reports.report_type') }}</span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="reports.report_type_description">{{ __('ui.reports.report_type_description') }}</span>
                </p>
            </div>

            {{--
                Below the xl breakpoint the nine full-height type cards are
                replaced by one compact select so the criteria column stays
                short before the results. Both controls stay in sync via
                updateReportTypeUi().
            --}}
            <div class="p-3 xl:hidden">
                <label
                    for="report-type-select"
                    class="sr-only"
                >
                    <span data-i18n="reports.report_type">{{ __('ui.reports.report_type') }}</span>
                </label>

                <select
                    id="report-type-select"
                    class="pm-input"
                >
                    <option
                        value="managing-organisation"
                        data-i18n="reports.managing_organisation"
                    >{{ __('ui.reports.managing_organisation') }}</option>

                    <option
                        value="owner"
                        data-i18n="reports.owner_report"
                    >{{ __('ui.reports.owner_report') }}</option>

                    <option
                        value="building"
                        data-i18n="reports.building_report"
                    >{{ __('ui.reports.building_report') }}</option>

                    <option
                        value="unit"
                        data-i18n="reports.unit_report"
                    >{{ __('ui.reports.unit_report') }}</option>

                    <option
                        value="tenant"
                        data-i18n="reports.tenant_statement"
                    >{{ __('ui.reports.tenant_statement') }}</option>

                    <option
                        value="payments"
                        data-i18n="reports.payments"
                    >{{ __('ui.reports.payments') }}</option>

                    <option
                        value="occupancy"
                        data-i18n="reports.occupancy_report"
                    >{{ __('ui.reports.occupancy_report') }}</option>

                    <option
                        value="arrears"
                        data-i18n="reports.arrears_report"
                    >{{ __('ui.reports.arrears_report') }}</option>

                    <option
                        value="funds"
                        data-i18n="reports.funds_report"
                    >{{ __('ui.reports.funds_report') }}</option>
                </select>
            </div>

            {{--
                Report type cards: a single column in the xl sidebar,
                hidden below xl where the select above replaces them.
            --}}
            <div
                class="
                    hidden gap-1 p-3
                    xl:grid
                    xl:grid-cols-1
                "
            >

                <button
                    type="button"
                    data-report-type="managing-organisation"
                    class="
                        report-type-button
                        is-active
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.managing_organisation">{{ __('ui.reports.managing_organisation') }}</span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.managing_organisation_summary">{{ __('ui.reports.managing_organisation_summary') }}</span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="owner"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.owner_report">{{ __('ui.reports.owner_report') }}</span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.owner_report_summary">{{ __('ui.reports.owner_report_summary') }}</span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="building"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.building_report">{{ __('ui.reports.building_report') }}</span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.building_report_summary">{{ __('ui.reports.building_report_summary') }}</span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="unit"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.unit_report">{{ __('ui.reports.unit_report') }}</span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.unit_report_summary">{{ __('ui.reports.unit_report_summary') }}</span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="tenant"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.tenant_statement">{{ __('ui.reports.tenant_statement') }}</span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.tenant_statement_summary">{{ __('ui.reports.tenant_statement_summary') }}</span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="payments"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.payments">
                                {{ __('ui.reports.payments') }}
                            </span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.payments_report_summary">
                                {{ __('ui.reports.payments_report_summary') }}
                            </span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="occupancy"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.occupancy_report">
                                {{ __('ui.reports.occupancy_report') }}
                            </span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.occupancy_report_summary">
                                {{ __('ui.reports.occupancy_report_summary') }}
                            </span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="arrears"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.arrears_report">
                                {{ __('ui.reports.arrears_report') }}
                            </span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.arrears_report_summary">
                                {{ __('ui.reports.arrears_report_summary') }}
                            </span>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="funds"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            <span data-i18n="reports.funds_report">
                                {{ __('ui.reports.funds_report') }}
                            </span>
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.funds_report_summary">
                                {{ __('ui.reports.funds_report_summary') }}
                            </span>
                        </div>
                    </div>
                </button>

            </div>

            {{-- ====================================================
                 Report Subject
            ==================================================== --}}

            <div
                id="report-subject-section"
                class="
                    hidden border-t
                    border-[var(--pm-border-subtle)]
                    px-5 py-4
                "
            >
                <label
                    id="report-subject-label"
                    for="report-subject-search"
                    class="pm-field-label"
                >
                    <span data-i18n="reports.search">{{ __('ui.reports.search') }}</span>
                </label>

                <div class="relative">

                    <input
                        id="report-subject-search"
                        type="search"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-autocomplete="list"
                        aria-controls="report-subject-results"
                        placeholder="{{ __('ui.reports.search_placeholder') }}" data-i18n-placeholder="reports.search_placeholder"
                        class="pm-input"
                    >

                    <div
                        id="report-subject-results"
                        role="listbox"
                        class="
                            absolute z-30 mt-1 hidden
                            max-h-72 w-full
                            overflow-y-auto
                            rounded-xl
                            border border-[var(--pm-border)]
                            bg-[var(--pm-surface-elevated)] shadow-lg
                        "
                    ></div>

                </div>

                <input
                    id="report-subject-id"
                    type="hidden"
                >

                <div
                    id="report-selected-subject"
                    class="
                        mt-3 hidden
                        rounded-xl
                        border border-[var(--pm-border-strong)]
                        bg-[var(--pm-selected)]
                        p-3
                    "
                >
                    <div
                        class="
                            flex items-start
                            justify-between gap-3
                        "
                    >
                        <div class="min-w-0">
                            <div
                                id="report-selected-subject-name"
                                class="
                                    truncate text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            ></div>

                            <div
                                id="report-selected-subject-meta"
                                class="
                                    mt-1 text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            ></div>
                        </div>

                        <button
                            id="report-clear-subject"
                            type="button"
                            class="
                                shrink-0 text-xs
                                font-medium
                                text-[var(--pm-accent)]
                                hover:text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="reports.change">{{ __('ui.reports.change') }}</span>
                        </button>
                    </div>
                </div>
            </div>


            <div
                id="payment-report-filters"
                class="
                    hidden border-t
                    border-[var(--pm-border-subtle)] p-4
                "
            >
                <div class="text-sm font-medium text-[var(--pm-text-secondary)]">
                    <span data-i18n="reports.payment_filters">
                        {{ __('ui.reports.payment_filters') }}
                    </span>
                </div>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="reports.payment_filters_description">
                        {{ __('ui.reports.payment_filters_description') }}
                    </span>
                </p>

                {{-- Two filter columns on small screens, one in the xl sidebar. --}}
                <div
                    class="
                        mt-4 grid gap-3
                        sm:grid-cols-2
                        xl:grid-cols-1
                    "
                >
                    <div>
                        <label
                            for="payment-report-tenant"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.tenant">
                                {{ __('ui.reports.tenant') }}
                            </span>
                        </label>

                        <select
                            id="payment-report-tenant"
                            class="pm-input"
                        >
                            <option value="" data-i18n="reports.all_tenants">{{ __('ui.reports.all_tenants') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-report-lease"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.lease">
                                {{ __('ui.reports.lease') }}
                            </span>
                        </label>

                        <select
                            id="payment-report-lease"
                            class="pm-input"
                        >
                            <option value="" data-i18n="reports.all_leases">{{ __('ui.reports.all_leases') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-report-building"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.building">
                                {{ __('ui.reports.building') }}
                            </span>
                        </label>

                        <select
                            id="payment-report-building"
                            class="pm-input"
                        >
                            <option value="" data-i18n="reports.all_buildings">{{ __('ui.reports.all_buildings') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-report-unit"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.unit">
                                {{ __('ui.reports.unit') }}
                            </span>
                        </label>

                        <select
                            id="payment-report-unit"
                            class="pm-input"
                        >
                            <option value="" data-i18n="reports.all_units">{{ __('ui.reports.all_units') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-report-method"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.payment_method_label">
                                {{ __('ui.reports.payment_method_label') }}
                            </span>
                        </label>

                        <select
                            id="payment-report-method"
                            class="pm-input"
                        >
                            <option value="" data-i18n="reports.all_payment_methods">{{ __('ui.reports.all_payment_methods') }}</option>

                            <option value="cash" data-i18n="reports.payment_method_cash">{{ __('ui.reports.payment_method_cash') }}</option>

                            <option value="bank_transfer" data-i18n="reports.payment_method_bank">{{ __('ui.reports.payment_method_bank') }}</option>

                            <option value="momo" data-i18n="reports.payment_method_mobile">{{ __('ui.reports.payment_method_mobile') }}</option>

                            <option value="cheque" data-i18n="reports.payment_method_cheque">{{ __('ui.reports.payment_method_cheque') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="payment-report-cash-receiver"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.cash_receiver">
                                {{ __('ui.reports.cash_receiver') }}
                            </span>
                        </label>

                        <input
                            id="payment-report-cash-receiver"
                            type="search"
                            autocomplete="off"
                            class="pm-input"
                            placeholder="{{ __('ui.reports.cash_receiver_placeholder') }}"
                            data-i18n-placeholder="reports.cash_receiver_placeholder"
                        >
                    </div>

                    <div>
                        <label
                            for="payment-report-reference"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.payment_reference">
                                {{ __('ui.reports.payment_reference') }}
                            </span>
                        </label>

                        <input
                            id="payment-report-reference"
                            type="search"
                            autocomplete="off"
                            class="pm-input"
                            placeholder="{{ __('ui.reports.payment_reference_placeholder') }}"
                            data-i18n-placeholder="reports.payment_reference_placeholder"
                        >
                    </div>
                </div>
            </div>

{{-- ====================================================
                 Report Period
            ==================================================== --}}

            <div
                class="
                    border-t border-[var(--pm-border-subtle)]
                    px-5 py-4
                "
            >
                <div id="report-period-fields">

                <div class="text-sm font-medium text-[var(--pm-text-secondary)]">
                    <span data-i18n="reports.reporting_period">{{ __('ui.reports.reporting_period') }}</span>
                </div>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="reports.period_description">{{ __('ui.reports.period_description') }}</span>
                </p>

                {{-- From/To pair up on small screens, stack in the xl sidebar. --}}
                <div
                    class="
                        mt-4 grid gap-3
                        sm:grid-cols-2
                        xl:grid-cols-1
                    "
                >

                    <div>
                        <label
                            for="report-from"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.from">{{ __('ui.reports.from') }}</span>
                        </label>

                        <div class="relative">
                            <input
                                id="report-from"
                                type="text"
                                inputmode="numeric"
                                placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                data-i18n-placeholder="reports.date_placeholder"
                                maxlength="10"
                                autocomplete="off"
                                data-report-date-input
                                data-pm-date-input
                                class="pm-input pr-11"
                            >

                            <button
                                type="button"
                                data-report-date-picker="report-from"
                                aria-label="{{ __('ui.reports.select_date') }}"
                                data-i18n-aria-label="reports.select_date"
                                class="
                                    absolute inset-y-0 right-0
                                    flex w-10 items-center
                                    justify-center
                                    text-[var(--pm-text-subtle)]
                                    transition
                                    hover:text-[var(--pm-text-secondary)]
                                "
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
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
                        </div>
                    </div>

                    <div>
                        <label
                            for="report-to"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.to">{{ __('ui.reports.to') }}</span>
                        </label>

                        <div class="relative">
                            <input
                                id="report-to"
                                type="text"
                                inputmode="numeric"
                                placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                data-i18n-placeholder="reports.date_placeholder"
                                maxlength="10"
                                autocomplete="off"
                                data-report-date-input
                                data-pm-date-input
                                class="pm-input pr-11"
                            >

                            <button
                                type="button"
                                data-report-date-picker="report-to"
                                aria-label="{{ __('ui.reports.select_date') }}"
                                data-i18n-aria-label="reports.select_date"
                                class="
                                    absolute inset-y-0 right-0
                                    flex w-10 items-center
                                    justify-center
                                    text-[var(--pm-text-subtle)]
                                    transition
                                    hover:text-[var(--pm-text-secondary)]
                                "
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
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
                        </div>
                    </div>

                </div>

                </div>

                {{-- ================================================
                     As-of Reference Date

                     Portfolio snapshot reports (Occupancy, Arrears
                     Aging) use one reference date instead of a
                     From/To period. Hidden for all other types.
                ================================================ --}}

                <div
                    id="report-asof-field"
                    class="hidden"
                >
                    <div class="text-sm font-medium text-[var(--pm-text-secondary)]">
                        <span data-i18n="reports.as_of_heading">{{ __('ui.reports.as_of_heading') }}</span>
                    </div>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="reports.as_of_description">{{ __('ui.reports.as_of_description') }}</span>
                    </p>

                    <div class="mt-4">
                        <label
                            for="report-as-of"
                            class="pm-field-label"
                        >
                            <span data-i18n="reports.as_of">{{ __('ui.reports.as_of') }}</span>
                        </label>

                        <div class="relative">
                            <input
                                id="report-as-of"
                                type="text"
                                inputmode="numeric"
                                placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                data-i18n-placeholder="reports.date_placeholder"
                                maxlength="10"
                                autocomplete="off"
                                data-report-date-input
                                data-pm-date-input
                                class="pm-input pr-11"
                            >

                            <button
                                type="button"
                                data-report-date-picker="report-as-of"
                                aria-label="{{ __('ui.reports.select_date') }}"
                                data-i18n-aria-label="reports.select_date"
                                class="
                                    absolute inset-y-0 right-0
                                    flex w-10 items-center
                                    justify-center
                                    text-[var(--pm-text-subtle)]
                                    transition
                                    hover:text-[var(--pm-text-secondary)]
                                "
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
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
                        </div>
                    </div>
                </div>

                <button
                    id="run-report-button"
                    type="button"
                    class="
                        pm-button-primary
                        mt-4 w-full
                    "
                >
                    <span data-i18n="reports.run_report">{{ __('ui.reports.run_report') }}</span>
                </button>

                <button
                    id="report-reset-filters"
                    type="button"
                    class="
                        pm-button-secondary
                        mt-2 w-full
                    "
                >
                    <span data-i18n="reports.reset_filters">{{ __('ui.reports.reset_filters') }}</span>
                </button>
            </div>
        </aside>

        {{-- ========================================================
             Report Output
        ======================================================== --}}

        <section
            class="
                pm-reports-output-shell
                pm-card
                min-w-0 overflow-hidden
                shadow-sm
            "
        >

            <div
                class="
                    flex flex-col gap-4
                    border-b border-[var(--pm-border-subtle)]
                    px-4 py-5 sm:px-6
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                "
            >
                <div>
                    <h2
                        id="report-output-title"
                        class="
                            text-xl font-semibold
                            tracking-tight
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="reports.managing_organisation_report">{{ __('ui.reports.managing_organisation_report') }}</span>
                    </h2>

                    <div
                        id="report-output-subtitle"
                        class="
                            mt-1 text-sm
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span data-i18n="reports.managing_organisation_description">{{ __('ui.reports.managing_organisation_description') }}</span>
                    </div>
                </div>

                <div
                    id="report-export-actions"
                    data-requires-capability="export_reports"
                    class="
                        hidden flex-wrap
                        items-center gap-2
                        max-sm:w-full
                    "
                >
                    <button
                        id="report-pdf-button"
                        type="button"
                        class="
                            pm-button-secondary
                            min-w-[5rem] max-sm:flex-1
                        "
                    >
                        <span data-i18n="reports.pdf">{{ __('ui.reports.pdf') }}</span>
                    </button>

                    <button
                        id="report-xlsx-button"
                        type="button"
                        class="
                            pm-button-secondary
                            hidden
                            min-w-[5rem] max-sm:flex-1
                        "
                    >
                        <span data-i18n="reports.xlsx">
                            {{ __('ui.reports.xlsx') }}
                        </span>
                    </button>

                    <button
                        id="report-csv-button"
                        type="button"
                        class="
                            pm-button-secondary
                            min-w-[5rem] max-sm:flex-1
                        "
                    >
                        <span data-i18n="reports.csv">{{ __('ui.reports.csv') }}</span>
                    </button>
                </div>
            </div>

            {{--
                Stale-results notice: shown by reports.js whenever a report
                criterion changes after a run, until the report is re-run.
            --}}
            <div
                id="report-stale-notice"
                class="
                    hidden border-b
                    border-[var(--pm-warning-border)]
                    bg-[var(--pm-warning-background)]
                    px-4 py-2.5 sm:px-6
                    text-sm text-[var(--pm-warning-text)]
                "
            >
                <span data-i18n="reports.stale_results">{{ __('ui.reports.stale_results') }}</span>
            </div>

            <div
                id="report-output"
                class="pm-reports-output p-4 sm:p-6"
            >
                <div
                    class="
                        flex min-h-[520px]
                        items-center justify-center
                    "
                >
                    <div class="max-w-md text-center">
                        <div
                            class="
                                text-sm text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="reports.initial_prompt">{{ __('ui.reports.initial_prompt') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </section>

    </div>

</div>

@endsection
