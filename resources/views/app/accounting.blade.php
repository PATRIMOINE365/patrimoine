@extends('layouts.app')

@section('title', __('ui.accounting.title'))
@section('title-i18n', 'accounting.title')

@section('content')

<div class="pm-accounting-page mx-auto max-w-[1600px] text-[var(--pm-text)]">

    {{-- ============================================================
         Page Header
    ============================================================ --}}

    <div class="mb-8">
        <p class="text-sm font-medium text-[var(--pm-accent)]">
            <span data-i18n="navigation.finance">{{ __('ui.navigation.finance') }}</span>
        </p>

        <h1
            class="
                mt-1 text-3xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
        >
            <span data-i18n="accounting.title">{{ __('ui.accounting.title') }}</span>
        </h1>

        <p class="mt-2 max-w-3xl text-sm text-[var(--pm-text-muted)]">
            <span data-i18n="accounting.subtitle">{{ __('ui.accounting.subtitle') }}</span>
        </p>
    </div>

    {{-- Page Error --}}

    <div
        id="accounting-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- ============================================================
         Period filter
    ============================================================ --}}

    {{--
        V1.0.36: two things were wrong here.

        The dates were <input type="date">, which is the BROWSER's
        control in the BROWSER's language and format — so a French
        organisation on an English browser was shown mm/dd/yyyy while
        every other date field in Patrimoine reads jj-mm-aaaa. They are
        now ordinary text fields carrying data-pm-date-input, the same
        control the financial journal and the activity log use, which
        follows the organisation's language and opens Patrimoine's own
        calendar.

        And the two fields carried a small word each, From and To,
        stacked above narrow boxes. One label over the pair says the
        same thing once: this is a period, and it runs from the left
        box to the right one.
    --}}
    <div
        class="
            mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-4
            rounded-xl border border-[var(--pm-border)]
            bg-[var(--pm-surface)] p-5
        "
    >
        <div>
            <span
                class="pm-field-label mb-2 block"
                id="accounting-period-label"
            >
                <span data-i18n="accounting.period">{{ __('ui.accounting.period') }}</span>
            </span>

            <div class="flex items-center gap-3">
                <input
                    id="accounting-from"
                    type="text"
                    inputmode="numeric"
                    maxlength="10"
                    autocomplete="off"
                    data-pm-date-input
                    class="pm-input w-40"
                    aria-label="{{ __('ui.accounting.from') }}"
                    data-i18n-aria-label="accounting.from"
                >

                <span
                    class="text-sm text-[var(--pm-text-muted)]"
                    aria-hidden="true"
                >&ndash;</span>

                <input
                    id="accounting-to"
                    type="text"
                    inputmode="numeric"
                    maxlength="10"
                    autocomplete="off"
                    data-pm-date-input
                    class="pm-input w-40"
                    aria-label="{{ __('ui.accounting.to') }}"
                    data-i18n-aria-label="accounting.to"
                >
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                id="accounting-apply"
                type="button"
                class="pm-button-primary"
                data-i18n="accounting.apply"
            >{{ __('ui.accounting.apply') }}</button>

            <button
                id="accounting-reset"
                type="button"
                class="pm-button-secondary"
                data-i18n="accounting.reset"
            >{{ __('ui.accounting.reset') }}</button>
        </div>
    </div>

    {{-- ============================================================
         Totals
    ============================================================ --}}

    <div class="mb-6 grid gap-4 md:grid-cols-3">

        <div
            class="
                rounded-xl border border-[var(--pm-border)]
                bg-[var(--pm-surface)] p-5
            "
        >
            <p class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="accounting.fee_income">{{ __('ui.accounting.fee_income') }}</span>
            </p>

            <p
                id="accounting-fee-income"
                class="mt-2 text-3xl font-semibold tracking-tight"
            >—</p>

            <p class="mt-2 text-xs text-[var(--pm-text-muted)]">
                <span data-i18n="accounting.fee_income_hint">{{ __('ui.accounting.fee_income_hint') }}</span>
            </p>
        </div>

        <div
            class="
                rounded-xl border border-[var(--pm-border)]
                bg-[var(--pm-surface)] p-5
            "
        >
            <p class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="accounting.vat_charged">{{ __('ui.accounting.vat_charged') }}</span>
            </p>

            <p
                id="accounting-vat-charged"
                class="mt-2 text-3xl font-semibold tracking-tight"
            >—</p>

            <p class="mt-2 text-xs text-[var(--pm-text-muted)]">
                <span data-i18n="accounting.vat_charged_hint">{{ __('ui.accounting.vat_charged_hint') }}</span>
            </p>
        </div>

        <div
            class="
                rounded-xl border border-[var(--pm-border)]
                bg-[var(--pm-surface)] p-5
            "
        >
            <p class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="accounting.charged_to_owners">{{ __('ui.accounting.charged_to_owners') }}</span>
            </p>

            <p
                id="accounting-charged-total"
                class="mt-2 text-3xl font-semibold tracking-tight"
            >—</p>

            <p class="mt-2 text-xs text-[var(--pm-text-muted)]">
                <span data-i18n="accounting.charged_to_owners_hint">{{ __('ui.accounting.charged_to_owners_hint') }}</span>
            </p>
        </div>
    </div>

    <p class="mb-6 text-xs text-[var(--pm-text-muted)]">
        <span data-i18n="accounting.vat_note">{{ __('ui.accounting.vat_note') }}</span>
    </p>

    {{-- ============================================================
         Charges
    ============================================================ --}}

    <div
        class="
            overflow-hidden rounded-xl
            border border-[var(--pm-border)]
            bg-[var(--pm-surface)]
        "
    >
        <div class="border-b border-[var(--pm-border)] px-5 py-4">
            <h2 class="text-base font-semibold">
                <span data-i18n="accounting.transactions">{{ __('ui.accounting.transactions') }}</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="
                            border-b border-[var(--pm-border-subtle)]
                            bg-[var(--pm-surface-subtle)]
                            text-left text-xs font-medium
                            uppercase tracking-wide
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <th class="px-4 py-2.5 font-medium">
                            <span data-i18n="accounting.date">{{ __('ui.accounting.date') }}</span>
                        </th>

                        <th class="px-4 py-2.5 font-medium">
                            <span data-i18n="accounting.type">{{ __('ui.accounting.type') }}</span>
                        </th>

                        <th class="px-4 py-2.5 font-medium">
                            <span data-i18n="accounting.owner">{{ __('ui.accounting.owner') }}</span>
                        </th>

                        <th class="px-4 py-2.5 font-medium">
                            <span data-i18n="accounting.property">{{ __('ui.accounting.property') }}</span>
                        </th>

                        <th class="px-4 py-2.5 font-medium">
                            <span data-i18n="accounting.reference">{{ __('ui.accounting.reference') }}</span>
                        </th>

                        <th class="px-4 py-2.5 text-right font-medium">
                            <span data-i18n="accounting.amount">{{ __('ui.accounting.amount') }}</span>
                        </th>
                    </tr>
                </thead>

                <tbody id="accounting-rows"></tbody>
            </table>
        </div>

        <div
            id="accounting-capped"
            class="
                hidden border-t border-[var(--pm-border)]
                px-5 py-3 text-xs text-[var(--pm-text-muted)]
            "
        >
            <span data-i18n="accounting.capped">{{ __('ui.accounting.capped') }}</span>
        </div>
    </div>
</div>

@endsection
