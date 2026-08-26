@extends('emails.layouts.base')

@section('title', __('emails.license_issued.title'))

@section('preheader')
    {{ __('emails.license_issued.preheader', ['plan' => __('emails.plans.'.$license->plan)]) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.license_issued.heading') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.license_issued.greeting', ['name' => $user->name]) }}
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.license_issued.introduction', [
            'organisation' => $organisationName,
        ]) }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px 0; background-color:#f6faf8; border:1px solid #dbe7e0; border-radius:10px;">
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.license_issued.plan') }}
            </td>
            <td align="right" style="padding:12px 18px; font-weight:700; color:#123527; border-bottom:1px solid #e3ede7;">
                {{ __('emails.plans.'.$license->plan) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.license_issued.starts_on') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $license->starts_on?->format('Y-m-d') }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a;">
                {{ __('emails.license_issued.expires_on') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#1d2a24;">
                {{ $license->expires_on?->format('Y-m-d') ?? __('emails.license_issued.no_expiry') }}
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#51615a; font-size:13px; line-height:21px;">
        {{ __('emails.license_issued.questions') }}
        <a href="mailto:{{ config('legal.mailboxes.billing') }}" style="color:#2f6f52;">{{ config('legal.mailboxes.billing') }}</a>
    </p>
@endsection
