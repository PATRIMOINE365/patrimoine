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
    <link rel="apple-touch-icon" sizes="180x180" href="/branding/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/branding/site.webmanifest">
    <meta name="theme-color" content="#26744b">

    <x-theme-bootstrap />

    {{-- Untitled UI's typeface. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        html, body { background-color: #f9fafb; }
        html[data-theme="dark"],
        html[data-theme="dark"] body { background-color: #0c111d; }
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
<body class="pm-admin min-h-screen bg-[var(--pm-page)] font-sans text-[var(--pm-text)]">

<div class="pm-admin-shell">

    {{-- ========================== Sidebar ========================== --}}
    <aside class="pm-admin-sidebar">

        <div class="pm-admin-brand">
            <img
                src="/branding/patrimoine-logo.svg"
                alt="Patrimoine 365"
                class="pm-admin-brand-logo"
            >

            <span class="pm-admin-brand-name">Patrimoine&nbsp;<span class="pm-admin-brand-365">365</span></span>

            <span class="pm-admin-chip">Admin</span>
        </div>

        <nav class="pm-admin-nav">
            <div class="pm-admin-nav-group">Workspace</div>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="dashboard">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 15v2M12 11v6M16 7v10"/><rect x="3" y="3" width="18" height="18" rx="4"/></svg>
                Dashboard
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="users">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/><path d="M15 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="organizations">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.5 11h-2c-.93 0-1.4 0-1.77.15a2 2 0 0 0-1.08 1.08C2.5 12.6 2.5 13.07 2.5 14v7h19v-7c0-.93 0-1.4-.15-1.77a2 2 0 0 0-1.08-1.08C19.9 11 19.43 11 18.5 11h-2"/><path d="M7.5 21V6.2c0-1.12 0-1.68.22-2.11a2 2 0 0 1 .87-.87C9.02 3 9.58 3 10.7 3h2.6c1.12 0 1.68 0 2.11.22a2 2 0 0 1 .87.87c.22.43.22.99.22 2.11V21"/><path d="M11 7h2M11 11h2M11 15h2"/></svg>
                Organizations
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="licenses">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18.5H6.2c-1.12 0-1.68 0-2.11-.22a2 2 0 0 1-.87-.87C3 16.98 3 16.42 3 15.3V6.7c0-1.12 0-1.68.22-2.11a2 2 0 0 1 .87-.87C4.52 3.5 5.08 3.5 6.2 3.5h11.6c1.12 0 1.68 0 2.11.22a2 2 0 0 1 .87.87c.22.43.22.99.22 2.11v5.3"/><path d="M6.5 12h3M6.5 8.5h6"/><circle cx="18" cy="15.5" r="2.5"/><path d="M16.5 17.5l-.5 4 2-1.2 2 1.2-.5-4"/></svg>
                Licenses
            </button>

            <div class="pm-admin-nav-group">Operations</div>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="activity">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                Activity
            </button>

            <button type="button" class="pm-admin-nav-item" data-admin-nav="settings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M18.73 14.6a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V20.6a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H2.4a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1.08 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.08a1.65 1.65 0 0 0 1-1.51V2.4a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.08a1.65 1.65 0 0 0 1.51 1h.17a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4h2"/></svg>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>

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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>

                <button
                    id="admin-assign-license"
                    type="button"
                    class="pm-button-primary"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M12 5v14M5 12h14"/></svg>
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
