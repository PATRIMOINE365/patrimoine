@extends('emails.layouts.base')

@section('title', __('emails.email_verification.title'))

@section('preheader')
    {{ __('emails.email_verification.preheader') }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.email_verification.heading') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.email_verification.greeting', ['name' => $user->name]) }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ __('emails.email_verification.introduction', [
            'organisation' => $organisationName,
        ]) }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:6px 0 30px 0;">
                <a href="{{ $verificationUrl }}"
                   style="display:inline-block; background-color:#0B6449; color:#ffffff; font-size:16px; line-height:24px; font-weight:600; text-decoration:none; padding:13px 34px; border-radius:8px;">
                    {{ __('emails.email_verification.action') }}
                </a>
            </td>
        </tr>
    </table>

    {{--
        Showing the destination in full is deliberate: a visible address
        reads as transactional rather than as a token hidden behind a
        button, and it keeps the message usable when the button fails.
    --}}
    <p style="margin:0 0 6px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_verification.link_fallback') }}
    </p>

    <p style="margin:0 0 22px 0; font-size:12px; line-height:18px; word-break:break-all;">
        <a href="{{ $verificationUrl }}" style="color:#0E7A56; text-decoration:underline;">{{ $verificationUrl }}</a>
    </p>

    <p style="margin:0 0 14px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_verification.expiry') }}
    </p>

    <p style="margin:0 0 14px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_verification.next_steps') }}
    </p>

    <p style="margin:0 0 14px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_verification.ignore') }}
    </p>

    <p style="margin:0; color:#4E5B56; font-size:12px; line-height:18px;">
        {{ __('emails.email_verification.sent_to', ['email' => $user->email]) }}
    </p>
@endsection
