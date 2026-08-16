@extends('layouts.app')

@section('title', 'Activity Log — Patrimoine')
@section('title-i18n', 'activity_log.title')

@section('content')

<div
    id="activity-log-workspace"
    data-requires-capability="view_activity_log"
    class="rbac-hidden"
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
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="activity_log.heading">
                    Activity Log
                </span>
            </h1>

            <p
                class="
                    mt-2 max-w-3xl
                    text-sm leading-6 text-slate-500
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
                class="
                    rounded-lg border
                    border-slate-200
                    bg-white px-4 py-2.5
                    text-sm font-medium
                    text-slate-700
                    transition
                    hover:bg-slate-50
                    disabled:cursor-not-allowed
                    disabled:opacity-50
                "
            >
                <span data-i18n="activity_log.export_pdf">
                    Export PDF
                </span>
            </button>

            <button
                id="activity-log-export-csv"
                type="button"
                class="
                    rounded-lg
                    bg-patrimoine-700
                    px-4 py-2.5
                    text-sm font-medium
                    text-white
                    transition
                    hover:bg-patrimoine-800
                    disabled:cursor-not-allowed
                    disabled:opacity-50
                "
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
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
        role="alert"
    ></div>

    <section
        class="
            mt-7 rounded-xl
            border border-slate-200
            bg-white
        "
    >
        <div
            class="
                grid gap-4
                border-b border-slate-200
                p-5
                md:grid-cols-2
                xl:grid-cols-4
            "
        >
            <div class="md:col-span-2">
                <label
                    for="activity-log-search"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
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
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                        outline-none transition
                        focus:border-patrimoine-500
                        focus:ring-2
                        focus:ring-patrimoine-100
                    "
                >
            </div>

            <div>
                <label
                    for="activity-log-from"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="activity_log.from">
                        From
                    </span>
                </label>

                <input
                    id="activity-log-from"
                    type="date"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
            </div>

            <div>
                <label
                    for="activity-log-to"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="activity_log.to">
                        To
                    </span>
                </label>

                <input
                    id="activity-log-to"
                    type="date"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
            </div>

            <div>
                <label
                    for="activity-log-user"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="activity_log.user">
                        User
                    </span>
                </label>

                <select
                    id="activity-log-user"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
                    <option value="" data-i18n="activity_log.all_users">
                        All users
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="activity-log-role"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="activity_log.role">
                        Role
                    </span>
                </label>

                <select
                    id="activity-log-role"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
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
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
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
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
            </div>

            <div>
                <label
                    for="activity-log-entity-type"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
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
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
            </div>

            <div class="flex items-end">
                <button
                    id="activity-log-clear-filters"
                    type="button"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-4 py-2.5
                        text-sm font-medium
                        text-slate-700
                        hover:bg-slate-50
                    "
                >
                    <span data-i18n="activity_log.clear_filters">
                        Clear Filters
                    </span>
                </button>
            </div>
        </div>

        <div
            id="activity-log-list"
            class="divide-y divide-slate-100"
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
                border-slate-200
                px-5 py-4
            "
        ></div>
    </section>
</div>

{{-- Read-only Activity Log detail --}}
<div
    id="activity-log-modal"
    class="
        fixed inset-0 z-[70]
        hidden items-center justify-center
        p-4
    "
    aria-hidden="true"
>
    <div
        id="activity-log-modal-backdrop"
        class="
            absolute inset-0
            bg-slate-950/50
            backdrop-blur-[1px]
        "
    ></div>

    <div
        class="
            relative z-10
            max-h-[90vh]
            w-full max-w-3xl
            overflow-hidden
            rounded-2xl bg-white
            shadow-2xl
        "
    >
        <div
            class="
                flex items-start
                justify-between gap-5
                border-b border-slate-200
                px-6 py-5
            "
        >
            <div>
                <h2
                    class="
                        text-lg font-semibold
                        text-slate-950
                    "
                    data-i18n="activity_log.detail_heading"
                >
                    Activity Details
                </h2>

                <p
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                    data-i18n="activity_log.detail_description"
                >
                    Immutable historical information recorded for this action.
                </p>
            </div>

            <button
                id="activity-log-modal-close"
                type="button"
                class="
                    rounded-lg p-2
                    text-slate-400
                    hover:bg-slate-100
                    hover:text-slate-700
                "
                data-i18n-aria-label="activity_log.close"
                aria-label="Close"
            >
                ✕
            </button>
        </div>

        <div
            id="activity-log-detail"
            class="
                max-h-[calc(90vh-95px)]
                overflow-y-auto
                p-6
            "
        ></div>
    </div>
</div>

@endsection
