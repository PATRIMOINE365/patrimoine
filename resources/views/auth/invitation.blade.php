@extends('layouts.auth')

@section('title_key', 'password.invitation_title')
@section('title_fallback', 'Set password — Patrimoine')

@section('content')
<div id="invitation-workspace" class="pm-auth-password-page">
    <div class="mb-9">
        <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
            <span data-i18n="password.invitation_heading">
                Set your password
            </span>
        </h2>

        <p class="pm-auth-description mt-2 text-sm leading-6">
            <span data-i18n="password.invitation_description">
                Create a password to activate your Patrimoine account.
            </span>
        </p>
    </div>

    <div
        id="invitation-message"
        class="pm-auth-message mb-5 hidden rounded-lg px-4 py-3 text-sm"
        role="status"
    ></div>

    <form id="invitation-form" class="space-y-5">
        <div>
            <label
                for="invitation-email"
                class="pm-field-label mb-2 block text-sm font-medium"
            >
                <span data-i18n="login.email">Email address</span>
            </label>

            <input
                id="invitation-email"
                type="email"
                autocomplete="email"
                required
                class="pm-input"
            >
        </div>

        <div>
            <label
                for="invitation-password"
                class="pm-field-label mb-2 block text-sm font-medium"
            >
                <span data-i18n="password.new_password">
                    New password
                </span>
            </label>

            <input
                id="invitation-password"
                type="password"
                autocomplete="new-password"
                minlength="12"
                required
                class="pm-input"
            >
        </div>

        <div>
            <label
                for="invitation-password-confirmation"
                class="pm-field-label mb-2 block text-sm font-medium"
            >
                <span data-i18n="password.confirm_password">
                    Confirm password
                </span>
            </label>

            <input
                id="invitation-password-confirmation"
                type="password"
                autocomplete="new-password"
                minlength="12"
                required
                class="pm-input"
            >
        </div>

        <button
            id="invitation-button"
            type="submit"
            class="pm-button-primary w-full disabled:cursor-wait"
        >
            <span data-i18n="password.set_password">
                Set password
            </span>
        </button>
    </form>

    <p class="mt-7 text-center text-sm">
        <a
            href="/login"
            class="pm-auth-link font-medium"
            data-i18n="password.back_to_login"
        >
            Back to sign in
        </a>
    </p>
</div>
@endsection
