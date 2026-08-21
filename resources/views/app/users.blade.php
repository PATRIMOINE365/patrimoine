@extends('layouts.app')

@section('title', __('ui.users.title'))
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
                <span data-i18n="users.administration">{{ __('ui.users.administration') }}</span>
            </div>

            <h1
                class="
                    mt-2 text-2xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="users.heading">{{ __('ui.users.heading') }}</span>
            </h1>

            <p
                class="
                    mt-2 max-w-3xl
                    text-sm leading-6 text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="users.description">{{ __('ui.users.description') }}</span>
            </p>
        </div>

        <button
            id="add-user-button"
            type="button"
            class="pm-button-primary"
        >
            <span data-i18n="users.add_user">{{ __('ui.users.add_user') }}</span>
        </button>
    </div>

    <div
        id="users-error"
        class="
            mt-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
        role="alert"
    ></div>

    <div
        id="users-success"
        class="
            mt-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-success-border)]
            bg-[var(--pm-success-background)]
            text-[var(--pm-success-text)]
        "
        role="status"
    ></div>

    <section
        class="
            mt-7 rounded-xl
            border border-[var(--pm-border)]
            bg-[var(--pm-surface)]
        "
    >
        <div
            class="
                grid gap-4 border-b
                border-[var(--pm-border)]
                p-5
                md:grid-cols-3
            "
        >
            <div>
                <label
                    for="users-search"
                    class="pm-field-label"
                >
                    <span data-i18n="users.search">{{ __('ui.users.search') }}</span>
                </label>

                <input
                    id="users-search"
                    type="search"
                    data-i18n-placeholder="users.search_placeholder"
                    placeholder="{{ __('ui.users.search_placeholder') }}"
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="users-role-filter"
                    class="pm-field-label"
                >
                    <span data-i18n="users.role">{{ __('ui.users.role') }}</span>
                </label>

                <select
                    id="users-role-filter"
                    class="pm-input"
                >
                    <option value="" data-i18n="users.all_roles">{{ __('ui.users.all_roles') }}</option>
                    <option value="administrator" data-i18n="roles.administrator">{{ __('ui.roles.administrator') }}</option>
                    <option value="property_manager" data-i18n="roles.property_manager">{{ __('ui.roles.property_manager') }}</option>
                    <option value="viewer" data-i18n="roles.viewer">{{ __('ui.roles.viewer') }}</option>
                </select>
            </div>

            <div>
                <label
                    for="users-status-filter"
                    class="pm-field-label"
                >
                    <span data-i18n="users.status">{{ __('ui.users.status') }}</span>
                </label>

                <select
                    id="users-status-filter"
                    class="pm-input"
                >
                    <option value="" data-i18n="users.all_statuses">{{ __('ui.users.all_statuses') }}</option>
                    <option value="1" data-i18n="users.active">{{ __('ui.users.active') }}</option>
                    <option value="0" data-i18n="users.inactive">{{ __('ui.users.inactive') }}</option>
                </select>
            </div>
        </div>

        <div
            id="users-list"
            class="divide-y divide-[var(--pm-border)]"
        >
            <div
                class="
                    px-5 py-12 text-center
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="users.loading">{{ __('ui.users.loading') }}</span>
            </div>
        </div>

        <div
            id="users-pagination"
            class="
                hidden border-t
                border-[var(--pm-border)]
                px-5 py-4
            "
        ></div>
    </section>
</div>

{{-- User create/edit drawer --}}
<x-drawer
    id="user-modal"
    backdrop-id="user-modal-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="user-modal-title"
        description-id="user-modal-description"
        close-id="user-modal-close"
        close-label="Close"
        close-label-key="actions.close"
    >
        <x-slot:title>
            <span data-i18n="users.add_user">{{ __('ui.users.add_user') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span data-i18n="users.create_description">{{ __('ui.users.create_description') }}</span>
        </x-slot:description>
    </x-drawer-header>

    <form
        id="user-form"
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
                id="user-form-error"
                class="
                    mb-5 hidden rounded-lg
                    border px-4 py-3
                    text-sm
                    border-[var(--pm-danger-border)]
                    bg-[var(--pm-danger-background)]
                    text-[var(--pm-danger-text)]
                "
                role="alert"
            ></div>

            <div class="grid gap-5 sm:grid-cols-2">
                {{--
                    V1.0.7 structured names: the API accepts
                    given_names + surname and recomposes the
                    display name.
                --}}
                <div>
                    <label
                        for="user-given-names"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.given_names">Given names</span>
                    </label>

                    <input
                        id="user-given-names"
                        type="text"
                        maxlength="255"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="user-surname"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.surname">Surname</span>
                    </label>

                    <input
                        id="user-surname"
                        type="text"
                        maxlength="255"
                        required
                        class="pm-input"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label
                        for="user-email"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.email">{{ __('ui.users.email') }}</span>
                    </label>

                    <input
                        id="user-email"
                        type="email"
                        maxlength="255"
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="user-phone"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.phone">{{ __('ui.users.phone') }}</span>
                    </label>

                    <input
                        id="user-phone"
                        type="text"
                        maxlength="50"
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="user-role"
                        class="pm-field-label"
                    >
                        <span data-i18n="users.role">
                            Role
                        </span>
                    </label>

                    <select
                        id="user-role"
                        required
                        class="pm-input"
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
                        border-[var(--pm-border)]
                        bg-[var(--pm-surface-subtle)]
                        px-4 py-3
                    "
                >
                    <input
                        id="user-active"
                        type="checkbox"
                        checked
                        class="
                            h-4 w-4 rounded
                            border-[var(--pm-border-strong)]
                            text-patrimoine-700
                        "
                    >

                    <span>
                        <span
                            class="
                                block text-sm font-medium
                                text-[var(--pm-text)]
                            "
                            data-i18n="users.active_account"
                        >
                            Active account
                        </span>

                        <span
                            class="
                                mt-0.5 block text-xs
                                text-[var(--pm-text-muted)]
                            "
                            data-i18n="users.active_account_help"
                        >
                            Inactive users cannot sign in.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <x-drawer-footer>
            <button
                id="user-cancel-button"
                type="button"
                class="pm-button-secondary"
            >
                <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
            </button>

            <button
                id="user-submit-button"
                type="submit"
                class="pm-button-primary"
                data-i18n="actions.save"
            >
                {{ __('ui.actions.save') }}
            </button>
        </x-drawer-footer>
    </form>
</x-drawer>

@endsection
