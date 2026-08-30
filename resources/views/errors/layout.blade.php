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
            color: #101917;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        @media (prefers-color-scheme: dark) {
            body { background: #0e1614; color: #f2f6f4; }
            .card { background: #161c19 !important; border-color: #26302b !important; }
            .muted { color: #97a69d !important; }
            .badge { border-color: #26302b !important; color: #97a69d !important; }
            .contact { background: #1b221e !important; border-color: #26302b !important; }
        }

        .card {
            width: 100%;
            max-width: 34rem;
            background: #ffffff;
            border: 1px solid #dfe6e2;
            border-radius: 1rem;
            padding: 2rem;
        }

        .mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            background: #0f2e21;
            color: #ffffff;
            font-weight: 600;
        }

        h1 { font-size: 1.5rem; margin: 1.25rem 0 0; letter-spacing: -0.01em; }
        p { margin: 0.75rem 0 0; }
        .muted { color: #5c6b64; font-size: 0.9375rem; }

        .badge {
            display: inline-block;
            margin-top: 1.25rem;
            border: 1px solid #dfe6e2;
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
            border: 1px solid #dfe6e2;
            border-radius: 0.75rem;
            background: #f2f6f4;
            padding: 1rem;
            font-size: 0.875rem;
        }

        .actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem; }

        .button {
            display: inline-block;
            border-radius: 0.625rem;
            background: #0f2e21;
            color: #ffffff;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
        }

        .button-secondary {
            background: transparent;
            border: 1px solid #dfe6e2;
            color: inherit;
        }

        a { color: inherit; }
    </style>
</head>

<body>
    <main class="card">
        <span class="mark">P</span>

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
