<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    <title>
        {{ __('ui.leases.financial_history') }}
    </title>

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

        .context {
            margin-bottom: 14px;
            line-height: 1.5;
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

        .generated {
            margin-top: 12px;
            font-size: 8px;
            color: #66736F;
        }
    </style>
</head>

<body>
    <h1>
        {{ __('ui.leases.financial_history') }}
    </h1>

    <div class="context">
        <strong>{{ __('ui.leases.financial_history_export_tenant') }}:</strong>
        {{ $leaseContext['tenant'] ?? '—' }}

        &nbsp;&nbsp;

        <strong>{{ __('ui.leases.financial_history_export_building') }}:</strong>
        {{ $leaseContext['building'] ?? '—' }}

        &nbsp;&nbsp;

        <strong>{{ __('ui.leases.financial_history_export_unit') }}:</strong>
        {{ $leaseContext['unit'] ?? '—' }}
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
                        <td>{{ $row[$column] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">
                        {{ __('ui.leases.financial_history_empty') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="generated">
        {{ __('ui.leases.financial_history_export_generated') }}
        {{ $generatedAt->format('d-m-Y H:i:s') }}
    </div>
</body>
</html>
