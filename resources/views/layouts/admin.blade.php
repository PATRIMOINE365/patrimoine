<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>@yield('title', 'Administration — Patrimoine 365')</title>

    <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/branding/favicon/favicon-16.png">

    <x-theme-bootstrap />

    <style>
        html, body { background-color: #f5f7f6; }
        html[data-theme="dark"],
        html[data-theme="dark"] body { background-color: #0e1311; }
    </style>

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
<body class="min-h-screen bg-[var(--pm-page)] font-sans text-[var(--pm-text)]">

<div class="pm-admin-shell">

    {{-- ========================== Sidebar ========================== --}}
    <aside class="pm-admin-sidebar">

        <div class="pm-admin-brand">
            <span class="pm-admin-brand-mark">P</span>

            <span class="pm-admin-brand-name">Patrimoine&nbsp;365</span>

            <span class="pm-admin-chip">Admin</span>
        </div>

        <nav class="pm-admin-nav">
            <div class="pm-admin-nav-group">Workspace</div>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="dashboard">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
                Dashboard
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="organizations">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="8" height="14" rx="1"/><rect x="13" y="3" width="8" height="18" rx="1"/><path d="M6 11h2M6 15h2M16 7h2M16 11h2M16 15h2"/></svg>
                Organizations
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="licenses">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15h4M7 11h2"/><circle cx="16" cy="13" r="2.2"/><path d="M16 15.2V18"/></svg>
                Licenses
            </button>

            <div class="pm-admin-nav-group">Operations</div>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="activity">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
                Activity
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="settings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1a1.7 1.7 0 0 0-1.4-1.5 1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h-.1"/></svg>
                Settings
            </button>
        </nav>

        <div class="pm-admin-sidebar-footer">
            <div class="pm-admin-user">
                <span id="admin-user-avatar" class="pm-admin-user-avatar"></span>

                <span class="min-w-0">
                    <span id="admin-user-name" class="pm-admin-user-name"></span>
                    <span class="pm-admin-user-role">super admin</span>
                </span>

                <button
                    id="admin-logout"
                    type="button"
                    class="pm-icon-button shrink-0"
                    aria-label="Sign out"
                    title="Sign out"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9"/></svg>
                </button>
            </div>
        </div>

    </aside>

    {{-- =========================== Main ============================ --}}
    <div class="pm-admin-main">

        <header class="pm-admin-topbar">
            <div class="pm-admin-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>

                <input
                    id="admin-global-search"
                    type="search"
                    placeholder="Search organizations, licenses…"
                    autocomplete="off"
                >

                <kbd>Ctrl K</kbd>
            </div>

            <div class="flex items-center gap-2">
                <button
                    id="admin-theme-toggle"
                    type="button"
                    class="pm-icon-button"
                    aria-label="Toggle theme"
                    title="Toggle theme"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>

                <button
                    id="admin-assign-license"
                    type="button"
                    class="pm-button-primary"
                >
                    + Assign License
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
