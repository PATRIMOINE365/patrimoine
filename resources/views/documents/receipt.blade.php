<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('documents.receipt.title') }}
        {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
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

        .summary-table {
            margin-top: 8px;
        }

        .summary-table td {
            padding: 5px 0;
            vertical-align: top;
        }

        .summary-table .summary-label {
            font-weight: bold;
        }

        .summary-table .summary-amount {
            text-align: right;
            white-space: nowrap;
        }

        .summary-table .summary-detail {
            color: #555;
            padding-left: 16px;
        }

        .summary-table .total-row td {
            padding-top: 8px;
            border-top: 1px solid #aaa;
            font-weight: bold;
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
    /*
     * Payment money may be used in several different ways:
     *
     * - allocated to rent invoices;
     * - protected as Rent Reserve;
     * - retained as Consumable Advance;
     * - held as Security Deposit;
     * - or remain genuinely unclassified.
     *
     * The receipt should explain these destinations clearly rather than
     * describing all non-invoice money simply as "Unallocated".
     */
/*
 * Separate normal rent allocations from other tenant receivables.
 *
 * PaymentAllocation is shared infrastructure, but not every Invoice
 * represents rent. In particular, Security Deposit close-out debt uses
 * the same allocation mechanism without becoming rent revenue.
 */
$rentAllocatedAmount =
    (int) $payment
        ->allocations
        ->filter(
            fn ($allocation) =>
                $allocation->invoice?->isRentInvoice()
        )
        ->sum('amount');

$securityDepositDebtAllocatedAmount =
    (int) $payment
        ->allocations
        ->filter(
            fn ($allocation) =>
                $allocation->invoice
                    ?->isSecurityDepositDebtInvoice()
        )
        ->sum('amount');

/*
 * Total money allocated to receivable Invoices regardless of type.
 */
$allocatedAmount =
    $rentAllocatedAmount
    + $securityDepositDebtAllocatedAmount;

    $rentReserveAmount =
        (int) \App\Models\TenantFundTransaction::query()
            ->where(
                'payment_id',
                $payment->id
            )
            ->where(
                'direction',
                'credit'
            )
            ->where(
                'category',
                'reserve_funding'
            )
            ->sum('amount');

    $consumableAdvanceAmount =
        (int) \App\Models\TenantFundTransaction::query()
            ->where(
                'payment_id',
                $payment->id
            )
            ->where(
                'direction',
                'credit'
            )
            ->where(
                'category',
                'advance_funding'
            )
            ->sum('amount');

    $securityDepositAmount =
        (int) \App\Models\TenantFundTransaction::query()
            ->where(
                'payment_id',
                $payment->id
            )
            ->where(
                'direction',
                'credit'
            )
            ->where(
                'category',
                'deposit_funding'
            )
            ->sum('amount');

    $classifiedFundAmount =
        $rentReserveAmount
        + $consumableAdvanceAmount
        + $securityDepositAmount;

    /*
     * Any positive amount here represents money that has genuinely not yet
     * been assigned either to rent or to a tenant-held fund.
     */
    $unclassifiedAmount =
        max(
            0,
            $payment->amount
            - $allocatedAmount
            - $classifiedFundAmount
        );

    $accountedAmount =
        $allocatedAmount
        + $classifiedFundAmount;

    /*
     * Translate only the user-facing payment-method label.
     * Persisted payment_method values remain unchanged.
     */
    $paymentMethodKey =
        'documents.common.payment_method.'
        . $payment->payment_method;

    $paymentMethodLabel =
        __($paymentMethodKey);

    if ($paymentMethodLabel === $paymentMethodKey) {
        $paymentMethodLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $payment->payment_method
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
                {{ __('documents.receipt.heading') }}
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    {{ __('documents.receipt.received_from') }}
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
                <strong>
                    {{ __('documents.receipt.receipt_number') }}:
                </strong>
                RCT-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
                <br>

                <strong>
                    {{ __('documents.receipt.payment_date') }}:
                </strong>
                {{ $formatter->date($payment->payment_date) }}
                <br>

                <strong>
                    {{ __('documents.receipt.method') }}:
                </strong>
                {{ $paymentMethodLabel }}

                @if($payment->reference)
                    <br>
                    <strong>
                        {{ __('documents.receipt.reference') }}:
                    </strong>
                    {{ $payment->reference }}
                @endif

                @if(($payment->cash_receiver_name ?? $payment->collector_name))
                    <br>
                    <strong>
                        {{ __('documents.receipt.collector') }}:
                    </strong>
                    {{ ($payment->cash_receiver_name ?? $payment->collector_name) }}
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="payment-box">
    <div class="muted" style="text-align:center;">
        {{ __('documents.receipt.amount_received') }}
    </div>

    <div class="payment-amount">
        {{ $formatter->money($payment->amount) }}
    </div>
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.receipt.property') }}
    </div>

    <strong>{{ __('documents.receipt.building') }}:</strong>
    {{ $payment->lease->unit->building->name }}

    <br>

    <strong>{{ __('documents.receipt.unit') }}:</strong>
    {{ $payment->lease->unit->name }}

    <br>

    <strong>{{ __('documents.receipt.lease_number') }}:</strong>
    {{ $payment->lease_id }}
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.receipt.payment_allocation') }}
    </div>

@if($payment->allocations->isEmpty())
    <p>
        {{ __('documents.receipt.no_direct_allocation') }}
    </p>
    @else
        <table class="allocation-table">
<thead>
    <tr>
        <th>{{ __('documents.receipt.invoice') }}</th>
        <th>{{ __('documents.receipt.purpose') }}</th>
        <th>{{ __('documents.receipt.period') }}</th>
        <th class="numeric">
            {{ __('documents.receipt.allocated') }}
        </th>
    </tr>
</thead>

<tbody>
    @foreach($payment->allocations as $allocation)
        <tr>
            <td>
                {{ $allocation->invoice->invoice_number }}
            </td>

            <td>
                @if($allocation->invoice->isSecurityDepositDebtInvoice())
                    {{ __('documents.receipt.security_deposit_debt') }}
                @else
                    {{ __('documents.receipt.rent') }}
                @endif
            </td>

            <td>
                @if($allocation->invoice->isSecurityDepositDebtInvoice())
                    {{ __('documents.receipt.close_out') }}
                @else
                    {{ $formatter->date($allocation->invoice->period_start) }}
                    -
                    {{ $formatter->date($allocation->invoice->period_end) }}
                @endif
            </td>

            <td class="numeric">
                {{ $formatter->money($allocation->amount) }}
            </td>
        </tr>
    @endforeach
</tbody>
        </table>
    @endif
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.receipt.payment_summary') }}
    </div>

    <table class="summary-table">
        <tr>
            <td class="summary-label">
                {{ __('documents.receipt.amount_received') }}
            </td>

            <td class="summary-amount">
                {{ $formatter->money($payment->amount) }}
            </td>
        </tr>

@if($rentAllocatedAmount > 0)
    <tr>
        <td class="summary-detail">
            {{ __('documents.receipt.applied_to_rent') }}
        </td>

        <td class="summary-amount">
            {{ $formatter->money($rentAllocatedAmount) }}
        </td>
    </tr>
@endif

@if($securityDepositDebtAllocatedAmount > 0)
    <tr>
        <td class="summary-detail">
            {{ __('documents.receipt.applied_to_security_deposit_debt') }}
        </td>

        <td class="summary-amount">
            {{ $formatter->money($securityDepositDebtAllocatedAmount) }}
        </td>
    </tr>
@endif

        @if($rentReserveAmount > 0)
            <tr>
                <td class="summary-detail">
                    {{ __('documents.receipt.held_as_rent_reserve') }}
                </td>

                <td class="summary-amount">
                    {{ $formatter->money($rentReserveAmount) }}
                </td>
            </tr>
        @endif

        @if($consumableAdvanceAmount > 0)
            <tr>
                <td class="summary-detail">
                    {{ __('documents.receipt.held_as_consumable_advance') }}
                </td>

                <td class="summary-amount">
                    {{ $formatter->money($consumableAdvanceAmount) }}
                </td>
            </tr>
        @endif

        @if($securityDepositAmount > 0)
            <tr>
                <td class="summary-detail">
                    {{ __('documents.receipt.held_as_security_deposit') }}
                </td>

                <td class="summary-amount">
                    {{ $formatter->money($securityDepositAmount) }}
                </td>
            </tr>
        @endif

        @if($unclassifiedAmount > 0)
            <tr>
                <td class="summary-detail">
                    {{ __('documents.receipt.unclassified_balance') }}
                </td>

                <td class="summary-amount">
                    {{ $formatter->money($unclassifiedAmount) }}
                </td>
            </tr>
        @endif

        <tr class="total-row">
            <td>
                {{ __('documents.receipt.accounted_for') }}
            </td>

            <td class="summary-amount">
                {{ $formatter->money($accountedAmount) }}
            </td>
        </tr>
    </table>
</div>

@if($payment->notes)
    <div class="section">
        <div class="section-title">
            {{ __('documents.receipt.notes') }}
        </div>

        {{ $payment->notes }}
    </div>
@endif

<div class="footer">
    {{ __('documents.receipt.footer_prefix') }}
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.
</div>

</body>
</html>
