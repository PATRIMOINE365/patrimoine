<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>@yield('title', 'Administration — Patrimoine 365')</title>

    {{--
        The platform console wears the same mark as the customer app, in a
        dark red rather than the brand green, so an admin tab is
        identifiable at a glance among a row of Patrimoine tabs. The icons
        set is the customer mark with its hue rotated to red and its
        saturation and lightness left alone -- Patrimoine Green #123D35
        becomes #3D1212 and Mint #39D6A3 becomes #D63939, so the two read
        as one mark in two colours rather than as two marks. Both sets are
        drawn by scripts/generate-favicons.mjs. This is the only place the
        red set is referenced.
    --}}
    <link rel="icon" sizes="48x48" href="/branding/favicon/favicon-admin.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon/favicon-admin-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/branding/favicon/favicon-admin-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/branding/favicon/apple-touch-icon-admin.png">
    <link rel="manifest" href="/branding/admin.webmanifest">
    <meta name="theme-color" content="#3d1212">

    <x-theme-bootstrap />

    {{--
        Inter used to come from fonts.googleapis.com here, which sent every
        visitor's IP address to Google before the first paint — in a product
        whose privacy policy tells customers there are no third parties in
        the page. It is served from our own origin now, by resources/css/
        fonts.css, along with the rest of the application.

        The console also used to paint its own background colour inline
        because it carried its own copy of the Untitled UI palette. It reads
        the same tokens as everything else now, so there is nothing to paint.
    --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

{{--
    V1.0.11 platform administration shell.

    Deliberately NOT the customer application layout: platform staff
    never manage parties, leases or units directly, so the sidebar
    carries only the console. Styling stays on the central --pm-*
    tokens so the whole console recolours with the design system.

    No data-auth-required here: admin.js performs its own
    authentication bootstrap and redirects non-staff away.
--}}
<body class="pm-admin min-h-screen bg-[var(--pm-page)] font-sans text-[var(--pm-text)]">

<div class="pm-admin-shell">

    {{-- ========================== Sidebar ========================== --}}
    <aside class="pm-admin-sidebar">

        <div class="pm-admin-brand">
            <x-logo :size="32" class="pm-admin-brand-logo" />

            <span class="pm-admin-brand-name">Patrimoine&nbsp;<span class="pm-admin-brand-365">365</span></span>

            <span class="pm-admin-chip">Admin</span>
        </div>

        <nav class="pm-admin-nav">
            <div class="pm-admin-nav-group">Workspace</div>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="dashboard">
                <x-icon name="grid-01" />
                Dashboard
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="users">
                <x-icon name="users-01" />
                Users
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="organizations">
                <x-icon name="building-07" />
                Organizations
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="licenses">
                <x-icon name="award-01" />
                Licenses
            </button>

            <div class="pm-admin-nav-group">Operations</div>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="emails">
                <x-icon name="mail-01" />
                Emails
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="activity">
                <x-icon name="activity" />
                Activity
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="releases">
                <x-icon name="package" />
                Release log
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="settings">
                <x-icon name="settings-01" />
                Settings
            </button>
        </nav>

        <div class="pm-admin-sidebar-footer">
            <div class="pm-admin-user">
                <button
                    id="admin-user-button"
                    type="button"
                    class="pm-admin-user-open"
                    title="My profile"
                >
                    <span id="admin-user-avatar" class="pm-admin-user-avatar">
                        <img id="admin-user-avatar-img" alt="" class="hidden h-full w-full rounded-full object-cover">
                        <span id="admin-user-avatar-initials"></span>
                    </span>

                    <span class="min-w-0 text-left">
                        <span id="admin-user-name" class="pm-admin-user-name"></span>
                        <span class="pm-admin-user-role">super admin</span>
                    </span>
                </button>

                <button
                    id="admin-logout"
                    type="button"
                    class="pm-icon-button shrink-0"
                    aria-label="Sign out"
                    title="Sign out"
                >
                    <x-icon name="log-out-01" />
                </button>
            </div>
        </div>

        {{-- Brand footer: same message as the marketing site --}}
        <div class="pm-admin-brand-footer">
            <p>Property management, minus the drama.</p>
            <p>&copy; 2026 Patrimoine 365. All rights reserved.</p>
        </div>

    </aside>

    {{-- =========================== Main ============================ --}}
    <div class="pm-admin-main">

        <header class="pm-admin-topbar">
            <div class="pm-admin-search">
                <x-icon name="search-lg" class="shrink-0" />

                <input
                    id="admin-global-search"
                    type="search"
                    placeholder="Search organizations, licenses…"
                    autocomplete="off"
                >

                <kbd>&#8984;K</kbd>
            </div>

            <div class="flex items-center gap-2">
                <button
                    id="admin-theme-toggle"
                    type="button"
                    class="pm-icon-button"
                    aria-label="Toggle theme"
                    title="Toggle theme"
                >
                    <x-icon name="moon" data-theme-icon="moon" />
                </button>

                <button
                    id="admin-assign-license"
                    type="button"
                    class="pm-button-primary"
                >
                    <x-icon name="plus" />
                    Assign License
                </button>
            </div>
        </header>

        <main class="pm-admin-content">
            @yield('content')
        </main>

    </div>

</div>

@yield('drawers')

</body>
</html>
