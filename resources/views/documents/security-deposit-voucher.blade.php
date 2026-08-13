<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Security Deposit Settlement Voucher
    </title>

    <style>
        @page {
            margin: 34px 42px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #0f172a;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-table,
        .summary-table,
        .details-table,
        .deductions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .organisation {
            font-size: 17px;
            font-weight: bold;
        }

        .document-title {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
        }

        .voucher-number {
            margin-top: 4px;
            text-align: right;
            color: #475569;
        }

        .section-title {
            margin: 22px 0 8px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .details-table td {
            width: 50%;
            padding: 5px 0;
            vertical-align: top;
        }

        .label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
        }

        .value {
            margin-top: 2px;
            font-weight: bold;
        }

        .deductions-table th,
        .deductions-table td {
            border: 1px solid #cbd5e1;
            padding: 7px;
            vertical-align: top;
        }

        .deductions-table th {
            background: #f8fafc;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .summary-wrap {
            margin-top: 20px;
            margin-left: 48%;
        }

        .summary-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-table .total {
            font-weight: bold;
            border-top: 2px solid #0f172a;
        }

        .result-box {
            margin-top: 22px;
            padding: 14px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }

        .notes {
            margin-top: 18px;
            padding: 10px 12px;
            background: #f8fafc;
            border-left: 3px solid #94a3b8;
        }

        .footer {
            margin-top: 34px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            font-size: 9px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $lease = $settlement->lease;
        $tenant = $lease->tenant;
        $unit = $lease->unit;
        $building = $unit?->building;

        $organisationName =
            $managingOrganisation?->legal_name
            ?? $managingOrganisation?->name
            ?? 'Patrimoine';

        $money = static fn (int $amount): string =>
            number_format($amount, 0);
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="organisation">
                        {{ $organisationName }}
                    </div>
                </td>

                <td>
                    <div class="document-title">
                        Security Deposit Settlement Voucher
                    </div>

                    <div class="voucher-number">
                        Voucher:
                        {{ $settlement->refund_voucher_number }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">
        Settlement Details
    </div>

    <table class="details-table">
        <tr>
            <td>
                <div class="label">
                    Tenant
                </div>

                <div class="value">
                    {{ $tenant?->name ?? $tenant?->legal_name ?? '—' }}
                </div>
            </td>

            <td>
                <div class="label">
                    Settlement Date
                </div>

                <div class="value">
                    {{ $settlement->settlement_date->format('d M Y') }}
                </div>
            </td>
        </tr>

        <tr>
            <td>
                <div class="label">
                    Property
                </div>

                <div class="value">
                    {{ $building?->name ?? '—' }}
                </div>
            </td>

            <td>
                <div class="label">
                    Unit
                </div>

                <div class="value">
                    {{ $unit?->name ?? '—' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">
        Itemized Deductions
    </div>

    <table class="deductions-table">
        <thead>
            <tr>
                <th style="width: 15%;">
                    Date
                </th>

                <th>
                    Description
                </th>

                <th style="width: 20%;">
                    Reference
                </th>

                <th
                    class="amount"
                    style="width: 18%;"
                >
                    Amount
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse ($deductions as $deduction)
                <tr>
                    <td>
                        {{ $deduction->deduction_date->format('d M Y') }}
                    </td>

                    <td>
                        {{ $deduction->description }}

                        @if ($deduction->notes)
                            <div style="margin-top: 3px; color: #64748b;">
                                {{ $deduction->notes }}
                            </div>
                        @endif
                    </td>

                    <td>
                        {{ $deduction->reference ?: '—' }}
                    </td>

                    <td class="amount">
                        {{ $money($deduction->amount) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="4"
                        style="text-align: center; color: #64748b;"
                    >
                        No deductions were recorded.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary-table">
            <tr>
                <td>
                    Security Deposit Held
                </td>

                <td class="amount">
                    {{ $money($settlement->deposit_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Total Deductions
                </td>

                <td class="amount">
                    {{ $money($settlement->deduction_amount) }}
                </td>
            </tr>

            <tr class="total">
                <td>
                    Refund Due
                </td>

                <td class="amount">
                    {{ $money($settlement->refund_amount) }}
                </td>
            </tr>

            <tr>
                <td>
                    Tenant Debt
                </td>

                <td class="amount">
                    {{ $money($settlement->tenant_debt_amount) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="result-box">
        @if ($settlement->refund_amount > 0)
            The tenant is due a Security Deposit refund of
            <strong>{{ $money($settlement->refund_amount) }}</strong>.
        @elseif ($settlement->tenant_debt_amount > 0)
            The Security Deposit has been fully applied and an outstanding
            tenant debt of
            <strong>{{ $money($settlement->tenant_debt_amount) }}</strong>
            remains.
        @else
            The Security Deposit has been fully settled with no refund and
            no remaining tenant debt.
        @endif
    </div>

    @if ($settlement->notes)
        <div class="notes">
            <strong>Settlement Notes</strong><br>
            {{ $settlement->notes }}
        </div>
    @endif

    <div class="footer">
        This document records the final Security Deposit close-out retained
        by Patrimoine under voucher
        {{ $settlement->refund_voucher_number }}.
    </div>
</body>
</html>
