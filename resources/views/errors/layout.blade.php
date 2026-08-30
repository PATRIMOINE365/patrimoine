@php
    use App\Support\ErrorCodes;

    /*
     * These pages appear when part of Patrimoine is not working, so they
     * lean on as little of it as possible: no API call, no JavaScript, no
     * session. Everything shown is read straight from the catalogue.
     */
    $language = in_array(request()->cookie('patrimoine_language'), ['en', 'fr'], true)
        ? request()->cookie('patrimoine_language')
        : app()->getLocale();

    $text = ErrorCodes::text($code, $language) ?? [
        'title' => $code,
        'what' => '',
        'fix' => '',
    ];

    $contact = ErrorCodes::contact();
    $showContact = ErrorCodes::severity($code) === 'contact_us';
@endphp

<!DOCTYPE html>
<html lang="{{ $language }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $text['title'] }} — {{ config('legal.product.name') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon/favicon-32.png">

    <style>
        :root { color-scheme: light dark; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: #f2f6f4;
            color: #17201e;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        @media (prefers-color-scheme: dark) {
            body { background: #0e1614; color: #f2f6f4; }
            /* White pillars and Mint bars on a dark ground, per the brand. */
            .mark rect:nth-child(2), .mark rect:nth-child(3) { fill: #ffffff; }
            .mark rect:nth-child(4), .mark rect:nth-child(5), .mark rect:nth-child(6) { fill: #39D6A3; }
            .button { background: #0e7a56 !important; }
            .card { background: #17201e !important; border-color: #2c3936 !important; }
            .muted { color: #9fada8 !important; }
            .badge { border-color: #2c3936 !important; color: #9fada8 !important; }
            .contact { background: #1b2523 !important; border-color: #2c3936 !important; }
        }

        .card {
            width: 100%;
            max-width: 34rem;
            background: #ffffff;
            border: 1px solid #dde6e2;
            border-radius: 1rem;
            padding: 2rem;
        }

        /*
         * The mark is drawn inline rather than linked: these pages have to
         * render when the application cannot, and an <img> that 404s would
         * leave a broken picture at the top of an apology.
         */
        .mark { display: block; }

        h1 { font-size: 1.5rem; margin: 1.25rem 0 0; letter-spacing: -0.01em; }
        p { margin: 0.75rem 0 0; }
        .muted { color: #66736f; font-size: 1rem; }

        .badge {
            display: inline-block;
            margin-top: 1.25rem;
            border: 1px solid #dde6e2;
            border-radius: 9999px;
            padding: 0.25rem 0.625rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            color: inherit;
        }

        .contact {
            margin-top: 1.5rem;
            border: 1px solid #dde6e2;
            border-radius: 0.75rem;
            background: #f2f6f4;
            padding: 1rem;
            font-size: 0.875rem;
        }

        .actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem; }

        .button {
            display: inline-block;
            border-radius: 0.625rem;
            background: #123d35;
            color: #ffffff;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
        }

        .button-secondary {
            background: transparent;
            border: 1px solid #dde6e2;
            color: inherit;
        }

        a { color: inherit; }
    </style>
</head>

<body>
    <main class="card">
        <svg class="mark" width="36" height="36" viewBox="0 0 64 64" fill="none" role="img" aria-label="Patrimoine 365">
            <title>Patrimoine 365</title>
            <rect x="2"  y="4"  width="10" height="56" rx="2" fill="#123D35"/>
            <rect x="52" y="4"  width="10" height="56" rx="2" fill="#123D35"/>
            <rect x="18" y="9"  width="28" height="10" rx="2" fill="#0E7A56"/>
            <rect x="18" y="27" width="28" height="10" rx="2" fill="#0E7A56"/>
            <rect x="18" y="45" width="28" height="10" rx="2" fill="#0E7A56"/>
        </svg>

        <h1>{{ $text['title'] }}</h1>

        @if ($text['what'] !== '')
            <p class="muted">{{ $text['what'] }}</p>
        @endif

        @if ($text['fix'] !== '')
            <p class="muted">{{ $text['fix'] }}</p>
        @endif

        @if ($showContact)
            <div class="contact">
                <a href="tel:{{ $contact['phone'] }}">{{ config('legal.support.phone_display') }}</a>
                ·
                <a rel="noopener" target="_blank" href="https://wa.me/{{ ltrim($contact['whatsapp'], '+') }}">WhatsApp</a>
                ·
                <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
            </div>
        @endif

        <div class="actions">
            <a class="button" href="/dashboard">{{ __('ui.errors.back_to_app', [], $language) }}</a>
            <a class="button button-secondary" href="/errors/{{ $code }}">{{ __('ui.errors.explain_code', [], $language) }}</a>
        </div>

        <a class="badge" href="/errors/{{ $code }}">{{ $code }}</a>
    </main>
</body>
</html>
