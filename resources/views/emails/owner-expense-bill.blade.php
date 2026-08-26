@extends('emails.layouts.base')

@php($organisationName = $managingOrganisation?->legal_name ?? $managingOrganisation?->name ?? null)

@section('title', __('emails.owner_expense_bill.title'))

@section('preheader')
    {{ __('emails.owner_expense_bill.total_billed') }}: {{ $formatter->money($bill->total_amount) }}
@endsection

@section('content')
    <h1 style="margin:0 0 18px 0; font-size:21px; line-height:30px; color:#123527; font-weight:600;">
        {{ __('emails.owner_expense_bill.title') }}
    </h1>

    <p style="margin:0 0 14px 0;">
        {{ __('emails.common.dear') }}
        <strong>
            {{ $bill->ownerAccount->party->name
                ?? $bill->ownerAccount->party->legal_name }}
        </strong>,
    </p>

    <p style="margin:0 0 22px 0;">
        {{ __('emails.owner_expense_bill.intro') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:0 0 22px 0;">
                <div style="display:inline-block; background-color:#f0f5f2; border:1px solid #d4e2da; border-radius:10px; padding:18px 40px;">
                    <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#51615a;">
                        {{ __('emails.owner_expense_bill.total_billed') }}
                    </div>
                    <div style="margin-top:6px; font-size:28px; font-weight:700; color:#123527;">
                        {{ $formatter->money($bill->total_amount) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.owner_expense_bill.bill') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $bill->bill_number }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a; border-bottom:1px solid #e3ede7;">
                {{ __('emails.owner_expense_bill.bill_date') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24; border-bottom:1px solid #e3ede7;">
                {{ $formatter->date($bill->bill_date) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 4px; font-size:13px; color:#51615a;">
                {{ __('emails.owner_expense_bill.line_count') }}
            </td>
            <td align="right" style="padding:10px 4px; color:#1d2a24;">
                {{ $bill->expenses->count() }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 22px 0; color:#51615a; font-size:13px;">
        {{ __('emails.owner_expense_bill.pdf_attached') }}
    </p>

    @include('emails.partials.regards')
@endsection
