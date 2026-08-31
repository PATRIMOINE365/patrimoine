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
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #17201E;
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

        /*
         * 18px rather than 22px, and the cells take a top alignment.
         *
         * The header is a two-cell table with no widths, so dompdf shares
         * the room out by content: a 22px title took enough of it that an
         * organisation with a long name — "Akwaba Property Management" —
         * had its name wrapped onto two lines and the title wrapped onto
         * two beside it, and the two collided.
         */
        /*
         * The header is a two-cell table, and dompdf shares the room out
         * by content unless it is told otherwise — so an organisation
         * with a long name wrapped onto two lines and pushed the title
         * onto two beside it. The cells are given widths and both take a
         * top alignment.
         */
        .header-name {
            width: 58%;
            vertical-align: top;
        }

        .header-title {
            width: 42%;
            vertical-align: top;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            vertical-align: top;
        }

        .muted {
            color: #66736F;
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
            background: #FBFCFC;
            text-align: left;
            padding: 8px;
            border: 1px solid #DDE6E2;
        }

        .amount-table td {
            padding: 8px;
            border: 1px solid #DDE6E2;
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
            border-top: 1px solid #333F3B;
            padding-top: 8px;
        }

        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #DDE6E2;
            font-size: 9px;
            color: #66736F;
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
            border: 1px solid #7E8C87;
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
        <td class="header-name">
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
                        {{ $managingOrganisation->phone_display }}
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

        <td class="header-title">
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
                    {{ $invoice->lease->tenant->phone_display }}
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
        {{--
            Rent no longer carries VAT: it is charged on the managing
            organisation's fee and billed to the Owner instead. Invoices
            issued before that change keep the VAT they were posted with,
            so the net and VAT lines are shown only when there is VAT to
            show, and a VAT-free Invoice reads as a single total.
        --}}
        @if($invoice->vat_amount > 0)
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
        @endif

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
