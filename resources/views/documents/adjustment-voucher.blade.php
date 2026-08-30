<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('documents.adjustment_voucher.title') }}
        {{ $voucher->voucher_number }}
    </title>

    <style>
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #17201E;
            line-height: 1.45;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 4px;
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
            border: 1px solid #DDE6E2;
            vertical-align: top;
        }

        th {
            width: 34%;
            text-align: left;
            background: #F7F5EF;
        }

        .reason {
            white-space: pre-wrap;
        }
    </style>
    @include('documents.partials.base-styles')
</head>
<body>

@include('documents.partials.letterhead')

<h1>
    {{ __('documents.adjustment_voucher.title') }}
</h1>

<div class="reference">
    <strong>
        {{ __('documents.adjustment_voucher.voucher_number') }}:
    </strong>

    {{ $voucher->voucher_number }}
</div>

<table>
    <tr>
        <th>
            {{ __('documents.adjustment_voucher.date') }}
        </th>

        <td>
            {{ $formatter->date($voucher->adjustment_date) }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.context') }}
        </th>

        <td>
            {{ $voucher->entity_label }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.account') }}
        </th>

        <td>
            {{ $voucher->account_label }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.previous_balance') }}
        </th>

        <td>
            {{ $formatter->money($voucher->previous_balance) }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.corrected_balance') }}
        </th>

        <td>
            {{ $formatter->money($voucher->corrected_balance) }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.difference') }}
        </th>

        <td>
            {{ $formatter->money($voucher->difference) }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.reason') }}
        </th>

        <td class="reason">
            {{ $voucher->reason }}
        </td>
    </tr>

    <tr>
        <th>
            {{ __('documents.adjustment_voucher.performed_by') }}
        </th>

        <td>
            {{ $voucher->performed_by_name }}
        </td>
    </tr>
</table>

@include('documents.partials.doc-footer')

</body>
</html>
