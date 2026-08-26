@extends('emails.layouts.base')

@section('title', __('emails.plan_expiry.title'))

@section('preheader')
    {{ __('emails.plan_expiry.subject', ['days' => $daysLeft]) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.plan_expiry.heading', ['days' => $daysLeft]) }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.plan_expiry.greeting', ['name' => $user->name]) }}
    </p>

    <p style="margin:0 0 22px 0;">
        {{ $kind === 'trial'
            ? __('emails.plan_expiry.introduction_trial', [
                'organisation' => $organisationName,
                'date' => $endsOn,
            ])
            : __('emails.plan_expiry.introduction_license', [
                'organisation' => $organisationName,
                'plan' => __('emails.plans.'.$plan),
                'date' => $endsOn,
            ]) }}
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.plan_expiry.what_changes') }}
    </p>

    <p style="margin:0; color:#51615a; font-size:13px; line-height:21px;">
        {{ __('emails.plan_expiry.renew') }}
        <a href="mailto:{{ config('legal.mailboxes.billing') }}" style="color:#2f6f52;">{{ config('legal.mailboxes.billing') }}</a>
    </p>
@endsection
