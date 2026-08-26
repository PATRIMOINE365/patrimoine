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

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <select
                        id="tenant-deposit-lease"
                        class="pm-input"
                    >
                        <option value="">{{ __('ui.tenants.all_leases') }}</option>
                    </select>

                    <p
                        class="mt-1.5 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.any_account_help"
                    >
                        {{ __('ui.tenants.any_account_help') }}
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
                        <option value="">{{ __('ui.tenants.select_account') }}</option>
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
                        type="text"
                                        inputmode="numeric"
                                        data-money-input
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
                        <option value="cash">{{ __('ui.tenants.payment_method_cash') }}</option>

                        <option value="bank_transfer">{{ __('ui.tenants.payment_method_bank_transfer') }}</option>

                        <option value="momo">{{ __('ui.tenants.payment_method_momo') }}</option>
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

                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            {{ __('ui.tenants.optional') }}
                        </span>
                    </label>

                    <select
                        id="tenant-withdrawal-lease"
                        class="pm-input"
                    >
                        <option value="">{{ __('ui.tenants.all_leases') }}</option>
                    </select>

                    <p
                        class="mt-1.5 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.any_account_help"
                    >
                        {{ __('ui.tenants.any_account_help') }}
                    </p>
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
                        <option value="">{{ __('ui.tenants.select_account') }}</option>
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
                        type="text"
                                        inputmode="numeric"
                                        data-money-input
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
                        <option value="cash">{{ __('ui.tenants.payment_method_cash') }}</option>

                        <option value="bank_transfer">{{ __('ui.tenants.payment_method_bank_transfer') }}</option>

                        <option value="momo">{{ __('ui.tenants.payment_method_momo') }}</option>
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

{{-- ====================================================
     V1.0.8 Tenant Expense Drawer
==================================================== --}}

<x-drawer
    id="tenant-expense-drawer"
    backdrop-id="tenant-expense-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="tenant-expense-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.expense">
                {{ __('ui.tenants.withdrawal') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.expense_description_text">
                {{ __('ui.tenants.expense_description_text') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="tenant-expense-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-expense-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div id="tenant-expense-fields">

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
                    id="tenant-expense-tenant-context"
                    class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="tenant-expense-property-context"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div class="space-y-5">
                <div>
                    <label
                        for="tenant-expense-lease"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.lease">
                            {{ __('ui.tenants.lease') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-expense-lease"
                        required
                        class="pm-input"
                    >
                        <option value="">{{ __('ui.tenants.select_lease') }}</option>
                    </select>

                    {{-- V1.0.8: recording creates an unpaid EXP- invoice;
                         money leaves a fund account only through the
                         Invoice Pay flow. --}}
                    <p
                        class="mt-1.5 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.expense_invoice_help"
                    >
                        {{ __('ui.tenants.expense_invoice_help') }}
                    </p>
                </div>

                <div>
                    <label
                        for="tenant-expense-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.transaction_date">
                            {{ __('ui.tenants.transaction_date') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-expense-date"
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
                        for="tenant-expense-reference"
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
                        id="tenant-expense-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                
            </div>
            

    <div class="mt-5">
                    <span class="pm-field-label">
                        <span data-i18n="tenants.expense_lines">
                            {{ __('ui.tenants.expense_lines') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </span>

                    <div
                        id="tenant-expense-lines"
                        class="mt-2 space-y-2"
                    ></div>

                    <div class="mt-2 flex items-center justify-between">
                        <button
                            id="tenant-expense-add-line"
                            type="button"
                            class="pm-button-secondary"
                        >
                            <span data-i18n="tenants.add_line">
                                {{ __('ui.tenants.add_line') }}
                            </span>
                        </button>

                        <div class="text-sm">
                            <span
                                class="text-[var(--pm-text-muted)]"
                                data-i18n="tenants.expense_total"
                            >
                                {{ __('ui.tenants.expense_total') }}
                            </span>

                            <span
                                id="tenant-expense-total"
                                class="ml-2 font-semibold text-[var(--pm-text)]"
                            >
                                —
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- V1.0.8 read-only review, shown instead of the fields. --}}
            <div
                id="tenant-expense-review"
                class="hidden"
            ></div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-tenant-transaction="tenant-expense-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="tenant-expense-submit"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="tenants.review">
                    {{ __('ui.tenants.review') }}
                </span>
            </button>

            <button
                id="tenant-expense-back"
                type="button"
                class="pm-button-secondary pm-hide"
            >
                <span data-i18n="tenants.back">
                    {{ __('ui.tenants.back') }}
                </span>
            </button>

            <button
                id="tenant-expense-confirm"
                type="button"
                class="pm-button-primary pm-hide"
            >
                <span data-i18n="tenants.confirm">
                    {{ __('ui.tenants.confirm') }}
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
                        <option value="">{{ __('ui.tenants.select_account') }}</option>
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
                        type="text"
                                        inputmode="numeric"
                                        data-money-input
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




{{-- ================================================================
     V1.0.7 Tenant Accounts Drawer
================================================================ --}}

<x-drawer
    id="tenant-accounts-drawer"
    backdrop-id="tenant-accounts-drawer-backdrop"
    width="lg"
>
    <x-drawer-header
        close-id="tenant-accounts-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.accounts">
                {{ __('ui.tenants.accounts') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.accounts_description">
                {{ __('ui.tenants.accounts_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-accounts-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                id="tenant-accounts-success"
                class="
                    mb-5 hidden flex-wrap items-center
                    justify-between gap-3 rounded-xl
                    border border-[var(--pm-success-border)]
                    bg-[var(--pm-success-background)] px-4 py-3
                    text-sm text-[var(--pm-success-text)]
                "
            ></div>

            <div
                class="
                    mb-4 flex flex-wrap items-center
                    justify-between gap-3
                "
            >
                <div
                    id="tenant-accounts-context"
                    class="text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <button
                    type="button"
                    id="tenant-accounts-transfer"
                    data-requires-capability="manage_finance"
                    class="pm-button-primary"
                >
                    <span data-i18n="tenants.transfer">
                        {{ __('ui.tenants.transfer') }}
                    </span>
                </button>
            </div>

            <div id="tenant-accounts-table">
                <div
                    class="
                        rounded-xl border
                        border-dashed border-[var(--pm-border)]
                        px-5 py-8 text-center
                        text-sm text-[var(--pm-text-muted)]
                    "
                    data-i18n="tenants.loading_accounts"
                >
                    {{ __('ui.tenants.loading_accounts') }}
                </div>
            </div>

            <div
                id="tenant-accounts-position"
                class="mt-6"
            ></div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-tenant-transaction="tenant-accounts-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.close">
                    {{ __('ui.actions.close') }}
                </span>
            </button>
        </x-drawer-footer>
    </div>
</x-drawer>

{{-- ================================================================
     V1.0.7 Tenant Transfer Drawer
================================================================ --}}

<x-drawer
    id="tenant-transfer-drawer"
    backdrop-id="tenant-transfer-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="tenant-transfer-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.transfer">
                {{ __('ui.tenants.transfer') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.transfer_description">
                {{ __('ui.tenants.transfer_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="tenant-transfer-form"
        data-requires-capability="manage_finance"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
            <div
                id="tenant-transfer-error"
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
                    id="tenant-transfer-tenant-context"
                    class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="tenant-transfer-property-context"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div class="space-y-5">
                <div>
                    <label
                        for="tenant-transfer-source"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.source_account">
                            {{ __('ui.tenants.source_account') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-transfer-source"
                        required
                        disabled
                        class="pm-input"
                    >
                        <option value="">{{ __('ui.tenants.select_source_account') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="tenant-transfer-destination"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.destination_account">
                            {{ __('ui.tenants.destination_account') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="tenant-transfer-destination"
                        required
                        disabled
                        class="pm-input"
                    >
                        <option value="">{{ __('ui.tenants.select_destination_account') }}</option>
                    </select>
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
                            data-i18n="tenants.source_balance"
                        >
                            {{ __('ui.tenants.source_balance') }}
                        </div>

                        <div
                            id="tenant-transfer-source-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.destination_balance"
                        >
                            {{ __('ui.tenants.destination_balance') }}
                        </div>

                        <div
                            id="tenant-transfer-destination-balance"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.resulting_source_balance"
                        >
                            {{ __('ui.tenants.resulting_source_balance') }}
                        </div>

                        <div
                            id="tenant-transfer-resulting-source"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-xs text-[var(--pm-text-muted)]"
                            data-i18n="tenants.resulting_destination_balance"
                        >
                            {{ __('ui.tenants.resulting_destination_balance') }}
                        </div>

                        <div
                            id="tenant-transfer-resulting-destination"
                            class="mt-1 font-semibold text-[var(--pm-text)]"
                        >
                            —
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="tenant-transfer-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.amount">
                            {{ __('ui.tenants.amount') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="tenant-transfer-amount"
                        type="text"
                                        inputmode="numeric"
                                        data-money-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="tenant-transfer-reason"
                        class="pm-field-label"
                    >
                        <span data-i18n="tenants.reason">
                            {{ __('ui.tenants.reason') }}
                        </span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <textarea
                        id="tenant-transfer-reason"
                        rows="4"
                        maxlength="500"
                        required
                        class="pm-input"
                        placeholder="{{ __('ui.tenants.transfer_reason_placeholder') }}"
                        data-i18n-placeholder="tenants.transfer_reason_placeholder"
                    ></textarea>
                </div>

                <div>
                    <label
                        for="tenant-transfer-reference"
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
                        id="tenant-transfer-reference"
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
                data-close-tenant-transaction="tenant-transfer-drawer"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="tenant-transfer-submit"
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

{{-- ====================================================
     V1.0.8 Invoice Pay Drawer

     Pays all or part of an Invoice (rent or expense) from
     one of the Lease's tenant fund accounts, with the same
     read-only review step the Expense drawer uses.
==================================================== --}}

<x-drawer
    id="invoice-pay-drawer"
    backdrop-id="invoice-pay-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="invoice-pay-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.pay_invoice_title">
                {{ __('ui.tenants.pay_invoice_title') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.pay_invoice_description">
                {{ __('ui.tenants.pay_invoice_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="invoice-pay-form"
        class="flex min-h-0 flex-1 flex-col"
        novalidate
    >
        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div
                id="invoice-pay-error"
                class="hidden rounded-xl border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3 text-sm
                    text-[var(--pm-danger-text)]"
            ></div>

            <div id="invoice-pay-fields">
                <div
                    class="
                        mb-5 rounded-xl border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)] p-4
                    "
                >
                    <div
                        class="text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.invoice"
                    >
                        {{ __('ui.tenants.invoice') }}
                    </div>

                    <div
                        id="invoice-pay-invoice-context"
                        class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                    >
                        —
                    </div>

                    <div
                        id="invoice-pay-lease-context"
                        class="mt-1 text-sm text-[var(--pm-text-muted)]"
                    ></div>

                    <div class="mt-3 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="tenants.outstanding"
                    >
                        {{ __('ui.tenants.outstanding') }}
                    </div>

                    <div
                        id="invoice-pay-outstanding"
                        class="mt-1 font-semibold text-[var(--pm-text)]"
                    >
                        —
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <label
                            for="invoice-pay-account"
                            class="pm-field-label"
                        >
                            <span data-i18n="tenants.account">
                                {{ __('ui.tenants.account') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <select
                            id="invoice-pay-account"
                            required
                            class="pm-input"
                        >
                            <option value="">{{ __('ui.tenants.select_account') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="invoice-pay-amount"
                            class="pm-field-label"
                        >
                            <span data-i18n="tenants.amount">
                                {{ __('ui.tenants.amount') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="invoice-pay-amount"
                            type="text"
                            inputmode="numeric"
                            data-money-input
                            required
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="invoice-pay-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="tenants.transaction_date">
                                {{ __('ui.tenants.transaction_date') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="invoice-pay-date"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            autocomplete="off"
                            data-pm-date-input
                            required
                            class="pm-input"
                        >
                    </div>
                </div>
            </div>

            {{-- Read-only review, shown instead of the fields. --}}
            <div
                id="invoice-pay-review"
                class="hidden"
            ></div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                id="invoice-pay-cancel"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="invoice-pay-submit"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="tenants.review">
                    {{ __('ui.tenants.review') }}
                </span>
            </button>

            <button
                id="invoice-pay-back"
                type="button"
                class="pm-button-secondary pm-hide"
            >
                <span data-i18n="tenants.back">
                    {{ __('ui.tenants.back') }}
                </span>
            </button>

            <button
                id="invoice-pay-confirm"
                type="button"
                class="pm-button-primary pm-hide"
            >
                <span data-i18n="tenants.confirm">
                    {{ __('ui.tenants.confirm') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ====================================================
     V1.0.8 Cancel Invoice Payment Drawer

     Reverts the most recent active account payment of an
     Invoice. The reason is mandatory and becomes part of
     the immutable reversal audit trail.
==================================================== --}}

<x-drawer
    id="invoice-cancel-payment-drawer"
    backdrop-id="invoice-cancel-payment-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="invoice-cancel-payment-drawer-close"
        close-label="{{ __('ui.tenants.close') }}"
        close-label-key="tenants.close"
    >
        <x-slot:title>
            <span data-i18n="tenants.cancel_payment_title">
                {{ __('ui.tenants.cancel_payment_title') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="tenants.cancel_payment_description">
                {{ __('ui.tenants.cancel_payment_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="invoice-cancel-payment-form"
        class="flex min-h-0 flex-1 flex-col"
        novalidate
    >
        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div
                id="invoice-cancel-payment-error"
                class="hidden rounded-xl border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3 text-sm
                    text-[var(--pm-danger-text)]"
            ></div>

            <div
                class="
                    rounded-xl border border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    id="invoice-cancel-payment-context"
                    class="text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="invoice-cancel-payment-detail"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div>
                <label
                    for="invoice-cancel-payment-reason"
                    class="pm-field-label"
                >
                    <span data-i18n="tenants.cancellation_reason">
                        {{ __('ui.tenants.cancellation_reason') }}
                    </span>
                    <span class="text-[var(--pm-danger-text)]">*</span>
                </label>

                <textarea
                    id="invoice-cancel-payment-reason"
                    rows="3"
                    maxlength="500"
                    required
                    class="pm-input"
                ></textarea>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                id="invoice-cancel-payment-close"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="invoice-cancel-payment-submit"
                type="submit"
                class="pm-button-danger"
            >
                <span data-i18n="tenants.cancel_payment">
                    {{ __('ui.tenants.cancel_payment') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

@endsection
