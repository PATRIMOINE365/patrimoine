{{--
    Patrimoine 365 transactional email layout (V1.0.10).

    Every outbound email renders inside this shell so the product speaks
    with one visual voice: centred 600px card, forest-green masthead,
    generous whitespace, and a legal footer naming the operating company.

    Email clients demand table layout and inline styles; nothing here
    depends on external CSS, web fonts or images.

    Sections a template may provide:
      - content   (required) the message body
      - preheader (optional) hidden preview line

    Variables the layout understands:
      - $organisationName (optional) the customer organisation on whose
        behalf the message is sent; shown under the masthead.
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('legal.product.name'))</title>
</head>
<body style="margin:0; padding:0; background-color:#eef2f0; -webkit-text-size-adjust:100%;">

    {{-- Hidden preview text shown by inbox list views. --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all;">
        @yield('preheader')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f0;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%;">

                    {{-- Masthead --}}
                    <tr>
                        <td style="background-color:#123527; border-radius:12px 12px 0 0; padding:28px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Segoe UI, Helvetica, Arial, sans-serif;">
                                        {{--
                                            The logomark is served from the marketing site so every
                                            email client renders the real identity. Its alt is empty
                                            on purpose: the wordmark beside it is live text, so it
                                            survives blocked images — and an alt here would repeat
                                            the brand name in the plain-text alternative.
                                        --}}
                                        <img src="https://patrimoine365.com/icon-192.png" width="40" height="40" alt="" style="display:inline-block; vertical-align:middle; border:0; border-radius:10px;">
                                        <span style="color:#ffffff; font-size:19px; font-weight:600; letter-spacing:0.2px; padding-left:10px; vertical-align:middle;">Patrimoine <span style="color:#89c5a2;">365</span></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family:Segoe UI, Helvetica, Arial, sans-serif; color:#9fc3b2; font-size:12px; padding-top:6px;">
                                        {{ __('emails.layout.tagline') }}
                                    </td>
                                </tr>
                                @isset($organisationName)
                                <tr>
                                    <td style="font-family:Segoe UI, Helvetica, Arial, sans-serif; color:#9fc3b2; font-size:13px; padding-top:10px;">
                                        {{ __('emails.layout.on_behalf_of', ['organisation' => $organisationName]) }}
                                    </td>
                                </tr>
                                @endisset
                            </table>
                        </td>
                    </tr>

                    {{-- Body card --}}
                    <tr>
                        <td style="background-color:#ffffff; padding:40px; border-radius:0 0 12px 12px; font-family:Segoe UI, Helvetica, Arial, sans-serif; color:#1d2a24; font-size:15px; line-height:24px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:26px 40px 8px 40px; font-family:Segoe UI, Helvetica, Arial, sans-serif; color:#6d7d75; font-size:12px; line-height:19px;" align="center">
                            <p style="margin:0 0 6px 0;">
                                {{ __('emails.layout.sent_by_product') }}
                                {{ __('emails.layout.tagline') }}
                            </p>
                            <p style="margin:0;">
                                {{ __('emails.layout.questions') }}
                                <a href="mailto:{{ config('legal.mailboxes.support') }}" style="color:#2f6f52; text-decoration:none;">{{ config('legal.mailboxes.support') }}</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
