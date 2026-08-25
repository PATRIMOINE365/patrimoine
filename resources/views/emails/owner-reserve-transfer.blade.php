<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('emails.owner_reserve_transfer.title') }}
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
                                    {{ $transaction->ownerAccount->party->name
                                        ?? $transaction->ownerAccount->party->legal_name }}
                                </strong>,
                            </p>

                            <p>
                                {{ __('emails.owner_reserve_transfer.intro') }}
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
                                    {{ __('emails.owner_reserve_transfer.amount_moved') }}
                                </div>

                                <div style="
                                    font-size:28px;
                                    font-weight:bold;
                                    margin-top:8px;
                                ">
                                    {{ $formatter->money($transaction->amount) }}
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
                                        {{ __('emails.owner_reserve_transfer.voucher') }}
                                    </td>

                                    <td align="right">
                                        {{ $transaction->reference }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.owner_reserve_transfer.transfer_date') }}
                                    </td>

                                    <td align="right">
                                        {{ $formatter->date($transaction->transaction_date) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.owner_reserve_transfer.from_account') }}
                                    </td>

                                    <td align="right">
                                        {{ $transaction->direction === 'credit'
                                            ? __('emails.owner_reserve_transfer.payout_account')
                                            : __('emails.owner_reserve_transfer.deposit_account') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        {{ __('emails.owner_reserve_transfer.to_account') }}
                                    </td>

                                    <td align="right">
                                        {{ $transaction->direction === 'credit'
                                            ? __('emails.owner_reserve_transfer.deposit_account')
                                            : __('emails.owner_reserve_transfer.payout_account') }}
                                    </td>
                                </tr>

                                @if (trim((string) $transaction->notes) !== '')
                                    <tr>
                                        <td>
                                            {{ __('emails.owner_reserve_transfer.reason') }}
                                        </td>

                                        <td align="right">
                                            {{ $transaction->notes }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin-top:24px;">
                                {{ __('emails.owner_reserve_transfer.pdf_attached') }}
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
