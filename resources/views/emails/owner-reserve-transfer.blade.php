@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

@section('title', __('emails.owner_reserve_transfer.title'))

@section('preheader')
    {{ __('emails.owner_reserve_transfer.amount_moved') }}: {{ $formatter->money($transaction->amount) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:20px; line-height:30px; color:#123D35; font-weight:600;">
        {{ __('emails.owner_reserve_transfer.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $transaction->ownerAccount->party->name
                ?? $transaction->ownerAccount->party->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.owner_reserve_transfer.intro') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:0 0 22px 0;">
                <div style="display:inline-block; background-color:#F2F6F4; border:1px solid #C4CFCA; border-radius:10px; padding:18px 40px;">
                    <div style="font-size:12px; line-height:18px; text-transform:uppercase; letter-spacing:1px; color:#4E5B56;">
                        {{ __('emails.owner_reserve_transfer.amount_moved') }}
                    </div>
                    <div style="margin-top:6px; font-size:30px; line-height:38px; font-weight:700; color:#123D35;">
                        {{ $formatter->money($transaction->amount) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.owner_reserve_transfer.voucher') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $transaction->reference }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.owner_reserve_transfer.transfer_date') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $formatter->date($transaction->transaction_date) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; border-bottom:1px solid #DDE6E2;">
                {{ __('emails.owner_reserve_transfer.from_account') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; border-bottom:1px solid #DDE6E2;">
                {{ $transaction->direction === 'credit'
                    ? __('emails.owner_reserve_transfer.payout_account')
                    : __('emails.owner_reserve_transfer.deposit_account') }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56; {{ trim((string) $transaction->notes) !== '' ? 'border-bottom:1px solid #DDE6E2;' : '' }}">
                {{ __('emails.owner_reserve_transfer.to_account') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#17201E; {{ trim((string) $transaction->notes) !== '' ? 'border-bottom:1px solid #DDE6E2;' : '' }}">
                {{ $transaction->direction === 'credit'
                    ? __('emails.owner_reserve_transfer.deposit_account')
                    : __('emails.owner_reserve_transfer.payout_account') }}
            </td>
        </tr>
        @if (trim((string) $transaction->notes) !== '')
            <tr>
                <td style="padding:10px 4px; font-size:14px; line-height:20px; color:#4E5B56;">
                    {{ __('emails.owner_reserve_transfer.reason') }}
                </td>
                <td align="right" style="padding:10px 4px; color:#17201E;">
                    {{ $transaction->notes }}
                </td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 22px 0; color:#4E5B56; font-size:14px; line-height:20px;">
        {{ __('emails.owner_reserve_transfer.pdf_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
