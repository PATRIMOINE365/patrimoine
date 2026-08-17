@extends('layouts.app')

@section('title', 'Activity Log — Patrimoine')
@section('title-i18n', 'activity_log.title')

@section('content')

<div
    id="activity-log-workspace"
    data-requires-capability="view_activity_log"
    class="rbac-hidden pm-activity-log-page"
>
    <div
        class="
            flex flex-col gap-5
            sm:flex-row
            sm:items-start
            sm:justify-between
        "
    >
        <div>
            <div
                class="
                    text-xs font-semibold uppercase
                    tracking-[0.14em]
                    text-patrimoine-700
                "
            >
                <span data-i18n="activity_log.administration">
                    Administration
                </span>
            </div>

            <h1
                class="
                    mt-2 text-2xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="activity_log.heading">
                    Activity Log
                </span>
            </h1>

            <p
                class="
                    mt-2 max-w-3xl
                    text-sm leading-6 text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="activity_log.description">
                    Review meaningful human actions recorded by Patrimoine.
                </span>
            </p>
        </div>

        <div
            class="
                flex shrink-0
                flex-wrap gap-2
            "
        >
            <button
                id="activity-log-export-pdf"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="activity_log.export_pdf">
                    Export PDF
                </span>
            </button>

            <button
                id="activity-log-export-csv"
                type="button"
                class="pm-button-primary"
            >
                <span data-i18n="activity_log.export_csv">
                    Export CSV
                </span>
            </button>
        </div>
    </div>

    <div
        id="activity-log-error"
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
                    for="activity-log-search"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.search">
                        Search
                    </span>
                </label>

                <input
                    id="activity-log-search"
                    type="search"
                    maxlength="255"
                    data-i18n-placeholder="activity_log.search_placeholder"
                    placeholder="Search actor, action, record, IP or historical context..."
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="activity-log-from"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.from">
                        From
                    </span>
                </label>

                <input
                    id="activity-log-from"
                    type="date"
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="activity-log-to"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.to">
                        To
                    </span>
                </label>

                <input
                    id="activity-log-to"
                    type="date"
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="activity-log-user"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.user">
                        User
                    </span>
                </label>

                <select
                    id="activity-log-user"
                    class="pm-input"
                >
                    <option value="" data-i18n="activity_log.all_users">
                        All users
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="activity-log-role"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.role">
                        Role
                    </span>
                </label>

                <select
                    id="activity-log-role"
                    class="pm-input"
                >
                    <option value="" data-i18n="activity_log.all_roles">
                        All roles
                    </option>

                    <option
                        value="administrator"
                        data-i18n="roles.administrator"
                    >
                        Administrator
                    </option>

                    <option
                        value="property_manager"
                        data-i18n="roles.property_manager"
                    >
                        Property Manager
                    </option>

                    <option
                        value="viewer"
                        data-i18n="roles.viewer"
                    >
                        Viewer
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="activity-log-action"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.action">
                        Action
                    </span>
                </label>

                <input
                    id="activity-log-action"
                    type="text"
                    maxlength="100"
                    data-i18n-placeholder="activity_log.action_placeholder"
                    placeholder="e.g. payment.recorded"
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="activity-log-entity-type"
                    class="pm-field-label"
                >
                    <span data-i18n="activity_log.entity_type">
                        Record Type
                    </span>
                </label>

                <input
                    id="activity-log-entity-type"
                    type="text"
                    maxlength="100"
                    data-i18n-placeholder="activity_log.entity_type_placeholder"
                    placeholder="e.g. payment"
                    class="pm-input"
                >
            </div>

            <div class="flex items-end">
                <button
                    id="activity-log-clear-filters"
                    type="button"
                    class="pm-button-secondary w-full"
                >
                    <span data-i18n="activity_log.clear_filters">
                        Clear Filters
                    </span>
                </button>
            </div>
        </div>

        <div
            id="activity-log-list"
            class="divide-y divide-[var(--pm-border-subtle)]"
        >
            <div
                class="
                    px-5 py-12 text-center
                    text-sm text-slate-400
                "
            >
                <span data-i18n="activity_log.loading">
                    Loading activity...
                </span>
            </div>
        </div>

        <div
            id="activity-log-pagination"
            class="
                hidden border-t
                border-[var(--pm-border)]
                px-5 py-4
            "
        ></div>
    </section>
</div>

{{-- Read-only Activity Log detail drawer --}}
<x-drawer
    id="activity-log-modal"
    backdrop-id="activity-log-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="activity-log-modal-close"
        close-label="Close"
        close-label-key="activity_log.close"
    >
        <x-slot:title>
            <span data-i18n="activity_log.detail_heading">
                Activity Details
            </span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="activity_log.detail_description">
                Immutable historical information recorded for this action.
            </span>
        </x-slot:description>
    </x-drawer-header>

    <div
        id="activity-log-detail"
        class="
            min-h-0 flex-1
            overflow-y-auto
            px-6 py-6
        "
    ></div>
</x-drawer>

@endsection
