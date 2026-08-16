<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('emails.user_invitation.title') }}</title>
</head>
<body>
    <p>
        {{ __('emails.user_invitation.greeting', [
            'name' => $user->name,
        ]) }}
    </p>

    <p>
        {{ __('emails.user_invitation.introduction', [
            'organisation' => $organisationName,
        ]) }}
    </p>

    <p>
        <a href="{{ $invitationUrl }}">
            {{ __('emails.user_invitation.action') }}
        </a>
    </p>

    <p>
        {{ __('emails.user_invitation.expiry') }}
    </p>

    <p>
        {{ __('emails.user_invitation.ignore') }}
    </p>
</body>
</html>
