@extends('emails.layouts.base')

@section('title', __('emails.mfa_code.title'))

@section('preheader')
    {{ __('emails.mfa_code.preheader') }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.mfa_code.heading') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.mfa_code.greeting', ['name' => $user->name]) }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ __('emails.mfa_code.introduction') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:2px 0 26px 0;">
                <span style="display:inline-block; background-color:#f0f5f2; border:1px solid #d4e2da; border-radius:10px; padding:16px 30px; font-family:Consolas, Menlo, monospace; font-size:30px; font-weight:700; letter-spacing:10px; color:#123527;">{{ $code }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px 0; color:#51615a; font-size:13px; line-height:21px;">
        {{ __('emails.mfa_code.expiry', [
            'minutes' => \App\Models\MfaChallenge::CODE_LIFETIME_MINUTES,
        ]) }}
    </p>

    <p style="margin:0; color:#51615a; font-size:13px; line-height:21px;">
        {{ __('emails.mfa_code.ignore') }}
    </p>
@endsection
