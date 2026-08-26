@extends('emails.layouts.base')

@php
    $increment = $rentIncrement;
    $lease = $increment->lease;
    $tenant = $lease->tenant;
    $unit = $lease->unit;
    $building = $unit?->building;

    $organisationName =
        $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? null;

    $tenantName =
        $tenant?->name
        ?? $tenant?->legal_name
        ?? __('emails.common.tenant');

    $propertyName =
        collect([
            $building?->name,
            $unit?->name,
        ])
            ->filter()
            ->implode(' — ');
@endphp

@section('title', __('emails.rent_increment.title'))

@section('preheader')
    {{ __('emails.rent_increment.new_rent') }}: {{ $formatter->money($increment->new_rent_amount) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.rent_increment.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }} <strong>{{ $tenantName }}</strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.rent_increment.intro_before_property') }}
        @if($propertyName !== '')
            {{ __('emails.rent_increment.at') }}
            <strong>{{ $propertyName }}</strong>
        @endif
        {{ __('emails.rent_increment.intro_before_date') }}
        <strong>{{ $formatter->date($increment->effective_date) }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px 0; background-color:#f6faf8; border:1px solid #dbe7e0; border-radius:10px;">
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.rent_increment.current_rent') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $formatter->money($increment->old_rent_amount) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.rent_increment.increment') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                @if($increment->increment_type === 'percentage')
                    {{ number_format((float) $increment->increment_value, 2) }}%
                @else
                    {{ $formatter->money($increment->increment_value) }}
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.rent_increment.new_rent') }}
            </td>
            <td align="right" style="padding:12px 18px; font-weight:700; color:#123527; border-bottom:1px solid #e3ede7;">
                {{ $formatter->money($increment->new_rent_amount) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a;">
                {{ __('emails.rent_increment.effective_date') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#1d2a24;">
                {{ $formatter->date($increment->effective_date) }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.rent_increment.unchanged_until') }}
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.rent_increment.contact_before') }}
        {{ $organisationName ?? config('legal.product.name') }}
        {{ __('emails.rent_increment.contact_after') }}
    </p>

    @include('emails.partials.regards')
@endsection
