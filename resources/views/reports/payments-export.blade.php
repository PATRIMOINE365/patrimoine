<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>{{ __('ui.reports.payments_report') }}</title>

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

        .summary {
            margin-bottom: 14px;
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
        'title' => __('ui.reports.payments_report'),
    ])

    <div class="summary">
        <strong>{{ __('ui.reports.payment_count') }}:</strong>
        {{ $summary['payment_count'] ?? 0 }}

        &nbsp;&nbsp;

        <strong>{{ __('ui.reports.total_received') }}:</strong>
        {{ $formatter->money($summary['total_received'] ?? 0) }}

        @if (! empty($filters['from']) || ! empty($filters['to']))
            <br>

            <strong>{{ __('ui.reports.period') }}:</strong>

            {{ ! empty($filters['from']) ? $formatter->date($filters['from']) : '—' }}

            —

            {{ ! empty($filters['to']) ? $formatter->date($filters['to']) : '—' }}
        @endif
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
                        <td class="{{ $column === 'amount' ? 'amount' : '' }}">
                            {{ $row[$column] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">
                        {{ __('ui.reports.no_payments_found') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.footer')
</body>
</html>
