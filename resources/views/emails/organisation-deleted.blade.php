@extends('emails.layouts.base')

@section('title', __('emails.organisation_deleted.title'))

@section('preheader')
    {{ __('emails.organisation_deleted.preheader', ['organisation' => $organisationName]) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.organisation_deleted.heading') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.organisation_deleted.greeting', ['name' => $administratorName]) }}
    </p>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.organisation_deleted.introduction', [
            'organisation' => $organisationName,
            'date' => $deletedOn,
        ]) }}
    </p>

    <p style="margin:0 0 24px 0;">
        {{ __('emails.organisation_deleted.what_happened') }}
    </p>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.organisation_deleted.questions', [
            'support' => config('legal.mailboxes.support', 'support@patrimoine365.com'),
        ]) }}
    </p>
@endsection
