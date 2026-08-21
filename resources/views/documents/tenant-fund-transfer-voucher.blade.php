<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.tenant_fund_transfer_voucher.title') }}
        {{ $debitTransaction->reference }}
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

@php
    $sourceAccount = $debitTransaction->account;
    $destinationAccount = $creditTransaction->account;

    $tenant = $sourceAccount?->lease?->tenant;

    $fundLabel = fn (?string $type): string => __(
        'documents.tenant_fund_transfer_voucher.funds.'.$type
    );
@endphp

<div class="organisation">
    <strong>
        {{ $managingOrganisation?->legal_name
            ?? $managingOrganisation?->name
            ?? 'Patrimoine' }}
    </strong>

    <div class="muted">
        {{ __('documents.common.property_management') }}
    </div>
</div>

<h1>
    {{ __('documents.tenant_fund_transfer_voucher.title') }}
</h1>

<div class="reference">
    <strong>
        {{ __('documents.tenant_fund_transfer_voucher.voucher_number') }}:
    </strong>

    {{ $debitTransaction->reference }}
</div>

<table>
    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.date') }}
        </th>

        <td>
            {{ $formatter->date($debitTransaction->transaction_date) }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.tenant') }}
        </th>

        <td>
            {{ $tenant?->name ?? '—' }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.source_account') }}
        </th>

        <td>
            {{ $fundLabel($sourceAccount?->type) }}
            —
            {{ __('documents.tenant_fund_transfer_voucher.lease') }}
            #{{ $sourceAccount?->lease_id }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.destination_account') }}
        </th>

        <td>
            {{ $fundLabel($destinationAccount?->type) }}
            —
            {{ __('documents.tenant_fund_transfer_voucher.lease') }}
            #{{ $destinationAccount?->lease_id }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.amount') }}
        </th>

        <td>
            {{ $formatter->money($debitTransaction->amount) }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.reason') }}
        </th>

        <td class="reason">
            {{ $debitTransaction->notes }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.debit_transaction') }}
        </th>

        <td>
            #{{ $debitTransaction->id }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.tenant_fund_transfer_voucher.credit_transaction') }}
        </th>

        <td>
            #{{ $creditTransaction->id }}
        </td>
    </tr>
</table>

</body>
</html>
