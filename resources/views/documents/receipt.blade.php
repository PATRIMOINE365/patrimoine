<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Receipt {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
    </title>

    <style>
        @page {
            margin: 36px 42px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
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

        .payment-box {
            margin-top: 24px;
            padding: 18px;
            border: 1px solid #ccc;
        }

        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 8px 0;
        }

        .allocation-table {
            margin-top: 8px;
        }

        .allocation-table th {
            background: #f1f1f1;
            text-align: left;
            padding: 7px;
            border: 1px solid #ddd;
        }

        .allocation-table td {
            padding: 7px;
            border: 1px solid #ddd;
        }

        .numeric {
            text-align: right;
        }

        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #777;
        }
    </style>
</head>

<body>

<table>
    <tr>
        <td>


            <div class="brand">
                {{ $managingOrganisation?->legal_name
                    ?? $managingOrganisation?->name
                    ?? 'Patrimoine' }}
            </div>

            <div class="muted">
                Property Management
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
                        VAT/TIN: {{ $managingOrganisation->vat_tin }}
                    </div>
                @endif
            @endif


        </td>

        <td>
            <div class="document-title">
                RECEIPT
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    Received From
                </div>

                <strong>
                    {{ $payment->lease->tenant->name
                        ?? $payment->lease->tenant->legal_name }}
                </strong>

                @if($payment->lease->tenant->phone)
                    <br>
                    {{ $payment->lease->tenant->phone }}
                @endif

                @if($payment->lease->tenant->email)
                    <br>
                    {{ $payment->lease->tenant->email }}
                @endif
            </td>

            <td>
                <strong>Receipt No:</strong>
                RCT-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
                <br>

                <strong>Payment Date:</strong>
                {{ $payment->payment_date->format('d M Y') }}
                <br>

                <strong>Method:</strong>
                {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}

                @if($payment->reference)
                    <br>
                    <strong>Reference:</strong>
                    {{ $payment->reference }}
                @endif

                @if($payment->collector_name)
                    <br>
                    <strong>Collector:</strong>
                    {{ $payment->collector_name }}
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="payment-box">
    <div class="muted" style="text-align:center;">
        Amount Received
    </div>

    <div class="payment-amount">
        GHS {{ number_format($payment->amount, 0) }}
    </div>
</div>

<div class="section">
    <div class="section-title">
        Property
    </div>

    <strong>Building:</strong>
    {{ $payment->lease->unit->building->name }}

    <br>

    <strong>Unit:</strong>
    {{ $payment->lease->unit->name }}

    <br>

    <strong>Lease #:</strong>
    {{ $payment->lease_id }}
</div>

<div class="section">
    <div class="section-title">
        Payment Allocation
    </div>

    @if($payment->allocations->isEmpty())
        <p>
            This payment is currently unapplied to rent invoices.
        </p>
    @else
        <table class="allocation-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Period</th>
                    <th class="numeric">Allocated</th>
                </tr>
            </thead>

            <tbody>
                @foreach($payment->allocations as $allocation)
                    <tr>
                        <td>
                            {{ $allocation->invoice->invoice_number }}
                        </td>

                        <td>
                            {{ $allocation->invoice->period_start->format('d M Y') }}
                            -
                            {{ $allocation->invoice->period_end->format('d M Y') }}
                        </td>

                        <td class="numeric">
                            GHS {{ number_format($allocation->amount, 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <strong>Allocated:</strong>
                GHS {{ number_format($payment->allocatedAmount(), 0) }}
            </td>

            <td>
                <strong>Unallocated:</strong>
                GHS {{ number_format($payment->unallocatedAmount(), 0) }}
            </td>
        </tr>
    </table>
</div>

@if($payment->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        {{ $payment->notes }}
    </div>
@endif

<div class="footer">
    Thank you. This receipt confirms payment recorded by
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.
</div>

</body>
</html>
