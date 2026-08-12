@extends('layouts.app')

@section('title', 'Reports — Patrimoine')

@section('content')

<div class="mx-auto max-w-[1600px]">

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
                    text-patrimoine-700
                "
            >
                Finance
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                Reports
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Review financial and operational reports across owners,
                tenants and properties.
            </p>
        </div>
    </div>

    {{-- Page Error --}}

    <div
        id="reports-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    <div
        class="
            grid gap-6
            xl:grid-cols-[320px_minmax(0,1fr)]
        "
    >

        {{-- ========================================================
             Report Controls
        ======================================================== --}}

        <aside
            class="
                self-start rounded-xl
                border border-slate-200
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
                    Report Type
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Select the report you want to review.
                </p>
            </div>

            <div class="p-3">

                <button
                    type="button"
                    data-report-type="managing-organisation"
                    class="
                        report-type-button
                        mb-1 flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left transition
                        bg-patrimoine-50
                        text-patrimoine-950
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            Managing Organisation
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            Portfolio-wide operational and financial summary.
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="owner"
                    class="
                        report-type-button
                        mb-1 flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left text-slate-700
                        transition hover:bg-slate-50
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            Owner Report
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            Owner balance, credits, debits and ledger history.
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="building"
                    class="
                        report-type-button
                        mb-1 flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left text-slate-700
                        transition hover:bg-slate-50
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            Building Report
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            Billing, collections, expenses and ownership.
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="unit"
                    class="
                        report-type-button
                        mb-1 flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left text-slate-700
                        transition hover:bg-slate-50
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            Unit Report
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            Lease, billing and collection history for one Unit.
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    data-report-type="tenant"
                    class="
                        report-type-button
                        flex w-full
                        items-start gap-3
                        rounded-lg px-3 py-3
                        text-left text-slate-700
                        transition hover:bg-slate-50
                    "
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold">
                            Tenant Statement
                        </div>

                        <div
                            class="
                                mt-1 text-xs
                                text-slate-500
                            "
                        >
                            Tenant billing, payments and held funds.
                        </div>
                    </div>
                </button>

            </div>

            {{-- ====================================================
                 Report Subject
            ==================================================== --}}

            <div
                id="report-subject-section"
                class="
                    hidden border-t
                    border-slate-100
                    px-5 py-4
                "
            >
                <label
                    id="report-subject-label"
                    for="report-subject-search"
                    class="
                        mb-1.5 block
                        text-sm font-medium
                        text-slate-700
                    "
                >
                    Search
                </label>

                <div class="relative">

                    <input
                        id="report-subject-search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search..."
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                            transition
                            focus:border-patrimoine-500
                            focus:ring-2
                            focus:ring-patrimoine-100
                        "
                    >

                    <div
                        id="report-subject-results"
                        class="
                            absolute z-30 mt-1 hidden
                            max-h-72 w-full
                            overflow-y-auto
                            rounded-xl
                            border border-slate-200
                            bg-white shadow-lg
                        "
                    ></div>

                </div>

                <input
                    id="report-subject-id"
                    type="hidden"
                >

                <div
                    id="report-selected-subject"
                    class="
                        mt-3 hidden
                        rounded-xl
                        border border-patrimoine-200
                        bg-patrimoine-50/50
                        p-3
                    "
                >
                    <div
                        class="
                            flex items-start
                            justify-between gap-3
                        "
                    >
                        <div class="min-w-0">
                            <div
                                id="report-selected-subject-name"
                                class="
                                    truncate text-sm font-semibold
                                    text-slate-900
                                "
                            ></div>

                            <div
                                id="report-selected-subject-meta"
                                class="
                                    mt-1 text-xs
                                    text-slate-500
                                "
                            ></div>
                        </div>

                        <button
                            id="report-clear-subject"
                            type="button"
                            class="
                                shrink-0 text-xs
                                font-medium
                                text-patrimoine-700
                                hover:text-patrimoine-950
                            "
                        >
                            Change
                        </button>
                    </div>
                </div>
            </div>

            {{-- ====================================================
                 Report Period
            ==================================================== --}}

            <div
                class="
                    border-t border-slate-100
                    px-5 py-4
                "
            >
                <div class="text-sm font-medium text-slate-700">
                    Reporting Period
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Leave dates empty to include all available history.
                </p>

                <div class="mt-4 space-y-3">

                    <div>
                        <label
                            for="report-from"
                            class="
                                mb-1.5 block
                                text-xs font-medium
                                text-slate-600
                            "
                        >
                            From
                        </label>

                        <input
                            id="report-from"
                            type="date"
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                px-3 py-2.5
                                text-sm outline-none
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        >
                    </div>

                    <div>
                        <label
                            for="report-to"
                            class="
                                mb-1.5 block
                                text-xs font-medium
                                text-slate-600
                            "
                        >
                            To
                        </label>

                        <input
                            id="report-to"
                            type="date"
                            class="
                                w-full rounded-lg
                                border border-slate-200
                                px-3 py-2.5
                                text-sm outline-none
                                focus:border-patrimoine-500
                                focus:ring-2
                                focus:ring-patrimoine-100
                            "
                        >
                    </div>

                </div>

                <button
                    id="run-report-button"
                    type="button"
                    class="
                        mt-4 w-full rounded-lg
                        bg-patrimoine-950
                        px-4 py-2.5
                        text-sm font-medium
                        text-white shadow-sm
                        transition
                        hover:bg-patrimoine-900
                        disabled:cursor-not-allowed
                        disabled:opacity-50
                    "
                >
                    Run Report
                </button>
            </div>
        </aside>

        {{-- ========================================================
             Report Output
        ======================================================== --}}

        <section
            class="
                min-w-0 overflow-hidden
                rounded-xl border
                border-slate-200
                bg-white shadow-sm
            "
        >

            <div
                class="
                    flex flex-col gap-4
                    border-b border-slate-100
                    px-6 py-5
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                "
            >
                <div>
                    <h2
                        id="report-output-title"
                        class="
                            text-xl font-semibold
                            tracking-tight
                            text-slate-950
                        "
                    >
                        Managing Organisation Report
                    </h2>

                    <div
                        id="report-output-subtitle"
                        class="
                            mt-1 text-sm
                            text-slate-500
                        "
                    >
                        Portfolio-wide financial and operational report.
                    </div>
                </div>

                <div
                    id="report-export-actions"
                    class="
                        hidden flex-wrap gap-2
                    "
                >
                    <button
                        id="report-pdf-button"
                        type="button"
                        class="
                            rounded-lg border
                            border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        PDF
                    </button>

                    <button
                        id="report-csv-button"
                        type="button"
                        class="
                            rounded-lg border
                            border-slate-200
                            bg-white px-3.5 py-2.5
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        CSV
                    </button>
                </div>
            </div>

            <div
                id="report-output"
                class="p-6"
            >
                <div
                    class="
                        flex min-h-[520px]
                        items-center justify-center
                    "
                >
                    <div class="max-w-md text-center">
                        <div
                            class="
                                text-sm text-slate-500
                            "
                        >
                            Select a report type and run the report.
                        </div>
                    </div>
                </div>
            </div>

        </section>

    </div>

</div>

@endsection
