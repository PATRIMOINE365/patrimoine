@extends('layouts.auth')

@section('title_key', 'login.title')
@section('title_fallback', 'Sign in — Patrimoine')

@section('content')

<div class="pm-auth-login">

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

    <p class="pm-auth-footer mt-10 text-center text-xs leading-5">
        <span data-i18n="login.secure_access">
            {{ __('ui.login.secure_access') }}
        </span>
    </p>

</div>

@endsection
