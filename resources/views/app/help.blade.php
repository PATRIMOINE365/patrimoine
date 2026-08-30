@extends('layouts.app')

@section('title', 'Help & documentation — Patrimoine')
@section('title-i18n', 'help.title')

@section('content')

{{--
    V1.0.7 Help & Documentation.

    Static shell only: the guide topics, the error catalogue and the
    update log are rendered by resources/js/help.js so that search
    operates on the resolved translated strings. Available to every
    authenticated role.

    V1.0.36 changed two things about the shape of this page.

    Support comes first, because somebody who opens Help usually wants a
    person, and the profile menu now has one entry pointing here rather
    than two pointing at reference material.

    The tabs no longer sit inside a card. A tab strip is already a
    container; putting it in a second one drew a box around a box and
    made the page look like a dialog inside a dialog.
--}}
<div
    id="help-workspace"
    class="mx-auto max-w-[1040px]"
>
    <div>
        <div
            class="
                text-xs font-semibold uppercase
                tracking-[0.14em]
                text-[var(--pm-accent)]
            "
            data-i18n="help.eyebrow"
        >
            Support
        </div>

        <h1
            class="
                mt-2 text-2xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
            data-i18n="help.heading"
        >
            Help &amp; documentation
        </h1>

        <p
            class="
                mt-2 max-w-3xl
                text-sm leading-6
                text-[var(--pm-text-muted)]
            "
            data-i18n="help.description"
        >
            Ask us a question, read how a task is done, or look up a code.
        </p>
    </div>

    {{-- Sticky tab strip, on the page rather than in a card. --}}
    <div
        id="help-toolbar"
        class="
            sticky top-20 z-20
            mt-6 py-3
            bg-[var(--pm-page)]
        "
    >
        <div
            role="tablist"
            class="
                inline-flex max-w-full shrink-0
                overflow-x-auto rounded-xl
                border border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)]
                p-1
            "
        >
            <button
                id="help-tab-support"
                type="button"
                role="tab"
                aria-selected="true"
                aria-controls="help-support-panel"
                class="
                    whitespace-nowrap rounded-lg px-4 py-2
                    text-sm font-medium
                    transition
                "
            >
                <span data-i18n="help.tab_support">
                    Contact support
                </span>
            </button>

            <button
                id="help-tab-guide"
                type="button"
                role="tab"
                aria-selected="false"
                aria-controls="help-guide-panel"
                class="
                    whitespace-nowrap rounded-lg px-4 py-2
                    text-sm font-medium
                    transition
                "
            >
                <span data-i18n="help.tab_guide">
                    Guide
                </span>
            </button>

            <button
                id="help-tab-errors"
                type="button"
                role="tab"
                aria-selected="false"
                aria-controls="help-errors-panel"
                class="
                    whitespace-nowrap rounded-lg px-4 py-2
                    text-sm font-medium
                    transition
                "
            >
                <span data-i18n="errors.heading">
                    Error codes
                </span>
            </button>

            <button
                id="help-tab-updates"
                type="button"
                role="tab"
                aria-selected="false"
                aria-controls="help-updates-panel"
                class="
                    whitespace-nowrap rounded-lg px-4 py-2
                    text-sm font-medium
                    transition
                "
            >
                <span data-i18n="help.tab_updates">
                    Update log
                </span>
            </button>
        </div>
    </div>

    <div
        id="help-error"
        class="
            mt-4 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
        role="alert"
    ></div>

    {{-- Contact support --}}
    <section
        id="help-support-panel"
        role="tabpanel"
        aria-labelledby="help-tab-support"
        class="mt-4"
    >
        <div class="pm-card p-6">
            <p class="max-w-2xl text-sm leading-6 text-[var(--pm-text-muted)]">
                <span data-i18n="help.support_intro">
                    Tell us what you were trying to do and what happened
                    instead. Your name, your organisation and the address
                    we should answer are taken from your account, so there
                    is nothing else to fill in.
                </span>
            </p>

            <form id="help-support-form" class="mt-6 grid max-w-2xl gap-5" novalidate>
                <div
                    id="help-support-message"
                    class="hidden rounded-lg border px-4 py-3 text-sm"
                    role="alert"
                ></div>

                <div>
                    <label for="help-support-subject" class="pm-field-label">
                        <span data-i18n="help.support_subject">Subject</span>
                    </label>

                    <input
                        id="help-support-subject"
                        type="text"
                        maxlength="150"
                        required
                        autocomplete="off"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label for="help-support-body" class="pm-field-label">
                        <span data-i18n="help.support_body">Your message</span>
                    </label>

                    <textarea
                        id="help-support-body"
                        rows="8"
                        maxlength="5000"
                        required
                        class="pm-input"
                    ></textarea>

                    <p class="mt-2 text-xs text-[var(--pm-text-muted)]">
                        <span data-i18n="help.support_body_help">
                            If a message carried a code beginning PM-, include it.
                        </span>
                    </p>
                </div>

                <div>
                    <button
                        id="help-support-submit"
                        type="submit"
                        class="pm-button-primary"
                    >
                        <span data-i18n="help.support_send">Send to support</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{--
        Guide.

        V1.0.36: one guide at a time, the way the public documentation
        does it. Rendering all ten categories into one page meant every
        reader downloaded seventy tasks and their screenshots to read
        one. The category filter went with it — the search box already
        finds a task by any word in it, and a dropdown that duplicates
        the list below it is a second way of doing the same thing.
    --}}
    <section
        id="help-guide-panel"
        role="tabpanel"
        aria-labelledby="help-tab-guide"
        class="mt-4 hidden"
    >
        <div id="help-guide-index">
            <label
                for="help-search"
                class="pm-field-label"
                data-i18n="help.search"
            >Search</label>

            <input
                id="help-search"
                type="search"
                maxlength="255"
                autocomplete="off"
                class="pm-input max-w-xl"
                placeholder="Search the guide…"
                data-i18n-placeholder="help.search_placeholder"
            >

            <div id="help-guide-index-content" class="mt-6"></div>
        </div>

        <div id="help-guide-detail" class="hidden"></div>
    </section>

    {{-- Error codes --}}
    <section
        id="help-errors-panel"
        role="tabpanel"
        aria-labelledby="help-tab-errors"
        class="mt-4 hidden"
    >
        <p class="mb-4 text-sm text-[var(--pm-text-muted)]">
            <span data-i18n="errors.intro">{{ __('ui.errors.intro') }}</span>
        </p>

        <div class="mb-5">
            <label for="help-error-search" class="pm-field-label">
                <span data-i18n="errors.search_label">{{ __('ui.errors.search_label') }}</span>
            </label>

            <input
                id="help-error-search"
                type="search"
                autocomplete="off"
                class="pm-input"
                placeholder="{{ __('ui.errors.search_placeholder') }}"
                data-i18n-placeholder="errors.search_placeholder"
            >
        </div>

        <div id="help-errors-content"></div>

        <div id="help-errors-pagination" class="mt-6 hidden"></div>
    </section>

    {{-- Update log --}}
    <section
        id="help-updates-panel"
        role="tabpanel"
        aria-labelledby="help-tab-updates"
        class="mt-4 hidden"
    >
        <div id="help-updates-content">
            <div
                class="
                    px-5 py-12 text-center
                    text-sm
                    text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="help.updates_loading">
                    Loading update log…
                </span>
            </div>
        </div>
    </section>
</div>

@endsection
