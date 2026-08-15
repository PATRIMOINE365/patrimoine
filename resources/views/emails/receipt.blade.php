<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('emails.receipt.title') }}
    </title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#f5f5f5;
    font-family:Arial, Helvetica, sans-serif;
    color:#222222;
">
    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="background:#f5f5f5;padding:30px 0;"
    >
        <tr>
            <td align="center">

                <table
                    width="620"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="
                        background:#ffffff;
                        padding:32px;
                        border-collapse:collapse;
                    "
                >
                    <tr>
                        <td>
                            <div style="
                                font-size:26px;
                                font-weight:bold;
                            ">
                                {{ $managingOrganisation?->legal_name
    ?? $managingOrganisation?->name
    ?? 'Patrimoine' }}
                            </div>

                            <div style="
                                color:#666666;
                                font-size:13px;
                                margin-bottom:28px;
                            ">
                                {{ __('emails.common.property_management') }}
                            </div>

                            <p>
                                {{ __('emails.common.dear') }}
                                <strong>
                                    {{ $payment->lease->tenant->name
                                        ?? $payment->lease->tenant->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                {{ __('emails.receipt.confirm_before_property') }}
                                <strong>
                                    {{ $payment->lease->unit->building->name }}
                                    /
                                    {{ $payment->lease->unit->name }}
                                </strong>.
                            </p>

                            <div style="
                                text-align:center;
                                margin:28px 0;
                                padding:24px;
                                background:#f7f7f7;
                            ">
                                <div style="
                                    color:#666666;
                                    font-size:13px;
                                ">
                                    {{ __('emails.receipt.amount_received') }}
                                </div>

                                <div style="
                                    font-size:28px;
                                    font-weight:bold;
                                    margin-top:8px;
                                ">
                                    {{ $formatter->money($payment->amount) }}
                                </div>
                            </div>

                            <table
                                width="100%"
                                cellpadding="6"
                                cellspacing="0"
                                border="0"
                            >
                                <tr>
                                    <td>
                                        {{ __('emails.receipt.receipt') }}
                                    </td>

                                    <td align="right">
                                        RCT-{{ str_pad(
                                            $payment->id,
                                            6,
                                            '0',
                                            STR_PAD_LEFT
                                        ) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.receipt.payment_date') }}
                                    </td>

                                    <td align="right">
                                        {{ $formatter->date($payment->payment_date) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.receipt.payment_method') }}
                                    </td>

                                    <td align="right">
                                        {{ __(
                                            'emails.payment_methods.'
                                            . $payment->payment_method
                                        ) }}
                                    </td>
                                </tr>

                                @if($payment->reference)
                                    <tr>
                                        <td>
                                            {{ __('emails.receipt.reference') }}
                                        </td>

                                        <td align="right">
                                            {{ $payment->reference }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin-top:24px;">
                                {{ __('emails.receipt.pdf_attached') }}
                            </p>

                            <p style="margin-top:28px;">
                                {{ __('emails.common.regards') }},<br>

<strong>
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}
</strong>

@if($managingOrganisation)
    @if($managingOrganisation->phone)
        <br>
        {{ $managingOrganisation->phone }}
    @endif

    @if($managingOrganisation->email)
        <br>
        {{ $managingOrganisation->email }}
    @endif
@endif
                            </p>

                            <div style="
                                margin-top:32px;
                                padding-top:16px;
                                border-top:1px solid #dddddd;
                                color:#888888;
                                font-size:11px;
                            ">
                                {{ __('emails.common.generated_by') }}
                            </div>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
