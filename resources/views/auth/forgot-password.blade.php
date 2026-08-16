@extends('layouts.auth')

@section('title_key', 'password.forgot_title')
@section('title_fallback', 'Forgot password — Patrimoine')

@section('content')
<div id="forgot-password-workspace">
    <div class="mb-9">
        <h2 class="text-3xl font-semibold tracking-tight text-slate-950">
            <span data-i18n="password.forgot_heading">
                Forgot your password?
            </span>
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            <span data-i18n="password.forgot_description">
                Enter your email address and we will send you a password reset link.
            </span>
        </p>
    </div>

    <div
        id="forgot-password-message"
        class="
            mb-5 hidden rounded-lg
            border px-4 py-3 text-sm
        "
        role="status"
    ></div>

    <form id="forgot-password-form" class="space-y-5">
        <div>
            <label
                for="forgot-email"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span data-i18n="login.email">Email address</span>
            </label>

            <input
                id="forgot-email"
                type="email"
                autocomplete="email"
                required
                autofocus
                class="
                    block w-full rounded-lg
                    border border-slate-300
                    bg-white px-3.5 py-3
                    text-sm text-slate-900
                    shadow-sm outline-none transition
                    focus:border-patrimoine-600
                    focus:ring-3 focus:ring-patrimoine-600/10
                "
                data-i18n-placeholder="login.email_placeholder"
                placeholder="name@example.com"
            >
        </div>

        <button
            id="forgot-password-button"
            type="submit"
            class="
                flex w-full items-center justify-center
                rounded-lg bg-patrimoine-950
                px-4 py-3 text-sm font-semibold text-white
                shadow-sm transition
                hover:bg-patrimoine-800
                disabled:cursor-not-allowed
                disabled:opacity-60
            "
        >
            <span data-i18n="password.send_reset">
                Send reset link
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
