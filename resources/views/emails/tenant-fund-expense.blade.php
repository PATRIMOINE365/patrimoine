@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

@section('title', __('emails.tenant_fund_expense.title'))

@section('preheader')
    {{ __('emails.tenant_fund_expense.amount_moved') }}: {{ $formatter->money($totalAmount) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.tenant_fund_expense.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $transaction->account->lease->tenant->name
                ?? $transaction->account->lease->tenant->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.tenant_fund_expense.intro') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:0 0 22px 0;">
                <div style="display:inline-block; background-color:#f0f5f2; border:1px solid #d4e2da; border-radius:10px; padding:18px 40px;">
                    <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#51615a;">
                        {{ __('emails.tenant_fund_expense.amount_moved') }}
                    </div>
                    <div style="margin-top:6px; font-size:28px; font-weight:700; color:#123527;">
                        {{ $formatter->money($totalAmount) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.tenant_fund_expense.voucher') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $transaction->reference }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.tenant_fund_expense.transfer_date') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $formatter->date($transaction->transaction_date) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a;">
                {{ __('emails.tenant_fund_expense.source_fund') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24;">
                {{ __(
                    'emails.tenant_fund_expense.fund_'
                    . $transaction->account->type
                ) }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 22px 0; color:#51615a; font-size:13px;">
        {{ __('emails.tenant_fund_expense.pdf_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
