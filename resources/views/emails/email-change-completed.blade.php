@extends('emails.layouts.base')

@section('title', __('emails.email_change_completed.title'))

@section('preheader')
    {{ __('emails.email_change_completed.preheader') }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.email_change_completed.heading') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.email_change_completed.greeting', ['name' => $user->name]) }}
    </p>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.email_change_completed.introduction', [
            'previous' => $previousEmail,
            'new' => $newEmail,
        ]) }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ __('emails.email_change_completed.sign_in') }}
    </p>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.email_change_completed.not_you', [
            'support' => config('legal.mailboxes.support', 'support@patrimoine365.com'),
        ]) }}
    </p>
@endsection
