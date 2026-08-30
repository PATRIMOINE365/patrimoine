@extends('emails.layouts.base')

@section('title', 'Expiring plans')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        Plans expiring within 14 days
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:8px 4px; font-size:12px; line-height:18px; font-weight:600; color:#4E5B56; border-bottom:2px solid #C4CFCA;">Organisation</td>
            <td style="padding:8px 4px; font-size:12px; line-height:18px; font-weight:600; color:#4E5B56; border-bottom:2px solid #C4CFCA;">Kind</td>
            <td style="padding:8px 4px; font-size:12px; line-height:18px; font-weight:600; color:#4E5B56; border-bottom:2px solid #C4CFCA;">Plan</td>
            <td align="right" style="padding:8px 4px; font-size:12px; line-height:18px; font-weight:600; color:#4E5B56; border-bottom:2px solid #C4CFCA;">Ends</td>
        </tr>
        @foreach($rows as $row)
            <tr>
                <td style="padding:9px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">{{ $row['organisation'] }}</td>
                <td style="padding:9px 4px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">{{ $row['kind'] }}</td>
                <td style="padding:9px 4px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">{{ $row['plan'] }}</td>
                <td align="right" style="padding:9px 4px; font-weight:600; color:#123D35; border-bottom:1px solid #DDE6E2;">{{ $row['ends_on'] }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        Issue or extend licences from the platform console at /admin.
    </p>
@endsection
