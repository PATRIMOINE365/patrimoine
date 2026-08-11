@extends('layouts.app')

@section('title', 'Dashboard — Patrimoine')

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
                Overview
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                Dashboard
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Current portfolio and financial position.
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
                Buildings
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
                Total Units
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
                Occupied Units
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
                Vacant Units
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
                Rent Due
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
                Rent Overdue
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
                Collected This Month
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
                Owner Funds Held
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
                    Overdue Rent
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Outstanding obligations requiring attention.
                </p>
            </div>

            <div
                id="overdue-list"
                class="p-5"
            >
                <div class="text-sm text-slate-400">
                    Loading…
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
                    Upcoming Rent
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Rent obligations becoming due soon.
                </p>
            </div>

            <div
                id="upcoming-list"
                class="p-5"
            >
                <div class="text-sm text-slate-400">
                    Loading…
                </div>
            </div>
        </section>
    </div>

</div>

@endsection
