<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Payment Receipt
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
                                Property Management
                            </div>

                            <p>
                                Dear
                                <strong>
                                    {{ $payment->lease->tenant->name
                                        ?? $payment->lease->tenant->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                We confirm receipt of your payment for
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
                                    Amount Received
                                </div>

                                <div style="
                                    font-size:28px;
                                    font-weight:bold;
                                    margin-top:8px;
                                ">
                                    GHS {{ number_format($payment->amount, 0) }}
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
                                        Receipt
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
                                        Payment Date
                                    </td>

                                    <td align="right">
                                        {{ $payment->payment_date->format('d M Y') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        Payment Method
                                    </td>

                                    <td align="right">
                                        {{ ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $payment->payment_method
                                            )
                                        ) }}
                                    </td>
                                </tr>

                                @if($payment->reference)
                                    <tr>
                                        <td>
                                            Reference
                                        </td>

                                        <td align="right">
                                            {{ $payment->reference }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin-top:24px;">
                                Your official receipt is attached as a PDF.
                            </p>

                            <p style="margin-top:28px;">
                                Regards,<br>

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
                                This message was generated by Patrimoine.
                            </div>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
