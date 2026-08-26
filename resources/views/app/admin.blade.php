@extends('layouts.app')

@section('title', 'Administration — Patrimoine 365')

@section('content')

{{--
    V1.0.11 platform administration console (Kality Ltd staff only).

    Internal tool: deliberately English-only. The API behind every
    element is guarded by the platform.admin middleware; admin.js also
    redirects non-staff away from the shell.
--}}
<div
    id="admin-workspace"
    class="mx-auto max-w-[1120px]"
>
    <div>
        <div
            class="
                text-xs font-semibold uppercase
                tracking-[0.14em]
                text-[var(--pm-accent)]
            "
        >
            Platform
        </div>

        <h1
            class="
                mt-2 text-2xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
        >
            Administration console
        </h1>

        <p
            class="
                mt-2 max-w-3xl
                text-sm leading-6
                text-[var(--pm-text-muted)]
            "
        >
            Customer organisations, licences, suspensions and support —
            every action is recorded in both audit trails.
        </p>
    </div>

    <div
        id="admin-error"
        class="
            pm-auth-error
            mt-6 hidden rounded-lg
            px-4 py-3 text-sm
        "
    ></div>

    {{-- ============================ Overview ============================ --}}
    <div id="admin-overview">

        <div
            id="admin-metrics"
            class="
                mt-6 grid gap-4
                sm:grid-cols-2 xl:grid-cols-4
            "
        ></div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <div
                class="
                    rounded-2xl border border-[var(--pm-border)]
                    bg-[var(--pm-surface)] p-5
                "
            >
                <h2 class="text-sm font-semibold text-[var(--pm-text)]">
                    Expiring within 14 days
                </h2>

                <div id="admin-expiring" class="mt-3"></div>
            </div>

            <div
                class="
                    rounded-2xl border border-[var(--pm-border)]
                    bg-[var(--pm-surface)] p-5
                "
            >
                <h2 class="text-sm font-semibold text-[var(--pm-text)]">
                    Email usage this month
                </h2>

                <div id="admin-email-usage" class="mt-3"></div>
            </div>
        </div>

        {{-- ======================= Organisations ======================= --}}
        <div class="mt-10 flex flex-wrap items-center justify-between gap-3">
            <h2
                class="
                    text-lg font-semibold tracking-tight
                    text-[var(--pm-text)]
                "
            >
                Organisations
            </h2>

            <div class="flex items-center gap-3">
                <input
                    id="admin-search"
                    type="search"
                    class="pm-input w-64"
                    placeholder="Search by name…"
                >

                <select
                    id="admin-status-filter"
                    class="pm-input w-40"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table
                class="
                    w-full min-w-[760px] border-separate
                    border-spacing-0 text-sm
                "
            >
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-[var(--pm-text-muted)]">
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Organisation</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Status</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Plan</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Users</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Active leases</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Signed up</th>
                    </tr>
                </thead>
                <tbody id="admin-orgs-body"></tbody>
            </table>
        </div>

        <div
            id="admin-pagination"
            class="mt-4 flex items-center justify-between text-sm"
        ></div>

    </div>

    {{-- ========================= Detail view ========================= --}}
    <div id="admin-detail" class="hidden">

        <button
            id="admin-back"
            type="button"
            class="
                mt-6 inline-flex items-center gap-2
                text-sm font-medium text-[var(--pm-accent)]
            "
        >
            ← All organisations
        </button>

        <div
            class="
                mt-4 rounded-2xl border border-[var(--pm-border)]
                bg-[var(--pm-surface)] p-6
            "
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h2
                            id="admin-detail-name"
                            class="
                                text-xl font-semibold tracking-tight
                                text-[var(--pm-text)]
                            "
                        ></h2>

                        <span id="admin-detail-status"></span>
                    </div>

                    <p
                        id="admin-detail-meta"
                        class="mt-1 text-sm text-[var(--pm-text-muted)]"
                    ></p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        id="admin-issue-license"
                        type="button"
                        class="pm-button-primary"
                    >
                        Issue licence
                    </button>

                    <button
                        id="admin-suspend"
                        type="button"
                        class="pm-button-secondary"
                    >
                        Suspend
                    </button>

                    <button
                        id="admin-reactivate"
                        type="button"
                        class="pm-button-secondary hidden"
                    >
                        Reactivate
                    </button>

                    <button
                        id="admin-delete"
                        type="button"
                        class="pm-button-danger"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div
                id="admin-detail-usage"
                class="
                    mt-6 grid gap-4
                    sm:grid-cols-2 xl:grid-cols-4
                "
            ></div>
        </div>

        <h3 class="mt-8 text-sm font-semibold text-[var(--pm-text)]">
            Licence history
        </h3>

        <div class="mt-3 overflow-x-auto">
            <table
                class="
                    w-full min-w-[720px] border-separate
                    border-spacing-0 text-sm
                "
            >
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-[var(--pm-text-muted)]">
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Plan</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Starts</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Expires</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Payment</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">State</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5"></th>
                    </tr>
                </thead>
                <tbody id="admin-detail-licenses"></tbody>
            </table>
        </div>

        <h3 class="mt-8 text-sm font-semibold text-[var(--pm-text)]">
            Users
        </h3>

        <div class="mt-3 overflow-x-auto">
            <table
                class="
                    w-full min-w-[760px] border-separate
                    border-spacing-0 text-sm
                "
            >
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-[var(--pm-text-muted)]">
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Name</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Email</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Role</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">State</th>
                        <th class="border-b border-[var(--pm-border)] px-3 py-2.5">Support</th>
                    </tr>
                </thead>
                <tbody id="admin-detail-users"></tbody>
            </table>
        </div>

    </div>
</div>

{{-- ===================== Issue licence drawer ===================== --}}
<x-drawer
    id="admin-license-modal"
    backdrop-id="admin-license-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="admin-license-close"
        close-label="Close"
    >
        <x-slot:title>Issue licence</x-slot:title>
        <x-slot:description>
            <span id="admin-license-org"></span>
        </x-slot:description>
    </x-drawer-header>

    <form id="admin-license-form" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-plan">Plan</label>
                <select id="admin-license-plan" class="pm-input" required>
                    <option value="standard">Standard</option>
                    <option value="professional">Professional</option>
                    <option value="free">Free</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-starts">Starts</label>
                    <input id="admin-license-starts" type="date" class="pm-input" required>
                </div>
                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-expires">Expires (empty = never)</label>
                    <input id="admin-license-expires" type="date" class="pm-input">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-amount">Amount received</label>
                    <input id="admin-license-amount" type="number" min="0" class="pm-input">
                </div>
                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-currency">Currency</label>
                    <input id="admin-license-currency" type="text" maxlength="10" class="pm-input" placeholder="USD">
                </div>
            </div>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-method">Payment method</label>
                <select id="admin-license-method" class="pm-input">
                    <option value="">—</option>
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="momo">Mobile money</option>
                    <option value="cash">Cash</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-reference">Payment reference</label>
                <input id="admin-license-reference" type="text" class="pm-input">
            </div>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-notes">Note</label>
                <input id="admin-license-notes" type="text" class="pm-input">
            </div>

            <p class="text-xs leading-5 text-[var(--pm-text-muted)]">
                The organisation's administrators are notified by email
                as soon as the licence is issued.
            </p>
        </div>

        <x-drawer-footer>
            <button id="admin-license-cancel" type="button" class="pm-button-secondary">Cancel</button>
            <button id="admin-license-submit" type="submit" class="pm-button-primary">Issue licence</button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ======================= Suspend drawer ======================= --}}
<x-drawer
    id="admin-suspend-modal"
    backdrop-id="admin-suspend-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="admin-suspend-close"
        close-label="Close"
    >
        <x-slot:title>Suspend organisation</x-slot:title>
        <x-slot:description>
            <span id="admin-suspend-org"></span>
        </x-slot:description>
    </x-drawer-header>

    <form id="admin-suspend-form" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <p class="text-sm leading-6 text-[var(--pm-text-secondary)]">
                Sign-in and API access stop immediately; every record is
                preserved and reactivation is a single click.
            </p>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-suspend-reason">Reason (internal)</label>
                <input id="admin-suspend-reason" type="text" class="pm-input">
            </div>
        </div>

        <x-drawer-footer>
            <button id="admin-suspend-cancel" type="button" class="pm-button-secondary">Cancel</button>
            <button id="admin-suspend-submit" type="submit" class="pm-button-danger">Suspend</button>
        </x-drawer-footer>
    </form>
</x-drawer>

{{-- ======================== Delete drawer ======================== --}}
<x-drawer
    id="admin-delete-modal"
    backdrop-id="admin-delete-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="admin-delete-close"
        close-label="Close"
    >
        <x-slot:title>Delete organisation permanently</x-slot:title>
        <x-slot:description>
            <span id="admin-delete-org"></span>
        </x-slot:description>
    </x-drawer-header>

    <form id="admin-delete-form" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <p class="text-sm leading-6 text-[var(--pm-text-secondary)]">
                This destroys the organisation and <strong>every row it
                owns</strong> — properties, leases, financial history,
                users, licences. There is no undo and no recycle bin.
                The organisation must already be suspended.
            </p>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-delete-name">Type the organisation's exact name</label>
                <input id="admin-delete-name" type="text" class="pm-input" autocomplete="off" required>
            </div>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-delete-password">Your password</label>
                <input id="admin-delete-password" type="password" class="pm-input" autocomplete="current-password" required>
            </div>
        </div>

        <x-drawer-footer>
            <button id="admin-delete-cancel" type="button" class="pm-button-secondary">Cancel</button>
            <button id="admin-delete-submit" type="submit" class="pm-button-danger">Delete permanently</button>
        </x-drawer-footer>
    </form>
</x-drawer>

@endsection
