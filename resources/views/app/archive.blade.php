@extends('layouts.app')

@section('title', __('ui.archive.title'))
@section('title-i18n', 'archive.title')

@section('content')

{{--
    The archive.

    Patrimoine refuses to delete anything the accounting still refers to,
    which is correct and also unhelpful: the operator rarely wants the
    record gone, they want it off the screen. Archiving does that and only
    that — the row is untouched, so every invoice, receipt, journal entry
    and audit line still names it — and this is where the things that have
    been put away are listed, and where they come back from.

    Restoring returns a record to every list and every picker in the
    product, so it is an administrator's decision; reading the page is not.
--}}

<div
    id="archive-page"
    data-requires-capability="view_operations"
    class="pm-page rbac-hidden mx-auto max-w-[1600px]"
>
    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--pm-accent)]">
        <span data-i18n="archive.eyebrow">{{ __('ui.archive.eyebrow') }}</span>
    </div>

    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-[var(--pm-text)]">
        <span data-i18n="archive.heading">{{ __('ui.archive.heading') }}</span>
    </h1>

    <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--pm-text-muted)]">
        <span data-i18n="archive.description">{{ __('ui.archive.description') }}</span>
    </p>

    <div
        id="archive-error"
        class="
            mt-6 hidden rounded-lg
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    <section class="pm-card mt-6">
        <div class="pm-card-header">
            <h2 class="pm-card-title">
                <span data-i18n="archive.list_title">{{ __('ui.archive.list_title') }}</span>
            </h2>
        </div>

        <div
            id="archive-loading"
            class="py-12 text-center text-sm text-[var(--pm-text-subtle)]"
        >
            <span data-i18n="archive.loading">{{ __('ui.archive.loading') }}</span>
        </div>

        <div id="archive-list" class="hidden"></div>

        <div
            id="archive-empty"
            class="pm-empty hidden"
        >
            <p class="text-sm text-[var(--pm-text-muted)]">
                <span data-i18n="archive.empty">{{ __('ui.archive.empty') }}</span>
            </p>
        </div>
    </section>
</div>

@endsection
