@extends('layouts.auth')

@section('title_key', 'password.invitation_title')
@section('title_fallback', 'Set password — Patrimoine')

@section('content')
<div id="invitation-workspace">
    <div class="mb-9">
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950">
            <span data-i18n="password.invitation_heading">
                Set your password
            </span>
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            <span data-i18n="password.invitation_description">
                Create a password to activate your Patrimoine account.
            </span>
        </p>
    </div>

    <div
        id="invitation-message"
        class="mb-5 hidden rounded-lg border px-4 py-3 text-sm"
        role="status"
    ></div>

    <form id="invitation-form" class="space-y-5">
        <div>
            <label
                for="invitation-email"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="login.email">Email address</span>
            </label>

            <input
                id="invitation-email"
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
                for="invitation-password"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="password.new_password">
                    New password
                </span>
            </label>

            <input
                id="invitation-password"
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
                for="invitation-password-confirmation"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="password.confirm_password">
                    Confirm password
                </span>
            </label>

            <input
                id="invitation-password-confirmation"
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
            id="invitation-button"
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
            <span data-i18n="password.set_password">
                Set password
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
