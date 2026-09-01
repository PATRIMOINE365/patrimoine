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
         * with a long name ("Akwaba Property Management") wrapped onto
         * two lines and pushed the title onto two beside it. The cells
         * are given widths, both take a top alignment, and the title is
         * small enough to hold one line inside its share.
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
        /*
         * The workings.
         *
         * A receipt for a busy month runs to a lot of rows, so these are
         * pulled tighter than the rest of the document: an owner reading
         * one wants the whole month on as few pages as it will go.
         */
        .working-section {
            margin-top: 18px;
        }

        .working-table {
            width: 100%;
            margin-top: 4px;
            border-collapse: collapse;
        }

        .working-table td,
        .working-table th {
            padding: 3px 0;
            vertical-align: top;
        }

        .working-table thead th {
            border-bottom: 1px solid #DDE6E2;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            color: #66736F;
            padding-bottom: 4px;
        }

        /* The row number: narrow, quiet, and there to be referred to. */
        .working-number {
            width: 22px;
            color: #66736F;
        }

        .working-value {
            text-align: right;
            width: 24%;
            white-space: nowrap;
        }

        .working-total td {
            border-top: 1px solid #DDE6E2;
            padding-top: 5px;
            font-weight: bold;
        }

        .working-period {
            font-size: 9px;
            color: #66736F;
            margin-bottom: 2px;
        }

        /*
         * The summary sits directly under the amount, because it is the
         * thing a person checks first and everything below it is the
         * evidence for it.
         */
        .summary-block {
            margin-top: 20px;
        }

        .summary-block .working-total td {
            border-top: 1px solid #DDE6E2;
        }

        .summary-rule td {
            border-top: 1px solid #DDE6E2;
            padding-top: 5px;
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

        The summary comes first, directly under the amount, because it is
        what a person checks; the three tables under it are the evidence,
        and every movement in the period is in exactly one of them. Each
        table's total is therefore the summary line that names it, and an
        owner can add the tables up and arrive at the payout.
    --}}
    <div class="section summary-block">
        <div class="section-title">
            {{ __('documents.owner_payout_receipt.summary') }}
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
            <tr>
                <td>{{ __('documents.owner_payout_receipt.brought_forward') }}</td>
                <td class="working-value">
                    {{ $formatter->money($breakdown['brought_forward']) }}
                </td>
            </tr>

            <tr>
                <td>{{ __('documents.owner_payout_receipt.total_received') }}</td>
                <td class="working-value">
                    {{ $formatter->money($breakdown['received_total']) }}
                </td>
            </tr>

            <tr>
                <td>{{ __('documents.owner_payout_receipt.total_deductions') }}</td>
                <td class="working-value">
                    &minus; {{ $formatter->money($breakdown['deductions_total']) }}
                </td>
            </tr>

            <tr>
                <td>{{ __('documents.owner_payout_receipt.total_expenses') }}</td>
                <td class="working-value">
                    &minus; {{ $formatter->money($breakdown['expenses_total']) }}
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

            <tr class="summary-rule">
                <td>{{ __('documents.owner_payout_receipt.this_payout') }}</td>
                <td class="working-value">
                    &minus; {{ $formatter->money($breakdown['amount']) }}
                </td>
            </tr>

            <tr class="working-total">
                <td>{{ __('documents.owner_payout_receipt.carried_forward') }}</td>
                <td class="working-value">
                    {{ $formatter->money($breakdown['carried_forward']) }}
                </td>
            </tr>
        </table>
    </div>

    @php
        /*
         * V1.0.48: internal reserve transfers get their own table, with
         * zero effect on the summary above. Statements frozen before
         * that carry no transfers key, and nothing is shown for them;
         * an empty table is likewise not worth a section.
         */
        $breakdownTables = [
            ['rows' => $breakdown['received'],   'total' => $breakdown['received_total'],   'title' => 'received_table',   'empty' => 'nothing_received'],
            ['rows' => $breakdown['deductions'], 'total' => $breakdown['deductions_total'], 'title' => 'deductions_table', 'empty' => 'nothing_deducted'],
            ['rows' => $breakdown['expenses'],   'total' => $breakdown['expenses_total'],   'title' => 'expenses_table',   'empty' => 'nothing_spent'],
        ];

        if (! empty($breakdown['transfers'] ?? [])) {
            $breakdownTables[] = [
                'rows' => $breakdown['transfers'],
                'total' => $breakdown['transfers_total'] ?? 0,
                'title' => 'transfers_table',
                'empty' => 'nothing_transferred',
            ];
        }
    @endphp

    @foreach($breakdownTables as $table)
        <div class="section working-section">
            <div class="section-title">
                {{ __('documents.owner_payout_receipt.'.$table['title']) }}
            </div>

            <table class="working-table">
                @if(count($table['rows']) > 0)
                    <thead>
                        <tr>
                            <th class="working-number">
                                {{ __('documents.owner_payout_receipt.column_number') }}
                            </th>

                            <th>
                                {{ __('documents.owner_payout_receipt.column_detail') }}
                            </th>

                            <th class="working-value" style="text-align:right;">
                                {{ __('documents.owner_payout_receipt.column_amount') }}
                            </th>
                        </tr>
                    </thead>
                @endif

                <tbody>
                    @forelse($table['rows'] as $row)
                        <tr>
                            <td class="working-number">{{ $row['number'] }}</td>

                            <td>
                                @include('documents.partials.payout-row', [
                                    'row' => $row,
                                    'formatter' => $formatter,
                                ])
                            </td>

                            <td class="working-value">
                                {{ $formatter->money($row['amount']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">
                                {{ __('documents.owner_payout_receipt.'.$table['empty']) }}
                            </td>
                        </tr>
                    @endforelse

                    <tr class="working-total">
                        <td></td>

                        <td>{{ __('documents.owner_payout_receipt.total') }}</td>

                        <td class="working-value">
                            {{ $formatter->money($table['total']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
@endif

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
