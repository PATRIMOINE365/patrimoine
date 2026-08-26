@extends('emails.layouts.base')

@section('title', __('emails.password_reset.title'))

@section('preheader')
    {{ __('emails.password_reset.expiry') }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
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
                   style="display:inline-block; background-color:#1d5c3f; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none; padding:13px 34px; border-radius:8px;">
                    {{ __('emails.password_reset.action') }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px 0; color:#51615a; font-size:13px; line-height:21px;">
        {{ __('emails.password_reset.expiry') }}
    </p>

    <p style="margin:0; color:#51615a; font-size:13px; line-height:21px;">
        {{ __('emails.password_reset.ignore') }}
    </p>
@endsection
