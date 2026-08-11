<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Patrimoine')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body
    class="
        min-h-screen bg-[#f7f8f7]
        font-sans text-slate-900
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
                        flex h-10 w-10 items-center justify-center
                        rounded-xl bg-white
                        font-semibold text-patrimoine-950
                    "
                >
                    P
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
                        Property Management
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
                Workspace
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

                    Dashboard
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

                    Properties
                </a>

                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-200
                        transition
                        hover:bg-white/5 hover:text-white
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

                    Parties
                </a>

                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-200
                        transition
                        hover:bg-white/5 hover:text-white
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

                    Leases
                </a>

                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-200
                        transition
                        hover:bg-white/5 hover:text-white
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

                    Payments
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
                Finance
            </p>

            <div class="space-y-1">

                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-200
                        transition
                        hover:bg-white/5 hover:text-white
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

                    Reports
                </a>

                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-lg px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-200
                        transition
                        hover:bg-white/5 hover:text-white
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

                    Settings
                </a>
            </div>
        </nav>

        <div
            class="
                border-t border-white/10
                p-4
            "
        >
            <div
                class="
                    flex items-center gap-3
                    rounded-lg px-3 py-2
                "
            >
                <div
                    id="sidebar-avatar"
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        rounded-full bg-patrimoine-700
                        text-sm font-semibold text-white
                    "
                >
                    PM
                </div>

                <div class="min-w-0 flex-1">
                    <div
                        id="sidebar-user-name"
                        class="
                            truncate text-sm font-medium
                            text-white
                        "
                    >
                        Property Manager
                    </div>

                    <div
                        id="sidebar-user-role"
                        class="
                            truncate text-xs
                            text-patrimoine-300
                        "
                    >
                        Property Manager
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main application --}}
    <div class="min-h-screen lg:pl-72">

        {{-- Top bar --}}
        <header
            class="
                sticky top-0 z-30
                flex h-20 items-center
                border-b border-slate-200
                bg-white/95 px-5
                backdrop-blur
                sm:px-7 lg:px-10
            "
        >
            <button
                id="sidebar-open"
                type="button"
                class="
                    mr-4 inline-flex h-10 w-10
                    items-center justify-center
                    rounded-lg border border-slate-200
                    text-slate-600
                    hover:bg-slate-50
                    lg:hidden
                "
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
                        truncate text-sm font-medium
                        text-slate-900
                    "
                >
                    Patrimoine
                </div>

                <div class="text-xs text-slate-500">
                    Property Management
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    id="logout-button"
                    type="button"
                    class="
                        rounded-lg border border-slate-200
                        bg-white px-4 py-2
                        text-sm font-medium text-slate-700
                        shadow-sm transition
                        hover:bg-slate-50
                    "
                >
                    Sign out
                </button>
            </div>
        </header>

        <main class="px-5 py-7 sm:px-7 lg:px-10 lg:py-9">
            @yield('content')
        </main>

    </div>

</body>
</html>
