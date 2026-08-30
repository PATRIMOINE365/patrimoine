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
        @yield('title', 'Patrimoine 365')
    </title>

    <link
        rel="icon"
        sizes="48x48"
        href="/favicon.ico"
    >
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

    <link
        rel="manifest"
        href="/branding/site.webmanifest"
    >

    <meta
        name="theme-color"
        content="#26744b"
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
            background-color: #f5f7f6;
        }

        html[data-theme="dark"],
        html[data-theme="dark"] body {
            background-color: #0e1311;
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
            bg-[var(--pm-overlay)]
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
                <img
                    src="/branding/patrimoine-logo.svg"
                    alt="Patrimoine 365"
                    class="h-10 w-10 shrink-0"
                >

                <div>
                    <div
                        class="
                            text-base font-semibold
                            tracking-tight text-white
                        "
                    >
                        Patrimoine <span class="text-patrimoine-300">365</span>
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
                    href="/accounting"
                    class="
                        {{
                            request()->is('accounting')
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
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <path d="M8 7h8"/>
                        <path d="M8 11h3"/>
                        <path d="M8 15h3"/>
                        <path d="M15 11v6"/>
                        <path d="M13.5 13.5h3"/>
                    </svg>

                    <span data-i18n="navigation.accounting">{{ __('ui.navigation.accounting') }}</span>
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

            {{--
                Manage group. Every capability behind these links is
                Administrator-only, so the group carries the same gate the
                bottom menu used to; each link still declares its own
                capability, leaving permissions.js the single authority.
            --}}
            <div
                data-requires-capability="view_activity_log"
                class="rbac-hidden shell-admin-only"
            >
                <p
                    class="
                        mb-3 mt-8 px-3
                        text-[10px] font-semibold uppercase
                        tracking-[0.16em]
                        text-patrimoine-400
                    "
                >
                    <span data-i18n="navigation.manage">{{ __('ui.navigation.manage') }}</span>
                </p>

                <div class="space-y-1">

                    <a
                        href="/settings"
                        data-requires-capability="manage_settings"
                        class="
                            {{ request()->is('settings')
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
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.2.37.5.7.9.9.3.16.7.2 1.1.2h.1v4h-.1a1.7 1.7 0 0 0-2 .9Z"/>
                        </svg>

                        <span data-i18n="navigation.settings">{{ __('ui.navigation.settings') }}</span>
                    </a>

                    <a
                        href="/activity-log"
                        data-requires-capability="view_activity_log"
                        class="
                            {{ request()->is('activity-log')
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
                            {{--
                                V1.0.36: a history dial rather than a
                                bulleted list. The log answers "what
                                happened, and when" — the list icon said
                                only "several things".
                            --}}
                            <path d="M3 4v5h5"/>
                            <path d="M3.5 9A9 9 0 1 1 3 12"/>
                            <path d="M12 7.5V12l3 2"/>
                        </svg>

                        <span data-i18n="navigation.activity_log">{{ __('ui.navigation.activity_log') }}</span>
                    </a>

                    <a
                        href="/financial-journal"
                        data-requires-capability="view_financial_journal"
                        class="
                            {{ request()->is('financial-journal')
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
                            {{--
                                V1.0.36: an open ledger. Four ruled lines
                                and two ticks read as a table, which is
                                what every other list here is too.
                            --}}
                            <path d="M12 7c-1.5-1.4-3.5-2.1-5.6-2.1H4v12.6h2.4c2.1 0 4.1.7 5.6 2.1"/>
                            <path d="M12 7c1.5-1.4 3.5-2.1 5.6-2.1H20v12.6h-2.4c-2.1 0-4.1.7-5.6 2.1"/>
                            <path d="M12 7v12.6"/>
                        </svg>

                        <span data-i18n="navigation.financial_journal">{{ __('ui.navigation.financial_journal') }}</span>
                    </a>

                    {{--
                        V1.0.11: platform staff only; revealed by auth.js
                        once /api/auth/me confirms is_platform_admin. Both the
                        attribute and the class are needed: Tailwind's flex
                        utility would otherwise defeat the attribute alone,
                        and auth.js clears both.
                    --}}
                    <a
                        href="/admin"
                        data-platform-admin-only
                        hidden
                        class="
                            hidden
                            {{ request()->is('admin') || request()->is('admin/*')
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
                            <path d="M12 3l8 4v5c0 4.4-3.2 8.4-8 9-4.8-.6-8-4.6-8-9V7l8-4z"/>
                            <path d="M9.5 12l2 2 3.5-4"/>
                        </svg>

                        <span data-i18n="navigation.platform_console">{{ __('ui.navigation.platform_console') }}</span>
                    </a>
                </div>
            </div>
        </nav>
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
                            id="notification-unread-badge"
                            class="
                                pm-notification-unread-badge
                                hidden
                            "
                            aria-hidden="true"
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
                                data-i18n="shell.notifications"
                            >
                                Notifications
                            </div>
                        </div>

                        {{--
                            V1.0.7 notification center.

                            Rows are rendered by auth.js from
                            GET /api/notifications every time the panel
                            opens. Each row deep-links to the page where
                            the situation is handled.
                        --}}
                        <div
                            id="notification-list"
                            class="
                                max-h-[22rem]
                                overflow-y-auto p-2
                            "
                        >
                            <div
                                class="
                                    px-3 py-6 text-center
                                    text-sm
                                    text-[var(--pm-text-muted)]
                                "
                                data-i18n="notifications.loading"
                            >
                                Loading notifications…
                            </div>
                        </div>

                        {{--
                            Release announcement details. Hidden until the
                            release row is selected; opening it preserves
                            the existing release read-state flow.
                        --}}
                        <div
                            id="notification-release-panel"
                            class="
                                hidden border-t
                                border-[var(--pm-border)]
                                p-4
                            "
                            data-release-notification
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

                            {{--
                                V1.0.7: release detail lives on the Update
                                log page; the panel links there instead of
                                carrying per-version bullets that go stale.
                            --}}
                            <p
                                class="
                                    mt-3 text-sm
                                    text-[var(--pm-text-muted)]
                                "
                                data-i18n="release.summary_line"
                            >
                                This update brings new features and improvements across Patrimoine 365.
                            </p>

                            <a
                                href="/help#updates"
                                class="
                                    mt-3 inline-flex items-center gap-1.5
                                    text-sm font-medium
                                    text-[var(--pm-accent)]
                                    hover:underline
                                "
                                data-i18n="release.view_details"
                            >
                                View the full update log
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Current user --}}
                <div class="relative">
                    {{--
                        The button is the avatar itself, so the photograph
                        fills what you click. auth.js paints it once
                        /api/auth/me answers; until then it carries the
                        initials the cached identity already gave us.
                    --}}
                    <button
                        id="user-menu-toggle"
                        type="button"
                        class="
                            pm-avatar pm-avatar-initials
                            h-10 w-10
                            text-sm transition
                        "
                        style="width:2.5rem;height:2.5rem;font-size:0.8125rem"
                        aria-expanded="false"
                        aria-label="{{ __('ui.shell.my_profile') }}"
                        data-i18n-aria-label="shell.my_profile"
                        title="{{ __('ui.shell.my_profile') }}"
                    ></button>

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
                                    id="user-menu-email"
                                    class="
                                        mt-0.5 truncate text-xs
                                        text-[var(--pm-text-muted)]
                                    "
                                >
                                    user@example.com
                                </div>

                                <div class="mt-2">
                                    <span
                                        id="user-menu-role"
                                        class="
                                            pm-user-role-pill
                                            inline-flex items-center
                                            rounded-full
                                            px-2.5 py-1
                                            text-xs font-semibold
                                        "
                                    >
                                        Property Manager
                                    </span>
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
                                        data-i18n="shell.my_profile"
                                    >
                                        {{ __('ui.shell.my_profile') }}
                                    </span>

                                    <span
                                        class="
                                            mt-0.5 block text-xs
                                            text-[var(--pm-text-muted)]
                                        "
                                        data-i18n="shell.my_profile_description"
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

                        {{--
                            V1.0.36: one door instead of two. Help and
                            Update log were two entries into the same
                            page; Support lands on the tab where you can
                            actually ask somebody, with the guide, the
                            error codes and the update log beside it.
                        --}}
                        <div
                            class="
                                border-t border-[var(--pm-border)]
                                p-2
                            "
                        >
                            <a
                                href="/help"
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
                                    <path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L4 21l1.1-3.6A8.4 8.4 0 1 1 21 11.5Z"/>
                                    <path d="M9.6 9.2a2.5 2.5 0 0 1 4.9.4c0 1.6-2.4 2-2.4 3.4"/>
                                    <path d="M12 16h.01"/>
                                </svg>

                                <span
                                    class="
                                        block text-sm font-medium
                                        text-[var(--pm-text)]
                                    "
                                    data-i18n="shell.support"
                                >
                                    Support
                                </span>
                            </a>
                        </div>

                        {{-- Account actions --}}
                        <div
                            class="
                                border-t border-[var(--pm-border)]
                                p-2
                            "
                        >
                            <button
                                id="logout-button"
                                type="button"
                                class="shell-menu-item shell-menu-item-danger"
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

                                <span class="min-w-0">
                                    <span
                                        class="
                                            shell-menu-item-title
                                            block text-sm font-medium
                                        "
                                        data-i18n="navigation.sign_out"
                                    >
                                        {{ __('ui.navigation.sign_out') }}
                                    </span>

                                    <span
                                        class="
                                            shell-menu-item-note
                                            mt-0.5 block text-xs
                                            font-normal opacity-80
                                        "
                                        data-i18n="navigation.sign_out_description"
                                    >
                                        {{ __('ui.navigation.sign_out_description') }}
                                    </span>
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
        close-label-key="actions.close"
    >
        <x-slot:title>
            {{-- A slot is not an element, so the span carries the hook. --}}
            <span data-i18n="shell.my_profile">{{ __('ui.shell.my_profile') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="shell.profile_description">{{ __('ui.shell.profile_description') }}</span>
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

            {{--
                V1.0.31: the photograph. The round window is the result, so
                what is framed is what appears everywhere else. The whole
                picture is kept behind it, which is what lets Reframe reopen
                this exactly where it was left.
            --}}
            <div class="mb-6 flex flex-col items-center gap-3">
                <span
                    id="profile-avatar"
                    class="pm-avatar pm-avatar-initials"
                    style="width:4rem;height:4rem;font-size:1.25rem"
                    aria-hidden="true"
                ></span>

                <div class="flex flex-wrap items-center justify-center gap-2">
                    <button
                        id="profile-photo-choose"
                        type="button"
                        class="pm-button-secondary"
                    >
                        <span data-i18n="profile.photo_choose">{{ __('ui.profile.photo_choose') }}</span>
                    </button>

                    <button
                        id="profile-photo-reframe"
                        type="button"
                        class="pm-button-secondary hidden"
                    >
                        <span data-i18n="profile.photo_reframe">{{ __('ui.profile.photo_reframe') }}</span>
                    </button>

                    <button
                        id="profile-photo-remove"
                        type="button"
                        class="pm-button-danger-outline hidden"
                    >
                        <span data-i18n="profile.photo_remove">{{ __('ui.profile.photo_remove') }}</span>
                    </button>
                </div>

                <input
                    id="profile-photo-file"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,.heic,.heif"
                    class="hidden"
                >
            </div>

            {{-- The cropper, shown only while a picture is being framed. --}}
            <div id="profile-photo-editor" class="mb-6 hidden">
                <div id="profile-photo-stage" class="pm-avatar-crop">
                    <div class="pm-avatar-crop-window"></div>
                </div>

                <div class="pm-avatar-zoom">
                    <span
                        class="text-xs text-[var(--pm-text-muted)]"
                        data-i18n="profile.photo_zoom"
                    >{{ __('ui.profile.photo_zoom') }}</span>

                    <input
                        id="profile-photo-zoom"
                        type="range"
                        min="100"
                        max="400"
                        value="100"
                        aria-label="{{ __('ui.profile.photo_zoom') }}"
                        data-i18n-aria-label="profile.photo_zoom"
                    >
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        id="profile-photo-save"
                        type="button"
                        class="pm-button-primary"
                    >
                        <span data-i18n="profile.photo_save">{{ __('ui.profile.photo_save') }}</span>
                    </button>

                    <button
                        id="profile-photo-cancel"
                        type="button"
                        class="pm-button-secondary"
                    >
                        <span data-i18n="profile.photo_cancel">{{ __('ui.profile.photo_cancel') }}</span>
                    </button>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                {{--
                    V1.0.7 structured names: the API accepts
                    given_names + surname and recomposes the
                    display name.
                --}}
                <div>
                    <label
                        for="profile-given-names"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.given_names">
                            Given names
                        </span>
                    </label>

                    <input
                        id="profile-given-names"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="profile-surname"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.surname">
                            Surname
                        </span>
                    </label>

                    <input
                        id="profile-surname"
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
                        data-i18n="users.email"
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

                <x-phone-field
                    id="profile-phone"
                    label="users.phone"
                    wrapper="sm:col-span-2"
                />

                <div>
                    <label
                        for="profile-role"
                        class="pm-field-label"
                        data-i18n="users.role"
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
                        data-i18n="users.status"
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


                {{-- Optional authenticated password change --}}
                <div
                    class="
                        sm:col-span-2
                        mt-3 border-t
                        border-[var(--pm-border)]
                        pt-6
                    "
                >
                    <div
                        class="
                            mb-5 text-xs font-semibold uppercase
                            tracking-wide
                            text-[var(--pm-text-subtle)]
                        "
                        data-i18n="password.section"
                    >
                        {{ __('ui.password.section') }}
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label
                                for="profile-new-password"
                                class="pm-field-label"
                                data-i18n="password.new_password"
                            >
                                {{ __('ui.password.new_password') }}
                            </label>

                            <div class="relative">
                                <input
                                    id="profile-new-password"
                                    type="password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    class="pm-input pr-12"
                                >

                                <button
                                    type="button"
                                    data-password-toggle="profile-new-password"
                                    class="
                                        absolute right-0 top-0
                                        flex h-full w-11
                                        items-center justify-center
                                        text-[var(--pm-text-muted)]
                                        hover:text-[var(--pm-text)]
                                    "
                                    aria-label="{{ __('ui.password.show_password') }}"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true"
                                    >
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            <p
                                class="
                                    mt-2 text-xs
                                    text-[var(--pm-text-muted)]
                                "
                                data-i18n="password.profile_new_help"
                            >
                                {{ __('ui.password.profile_new_help') }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="profile-current-password"
                                class="pm-field-label"
                                data-i18n="password.current_password"
                            >
                                {{ __('ui.password.current_password') }}
                            </label>

                            <div class="relative">
                                <input
                                    id="profile-current-password"
                                    type="password"
                                    autocomplete="current-password"
                                    class="pm-input pr-12"
                                >

                                <button
                                    type="button"
                                    data-password-toggle="profile-current-password"
                                    class="
                                        absolute right-0 top-0
                                        flex h-full w-11
                                        items-center justify-center
                                        text-[var(--pm-text-muted)]
                                        hover:text-[var(--pm-text)]
                                    "
                                    aria-label="{{ __('ui.password.show_password') }}"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        aria-hidden="true"
                                    >
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            <p
                                class="
                                    mt-2 text-xs
                                    text-[var(--pm-text-muted)]
                                "
                                data-i18n="password.profile_current_help"
                            >
                                {{ __('ui.password.profile_current_help') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{--
                    V1.0.36: asking for your own data is not an
                    administrative act, so it sits with the account
                    rather than among the drawer's save actions, where
                    it read as a third thing the Save button might do.
                    It is a link because it takes you somewhere with
                    your data rather than changing anything here.
                --}}
                <div
                    class="
                        sm:col-span-2
                        mt-3 border-t
                        border-[var(--pm-border)]
                        pt-6
                    "
                >
                    <div
                        class="
                            mb-3 text-xs font-semibold uppercase
                            tracking-wide
                            text-[var(--pm-text-subtle)]
                        "
                        data-i18n="profile.data_section"
                    >
                        {{ __('ui.profile.data_section') }}
                    </div>

                    <button
                        id="profile-download-data"
                        type="button"
                        class="
                            text-sm font-medium
                            text-[var(--pm-accent)]
                            hover:underline
                            disabled:opacity-60
                        "
                    >
                        <span data-i18n="profile.download_data">{{ __('ui.profile.download_data') }}</span>
                    </button>

                    <p
                        class="
                            mt-2 text-xs
                            text-[var(--pm-text-muted)]
                        "
                        data-i18n="profile.download_data_help"
                    >
                        {{ __('ui.profile.download_data_help') }}
                    </p>
                </div>
            </div>
        </div>

        <x-drawer-footer>
            <button
                id="profile-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">
                    {{ __('ui.actions.cancel') }}
                </span>
            </button>

            <button
                id="profile-submit-button"
                type="submit"
                class="pm-button-primary"
            >
                <span data-i18n="actions.save">
                    {{ __('ui.actions.save') }}
                </span>
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>


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

            /*
                V1.0.31: the top bar button is the avatar. Painting the
                cached initials here means it is never an empty circle
                while /api/auth/me is in flight; auth.js replaces them
                with the photograph the moment it answers.
            */
            [
                'sidebar-avatar',
                'topbar-avatar',
                'user-menu-toggle',
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
