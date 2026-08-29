@extends('layouts.app')

@section('title', 'Help & documentation — Patrimoine')
@section('title-i18n', 'help.title')

@section('content')

{{--
    V1.0.7 Help & Documentation.

    Static shell only: the guide topics and the update log are rendered by
    resources/js/help.js so that search operates on the resolved translated
    strings. The page is available to every authenticated role.
--}}
<div
    id="help-workspace"
    class="mx-auto max-w-[1040px]"
>
    <div>
        <div
            class="
                text-xs font-semibold uppercase
                tracking-[0.14em]
                text-[var(--pm-accent)]
            "
            data-i18n="help.eyebrow"
        >
            Support
        </div>

        <h1
            class="
                mt-2 text-2xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
            data-i18n="help.heading"
        >
            Help &amp; documentation
        </h1>

        <p
            class="
                mt-2 max-w-3xl
                text-sm leading-6
                text-[var(--pm-text-muted)]
            "
            data-i18n="help.description"
        >
            How Patrimoine works, organised by topic,
            plus the history of application updates.
        </p>
    </div>

    {{-- Sticky toolbar: tabs + guide filters --}}
    <div
        id="help-toolbar"
        class="
            sticky top-20 z-20
            mt-6 py-3
            bg-[var(--pm-page)]
        "
    >
        <div class="pm-card p-4">
            <div
                class="
                    flex flex-col gap-4
                    md:flex-row md:items-end
                "
            >
                {{--
                    self-start keeps the tabs left-aligned while the toolbar
                    is a column (mobile); md:self-end bottom-aligns them with
                    the labelled search + category fields once it becomes a
                    row, so the controls sit on one line.
                --}}
                <div
                    role="tablist"
                    class="
                        inline-flex shrink-0
                        self-start md:self-end
                        rounded-xl
                        border border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)]
                        p-1
                    "
                >
                    <button
                        id="help-tab-guide"
                        type="button"
                        role="tab"
                        aria-selected="true"
                        aria-controls="help-guide-panel"
                        class="
                            rounded-lg px-4 py-2
                            text-sm font-medium
                            transition
                        "
                    >
                        <span data-i18n="help.tab_guide">
                            Guide
                        </span>
                    </button>

                    <button
                        id="help-tab-errors"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="help-errors-panel"
                        class="
                            rounded-lg px-4 py-2
                            text-sm font-medium
                            transition
                        "
                    >
                        <span data-i18n="errors.heading">
                            Error codes
                        </span>
                    </button>

                    <button
                        id="help-tab-updates"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="help-updates-panel"
                        class="
                            rounded-lg px-4 py-2
                            text-sm font-medium
                            transition
                        "
                    >
                        <span data-i18n="help.tab_updates">
                            Update log
                        </span>
                    </button>
                </div>

                <div
                    id="help-guide-filters"
                    class="
                        grid flex-1 grid-cols-1 gap-4
                        sm:grid-cols-3
                    "
                >
                    {{--
                        No labels above these two: the placeholder says
                        what the box is for, and the guide reads cleaner
                        without a word sitting over every control.
                    --}}
                    <div class="sm:col-span-2">
                        <label
                            for="help-search"
                            class="sr-only"
                            data-i18n="help.search"
                        >Search</label>

                        <input
                            id="help-search"
                            type="search"
                            maxlength="255"
                            autocomplete="off"
                            class="pm-input"
                            placeholder="Search the guide…"
                            data-i18n-placeholder="help.search_placeholder"
                        >
                    </div>

                    <div>
                        <label
                            for="help-category"
                            class="sr-only"
                            data-i18n="help.category"
                        >Category</label>

                        <select
                            id="help-category"
                            class="pm-input"
                        >
                            <option
                                value=""
                                data-i18n="help.all_categories"
                            >All categories</option>

                            <option
                                value="getting_started"
                                data-i18n="help.category_getting_started"
                            >Getting started</option>

                            <option
                                value="properties"
                                data-i18n="help.category_properties"
                            >Properties &amp; units</option>

                            <option
                                value="parties"
                                data-i18n="help.category_parties"
                            >Parties</option>

                            <option
                                value="leases"
                                data-i18n="help.category_leases"
                            >Leases</option>

                            <option
                                value="money_in"
                                data-i18n="help.category_money_in"
                            >Money in</option>

                            <option
                                value="owners"
                                data-i18n="help.category_owners"
                            >Owners</option>

                            <option
                                value="invoicing"
                                data-i18n="help.category_invoicing"
                            >Invoicing &amp; automation</option>

                            <option
                                value="reports"
                                data-i18n="help.category_reports"
                            >Reports &amp; exports</option>

                            <option
                                value="journal"
                                data-i18n="help.category_journal"
                            >Financial journal &amp; activity log</option>

                            <option
                                value="admin"
                                data-i18n="help.category_admin"
                            >Users &amp; settings</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        id="help-error"
        class="
            mt-4 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
        role="alert"
    ></div>

    {{-- Guide --}}
    <section
        id="help-guide-panel"
        role="tabpanel"
        aria-labelledby="help-tab-guide"
        class="mt-4"
    >
        <div id="help-guide-content"></div>
    </section>

    {{-- Error codes --}}
    <section
        id="help-errors-panel"
        role="tabpanel"
        aria-labelledby="help-tab-errors"
        class="mt-4 hidden"
    >
        <p class="mb-4 text-sm text-[var(--pm-text-muted)]">
            <span data-i18n="errors.intro">{{ __('ui.errors.intro') }}</span>
        </p>

        <div class="mb-5">
            <label for="help-error-search" class="pm-field-label">
                <span data-i18n="errors.search_label">{{ __('ui.errors.search_label') }}</span>
            </label>

            <input
                id="help-error-search"
                type="search"
                autocomplete="off"
                class="pm-input"
                placeholder="{{ __('ui.errors.search_placeholder') }}"
                data-i18n-placeholder="errors.search_placeholder"
            >
        </div>

        <div id="help-errors-content"></div>
    </section>

    {{-- Update log --}}
    <section
        id="help-updates-panel"
        role="tabpanel"
        aria-labelledby="help-tab-updates"
        class="mt-4 hidden"
    >
        <div id="help-updates-content">
            <div
                class="
                    px-5 py-12 text-center
                    text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="help.updates_loading">
                    Loading update log…
                </span>
            </div>
        </div>
    </section>
</div>

@endsection
