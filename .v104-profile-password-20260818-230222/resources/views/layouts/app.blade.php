<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title
        @hasSection('title-i18n')
            data-i18n-title="@yield('title-i18n')"
        @endif
    >
        @yield('title', 'Patrimoine')
    </title>

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="/branding/favicon/favicon-32.png"
    >
    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="/branding/favicon/favicon-16.png"
    >
    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="/branding/favicon/apple-touch-icon.png"
    >

    <script>
        (() => {
            try {
                const role =
                    window.sessionStorage.getItem(
                        'patrimoine.user_role'
                    );

                if (
                    role === 'administrator'
                    || role === 'property_manager'
                    || role === 'viewer'
                ) {
                    document.documentElement.dataset.shellRole =
                        role;
                }
            } catch (error) {
                /*
                 * Storage restrictions must never prevent page rendering.
                 */
            }
        })();
    </script>

    <x-presentation-language-bootstrap />

    <script>
        (() => {
            try {
                const cachedUser =
                    JSON.parse(
                        window.sessionStorage.getItem(
                            'patrimoine.current_user'
                        )
                        || 'null'
                    );

                if (
                    cachedUser
                    && typeof cachedUser === 'object'
                ) {
                    document.documentElement.dataset
                        .cachedUserName =
                            String(
                                cachedUser.name
                                || ''
                            );

                    document.documentElement.dataset
                        .cachedUserRole =
                            String(
                                cachedUser.role
                                || ''
                            );
                }
            } catch (error) {
                /*
                 * Cached presentation identity is optional.
                 */
            }
        })();
    </script>

    <x-theme-bootstrap />

    {{--
        V1.0.4 Initial Paint Theme

        The theme bootstrap above resolves data-theme synchronously before
        Vite loads. Give the browser canvas the matching Patrimoine page
        colour immediately so full-page navigation never exposes the
        browser's default white background between documents.

        These values intentionally mirror --pm-page in app.css. Runtime
        styling remains controlled by the semantic theme variables.
    --}}
    <style>
        html,
        body {
            background-color: #f7f8f7;
        }

        html[data-theme="dark"],
        html[data-theme="dark"] body {
            background-color: #0f1412;
        }
    </style>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body
    class="
        min-h-screen bg-[var(--pm-page)]
        font-sans text-[var(--pm-text)]
    "
    data-auth-required="true"
>

    {{-- Mobile overlay --}}
    <div
        id="sidebar-overlay"
        class="
            fixed inset-0 z-40 hidden
            bg-slate-950/40
            backdrop-blur-[1px]
            lg:hidden
        "
    ></div>

    {{-- Sidebar --}}
    <aside
        id="sidebar"
        class="
            fixed inset-y-0 left-0 z-50
            flex w-72 -translate-x-full
            flex-col bg-patrimoine-950
            transition-transform duration-200
            lg:translate-x-0
        "
    >
        <div
            class="
                flex h-20 items-center
                border-b border-white/10
                px-6
            "
        >
            <a
                href="/dashboard"
                class="flex items-center gap-3"
            >
                <div
                    class="
                        flex h-10 w-10 shrink-0
                        items-center justify-center
                        overflow-hidden rounded-xl bg-white
                    "
                >
                    <img
                        src="/branding/patrimoine-logo.svg"
                        alt="Patrimoine"
                        class="h-9 w-9 object-contain"
                    >
                </div>

                <div>
                    <div
                        class="
                            text-base font-semibold
                            tracking-tight text-white
                        "
                    >
                        Patrimoine
                    </div>

                    <div
                        class="
                            mt-0.5 text-[11px]
                            text-patrimoine-300
                        "
                    >
                        <span data-i18n="product.property_management">
                            {{ __('ui.product.property_management') }}
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <nav
            class="
                flex-1 overflow-y-auto
                px-4 py-6
            "
        >
            <p
                class="
                    mb-3 px-3
                    text-[10px] font-semibold uppercase
                    tracking-[0.16em]
                    text-patrimoine-400
                "
            >
                <span data-i18n="navigation.workspace">{{ __('ui.navigation.workspace') }}</span>
            </p>

            <div class="space-y-1">

                <a
                    href="/dashboard"
                    class="
                        {{ request()->is('dashboard')
                            ? 'bg-white/10 text-white'
                            : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>

                    <span data-i18n="navigation.dashboard">{{ __('ui.navigation.dashboard') }}</span>
                </a>

                <a
                    href="/properties"
                    class="
                        {{ request()->is('properties')
                            ? 'bg-white/10 text-white'
                            : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M3 21h18"/>
                        <path d="M6 21V5l6-2 6 2v16"/>
                        <path d="M9 9h.01"/>
                        <path d="M15 9h.01"/>
                        <path d="M9 13h.01"/>
                        <path d="M15 13h.01"/>
                    </svg>

                    <span data-i18n="navigation.properties">{{ __('ui.navigation.properties') }}</span>
                </a>

                <a
                    href="/parties"
                    class="
                        {{ request()->is('parties')
                            ? 'bg-white/10 text-white'
                            : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>

                    <span data-i18n="navigation.parties">{{ __('ui.navigation.parties') }}</span>
                </a>


                <a
                    href="/leases"
                    class="
                        {{ request()->is('leases')
                            ? 'bg-white/10 text-white'
                            : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/>
                        <line x1="8" y1="17" x2="16" y2="17"/>
                    </svg>

                    <span data-i18n="navigation.leases">{{ __('ui.navigation.leases') }}</span>
                </a>




                <a
                    href="/payments"
                    class="
                        {{
                            request()->is('payments')
                                ? 'bg-white/10 text-white'
                                : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>

                    <span data-i18n="navigation.payments">{{ __('ui.navigation.payments') }}</span>
                </a>
            </div>

            <p
                class="
                    mb-3 mt-8 px-3
                    text-[10px] font-semibold uppercase
                    tracking-[0.16em]
                    text-patrimoine-400
                "
            >
                <span data-i18n="navigation.finance">{{ __('ui.navigation.finance') }}</span>
            </p>







            <div class="space-y-1">

                <a
                    href="/tenants"
                    class="
                        {{
                            request()->is('tenants')
                                ? 'bg-white/10 text-white'
                                : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                        <path d="M17 11h4"/>
                    </svg>

                    <span data-i18n="navigation.tenants">{{ __('ui.navigation.tenants') }}</span>
                </a>


                <a
                    href="/owners"
                    class="
                        {{
                            request()->is('owners')
                                ? 'bg-white/10 text-white'
                                : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                        <path d="M17 8h4"/>
                        <path d="M19 6v4"/>
                    </svg>

                    <span data-i18n="navigation.owners">{{ __('ui.navigation.owners') }}</span>
                </a>


                <a
                    href="/reports"
                    class="
                        {{
                            request()->is('reports')
                                ? 'bg-white/10 text-white'
                                : 'text-patrimoine-200 hover:bg-white/5 hover:text-white'
                        }}
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        transition
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M3 3v18h18"/>
                        <path d="m7 16 4-5 4 3 5-7"/>
                    </svg>

                    <span data-i18n="navigation.reports">{{ __('ui.navigation.reports') }}</span>
                </a>












            </div>
        </nav>

        {{-- Administrator management menu --}}
        <div
            data-requires-capability="view_activity_log"
            class="
                rbac-hidden shell-admin-only
                relative
                border-t border-white/10
                p-4
            "
        >
            <div
                id="sidebar-manage-menu"
                class="
                    pm-sidebar-manage-menu
                    hidden
                "
            >
                <a
                    href="/activity-log"
                    data-requires-capability="view_activity_log"
                    class="pm-sidebar-manage-item"
                >
                    <div class="pm-sidebar-manage-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M9 5h10"/>
                            <path d="M9 12h10"/>
                            <path d="M9 19h10"/>
                            <circle cx="5" cy="5" r="1"/>
                            <circle cx="5" cy="12" r="1"/>
                            <circle cx="5" cy="19" r="1"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="pm-sidebar-manage-title"
                            data-i18n="navigation.activity_log"
                        >
                            {{ __('ui.navigation.activity_log') }}
                        </div>

                        <div
                            class="pm-sidebar-manage-description"
                        >
                            {{ __('ui.navigation.activity_log_description') }}
                        </div>
                    </div>
                </a>

                <a
                    href="/users"
                    data-requires-capability="manage_users"
                    class="pm-sidebar-manage-item"
                >
                    <div class="pm-sidebar-manage-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M19 8v6"/>
                            <path d="M16 11h6"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="pm-sidebar-manage-title"
                            data-i18n="navigation.users"
                        >
                            {{ __('ui.navigation.users') }}
                        </div>

                        <div
                            class="pm-sidebar-manage-description"
                        >
                            {{ __('ui.navigation.users_description') }}
                        </div>
                    </div>
                </a>

                <a
                    href="/settings"
                    data-requires-capability="manage_settings"
                    class="pm-sidebar-manage-item"
                >
                    <div class="pm-sidebar-manage-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.2.37.5.7.9.9.3.16.7.2 1.1.2h.1v4h-.1a1.7 1.7 0 0 0-2 .9Z"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="pm-sidebar-manage-title"
                            data-i18n="navigation.settings"
                        >
                            {{ __('ui.navigation.settings') }}
                        </div>

                        <div
                            class="pm-sidebar-manage-description"
                        >
                            {{ __('ui.navigation.settings_description') }}
                        </div>
                    </div>
                </a>
            </div>

            <button
                id="sidebar-manage-toggle"
                type="button"
                class="
                    pm-sidebar-manage-toggle
                    {{ request()->is('activity-log') || request()->is('settings')
                        ? 'pm-sidebar-manage-toggle-active'
                        : ''
                    }}
                "
                aria-expanded="false"
                aria-controls="sidebar-manage-menu"
            >
                <span class="pm-sidebar-manage-toggle-icon">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <circle cx="12" cy="9" r="2.5"/>
                        <path d="M8 17c.7-2 2-3 4-3s3.3 1 4 3"/>
                    </svg>
                </span>

                <span
                    class="flex-1 text-left"
                    data-i18n="navigation.manage"
                >
                    {{ __('ui.navigation.manage') }}
                </span>

                <svg
                    id="sidebar-manage-chevron"
                    class="pm-sidebar-manage-chevron"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="m6 15 6-6 6 6"/>
                </svg>
            </button>
        </div>
        </div>
    </aside>

    {{-- Main application --}}
    <div class="min-h-screen lg:pl-72">

        {{-- Top bar --}}
        <header
            class="
                sticky top-0 z-30
                flex min-h-20 items-center
                border-b border-[var(--pm-border)]
                bg-[var(--pm-surface)]/95
                px-4 backdrop-blur
                sm:px-6 lg:px-8
            "
        >
            <button
                id="sidebar-open"
                type="button"
                class="
                    mr-3 inline-flex h-10 w-10
                    shrink-0 items-center justify-center
                    rounded-lg
                    border border-[var(--pm-border)]
                    bg-[var(--pm-surface)]
                    text-[var(--pm-text-secondary)]
                    transition
                    hover:bg-[var(--pm-hover)]
                    lg:hidden
                "
                aria-label="Open navigation"
            >
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="4" y1="12" x2="20" y2="12"/>
                    <line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
            </button>

            <div class="min-w-0 flex-1">
                <div
                    id="organisation-name"
                    class="
                        truncate text-sm font-semibold
                        text-[var(--pm-text)]
                    "
                >
                    {{ app(App\Services\ApplicationIdentityService::class)->displayName() }}
                </div>

                <div
                    class="
                        hidden text-xs
                        text-[var(--pm-text-muted)]
                        sm:block
                    "
                    data-i18n="product.property_management"
                >
                    {{ __('ui.product.property_management') }}
                </div>
            </div>

            <div
                class="
                    ml-3 flex shrink-0
                    items-center gap-1.5
                    sm:gap-2
                "
            >
                {{-- Current date --}}
                <div
                    id="shell-current-date"
                    class="
                        mr-1 hidden whitespace-nowrap
                        text-sm font-medium
                        text-[var(--pm-text-secondary)]
                        xl:block
                    "
                ></div>

                {{-- Refresh --}}
                <button
                    id="shell-refresh"
                    type="button"
                    class="
                        inline-flex h-10 w-10
                        items-center justify-center
                        rounded-lg
                        text-[var(--pm-text-muted)]
                        transition
                        hover:bg-[var(--pm-hover)]
                        hover:text-[var(--pm-text)]
                    "
                    data-i18n-aria-label="shell.refresh"
                    aria-label="Refresh"
                    title="Refresh"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5"/>
                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/>
                    </svg>
                </button>

                {{-- Notifications / release information --}}
                <div class="relative">
                    <button
                        id="notification-menu-toggle"
                        type="button"
                        class="
                            relative inline-flex h-10 w-10
                            items-center justify-center
                            rounded-lg
                            text-[var(--pm-text-muted)]
                            transition
                            hover:bg-[var(--pm-hover)]
                            hover:text-[var(--pm-text)]
                        "
                        data-i18n-aria-label="shell.notifications"
                        aria-label="Notifications"
                        aria-expanded="false"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>

                        <span
                            class="
                                absolute right-2 top-2
                                h-2 w-2 rounded-full
                                bg-patrimoine-600
                                ring-2 ring-[var(--pm-surface)]
                            "
                        ></span>
                    </button>

                    <div
                        id="notification-menu"
                        class="
                            absolute right-0 mt-2 hidden
                            w-[min(22rem,calc(100vw-2rem))]
                            overflow-hidden rounded-xl
                            border border-[var(--pm-border)]
                            bg-[var(--pm-surface-elevated)]
                            shadow-xl
                        "
                    >
                        <div
                            class="
                                border-b border-[var(--pm-border)]
                                px-4 py-3
                            "
                        >
                            <div
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                                data-i18n="shell.whats_new"
                            >
                                What's new
                            </div>
                        </div>

                        <div class="p-4">
                            <div
                                class="
                                    text-sm font-semibold
                                    text-[var(--pm-text)]
                                "
                                data-i18n="release.v104_heading"
                            >
                                You are now on Patrimoine v1.0.4
                            </div>

                            <ul
                                class="
                                    mt-3 space-y-2
                                    text-sm
                                    text-[var(--pm-text-muted)]
                                "
                            >
                                <li
                                    class="flex gap-2"
                                    data-i18n="release.v104_ui"
                                >
                                    Updated interface for a cleaner experience.
                                </li>

                                <li
                                    class="flex gap-2"
                                    data-i18n="release.v104_fixes"
                                >
                                    Usability and localisation fixes.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Current user --}}
                <div class="relative">
                    <button
                        id="user-menu-toggle"
                        type="button"
                        class="
                            inline-flex h-10 w-10
                            items-center justify-center
                            rounded-lg
                            text-[var(--pm-text-muted)]
                            transition
                            hover:bg-[var(--pm-hover)]
                            hover:text-[var(--pm-text)]
                        "
                        aria-expanded="false"
                        aria-label="{{ __('ui.shell.my_profile') }}"
                        title="{{ __('ui.shell.my_profile') }}"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                    </button>

                    <div
                        id="user-menu"
                        class="
                            absolute right-0 mt-2 hidden
                            w-[min(22rem,calc(100vw-2rem))]
                            overflow-hidden rounded-xl
                            border border-[var(--pm-border)]
                            bg-[var(--pm-surface-elevated)]
                            shadow-xl
                        "
                    >
                        {{-- Signed-in identity --}}
                        <div
                            class="
                                flex items-center gap-3
                                border-b border-[var(--pm-border)]
                                px-4 py-4
                            "
                        >
                            <div
                                id="topbar-avatar"
                                class="
                                    flex h-11 w-11 shrink-0
                                    items-center justify-center
                                    rounded-full
                                    bg-patrimoine-800
                                    text-sm font-semibold text-white
                                "
                            >
                                PM
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    id="user-menu-name"
                                    class="
                                        truncate text-sm font-semibold
                                        text-[var(--pm-text)]
                                    "
                                >
                                    Property Manager
                                </div>

                                <div
                                    id="user-menu-role"
                                    class="
                                        mt-0.5 truncate text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    Property Manager
                                </div>
                            </div>
                        </div>

                        {{-- My Profile --}}
                        <div class="p-2">
                            <button
                                id="my-profile-open"
                                type="button"
                                class="
                                    flex w-full items-center gap-3
                                    rounded-lg px-3 py-3
                                    text-left transition
                                    hover:bg-[var(--pm-hover)]
                                "
                            >
                                <svg
                                    class="
                                        h-5 w-5 shrink-0
                                        text-[var(--pm-text-muted)]
                                    "
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 21a8 8 0 0 1 16 0"/>
                                </svg>

                                <span class="min-w-0 flex-1">
                                    <span
                                        class="
                                            block text-sm font-semibold
                                            text-[var(--pm-text)]
                                        "
                                    >
                                        {{ __('ui.shell.my_profile') }}
                                    </span>

                                    <span
                                        class="
                                            mt-0.5 block text-xs
                                            text-[var(--pm-text-muted)]
                                        "
                                    >
                                        {{ __('ui.shell.my_profile_description') }}
                                    </span>
                                </span>

                                <svg
                                    class="
                                        h-4 w-4 shrink-0
                                        text-[var(--pm-text-subtle)]
                                    "
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Appearance --}}
                        <div
                            class="
                                border-t border-[var(--pm-border)]
                                p-2
                            "
                        >
                            <div
                                class="
                                    px-2 pb-1.5 pt-1
                                    text-[11px] font-semibold uppercase
                                    tracking-wide
                                    text-[var(--pm-text-subtle)]
                                "
                                data-i18n="shell.appearance"
                            >
                                {{ __('ui.shell.appearance') }}
                            </div>

                            <div
                                class="
                                    grid grid-cols-3 gap-1
                                    rounded-lg
                                    bg-[var(--pm-surface-subtle)]
                                    p-1
                                "
                                role="group"
                                aria-label="Appearance"
                            >
                                <button
                                    type="button"
                                    data-theme-option="light"
                                    class="theme-option"
                                    data-i18n="shell.theme_light"
                                >
                                    {{ __('ui.shell.theme_light') }}
                                </button>

                                <button
                                    type="button"
                                    data-theme-option="dark"
                                    class="theme-option"
                                    data-i18n="shell.theme_dark"
                                >
                                    {{ __('ui.shell.theme_dark') }}
                                </button>

                                <button
                                    type="button"
                                    data-theme-option="system"
                                    class="theme-option"
                                    data-i18n="shell.theme_system"
                                >
                                    {{ __('ui.shell.theme_system') }}
                                </button>
                            </div>
                        </div>

                        {{-- Account actions --}}
                        <div
                            class="
                                border-t border-[var(--pm-border)]
                                p-2
                            "
                        >
                            <button
                                id="change-password-open"
                                type="button"
                                class="shell-menu-item"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <rect x="4" y="10" width="16" height="10" rx="2"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                </svg>

                                <span data-i18n="password.change_action">
                                    {{ __('ui.password.change_action') }}
                                </span>
                            </button>

                            <button
                                id="logout-button"
                                type="button"
                                class="shell-menu-item"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path d="M10 17l5-5-5-5"/>
                                    <path d="M15 12H3"/>
                                    <path d="M21 19V5a2 2 0 0 0-2-2h-6"/>
                                </svg>

                                <span data-i18n="navigation.sign_out">
                                    {{ __('ui.navigation.sign_out') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-5 py-7 sm:px-7 lg:px-10 lg:py-9">
            @yield('content')
        </main>

    </div>



<x-drawer
    id="profile-modal"
    backdrop-id="profile-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="profile-modal-title"
        description-id="profile-modal-description"
        close-id="profile-modal-close"
        close-label="Close"
        close-label-key="users.close"
    >
        <x-slot:title>
            {{ __('ui.shell.my_profile') }}
        </x-slot:title>

        <x-slot:description>
            {{ __('ui.shell.profile_description') }}
        </x-slot:description>
    </x-drawer-header>

    <form
        id="profile-form"
        class="flex min-h-0 flex-1 flex-col"
    >
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                px-6 py-6
            "
        >
            <div
                id="profile-form-message"
                class="
                    mb-5 hidden rounded-lg
                    border px-4 py-3
                    text-sm
                "
                role="alert"
            ></div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label
                        for="profile-name"
                        class="pm-field-label"
                    >
                        {{ __('ui.users.name') }}
                    </label>

                    <input
                        id="profile-name"
                        type="text"
                        maxlength="255"
                        required
                        class="pm-input"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label
                        for="profile-email"
                        class="pm-field-label"
                    >
                        {{ __('ui.users.email') }}
                    </label>

                    <input
                        id="profile-email"
                        type="email"
                        maxlength="255"
                        required
                        class="pm-input"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label
                        for="profile-phone"
                        class="pm-field-label"
                    >
                        {{ __('ui.users.phone') }}
                    </label>

                    <input
                        id="profile-phone"
                        type="text"
                        maxlength="50"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="profile-role"
                        class="pm-field-label"
                    >
                        {{ __('ui.users.role') }}
                    </label>

                    <input
                        id="profile-role"
                        type="text"
                        disabled
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="profile-status"
                        class="pm-field-label"
                    >
                        {{ __('ui.users.status') }}
                    </label>

                    <input
                        id="profile-status"
                        type="text"
                        disabled
                        class="pm-input"
                    >
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                id="profile-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                {{ __('ui.users.cancel') }}
            </button>

            <button
                id="profile-submit-button"
                type="submit"
                class="pm-button-primary"
            >
                {{ __('ui.actions.save') }}
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>


<div
    id="change-password-modal"
    class="
        fixed inset-0 z-[80]
        hidden items-center justify-center
        p-4
    "
    aria-hidden="true"
>
    <div
        id="change-password-backdrop"
        class="absolute inset-0 bg-slate-950/50 backdrop-blur-[1px]"
    ></div>

    <div
        class="
            relative z-10 w-full max-w-md
            rounded-2xl bg-white shadow-2xl
        "
    >
        <div
            class="
                flex items-start justify-between gap-5
                border-b border-slate-200
                px-6 py-5
            "
        >
            <div>
                <h2
                    class="text-lg font-semibold text-slate-950"
                    data-i18n="password.change_heading"
                >
                    Change password
                </h2>

                <p
                    class="mt-1 text-sm text-slate-500"
                    data-i18n="password.change_description"
                >
                    Update the password for your account.
                </p>
            </div>

            <button
                id="change-password-close"
                type="button"
                class="
                    rounded-lg p-2 text-slate-400
                    hover:bg-slate-100 hover:text-slate-700
                "
                data-i18n-aria-label="users.close"
                aria-label="Close"
            >
                ✕
            </button>
        </div>

        <form
            id="change-password-form"
            class="space-y-5 p-6"
        >
            <div
                id="change-password-message"
                class="
                    hidden rounded-lg border
                    px-4 py-3 text-sm
                "
                role="alert"
            ></div>

            <div>
                <label
                    for="current-password"
                    class="mb-1.5 block text-sm font-medium text-slate-700"
                    data-i18n="password.current_password"
                >
                    Current password
                </label>

                <input
                    id="current-password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        px-3 py-2.5 text-sm
                    "
                >
            </div>

            <div>
                <label
                    for="change-new-password"
                    class="mb-1.5 block text-sm font-medium text-slate-700"
                    data-i18n="password.new_password"
                >
                    New password
                </label>

                <input
                    id="change-new-password"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        px-3 py-2.5 text-sm
                    "
                >
            </div>

            <div>
                <label
                    for="change-password-confirmation"
                    class="mb-1.5 block text-sm font-medium text-slate-700"
                    data-i18n="password.confirm_password"
                >
                    Confirm password
                </label>

                <input
                    id="change-password-confirmation"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        px-3 py-2.5 text-sm
                    "
                >
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button
                    id="change-password-cancel"
                    type="button"
                    class="
                        rounded-lg border border-slate-200
                        bg-white px-4 py-2.5
                        text-sm font-medium text-slate-700
                        hover:bg-slate-50
                    "
                    data-i18n="users.cancel"
                >
                    Cancel
                </button>

                <button
                    id="change-password-submit"
                    type="submit"
                    class="
                        rounded-lg bg-patrimoine-800
                        px-4 py-2.5
                        text-sm font-medium text-white
                        hover:bg-patrimoine-900
                        disabled:opacity-50
                    "
                    data-i18n="password.change_action"
                >
                    Change password
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        (() => {
            const root =
                document.documentElement;

            const name =
                root.dataset.cachedUserName
                || '';

            const role =
                root.dataset.cachedUserRole
                || '';

            if (
                name === ''
                && role === ''
            ) {
                return;
            }

            const initials = (value) => {
                const parts =
                    String(value)
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean);

                if (parts.length === 0) {
                    return '';
                }

                return parts
                    .slice(0, 2)
                    .map(
                        part =>
                            part
                                .charAt(0)
                                .toUpperCase()
                    )
                    .join('');
            };

            const roleLabels = {
                en: {
                    administrator:
                        'Administrator',
                    property_manager:
                        'Property Manager',
                    viewer:
                        'Viewer',
                },

                fr: {
                    administrator:
                        'Administrateur',
                    property_manager:
                        'Gestionnaire immobilier',
                    viewer:
                        'Lecteur',
                },
            };

            const language =
                root.dataset.presentationLanguage
                || 'en';

            const translatedRole =
                roleLabels[language]?.[role]
                || roleLabels.en[role]
                || role;

            const avatar =
                initials(name);

            [
                'sidebar-user-name',
                'topbar-user-name',
                'user-menu-name',
            ].forEach(
                id => {
                    const element =
                        document.getElementById(id);

                    if (
                        element
                        && name !== ''
                    ) {
                        element.textContent =
                            name;
                    }
                }
            );

            [
                'sidebar-user-role',
                'topbar-user-role',
                'user-menu-role',
            ].forEach(
                id => {
                    const element =
                        document.getElementById(id);

                    if (
                        element
                        && translatedRole !== ''
                    ) {
                        element.textContent =
                            translatedRole;
                    }
                }
            );

            [
                'sidebar-avatar',
                'topbar-avatar',
            ].forEach(
                id => {
                    const element =
                        document.getElementById(id);

                    if (
                        element
                        && avatar !== ''
                    ) {
                        element.textContent =
                            avatar;
                    }
                }
            );
        })();
    </script>

</body>
</html>
