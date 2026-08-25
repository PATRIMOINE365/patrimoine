<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.tenant_fund_expense.title') }}
        {{ $transaction->reference }}
    </title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            line-height: 1.45;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .organisation {
            margin-bottom: 18px;
        }

        .muted {
            color: #666;
        }

        .reference {
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        th,
        td {
            padding: 9px;
            border: 1px solid #d7d7d7;
            vertical-align: top;
        }

        th {
            width: 34%;
            text-align: left;
            background: #f5f5f5;
        }

        .description {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="organisation">
        <h1>
            {{ $managingOrganisation?->legal_name
                ?? $managingOrganisation?->name
                ?? 'Patrimoine' }}
        </h1>

        <div class="muted">
            {{ __('documents.common.property_management') }}
        </div>
    </div>

    <div class="reference">
        <strong>
            {{ __('documents.tenant_fund_expense.title') }}
        </strong>

        <div>
            {{ __('documents.tenant_fund_expense.voucher_number') }}:
            {{ $transaction->reference }}
        </div>

        <div>
            {{ __('documents.tenant_fund_expense.date') }}:
            {{ $formatter->date($transaction->transaction_date) }}
        </div>
    </div>

    <table>
        <tr>
            <th>
                {{ __('documents.tenant_fund_expense.tenant') }}
            </th>

            <td>
                {{ $transaction->account->lease->tenant->name
                    ?? $transaction->account->lease->tenant->legal_name }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.tenant_fund_expense.lease') }}
            </th>

            <td>
                {{ $transaction->account->lease->unit->building->name ?? '' }}
                /
                {{ $transaction->account->lease->unit->name ?? '' }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.tenant_fund_expense.source_fund') }}
            </th>

            <td>
                {{ __(
                    'documents.tenant_fund_expense.funds.'
                    . $transaction->account->type
                ) }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.tenant_fund_expense.payment_method') }}
            </th>

            <td>
                {{ __(
                    'documents.common.payment_method.'
                    . $transaction->payment_method
                ) }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th style="width: 66%; background: #efefef;">
                {{ __('documents.tenant_fund_expense.description') }}
            </th>

            <th style="width: 34%; background: #efefef; text-align: right;">
                {{ __('documents.tenant_fund_expense.amount') }}
            </th>
        </tr>

        @foreach ($lines as $line)
            <tr>
                <td class="description">{{ $line->notes }}</td>

                <td style="text-align: right;">
                    {{ $formatter->money($line->amount) }}
                </td>
            </tr>
        @endforeach

        <tr>
            <td style="background: #f5f5f5;">
                <strong>
                    {{ __('documents.tenant_fund_expense.total') }}
                </strong>
            </td>

            <td style="background: #f5f5f5; text-align: right;">
                <strong>
                    {{ $formatter->money($totalAmount) }}
                </strong>
            </td>
        </tr>
    </table>
</body>
</html>
