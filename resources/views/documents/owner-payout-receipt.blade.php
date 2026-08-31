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
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #17201E;
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

        .payment-box {
            margin-top: 24px;
            padding: 18px;
            border: 1px solid #DDE6E2;
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

        /*
         * The workings. A ledger table rather than a summary one: the
         * label is not the emphasis, the figure is, and the rows have to
         * sit tight enough that a period with a dozen movements still
         * fits on the page with the receipt above it.
         */
        .working-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }

        .working-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .working-value {
            text-align: right;
            width: 30%;
            white-space: nowrap;
        }

        .working-total td {
            border-top: 1px solid #DDE6E2;
            padding-top: 6px;
            font-weight: bold;
        }

        .working-period {
            font-size: 9px;
            color: #66736F;
            margin-bottom: 2px;
        }

        .reconciliation td {
            padding: 4px 0;
        }

        .reconciliation .final td {
            border-top: 1px solid #DDE6E2;
            padding-top: 6px;
            font-weight: bold;
        }

        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #DDE6E2;
            text-align: center;
            font-size: 9px;
            color: #66736F;
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

@if($breakdown)
    {{--
        How the figure above was arrived at.

        The receipt used to show the amount, a count of the ledger rows it
        consumed, and the balance left, which an owner could not check
        against anything. This is the period they actually ask about —
        since they last collected — with what came in, what was taken off,
        and the arithmetic between the two.

        Totals are the period's own credit and debit sums rather than the
        sum of the lines printed, so the reconciliation holds even if a
        movement appears that this receipt does not have a name for; it is
        printed as "Other".
    --}}
    <div class="section">
        <div class="section-title">
            {{ __('documents.owner_payout_receipt.money_in') }}
        </div>

        <div class="working-period">
            @if($breakdown['has_previous_payout'])
                {{ __('documents.owner_payout_receipt.period_since_payout', [
                    'from' => $formatter->date($breakdown['from']),
                    'to' => $formatter->date($breakdown['to']),
                ]) }}
            @else
                {{ __('documents.owner_payout_receipt.period_from_start', [
                    'to' => $formatter->date($breakdown['to']),
                ]) }}
            @endif
        </div>

        <table class="working-table">
            @forelse($breakdown['received'] as $line)
                <tr>
                    <td>
                        {{ __('documents.owner_payout_receipt.lines.'.$line['key']) }}
                    </td>

                    <td class="working-value">
                        {{ $formatter->money($line['amount']) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="muted">
                        {{ __('documents.owner_payout_receipt.nothing_received') }}
                    </td>
                </tr>
            @endforelse

            <tr class="working-total">
                <td>{{ __('documents.owner_payout_receipt.total_in') }}</td>

                <td class="working-value">
                    {{ $formatter->money($breakdown['received_total']) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">
            {{ __('documents.owner_payout_receipt.deducted') }}
        </div>

        <table class="working-table">
            @forelse($breakdown['deducted'] as $line)
                <tr>
                    <td>
                        {{ __('documents.owner_payout_receipt.lines.'.$line['key']) }}
                    </td>

                    <td class="working-value">
                        {{ $formatter->money($line['amount']) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="muted">
                        {{ __('documents.owner_payout_receipt.nothing_deducted') }}
                    </td>
                </tr>
            @endforelse

            <tr class="working-total">
                <td>{{ __('documents.owner_payout_receipt.total_deducted') }}</td>

                <td class="working-value">
                    {{ $formatter->money($breakdown['deducted_total']) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">
            {{ __('documents.owner_payout_receipt.reconciliation') }}
        </div>

        <table class="working-table reconciliation">
            <tr>
                <td>{{ __('documents.owner_payout_receipt.brought_forward') }}</td>

                <td class="working-value">
                    {{ $formatter->money($breakdown['brought_forward']) }}
                </td>
            </tr>

            <tr>
                <td>{{ __('documents.owner_payout_receipt.total_in') }}</td>

                <td class="working-value">
                    {{ $formatter->money($breakdown['received_total']) }}
                </td>
            </tr>

            <tr>
                <td>{{ __('documents.owner_payout_receipt.total_deducted') }}</td>

                <td class="working-value">
                    &minus; {{ $formatter->money($breakdown['deducted_total']) }}
                </td>
            </tr>

            <tr class="working-total">
                <td>{{ __('documents.owner_payout_receipt.available') }}</td>

                <td class="working-value">
                    {{ $formatter->money($breakdown['available']) }}
                </td>
            </tr>

            @if($breakdown['other_payouts'] !== 0)
                <tr>
                    <td>{{ __('documents.owner_payout_receipt.other_payouts') }}</td>

                    <td class="working-value">
                        &minus; {{ $formatter->money($breakdown['other_payouts']) }}
                    </td>
                </tr>
            @endif

            <tr>
                <td>{{ __('documents.owner_payout_receipt.this_payout') }}</td>

                <td class="working-value">
                    &minus; {{ $formatter->money($breakdown['amount']) }}
                </td>
            </tr>

            <tr class="final working-total">
                <td>{{ __('documents.owner_payout_receipt.carried_forward') }}</td>

                <td class="working-value">
                    {{ $formatter->money($breakdown['carried_forward']) }}
                </td>
            </tr>
        </table>
    </div>
@endif

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
