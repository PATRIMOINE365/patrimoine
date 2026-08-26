<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }}</title>

    <style>
        @page {
            margin: 36px 42px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222222;
            line-height: 1.4;
        }

        .section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cccccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f1f1;
            text-align: left;
            font-weight: bold;
            padding: 6px;
            border: 1px solid #dddddd;
        }

        td {
            padding: 6px;
            vertical-align: top;
            border: 1px solid #dddddd;
        }

        .label {
            width: 38%;
            font-weight: bold;
            background: #fafafa;
        }

        .empty {
            color: #888888;
            font-style: italic;
            padding: 8px 0;
        }
    </style>
</head>

<body>
    @include('reports.partials.letterhead')

    @foreach($sections as $section)
        <div class="section">
            <div class="section-title">
                {{ $section['title'] }}
            </div>

            @if($section['type'] === 'pairs')
                @if(count($section['rows']) === 0)
                    <div class="empty">
                        {{ __('reports.no_information_available') }}
                    </div>
                @else
                    <table>
                        @foreach($section['rows'] as $row)
                            <tr>
                                <td class="label">
                                    {{ $row['label'] }}
                                </td>

                                <td>
                                    {{ $row['value'] }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            @endif

            @if($section['type'] === 'table')
                @if(count($section['rows']) === 0)
                    <div class="empty">
                        {{ __('reports.no_records_for_section') }}
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                @foreach($section['headers'] as $header)
                                    <th>
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($section['rows'] as $row)
                                <tr>
                                    @foreach($row as $value)
                                        <td>
                                            {{ $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </div>
    @endforeach

    @include('reports.partials.footer')
</body>
</html>
