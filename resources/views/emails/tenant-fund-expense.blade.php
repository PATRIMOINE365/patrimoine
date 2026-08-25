<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('emails.tenant_fund_expense.title') }}
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
                                    {{ $transaction->account->lease->tenant->name
                                        ?? $transaction->account->lease->tenant->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                {{ __('emails.tenant_fund_expense.intro') }}
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
                                    {{ __('emails.tenant_fund_expense.amount_moved') }}
                                </div>

                                <div style="
                                    font-size:28px;
                                    font-weight:bold;
                                    margin-top:8px;
                                ">
                                    {{ $formatter->money($totalAmount) }}
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
                                        {{ __('emails.tenant_fund_expense.voucher') }}
                                    </td>

                                    <td align="right">
                                        {{ $transaction->reference }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.tenant_fund_expense.transfer_date') }}
                                    </td>

                                    <td align="right">
                                        {{ $formatter->date($transaction->transaction_date) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.tenant_fund_expense.source_fund') }}
                                    </td>

                                    <td align="right">
                                        {{ __(
                                            'emails.tenant_fund_expense.fund_'
                                            . $transaction->account->type
                                        ) }}
                                    </td>
                                </tr>

                            </table>

                            <p style="margin-top:24px;">
                                {{ __('emails.tenant_fund_expense.pdf_attached') }}
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
