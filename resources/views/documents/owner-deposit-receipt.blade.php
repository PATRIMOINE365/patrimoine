<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Owner Deposit Receipt
        {{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}
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

        .summary-table {
            margin-top: 8px;
        }

        .summary-table td {
            padding: 6px 0;
            vertical-align: top;
        }

        .summary-label {
            font-weight: bold;
        }

        .summary-value {
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

@php
    $owner =
        $transaction
            ->ownerAccount
            ->party;

    $ownerName =
        $owner->name
        ?? $owner->legal_name
        ?? 'Property Owner';

    $purposeLabels = [
        'general_funding' =>
            'General Funding',

        'property_expense' =>
            'Property Expense',

        'repair_maintenance' =>
            'Repair & Maintenance',

        'other' =>
            'Other',
    ];

    $purposeLabel =
        $purposeLabels[
            $transaction->deposit_purpose
        ]
        ?? ucwords(
            str_replace(
                '_',
                ' ',
                (string) $transaction->deposit_purpose
            )
        );

    $paymentMethod =
        ucwords(
            str_replace(
                '_',
                ' ',
                (string) $transaction->payment_method
            )
        );
@endphp

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
                        VAT/TIN:
                        {{ $managingOrganisation->vat_tin }}
                    </div>
                @endif
            @endif
        </td>

        <td>
            <div class="document-title">
                OWNER DEPOSIT RECEIPT
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
                    {{ $ownerName }}
                </strong>

                @if($owner->phone)
                    <br>
                    {{ $owner->phone }}
                @endif

                @if($owner->email)
                    <br>
                    {{ $owner->email }}
                @endif
            </td>

            <td>
                <strong>Receipt No:</strong>

                ODR-{{ str_pad(
                    $transaction->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ) }}

                <br>

                <strong>Payment Date:</strong>

                {{ $transaction
                    ->transaction_date
                    ->format('d M Y') }}

                <br>

                <strong>Method:</strong>

                {{ $paymentMethod }}

                @if($transaction->reference)
                    <br>

                    <strong>Reference:</strong>

                    {{ $transaction->reference }}
                @endif

                @if($transaction->collector_name)
                    <br>

                    <strong>Collector:</strong>

                    {{ $transaction->collector_name }}
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="payment-box">
    <div
        class="muted"
        style="text-align:center;"
    >
        Amount Received
    </div>

    <div class="payment-amount">
        GHS {{ number_format(
            $transaction->amount,
            0
        ) }}
    </div>
</div>

<div class="section">
    <div class="section-title">
        Deposit Details
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-label">
                Purpose
            </td>

            <td class="summary-value">
                {{ $purposeLabel }}
            </td>
        </tr>

        @if($transaction->building)
            <tr>
                <td class="summary-label">
                    Building
                </td>

                <td class="summary-value">
                    {{ $transaction->building->name }}
                </td>
            </tr>
        @endif

        @if($transaction->unit)
            <tr>
                <td class="summary-label">
                    Unit
                </td>

                <td class="summary-value">
                    {{ $transaction->unit->name }}
                </td>
            </tr>
        @endif

        @if(
            ! $transaction->building
            && ! $transaction->unit
        )
            <tr>
                <td class="summary-label">
                    Property Allocation
                </td>

                <td class="summary-value">
                    General Owner Account
                </td>
            </tr>
        @endif
    </table>
</div>

<div class="section">
    <div class="section-title">
        Owner Account
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-label">
                Current Balance
            </td>

            <td class="summary-value">
                GHS {{ number_format(
                    $ownerBalance,
                    0
                ) }}
            </td>
        </tr>
    </table>

    <div class="muted" style="margin-top:6px;">
        The Owner account balance is derived from all Owner ledger
        credits and debits recorded in Patrimoine.
    </div>
</div>

@if($transaction->notes)
    <div class="section">
        <div class="section-title">
            Notes
        </div>

        {{ $transaction->notes }}
    </div>
@endif

<div class="footer">
    This receipt confirms money received from
    {{ $ownerName }}
    and recorded by
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.
</div>

</body>
</html>
