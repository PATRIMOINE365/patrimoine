<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('documents.owner_payout_receipt.title') }}
        {{ str_pad($payout->id, 6, '0', STR_PAD_LEFT) }}
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
    @include('documents.partials.base-styles')
</head>

<body>

@php
    $owner =
        $payout
            ->ownerAccount
            ->party;

    $ownerName =
        $owner->name
        ?? $owner->legal_name
        ?? __('documents.owner_payout_receipt.property_owner');

    /*
     * Persisted payment-method codes remain unchanged.
     * Only their document presentation is translated.
     */
    $paymentMethodKey =
        'documents.common.payment_method.'
        . $payout->payment_method;

    $paymentMethodLabel =
        __($paymentMethodKey);

    if ($paymentMethodLabel === $paymentMethodKey) {
        $paymentMethodLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $payout->payment_method
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

        <td>
            <div class="document-title">
                {{ __('documents.owner_payout_receipt.heading') }}
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    {{ __('documents.owner_payout_receipt.paid_to') }}
                </div>

                <strong>
                    {{ $ownerName }}
                </strong>

                @if($owner->phone)
                    <br>
                    {{ $owner->phone_display }}
                @endif

                @if($owner->email)
                    <br>
                    {{ $owner->email }}
                @endif
            </td>

            <td>
                <strong>
                    {{ __('documents.owner_payout_receipt.payout_number') }}:
                </strong>

                POUT-{{ str_pad(
                    $payout->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ) }}

                <br>

                <strong>
                    {{ __('documents.owner_payout_receipt.payout_date') }}:
                </strong>

                {{ $formatter->date(
                    $payout->payout_date
                ) }}

                <br>

                <strong>
                    {{ __('documents.owner_payout_receipt.method') }}:
                </strong>

                {{ $paymentMethodLabel }}

                @if($payout->reference)
                    <br>

                    <strong>
                        {{ __('documents.owner_payout_receipt.reference') }}:
                    </strong>

                    {{ $payout->reference }}
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
        {{ __('documents.owner_payout_receipt.amount_paid') }}
    </div>

    <div class="payment-amount">
        {{ $formatter->money(
            $payout->amount
        ) }}
    </div>
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.owner_payout_receipt.payout_details') }}
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-label">
                {{ __('documents.owner_payout_receipt.allocations_count') }}
            </td>

            <td class="summary-value">
                {{ $payout->allocations->count() }}
            </td>
        </tr>

        <tr>
            <td class="summary-label">
                {{ __('documents.owner_payout_receipt.current_balance') }}
            </td>

            <td class="summary-value">
                {{ $formatter->money(
                    $ownerBalance
                ) }}
            </td>
        </tr>
    </table>

    <div class="muted" style="margin-top:6px;">
        {{ __('documents.owner_payout_receipt.balance_explanation') }}
    </div>
</div>

@if($payout->notes)
    <div class="section">
        <div class="section-title">
            {{ __('documents.owner_payout_receipt.notes') }}
        </div>

        {{ $payout->notes }}
    </div>
@endif

<div class="footer">
    {{ __('documents.owner_payout_receipt.footer_paid_to') }}
    {{ $ownerName }}
    {{ __('documents.owner_payout_receipt.footer_recorded_by') }}
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.
</div>

</body>
</html>
