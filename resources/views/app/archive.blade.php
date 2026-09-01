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

    V1.0.43 adds the search and the kind filter. This is the one list in
    Patrimoine that mixes parties, properties, units and lettings in a
    single column, so once it holds more than a handful of rows the only
    way to read it is to narrow it — and the reason each record was put
    away is searched alongside its name, because "everything we archived
    when the Ridge Road sale went through" is how somebody actually looks.
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

            <p
                id="archive-showing"
                class="text-xs text-[var(--pm-text-muted)]"
            ></p>
        </div>

        {{--
            Search and filter. Hidden while the archive is empty: there is
            nothing to search, and a filter bar over nothing only asks a
            reader to work out that the page is not broken.
        --}}
        <div
            id="archive-controls"
            class="
                hidden border-b border-[var(--pm-border-subtle)]
                px-5 py-4
            "
        >
            <label for="archive-search" class="sr-only">
                <span data-i18n="archive.search_label">{{ __('ui.archive.search_label') }}</span>
            </label>

            <input
                id="archive-search"
                type="search"
                autocomplete="off"
                data-i18n-placeholder="archive.search_placeholder"
                placeholder="{{ __('ui.archive.search_placeholder') }}"
                class="pm-input max-w-md"
            >

            <div
                id="archive-kind-filters"
                class="mt-3 flex flex-wrap items-center gap-2"
            ></div>
        </div>

        <div
            id="archive-loading"
            class="py-12 text-center text-sm text-[var(--pm-text-subtle)]"
        >
            <span data-i18n="archive.loading">{{ __('ui.archive.loading') }}</span>
        </div>

        <div id="archive-list" class="hidden"></div>

        <div
            id="archive-no-matches"
            class="hidden py-12 text-center text-sm text-[var(--pm-text-muted)]"
        >
            <span data-i18n="archive.no_matches">{{ __('ui.archive.no_matches') }}</span>
        </div>

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
