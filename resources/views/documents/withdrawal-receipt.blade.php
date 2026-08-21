<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.withdrawal_receipt.title') }}
        {{ $receipt->receipt_number }}
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border: 1px solid #d7d7d7;
            padding: 9px;
            vertical-align: top;
        }

        th {
            width: 34%;
            text-align: left;
            background: #f5f5f5;
        }

        .reference {
            margin-bottom: 18px;
        }
    </style>
</head>
<body>

<h1>
    {{ __('documents.withdrawal_receipt.title') }}
</h1>

<div class="reference">
    <strong>
        {{ __('documents.withdrawal_receipt.receipt_number') }}:
    </strong>

    {{ $receipt->receipt_number }}
</div>

<table>
    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.tenant') }}
        </th>
        <td>{{ $receipt->tenant_name }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.lease') }}
        </th>
        <td>{{ $receipt->lease_label }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.building') }}
        </th>
        <td>{{ $receipt->building_label ?: '—' }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.unit') }}
        </th>
        <td>{{ $receipt->unit_label ?: '—' }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.fund') }}
        </th>
        <td>{{ $receipt->fund_type }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.amount') }}
        </th>
        <td>{{ $formatter->money($receipt->amount) }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.payment_method') }}
        </th>
        <td>{{ $receipt->payment_method }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.date') }}
        </th>
        <td>{{ $formatter->date($receipt->transaction_date) }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.reference') }}
        </th>
        <td>{{ $receipt->reference ?: '—' }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.notes') }}
        </th>
        <td>{{ $receipt->notes ?: '—' }}</td>
    </tr>

    <tr>
        <th>
            {{ __('documents.withdrawal_receipt.performed_by') }}
        </th>
        <td>{{ $receipt->performed_by_name }}</td>
    </tr>
</table>

</body>
</html>
