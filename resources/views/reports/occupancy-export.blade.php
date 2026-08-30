<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>{{ __('reports.occupancy_report') }}</title>

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

        h2 {
            margin: 14px 0 6px;
            font-size: 12px;
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

        .number {
            text-align: right;
        }
    </style>
</head>

<body>
    @include('reports.partials.letterhead', [
        'title' => __('reports.occupancy_report'),
    ])

    <div class="summary">
        <strong>{{ __('reports.as_of') }}:</strong>
        {{ $formatter->date($asOf) }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.units') }}:</strong>
        {{ $totals['units'] ?? 0 }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.occupied') }}:</strong>
        {{ $totals['occupied'] ?? 0 }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.vacant') }}:</strong>
        {{ $totals['vacant'] ?? 0 }}

        &nbsp;&nbsp;

        <strong>{{ __('reports.labels.occupancy_rate') }}:</strong>
        {{ $totals['occupancy_rate'] ?? 0 }}%
    </div>

    <h2>
        {{ __('reports.labels.classification') }}
    </h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.labels.classification') }}</th>
                <th>{{ __('reports.labels.units') }}</th>
                <th>{{ __('reports.labels.occupied') }}</th>
                <th>{{ __('reports.labels.vacant') }}</th>
                <th>{{ __('reports.labels.occupancy_rate') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach (['commercial', 'residential'] as $segment)
                <tr>
                    <td>{{ __('reports.labels.'.$segment) }}</td>

                    <td class="number">
                        {{ $classification[$segment]['units'] ?? 0 }}
                    </td>

                    <td class="number">
                        {{ $classification[$segment]['occupied'] ?? 0 }}
                    </td>

                    <td class="number">
                        {{ $classification[$segment]['vacant'] ?? 0 }}
                    </td>

                    <td class="number">
                        {{ $classification[$segment]['occupancy_rate'] ?? 0 }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>
        {{ __('reports.labels.buildings') }}
    </h2>

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
                        <td class="{{ $column === 'building' ? '' : 'number' }}">
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
