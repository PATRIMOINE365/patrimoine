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
        html, body { background-color: #f2f6f4; }
        html[data-theme="dark"],
        html[data-theme="dark"] body { background-color: #0e1614; }
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
                <x-logo :size="36" />

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

        {{--
            The catalogue is long enough to page through. This page carries
            no JavaScript bundle by design, so the control below is drawn by
            the inline script at the foot of the document, using the same
            classes as the rest of Patrimoine and the words handed to it
            here.
        --}}
        <div
            id="error-pagination"
            class="mt-10 hidden"
            data-summary="{{ __('ui.pagination.summary', ['from' => ':from', 'to' => ':to', 'total' => ':total']) }}"
            data-rows-per-page="{{ __('ui.pagination.rows_per_page') }}"
            data-navigation="{{ __('ui.pagination.navigation') }}"
            data-previous="{{ __('ui.pagination.previous') }}"
            data-next="{{ __('ui.pagination.next') }}"
            data-go-to-page="{{ __('ui.pagination.go_to_page', ['page' => ':page']) }}"
            data-current-page="{{ __('ui.pagination.current_page', ['page' => ':page']) }}"
        ></div>

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

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        /*
         * Filtering and paging both happen here rather than on the server:
         * the whole catalogue is already on the page, and somebody hunting
         * a code should not wait for a round trip to find it.
         *
         * A card is on screen when it matches the search AND falls inside
         * the page being read. Searching returns to the first page, because
         * the match a reader is looking for is otherwise three pages away
         * with nothing to say so.
         */
        (function () {
            var input = document.getElementById('error-search');
            var empty = document.getElementById('error-search-empty');
            var control = document.getElementById('error-pagination');

            if (! input) { return; }

            var SIZES = [25, 50, 100];
            var STORAGE = 'pm.rows.error-codes';

            var cards = [].slice.call(
                document.querySelectorAll('[data-error-code]')
            );

            var page = 1;
            var perPage = SIZES[0];

            try {
                var stored = Number(window.localStorage.getItem(STORAGE));

                if (SIZES.indexOf(stored) !== -1) { perPage = stored; }
            } catch (error) {
                // A browser refusing storage simply gets the default.
            }

            /*
             * Longest name first: ':to' is a prefix of ':total', and
             * replacing in declaration order turned "of :total" into
             * "of 25tal".
             */
            function words(name, replacements) {
                var text = control ? (control.dataset[name] || '') : '';

                Object.keys(replacements || {})
                    .sort(function (a, b) { return b.length - a.length; })
                    .forEach(function (key) {
                        text = text.split(':' + key).join(replacements[key]);
                    });

                return text;
            }

            function draw() {
                var needle = input.value.trim().toLowerCase();

                var matching = cards.filter(function (card) {
                    var haystack = card.dataset.errorHaystack || '';

                    return needle === '' || haystack.indexOf(needle) !== -1;
                });

                var lastPage = Math.max(1, Math.ceil(matching.length / perPage));

                if (page > lastPage) { page = lastPage; }

                var start = (page - 1) * perPage;
                var shown = matching.slice(start, start + perPage);

                cards.forEach(function (card) {
                    card.classList.toggle('hidden', shown.indexOf(card) === -1);
                });

                document.querySelectorAll('[data-error-family]').forEach(function (family) {
                    var visible = family.querySelectorAll('[data-error-code]:not(.hidden)').length;

                    family.classList.toggle('hidden', visible === 0);
                });

                empty.classList.toggle('hidden', matching.length > 0);

                drawControl(matching.length, lastPage, start, shown.length);
            }

            function pageNumbers(lastPage) {
                var wanted = [];

                [1, page - 1, page, page + 1, lastPage].forEach(function (number) {
                    if (
                        number >= 1
                        && number <= lastPage
                        && wanted.indexOf(number) === -1
                    ) {
                        wanted.push(number);
                    }
                });

                wanted.sort(function (a, b) { return a - b; });

                var drawn = [];

                wanted.forEach(function (number, index) {
                    if (index > 0 && number - wanted[index - 1] > 1) {
                        drawn.push(null);
                    }

                    drawn.push(number);
                });

                return drawn;
            }

            function drawControl(total, lastPage, start, count) {
                if (! control) { return; }

                if (total <= SIZES[0] && lastPage <= 1) {
                    control.innerHTML = '';
                    control.classList.add('hidden');

                    return;
                }

                control.classList.remove('hidden');

                var sizes = SIZES.map(function (size) {
                    return '<option value="' + size + '"'
                        + (size === perPage ? ' selected' : '')
                        + '>' + size + '</option>';
                }).join('');

                var numbers = lastPage <= 1 ? '' : pageNumbers(lastPage).map(function (number) {
                    if (number === null) {
                        return '<span class="pm-pagination-gap" aria-hidden="true">&hellip;</span>';
                    }

                    var current = number === page;

                    return '<button type="button" data-page="' + number + '"'
                        + ' aria-label="' + words(
                            current ? 'currentPage' : 'goToPage',
                            { page: number }
                        ) + '"'
                        + (current ? ' aria-current="page"' : '')
                        + ' class="pm-pagination-page' + (current ? ' is-current' : '') + '">'
                        + number + '</button>';
                }).join('');

                var steps = lastPage <= 1 ? '' : (
                    '<button type="button" data-step="-1"'
                    + (page <= 1 ? ' disabled' : '')
                    + ' aria-label="' + words('previous', {}) + '"'
                    + ' class="pm-pagination-step">&lsaquo;</button>'
                    + numbers
                    + '<button type="button" data-step="1"'
                    + (page >= lastPage ? ' disabled' : '')
                    + ' aria-label="' + words('next', {}) + '"'
                    + ' class="pm-pagination-step">&rsaquo;</button>'
                );

                control.innerHTML =
                    '<div class="pm-pagination">'
                    + '<p class="pm-pagination-summary">'
                    + words('summary', {
                        from: total === 0 ? 0 : start + 1,
                        to: start + count,
                        total: total,
                    })
                    + '</p>'
                    + '<div class="pm-pagination-controls">'
                    + '<label class="pm-pagination-size"><span>'
                    + words('rowsPerPage', {})
                    + '</span><select class="pm-input" data-size>' + sizes + '</select></label>'
                    + '<nav class="pm-pagination-pages" aria-label="'
                    + words('navigation', {}) + '">' + steps + '</nav>'
                    + '</div></div>';

                control.querySelectorAll('[data-page]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        page = Number(button.dataset.page);

                        draw();
                    });
                });

                control.querySelectorAll('[data-step]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        page += Number(button.dataset.step);

                        draw();
                    });
                });

                control.querySelector('[data-size]')
                    .addEventListener('change', function (event) {
                        perPage = Number(event.target.value);
                        page = 1;

                        try {
                            window.localStorage.setItem(STORAGE, String(perPage));
                        } catch (error) {
                            // Nothing to do; the choice simply is not kept.
                        }

                        draw();
                    });
            }

            input.addEventListener('input', function () {
                page = 1;

                draw();
            });

            draw();
        })();
    </script>

</body>
</html>
