@extends('layouts.app')

@section('title', 'Dashboard — Patrimoine')
@section('title-i18n', 'dashboard.title')

@section('content')

<div class="mx-auto max-w-[1600px]">

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
                    Overview
                </span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="dashboard.heading">
                    Dashboard
                </span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="dashboard.description">
                    Current portfolio and financial position.
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
                    Buildings
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
                    Total Units
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
                    Occupied Units
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
                    Vacant Units
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
                    Rent Due
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
                    Rent Overdue
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
                    Collected This Month
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
                    Owner Funds Held
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
                        Overdue Rent
                    </span>
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    <span data-i18n="dashboard.overdue_description">
                        Outstanding obligations requiring attention.
                    </span>
                </p>
            </div>

            <div
                id="overdue-list"
                class="p-5"
            >
                <div class="text-sm text-slate-400">
                    <span data-i18n="dashboard.loading">
                        Loading…
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
                        Upcoming Rent
                    </span>
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    <span data-i18n="dashboard.upcoming_description">
                        Rent obligations becoming due soon.
                    </span>
                </p>
            </div>

            <div
                id="upcoming-list"
                class="p-5"
            >
                <div class="text-sm text-slate-400">
                    <span data-i18n="dashboard.loading">
                        Loading…
                    </span>
                </div>
            </div>
        </section>
    </div>

</div>

@endsection
