@extends('emails.layouts.base')

@section('title', 'Account closed')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        A customer closed their account
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                Organisation
            </td>
            <td align="right" style="padding:10px 4px; font-weight:600; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $organisationName }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                Closed by
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $administratorName }} ({{ $administratorEmail }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56;">
                Rows destroyed
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E;">
                {{ number_format($rowsDeleted) }}
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        This was done from the customer's own Settings page, with the organisation
        name typed back and the administrator's password re-entered. Nothing of
        theirs remains: the record of the closure is in the platform activity log,
        and this message is the only copy of their details.
    </p>
@endsection
