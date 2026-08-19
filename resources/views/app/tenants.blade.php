@extends('layouts.app')

@section('title', __('ui.tenants.title'))
@section('title-i18n', 'tenants.title')

@section('content')

<div
    id="tenant-workspace"
    class="pm-tenants-page mx-auto max-w-[1600px]"
>
    <div
        class="
            mb-8 flex flex-col gap-5
            lg:flex-row lg:items-end lg:justify-between
        "
    >
        <div>
            <p
                class="
                    text-sm font-medium
                    text-patrimoine-700
                "
            >
                <span data-i18n="tenants.finance">{{ __('ui.tenants.finance') }}</span>
            </p>

            <h1
                class="
                    mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950
                "
            >
                <span data-i18n="tenants.heading">{{ __('ui.tenants.heading') }}</span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                <span data-i18n="tenants.page_description">{{ __('ui.tenants.page_description') }}</span>
            </p>
        </div>
    </div>

    <div
        id="tenant-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    <div
        class="pm-tenants-workspace"
    >
        <section class="pm-tenant-directory">
            <div class="pm-tenant-directory-header">
                <h2
                    class="
                        text-base font-semibold
                        text-slate-950
                    "
                >
                    <span data-i18n="tenants.directory">{{ __('ui.tenants.directory') }}</span>
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    <span data-i18n="tenants.search_description">
                        {{ __('ui.tenants.search_description') }}
                    </span>
                </p>

                <div class="mt-4">
                    <label
                        for="tenant-search"
                        class="sr-only"
                    >
                        <span data-i18n="tenants.search">{{ __('ui.tenants.search') }}</span>
                    </label>

                    <input
                        id="tenant-search"
                        type="search"
                        autocomplete="off"
                        placeholder="{{ __('ui.tenants.search_placeholder') }}"
                        data-i18n-placeholder="tenants.search_placeholder"
                        class="pm-input pm-tenant-search-input"
                    >
                </div>
            </div>

            <div
                id="tenant-list"
                class="pm-tenant-directory-list"
            >
                <div
                    class="
                        px-5 py-8 text-center
                        text-sm text-slate-400
                    "
                >
                    <span data-i18n="tenants.loading">{{ __('ui.tenants.loading') }}</span>
                </div>
            </div>

            <div
                id="tenant-pagination"
                class="pm-tenant-directory-pagination hidden"
            ></div>
        </section>

        <section
            id="tenant-detail"
            class="pm-tenant-detail-shell"
        >
            <div
                class="
                    flex min-h-[620px]
                    items-center justify-center
                    px-6 py-16
                "
            >
                <div class="max-w-md text-center">
                    <div
                        class="
                            mx-auto flex h-12 w-12
                            items-center justify-center
                            rounded-full bg-slate-100
                            text-slate-500
                        "
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                            <path d="M17 11h4"/>
                        </svg>
                    </div>

                    <h2
                        class="
                            mt-4 text-base font-semibold
                            text-slate-900
                        "
                    >
                        <span data-i18n="tenants.select_tenant">{{ __('ui.tenants.select_tenant') }}</span>
                    </h2>

                    <p
                        class="
                            mt-2 text-sm leading-6
                            text-slate-500
                        "
                    >
                        <span data-i18n="tenants.select_tenant_description">{{ __('ui.tenants.select_tenant_description') }}</span>
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>

@endsection
