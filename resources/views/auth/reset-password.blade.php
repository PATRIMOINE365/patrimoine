@extends('layouts.auth')

@section('title_key', 'password.reset_title')
@section('title_fallback', 'Reset password — Patrimoine')

@section('content')
<div id="reset-password-workspace">
    <div class="mb-9">
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950">
            <span data-i18n="password.reset_heading">
                Reset your password
            </span>
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            <span data-i18n="password.reset_description">
                Choose a new password for your Patrimoine account.
            </span>
        </p>
    </div>

    <div
        id="reset-password-message"
        class="mb-5 hidden rounded-lg border px-4 py-3 text-sm"
        role="status"
    ></div>

    <form id="reset-password-form" class="space-y-5">
        <div>
            <label
                for="reset-email"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="login.email">Email address</span>
            </label>

            <input
                id="reset-email"
                type="email"
                autocomplete="email"
                required
                class="
                    block w-full rounded-lg
                    border border-slate-300 bg-white
                    px-3.5 py-3 text-sm text-slate-900
                    shadow-sm outline-none
                "
            >
        </div>

        <div>
            <label
                for="reset-password"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="password.new_password">
                    New password
                </span>
            </label>

            <input
                id="reset-password"
                type="password"
                autocomplete="new-password"
                minlength="8"
                required
                class="
                    block w-full rounded-lg
                    border border-slate-300 bg-white
                    px-3.5 py-3 text-sm text-slate-900
                    shadow-sm outline-none
                "
            >
        </div>

        <div>
            <label
                for="reset-password-confirmation"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="password.confirm_password">
                    Confirm password
                </span>
            </label>

            <input
                id="reset-password-confirmation"
                type="password"
                autocomplete="new-password"
                minlength="8"
                required
                class="
                    block w-full rounded-lg
                    border border-slate-300 bg-white
                    px-3.5 py-3 text-sm text-slate-900
                    shadow-sm outline-none
                "
            >
        </div>

        <button
            id="reset-password-button"
            type="submit"
            class="
                flex w-full items-center justify-center
                rounded-lg bg-patrimoine-950
                px-4 py-3 text-sm font-semibold text-white
                shadow-sm transition
                hover:bg-patrimoine-800
                disabled:cursor-not-allowed disabled:opacity-60
            "
        >
            <span data-i18n="password.reset_action">
                Reset password
            </span>
        </button>
    </form>

    <p class="mt-7 text-center text-sm">
        <a
            href="/login"
            class="font-medium text-patrimoine-800 hover:text-patrimoine-950"
            data-i18n="password.back_to_login"
        >
            Back to sign in
        </a>
    </p>
</div>
@endsection
