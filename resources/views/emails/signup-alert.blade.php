@extends('emails.layouts.base')

@section('title', 'New signup')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        New organisation signed up
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                Organisation
            </td>
            <td align="right" style="padding:10px 4px; font-weight:600; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $organisation->name }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                Administrator
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $administrator->name }} ({{ $administrator->email }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56;">
                Trial ends
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E;">
                {{ $organisation->trial_ends_on?->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        Verification is pending until the administrator confirms their email address.
    </p>
@endsection
