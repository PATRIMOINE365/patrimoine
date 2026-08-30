<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">

    @include('documents.partials.fonts')

    <style>
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #17201E;
            line-height: 1.55;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 24px;
        }

        .section {
            margin-bottom: 18px;
        }

        .label {
            font-weight: bold;
        }

        .notice {
            margin-top: 26px;
            padding: 16px;
            border: 1px solid #DDE6E2;
        }

        .footer {
            margin-top: 40px;
            font-size: 10px;
            color: #66736F;
            text-align: center;
        }
    </style>
</head>

<body>

<h1>{{ __('documents.termination_notice.title') }}</h1>

<div class="section">
    <div>
        <span class="label">{{ __('documents.termination_notice.tenant') }}:</span>
        {{ $lease->tenant->name }}
    </div>

    <div>
        <span class="label">{{ __('documents.termination_notice.building') }}:</span>
        {{ $lease->unit->building->name }}
    </div>

    <div>
        <span class="label">{{ __('documents.termination_notice.unit') }}:</span>
        {{ $lease->unit->name }}
    </div>

    <div>
        <span class="label">{{ __('documents.termination_notice.lease') }}:</span>
        #{{ $lease->id }}
    </div>
</div>

<div class="notice">
    <p>
        {{ __('documents.termination_notice.body', [
            'notice_date' => $lease->termination_notice_date->format('d-m-Y'),
            'termination_date' => $lease->termination_date->format('d-m-Y'),
        ]) }}
    </p>
</div>

<div class="section" style="margin-top: 28px;">
    <div>
        <span class="label">{{ __('documents.termination_notice.notice_date') }}:</span>
        {{ $lease->termination_notice_date->format('d-m-Y') }}
    </div>

    <div>
        <span class="label">{{ __('documents.termination_notice.termination_date') }}:</span>
        {{ $lease->termination_date->format('d-m-Y') }}
    </div>
</div>

<div class="footer">
    {{ __('documents.termination_notice.footer') }}
</div>

</body>
</html>
