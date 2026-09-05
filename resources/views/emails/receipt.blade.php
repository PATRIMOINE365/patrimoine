@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

@section('title', __('emails.receipt.title'))

@section('preheader')
    {{ __('emails.receipt.amount_received') }}: {{ $formatter->money($payment->amount) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.receipt.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $payment->lease->tenant->name
                ?? $payment->lease->tenant->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.receipt.confirm_before_property') }}
        <strong>
            {{ $payment->lease->unit->building->name }}
            /
            {{ $payment->lease->unit->name }}
        </strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:0 0 22px 0;">
                <div style="display:inline-block; background-color:#F2F6F4; border:1px solid #C4CFCA; border-radius:10px; padding:18px 40px;">
                    <div style="font-size:12px; line-height:18px; text-transform:uppercase; letter-spacing:1px; color:#4E5B56;">
                        {{ __('emails.receipt.amount_received') }}
                    </div>
                    <div style="margin-top:6px; font-size:30px; line-height:38px; font-weight:700; color:#123D35;">
                        {{ $formatter->money($payment->amount) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.receipt.receipt') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $payment->receiptNumber() }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.receipt.payment_date') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $formatter->date($payment->payment_date) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; {{ $payment->reference ? 'border-bottom:1px solid #DDE6E2;' : '' }}">
                {{ __('emails.receipt.payment_method') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; {{ $payment->reference ? 'border-bottom:1px solid #DDE6E2;' : '' }}">
                {{ __('emails.payment_methods.'.$payment->payment_method) }}
            </td>
        </tr>
        @if($payment->reference)
            <tr>
                <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56;">
                    {{ __('emails.receipt.reference') }}
                </td>
                <td align="right" style="padding:10px 4px; color:#17201E;">
                    {{ $payment->reference }}
                </td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 22px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.receipt.pdf_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
