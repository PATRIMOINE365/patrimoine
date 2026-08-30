<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.invoice_payment_receipt.title') }}
        {{ $invoice->invoice_number }}
    </title>

    <style>
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #17201E;
            line-height: 1.45;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .organisation {
            margin-bottom: 18px;
        }

        .muted {
            color: #66736F;
        }

        .reference {
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        th,
        td {
            padding: 9px;
            border: 1px solid #DDE6E2;
            vertical-align: top;
        }

        th {
            width: 34%;
            text-align: left;
            background: #F7F5EF;
        }
    </style>
    @include('documents.partials.base-styles')
</head>
<body>

    <div class="organisation">
        <h1>
            {{ $managingOrganisation?->legal_name
                ?? $managingOrganisation?->name
                ?? 'Patrimoine' }}
        </h1>

        <div class="muted">
            {{ __('documents.common.property_management') }}
        </div>
    </div>

    <div class="reference">
        <strong>
            {{ __('documents.invoice_payment_receipt.title') }}
        </strong>

        <div>
            {{ __('documents.invoice_payment_receipt.invoice_number') }}:
            {{ $invoice->invoice_number }}
        </div>

        <div>
            {{ __('documents.invoice_payment_receipt.date') }}:
            {{ $formatter->date(now()) }}
        </div>
    </div>

    <table>
        <tr>
            <th>
                {{ __('documents.invoice_payment_receipt.tenant') }}
            </th>

            <td>
                {{ $invoice->lease->tenant->name
                    ?? $invoice->lease->tenant->legal_name }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.invoice_payment_receipt.lease') }}
            </th>

            <td>
                {{ $invoice->lease->unit->building->name ?? '' }}
                /
                {{ $invoice->lease->unit->name ?? '' }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.invoice_payment_receipt.invoice_total') }}
            </th>

            <td>
                {{ $formatter->money($invoice->total_amount) }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th style="width: 33%; background: #FBFCFC;">
                {{ __('documents.invoice_payment_receipt.payment_date') }}
            </th>

            <th style="width: 33%; background: #FBFCFC;">
                {{ __('documents.invoice_payment_receipt.source_fund') }}
            </th>

            <th style="width: 34%; background: #FBFCFC; text-align: right;">
                {{ __('documents.invoice_payment_receipt.amount') }}
            </th>
        </tr>

        @foreach ($payments as $payment)
            <tr>
                <td>
                    {{ $formatter->date($payment->transaction_date) }}
                </td>

                <td>
                    {{ __(
                        'documents.invoice_payment_receipt.funds.'
                        . $payment->account->type
                    ) }}
                </td>

                <td style="text-align: right;">
                    {{ $formatter->money($payment->amount) }}
                </td>
            </tr>
        @endforeach

        <tr>
            <td colspan="2" style="background: #F7F5EF;">
                <strong>
                    {{ __('documents.invoice_payment_receipt.total_paid') }}
                </strong>
            </td>

            <td style="background: #F7F5EF; text-align: right;">
                <strong>
                    {{ $formatter->money($invoice->paidAmount()) }}
                </strong>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                {{ __('documents.invoice_payment_receipt.outstanding') }}
            </td>

            <td style="text-align: right;">
                {{ $formatter->money($invoice->outstandingAmount()) }}
            </td>
        </tr>
    </table>
@include('documents.partials.doc-footer')

</body>
</html>
