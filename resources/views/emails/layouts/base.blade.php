{{--
    Patrimoine 365 transactional email layout (V1.0.10).

    Every outbound email renders inside this shell so the product speaks
    with one visual voice: a centred 600px card on Warm Ivory, a Patrimoine
    Green masthead, generous whitespace, and a legal footer naming the
    operating company.

    Warm Ivory #F7F5EF is the ground because the brand reserves it for
    documents, e-mail and print, and keeps it out of the product itself.
    Every colour below is a brand colour, and every pair on it was checked:
    Ink 15.3:1, the supporting grey 6.5:1, Slate 4.5:1, Mint Deep 4.9:1,
    and on the green masthead the muted line is 7.4:1 and Mint 6.5:1.

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
<body style="margin:0; padding:0; background-color:#F7F5EF; -webkit-text-size-adjust:100%;">

    {{-- Hidden preview text shown by inbox list views. --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all;">
        @yield('preheader')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F5EF;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%;">

                    {{-- Masthead --}}
                    <tr>
                        <td style="background-color:#123D35; border-radius:12px 12px 0 0; padding:28px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Inter, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
                                        {{--
                                            The mark is served from the marketing site so every
                                            email client renders the real identity, and as a PNG
                                            because e-mail clients do not render SVG. This is the
                                            reverse pair from the brand package — white pillars,
                                            Mint ledger bars — because the masthead is a dark
                                            Patrimoine Green band.

                                            Its alt is empty on purpose: the wordmark beside it is
                                            live text, so the identity survives blocked images, and
                                            an alt here would repeat the brand name in the
                                            plain-text alternative.
                                        --}}
                                        <img src="https://patrimoine365.com/branding/patrimoine-mark-reverse.png" width="32" height="32" alt="" style="display:inline-block; vertical-align:middle; border:0;">
                                        <span style="color:#ffffff; font-size:20px; line-height:30px; font-weight:600; letter-spacing:-0.2px; padding-left:12px; vertical-align:middle;">Patrimoine <span style="color:#39D6A3;">365</span></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family:Inter, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#C6CDCA; font-size:12px; line-height:18px; padding-top:6px;">
                                        {{ __('emails.layout.tagline') }}
                                    </td>
                                </tr>
                                @isset($organisationName)
                                <tr>
                                    <td style="font-family:Inter, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#C6CDCA; font-size:14px; line-height:20px; padding-top:10px;">
                                        {{ __('emails.layout.on_behalf_of', ['organisation' => $organisationName]) }}
                                    </td>
                                </tr>
                                @endisset
                            </table>
                        </td>
                    </tr>

                    {{-- Body card --}}
                    <tr>
                        <td style="background-color:#ffffff; padding:40px; border-radius:0 0 12px 12px; font-family:Inter, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#17201E; font-size:16px; line-height:24px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:26px 40px 8px 40px; font-family:Inter, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#66736F; font-size:12px; line-height:18px;" align="center">
                            <p style="margin:0 0 6px 0;">
                                {{ __('emails.layout.sent_by_product') }}
                                {{ __('emails.layout.tagline') }}
                            </p>
                            <p style="margin:0;">
                                {{ __('emails.layout.questions') }}
                                <a href="mailto:{{ config('legal.mailboxes.support') }}" style="color:#0E7A56; text-decoration:none;">{{ config('legal.mailboxes.support') }}</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
