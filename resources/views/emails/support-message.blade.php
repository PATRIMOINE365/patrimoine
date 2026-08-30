@extends('emails.layouts.base')

@section('title', 'Support request')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ $subjectLine }}
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                From
            </td>
            <td align="right" style="padding:10px 4px; font-weight:600; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $author->name }} ({{ $author->email }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                Organisation
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $organisation?->name ?? '—' }}
                @if ($organisation)
                    (#{{ $organisation->id }})
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                Role
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $author->role }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a;">
                Reads Patrimoine in
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24;">
                {{ $pageLanguage === 'fr' ? 'French' : 'English' }}
            </td>
        </tr>
    </table>

    <div style="margin:0 0 22px 0; padding:16px 18px; background:#f4f8f6; border:1px solid #e3ede7; border-radius:10px;">
        <p style="margin:0; color:#1d2a24; font-size:14px; line-height:23px; white-space:pre-wrap;">{{ $body }}</p>
    </div>

    <p style="margin:0; color:#51615a; font-size:13px;">
        Replying to this message answers {{ $author->name }} directly.
    </p>
@endsection
