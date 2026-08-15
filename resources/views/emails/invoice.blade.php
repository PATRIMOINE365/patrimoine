<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('emails.invoice.title', ['number' => $invoice->invoice_number]) }}
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
                                {{ __('emails.common.property_management') }}
                            </div>

                            <p>
                                {{ __('emails.common.dear') }}
                                <strong>
                                    {{ $invoice->lease->tenant->name
                                        ?? $invoice->lease->tenant->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                {{ __('emails.invoice.intro_before_number') }}
                                <strong>
                                    {{ $invoice->invoice_number }}
                                </strong>
                                {{ __('emails.invoice.intro_for') }}
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
                                        {{ __('emails.invoice.invoice_amount') }}
                                    </td>

                                    <td align="right">
                                        <strong>
                                            {{ $formatter->money($invoice->total_amount) }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.invoice.amount_paid') }}
                                    </td>

                                    <td align="right">
                                        {{ $formatter->money($invoice->paidAmount()) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.common.balance_due') }}
                                    </td>

                                    <td align="right">
                                        <strong>
                                            {{ $formatter->money($invoice->outstandingAmount()) }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.common.due_date') }}
                                    </td>

                                    <td align="right">
                                        {{ $formatter->date($invoice->due_date) }}
                                    </td>
                                </tr>
                            </table>

                            <p>
                                {{ __('emails.invoice.pdf_attached') }}
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
