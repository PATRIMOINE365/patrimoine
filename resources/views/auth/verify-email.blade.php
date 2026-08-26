@extends('layouts.auth')

@section('title_key', 'verify_email.title')
@section('title_fallback', 'Verify your email — Patrimoine 365')

@section('content')

<div class="pm-auth-login">

    {{-- Spinner while the token is submitted --}}
    <div id="verify-step-pending">
        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="verify_email.pending_heading">
                    {{ __('ui.verify_email.pending_heading') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="verify_email.pending_description">
                    {{ __('ui.verify_email.pending_description') }}
                </span>
            </p>
        </div>
    </div>

    {{-- Success --}}
    <div id="verify-step-success" class="hidden">
        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="verify_email.success_heading">
                    {{ __('ui.verify_email.success_heading') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="verify_email.success_description">
                    {{ __('ui.verify_email.success_description') }}
                </span>
            </p>
        </div>

        <a
            href="/login"
            class="pm-button-primary block w-full text-center"
            data-i18n="verify_email.continue"
        >
            {{ __('ui.verify_email.continue') }}
        </a>
    </div>

    {{-- Failure + resend --}}
    <div id="verify-step-failed" class="hidden">
        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="verify_email.failed_heading">
                    {{ __('ui.verify_email.failed_heading') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="verify_email.failed_description">
                    {{ __('ui.verify_email.failed_description') }}
                </span>
            </p>
        </div>

        <div
            id="verify-resend-feedback"
            class="
                pm-auth-error
                mb-5 hidden rounded-lg
                px-4 py-3 text-sm
            "
        ></div>

        <form id="verify-resend-form" class="space-y-5">
            <div>
                <label
                    for="verify-resend-email"
                    class="pm-field-label mb-2 block text-sm font-medium"
                >
                    <span data-i18n="login.email">
                        {{ __('ui.login.email') }}
                    </span>
                </label>

                <input
                    id="verify-resend-email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="pm-input"
                    data-i18n-placeholder="login.email_placeholder"
                    placeholder="name@example.com"
                >
            </div>

            <button
                id="verify-resend-button"
                type="submit"
                class="pm-button-primary w-full disabled:cursor-wait"
            >
                <span data-i18n="verify_email.resend">
                    {{ __('ui.verify_email.resend') }}
                </span>
            </button>
        </form>

        <p class="pm-auth-description mt-6 text-center text-sm">
            <a
                href="/login"
                class="pm-auth-link font-medium"
                data-i18n="verify_email.back_to_login"
            >
                {{ __('ui.verify_email.back_to_login') }}
            </a>
        </p>
    </div>

    <p class="pm-auth-footer mt-10 text-center text-xs leading-5">
        <span data-i18n="login.secure_access">
            {{ __('ui.login.secure_access') }}
        </span>
    </p>

</div>

@endsection
