@props([
    'entry',
    'contact' => [],
    'focused' => false,
])

{{--
    One error, as somebody stuck reads it: the code they are staring at,
    the sentence they saw, what it means, and what to do next.

    The support details appear only on the codes nobody but us can
    resolve. Repeating them everywhere would teach people to ignore them.
--}}

@php
    $severityLabel = __('ui.errors.severity_'.$entry['severity']);

    $haystack = mb_strtolower(
        $entry['code'].' '.$entry['title'].' '.$entry['what'].' '.$entry['fix']
    );
@endphp

<article
    id="{{ $entry['code'] }}"
    data-error-code="{{ $entry['code'] }}"
    data-error-haystack="{{ $haystack }}"
    @class([
        'rounded-xl border p-5',
        'border-[var(--pm-border)] bg-[var(--pm-surface)]' => ! $focused,
        'border-[var(--pm-accent)] bg-[var(--pm-surface)] ring-1 ring-[var(--pm-accent)]' => $focused,
    ])
>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="text-base font-semibold">
            {{ $entry['title'] }}
        </h3>

        <a
            href="{{ url('/errors/'.$entry['code']) }}"
            class="
                shrink-0 rounded-full border border-[var(--pm-border)]
                px-2.5 py-1 font-mono text-xs font-semibold
                text-[var(--pm-text-secondary)]
            "
        >{{ $entry['code'] }}</a>
    </div>

    <p class="mt-1 text-xs uppercase tracking-wide text-[var(--pm-text-muted)]">
        {{ $severityLabel }}
    </p>

    <div class="mt-3 grid gap-3 text-sm">
        <div>
            <p class="font-medium text-[var(--pm-text-secondary)]">
                {{ __('ui.errors.what_happened') }}
            </p>
            <p class="mt-0.5 text-[var(--pm-text-muted)]">{{ $entry['what'] }}</p>
        </div>

        <div>
            <p class="font-medium text-[var(--pm-text-secondary)]">
                {{ __('ui.errors.what_to_do') }}
            </p>
            <p class="mt-0.5 text-[var(--pm-text-muted)]">{{ $entry['fix'] }}</p>
        </div>
    </div>

    @if ($entry['needs_support'] && $contact !== [])
        <div
            class="
                mt-4 rounded-lg border border-[var(--pm-border-subtle)]
                bg-[var(--pm-surface-subtle)] px-4 py-3 text-sm
            "
        >
            <a class="underline" href="tel:{{ $contact['phone'] }}">
                {{ config('legal.support.phone_display') }}
            </a>
            <span class="text-[var(--pm-text-muted)]"> · </span>
            <a
                class="underline"
                rel="noopener"
                target="_blank"
                href="https://wa.me/{{ ltrim($contact['whatsapp'], '+') }}"
            >WhatsApp</a>
            <span class="text-[var(--pm-text-muted)]"> · </span>
            <a class="underline break-all" href="mailto:{{ $contact['email'] }}">
                {{ $contact['email'] }}
            </a>
        </div>
    @endif
</article>
