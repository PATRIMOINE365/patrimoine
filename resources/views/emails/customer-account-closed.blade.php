@extends('emails.layouts.base')

@section('title', 'Account closed')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        A customer closed their account
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                Organisation
            </td>
            <td align="right" style="padding:10px 4px; font-weight:600; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $organisationName }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                Closed by
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $administratorName }} ({{ $administratorEmail }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a;">
                Rows destroyed
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24;">
                {{ number_format($rowsDeleted) }}
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#51615a; font-size:13px;">
        This was done from the customer's own Settings page, with the organisation
        name typed back and the administrator's password re-entered. Nothing of
        theirs remains: the record of the closure is in the platform activity log,
        and this message is the only copy of their details.
    </p>
@endsection
