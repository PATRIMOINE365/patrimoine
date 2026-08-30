{{--
    The financial journal, as the Journal tab of Audit.

    Lifted unchanged from app/financial-journal.blade.php when the two logs
    became one page; financial-journal.js still finds it by
    #financial-journal-workspace and neither module knows it moved.
--}}

<div
    id="financial-journal-workspace"
    data-requires-capability="view_financial_journal"
    class="rbac-hidden"
>
    {{--
        The heading moved to the page; what is left in this row is the
        tab's own toolbar, so it sits to the right rather than filling
        the space the title used to hold.
    --}}
    <div
        class="
            flex flex-col gap-5
            sm:flex-row
            sm:items-start
            sm:justify-end
        "
    >

        <div class="flex shrink-0 flex-wrap gap-2 max-sm:w-full">
            <button
                id="financial-journal-export-xlsx"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="financial_journal.export_xlsx">
                    {{ __('ui.financial_journal.export_xlsx') }}
                </span>
            </button>

            <button
                id="financial-journal-export-csv"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="financial_journal.export_csv">
                    {{ __('ui.financial_journal.export_csv') }}
                </span>
            </button>
        </div>
    </div>

    <div
        id="financial-journal-error"
        class="
            mt-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
        role="alert"
    ></div>

    <section class="pm-activity-log-shell">
        <div class="pm-activity-log-filters">
            <div class="md:col-span-2">
                <label
                    for="financial-journal-search"
                    class="pm-field-label"
                >
                    <span data-i18n="financial_journal.search">
                        {{ __('ui.financial_journal.search') }}
                    </span>
                </label>

                <input
                    id="financial-journal-search"
                    type="search"
                    maxlength="255"
                    class="pm-input"
                    placeholder="{{ __('ui.financial_journal.search_placeholder') }}"
                    data-i18n-placeholder="financial_journal.search_placeholder"
                >
            </div>

            <div>
                <label
                    for="financial-journal-from"
                    class="pm-field-label"
                >
                    <span data-i18n="financial_journal.from">
                        {{ __('ui.financial_journal.from') }}
                    </span>
                </label>

                <input
                    id="financial-journal-from"
                    type="text"
                    inputmode="numeric"
                    maxlength="10"
                    autocomplete="off"
                    data-pm-date-input
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="financial-journal-to"
                    class="pm-field-label"
                >
                    <span data-i18n="financial_journal.to">
                        {{ __('ui.financial_journal.to') }}
                    </span>
                </label>

                <input
                    id="financial-journal-to"
                    type="text"
                    inputmode="numeric"
                    maxlength="10"
                    autocomplete="off"
                    data-pm-date-input
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="financial-journal-entry-kind"
                    class="pm-field-label"
                >
                    <span data-i18n="financial_journal.entry_kind">
                        {{ __('ui.financial_journal.entry_kind') }}
                    </span>
                </label>

                <select
                    id="financial-journal-entry-kind"
                    class="pm-input"
                >
                    <option
                        value=""
                        data-i18n="financial_journal.all_entry_kinds"
                    >{{ __('ui.financial_journal.all_entry_kinds') }}</option>

                    <option
                        value="financial"
                        data-i18n="financial_journal.kind_financial"
                    >{{ __('ui.financial_journal.kind_financial') }}</option>

                    <option
                        value="reversal"
                        data-i18n="financial_journal.kind_reversal"
                    >{{ __('ui.financial_journal.kind_reversal') }}</option>

                    <option
                        value="informational"
                        data-i18n="financial_journal.kind_informational"
                    >{{ __('ui.financial_journal.kind_informational') }}</option>
                </select>
            </div>

            <div>
                <label
                    for="financial-journal-transaction-type"
                    class="pm-field-label"
                >
                    <span data-i18n="financial_journal.transaction_type">
                        {{ __('ui.financial_journal.transaction_type') }}
                    </span>
                </label>

                <select
                    id="financial-journal-transaction-type"
                    class="pm-input"
                >
                    <option
                        value=""
                        data-i18n="financial_journal.all_transaction_types"
                    >{{ __('ui.financial_journal.all_transaction_types') }}</option>
                </select>
            </div>

            <div>
                <label
                    for="financial-journal-account"
                    class="pm-field-label"
                >
                    <span data-i18n="financial_journal.account">
                        {{ __('ui.financial_journal.account') }}
                    </span>
                </label>

                <select
                    id="financial-journal-account"
                    class="pm-input"
                >
                    <option
                        value=""
                        data-i18n="financial_journal.all_accounts"
                    >{{ __('ui.financial_journal.all_accounts') }}</option>
                </select>
            </div>

            <div class="flex items-end">
                <button
                    id="financial-journal-clear-filters"
                    type="button"
                    class="pm-button-secondary w-full"
                >
                    <span data-i18n="financial_journal.clear_filters">
                        {{ __('ui.financial_journal.clear_filters') }}
                    </span>
                </button>
            </div>
        </div>

        <div
            id="financial-journal-list"
            class="divide-y divide-[var(--pm-border-subtle)]"
        >
            <div
                class="
                    px-5 py-12 text-center
                    text-sm
                "
            >
                <span data-i18n="financial_journal.loading">
                    {{ __('ui.financial_journal.loading') }}
                </span>
            </div>
        </div>

        <div
            id="financial-journal-pagination"
            class="
                hidden border-t
                border-[var(--pm-border)]
                px-5 py-4
            "
        ></div>
    </section>
</div>

{{-- Read-only Financial Journal accounting detail --}}
<x-drawer
    id="financial-journal-modal"
    backdrop-id="financial-journal-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="financial-journal-modal-close"
        close-label="{{ __('ui.financial_journal.close') }}"
        close-label-key="financial_journal.close"
    >
        <x-slot:title>
            <span data-i18n="financial_journal.detail_heading">
                {{ __('ui.financial_journal.detail_heading') }}
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="financial_journal.detail_description">
                {{ __('ui.financial_journal.detail_description') }}
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div
        id="financial-journal-detail"
        class="
            min-h-0 flex-1
            overflow-y-auto
            px-6 py-6
        "
    ></div>
</x-drawer>
