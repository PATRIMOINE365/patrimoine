<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Rent Reminder
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
                                    {{ $invoice->lease->tenant->name
                                        ?? $invoice->lease->tenant->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                This is a reminder regarding rent invoice
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
                                        Balance Due
                                    </td>

                                    <td align="right">
                                        <strong>
                                            GHS {{ number_format($invoice->outstandingAmount(), 0) }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        Due Date
                                    </td>

                                    <td align="right">
                                        {{ $invoice->due_date->format('d M Y') }}
                                    </td>
                                </tr>
                            </table>

                            @if($invoice->due_date->isPast())
                                <p>
                                    Our records indicate that this invoice
                                    is currently overdue.
                                </p>
                            @else
                                <p>
                                    Please arrange payment by the due date.
                                </p>
                            @endif

                            <p>
                                A copy of the invoice is attached for your
                                reference.
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
