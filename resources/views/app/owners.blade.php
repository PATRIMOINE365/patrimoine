@extends('layouts.app')

@section('title', __('ui.owners.title'))
@section('title-i18n', 'owners.title')

@section('content')

<div class="pm-owners-page mx-auto max-w-[1600px]">

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
                <span data-i18n="owners.finance">
    {{ __('ui.owners.finance') }}
</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="owners.heading">
    {{ __('ui.owners.heading') }}
</span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="owners.page_description">
    {{ __('ui.owners.page_description') }}
</span>
            </p>
        </div>
    </div>

    {{-- Page-level API Error --}}

    <div
        id="owners-error"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- Expense Bill success banner (post-creation document actions) --}}

    <div
        id="owner-expense-bill-success"
        class="
            mb-6 hidden rounded-xl
            border border-[var(--pm-success-border)]
            bg-[var(--pm-success-background)] px-4 py-3
            text-sm text-[var(--pm-success-text)]
        "
    >
        <div
            class="
                flex flex-col gap-3
                sm:flex-row sm:items-center
                sm:justify-between
            "
        >
            <span id="owner-expense-bill-success-message"></span>

            <div class="flex flex-wrap gap-2">
                <button
                    id="owner-expense-bill-download"
                    type="button"
                    class="pm-button-secondary"
                >
                    <span data-i18n="owners.download_bill">
    {{ __('ui.owners.download_bill') }}
</span>
                </button>

                <button
                    id="owner-expense-bill-email"
                    type="button"
                    class="pm-button-secondary"
                >
                    <span data-i18n="owners.email_to_owner">
    {{ __('ui.owners.email_to_owner') }}
</span>
                </button>
            </div>
        </div>

        <div
            id="owner-expense-bill-email-status"
            class="mt-2 hidden text-xs"
        ></div>
    </div>

    {{-- ============================================================
         Owner Workspace
    ============================================================ --}}

    <div
        class="
            pm-owners-workspace
            grid gap-6
            xl:grid-cols-[380px_minmax(0,1fr)]
        "
    >

        {{-- ========================================================
             Owner Search / Directory
        ======================================================== --}}

        <section
            class="
                pm-owner-directory
                overflow-hidden pm-card shadow-sm
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
                    <span data-i18n="owners.property_owners">
    {{ __('ui.owners.property_owners') }}
</span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="owners.search_description">
    {{ __('ui.owners.search_description') }}
</span>
                </p>

                <div class="mt-4">
                    <label
                        for="owners-search"
                        class="sr-only"
                    >
                        <span data-i18n="owners.search_property_owners">
    {{ __('ui.owners.search_property_owners') }}
</span>
                    </label>

                    <div class="relative">
                        <svg
                            class="
                                pointer-events-none
                                absolute left-3 top-1/2
                                h-4 w-4
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
                            id="owners-search"
                            type="search"
                            autocomplete="off"
                            placeholder="{{ __('ui.owners.search_placeholder') }}" data-i18n-placeholder="owners.search_placeholder"
                            class="pm-input pm-input-search"
                        >
                    </div>
                </div>
            </div>

            <div
                id="owners-list"
                class="
                    max-h-[calc(100vh-270px)]
                    min-h-[420px]
                    overflow-y-auto
                "
            >
                <div
                    class="
                        px-5 py-8 text-center
                        text-sm text-[var(--pm-text-subtle)]
                    "
                >
                    <span data-i18n="owners.loading">
    {{ __('ui.owners.loading') }}
</span>
                </div>
            </div>

            <div
                id="owners-list-pagination"
                class="
                    hidden border-t
                    border-[var(--pm-border-subtle)]
                    px-4 py-3
                "
            ></div>
        </section>

        {{-- ========================================================
             Owner Detail
        ======================================================== --}}

        <section
            id="owner-detail-panel"
            class="
                pm-owner-detail-shell
                min-w-0 overflow-hidden
                pm-card shadow-sm
            "
        >

            {{-- Empty state before an owner is selected --}}

            <div
                id="owner-detail-empty"
                class="
                    flex min-h-[620px]
                    items-center justify-center
                    px-6 py-16
                "
            >
                <div
                    class="
                        max-w-md text-center
                    "
                >
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
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        </svg>
                    </div>

                    <h2
                        class="
                            mt-4 text-base font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="owners.select_property_owner">
    {{ __('ui.owners.select_property_owner') }}
</span>
                    </h2>

                    <p
                        class="
                            mt-2 text-sm leading-6
                            text-[var(--pm-text-muted)]
                        "
                    >
                        <span data-i18n="owners.select_owner_description">
    {{ __('ui.owners.select_owner_description') }}
</span>
                    </p>
                </div>
            </div>

            {{-- Actual owner content --}}

            <div
                id="owner-detail-content"
                class="hidden"
            >

                {{-- ====================================================
                     Owner Header
                ==================================================== --}}

                <div
                    class="
                        border-b border-[var(--pm-border-subtle)]
                        px-6 py-5
                    "
                >
                    <div
                        class="
                            flex flex-col gap-5
                            lg:flex-row
                            lg:items-start
                            lg:justify-between
                        "
                    >
                        <div class="min-w-0">
                            <div
                                class="
                                    flex flex-wrap
                                    items-center gap-2
                                "
                            >
                                <h2
                                    id="owner-detail-name"
                                    class="
                                        truncate
                                        text-xl font-semibold
                                        tracking-tight
                                        text-[var(--pm-text)]
                                    "
                                >
                                    —
                                </h2>

                                <span
                                    id="owner-detail-status"
                                    class="
                                        inline-flex items-center
                                        rounded-full
                                        bg-[var(--pm-success-background)]
                                        px-2.5 py-1
                                        text-xs font-medium
                                        text-[var(--pm-success-text)]
                                    "
                                >
                                    <span data-i18n="owners.active">
    {{ __('ui.owners.active') }}
</span>
                                </span>
                            </div>

                            <div
                                id="owner-detail-contact"
                                class="
                                    mt-2 text-sm
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                —
                            </div>
                        </div>

                        <div
                            class="
                                flex flex-wrap gap-2
                            "
                        >
                            <button
                                id="owner-view-accounts-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-[var(--pm-border)]
                                    bg-[var(--pm-surface)] px-3.5 py-2.5
                                    text-sm font-medium
                                    text-[var(--pm-text-secondary)]
                                    transition
                                    hover:border-[var(--pm-border-strong)]
                                    hover:bg-[var(--pm-hover)]
                                "
                            >
                                <span data-i18n="owners.accounts">
    {{ __('ui.owners.accounts') }}
</span>
                            </button>

                            <button
                                id="owner-record-deposit-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-[var(--pm-border)]
                                    bg-[var(--pm-surface)] px-3.5 py-2.5
                                    text-sm font-medium
                                    text-[var(--pm-text-secondary)]
                                    transition
                                    hover:border-[var(--pm-border-strong)]
                                    hover:bg-[var(--pm-hover)]
                                "
                            >
                                <span data-i18n="owners.deposit">
    {{ __('ui.owners.deposit') }}
</span>
                            </button>

                            <button
                                id="owner-record-payout-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-[var(--pm-border)]
                                    bg-[var(--pm-surface)] px-3.5 py-2.5
                                    text-sm font-medium
                                    text-[var(--pm-text-secondary)]
                                    transition
                                    hover:border-[var(--pm-border-strong)]
                                    hover:bg-[var(--pm-hover)]
                                    disabled:cursor-not-allowed
                                    disabled:opacity-50
                                "
                            >
                                <span data-i18n="owners.payout">
    {{ __('ui.owners.payout') }}
</span>
                            </button>

                            <button
                                id="owner-record-expense-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-[var(--pm-border)]
                                    bg-[var(--pm-surface)] px-3.5 py-2.5
                                    text-sm font-medium
                                    text-[var(--pm-text-secondary)]
                                    transition
                                    hover:border-[var(--pm-border-strong)]
                                    hover:bg-[var(--pm-hover)]
                                "
                            >
                                <span data-i18n="owners.expense">
    {{ __('ui.owners.expense') }}
</span>
                            </button>

                            <button
                                id="owner-record-adjustment-button"
                                type="button"
                                class="
                                    inline-flex items-center
                                    rounded-lg border
                                    border-[var(--pm-border)]
                                    bg-[var(--pm-surface)] px-3.5 py-2.5
                                    text-sm font-medium
                                    text-[var(--pm-text-secondary)]
                                    transition
                                    hover:border-[var(--pm-border-strong)]
                                    hover:bg-[var(--pm-hover)]
                                "
                            >
                                <span data-i18n="owners.adjustment">
    {{ __('ui.owners.adjustment') }}
</span>
                            </button>

                        </div>
                    </div>
                </div>

                {{-- ====================================================
                     Account Summary
                ==================================================== --}}

                <div
                    class="
                        grid gap-4
                        border-b border-[var(--pm-border-subtle)]
                        bg-[var(--pm-surface-subtle)]
                        px-6 py-5
                        sm:grid-cols-2
                        xl:grid-cols-4
                    "
                >
                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.current_balance">
    {{ __('ui.owners.current_balance') }}
</span>
                        </div>

                        <div
                            id="owner-detail-balance"
                            class="
                                mt-2 text-2xl font-semibold
                                tracking-tight text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.total_credits">
    {{ __('ui.owners.total_credits') }}
</span>
                        </div>

                        <div
                            id="owner-detail-credits"
                            class="
                                mt-2 text-xl font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.total_debits">
    {{ __('ui.owners.total_debits') }}
</span>
                        </div>

                        <div
                            id="owner-detail-debits"
                            class="
                                mt-2 text-xl font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>

                    <div>
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                        </div>

                        <div
                            id="owner-detail-property-count"
                            class="
                                mt-2 text-xl font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>
                </div>

                {{-- ====================================================
                     <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                ==================================================== --}}

                <div
                    class="
                        border-b border-[var(--pm-border-subtle)]
                        px-6 py-6
                    "
                >
                    <div
                        class="
                            flex items-start
                            justify-between gap-4
                        "
                    >
                        <div>
                            <h3
                                class="
                                    text-base font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                            </h3>

                            <p
                                class="
                                    mt-1 text-xs
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                <span data-i18n="owners.properties_description">
    {{ __('ui.owners.properties_description') }}
</span>
                            </p>
                        </div>
                    </div>

                    <div
                        id="owner-properties-list"
                        class="
                            mt-4 grid gap-3
                            lg:grid-cols-2
                        "
                    ></div>
                </div>

                {{-- ====================================================
                     Ledger
                ==================================================== --}}

                <div
                    class="
                        border-b border-[var(--pm-border-subtle)]
                        px-6 py-6
                    "
                >
                    <div>
                        <h3
                            class="
                                text-base font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="owners.owner_ledger">
    {{ __('ui.owners.owner_ledger') }}
</span>
                        </h3>

                        <p
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.ledger_description">
    {{ __('ui.owners.ledger_description') }}
</span>
                        </p>
                    </div>

                    <div
                        id="owner-ledger-list"
                        class="mt-4"
                    ></div>

                    <div
                        id="owner-ledger-pagination"
                        class="
                            mt-4 hidden
                            border-t border-[var(--pm-border-subtle)]
                            pt-4
                        "
                    ></div>
                </div>

                {{-- ====================================================
                     <span data-i18n="owners.payout_history">
    {{ __('ui.owners.payout_history') }}
</span>
                ==================================================== --}}

                <div
                    class="
                        px-6 py-6
                    "
                >
                    <div>
                        <h3
                            class="
                                text-base font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="owners.payout_history">
    {{ __('ui.owners.payout_history') }}
</span>
                        </h3>

                        <p
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.payout_history_description">
    {{ __('ui.owners.payout_history_description') }}
</span>
                        </p>
                    </div>

                    <div
                        id="owner-payouts-list"
                        class="mt-4"
                    ></div>
                </div>

                {{-- ====================================================
                     V1.0.8 Account Transfers (Payout <-> Deposit/Expense)
                ==================================================== --}}

                <div
                    class="
                        border-t border-[var(--pm-border-subtle)]
                        px-6 py-6
                    "
                >
                    <div>
                        <h3
                            class="
                                text-base font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="owners.transfers">
    {{ __('ui.owners.transfers') }}
</span>
                        </h3>

                        <p
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.transfers_description">
    {{ __('ui.owners.transfers_description') }}
</span>
                        </p>
                    </div>

                    <div
                        id="owner-transfers-list"
                        class="mt-4"
                    ></div>
                </div>

                {{-- ====================================================
                     V1.0.8 Expenses (unpaid OEB bills paid explicitly)
                ==================================================== --}}

                <div
                    class="
                        border-t border-[var(--pm-border-subtle)]
                        px-6 py-6
                    "
                >
                    <div>
                        <h3
                            class="
                                text-base font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            <span data-i18n="owners.expenses">
    {{ __('ui.owners.expenses') }}
</span>
                        </h3>

                        <p
                            class="
                                mt-1 text-xs
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.expense_bills_description">
    {{ __('ui.owners.expense_bills_description') }}
</span>
                        </p>
                    </div>

                    <div
                        id="owner-expense-bills-list"
                        class="mt-4"
                    ></div>
                </div>

            </div>
        </section>
    </div>
</div>
{{-- ================================================================
     Owner Deposit Drawer
================================================================ --}}

{{-- ====================================================
     V1.0.8 Owner Account Transfer Drawer
==================================================== --}}

<x-drawer
    id="owner-transfer-modal"
    backdrop-id="owner-transfer-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="owner-transfer-modal-title"
        description-id="owner-transfer-modal-description"
        close-id="owner-transfer-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.transfer_title">
                {{ __('ui.owners.transfer_title') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.transfer_description">
                {{ __('ui.owners.transfer_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="owner-transfer-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div class="flex-1 overflow-y-auto px-6 py-6">
            <div
                id="owner-transfer-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div class="grid gap-4">
                <div>
                    <label
                        for="owner-transfer-direction"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.transfer_direction">
    {{ __('ui.owners.transfer_direction') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-transfer-direction"
                        required
                        class="pm-input"
                    >
                        <option
                            value="to_expense"
                            data-i18n="owners.transfer_to_expense"
                        >{{ __('ui.owners.transfer_to_expense') }}</option>

                        <option
                            value="to_payout"
                            data-i18n="owners.transfer_to_payout"
                        >{{ __('ui.owners.transfer_to_payout') }}</option>
                    </select>
                </div>

                <div>
                    <span class="pm-field-label">
                        <span data-i18n="owners.transfer_available">
    {{ __('ui.owners.transfer_available') }}
</span>
                    </span>

                    <div
                        id="owner-transfer-available"
                        class="
                            text-lg font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        —
                    </div>
                </div>

                <div>
                    <label
                        for="owner-transfer-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-transfer-amount"
                        type="text"
                        inputmode="numeric"
                        data-money-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-transfer-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.transaction_date">
    {{ __('ui.owners.transaction_date') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-transfer-date"
                        type="text"
                        data-pm-date-input
                        inputmode="numeric"
                        maxlength="10"
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-transfer-reason"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.transfer_reason">
    {{ __('ui.owners.transfer_reason') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <textarea
                        id="owner-transfer-reason"
                        rows="3"
                        required
                        maxlength="1000"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                id="owner-transfer-cancel"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="owner-transfer-submit"
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
    id="owner-deposit-modal"
    backdrop-id="owner-deposit-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-deposit-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.record_owner_deposit">
    {{ __('ui.owners.record_owner_deposit') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.deposit_description">
    {{ __('ui.owners.deposit_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-deposit-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-deposit-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            <div
                class="
                    grid gap-4
                    md:grid-cols-2
                "
            >
                <div>
                    <label
                        for="owner-deposit-amount"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <div class="relative">
                        <span
                            class="
                                pointer-events-none
                                absolute inset-y-0 left-0
                                flex items-center pl-3.5
                                text-sm text-[var(--pm-text-muted)]
                            "
                         data-currency-display>
                        </span>

                        <input
                            id="owner-deposit-amount"
                            type="text"
                                        inputmode="numeric"
                                        data-money-input
                            required
                            class="pm-input pm-input-currency"
                        >
                    </div>
                </div>

                <div>
                    <label
                        for="owner-deposit-date"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.deposit_date">
    {{ __('ui.owners.deposit_date') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-deposit-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-deposit-method"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.payment_method">
    {{ __('ui.owners.payment_method') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-deposit-method"
                        required
                        class="pm-input"
                    >
                        <option value="bank_transfer" data-i18n="owners.bank_transfer">{{ __('ui.owners.bank_transfer') }}</option>

                        <option value="momo" data-i18n="owners.momo">{{ __('ui.owners.momo') }}</option>

                        <option value="cash" data-i18n="owners.cash">{{ __('ui.owners.cash') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-purpose"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.deposit_purpose">
    {{ __('ui.owners.deposit_purpose') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-deposit-purpose"
                        required
                        class="pm-input"
                    >
                        <option value="general_funding" data-i18n="owners.general_funding">{{ __('ui.owners.general_funding') }}</option>

                        <option value="property_expense" data-i18n="owners.property_expense">{{ __('ui.owners.property_expense') }}</option>

                        <option value="repair_maintenance" data-i18n="owners.repair_maintenance_static">{{ __('ui.owners.repair_maintenance_static') }}</option>

                        <option value="other" data-i18n="owners.other">{{ __('ui.owners.other') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-building"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.building">
    {{ __('ui.owners.building') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <select
                        id="owner-deposit-building"
                        class="pm-input"
                    >
                        <option value="" data-i18n="owners.no_specific_building">{{ __('ui.owners.no_specific_building') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-unit"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.unit">
    {{ __('ui.owners.unit') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <select
                        id="owner-deposit-unit"
                        disabled
                        class="pm-input"
                    >
                        <option value="" data-i18n="owners.select_building_first">{{ __('ui.owners.select_building_first') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-deposit-reference"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-deposit-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div
                    id="owner-deposit-collector-wrapper"
                    class="hidden"
                >
                    <label
                        for="owner-deposit-collector"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.collector">
    {{ __('ui.owners.collector') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-deposit-collector"
                        type="text"
                        readonly
                        placeholder="{{ __('ui.owners.collector_placeholder') }}"
                        data-i18n-placeholder="owners.collector_placeholder"
                        class="pm-input cursor-not-allowed bg-[var(--pm-input-disabled)]"
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-deposit-notes"
                        class="
                            pm-field-label
                        "
                    >
                        <span data-i18n="owners.notes">
    {{ __('ui.owners.notes') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <textarea
                        id="owner-deposit-notes"
                        rows="3"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-deposit-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
    {{ __('ui.actions.cancel') }}
</span>
            </button>

            <button
                id="owner-deposit-submit"
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
     Owner Expense Drawer
================================================================ --}}



{{-- ================================================================
     Owner Payout Drawer
================================================================ --}}

<x-drawer
    id="owner-payout-modal"
    backdrop-id="owner-payout-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-payout-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.make_owner_payout">
    {{ __('ui.owners.make_owner_payout') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.payout_description">
    {{ __('ui.owners.payout_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-payout-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-payout-error"
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
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div class="text-xs font-medium uppercase text-[var(--pm-text-muted)]">
                    <span data-i18n="owners.available_owner_balance">
    {{ __('ui.owners.available_owner_balance') }}
</span>
                </div>

                <div
                    id="owner-payout-available-balance"
                    class="
                        mt-2 text-2xl font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="owner-payout-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-payout-amount"
                        type="text"
                                        inputmode="numeric"
                                        data-money-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-payout-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.payout_date">
    {{ __('ui.owners.payout_date') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-payout-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-payout-method"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.payment_method">
    {{ __('ui.owners.payment_method') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-payout-method"
                        required
                        class="pm-input"
                    >
                        <option value="bank_transfer" data-i18n="owners.bank_transfer">{{ __('ui.owners.bank_transfer') }}</option>

                        <option value="momo" data-i18n="owners.momo">{{ __('ui.owners.momo') }}</option>

                        <option value="cash" data-i18n="owners.cash">{{ __('ui.owners.cash') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-payout-reference"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-payout-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-payout-notes"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.notes">
    {{ __('ui.owners.notes') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <textarea
                        id="owner-payout-notes"
                        rows="3"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-payout-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
    {{ __('ui.actions.cancel') }}
</span>
            </button>

            <button
                id="owner-payout-submit"
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
     Owner Adjustment Drawer
================================================================ --}}

<x-drawer
    id="owner-adjustment-modal"
    backdrop-id="owner-adjustment-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-adjustment-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.owner_account_adjustment">
    {{ __('ui.owners.owner_account_adjustment') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.adjustment_description">
    {{ __('ui.owners.adjustment_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <form
            id="owner-adjustment-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-adjustment-error"
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
                    border border-[var(--pm-warning-border)]
                    bg-[var(--pm-warning-background)] px-4 py-3
                    text-sm leading-6 text-[var(--pm-warning-text)]
                "
            >
                <span data-i18n="owners.adjustment_warning">
    {{ __('ui.owners.adjustment_warning') }}
</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="owner-adjustment-direction"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.direction">
    {{ __('ui.owners.direction') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-adjustment-direction"
                        required
                        class="pm-input"
                    >
                        <option value="credit" data-i18n="owners.credit_increase_balance">{{ __('ui.owners.credit_increase_balance') }}</option>

                        <option value="debit" data-i18n="owners.debit_reduce_balance">{{ __('ui.owners.debit_reduce_balance') }}</option>
                    </select>
                </div>

                <div>
                    <label
                        for="owner-adjustment-amount"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-adjustment-amount"
                        type="text"
                                        inputmode="numeric"
                                        data-money-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-adjustment-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.adjustment_date">
    {{ __('ui.owners.adjustment_date') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-adjustment-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="owner-adjustment-reference"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.reference">
    {{ __('ui.owners.reference') }}
</span>
                        <span class="text-xs text-[var(--pm-text-subtle)]">
                            <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                        </span>
                    </label>

                    <input
                        id="owner-adjustment-reference"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div class="md:col-span-2">
                    <label
                        for="owner-adjustment-reason"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.reason">
    {{ __('ui.owners.reason') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <textarea
                        id="owner-adjustment-reason"
                        rows="4"
                        maxlength="1000"
                        required
                        placeholder="{{ __('ui.owners.adjustment_reason_placeholder') }}" data-i18n-placeholder="owners.adjustment_reason_placeholder"
                        class="pm-input"
                    ></textarea>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                data-close-owner-modal="owner-adjustment-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
    {{ __('ui.actions.cancel') }}
</span>
            </button>

            <button
                id="owner-adjustment-submit"
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
     Owner Accounts Drawer (read-only consolidated position)
================================================================ --}}

<x-drawer
    id="owner-accounts-modal"
    backdrop-id="owner-accounts-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        close-id="owner-accounts-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.owner_accounts_title">
    {{ __('ui.owners.owner_accounts_title') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.owner_accounts_description">
    {{ __('ui.owners.owner_accounts_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    <div
        class="
            min-h-0 flex-1
            overflow-y-auto
            px-6 py-6
        "
    >
        <div
            class="
                rounded-xl border
                border-[var(--pm-info-border)]
                bg-[var(--pm-info-background)] px-4 py-3
                text-sm leading-6 text-[var(--pm-info-text)]
            "
        >
            <span data-i18n="owners.consolidated_account_note">
    {{ __('ui.owners.consolidated_account_note') }}
</span>
        </div>

        <div class="mt-4 flex justify-end">
            <button
                id="owner-transfer-button"
                type="button"
                data-requires-capability="manage_finance"
                class="pm-button-secondary"
            >
                <span data-i18n="owners.transfer">
    {{ __('ui.owners.transfer') }}
</span>
            </button>
        </div>

        {{--
            V1.0.8 dual balance: the Payout account holds rent-derived
            money the owner can withdraw; the Deposit/Expense account
            holds earmarked deposits that fund expenses and may go
            negative. Reserve transfers move money between the two.
        --}}
        <div
            class="
                mt-5 grid gap-4
                sm:grid-cols-2
            "
        >
            <div
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    class="
                        text-xs font-medium
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="owners.payout_account_balance">
    {{ __('ui.owners.payout_account_balance') }}
</span>
                </div>

                <div
                    id="owner-accounts-payout-balance"
                    class="
                        mt-2 text-2xl font-semibold
                        tracking-tight text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

            <div
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    class="
                        text-xs font-medium
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="owners.deposit_account_balance">
    {{ __('ui.owners.deposit_account_balance') }}
</span>
                </div>

                <div
                    id="owner-accounts-deposit-balance"
                    class="
                        mt-2 text-2xl font-semibold
                        tracking-tight text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

        </div>

        <div
            class="
                mt-4 grid gap-4
                sm:grid-cols-2
            "
        >
            <div
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    class="
                        text-xs font-medium
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="owners.current_balance">
    {{ __('ui.owners.current_balance') }}
</span>
                </div>

                <div
                    id="owner-accounts-balance"
                    class="
                        mt-2 text-2xl font-semibold
                        tracking-tight text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

            <div
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    class="
                        text-xs font-medium
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="owners.properties">
    {{ __('ui.owners.properties') }}
</span>
                </div>

                <div
                    id="owner-accounts-property-count"
                    class="
                        mt-2 text-2xl font-semibold
                        tracking-tight text-[var(--pm-text)]
                    "
                >
                    —
                </div>
            </div>

            <div
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    class="
                        text-xs font-medium
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="owners.total_credits">
    {{ __('ui.owners.total_credits') }}
</span>
                </div>

                <div
                    id="owner-accounts-credits"
                    class="
                        mt-2 text-xl font-semibold
                        text-[var(--pm-success-text)]
                    "
                >
                    —
                </div>
            </div>

            <div
                class="
                    rounded-xl border
                    border-[var(--pm-border)]
                    bg-[var(--pm-surface-subtle)] p-4
                "
            >
                <div
                    class="
                        text-xs font-medium
                        uppercase tracking-wide
                        text-[var(--pm-text-muted)]
                    "
                >
                    <span data-i18n="owners.total_debits">
    {{ __('ui.owners.total_debits') }}
</span>
                </div>

                <div
                    id="owner-accounts-debits"
                    class="
                        mt-2 text-xl font-semibold
                        text-[var(--pm-danger-text)]
                    "
                >
                    —
                </div>
            </div>
        </div>

        <div class="mt-6">
            <h3
                class="
                    text-base font-semibold
                    text-[var(--pm-text)]
                "
            >
                <span data-i18n="owners.accounts_breakdown">
    {{ __('ui.owners.accounts_breakdown') }}
</span>
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="owners.accounts_breakdown_description">
    {{ __('ui.owners.accounts_breakdown_description') }}
</span>
            </p>

            <div
                class="
                    mt-3 overflow-x-auto
                    rounded-xl border
                    border-[var(--pm-border)]
                "
            >
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
                                <span data-i18n="owners.account">
    {{ __('ui.owners.account') }}
</span>
                            </th>

                            <th class="px-4 py-2.5 text-right font-medium">
                                <span data-i18n="owners.current_balance">
    {{ __('ui.owners.current_balance') }}
</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="owner-accounts-breakdown"></tbody>

                    <tfoot>
                        <tr
                            class="
                                border-t border-[var(--pm-border)]
                                bg-[var(--pm-surface-subtle)]
                            "
                        >
                            <td
                                class="
                                    px-4 py-2.5 text-left
                                    text-xs font-medium
                                    uppercase tracking-wide
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                <span data-i18n="owners.current_balance">
    {{ __('ui.owners.current_balance') }}
</span>
                            </td>

                            <td
                                id="owner-accounts-breakdown-total"
                                class="
                                    px-4 py-2.5 text-right
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                            >
                                —
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-6">
            <h3
                class="
                    text-base font-semibold
                    text-[var(--pm-text)]
                "
            >
                <span data-i18n="owners.recent_activity">
    {{ __('ui.owners.recent_activity') }}
</span>
            </h3>

            <p
                class="
                    mt-1 text-xs
                    text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="owners.recent_activity_description">
    {{ __('ui.owners.recent_activity_description') }}
</span>
            </p>

            <div
                class="
                    mt-3 overflow-x-auto
                    rounded-xl border
                    border-[var(--pm-border)]
                "
            >
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
                                <span data-i18n="owners.date">
    {{ __('ui.owners.date') }}
</span>
                            </th>

                            <th class="px-4 py-2.5 font-medium">
                                <span data-i18n="owners.type">
    {{ __('ui.owners.type') }}
</span>
                            </th>

                            <th class="px-4 py-2.5 text-right font-medium">
                                <span data-i18n="owners.amount">
    {{ __('ui.owners.amount') }}
</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="owner-accounts-ledger"></tbody>
                </table>
            </div>
        </div>
    </div>

    <x-drawer-footer>
        <button
            type="button"
            data-close-owner-modal="owner-accounts-modal"
            class="pm-button-secondary"
        >
            <span data-i18n="actions.close">
    {{ __('ui.actions.close') }}
</span>
        </button>
    </x-drawer-footer>
</x-drawer>

{{-- ================================================================
     Owner Expense Bill Drawer (multi-line, billed directly to owner)
================================================================ --}}

<x-drawer
    id="owner-expense-bill-modal"
    backdrop-id="owner-expense-bill-modal-backdrop"
    width="lg"
>
    <x-drawer-header
        close-id="owner-expense-bill-modal-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.expense_bill_title">
    {{ __('ui.owners.expense_bill_title') }}
</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.expense_bill_description">
    {{ __('ui.owners.expense_bill_description') }}
</span>
        </x-slot:description>
    </x-drawer-header>

    

    <form
            id="owner-expense-bill-form"
            class="flex min-h-0 flex-1 flex-col"
        >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
<div
                id="owner-expense-bill-error"
                class="
                    mb-5 hidden rounded-xl
                    border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3
                    text-sm text-[var(--pm-danger-text)]
                "
            ></div>

            {{--
                V1.0.8 review step: the fields wrapper hides while the
                read-only review renders in its place.
            --}}
            <div
                id="owner-expense-bill-review"
                class="hidden"
            ></div>

            <div id="owner-expense-bill-fields">

            <div class="mb-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="owner-expense-bill-building"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.building">
    {{ __('ui.owners.building') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-expense-bill-building"
                        required
                        class="pm-input"
                    >
                    </select>
                </div>

                <div>
                    <label
                        for="owner-expense-bill-split"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.billing_mode">
    {{ __('ui.owners.billing_mode') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <select
                        id="owner-expense-bill-split"
                        required
                        class="pm-input"
                    >
                        <option
                            value="single"
                            data-i18n="owners.billing_mode_single"
                        >{{ __('ui.owners.billing_mode_single') }}</option>

                        <option
                            value="split"
                            data-i18n="owners.billing_mode_split"
                        >{{ __('ui.owners.billing_mode_split') }}</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        for="owner-expense-bill-date"
                        class="pm-field-label"
                    >
                        <span data-i18n="owners.bill_date">
    {{ __('ui.owners.bill_date') }}
</span>
                        <span class="text-[var(--pm-danger-text)]">*</span>
                    </label>

                    <input
                        id="owner-expense-bill-date"
                        type="text"
                        inputmode="numeric"
                        placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                        maxlength="10"
                        autocomplete="off"
                        data-owner-date-input
                        data-pm-date-input
                        required
                        class="pm-input"
                    >
                </div>
            </div>

            <div class="mt-6">
                <div
                    class="
                        text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    <span data-i18n="owners.expense_lines">
    {{ __('ui.owners.expense_lines') }}
</span>
                </div>

                <div
                    id="owner-expense-bill-lines"
                    class="mt-3 space-y-3"
                ></div>

                <div
                    class="
                        mt-4 flex items-center
                        justify-between gap-4
                    "
                >
                    <button
                        id="owner-expense-bill-add-line"
                        type="button"
                        class="pm-button-secondary"
                    >
                        <span data-i18n="owners.add_line">
    {{ __('ui.owners.add_line') }}
</span>
                    </button>

                    <div class="text-right">
                        <div
                            class="
                                text-xs font-medium
                                uppercase tracking-wide
                                text-[var(--pm-text-muted)]
                            "
                        >
                            <span data-i18n="owners.bill_total">
    {{ __('ui.owners.bill_total') }}
</span>
                        </div>

                        <div
                            id="owner-expense-bill-total"
                            class="
                                mt-1 text-lg font-semibold
                                text-[var(--pm-text)]
                            "
                        >
                            —
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label
                    for="owner-expense-bill-notes"
                    class="pm-field-label"
                >
                    <span data-i18n="owners.notes">
    {{ __('ui.owners.notes') }}
</span>
                    <span class="text-xs text-[var(--pm-text-subtle)]">
                        <span data-i18n="owners.optional">
    {{ __('ui.owners.optional') }}
</span>
                    </span>
                </label>

                <textarea
                    id="owner-expense-bill-notes"
                    rows="3"
                    class="pm-input"
                ></textarea>
            </div>

            </div>
        </div>

        <x-drawer-footer>
            <button
                id="owner-expense-bill-cancel"
                type="button"
                data-close-owner-modal="owner-expense-bill-modal"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
    {{ __('ui.actions.cancel') }}
</span>
            </button>

            <button
                id="owner-expense-bill-submit"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="owners.review">
    {{ __('ui.owners.review') }}
</span>
            </button>

            <button
                id="owner-expense-bill-back"
                type="button"
                class="pm-button-secondary pm-hide"
            >
                <span data-i18n="owners.back">
    {{ __('ui.owners.back') }}
</span>
            </button>

            <button
                id="owner-expense-bill-confirm"
                type="button"
                class="pm-button-primary pm-hide"
            >
                <span data-i18n="owners.confirm">
    {{ __('ui.owners.confirm') }}
</span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ====================================================
     V1.0.8 Expense Bill Pay Drawer

     Pays all or part of an owner expense bill from either
     the Deposit/Expense account (may go negative) or the
     strictly capped Payout account, with a read-only
     review step before anything is recorded.
==================================================== --}}

<x-drawer
    id="owner-bill-pay-drawer"
    backdrop-id="owner-bill-pay-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-bill-pay-drawer-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.pay_bill_title">
                {{ __('ui.owners.pay_bill_title') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.pay_bill_description">
                {{ __('ui.owners.pay_bill_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="owner-bill-pay-form"
        class="flex min-h-0 flex-1 flex-col"
        novalidate
    >
        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div
                id="owner-bill-pay-error"
                class="hidden rounded-xl border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-4 py-3 text-sm
                    text-[var(--pm-danger-text)]"
            ></div>

            <div id="owner-bill-pay-fields">
                <div
                    class="
                        mb-5 rounded-xl border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)] p-4
                    "
                >
                    <div
                        class="text-xs text-[var(--pm-text-muted)]"
                        data-i18n="owners.expense_bill"
                    >
                        {{ __('ui.owners.expense_bill') }}
                    </div>

                    <div
                        id="owner-bill-pay-context"
                        class="mt-2 text-sm font-semibold text-[var(--pm-text)]"
                    >
                        —
                    </div>

                    <div class="mt-3 text-xs text-[var(--pm-text-muted)]"
                        data-i18n="owners.outstanding"
                    >
                        {{ __('ui.owners.outstanding') }}
                    </div>

                    <div
                        id="owner-bill-pay-outstanding"
                        class="mt-1 font-semibold text-[var(--pm-text)]"
                    >
                        —
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <label
                            for="owner-bill-pay-source"
                            class="pm-field-label"
                        >
                            <span data-i18n="owners.pay_source_account">
                                {{ __('ui.owners.pay_source_account') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <select
                            id="owner-bill-pay-source"
                            required
                            class="pm-input"
                        ></select>
                    </div>

                    <div>
                        <label
                            for="owner-bill-pay-amount"
                            class="pm-field-label"
                        >
                            <span data-i18n="owners.amount">
                                {{ __('ui.owners.amount') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="owner-bill-pay-amount"
                            type="text"
                            inputmode="numeric"
                            data-money-input
                            required
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label
                            for="owner-bill-pay-date"
                            class="pm-field-label"
                        >
                            <span data-i18n="owners.date">
                                {{ __('ui.owners.date') }}
                            </span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="owner-bill-pay-date"
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
                id="owner-bill-pay-review"
                class="hidden"
            ></div>
        </div>

        <x-drawer-footer>
            <button
                type="button"
                id="owner-bill-pay-cancel"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="owner-bill-pay-submit"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="owners.review">
                    {{ __('ui.owners.review') }}
                </span>
            </button>

            <button
                id="owner-bill-pay-back"
                type="button"
                class="pm-button-secondary pm-hide"
            >
                <span data-i18n="owners.back">
                    {{ __('ui.owners.back') }}
                </span>
            </button>

            <button
                id="owner-bill-pay-confirm"
                type="button"
                class="pm-button-primary pm-hide"
            >
                <span data-i18n="owners.confirm">
                    {{ __('ui.owners.confirm') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ====================================================
     V1.0.8 Cancel Expense Bill Payment Drawer
==================================================== --}}

<x-drawer
    id="owner-bill-cancel-payment-drawer"
    backdrop-id="owner-bill-cancel-payment-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="owner-bill-cancel-payment-drawer-close"
        close-label="Close"
        close-label-key="owners.close"
    >
        <x-slot:title>
            <span data-i18n="owners.cancel_payment_title">
                {{ __('ui.owners.cancel_payment_title') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="owners.cancel_payment_description">
                {{ __('ui.owners.cancel_payment_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="owner-bill-cancel-payment-form"
        class="flex min-h-0 flex-1 flex-col"
        novalidate
    >
        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div
                id="owner-bill-cancel-payment-error"
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
                    id="owner-bill-cancel-payment-context"
                    class="text-sm font-semibold text-[var(--pm-text)]"
                >
                    —
                </div>

                <div
                    id="owner-bill-cancel-payment-detail"
                    class="mt-1 text-sm text-[var(--pm-text-muted)]"
                ></div>
            </div>

            <div>
                <label
                    for="owner-bill-cancel-payment-reason"
                    class="pm-field-label"
                >
                    <span data-i18n="owners.cancellation_reason">
                        {{ __('ui.owners.cancellation_reason') }}
                    </span>
                    <span class="text-[var(--pm-danger-text)]">*</span>
                </label>

                <textarea
                    id="owner-bill-cancel-payment-reason"
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
                id="owner-bill-cancel-payment-close"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="owner-bill-cancel-payment-submit"
                type="submit"
                class="pm-button-danger"
            >
                <span data-i18n="owners.cancel_payment">
                    {{ __('ui.owners.cancel_payment') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

@endsection
