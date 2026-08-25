<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.owner_reserve_transfer.title') }}
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

        .reason {
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
            {{ __('documents.owner_reserve_transfer.title') }}
        </strong>

        <div>
            {{ __('documents.owner_reserve_transfer.voucher_number') }}:
            {{ $transaction->reference }}
        </div>

        <div>
            {{ __('documents.owner_reserve_transfer.date') }}:
            {{ $formatter->date($transaction->transaction_date) }}
        </div>
    </div>

    <table>
        <tr>
            <th>
                {{ __('documents.owner_reserve_transfer.owner') }}
            </th>

            <td>
                {{ $transaction->ownerAccount->party->legal_name
                    ?? $transaction->ownerAccount->party->name }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.owner_reserve_transfer.from_account') }}
            </th>

            <td>
                {{ $transaction->direction === 'credit'
                    ? __('documents.owner_reserve_transfer.payout_account')
                    : __('documents.owner_reserve_transfer.deposit_account') }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.owner_reserve_transfer.to_account') }}
            </th>

            <td>
                {{ $transaction->direction === 'credit'
                    ? __('documents.owner_reserve_transfer.deposit_account')
                    : __('documents.owner_reserve_transfer.payout_account') }}
            </td>
        </tr>

        <tr>
            <th>
                {{ __('documents.owner_reserve_transfer.amount') }}
            </th>

            <td>
                <strong>
                    {{ $formatter->money($transaction->amount) }}
                </strong>
            </td>
        </tr>

        @if (trim((string) $transaction->notes) !== '')
            <tr>
                <th>
                    {{ __('documents.owner_reserve_transfer.reason') }}
                </th>

                <td class="reason">{{ $transaction->notes }}</td>
            </tr>
        @endif
    </table>
</body>
</html>
