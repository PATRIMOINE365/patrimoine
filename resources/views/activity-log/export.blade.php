<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('activity_log.export_title') }}
    </title>

    <style>
        @page {
            margin: 18px 20px;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #17201E;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 14px;
            color: #66736F;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #DDE6E2;
            padding: 4px;
            vertical-align: top;
            text-align: left;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        th {
            background: #F7F5EF;
            font-weight: bold;
        }

        .structured {
            white-space: pre-wrap;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 6.5px;
        }

        .empty {
            padding: 20px 8px;
            text-align: center;
            color: #66736F;
        }

        .footer {
            margin-top: 10px;
            color: #66736F;
            font-size: 7px;
        }
    </style>
</head>

<body>
    <h1>
        {{ __('activity_log.export_title') }}
    </h1>

    <div class="meta">
        {{ __('activity_log.generated_at') }}:
        {{ $generatedAt }}
    </div>

    @if ($rows === [])
        <div class="empty">
            {{ __('activity_log.no_export_records') }}
        </div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($columns as $label)
                        <th>
                            {{ $label }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach (array_keys($columns) as $column)
                            <td
                                @if (
                                    in_array(
                                        $column,
                                        [
                                            'before_values',
                                            'after_values',
                                            'snapshot',
                                            'metadata',
                                        ],
                                        true
                                    )
                                )
                                    class="structured"
                                @endif
                            >
                                {{ $row[$column] ?? '' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ __('activity_log.export_read_only_notice') }}
    </div>
</body>
</html>
