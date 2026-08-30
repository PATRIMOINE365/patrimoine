@extends('emails.layouts.base')

@section('title', 'Support request')

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ $subjectLine }}
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                From
            </td>
            <td align="right" style="padding:10px 4px; font-weight:600; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $author->name }} ({{ $author->email }})
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                Organisation
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $organisation?->name ?? '—' }}
                @if ($organisation)
                    (#{{ $organisation->id }})
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                Role
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $author->role }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56;">
                Reads Patrimoine in
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E;">
                {{ $pageLanguage === 'fr' ? 'French' : 'English' }}
            </td>
        </tr>
    </table>

    <div style="margin:0 0 22px 0; padding:16px 18px; background:#FBFCFC; border:1px solid #DDE6E2; border-radius:10px;">
        <p style="margin:0; color:#17201E; font-size:14px; line-height:20px; white-space:pre-wrap;">{{ $body }}</p>
    </div>

    <p style="margin:0; color:#4E5B56; font-size:14px; line-height:20px;">
        Replying to this message answers {{ $author->name }} directly.
    </p>
@endsection
