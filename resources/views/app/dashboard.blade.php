@extends('layouts.app')

@section('title', __('ui.dashboard.title'))
@section('title-i18n', 'dashboard.title')

@section('content')

<div class="pm-dashboard-page mx-auto max-w-[1600px]">

    <div
        class="
            mb-8 flex flex-col gap-4
            sm:flex-row sm:items-end sm:justify-between
        "
    >
        <div>
            <p
                class="
                    text-sm font-medium
                    text-patrimoine-700
                "
            >
                <span data-i18n="dashboard.overview">
                    {{ __('ui.dashboard.overview') }}
                </span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="dashboard.heading">
                    {{ __('ui.dashboard.heading') }}
                </span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="dashboard.description">
                    {{ __('ui.dashboard.description') }}
                </span>
            </p>
        </div>

        <div
            id="dashboard-date"
            class="
                text-sm font-medium
                text-slate-500
            "
        ></div>
    </div>

    <div
        id="dashboard-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    {{-- Main metrics --}}
    <div
        class="
            grid gap-4
            sm:grid-cols-2
            xl:grid-cols-4
        "
    >
        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.buildings">
                    {{ __('ui.dashboard.buildings') }}
                </span>
            </div>

            <div
                id="metric-buildings"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.total_units">
                    {{ __('ui.dashboard.total_units') }}
                </span>
            </div>

            <div
                id="metric-units"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.occupied_units">
                    {{ __('ui.dashboard.occupied_units') }}
                </span>
            </div>

            <div
                id="metric-occupied"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.vacant_units">
                    {{ __('ui.dashboard.vacant_units') }}
                </span>
            </div>

            <div
                id="metric-vacant"
                class="
                    mt-3 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- Financial metrics --}}
    <div
        class="
            mt-4 grid gap-4
            sm:grid-cols-2
            xl:grid-cols-4
        "
    >
        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.rent_due">
                    {{ __('ui.dashboard.rent_due') }}
                </span>
            </div>

            <div
                id="metric-rent-due"
                class="
                    mt-3 text-2xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.rent_overdue">
                    {{ __('ui.dashboard.rent_overdue') }}
                </span>
            </div>

            <div
                id="metric-rent-overdue"
                class="
                    mt-3 text-2xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.collected_this_month">
                    {{ __('ui.dashboard.collected_this_month') }}
                </span>
            </div>

            <div
                id="metric-collected"
                class="
                    mt-3 text-2xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>

        <div
            class="
                rounded-xl border border-slate-200
                bg-white p-5 shadow-sm
            "
        >
            <div class="text-sm text-slate-500">
                <span data-i18n="dashboard.owner_funds_held">
                    {{ __('ui.dashboard.owner_funds_held') }}
                </span>
            </div>

            <div
                id="metric-owner-funds"
                class="
                    mt-3 text-2xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                —
            </div>
        </div>
    </div>

    <div
        class="
            mt-6 grid gap-6
            xl:grid-cols-2
        "
    >
        <section
            class="
                rounded-xl border border-slate-200
                bg-white shadow-sm
            "
        >
            <div
                class="
                    border-b border-slate-100
                    px-5 py-4
                "
            >
                <h2
                    class="
                        text-base font-semibold
                        text-slate-950
                    "
                >
                    <span data-i18n="dashboard.overdue_rent">
                        {{ __('ui.dashboard.overdue_rent') }}
                    </span>
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    <span data-i18n="dashboard.overdue_description">
                        {{ __('ui.dashboard.overdue_description') }}
                    </span>
                </p>
            </div>

            <div
                id="overdue-list"
                class="p-5"
            >
                <div class="text-sm text-slate-400">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>

        <section
            class="
                rounded-xl border border-slate-200
                bg-white shadow-sm
            "
        >
            <div
                class="
                    border-b border-slate-100
                    px-5 py-4
                "
            >
                <h2
                    class="
                        text-base font-semibold
                        text-slate-950
                    "
                >
                    <span data-i18n="dashboard.upcoming_rent">
                        {{ __('ui.dashboard.upcoming_rent') }}
                    </span>
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    <span data-i18n="dashboard.upcoming_description">
                        {{ __('ui.dashboard.upcoming_description') }}
                    </span>
                </p>
            </div>

            <div
                id="upcoming-list"
                class="p-5"
            >
                <div class="text-sm text-slate-400">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>
    </div>

</div>

@endsection
