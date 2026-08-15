<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('documents.owner_deposit_receipt.title') }}
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
        ?? __('documents.owner_deposit_receipt.property_owner');

    /*
     * Persisted purpose and payment-method codes remain unchanged.
     * Only their document presentation is translated.
     */
    $purposeKey =
        'documents.owner_deposit_receipt.purpose.'
        . $transaction->deposit_purpose;

    $purposeLabel =
        __($purposeKey);

    if ($purposeLabel === $purposeKey) {
        $purposeLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $transaction->deposit_purpose
                )
            );
    }

    $paymentMethodKey =
        'documents.common.payment_method.'
        . $transaction->payment_method;

    $paymentMethodLabel =
        __($paymentMethodKey);

    if ($paymentMethodLabel === $paymentMethodKey) {
        $paymentMethodLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $transaction->payment_method
                )
            );
    }
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
                {{ __('documents.owner_deposit_receipt.heading') }}
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    {{ __('documents.owner_deposit_receipt.received_from') }}
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
                <strong>
                    {{ __('documents.owner_deposit_receipt.receipt_number') }}:
                </strong>

                ODR-{{ str_pad(
                    $transaction->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ) }}

                <br>

                <strong>
                    {{ __('documents.owner_deposit_receipt.payment_date') }}:
                </strong>

                {{ $formatter->date(
                    $transaction->transaction_date
                ) }}

                <br>

                <strong>
                    {{ __('documents.owner_deposit_receipt.method') }}:
                </strong>

                {{ $paymentMethodLabel }}

                @if($transaction->reference)
                    <br>

                    <strong>
                        {{ __('documents.owner_deposit_receipt.reference') }}:
                    </strong>

                    {{ $transaction->reference }}
                @endif

                @if($transaction->collector_name)
                    <br>

                    <strong>
                        {{ __('documents.owner_deposit_receipt.collector') }}:
                    </strong>

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
        {{ __('documents.owner_deposit_receipt.amount_received') }}
    </div>

    <div class="payment-amount">
        {{ $formatter->money(
            $transaction->amount
        ) }}
    </div>
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.owner_deposit_receipt.deposit_details') }}
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-label">
                {{ __('documents.owner_deposit_receipt.purpose_label') }}
            </td>

            <td class="summary-value">
                {{ $purposeLabel }}
            </td>
        </tr>

        @if($transaction->building)
            <tr>
                <td class="summary-label">
                    {{ __('documents.owner_deposit_receipt.building') }}
                </td>

                <td class="summary-value">
                    {{ $transaction->building->name }}
                </td>
            </tr>
        @endif

        @if($transaction->unit)
            <tr>
                <td class="summary-label">
                    {{ __('documents.owner_deposit_receipt.unit') }}
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
                    {{ __('documents.owner_deposit_receipt.property_allocation') }}
                </td>

                <td class="summary-value">
                    {{ __('documents.owner_deposit_receipt.general_owner_account') }}
                </td>
            </tr>
        @endif
    </table>
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.owner_deposit_receipt.owner_account') }}
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-label">
                {{ __('documents.owner_deposit_receipt.current_balance') }}
            </td>

            <td class="summary-value">
                {{ $formatter->money(
                    $ownerBalance
                ) }}
            </td>
        </tr>
    </table>

    <div class="muted" style="margin-top:6px;">
        {{ __('documents.owner_deposit_receipt.balance_explanation') }}
    </div>
</div>

@if($transaction->notes)
    <div class="section">
        <div class="section-title">
            {{ __('documents.owner_deposit_receipt.notes') }}
        </div>

        {{ $transaction->notes }}
    </div>
@endif

<div class="footer">
    {{ __('documents.owner_deposit_receipt.footer_received_from') }}
    {{ $ownerName }}
    {{ __('documents.owner_deposit_receipt.footer_recorded_by') }}
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.
</div>

</body>
</html>
