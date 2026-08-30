<!DOCTYPE html>
{{--
    V1.0.7 Registry backup — PDF rendering.

    This PDF exists for eyeballing a backup, not for restoring one:
    CSV/XLSX are the machine-restorable formats. Headers deliberately use
    the same STABLE untranslated column keys as those files (rather than
    localized labels) so what the Administrator reviews here is exactly
    what the import will read.

    Very large registries are capped by the controller; a "+N more rows"
    line indicates the truncation.
--}}
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        Registry — {{ $entity }}
    </title>

    @include('documents.partials.fonts')

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
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7px;
        }

        .empty {
            padding: 20px 8px;
            text-align: center;
            color: #66736F;
        }

        .more {
            margin-top: 8px;
            font-weight: bold;
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
        Registry — {{ $entity }}
    </h1>

    <div class="meta">
        {{ $generatedAt }}
        —
        {{ $totalRowCount }} {{ $entity }}
    </div>

    @if ($rows === [])
        <div class="empty">
            No records.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td>
                                {{ $row[$column] ?? '' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($totalRowCount > count($rows))
            <div class="more">
                +{{ $totalRowCount - count($rows) }} more rows
            </div>
        @endif
    @endif

    <div class="footer">
        Review copy only — restore uses the CSV/XLSX backup files.
    </div>
</body>
</html>
