<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('documents.invoice.title') }}
        {{ $invoice->invoice_number }}
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
    @include('documents.partials.base-styles')
</head>

<body>

@php
    /*
     * Keep persisted Invoice codes unchanged and translate only their
     * user-facing presentation.
     */
    $statusKey =
        'documents.invoice.status.'
        . $invoice->status;

    $statusLabel =
        __($statusKey);

    if ($statusLabel === $statusKey) {
        $statusLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $invoice->status
                )
            );
    }

    $invoiceTypeKey =
        'documents.invoice.type.'
        . $invoice->type;

    $invoiceTypeLabel =
        __($invoiceTypeKey);

    if ($invoiceTypeLabel === $invoiceTypeKey) {
        $invoiceTypeLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $invoice->type
                )
            );
    }

    /*
     * VAT rates are percentages rather than monetary values.
     * Use the active language's decimal separator without altering
     * the underlying stored VAT rate.
     */
    $vatRate =
        number_format(
            (float) $invoice->vat_rate,
            2,
            app()->getLocale() === 'fr'
                ? ','
                : '.',
            ''
        );
@endphp

<table class="header-table">
    <tr>
        <td>
            <div class="brand">
            {{ $managingOrganisation?->legal_name
                ?? $managingOrganisation?->name
                ?? 'Patrimoine' }}
            </div>

            <div class="muted">
                {{ __('documents.common.property_management') }}
            </div>

            @if($managingOrganisation)
                @if($managingOrganisation->address)
                    <div class="muted">
                        {{ $managingOrganisation->address }}
                    </div>
                @endif

                @if($managingOrganisation->phone)
                    <div class="muted">
                        {{ $managingOrganisation->phone }}
                    </div>
                @endif

                @if($managingOrganisation->email)
                    <div class="muted">
                        {{ $managingOrganisation->email }}
                    </div>
                @endif

                @if($managingOrganisation->vat_tin)
                    <div class="muted">
                        {{ __('documents.common.vat_tin') }}:
                        {{ $managingOrganisation->vat_tin }}
                    </div>
                @endif
            @endif
        </td>

        <td>
            <div class="document-title">
                {{ __('documents.invoice.heading') }}
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    {{ __('documents.invoice.billed_to') }}
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
                            {{ __('documents.invoice.invoice_number') }}:
                        </td>
                        <td class="meta-value">
                            {{ $invoice->invoice_number }}
                        </td>
                    </tr>

                    <tr>
                        <td class="meta-label">
                            {{ __('documents.invoice.issue_date') }}:
                        </td>
                        <td class="meta-value">
                            {{ $formatter->date($invoice->issue_date) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="meta-label">
                            {{ __('documents.invoice.due_date') }}:
                        </td>
                        <td class="meta-value">
                            {{ $formatter->date($invoice->due_date) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="meta-label">
                            {{ __('documents.invoice.status_label') }}:
                        </td>
                        <td class="meta-value">
                            <span class="status">
                                {{ $statusLabel }}
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
        {{ __('documents.invoice.property') }}
    </div>

    <table class="details-table">
        <tr>
            <td>
                <strong>
                    {{ __('documents.invoice.building') }}:
                </strong>
                {{ $invoice->lease->unit->building->name }}
            </td>

            <td>
                <strong>
                    {{ __('documents.invoice.unit') }}:
                </strong>
                {{ $invoice->lease->unit->name }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>
                    {{ __('documents.invoice.billing_period') }}:
                </strong>
                {{ $formatter->date($invoice->period_start) }}
                -
                {{ $formatter->date($invoice->period_end) }}
            </td>

            <td>
                <strong>
                    {{ __('documents.invoice.lease_number') }}:
                </strong>
                {{ $invoice->lease_id }}
            </td>
        </tr>
    </table>
</div>

<table class="amount-table">
    <thead>
        <tr>
            <th>
                {{ __('documents.invoice.description') }}
            </th>
            <th class="numeric">
                {{ __('documents.invoice.amount') }}
            </th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>
                {{ $invoiceTypeLabel }}

                @if($invoice->isRentInvoice())
                    —
                    {{ $formatter->date($invoice->period_start) }}
                    {{ __('documents.invoice.to') }}
                    {{ $formatter->date($invoice->period_end) }}
                @endif
            </td>

            <td class="numeric">
                {{ $formatter->money($invoice->total_amount) }}
            </td>
        </tr>
    </tbody>
</table>

<div class="summary-wrapper">
    <table class="summary-table">
        <tr>
            <td>
                {{ __('documents.invoice.net_amount') }}
            </td>
            <td class="numeric">
                {{ $formatter->money($invoice->net_amount) }}
            </td>
        </tr>

        <tr>
            <td>
                {{ __('documents.invoice.vat') }}
                ({{ $vatRate }}%)
            </td>

            <td class="numeric">
                {{ $formatter->money($invoice->vat_amount) }}
            </td>
        </tr>

        <tr class="total">
            <td>
                {{ __('documents.invoice.total') }}
            </td>
            <td class="numeric">
                {{ $formatter->money($invoice->total_amount) }}
            </td>
        </tr>

        <tr>
            <td>
                {{ __('documents.invoice.paid') }}
            </td>
            <td class="numeric">
                {{ $formatter->money($invoice->paidAmount()) }}
            </td>
        </tr>

        <tr class="total">
            <td>
                {{ __('documents.invoice.balance_due') }}
            </td>
            <td class="numeric">
                {{ $formatter->money($invoice->outstandingAmount()) }}
            </td>
        </tr>
    </table>
</div>

@if($invoice->notes)
    <div class="section">
        <div class="section-title">
            {{ __('documents.invoice.notes') }}
        </div>
        {{ $invoice->notes }}
    </div>
@endif

<div class="footer">
    {{ __('documents.invoice.issued_by') }}
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.

    {{ __('documents.invoice.accounting_record_notice') }}
</div>

</body>
</html>
