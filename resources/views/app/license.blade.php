@extends('layouts.app')

@section('title', 'License & plan — Patrimoine 365')
@section('title-i18n', 'license.title')

@section('content')

{{--
    V1.0.10 licence & plan page.

    Static shell only: the current plan card, usage meters and the plan
    comparison table are rendered by resources/js/license.js from
    GET /api/license.
--}}
<div
    id="license-workspace"
    class="mx-auto max-w-[1040px]"
>
    <div>
        <div
            class="
                text-xs font-semibold uppercase
                tracking-[0.14em]
                text-[var(--pm-accent)]
            "
            data-i18n="license.eyebrow"
        >
            Subscription
        </div>

        <h1
            class="
                mt-2 text-2xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
            data-i18n="license.heading"
        >
            License &amp; plan
        </h1>

        <p
            class="
                mt-2 max-w-3xl
                text-sm leading-6
                text-[var(--pm-text-muted)]
            "
            data-i18n="license.description"
        >
            Your organisation's current plan, usage against its limits,
            and what each plan includes.
        </p>
    </div>

    <div
        id="license-error"
        class="
            mt-6 hidden rounded-xl border
            border-[var(--pm-danger,#b3261e)]
            px-4 py-3 text-sm
        "
    ></div>

    {{-- Current plan summary card --}}
    <div
        id="license-current"
        class="
            mt-6 rounded-2xl border border-[var(--pm-border)]
            bg-[var(--pm-surface)] p-6
        "
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div
                    class="text-sm text-[var(--pm-text-muted)]"
                    data-i18n="license.current_plan"
                >
                    Current plan
                </div>

                <div class="mt-1 flex items-center gap-3">
                    <span
                        id="license-plan-name"
                        class="
                            text-2xl font-semibold tracking-tight
                            text-[var(--pm-text)]
                        "
                    >—</span>

                    <span
                        id="license-trial-badge"
                        class="
                            hidden rounded-full
                            bg-[var(--pm-accent)]/10 px-3 py-1
                            text-xs font-semibold
                            text-[var(--pm-accent)]
                        "
                    ></span>
                </div>
            </div>

            <div class="max-w-sm text-sm leading-6 text-[var(--pm-text-muted)]">
                <span data-i18n="license.upgrade_hint">
                    To subscribe, extend or change plans, contact
                </span>
                <a
                    class="font-medium text-[var(--pm-accent)] underline"
                    href="mailto:billing@patrimoine365.com"
                >billing@patrimoine365.com</a>
            </div>
        </div>

        {{-- Usage meters --}}
        <div
            id="license-usage"
            class="
                mt-6 grid gap-4
                sm:grid-cols-2 xl:grid-cols-4
            "
        ></div>
    </div>

    {{-- Plan comparison --}}
    <h2
        class="
            mt-10 text-lg font-semibold
            tracking-tight text-[var(--pm-text)]
        "
        data-i18n="license.compare_heading"
    >
        Compare plans
    </h2>

    <div class="mt-4 overflow-x-auto">
        <table
            id="license-plans-table"
            class="
                w-full min-w-[640px] border-separate
                border-spacing-0 text-sm
            "
        ></table>
    </div>

    <p
        class="
            mt-6 text-sm leading-6
            text-[var(--pm-text-muted)]
        "
        data-i18n="license.footnotes"
    >
        Every new organisation starts with a 30-day Professional trial —
        no payment card required. Prices in USD; annual billing gives two
        months free. Above 1 000 active leases, talk to us. Financial
        integrity and transactional document email are identical on every
        plan, and sign-in email is never blocked.
    </p>
</div>

@endsection
