@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

@section('title', __('emails.reminder.title'))

@section('preheader')
    {{ __('emails.common.balance_due') }}: {{ $formatter->money($invoice->outstandingAmount()) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.reminder.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $invoice->lease->tenant->name
                ?? $invoice->lease->tenant->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.reminder.intro_before_number') }}
        <strong>{{ $invoice->invoice_number }}</strong>
        {{ __('emails.reminder.intro_for') }}
        <strong>
            {{ $invoice->lease->unit->building->name }}
            /
            {{ $invoice->lease->unit->name }}
        </strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px 0; background-color:#f6faf8; border:1px solid #dbe7e0; border-radius:10px;">
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.common.balance_due') }}
            </td>
            <td align="right" style="padding:12px 18px; font-weight:700; color:#123527; border-bottom:1px solid #e3ede7;">
                {{ $formatter->money($invoice->outstandingAmount()) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 18px; font-size:13px; color:#51615a;">
                {{ __('emails.common.due_date') }}
            </td>
            <td align="right" style="padding:12px 18px; color:#1d2a24;">
                {{ $formatter->date($invoice->due_date) }}
            </td>
        </tr>
    </table>

    @if($invoice->due_date->isPast())
        <p style="margin:0 0 14px 0;">
            {{ __('emails.reminder.overdue') }}
        </p>
    @else
        <p style="margin:0 0 14px 0;">
            {{ __('emails.reminder.pay_by_due_date') }}
        </p>
    @endif

    <p style="margin:0 0 22px 0; color:#51615a; font-size:13px;">
        {{ __('emails.reminder.invoice_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
