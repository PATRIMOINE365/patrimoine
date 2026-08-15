@extends('layouts.app')

@section('title', 'Tenants — Patrimoine')

@section('content')

<div
    id="tenant-workspace"
    class="mx-auto max-w-[1600px]"
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
                Finance
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                Tenants
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Review tenant identity, contact information and lease history.
            </p>
        </div>
    </div>

    <div
        id="tenant-error"
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
            xl:grid-cols-[380px_minmax(0,1fr)]
        "
    >
        <section
            class="
                overflow-hidden rounded-xl
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
                    Tenants
                </h2>

                <p
                    id="tenant-result-count"
                    class="mt-1 text-xs text-slate-500"
                >
                    Search by tenant name, phone or email.
                </p>

                <div class="mt-4">
                    <label
                        for="tenant-search"
                        class="sr-only"
                    >
                        Search Tenants
                    </label>

                    <input
                        id="tenant-search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search tenants..."
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
                </div>
            </div>

            <div
                id="tenant-list"
                class="
                    max-h-[calc(100vh-270px)]
                    min-h-[420px]
                    overflow-y-auto
                "
            >
                <div
                    class="
                        px-5 py-8 text-center
                        text-sm text-slate-400
                    "
                >
                    Loading tenants…
                </div>
            </div>

            <div
                id="tenant-pagination"
                class="
                    hidden border-t
                    border-slate-100
                    px-4 py-3
                "
            ></div>
        </section>

        <section
            id="tenant-detail"
            class="
                min-w-0 overflow-hidden
                rounded-xl
                border border-slate-200
                bg-white shadow-sm
            "
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
                            rounded-full bg-slate-100
                            text-slate-500
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
                            text-slate-900
                        "
                    >
                        Select a Tenant
                    </h2>

                    <p
                        class="
                            mt-2 text-sm leading-6
                            text-slate-500
                        "
                    >
                        Choose a tenant to review their details and leases.
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>

@endsection
