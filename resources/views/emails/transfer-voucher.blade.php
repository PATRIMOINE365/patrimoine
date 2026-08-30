@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

@section('title', __('emails.transfer_voucher.title'))

@section('preheader')
    {{ __('emails.transfer_voucher.amount_moved') }}: {{ $formatter->money($debitTransaction->amount) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.transfer_voucher.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $debitTransaction->account->lease->tenant->name
                ?? $debitTransaction->account->lease->tenant->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.transfer_voucher.intro') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:0 0 22px 0;">
                <div style="display:inline-block; background-color:#F2F6F4; border:1px solid #C4CFCA; border-radius:10px; padding:18px 40px;">
                    <div style="font-size:12px; line-height:18px; text-transform:uppercase; letter-spacing:1px; color:#4E5B56;">
                        {{ __('emails.transfer_voucher.amount_moved') }}
                    </div>
                    <div style="margin-top:6px; font-size:30px; line-height:38px; font-weight:700; color:#123D35;">
                        {{ $formatter->money($debitTransaction->amount) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.transfer_voucher.voucher') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $debitTransaction->reference }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.transfer_voucher.transfer_date') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $formatter->date($debitTransaction->transaction_date) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.transfer_voucher.from_fund') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ __(
                    'emails.transfer_voucher.fund_'
                    . $debitTransaction->account->type
                ) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; {{ trim((string) $debitTransaction->notes) !== '' ? 'border-bottom:1px solid #DDE6E2;' : '' }}">
                {{ __('emails.transfer_voucher.to_fund') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; {{ trim((string) $debitTransaction->notes) !== '' ? 'border-bottom:1px solid #DDE6E2;' : '' }}">
                {{ __(
                    'emails.transfer_voucher.fund_'
                    . $creditTransaction->account->type
                ) }}
            </td>
        </tr>
        @if (trim((string) $debitTransaction->notes) !== '')
            <tr>
                <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56;">
                    {{ __('emails.transfer_voucher.reason') }}
                </td>
                <td align="right" style="padding:10px 4px; color:#17201E;">
                    {{ $debitTransaction->notes }}
                </td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 22px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.transfer_voucher.pdf_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
