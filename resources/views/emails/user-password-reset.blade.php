<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('emails.password_reset.title') }}</title>
</head>
<body>
    <p>
        {{ __('emails.password_reset.greeting', [
            'name' => $user->name,
        ]) }}
    </p>

    <p>
        {{ __('emails.password_reset.introduction', [
            'organisation' => $organisationName,
        ]) }}
    </p>

    <p>
        <a href="{{ $resetUrl }}">
            {{ __('emails.password_reset.action') }}
        </a>
    </p>

    <p>{{ __('emails.password_reset.expiry') }}</p>
    <p>{{ __('emails.password_reset.ignore') }}</p>
</body>
</html>
