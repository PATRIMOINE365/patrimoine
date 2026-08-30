<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    <style>
        @page {
            margin: 18px;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #17201E;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 17px;
        }

        .meta {
            margin-bottom: 12px;
            color: #4E5B56;
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
            overflow-wrap: anywhere;
        }

        th {
            background: #F7F5EF;
            text-align: left;
            font-size: 7px;
        }

        .money {
            text-align: right;
            white-space: nowrap;
        }

        .journal-number {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <h1>{{ $title }}</h1>

    <div class="meta">
        {{ __('financial_journal.generated_at') }}:
        {{ $generatedAt }}

        @if ($filters !== [])
            · {{ __('financial_journal.filters') }}:
            {{
                json_encode(
                    $filters,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $key => $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($columns as $key => $label)
                        <td
                            @class([
                                'money' => in_array(
                                    $key,
                                    ['debit', 'credit'],
                                    true
                                ),
                                'journal-number' =>
                                    $key === 'journal_number',
                            ])
                        >
                            {{ $row[$key] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
