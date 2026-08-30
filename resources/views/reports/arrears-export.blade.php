<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>{{ __('reports.arrears_report') }}</title>

    @include('documents.partials.fonts')

    <style>
        @page {
            margin: 22px;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #17201E;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .summary {
            margin-bottom: 14px;
            line-height: 1.6;
            color: #4E5B56;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #DDE6E2;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #F7F5EF;
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
        'title' => __('reports.arrears_report'),
    ])

    <div class="summary">
        <strong>{{ __('reports.as_of') }}:</strong>
        {{ $formatter->date($asOf) }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.invoices_count') }}:</strong>
        {{ $totals['invoice_count'] ?? 0 }}

        <br>

        <strong>{{ __('reports.labels.current_0_30') }}:</strong>
        {{ $formatter->money($totals['current'] ?? 0) }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.days_31_60') }}:</strong>
        {{ $formatter->money($totals['days_31_60'] ?? 0) }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.days_61_90') }}:</strong>
        {{ $formatter->money($totals['days_61_90'] ?? 0) }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.over_90') }}:</strong>
        {{ $formatter->money($totals['over_90'] ?? 0) }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.grand_total') }}:</strong>
        {{ $formatter->money($totals['total'] ?? 0) }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach (array_keys($columns) as $column)
                        <td class="{{ in_array($column, ['tenant', 'lease', 'building', 'unit'], true) ? '' : 'amount' }}">
                            {{ $row[$column] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">
                        {{ __('reports.no_records_for_section') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.footer')
</body>
</html>
