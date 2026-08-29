{{--
    The Licence tab of Settings.

    Lifted wholesale from the standalone /license page in V1.0.32, which
    now redirects here. resources/js/license.js still fills it from
    GET /api/license and still finds it by #license-workspace.
--}}
{{--
    V1.0.10 licence & plan page.

    Static shell only: the current plan card, usage meters and the plan
    comparison table are rendered by resources/js/license.js from
    GET /api/license.
--}}
<div
    id="license-workspace"
    class="max-w-[1040px]"
>
    <div>
        <h2
            class="
                text-xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
            data-i18n="license.heading"
        >
            License &amp; plan
        </h2>

        <p
            class="
                mt-2 max-w-3xl
                text-sm leading-6
                text-[var(--pm-text-muted)]
            "
            data-i18n="license.description"
        >
            Your organisation's current plan, usage against its limits,
            and what each plan includes.
        </p>
    </div>

    <div
        id="license-error"
        class="
            mt-6 hidden rounded-xl border
            border-[var(--pm-danger,#b3261e)]
            px-4 py-3 text-sm
        "
    ></div>

    {{-- Current plan summary card --}}
    <div
        id="license-current"
        class="
            mt-6 rounded-2xl border border-[var(--pm-border)]
            bg-[var(--pm-surface)] p-6
        "
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div
                    class="text-sm text-[var(--pm-text-muted)]"
                    data-i18n="license.current_plan"
                >
                    Current plan
                </div>

                <div class="mt-1 flex items-center gap-3">
                    <span
                        id="license-plan-name"
                        class="
                            text-2xl font-semibold tracking-tight
                            text-[var(--pm-text)]
                        "
                    >—</span>

                    <span
                        id="license-trial-badge"
                        class="
                            hidden rounded-full
                            bg-[var(--pm-accent)]/10 px-3 py-1
                            text-xs font-semibold
                            text-[var(--pm-accent)]
                        "
                    ></span>
                </div>
            </div>

            <div class="max-w-sm text-sm leading-6 text-[var(--pm-text-muted)]">
                <span data-i18n="license.upgrade_hint">
                    To subscribe, extend or change plans, contact
                </span>
                <a
                    class="font-medium text-[var(--pm-accent)] underline"
                    href="mailto:billing@patrimoine365.com"
                >billing@patrimoine365.com</a>
            </div>
        </div>

        {{-- Usage meters --}}
        <div
            id="license-usage"
            class="
                mt-6 grid gap-4
                sm:grid-cols-2 xl:grid-cols-4
            "
        ></div>
    </div>

    <p
        class="
            mt-6 text-sm leading-6
            text-[var(--pm-text-muted)]
        "
        data-i18n="license.footnotes"
    >
        Going over a limit only pauses creating new records — your existing
        data is never touched. Financial integrity and transactional
        document email are identical on every plan, and sign-in email is
        never blocked.
    </p>
</div>
