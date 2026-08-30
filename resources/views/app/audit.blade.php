@extends('layouts.app')

@section('title', __('ui.audit.title'))
@section('title-i18n', 'audit.title')

@section('content')

{{--
    Audit — the activity log and the financial journal, on one page.

    They had a sidebar entry each and answered the same question from two
    places: what happened, and who did it. One is the record of ACTIONS and
    the other the record of MONEY, and somebody checking either is doing the
    same job.

    The tabs are Settings' tabs, deliberately: same strip, same pill, same
    switching, so the two administration pages that hold several things read
    as the same kind of page.

    Both workspaces render on load and each module finds its own by id, so
    activity-log.js and financial-journal.js did not have to change. That
    costs one extra request on arrival and buys a tab that is already there
    when it is opened.

    The old paths still work: /activity-log and /financial-journal redirect
    to the tab that now holds them, exactly as /users and /license redirect
    into Settings.
--}}

<div
    id="audit-page"
    data-requires-capability="view_activity_log"
    class="pm-page rbac-hidden mx-auto max-w-[1600px]"
>
    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--pm-accent)]">
        <span data-i18n="audit.eyebrow">{{ __('ui.audit.eyebrow') }}</span>
    </div>

    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-[var(--pm-text)]">
        <span data-i18n="audit.heading">{{ __('ui.audit.heading') }}</span>
    </h1>

    <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--pm-text-muted)]">
        <span data-i18n="audit.description">{{ __('ui.audit.description') }}</span>
    </p>

    <div class="mt-6">
        <div
            role="tablist"
            class="
                inline-flex max-w-full
                overflow-x-auto rounded-xl
                border border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)]
                p-1
            "
        >
            <button
                id="audit-tab-activity"
                type="button"
                role="tab"
                data-requires-capability="view_activity_log"
                aria-selected="true"
                aria-controls="audit-activity-panel"
                class="
                    rounded-lg px-4 py-2
                    text-sm font-medium
                    transition
                "
            >
                <span data-i18n="audit.tab_activity">{{ __('ui.audit.tab_activity') }}</span>
            </button>

            <button
                id="audit-tab-journal"
                type="button"
                role="tab"
                data-requires-capability="view_financial_journal"
                aria-selected="false"
                aria-controls="audit-journal-panel"
                class="
                    rounded-lg px-4 py-2
                    text-sm font-medium
                    transition
                "
            >
                <span data-i18n="audit.tab_journal">{{ __('ui.audit.tab_journal') }}</span>
            </button>
        </div>
    </div>

    <section
        id="audit-activity-panel"
        role="tabpanel"
        aria-labelledby="audit-tab-activity"
        class="mt-4"
    >
        @include('app.audit.activity')
    </section>

    <section
        id="audit-journal-panel"
        role="tabpanel"
        aria-labelledby="audit-tab-journal"
        class="mt-4 hidden"
    >
        @include('app.audit.journal')
    </section>
</div>

@endsection
