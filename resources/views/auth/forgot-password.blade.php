@extends('layouts.auth')

@section('title_key', 'password.forgot_title')
@section('title_fallback', 'Forgot password — Patrimoine')

@section('content')
<div id="forgot-password-workspace" class="pm-auth-password-page">
    <div class="mb-9">
        <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
            <span data-i18n="password.forgot_heading">
                Forgot your password?
            </span>
        </h2>

        <p class="pm-auth-description mt-2 text-sm leading-6">
            <span data-i18n="password.forgot_description">
                Enter your email address and we will send you a password reset link.
            </span>
        </p>
    </div>

    <div
        id="forgot-password-message"
        class="
            pm-auth-message
            mb-5 hidden rounded-lg
            px-4 py-3 text-sm
        "
        role="status"
    ></div>

    <form id="forgot-password-form" class="space-y-5">
        <div>
            <label
                for="forgot-email"
                class="pm-field-label mb-2 block text-sm font-medium"
            >
                <span data-i18n="login.email">Email address</span>
            </label>

            <input
                id="forgot-email"
                type="email"
                autocomplete="email"
                required
                autofocus
                class="pm-input"
                data-i18n-placeholder="login.email_placeholder"
                placeholder="name@example.com"
            >
        </div>

        <button
            id="forgot-password-button"
            type="submit"
            class="pm-button-primary w-full disabled:cursor-wait"
        >
            <span data-i18n="password.send_reset">
                Send reset link
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
