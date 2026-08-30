<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.expense_invoice.title') }}
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

        .description {
            white-space: pre-wrap;
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

    @php
        $statusKey =
            'documents.expense_invoice.statuses.'
            . $invoice->status;

        $statusLabel = __($statusKey);

        if ($statusLabel === $statusKey) {
            $statusLabel = ucwords(
                str_replace('_', ' ', (string) $invoice->status)
            );
        }
    @endphp

    <div class="reference">
        <strong>
            {{ __('documents.expense_invoice.title') }}
        </strong>

        <div>
            {{ __('documents.expense_invoice.invoice_number') }}:
            {{ $invoice->invoice_number }}
        </div>

        <div>
            {{ __('documents.expense_invoice.date') }}:
            {{ $formatter->date($invoice->issue_date) }}
        </div>
    </div>

    <table>
        <tr>
            <th>
                {{ __('documents.expense_invoice.tenant') }}
            </th>

            <td>
                {{ $invoice->lease->tenant->name
                    ?? $invoice->lease->tenant->legal_name }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.expense_invoice.lease') }}
            </th>

            <td>
                {{ $invoice->lease->unit->building->name ?? '' }}
                /
                {{ $invoice->lease->unit->name ?? '' }}
            </td>
        </tr>

        @if ($invoice->notes)
            <tr>
                <th>
                    {{ __('documents.expense_invoice.reference') }}
                </th>

                <td class="description">{{ $invoice->notes }}</td>
            </tr>
        @endif

        <tr>
            <th>
                {{ __('documents.expense_invoice.status') }}
            </th>

            <td>
                {{ $statusLabel }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th style="width: 66%; background: #FBFCFC;">
                {{ __('documents.expense_invoice.description') }}
            </th>

            <th style="width: 34%; background: #FBFCFC; text-align: right;">
                {{ __('documents.expense_invoice.amount') }}
            </th>
        </tr>

        @foreach ($invoice->lines as $line)
            <tr>
                <td class="description">{{ $line->description }}</td>

                <td style="text-align: right;">
                    {{ $formatter->money($line->amount) }}
                </td>
            </tr>
        @endforeach

        <tr>
            <td style="background: #F7F5EF;">
                <strong>
                    {{ __('documents.expense_invoice.total') }}
                </strong>
            </td>

            <td style="background: #F7F5EF; text-align: right;">
                <strong>
                    {{ $formatter->money($invoice->total_amount) }}
                </strong>
            </td>
        </tr>

        <tr>
            <td>
                {{ __('documents.expense_invoice.paid') }}
            </td>

            <td style="text-align: right;">
                {{ $formatter->money($invoice->paidAmount()) }}
            </td>
        </tr>

        <tr>
            <td>
                {{ __('documents.expense_invoice.outstanding') }}
            </td>

            <td style="text-align: right;">
                {{ $formatter->money($invoice->outstandingAmount()) }}
            </td>
        </tr>
    </table>
@include('documents.partials.doc-footer')

</body>
</html>
