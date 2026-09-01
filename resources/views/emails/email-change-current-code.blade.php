@extends('emails.layouts.base')

@section('title', __('emails.email_change_current.title'))

@section('preheader')
    {{ __('emails.email_change_current.preheader') }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.email_change_current.heading') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.email_change_current.greeting', ['name' => $user->name]) }}
    </p>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.email_change_current.introduction', [
            'proposed' => $proposedEmail,
        ]) }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ __('emails.email_change_current.unchanged') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:2px 0 26px 0;">
                <span style="display:inline-block; background-color:#F2F6F4; border:1px solid #C4CFCA; border-radius:10px; padding:16px 30px; font-family:Consolas, Menlo, monospace; font-size:30px; line-height:38px; font-weight:700; letter-spacing:10px; color:#123D35;">{{ $code }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_change_current.expiry', [
            'minutes' => \App\Models\EmailChangeRequest::CODE_LIFETIME_MINUTES,
        ]) }}
    </p>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_change_current.not_you', [
            'support' => config('legal.mailboxes.support', 'support@patrimoine365.com'),
        ]) }}
    </p>
@endsection
