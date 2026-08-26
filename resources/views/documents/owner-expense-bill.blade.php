<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('documents.owner_expense_bill.title') }}
        {{ $bill->bill_number }}
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

        .lines-table th {
            text-align: left;
            padding: 6px 8px;
            border-bottom: 2px solid #222;
            font-size: 10px;
            text-transform: uppercase;
        }

        .lines-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .amount-column {
            text-align: right;
            white-space: nowrap;
        }

        .total-row td {
            padding: 8px;
            border-bottom: none;
            border-top: 2px solid #222;
            font-weight: bold;
            font-size: 13px;
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
        $bill
            ->ownerAccount
            ->party;

    $ownerName =
        $owner->name
        ?? $owner->legal_name
        ?? __('documents.owner_expense_bill.property_owner');
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
                {{ __('documents.owner_expense_bill.heading') }}
            </div>
        </td>
    </tr>
</table>

<div class="section">
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">
                    {{ __('documents.owner_expense_bill.billed_to') }}
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
                    {{ __('documents.owner_expense_bill.bill_number') }}:
                </strong>

                {{ $bill->bill_number }}

                <br>

                <strong>
                    {{ __('documents.owner_expense_bill.bill_date') }}:
                </strong>

                {{ $formatter->date(
                    $bill->bill_date
                ) }}
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">
        {{ __('documents.owner_expense_bill.expense_lines') }}
    </div>

    <table class="lines-table">
        <thead>
            <tr>
                <th>
                    {{ __('documents.owner_expense_bill.description') }}
                </th>

                <th class="amount-column">
                    {{ __('documents.owner_expense_bill.amount') }}
                </th>
            </tr>
        </thead>

        <tbody>
            @foreach($bill->expenses as $expense)
                <tr>
                    <td>
                        {{ $expense->description }}
                    </td>

                    <td class="amount-column">
                        {{ $formatter->money(
                            $expense->amount
                        ) }}
                    </td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td>
                    {{ __('documents.owner_expense_bill.total') }}
                </td>

                <td class="amount-column">
                    {{ $formatter->money(
                        $bill->total_amount
                    ) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

@if($bill->notes)
    <div class="section">
        <div class="section-title">
            {{ __('documents.owner_expense_bill.notes') }}
        </div>

        {{ $bill->notes }}
    </div>
@endif

<div class="footer">
    {{ __('documents.owner_expense_bill.footer_billed_to') }}
    {{ $ownerName }}
    {{ __('documents.owner_expense_bill.footer_recorded_by') }}
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}.
</div>

</body>
</html>
