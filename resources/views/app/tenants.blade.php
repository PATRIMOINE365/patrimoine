@extends('layouts.app')

@section('title', __('ui.tenants.title'))
@section('title-i18n', 'tenants.title')

@section('content')

<div
    id="tenant-workspace"
    class="pm-tenants-page mx-auto max-w-[1600px]"
>
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
                <span data-i18n="tenants.finance">{{ __('ui.tenants.finance') }}</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="tenants.heading">{{ __('ui.tenants.heading') }}</span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="tenants.page_description">{{ __('ui.tenants.page_description') }}</span>
            </p>
        </div>
    </div>

    <div
        id="tenant-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    <div
        class="pm-tenants-workspace"
    >
        <section class="pm-tenant-directory">
            <div class="pm-tenant-directory-header">
                <h2
                    class="
                        text-base font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="tenants.directory">{{ __('ui.tenants.directory') }}</span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="tenants.search_description">
                        {{ __('ui.tenants.search_description') }}
                    </span>
                </p>

                <div class="mt-4">
                    <label
                        for="tenant-search"
                        class="sr-only"
                    >
                        <span data-i18n="tenants.search">{{ __('ui.tenants.search') }}</span>
                    </label>

                    <input
                        id="tenant-search"
                        type="search"
                        autocomplete="off"
                        placeholder="{{ __('ui.tenants.search_placeholder') }}"
                        data-i18n-placeholder="tenants.search_placeholder"
                        class="pm-input pm-tenant-search-input"
                    >
                </div>
            </div>

            <div
                id="tenant-list"
                class="pm-tenant-directory-list"
            >
                <div
                    class="
                        px-5 py-8 text-center
                        text-sm text-[var(--pm-text-subtle)]
                    "
                >
                    <span data-i18n="tenants.loading">{{ __('ui.tenants.loading') }}</span>
                </div>
            </div>

            <div
                id="tenant-pagination"
                class="pm-tenant-directory-pagination hidden"
            ></div>
        </section>

        <section
            id="tenant-detail"
            class="pm-tenant-detail-shell"
        >
            <div
                class="
                    flex min-h-[620px]
                    items-center justify-center
                    px-6 py-16
                "
            >
                <div class="max-w-md text-center">
                    <div
                        class="
                            mx-auto flex h-12 w-12
                            items-center justify-center
                            rounded-full bg-[var(--pm-surface-muted)]
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                            <path d="M17 11h4"/>
                        </svg>
                    </div>

                    <h2
                        class="
                            mt-4 text-base font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="tenants.select_tenant">{{ __('ui.tenants.select_tenant') }}</span>
                    </h2>

                    <p
                        class="
                            mt-2 text-sm leading-6
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span data-i18n="tenants.select_tenant_description">{{ __('ui.tenants.select_tenant_description') }}</span>
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>

{{-- ================================================================
     V1.0.5 Tenant Deposit Drawer
================================================================ --}}

<x-drawer
    id="tenant-deposit-drawer"
    backdrop-id="tenant-deposit-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="tenant-deposit-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.deposit">
                {{ __('ui.tenants.deposit') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.deposit_description">
                {{ __('ui.tenants.deposit_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="tenant-deposit-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-deposit-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                class="
                    mb-5 rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] px-4 py-4
                "
            >
                <div
                    class="
                        text-xs font-medium uppercase
                        tracking-wide text-[var(--pm-text-muted)]
                    "
                    data-i18n="tenants.transaction_context"
                >
                    {{ __('ui.tenants.transaction_context') }}
                </div>

                <div
                    id="tenant-deposit-tenant-context"
                    class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="tenant-deposit-property-context"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div class="space-y-5">
                <div>
                    <label
                        for="tenant-deposit-lease"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.lease">
                            {{ __('ui.tenants.lease') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-deposit-lease"
                        required
                        class="pm-input"
                    >
                        <option value="">
                            {{ __('ui.tenants.select_lease') }}
                        </option>
                    </select>

                    <p
                        class="mt-1.5 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.lease_first_help"
                    >
                        {{ __('ui.tenants.lease_first_help') }}
                    </p>
                </div>

                <div>
                    <label
                        for="tenant-deposit-account"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.destination">
                            {{ __('ui.tenants.destination') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-deposit-account"
                        required
                        disabled
                        class="pm-input"
                    >
                        <option value="">
                            {{ __('ui.tenants.select_lease_first') }}
                        </option>
                    </select>
                </div>

                <div
                    class="
                        grid gap-3 rounded-xl
                        border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)] p-4
                        sm:grid-cols-3
                    "
                >
                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.current_balance"
                        >
                            {{ __('ui.tenants.current_balance') }}
                        </div>

                        <div
                            id="tenant-deposit-current-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.transaction_amount"
                        >
                            {{ __('ui.tenants.transaction_amount') }}
                        </div>

                        <div
                            id="tenant-deposit-preview-amount"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.resulting_balance"
                        >
                            {{ __('ui.tenants.resulting_balance') }}
                        </div>

                        <div
                            id="tenant-deposit-resulting-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-deposit-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.amount">
                            {{ __('ui.tenants.amount') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-deposit-amount"
                        type="number"
                        min="1"
                        step="1"
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-deposit-method"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.payment_method_label">
                            {{ __('ui.tenants.payment_method_label') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-deposit-method"
                        required
                        class="pm-input"
                    >
                        <option value="cash">
                            {{ __('ui.tenants.payment_method_cash') }}
                        </option>

                        <option value="bank_transfer">
                            {{ __('ui.tenants.payment_method_bank_transfer') }}
                        </option>

                        <option value="momo">
                            {{ __('ui.tenants.payment_method_momo') }}
                        </option>
                    </select>
                </div>

                <div
                    id="tenant-deposit-cash-receiver-wrapper"
                >
                    <label
                        for="tenant-deposit-cash-receiver"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.cash_receiver">
                            {{ __('ui.tenants.cash_receiver') }}
                        </span>
                    </label>

                    <input
                        id="tenant-deposit-cash-receiver"
                        type="text"
                        readonly
                        class="pm-input cursor-not-allowed bg-[var(--pm-input-disabled)]"
                        placeholder="{{ __('ui.tenants.cash_receiver_automatic') }}"
                    >

                    <p
                        class="mt-1.5 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.cash_receiver_help"
                    >
                        {{ __('ui.tenants.cash_receiver_help') }}
                    </p>
                </div>

                <div>
                    <label
                        for="tenant-deposit-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.transaction_date">
                            {{ __('ui.tenants.transaction_date') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-deposit-date"
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
                        for="tenant-deposit-reference"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.reference">
                            {{ __('ui.tenants.reference') }}
                        </span>

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <input
                        id="tenant-deposit-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-deposit-notes"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.notes">
                            {{ __('ui.tenants.notes') }}
                        </span>

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <textarea
                        id="tenant-deposit-notes"
                        rows="3"
                        maxlength="2000"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-tenant-transaction="tenant-deposit-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="tenant-deposit-submit"
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
     V1.0.5 Tenant Withdrawal Drawer
================================================================ --}}

<x-drawer
    id="tenant-withdrawal-drawer"
    backdrop-id="tenant-withdrawal-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="tenant-withdrawal-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.withdrawal">
                {{ __('ui.tenants.withdrawal') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.withdrawal_description">
                {{ __('ui.tenants.withdrawal_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="tenant-withdrawal-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-withdrawal-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                class="
                    mb-5 rounded-xl border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] px-4 py-4
                "
            >
                <div
                    class="
                        text-xs font-medium uppercase
                        tracking-wide text-[var(--pm-text-muted)]
                    "
                    data-i18n="tenants.transaction_context"
                >
                    {{ __('ui.tenants.transaction_context') }}
                </div>

                <div
                    id="tenant-withdrawal-tenant-context"
                    class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="tenant-withdrawal-property-context"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div class="space-y-5">
                <div>
                    <label
                        for="tenant-withdrawal-lease"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.lease">
                            {{ __('ui.tenants.lease') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-withdrawal-lease"
                        required
                        class="pm-input"
                    >
                        <option value="">
                            {{ __('ui.tenants.select_lease') }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        for="tenant-withdrawal-account"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.account">
                            {{ __('ui.tenants.account') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-withdrawal-account"
                        required
                        disabled
                        class="pm-input"
                    >
                        <option value="">
                            {{ __('ui.tenants.select_lease_first') }}
                        </option>
                    </select>
                </div>

                <div
                    class="
                        grid gap-3 rounded-xl border
                        border-[var(--pm-border)] bg-[var(--pm-surface-subtle)]
                        p-4 sm:grid-cols-3
                    "
                >
                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.current_balance"
                        >
                            {{ __('ui.tenants.current_balance') }}
                        </div>

                        <div
                            id="tenant-withdrawal-current-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.transaction_amount"
                        >
                            {{ __('ui.tenants.transaction_amount') }}
                        </div>

                        <div
                            id="tenant-withdrawal-preview-amount"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.resulting_balance"
                        >
                            {{ __('ui.tenants.resulting_balance') }}
                        </div>

                        <div
                            id="tenant-withdrawal-resulting-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-withdrawal-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.amount">
                            {{ __('ui.tenants.amount') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-withdrawal-amount"
                        type="number"
                        min="1"
                        step="1"
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-withdrawal-method"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.payment_method_label">
                            {{ __('ui.tenants.payment_method_label') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-withdrawal-method"
                        required
                        class="pm-input"
                    >
                        <option value="cash">
                            {{ __('ui.tenants.payment_method_cash') }}
                        </option>

                        <option value="bank_transfer">
                            {{ __('ui.tenants.payment_method_bank_transfer') }}
                        </option>

                        <option value="momo">
                            {{ __('ui.tenants.payment_method_momo') }}
                        </option>
                    </select>
                </div>

                <div
                    id="tenant-withdrawal-cash-receiver-wrapper"
                >
                    <label
                        for="tenant-withdrawal-cash-receiver"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.cash_receiver">
                            {{ __('ui.tenants.cash_receiver') }}
                        </span>
                    </label>

                    <input
                        id="tenant-withdrawal-cash-receiver"
                        type="text"
                        readonly
                        class="pm-input cursor-not-allowed bg-[var(--pm-input-disabled)]"
                        placeholder="{{ __('ui.tenants.cash_receiver_automatic') }}"
                    >

                    <p
                        class="mt-1.5 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.cash_receiver_help"
                    >
                        {{ __('ui.tenants.cash_receiver_help') }}
                    </p>
                </div>

                <div>
                    <label
                        for="tenant-withdrawal-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.transaction_date">
                            {{ __('ui.tenants.transaction_date') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-withdrawal-date"
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
                        for="tenant-withdrawal-reference"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.reference">
                            {{ __('ui.tenants.reference') }}
                        </span>

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <input
                        id="tenant-withdrawal-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-withdrawal-notes"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.notes">
                            {{ __('ui.tenants.notes') }}
                        </span>

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <textarea
                        id="tenant-withdrawal-notes"
                        rows="3"
                        maxlength="2000"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-tenant-transaction="tenant-withdrawal-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="tenant-withdrawal-submit"
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
     V1.0.5 Tenant Adjustment Drawer
================================================================ --}}

<x-drawer
    id="tenant-adjustment-drawer"
    backdrop-id="tenant-adjustment-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="tenant-adjustment-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.adjustment">
                {{ __('ui.tenants.adjustment') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.adjustment_description">
                {{ __('ui.tenants.adjustment_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="tenant-adjustment-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-adjustment-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                class="
                    mb-5 rounded-xl border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] px-4 py-4
                "
            >
                <div
                    class="
                        text-xs font-medium uppercase
                        tracking-wide text-[var(--pm-text-muted)]
                    "
                    data-i18n="tenants.transaction_context"
                >
                    {{ __('ui.tenants.transaction_context') }}
                </div>

                <div
                    id="tenant-adjustment-tenant-context"
                    class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="tenant-adjustment-property-context"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div
                class="
                    mb-5 rounded-xl border border-[var(--pm-warning-border)]
                    bg-[var(--pm-warning-background)] px-4 py-3
                    text-sm leading-6 text-[var(--pm-warning-text)]
                "
            >
                <span data-i18n="tenants.adjustment_warning">
                    {{ __('ui.tenants.adjustment_warning') }}
                </span>
            </div>

            <div class="space-y-5">
                <div>
                    <label
                        for="tenant-adjustment-account"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.account">
                            {{ __('ui.tenants.account') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-adjustment-account"
                        required
                        class="pm-input"
                    >
                        <option value="">
                            {{ __('ui.tenants.select_account') }}
                        </option>
                    </select>
                </div>

                <div
                    class="
                        grid gap-3 rounded-xl border
                        border-[var(--pm-border)] bg-[var(--pm-surface-subtle)]
                        p-4 sm:grid-cols-3
                    "
                >
                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.current_balance"
                        >
                            {{ __('ui.tenants.current_balance') }}
                        </div>

                        <div
                            id="tenant-adjustment-current-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.correct_balance"
                        >
                            {{ __('ui.tenants.correct_balance') }}
                        </div>

                        <div
                            id="tenant-adjustment-preview-correct"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.calculated_adjustment"
                        >
                            {{ __('ui.tenants.calculated_adjustment') }}
                        </div>

                        <div
                            id="tenant-adjustment-difference"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-adjustment-corrected-balance"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.correct_balance">
                            {{ __('ui.tenants.correct_balance') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-adjustment-corrected-balance"
                        type="number"
                        step="1"
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-adjustment-reason"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.reason">
                            {{ __('ui.tenants.reason') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <textarea
                        id="tenant-adjustment-reason"
                        rows="4"
                        maxlength="1000"
                        required
                        class="pm-input"
                        placeholder="{{ __('ui.tenants.adjustment_reason_placeholder') }}"
                        data-i18n-placeholder="tenants.adjustment_reason_placeholder"
                    ></textarea>
                </div>

                <div>
                    <label
                        for="tenant-adjustment-reference"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.reference">
                            {{ __('ui.tenants.reference') }}
                        </span>

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <input
                        id="tenant-adjustment-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-tenant-transaction="tenant-adjustment-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="tenant-adjustment-submit"
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


<x-drawer
    id="tenant-security-application-drawer"
    backdrop-id="tenant-security-application-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="tenant-security-application-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.apply_security_deposit">
                {{ __('ui.tenants.apply_security_deposit') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.apply_security_deposit_description">
                {{ __('ui.tenants.apply_security_deposit_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="tenant-security-application-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <input
            id="tenant-security-application-lease-id"
            type="hidden"
        >

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-security-application-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                class="
                    mb-5 rounded-xl
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)]
                    px-4 py-4
                "
            >
                <div
                    class="
                        text-xs font-medium uppercase
                        tracking-wide text-[var(--pm-text-muted)]
                    "
                    data-i18n="tenants.transaction_context"
                >
                    {{ __('ui.tenants.transaction_context') }}
                </div>

                <div
                    id="tenant-security-application-tenant-context"
                    class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="tenant-security-application-property-context"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                >
                    —
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <div
                        class="pm-field-label"
                        data-i18n="tenants.security_deposit_available"
                    >
                        {{ __('ui.tenants.security_deposit_available') }}
                    </div>

                    <div
                        id="tenant-security-application-held-balance"
                        class="
                            rounded-xl
                            border border-[var(--pm-border)]
                            bg-[var(--pm-surface-subtle)]
                            px-4 py-3
                            text-lg font-semibold text-[var(--pm-text)]
                        "
                    >
                        —
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-security-application-invoice"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.receivable">
                            {{ __('ui.tenants.receivable') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-security-application-invoice"
                        required
                        disabled
                        class="pm-input"
                    >
                        <option value="">
                            {{ __('ui.tenants.select_receivable') }}
                        </option>
                    </select>
                </div>

                <div
                    class="
                        rounded-xl
                        border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)] p-4
                    "
                >
                    <div
                        class="text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.receivable_outstanding"
                    >
                        {{ __('ui.tenants.receivable_outstanding') }}
                    </div>

                    <div
                        id="tenant-security-application-invoice-balance"
                        class="mt-1 font-semibold text-[var(--pm-text)]"
                    >
                        —
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-security-application-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.transaction_amount">
                            {{ __('ui.tenants.transaction_amount') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-security-application-amount"
                        type="number"
                        min="1"
                        step="1"
                        required
                        class="pm-input"
                    >
                </div>

                <div
                    class="
                        grid gap-3 rounded-xl
                        border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)] p-4
                        sm:grid-cols-2
                    "
                >
                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.resulting_security_deposit"
                        >
                            {{ __('ui.tenants.resulting_security_deposit') }}
                        </div>

                        <div
                            id="tenant-security-application-resulting-deposit"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.resulting_receivable"
                        >
                            {{ __('ui.tenants.resulting_receivable') }}
                        </div>

                        <div
                            id="tenant-security-application-resulting-receivable"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-security-application-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.transaction_date">
                            {{ __('ui.tenants.transaction_date') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-security-application-date"
                        type="text"
                        required
                        data-pm-date-input
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-security-application-notes"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.notes">
                            {{ __('ui.tenants.notes') }}
                        </span>

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <textarea
                        id="tenant-security-application-notes"
                        rows="4"
                        maxlength="2000"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-tenant-transaction="tenant-security-application-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="tenant-security-application-submit"
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

@endsection
