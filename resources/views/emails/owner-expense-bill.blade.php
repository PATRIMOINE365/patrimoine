<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('emails.owner_expense_bill.title') }}
    </title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#f5f5f5;
    font-family:Arial, Helvetica, sans-serif;
    color:#222222;
">
    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="background:#f5f5f5;padding:30px 0;"
    >
        <tr>
            <td align="center">

                <table
                    width="620"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="
                        background:#ffffff;
                        padding:32px;
                        border-collapse:collapse;
                    "
                >
                    <tr>
                        <td>
                            <div style="
                                font-size:26px;
                                font-weight:bold;
                            ">
                                {{ $managingOrganisation?->legal_name
    ?? $managingOrganisation?->name
    ?? 'Patrimoine' }}
                            </div>

                            <div style="
                                color:#666666;
                                font-size:13px;
                                margin-bottom:28px;
                            ">
                                {{ __('emails.common.property_management') }}
                            </div>

                            <p>
                                {{ __('emails.common.dear') }}
                                <strong>
                                    {{ $bill->ownerAccount->party->name
                                        ?? $bill->ownerAccount->party->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                {{ __('emails.owner_expense_bill.intro') }}
                            </p>

                            <div style="
                                text-align:center;
                                margin:28px 0;
                                padding:24px;
                                background:#f7f7f7;
                            ">
                                <div style="
                                    color:#666666;
                                    font-size:13px;
                                ">
                                    {{ __('emails.owner_expense_bill.total_billed') }}
                                </div>

                                <div style="
                                    font-size:28px;
                                    font-weight:bold;
                                    margin-top:8px;
                                ">
                                    {{ $formatter->money($bill->total_amount) }}
                                </div>
                            </div>

                            <table
                                width="100%"
                                cellpadding="6"
                                cellspacing="0"
                                border="0"
                            >
                                <tr>
                                    <td>
                                        {{ __('emails.owner_expense_bill.bill') }}
                                    </td>

                                    <td align="right">
                                        {{ $bill->bill_number }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.owner_expense_bill.bill_date') }}
                                    </td>

                                    <td align="right">
                                        {{ $formatter->date($bill->bill_date) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.owner_expense_bill.line_count') }}
                                    </td>

                                    <td align="right">
                                        {{ $bill->expenses->count() }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin-top:24px;">
                                {{ __('emails.owner_expense_bill.pdf_attached') }}
                            </p>

                            <p style="margin-top:28px;">
                                {{ __('emails.common.regards') }},<br>

<strong>
    {{ $managingOrganisation?->legal_name
        ?? $managingOrganisation?->name
        ?? 'Patrimoine' }}
</strong>

@if($managingOrganisation)
    @if($managingOrganisation->phone)
        <br>
        {{ $managingOrganisation->phone }}
    @endif

    @if($managingOrganisation->email)
        <br>
        {{ $managingOrganisation->email }}
    @endif
@endif
                            </p>

                            <div style="
                                margin-top:32px;
                                padding-top:16px;
                                border-top:1px solid #dddddd;
                                color:#888888;
                                font-size:11px;
                            ">
                                {{ __('emails.common.generated_by') }}
                            </div>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
