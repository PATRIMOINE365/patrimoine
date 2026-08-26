@extends('layouts.auth')

@section('title_key', 'signup.title')
@section('title_fallback', 'Create your organisation — Patrimoine 365')

@section('content')

<div class="pm-auth-login">

    {{-- ============================================================
         Signup form
         ============================================================ --}}
    <div id="signup-step-form">

        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="signup.heading">
                    {{ __('ui.signup.heading') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="signup.description">
                    {{ __('ui.signup.description') }}
                </span>
            </p>
        </div>

        <div
            id="signup-error"
            class="
                pm-auth-error
                mb-5 hidden rounded-lg
                px-4 py-3 text-sm
            "
        ></div>

        <form
            id="signup-form"
            class="space-y-5"
        >
            <div>
                <label
                    for="signup-organisation"
                    class="pm-field-label mb-2 block text-sm font-medium"
                >
                    <span data-i18n="signup.organisation_name">
                        {{ __('ui.signup.organisation_name') }}
                    </span>
                </label>

                <input
                    id="signup-organisation"
                    name="organisation_name"
                    type="text"
                    autocomplete="organization"
                    required
                    autofocus
                    class="pm-input"
                    data-i18n-placeholder="signup.organisation_name_placeholder"
                    placeholder="Acme Properties Ltd"
                >
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        for="signup-given-names"
                        class="pm-field-label mb-2 block text-sm font-medium"
                    >
                        <span data-i18n="signup.given_names">
                            {{ __('ui.signup.given_names') }}
                        </span>
                    </label>

                    <input
                        id="signup-given-names"
                        name="given_names"
                        type="text"
                        autocomplete="given-name"
                        required
                        class="pm-input"
                    >
                </div>

                <div>
                    <label
                        for="signup-surname"
                        class="pm-field-label mb-2 block text-sm font-medium"
                    >
                        <span data-i18n="signup.surname">
                            {{ __('ui.signup.surname') }}
                        </span>
                    </label>

                    <input
                        id="signup-surname"
                        name="surname"
                        type="text"
                        autocomplete="family-name"
                        required
                        class="pm-input"
                    >
                </div>
            </div>

            <div>
                <label
                    for="signup-email"
                    class="pm-field-label mb-2 block text-sm font-medium"
                >
                    <span data-i18n="signup.email">
                        {{ __('ui.signup.email') }}
                    </span>
                </label>

                <input
                    id="signup-email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="pm-input"
                    data-i18n-placeholder="login.email_placeholder"
                    placeholder="name@example.com"
                >
            </div>

            <div>
                <label
                    for="signup-phone"
                    class="pm-field-label mb-2 block text-sm font-medium"
                >
                    <span data-i18n="signup.phone">
                        {{ __('ui.signup.phone') }}
                    </span>
                </label>

                <input
                    id="signup-phone"
                    name="phone"
                    type="tel"
                    autocomplete="tel"
                    class="pm-input"
                >
            </div>

            <div>
                <label
                    for="signup-password"
                    class="pm-field-label mb-2 block text-sm font-medium"
                >
                    <span data-i18n="signup.password">
                        {{ __('ui.signup.password') }}
                    </span>
                </label>

                <input
                    id="signup-password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    minlength="10"
                    class="pm-input"
                >

                <p class="pm-auth-description mt-1.5 text-xs">
                    <span data-i18n="signup.password_help">
                        {{ __('ui.signup.password_help') }}
                    </span>
                </p>
            </div>

            <div>
                <label
                    for="signup-password-confirmation"
                    class="pm-field-label mb-2 block text-sm font-medium"
                >
                    <span data-i18n="signup.password_confirmation">
                        {{ __('ui.signup.password_confirmation') }}
                    </span>
                </label>

                <input
                    id="signup-password-confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    minlength="10"
                    class="pm-input"
                >
            </div>

            <label
                class="flex items-start gap-3 text-sm leading-6"
                for="signup-legal"
            >
                <input
                    id="signup-legal"
                    name="accept_legal"
                    type="checkbox"
                    required
                    class="mt-1 h-4 w-4 shrink-0 rounded"
                >

                <span>
                    <span data-i18n="signup.accept_prefix">
                        {{ __('ui.signup.accept_prefix') }}
                    </span>
                    <a
                        href="/terms"
                        target="_blank"
                        rel="noopener"
                        class="pm-auth-link font-medium"
                        data-i18n="signup.terms_link"
                    >
                        {{ __('ui.signup.terms_link') }}
                    </a>
                    <span data-i18n="signup.accept_and">
                        {{ __('ui.signup.accept_and') }}
                    </span>
                    <a
                        href="/privacy"
                        target="_blank"
                        rel="noopener"
                        class="pm-auth-link font-medium"
                        data-i18n="signup.privacy_link"
                    >
                        {{ __('ui.signup.privacy_link') }}
                    </a>
                </span>
            </label>

            <button
                id="signup-button"
                type="submit"
                class="pm-button-primary w-full disabled:cursor-wait"
            >
                <span data-i18n="signup.submit">
                    {{ __('ui.signup.submit') }}
                </span>
            </button>
        </form>

        <p class="pm-auth-description mt-6 text-center text-sm">
            <span data-i18n="signup.have_account">
                {{ __('ui.signup.have_account') }}
            </span>
            <a
                href="/login"
                class="pm-auth-link font-medium"
                data-i18n="signup.sign_in_link"
            >
                {{ __('ui.signup.sign_in_link') }}
            </a>
        </p>

    </div>

    {{-- ============================================================
         Post-signup confirmation (same box, revealed by JS)
         ============================================================ --}}
    <div id="signup-step-done" class="hidden">

        <div class="mb-9">
            <h2 class="pm-auth-title text-3xl font-semibold tracking-tight">
                <span data-i18n="signup.done_heading">
                    {{ __('ui.signup.done_heading') }}
                </span>
            </h2>

            <p class="pm-auth-description mt-2 text-sm leading-6">
                <span data-i18n="signup.done_description">
                    {{ __('ui.signup.done_description') }}
                </span>
                <span
                    id="signup-done-email"
                    class="font-medium"
                ></span>
            </p>
        </div>

        <a
            href="/login"
            class="pm-button-primary block w-full text-center"
            data-i18n="signup.done_back_to_login"
        >
            {{ __('ui.signup.done_back_to_login') }}
        </a>

    </div>

    <p class="pm-auth-footer mt-10 text-center text-xs leading-5">
        <span data-i18n="login.secure_access">
            {{ __('ui.login.secure_access') }}
        </span>
    </p>

</div>

@endsection
