@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

{{--
    V1.0.50: an expense invoice is not a rent invoice, and the mail used
    to call every invoice a rent invoice. The intro is chosen by type,
    falling back to the rent wording for any type without one of its own.
--}}
@php($introKey = 'emails.invoice.intro_before_number_'.$invoice->type)
@php($intro = \Illuminate\Support\Facades\Lang::has($introKey) ? __($introKey) : __('emails.invoice.intro_before_number'))

@section('title', __('emails.invoice.title', ['number' => $invoice->invoice_number]))

@section('preheader')
    {{ $intro }} {{ $invoice->invoice_number }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.invoice.title', ['number' => $invoice->invoice_number]) }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $invoice->lease->tenant->name
                ?? $invoice->lease->tenant->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ $intro }}
        <strong>{{ $invoice->invoice_number }}</strong>
        {{ __('emails.invoice.intro_for') }}
        <strong>
            {{ $invoice->lease->unit->building->name }}
            /
            {{ $invoice->lease->unit->name }}
        </strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px 0; background-color:#FBFCFC; border:1px solid #DDE6E2; border-radius:10px;">
        <tr>
            <td style="padding:12px 18px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.invoice.invoice_amount') }}
            </td>
            <td align="right" style="padding:12px 18px; font-weight:700; color:#123D35; border-bottom:1px solid #DDE6E2;">
                {{ $formatter->money($invoice->total_amount) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.invoice.amount_paid') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $formatter->money($invoice->paidAmount()) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.common.balance_due') }}
            </td>
            <td align="right" style="padding:12px 18px; font-weight:700; color:#123D35; border-bottom:1px solid #DDE6E2;">
                {{ $formatter->money($invoice->outstandingAmount()) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:14px; line-height:20px; color:#4E5B56;">
                {{ __('emails.common.due_date') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#17201E;">
                {{ $formatter->date($invoice->due_date) }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 22px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.invoice.pdf_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
