@extends('layouts.app')

@section('title', 'Users — Patrimoine')
@section('title-i18n', 'users.title')

@section('content')

<div
    id="users-workspace"
    data-requires-role="administrator"
>
    <div
        class="
            flex flex-col gap-5
            sm:flex-row
            sm:items-start
            sm:justify-between
        "
    >
        <div>
            <div
                class="
                    text-xs font-semibold uppercase
                    tracking-[0.14em]
                    text-patrimoine-700
                "
            >
                <span data-i18n="users.administration">
                    Administration
                </span>
            </div>

            <h1
                class="
                    mt-2 text-2xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="users.heading">
                    User Management
                </span>
            </h1>

            <p
                class="
                    mt-2 max-w-3xl
                    text-sm leading-6 text-slate-500
                "
            >
                <span data-i18n="users.description">
                    Manage application users, roles and account access.
                </span>
            </p>
        </div>

        <button
            id="add-user-button"
            type="button"
            class="
                inline-flex items-center justify-center
                rounded-lg bg-patrimoine-800
                px-4 py-2.5
                text-sm font-medium text-white
                shadow-sm transition
                hover:bg-patrimoine-900
            "
        >
            <span data-i18n="users.add_user">
                Add User
            </span>
        </button>
    </div>

    <div
        id="users-error"
        class="
            mt-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
        role="alert"
    ></div>

    <div
        id="users-success"
        class="
            mt-6 hidden rounded-xl
            border border-emerald-200
            bg-emerald-50 px-4 py-3
            text-sm text-emerald-700
        "
        role="status"
    ></div>

    <section
        class="
            mt-7 rounded-xl
            border border-slate-200
            bg-white
        "
    >
        <div
            class="
                grid gap-4 border-b
                border-slate-200
                p-5
                md:grid-cols-3
            "
        >
            <div>
                <label
                    for="users-search"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="users.search">
                        Search
                    </span>
                </label>

                <input
                    id="users-search"
                    type="search"
                    data-i18n-placeholder="users.search_placeholder"
                    placeholder="Search name, email or phone..."
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                        outline-none transition
                        focus:border-patrimoine-500
                        focus:ring-2
                        focus:ring-patrimoine-100
                    "
                >
            </div>

            <div>
                <label
                    for="users-role-filter"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="users.role">
                        Role
                    </span>
                </label>

                <select
                    id="users-role-filter"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
                    <option value="" data-i18n="users.all_roles">
                        All roles
                    </option>
                    <option value="administrator" data-i18n="roles.administrator">
                        Administrator
                    </option>
                    <option value="property_manager" data-i18n="roles.property_manager">
                        Property Manager
                    </option>
                    <option value="viewer" data-i18n="roles.viewer">
                        Viewer
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="users-status-filter"
                    class="
                        mb-1.5 block
                        text-xs font-medium
                        text-slate-600
                    "
                >
                    <span data-i18n="users.status">
                        Status
                    </span>
                </label>

                <select
                    id="users-status-filter"
                    class="
                        w-full rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm text-slate-900
                    "
                >
                    <option value="" data-i18n="users.all_statuses">
                        All statuses
                    </option>
                    <option value="1" data-i18n="users.active">
                        Active
                    </option>
                    <option value="0" data-i18n="users.inactive">
                        Inactive
                    </option>
                </select>
            </div>
        </div>

        <div
            id="users-list"
            class="divide-y divide-slate-100"
        >
            <div
                class="
                    px-5 py-12 text-center
                    text-sm text-slate-400
                "
            >
                <span data-i18n="users.loading">
                    Loading users...
                </span>
            </div>
        </div>

        <div
            id="users-pagination"
            class="
                hidden border-t
                border-slate-200
                px-5 py-4
            "
        ></div>
    </section>
</div>

{{-- User create/edit modal --}}
<div
    id="user-modal"
    class="
        fixed inset-0 z-[70]
        hidden items-center justify-center
        p-4
    "
    aria-hidden="true"
>
    <div
        id="user-modal-backdrop"
        class="
            absolute inset-0
            bg-slate-950/50
            backdrop-blur-[1px]
        "
    ></div>

    <div
        class="
            relative z-10
            w-full max-w-xl
            rounded-2xl bg-white
            shadow-2xl
        "
    >
        <div
            class="
                flex items-start
                justify-between gap-5
                border-b border-slate-200
                px-6 py-5
            "
        >
            <div>
                <h2
                    id="user-modal-title"
                    class="
                        text-lg font-semibold
                        text-slate-950
                    "
                >
                    Add User
                </h2>

                <p
                    id="user-modal-description"
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                >
                    Create an application user and send an invitation.
                </p>
            </div>

            <button
                id="user-modal-close"
                type="button"
                class="
                    rounded-lg p-2
                    text-slate-400
                    hover:bg-slate-100
                    hover:text-slate-700
                "
                data-i18n-aria-label="users.close"
                aria-label="Close"
            >
                ✕
            </button>
        </div>

        <form
            id="user-form"
            class="p-6"
        >
            <div
                id="user-form-error"
                class="
                    mb-5 hidden rounded-lg
                    border border-red-200
                    bg-red-50 px-4 py-3
                    text-sm text-red-700
                "
                role="alert"
            ></div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label
                        for="user-name"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="users.name">
                            Name
                        </span>
                    </label>

                    <input
                        id="user-name"
                        type="text"
                        maxlength="255"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3 py-2.5
                            text-sm
                        "
                    >
                </div>

                <div class="sm:col-span-2">
                    <label
                        for="user-email"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="users.email">
                            Email
                        </span>
                    </label>

                    <input
                        id="user-email"
                        type="email"
                        maxlength="255"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3 py-2.5
                            text-sm
                        "
                    >
                </div>

                <div>
                    <label
                        for="user-phone"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="users.phone">
                            Phone
                        </span>
                    </label>

                    <input
                        id="user-phone"
                        type="text"
                        maxlength="50"
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            px-3 py-2.5
                            text-sm
                        "
                    >
                </div>

                <div>
                    <label
                        for="user-role"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        <span data-i18n="users.role">
                            Role
                        </span>
                    </label>

                    <select
                        id="user-role"
                        required
                        class="
                            w-full rounded-lg
                            border border-slate-200
                            bg-white px-3 py-2.5
                            text-sm
                        "
                    >
                        <option value="administrator" data-i18n="roles.administrator">
                            Administrator
                        </option>
                        <option value="property_manager" data-i18n="roles.property_manager">
                            Property Manager
                        </option>
                        <option value="viewer" data-i18n="roles.viewer">
                            Viewer
                        </option>
                    </select>
                </div>

                <label
                    class="
                        sm:col-span-2
                        flex items-center gap-3
                        rounded-lg border
                        border-slate-200
                        px-4 py-3
                    "
                >
                    <input
                        id="user-active"
                        type="checkbox"
                        checked
                        class="
                            h-4 w-4 rounded
                            border-slate-300
                            text-patrimoine-700
                        "
                    >

                    <span>
                        <span
                            class="
                                block text-sm
                                font-medium text-slate-800
                            "
                            data-i18n="users.active_account"
                        >
                            Active account
                        </span>

                        <span
                            class="
                                mt-0.5 block
                                text-xs text-slate-500
                            "
                            data-i18n="users.active_account_help"
                        >
                            Inactive users cannot sign in.
                        </span>
                    </span>
                </label>
            </div>

            <div
                class="
                    mt-7 flex
                    justify-end gap-3
                "
            >
                <button
                    id="user-cancel-button"
                    type="button"
                    class="
                        rounded-lg border
                        border-slate-200
                        bg-white px-4 py-2.5
                        text-sm font-medium
                        text-slate-700
                        hover:bg-slate-50
                    "
                >
                    <span data-i18n="users.cancel">
                        Cancel
                    </span>
                </button>

                <button
                    id="user-submit-button"
                    type="submit"
                    class="
                        rounded-lg
                        bg-patrimoine-800
                        px-4 py-2.5
                        text-sm font-medium
                        text-white
                        hover:bg-patrimoine-900
                        disabled:opacity-50
                    "
                >
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
