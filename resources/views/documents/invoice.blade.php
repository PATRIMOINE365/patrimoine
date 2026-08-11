<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Invoice {{ $invoice->invoice_number }}
    </title>

    <style>
        /*
         * Keep PDF CSS deliberately conservative.
         *
         * Dompdf supports the CSS required here reliably without depending
         * on browser-only layout features such as CSS Grid.
         */
        @page {
            margin: 36px 42px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #222;
        }

        .header-table,
        .details-table,
        .amount-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .document-title {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
        }

        .muted {
            color: #666;
        }

        .section {
            margin-top: 24px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .details-table td {
            width: 50%;
            vertical-align: top;
            padding: 3px 0;
        }

        .amount-table {
            margin-top: 18px;
        }

        .amount-table th {
            background: #f1f1f1;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }

        .amount-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .numeric {
            text-align: right;
        }

        .summary-wrapper {
            width: 45%;
            margin-left: auto;
            margin-top: 18px;
        }

        .summary-table td {
            padding: 5px 0;
        }

        .summary-table .total td {
            font-size: 13px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 8px;
        }

        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #777;
            text-align: center;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 0;
            vertical-align: middle;
        }

        .meta-label {
            width: 34%;
            font-weight: bold;
            white-space: nowrap;
        }

        .meta-value {
            width: 66%;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #aaa;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }
    </style>
</head>

<body>

<table class="header-table">
    <tr>
        <td>
            <div class="brand">Patrimoine</div>
            <div class="muted">
                Property Management
            </div>
        </td>

        <td>
            <div class="document-title">INVOICE</div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    Billed To
                </div>

                <strong>
                    {{ $invoice->lease->tenant->name
                        ?? $invoice->lease->tenant->legal_name }}
                </strong>

                @if($invoice->lease->tenant->address)
                    <br>
                    {{ $invoice->lease->tenant->address }}
                @endif

                @if($invoice->lease->tenant->phone)
                    <br>
                    {{ $invoice->lease->tenant->phone }}
                @endif

                @if($invoice->lease->tenant->email)
                    <br>
                    {{ $invoice->lease->tenant->email }}
                @endif
            </td>
            <td>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">
                            Invoice:
                        </td>
                        <td class="meta-value">
                            {{ $invoice->invoice_number }}
                        </td>
                    </tr>

                    <tr>
                        <td class="meta-label">
                            Issue Date:
                        </td>
                        <td class="meta-value">
                            {{ $invoice->issue_date->format('d M Y') }}
                        </td>
                    </tr>

                    <tr>
                        <td class="meta-label">
                            Due Date:
                        </td>
                        <td class="meta-value">
                            {{ $invoice->due_date->format('d M Y') }}
                        </td>
                    </tr>

                    <tr>
                        <td class="meta-label">
                            Status:
                        </td>
                        <td class="meta-value">
                            <span class="status">
                                {{ $invoice->status }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">
        Property
    </div>

    <table class="details-table">
        <tr>
            <td>
                <strong>Building:</strong>
                {{ $invoice->lease->unit->building->name }}
            </td>

            <td>
                <strong>Unit:</strong>
                {{ $invoice->lease->unit->name }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Billing Period:</strong>
                {{ $invoice->period_start->format('d M Y') }}
                -
                {{ $invoice->period_end->format('d M Y') }}
            </td>

            <td>
                <strong>Lease #:</strong>
                {{ $invoice->lease_id }}
            </td>
        </tr>
    </table>
</div>

<table class="amount-table">
    <thead>
        <tr>
            <th>Description</th>
            <th class="numeric">Amount</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>
                Rent —
                {{ $invoice->period_start->format('d M Y') }}
                to
                {{ $invoice->period_end->format('d M Y') }}
            </td>

            <td class="numeric">
                GHS {{ number_format($invoice->total_amount, 0) }}
            </td>
        </tr>
    </tbody>
</table>

<div class="summary-wrapper">
    <table class="summary-table">
        <tr>
            <td>Net Amount</td>
            <td class="numeric">
                GHS {{ number_format($invoice->net_amount, 0) }}
            </td>
        </tr>

        <tr>
            <td>
                VAT ({{ number_format((float) $invoice->vat_rate, 2) }}%)
            </td>

            <td class="numeric">
                GHS {{ number_format($invoice->vat_amount, 0) }}
            </td>
        </tr>

        <tr class="total">
            <td>Total</td>
            <td class="numeric">
                GHS {{ number_format($invoice->total_amount, 0) }}
            </td>
        </tr>

        <tr>
            <td>Paid</td>
            <td class="numeric">
                GHS {{ number_format($invoice->paidAmount(), 0) }}
            </td>
        </tr>

        <tr class="total">
            <td>Balance Due</td>
            <td class="numeric">
                GHS {{ number_format($invoice->outstandingAmount(), 0) }}
            </td>
        </tr>
    </table>
</div>

@if($invoice->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        {{ $invoice->notes }}
    </div>
@endif

<div class="footer">
    Generated by Patrimoine.
    This document reflects the accounting records available at the time of generation.
</div>

</body>
</html>
