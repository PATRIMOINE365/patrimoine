<!DOCTYPE html>
<html lang="{{ $language }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ __('ui.errors.title') }} — {{ config('legal.product.name') }}</title>

    <meta name="description" content="{{ __('ui.errors.intro') }}">

    <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/branding/favicon/favicon-16.png">

    <link rel="alternate" hreflang="en" href="{{ url('/errors') }}?lang=en">
    <link rel="alternate" hreflang="fr" href="{{ url('/errors') }}?lang=fr">

    <x-theme-bootstrap />

    <style>
        html, body { background-color: #f5f7f6; }
        html[data-theme="dark"],
        html[data-theme="dark"] body { background-color: #0e1311; }
    </style>

    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-[var(--pm-page)] font-sans text-[var(--pm-text)]">

    {{--
        Public on purpose: somebody locked out of Patrimoine is exactly
        the person who needs to look up the code in front of them.
    --}}

    <header class="border-b border-[var(--pm-border)] bg-[var(--pm-surface)]">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-6 py-5">
            <a href="/login" class="flex items-center gap-3">
                <span
                    class="
                        flex h-9 w-9 items-center justify-center
                        rounded-xl bg-patrimoine-950 font-semibold text-white
                    "
                >P</span>

                <span class="text-lg font-semibold">
                    {{ config('legal.product.name') }}
                </span>
            </a>

            <a
                href="{{ url('/errors') }}?lang={{ $language === 'en' ? 'fr' : 'en' }}"
                class="
                    rounded-lg border border-[var(--pm-border)]
                    px-3 py-1.5 text-sm font-medium
                "
            >{{ $language === 'en' ? 'FR' : 'EN' }}</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-10">

        <h1 class="text-3xl font-semibold tracking-tight">
            {{ __('ui.errors.heading') }}
        </h1>

        <p class="mt-3 text-sm text-[var(--pm-text-muted)]">
            {{ __('ui.errors.intro') }}
        </p>

        {{-- A code somebody typed or followed a link to --}}
        @if ($code && ! $known)
            <div
                class="
                    mt-8 rounded-xl border border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)] px-5 py-4
                    text-sm text-[var(--pm-danger-text)]
                "
            >
                <p class="font-semibold">{{ $code }}</p>
                <p class="mt-1">{{ __('ui.errors.unknown_code') }}</p>
            </div>
        @endif

        @if ($focused)
            <section class="mt-8" id="focused">
                <x-error-code-card :entry="$focused" :contact="$contact" :focused="true" />

                <p class="mt-4 text-sm">
                    <a class="underline" href="{{ url('/errors') }}?lang={{ $language }}">
                        {{ __('ui.errors.back_to_all') }}
                    </a>
                </p>
            </section>
        @endif

        {{-- Search --}}
        <div class="mt-10">
            <label for="error-search" class="pm-field-label">
                {{ __('ui.errors.search_label') }}
            </label>

            <input
                id="error-search"
                type="search"
                autocomplete="off"
                class="pm-input"
                placeholder="{{ __('ui.errors.search_placeholder') }}"
            >

            <p
                id="error-search-empty"
                class="mt-3 hidden text-sm text-[var(--pm-text-muted)]"
            >{{ __('ui.errors.no_matches') }}</p>
        </div>

        {{-- The catalogue --}}
        @foreach ($families as $family => $group)
            <section class="mt-10" data-error-family>
                <h2
                    class="
                        border-b border-[var(--pm-border-subtle)] pb-2
                        text-lg font-semibold
                    "
                >
                    {{ $group['name'] }}
                    <span class="text-sm font-normal text-[var(--pm-text-muted)]">
                        · {{ $family }}xxx
                    </span>
                </h2>

                <div class="mt-4 grid gap-4">
                    @foreach ($group['codes'] as $entry)
                        <x-error-code-card :entry="$entry" :contact="$contact" />
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- Reaching a person --}}
        <section
            class="
                mt-12 rounded-2xl border border-[var(--pm-border)]
                bg-[var(--pm-surface)] p-6
            "
        >
            <h2 class="text-lg font-semibold">
                {{ __('ui.errors.contact_heading') }}
            </h2>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                {{ __('ui.errors.contact_intro') }}
            </p>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-[var(--pm-text-muted)]">{{ __('ui.errors.contact_phone') }}</dt>
                    <dd class="mt-0.5 font-medium">
                        <a class="underline" href="tel:{{ $contact['phone'] }}">
                            {{ config('legal.support.phone_display') }}
                        </a>
                    </dd>
                </div>

                <div>
                    <dt class="text-[var(--pm-text-muted)]">{{ __('ui.errors.contact_whatsapp') }}</dt>
                    <dd class="mt-0.5 font-medium">
                        <a
                            class="underline"
                            rel="noopener"
                            target="_blank"
                            href="https://wa.me/{{ ltrim($contact['whatsapp'], '+') }}"
                        >{{ config('legal.support.phone_display') }}</a>
                    </dd>
                </div>

                <div>
                    <dt class="text-[var(--pm-text-muted)]">{{ __('ui.errors.contact_email') }}</dt>
                    <dd class="mt-0.5 font-medium break-all">
                        <a class="underline" href="mailto:{{ $contact['email'] }}">
                            {{ $contact['email'] }}
                        </a>
                    </dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-[var(--pm-text-muted)]">
                {{ __('ui.errors.contact_hint') }}
            </p>
        </section>

    </main>

    <footer class="border-t border-[var(--pm-border)]">
        <div class="mx-auto max-w-3xl px-6 py-8 text-sm text-[var(--pm-text-muted)]">
            <p>
                {{ config('legal.product.name') }} ·
                {{ config('legal.company.name') }} ·
                <a class="underline" href="/terms">{{ __('ui.errors.title') }}</a>
            </p>
        </div>
    </footer>

    <script>
        /*
         * Filtering happens here rather than on the server: the whole
         * catalogue is already on the page, and somebody hunting a code
         * should not wait for a round trip to find it.
         */
        (function () {
            var input = document.getElementById('error-search');
            var empty = document.getElementById('error-search-empty');

            if (! input) { return; }

            input.addEventListener('input', function () {
                var needle = input.value.trim().toLowerCase();
                var anyVisible = false;

                document.querySelectorAll('[data-error-code]').forEach(function (card) {
                    var haystack = card.dataset.errorHaystack || '';
                    var match = needle === '' || haystack.indexOf(needle) !== -1;

                    card.classList.toggle('hidden', ! match);

                    if (match) { anyVisible = true; }
                });

                document.querySelectorAll('[data-error-family]').forEach(function (family) {
                    var visible = family.querySelectorAll('[data-error-code]:not(.hidden)').length;
                    family.classList.toggle('hidden', visible === 0);
                });

                empty.classList.toggle('hidden', anyVisible);
            });
        })();
    </script>

</body>
</html>
