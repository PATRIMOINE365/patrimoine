@extends('layouts.admin')

@section('title', 'Administration — Patrimoine 365')

@section('content')

<div id="admin-workspace">

    <div
        id="admin-error"
        class="
            pm-auth-error
            mb-5 hidden rounded-lg
            px-4 py-3 text-sm
        "
    ></div>

    {{-- ========================= Dashboard ========================= --}}
    <section id="admin-section-dashboard" data-admin-section hidden>
        <div class="pm-admin-eyebrow">Workspace</div>
        <h1 class="pm-admin-title">Dashboard</h1>
        <p class="pm-admin-subtitle">The platform at a glance.</p>

        <div id="admin-metrics" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"></div>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <div class="pm-admin-card">
                <h2 class="pm-admin-card-title">Expiring within 14 days</h2>
                <div id="admin-expiring" class="mt-3"></div>
            </div>

            <div class="pm-admin-card">
                <h2 class="pm-admin-card-title">Email usage this month</h2>
                <div id="admin-email-usage" class="mt-3"></div>
            </div>
        </div>
    </section>

    {{-- ====================== Organizations ====================== --}}
    <section id="admin-section-organizations" data-admin-section hidden>
        <div class="pm-admin-eyebrow">Customers</div>
        <h1 class="pm-admin-title">Organizations</h1>
        <p class="pm-admin-subtitle">See workspaces, usage, and account health.</p>

        <div class="pm-admin-card pm-admin-card-flush mt-6">
            <div class="pm-admin-card-header">
                <span class="flex items-center">
                    <span class="pm-admin-card-title">All organizations</span>
                    <span id="admin-orgs-count" class="pm-admin-count-pill"></span>
                </span>

                <span class="flex items-center gap-3">
                    <input
                        id="admin-search"
                        type="search"
                        class="pm-input w-72"
                        placeholder="Search organization…"
                    >

                    <select id="admin-status-filter" class="pm-input w-40">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="pm-admin-table min-w-[820px]">
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th>Account</th>
                            <th>Users</th>
                            <th>Active leases</th>
                            <th>Plan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="admin-orgs-body"></tbody>
                </table>
            </div>

            <div id="admin-pagination" class="pm-admin-card-footer"></div>
        </div>
    </section>

    {{-- ========================= Licenses ========================= --}}
    <section id="admin-section-licenses" data-admin-section hidden>
        <div class="pm-admin-eyebrow">Entitlements</div>
        <h1 class="pm-admin-title">Licenses</h1>
        <p class="pm-admin-subtitle">Assign, track, and manage customer subscriptions.</p>

        <div id="admin-license-metrics" class="mt-6 grid gap-4 sm:grid-cols-3"></div>

        <div class="pm-admin-card pm-admin-card-flush mt-6">
            <div class="pm-admin-card-header">
                <span class="pm-admin-card-title">Subscriptions</span>

                <span class="flex items-center gap-3">
                    <input
                        id="admin-license-search"
                        type="search"
                        class="pm-input w-72"
                        placeholder="Search organization…"
                    >

                    <button type="button" class="pm-button-secondary" data-admin-assign>
                        Assign License
                    </button>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="pm-admin-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th>Subscription</th>
                            <th>Period</th>
                            <th>Consumption</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="admin-licenses-body"></tbody>
                </table>
            </div>

            <div id="admin-licenses-pagination" class="pm-admin-card-footer"></div>
        </div>
    </section>

    {{-- ========================= Activity ========================= --}}
    <section id="admin-section-activity" data-admin-section hidden>
        <div class="pm-admin-eyebrow">Operations</div>
        <h1 class="pm-admin-title">Activity</h1>
        <p class="pm-admin-subtitle">Every console action, newest first — the platform's own audit trail.</p>

        <div class="pm-admin-card pm-admin-card-flush mt-6">
            <div class="overflow-x-auto">
                <table class="pm-admin-table min-w-[820px]">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Customer</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody id="admin-activity-body"></tbody>
                </table>
            </div>

            <div id="admin-activity-pagination" class="pm-admin-card-footer"></div>
        </div>
    </section>

    {{-- ========================== Users =========================== --}}
    <section id="admin-section-users" data-admin-section hidden>
        <div class="pm-admin-eyebrow">Workspace</div>
        <h1 class="pm-admin-title">Users</h1>
        <p class="pm-admin-subtitle">The staff who operate the platform.</p>

        <div class="pm-admin-card pm-admin-card-flush mt-6">
            <div class="pm-admin-card-header">
                <span class="flex items-center">
                    <span class="pm-admin-card-title">Team members</span>
                    <span id="admin-staff-count" class="pm-admin-count-pill"></span>
                </span>

                <button id="admin-invite-staff" type="button" class="pm-button-secondary">
                    Invite staff
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="pm-admin-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>State</th>
                        </tr>
                    </thead>
                    <tbody id="admin-staff-body"></tbody>
                </table>
            </div>

            <div class="pm-admin-card-footer">
                <span class="text-[var(--pm-text-muted)]">
                    Staff accounts must use an @patrimoine365.com address; invitations arrive by email.
                </span>
            </div>
        </div>
    </section>

    {{-- ========================= Settings ========================= --}}
    <section id="admin-section-settings" data-admin-section hidden>
        <div class="pm-admin-eyebrow">Operations</div>
        <h1 class="pm-admin-title">Settings</h1>
        <p class="pm-admin-subtitle">Platform identity and legal versions.</p>

        <div class="pm-admin-card mt-6">
            <h2 class="pm-admin-card-title">Platform identity</h2>

            <dl class="mt-4 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-[var(--pm-text-muted)]">Product</dt>
                    <dd class="mt-0.5 font-medium text-[var(--pm-text)]">{{ config('legal.product.name') }}</dd>
                </div>
                <div>
                    <dt class="text-[var(--pm-text-muted)]">Domain</dt>
                    <dd class="mt-0.5 font-medium text-[var(--pm-text)]">{{ config('legal.product.domain') }}</dd>
                </div>
                <div>
                    <dt class="text-[var(--pm-text-muted)]">Signup alerts</dt>
                    <dd class="mt-0.5 font-medium text-[var(--pm-text)]">{{ config('legal.mailboxes.hello') }}</dd>
                </div>
                <div>
                    <dt class="text-[var(--pm-text-muted)]">Expiry digest (Mondays)</dt>
                    <dd class="mt-0.5 font-medium text-[var(--pm-text)]">{{ config('legal.mailboxes.billing') }}</dd>
                </div>
                <div>
                    <dt class="text-[var(--pm-text-muted)]">Terms version</dt>
                    <dd class="mt-0.5 font-medium text-[var(--pm-text)]">{{ config('legal.terms_version') }}</dd>
                </div>
                <div>
                    <dt class="text-[var(--pm-text-muted)]">Privacy version</dt>
                    <dd class="mt-0.5 font-medium text-[var(--pm-text)]">{{ config('legal.privacy_version') }}</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- ==================== Organisation detail ==================== --}}
    <section id="admin-section-organisation" data-admin-section hidden>

        <button
            id="admin-back"
            type="button"
            class="
                inline-flex items-center gap-2
                text-sm font-medium text-[var(--pm-accent)]
            "
        >
            ← All organizations
        </button>

        <div class="pm-admin-card mt-4">
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
                    <button id="admin-issue-license" type="button" class="pm-button-primary">
                        Assign License
                    </button>

                    <button id="admin-suspend" type="button" class="pm-button-secondary">
                        Suspend
                    </button>

                    <button id="admin-reactivate" type="button" class="pm-button-secondary hidden">
                        Reactivate
                    </button>

                    <button id="admin-delete" type="button" class="pm-button-danger">
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

        <div class="pm-admin-card pm-admin-card-flush mt-6">
            <div class="pm-admin-card-header">
                <span class="pm-admin-card-title">License history</span>
            </div>

            <div class="overflow-x-auto">
                <table class="pm-admin-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Starts</th>
                            <th>Expires</th>
                            <th>Payment</th>
                            <th>State</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="admin-detail-licenses"></tbody>
                </table>
            </div>
        </div>

        <div class="pm-admin-card pm-admin-card-flush mt-6">
            <div class="pm-admin-card-header">
                <span class="pm-admin-card-title">Users</span>
            </div>

            <div class="overflow-x-auto">
                <table class="pm-admin-table min-w-[760px]">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>State</th>
                            <th>Support</th>
                        </tr>
                    </thead>
                    <tbody id="admin-detail-users"></tbody>
                </table>
            </div>
        </div>

    </section>

</div>

@endsection

@section('drawers')

{{-- ===================== Assign licence drawer ===================== --}}
<x-drawer
    id="admin-license-modal"
    backdrop-id="admin-license-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="admin-license-close"
        close-label="Close"
    >
        <x-slot:title>Assign License</x-slot:title>
        <x-slot:description>
            Grant or extend a customer subscription.
        </x-slot:description>
    </x-drawer-header>

    <form id="admin-license-form" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-organisation">Organization</label>
                <select id="admin-license-organisation" class="pm-input" required>
                    <option value="">Select an organization</option>
                </select>
            </div>

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
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-license-notes">Internal note</label>
                <input id="admin-license-notes" type="text" class="pm-input" placeholder="Reason for assigning this license…">
            </div>

            <p class="rounded-lg bg-[var(--pm-hover)] px-4 py-3 text-xs leading-5 text-[var(--pm-text-muted)]">
                The organization's administrators are notified by email as
                soon as the license is assigned.
            </p>
        </div>

        <x-drawer-footer>
            <button id="admin-license-cancel" type="button" class="pm-button-secondary">Cancel</button>
            <button id="admin-license-submit" type="submit" class="pm-button-primary">Assign License</button>
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
        <x-slot:title>Suspend organization</x-slot:title>
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
        <x-slot:title>Delete organization permanently</x-slot:title>
        <x-slot:description>
            <span id="admin-delete-org"></span>
        </x-slot:description>
    </x-drawer-header>

    <form id="admin-delete-form" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div
                id="admin-delete-error"
                class="pm-auth-error hidden rounded-lg px-4 py-3 text-sm"
            ></div>

            <p class="text-sm leading-6 text-[var(--pm-text-secondary)]">
                This destroys the organization and <strong>every row it
                owns</strong> — properties, leases, financial history,
                users, licenses. There is no undo and no recycle bin.
                The organization must already be suspended.
            </p>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-delete-name">Type the organization's exact name</label>
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

{{-- ======================= My profile drawer ======================= --}}
<x-drawer
    id="admin-profile-modal"
    backdrop-id="admin-profile-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="admin-profile-close"
        close-label="Close"
    >
        <x-slot:title>My profile</x-slot:title>
        <x-slot:description>
            Your details, password and photo.
        </x-slot:description>
    </x-drawer-header>

    <div class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-8 overflow-y-auto px-6 py-6">

            <div
                id="admin-profile-feedback"
                class="hidden rounded-lg px-4 py-3 text-sm"
            ></div>

            {{-- ------------------------- Photo ------------------------- --}}
            <div>
                <div class="pm-field-label mb-3 text-sm font-medium">Profile photo</div>

                <div class="flex items-center gap-4">
                    <span id="admin-profile-avatar" class="pm-admin-user-avatar !h-16 !w-16 text-lg">
                        <img id="admin-profile-avatar-img" alt="" class="hidden h-full w-full rounded-full object-cover">
                        <span id="admin-profile-avatar-initials"></span>
                    </span>

                    <span class="flex flex-wrap items-center gap-2">
                        <label class="pm-button-secondary cursor-pointer">
                            Upload photo
                            <input
                                id="admin-profile-photo-input"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,.jpg,.jpeg,.png,.webp,.gif,.heic,.heif"
                                class="hidden"
                            >
                        </label>

                        <button id="admin-profile-photo-remove" type="button" class="pm-button-secondary hidden">
                            Remove
                        </button>
                    </span>
                </div>

                {{-- Crop stage: drag the square to frame the photo. --}}
                <div id="admin-profile-crop" class="mt-4 hidden">
                    <p class="mb-2 text-sm text-[var(--pm-text-muted)]">
                        Drag the square to frame your photo.
                    </p>

                    <div id="admin-profile-crop-stage" class="pm-admin-crop-stage">
                        <img id="admin-profile-crop-img" alt="" draggable="false">
                        <div id="admin-profile-crop-box" class="pm-admin-crop-box"></div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button id="admin-profile-photo-save" type="button" class="pm-button-primary">
                            Save photo
                        </button>

                        <button id="admin-profile-photo-cancel" type="button" class="pm-button-secondary">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            {{-- ---------------------- Details form --------------------- --}}
            <form id="admin-profile-form" class="space-y-5 border-t border-[var(--pm-border)] pt-6">
                <div class="pm-field-label text-sm font-medium">Details</div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-profile-given">Given names</label>
                        <input id="admin-profile-given" type="text" class="pm-input" required>
                    </div>
                    <div>
                        <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-profile-surname">Surname</label>
                        <input id="admin-profile-surname" type="text" class="pm-input" required>
                    </div>
                </div>

                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-profile-email">Email address</label>
                    <input id="admin-profile-email" type="email" class="pm-input" required>
                    <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                        Staff accounts stay on @patrimoine365.com.
                    </p>
                </div>

                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-profile-phone">Phone</label>
                    <input id="admin-profile-phone" type="tel" class="pm-input">
                </div>

                <button id="admin-profile-save" type="submit" class="pm-button-primary">
                    Save details
                </button>
            </form>

            {{-- ------------------------ Password ------------------------ --}}
            <form id="admin-password-form" class="space-y-5 border-t border-[var(--pm-border)] pt-6">
                <div class="pm-field-label text-sm font-medium">Change password</div>

                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-password-current">Current password</label>
                    <input id="admin-password-current" type="password" autocomplete="current-password" class="pm-input" required>
                </div>

                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-password-new">New password</label>
                    <input id="admin-password-new" type="password" autocomplete="new-password" minlength="10" class="pm-input" required>
                </div>

                <div>
                    <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-password-confirm">Confirm new password</label>
                    <input id="admin-password-confirm" type="password" autocomplete="new-password" minlength="10" class="pm-input" required>
                </div>

                <p class="text-xs text-[var(--pm-text-muted)]">
                    Changing your password signs you out everywhere, including here.
                </p>

                <button id="admin-password-save" type="submit" class="pm-button-secondary">
                    Update password
                </button>
            </form>

        </div>
    </div>
</x-drawer>

{{-- ===================== Invite staff drawer ===================== --}}
<x-drawer
    id="admin-staff-modal"
    backdrop-id="admin-staff-backdrop"
    width="sm"
>
    <x-drawer-header
        close-id="admin-staff-close"
        close-label="Close"
    >
        <x-slot:title>Invite staff</x-slot:title>
        <x-slot:description>
            The address must be @patrimoine365.com.
        </x-slot:description>
    </x-drawer-header>

    <form id="admin-staff-form" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-staff-name">Full name</label>
                <input id="admin-staff-name" type="text" class="pm-input" required>
            </div>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-staff-email">Email address</label>
                <input id="admin-staff-email" type="email" class="pm-input" placeholder="name@patrimoine365.com" required>
            </div>

            <div>
                <label class="pm-field-label mb-2 block text-sm font-medium" for="admin-staff-role">Role</label>
                <select id="admin-staff-role" class="pm-input" required>
                    <option value="administrator">Administrator</option>
                    <option value="viewer">Viewer</option>
                </select>
            </div>
        </div>

        <x-drawer-footer>
            <button id="admin-staff-cancel" type="button" class="pm-button-secondary">Cancel</button>
            <button id="admin-staff-submit" type="submit" class="pm-button-primary">Send invitation</button>
        </x-drawer-footer>
    </form>
</x-drawer>

@endsection
