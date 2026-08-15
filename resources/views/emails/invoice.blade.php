<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Invoice {{ $invoice->invoice_number }}
    </title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background: #f5f5f5;
    font-family: Arial, Helvetica, sans-serif;
    color: #222222;
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
                                margin-bottom:4px;
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
                                    {{ $invoice->lease->tenant->name
                                        ?? $invoice->lease->tenant->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                Please find attached your rent invoice
                                <strong>
                                    {{ $invoice->invoice_number }}
                                </strong>
                                for
                                <strong>
                                    {{ $invoice->lease->unit->building->name }}
                                    /
                                    {{ $invoice->lease->unit->name }}
                                </strong>.
                            </p>

                            <table
                                width="100%"
                                cellpadding="8"
                                cellspacing="0"
                                border="0"
                                style="
                                    margin:24px 0;
                                    background:#f7f7f7;
                                "
                            >
                                <tr>
                                    <td>
                                        Invoice Amount
                                    </td>

                                    <td align="right">
                                        <strong>
                                            {{ $formatter->money($invoice->total_amount) }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        Amount Paid
                                    </td>

                                    <td align="right">
                                        {{ $formatter->money($invoice->paidAmount()) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        Balance Due
                                    </td>

                                    <td align="right">
                                        <strong>
                                            {{ $formatter->money($invoice->outstandingAmount()) }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        Due Date
                                    </td>

                                    <td align="right">
                                        {{ $formatter->date($invoice->due_date) }}
                                    </td>
                                </tr>
                            </table>

                            <p>
                                The full invoice is attached as a PDF.
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
