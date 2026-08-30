@extends('emails.layouts.base')

@section('title', __('emails.password_reset.title'))

@section('preheader')
    {{ __('emails.password_reset.expiry') }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.password_reset.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.password_reset.greeting', [
            'name' => $user->name,
        ]) }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ __('emails.password_reset.introduction', [
            'organisation' => $organisationName,
        ]) }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:6px 0 30px 0;">
                <a href="{{ $resetUrl }}"
                   style="display:inline-block; background-color:#0B6449; color:#ffffff; font-size:16px; line-height:24px; font-weight:600; text-decoration:none; padding:13px 34px; border-radius:8px;">
                    {{ __('emails.password_reset.action') }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.password_reset.expiry') }}
    </p>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.password_reset.ignore') }}
    </p>
@endsection
