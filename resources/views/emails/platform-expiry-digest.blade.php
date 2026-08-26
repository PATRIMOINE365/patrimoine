@extends('emails.layouts.base')

@section('title', 'Expiring plans')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        Plans expiring within 14 days
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:8px 4px; font-size:12px; font-weight:600; color:#51615a; border-bottom:2px solid #d4e2da;">Organisation</td>
            <td style="padding:8px 4px; font-size:12px; font-weight:600; color:#51615a; border-bottom:2px solid #d4e2da;">Kind</td>
            <td style="padding:8px 4px; font-size:12px; font-weight:600; color:#51615a; border-bottom:2px solid #d4e2da;">Plan</td>
            <td align="right" style="padding:8px 4px; font-size:12px; font-weight:600; color:#51615a; border-bottom:2px solid #d4e2da;">Ends</td>
        </tr>
        @foreach($rows as $row)
            <tr>
                <td style="padding:9px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">{{ $row['organisation'] }}</td>
                <td style="padding:9px 4px; color:#51615a; border-bottom:1px solid #e3ede7;">{{ $row['kind'] }}</td>
                <td style="padding:9px 4px; color:#51615a; border-bottom:1px solid #e3ede7;">{{ $row['plan'] }}</td>
                <td align="right" style="padding:9px 4px; font-weight:600; color:#123527; border-bottom:1px solid #e3ede7;">{{ $row['ends_on'] }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:0; color:#51615a; font-size:13px;">
        Issue or extend licences from the platform console at /admin.
    </p>
@endsection
