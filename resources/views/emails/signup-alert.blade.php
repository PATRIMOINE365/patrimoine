@extends('emails.layouts.base')

@section('title', 'New signup')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        New organisation signed up
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                Organisation
            </td>
            <td align="right" style="padding:10px 4px; font-weight:600; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $organisation->name }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                Administrator
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $administrator->name }} ({{ $administrator->email }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a;">
                Trial ends
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24;">
                {{ $organisation->trial_ends_on?->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#51615a; font-size:13px;">
        Verification is pending until the administrator confirms their email address.
    </p>
@endsection
