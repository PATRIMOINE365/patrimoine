@extends('layouts.auth')

@section('title_key', 'login.title')
@section('title_fallback', 'Sign in — Patrimoine')

@section('content')

<div class="pm-auth-login">

    {{-- ============================================================
         Step 1 — email + password
         ============================================================ --}}
    <div id="login-step-credentials">

        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="login.welcome">
                    {{ __('ui.login.welcome') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="login.description">
                    {{ __('ui.login.description') }}
                </span>
            </p>
        </div>

        <div
            id="login-error"
            class="
                pm-auth-error
                mb-5 hidden rounded-lg
                px-4 py-3 text-sm
            "
        ></div>

        {{-- Revealed only when the account still needs email verification --}}
        <div id="login-resend" class="mb-5 hidden">
            <button
                type="button"
                id="login-resend-button"
                class="pm-button-secondary w-full disabled:cursor-wait"
                data-i18n="login.resend_verification"
            >
                {{ __('ui.login.resend_verification') }}
            </button>

            <p
                id="login-resend-feedback"
                class="pm-auth-description mt-2 hidden text-sm"
            ></p>
        </div>

        <form
            id="login-form"
            class="space-y-5"
        >
            <div>
                <label
                    for="email"
                    class="
                        pm-field-label
                        mb-2 block text-sm font-medium
                    "
                >
                    <span data-i18n="login.email">
                        {{ __('ui.login.email') }}
                    </span>
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    autofocus
                    class="pm-input"
                    data-i18n-placeholder="login.email_placeholder"
                    placeholder="name@example.com"
                >
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label
                        for="password"
                        class="
                            pm-field-label
                            block text-sm font-medium
                        "
                    >
                        <span data-i18n="login.password">
                            {{ __('ui.login.password') }}
                        </span>
                    </label>

                    <a
                        href="/forgot-password"
                        class="
                            pm-auth-link
                            text-xs font-medium
                        "
                        data-i18n="password.forgot_link"
                    >
                        {{ __('ui.password.forgot_link') }}
                    </a>
                </div>

                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="pm-input"
                    data-i18n-placeholder="login.password_placeholder"
                    placeholder="Enter your password"
                >
            </div>

            <button
                id="login-button"
                type="submit"
                class="pm-button-primary w-full disabled:cursor-wait"
            >
                <span data-i18n="login.sign_in">
                    {{ __('ui.login.sign_in') }}
                </span>
            </button>
        </form>

        <p class="pm-auth-description mt-6 text-center text-sm">
            <span data-i18n="login.no_account">
                {{ __('ui.login.no_account') }}
            </span>
            <a
                href="/signup"
                class="pm-auth-link font-medium"
                data-i18n="login.create_organisation"
            >
                {{ __('ui.login.create_organisation') }}
            </a>
        </p>

    </div>

    {{-- ============================================================
         Step 2 — emailed six-digit code (same box, revealed by JS)
         ============================================================ --}}
    <div id="login-step-mfa" class="hidden">

        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="login.mfa_heading">
                    {{ __('ui.login.mfa_heading') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="login.mfa_description">
                    {{ __('ui.login.mfa_description') }}
                </span>
                <span
                    id="mfa-email-hint"
                    class="font-medium"
                ></span>
            </p>
        </div>

        <div
            id="mfa-error"
            class="
                pm-auth-error
                mb-5 hidden rounded-lg
                px-4 py-3 text-sm
            "
        ></div>

        <form
            id="mfa-form"
            class="space-y-5"
        >
            <div>
                <label
                    for="mfa-code"
                    class="
                        pm-field-label
                        mb-2 block text-sm font-medium
                    "
                >
                    <span data-i18n="login.mfa_code_label">
                        {{ __('ui.login.mfa_code_label') }}
                    </span>
                </label>

                <input
                    id="mfa-code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    required
                    class="pm-input text-center text-2xl tracking-[0.6em] font-semibold"
                    placeholder="••••••"
                >
            </div>

            <button
                id="mfa-button"
                type="submit"
                class="pm-button-primary w-full disabled:cursor-wait"
            >
                <span data-i18n="login.mfa_verify">
                    {{ __('ui.login.mfa_verify') }}
                </span>
            </button>
        </form>

        <div class="mt-6 flex items-center justify-between text-sm">
            <button
                type="button"
                id="mfa-back"
                class="pm-auth-link font-medium"
                data-i18n="login.mfa_back"
            >
                {{ __('ui.login.mfa_back') }}
            </button>

            <button
                type="button"
                id="mfa-resend"
                class="pm-auth-link font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span data-i18n="login.mfa_resend">
                    {{ __('ui.login.mfa_resend') }}
                </span>
                <span id="mfa-resend-countdown"></span>
            </button>
        </div>

    </div>

    <p class="pm-auth-footer mt-10 text-center text-xs leading-5">
        <span data-i18n="login.secure_access">
            {{ __('ui.login.secure_access') }}
        </span>
    </p>

</div>

@endsection
