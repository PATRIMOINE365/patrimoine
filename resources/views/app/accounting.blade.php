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

    <div
        class="
            mb-6 flex flex-wrap items-end gap-4
            rounded-2xl border border-[var(--pm-border)]
            bg-[var(--pm-surface)] p-5
        "
    >
        <div>
            <label
                class="pm-field-label mb-2 block text-sm font-medium"
                for="accounting-from"
            >
                <span data-i18n="accounting.from">{{ __('ui.accounting.from') }}</span>
            </label>

            <input
                id="accounting-from"
                type="date"
                class="pm-input"
            >
        </div>

        <div>
            <label
                class="pm-field-label mb-2 block text-sm font-medium"
                for="accounting-to"
            >
                <span data-i18n="accounting.to">{{ __('ui.accounting.to') }}</span>
            </label>

            <input
                id="accounting-to"
                type="date"
                class="pm-input"
            >
        </div>

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

    {{-- ============================================================
         Totals
    ============================================================ --}}

    <div class="mb-6 grid gap-4 md:grid-cols-3">

        <div
            class="
                rounded-2xl border border-[var(--pm-border)]
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
                rounded-2xl border border-[var(--pm-border)]
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
                rounded-2xl border border-[var(--pm-border)]
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
            overflow-hidden rounded-2xl
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
            <table class="pm-table w-full text-sm">
                <thead>
                    <tr>
                        <th data-i18n="accounting.date">{{ __('ui.accounting.date') }}</th>
                        <th data-i18n="accounting.type">{{ __('ui.accounting.type') }}</th>
                        <th data-i18n="accounting.owner">{{ __('ui.accounting.owner') }}</th>
                        <th data-i18n="accounting.property">{{ __('ui.accounting.property') }}</th>
                        <th data-i18n="accounting.reference">{{ __('ui.accounting.reference') }}</th>
                        <th class="text-right" data-i18n="accounting.amount">{{ __('ui.accounting.amount') }}</th>
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
