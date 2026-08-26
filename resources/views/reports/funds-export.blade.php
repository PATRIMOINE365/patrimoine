<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>{{ __('reports.funds_report') }}</title>

    <style>
        @page {
            margin: 22px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        h2 {
            margin: 14px 0 6px;
            font-size: 12px;
        }

        .summary {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        .amount {
            text-align: right;
        }
    </style>
</head>

<body>
    @include('reports.partials.letterhead', [
        'title' => __('reports.funds_report'),
    ])

    <div class="summary">
        <strong>{{ __('reports.as_of') }}:</strong>
        {{ $formatter->date($asOf) }}
    </div>

    <h2>
        {{ __('reports.labels.tenant_funds') }}
    </h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.labels.type') }}</th>
                <th>{{ __('reports.labels.account_count') }}</th>
                <th>{{ __('reports.labels.total_held') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach (['rent_reserve', 'consumable_advance', 'security_deposit'] as $fundType)
                <tr>
                    <td>{{ __('reports.labels.'.$fundType) }}</td>

                    <td class="amount">
                        {{ $tenantSummary[$fundType]['account_count'] ?? 0 }}
                    </td>

                    <td class="amount">
                        {{ $formatter->money($tenantSummary[$fundType]['total_held'] ?? 0) }}
                    </td>
                </tr>
            @endforeach

            <tr>
                <td><strong>{{ __('reports.labels.total') }}</strong></td>

                <td class="amount"></td>

                <td class="amount">
                    <strong>{{ $formatter->money($tenantSummary['total_held'] ?? 0) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top: 8px;">
        <thead>
            <tr>
                @foreach ($tenantColumns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($tenantRows as $row)
                <tr>
                    @foreach (array_keys($tenantColumns) as $column)
                        <td class="{{ in_array($column, ['tenant', 'lease', 'building', 'unit'], true) ? '' : 'amount' }}">
                            {{ $row[$column] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($tenantColumns) }}">
                        {{ __('reports.no_records_for_section') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>
        {{ __('reports.labels.owner_funds') }}
    </h2>

    <div class="summary">
        <strong>{{ __('reports.labels.account_count') }}:</strong>
        {{ $ownerSummary['account_count'] ?? 0 }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.total_held') }}:</strong>
        {{ $formatter->money($ownerSummary['total_held'] ?? 0) }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($ownerColumns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($ownerRows as $row)
                <tr>
                    @foreach (array_keys($ownerColumns) as $column)
                        <td class="{{ $column === 'owner' ? '' : 'amount' }}">
                            {{ $row[$column] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($ownerColumns) }}">
                        {{ __('reports.no_records_for_section') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.footer')
</body>
</html>
