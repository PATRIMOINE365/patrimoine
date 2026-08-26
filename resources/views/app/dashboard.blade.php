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
                    text-[var(--pm-accent)]
                "
            >
                <span data-i18n="dashboard.overview">
                    {{ __('ui.dashboard.overview') }}
                </span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="dashboard.heading">
                    {{ __('ui.dashboard.heading') }}
                </span>
            </h1>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="dashboard.description">
                    {{ __('ui.dashboard.description') }}
                </span>
            </p>
        </div>
    </div>

    <div
        id="dashboard-error"
        role="alert"
        class="
            mb-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- Row 1 — occupancy hero band + money tiles --}}
    <div
        class="
            grid gap-4
            sm:grid-cols-2
            lg:grid-cols-4
            xl:grid-cols-5
        "
    >
        <div
            class="
                pm-card p-5
                sm:col-span-2
                lg:col-span-4
                xl:col-span-1
            "
        >
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="dashboard.occupancy_rate">
                    {{ __('ui.dashboard.occupancy_rate') }}
                </span>
            </div>

            <div
                id="metric-occupancy-rate"
                class="
                    mt-3 text-4xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>

            <div
                class="
                    mt-3 h-2 w-full overflow-hidden
                    rounded-full
                    bg-[var(--pm-surface-muted)]
                "
            >
                <div
                    id="occupancy-meter"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="0"
                    aria-label="{{ __('ui.dashboard.occupancy_rate') }}"
                    data-i18n-aria-label="dashboard.occupancy_rate"
                    class="
                        h-2 w-0 rounded-full
                        bg-[var(--pm-primary)]
                        transition-[width] duration-500
                    "
                ></div>
            </div>

            <div
                class="
                    mt-4 flex flex-wrap items-center
                    gap-x-4 gap-y-1 text-sm
                "
            >
                <div class="text-[var(--pm-text-secondary)]">
                    <span
                        id="metric-occupied"
                        class="
                            font-semibold
                            text-[var(--pm-text)]
                        "
                    >—</span>

                    <span data-i18n="dashboard.occupied">
                        {{ __('ui.dashboard.occupied') }}
                    </span>
                </div>

                <div class="text-[var(--pm-text-secondary)]">
                    <span
                        id="metric-vacant"
                        class="
                            font-semibold
                            text-[var(--pm-text)]
                        "
                    >—</span>

                    <span data-i18n="dashboard.vacant">
                        {{ __('ui.dashboard.vacant') }}
                    </span>
                </div>
            </div>

            <div
                class="
                    mt-2 flex flex-wrap items-center
                    gap-x-4 gap-y-1 text-xs
                    text-[var(--pm-text-muted)]
                "
            >
                <div>
                    <span id="metric-vacant-commercial">—</span>

                    <span data-i18n="dashboard.vacant_commercial">
                        {{ __('ui.dashboard.vacant_commercial') }}
                    </span>
                </div>

                <div>
                    <span id="metric-vacant-residential">—</span>

                    <span data-i18n="dashboard.vacant_residential">
                        {{ __('ui.dashboard.vacant_residential') }}
                    </span>
                </div>
            </div>

            <div
                class="
                    mt-4 border-t
                    border-[var(--pm-border-subtle)]
                    pt-3 text-xs
                    text-[var(--pm-text-muted)]
                "
            >
                <span
                    id="metric-buildings"
                    class="
                        font-medium
                        text-[var(--pm-text-secondary)]
                    "
                >—</span>

                <span data-i18n="dashboard.buildings">
                    {{ __('ui.dashboard.buildings') }}
                </span>

                <span class="mx-1">·</span>

                <span
                    id="metric-units"
                    class="
                        font-medium
                        text-[var(--pm-text-secondary)]
                    "
                >—</span>

                <span data-i18n="dashboard.total_units">
                    {{ __('ui.dashboard.total_units') }}
                </span>
            </div>
        </div>

        <div class="pm-card flex h-full flex-col p-5">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="dashboard.rent_overdue">
                    {{ __('ui.dashboard.rent_overdue') }}
                </span>
            </div>

            <div
                id="metric-rent-overdue"
                class="
                    mt-auto pt-3 text-2xl font-semibold
                    tracking-tight
                    text-[var(--pm-danger-text)]
                "
            >
                —
            </div>
        </div>

        <div class="pm-card flex h-full flex-col p-5">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="dashboard.rent_due">
                    {{ __('ui.dashboard.rent_due') }}
                </span>
            </div>

            <div
                id="metric-rent-due"
                class="
                    mt-auto pt-3 text-2xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>

        <div class="pm-card flex h-full flex-col p-5">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="dashboard.collected_this_month">
                    {{ __('ui.dashboard.collected_this_month') }}
                </span>
            </div>

            <div
                id="metric-collected"
                class="
                    mt-auto pt-3 text-2xl font-semibold
                    tracking-tight
                    text-[var(--pm-success-text)]
                "
            >
                —
            </div>
        </div>

        <div class="pm-card flex h-full flex-col p-5">
            <div class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="dashboard.management_fees_this_month">
                    {{ __('ui.dashboard.management_fees_this_month') }}
                </span>
            </div>

            <div
                id="metric-management-fees"
                class="
                    mt-auto pt-3 text-2xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                —
            </div>
        </div>
    </div>

    {{-- Row 2 — collections trend + funds held --}}
    <div
        class="
            mt-6 grid gap-6
            xl:grid-cols-3
        "
    >
        <section
            class="
                pm-card
                xl:col-span-2
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
                    <span data-i18n="dashboard.collections_trend">
                        {{ __('ui.dashboard.collections_trend') }}
                    </span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="dashboard.collections_trend_description">
                        {{ __('ui.dashboard.collections_trend_description') }}
                    </span>
                </p>
            </div>

            <div
                id="collections-chart"
                class="p-5"
            >
                <div class="text-sm text-[var(--pm-text-subtle)]">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>

        <section class="pm-card">
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
                    <span data-i18n="dashboard.funds_held">
                        {{ __('ui.dashboard.funds_held') }}
                    </span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="dashboard.funds_held_description">
                        {{ __('ui.dashboard.funds_held_description') }}
                    </span>
                </p>
            </div>

            <div class="grid gap-4 p-5">
                <div
                    class="
                        rounded-xl border
                        border-[var(--pm-border-subtle)]
                        bg-[var(--pm-surface-subtle)]
                        p-4
                    "
                >
                    <div class="text-sm text-[var(--pm-text-muted)]">
                        <span data-i18n="dashboard.owner_funds_held">
                            {{ __('ui.dashboard.owner_funds_held') }}
                        </span>
                    </div>

                    <div
                        id="metric-owner-funds"
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
                        border-[var(--pm-border-subtle)]
                        bg-[var(--pm-surface-subtle)]
                        p-4
                    "
                >
                    <div class="text-sm text-[var(--pm-text-muted)]">
                        <span data-i18n="dashboard.tenant_funds_held">
                            {{ __('ui.dashboard.tenant_funds_held') }}
                        </span>
                    </div>

                    <div
                        id="metric-tenant-funds"
                        class="
                            mt-2 text-2xl font-semibold
                            tracking-tight text-[var(--pm-text)]
                        "
                    >
                        —
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Row 3 — overdue + upcoming rent --}}
    <div
        class="
            mt-6 grid gap-6
            lg:grid-cols-2
        "
    >
        <section class="pm-card">
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
                    <span data-i18n="dashboard.overdue_rent">
                        {{ __('ui.dashboard.overdue_rent') }}
                    </span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="dashboard.overdue_description">
                        {{ __('ui.dashboard.overdue_description') }}
                    </span>
                </p>
            </div>

            <div
                id="overdue-list"
                class="p-4 sm:p-5"
            >
                <div class="text-sm text-[var(--pm-text-subtle)]">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>

        <section class="pm-card">
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
                    <span data-i18n="dashboard.upcoming_rent">
                        {{ __('ui.dashboard.upcoming_rent') }}
                    </span>
                </h2>

                <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                    <span data-i18n="dashboard.upcoming_description">
                        {{ __('ui.dashboard.upcoming_description') }}
                    </span>
                </p>
            </div>

            <div
                id="upcoming-list"
                class="p-4 sm:p-5"
            >
                <div class="text-sm text-[var(--pm-text-subtle)]">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>
    </div>

    {{-- Row 4 — expiring leases + upcoming increments --}}
    <div
        class="
            mt-6 grid gap-6
            lg:grid-cols-2
        "
    >
        <section class="pm-card">
            <div
                class="
                    flex items-start justify-between
                    gap-3 border-b
                    border-[var(--pm-border-subtle)]
                    px-5 py-4
                "
            >
                <div>
                    <h2
                        class="
                            text-base font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="dashboard.expiring_leases">
                            {{ __('ui.dashboard.expiring_leases') }}
                        </span>
                    </h2>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="dashboard.expiring_leases_description">
                            {{ __('ui.dashboard.expiring_leases_description') }}
                        </span>
                    </p>
                </div>

                <span
                    id="expiring-count"
                    class="
                        hidden shrink-0 rounded-full
                        border px-2.5 py-0.5
                        text-xs font-semibold
                        border-[var(--pm-warning-border)]
                        bg-[var(--pm-warning-background)]
                        text-[var(--pm-warning-text)]
                    "
                ></span>
            </div>

            <div
                id="expiring-list"
                class="p-5"
            >
                <div class="text-sm text-[var(--pm-text-subtle)]">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>

        <section class="pm-card">
            <div
                class="
                    flex items-start justify-between
                    gap-3 border-b
                    border-[var(--pm-border-subtle)]
                    px-5 py-4
                "
            >
                <div>
                    <h2
                        class="
                            text-base font-semibold
                            text-[var(--pm-text)]
                        "
                    >
                        <span data-i18n="dashboard.upcoming_increments">
                            {{ __('ui.dashboard.upcoming_increments') }}
                        </span>
                    </h2>

                    <p class="mt-1 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="dashboard.upcoming_increments_description">
                            {{ __('ui.dashboard.upcoming_increments_description') }}
                        </span>
                    </p>
                </div>

                <span
                    id="increments-count"
                    aria-label="{{ __('ui.dashboard.increments_count_aria') }}"
                    data-i18n-aria-label="dashboard.increments_count_aria"
                    class="
                        hidden shrink-0 rounded-full
                        border px-2.5 py-0.5
                        text-xs font-semibold
                        border-[var(--pm-info-border)]
                        bg-[var(--pm-info-background)]
                        text-[var(--pm-info-text)]
                    "
                ></span>
            </div>

            <div
                id="increments-list"
                class="p-5"
            >
                <div class="text-sm text-[var(--pm-text-subtle)]">
                    <span data-i18n="dashboard.loading">
                        {{ __('ui.dashboard.loading') }}
                    </span>
                </div>
            </div>
        </section>
    </div>

</div>

@endsection
