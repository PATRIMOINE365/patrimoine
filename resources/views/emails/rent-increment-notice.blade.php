<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notice of Rent Increment</title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f8fafc;
        font-family:
            Arial,
            Helvetica,
            sans-serif;
        color: #0f172a;
    "
>
    @php
        $increment =
            $rentIncrement;

        $lease =
            $increment->lease;

        $tenant =
            $lease->tenant;

        $unit =
            $lease->unit;

        $building =
            $unit?->building;

        $organisationName =
            $managingOrganisation?->legal_name
            ?? $managingOrganisation?->name
            ?? 'Patrimoine';

        $tenantName =
            $tenant?->name
            ?? $tenant?->legal_name
            ?? 'Tenant';

        $propertyName =
            collect([
                $building?->name,
                $unit?->name,
            ])
                ->filter()
                ->implode(' — ');
    @endphp

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            width: 100%;
            background-color: #f8fafc;
            padding: 32px 16px;
        "
    >
        <tr>
            <td align="center">

                <table
                    role="presentation"
                    width="620"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        width: 100%;
                        max-width: 620px;
                        background-color: #ffffff;
                        border: 1px solid #e2e8f0;
                        border-radius: 12px;
                    "
                >
                    <tr>
                        <td
                            style="
                                padding: 32px;
                            "
                        >
                            <div
                                style="
                                    margin-bottom: 24px;
                                "
                            >
                                <div
                                    style="
                                        font-size: 13px;
                                        font-weight: 600;
                                        color: #475569;
                                        margin-bottom: 6px;
                                    "
                                >
                                    {{ $organisationName }}
                                </div>

                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 24px;
                                        line-height: 1.3;
                                        color: #0f172a;
                                    "
                                >
                                    Notice of Rent Increment
                                </h1>
                            </div>

                            <p
                                style="
                                    margin:
                                        0
                                        0
                                        18px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Dear {{ $tenantName }},
                            </p>

                            <p
                                style="
                                    margin:
                                        0
                                        0
                                        18px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                This is to formally notify you that the
                                monthly rent applicable to your tenancy
                                @if($propertyName !== '')
                                    at
                                    <strong>{{ $propertyName }}</strong>
                                @endif
                                is scheduled to change with effect from
                                <strong>
                                    {{ $formatter->date($increment->effective_date) }}
                                </strong>.
                            </p>

                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                                style="
                                    width: 100%;
                                    margin: 24px 0;
                                    border-collapse: collapse;
                                "
                            >
                                <tr>
                                    <td
                                        style="
                                            width: 50%;
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            background-color: #f8fafc;
                                            font-size: 13px;
                                            color: #64748b;
                                        "
                                    >
                                        Current Monthly Rent
                                    </td>

                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            font-size: 14px;
                                            font-weight: 600;
                                            text-align: right;
                                        "
                                    >
                                        {{ $formatter->money(
                                            $increment->old_rent_amount
                                        ) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            background-color: #f8fafc;
                                            font-size: 13px;
                                            color: #64748b;
                                        "
                                    >
                                        Rent Increment
                                    </td>

                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            font-size: 14px;
                                            font-weight: 600;
                                            text-align: right;
                                        "
                                    >
                                        @if(
                                            $increment->increment_type
                                            === 'percentage'
                                        )
                                            {{ number_format(
                                                (float) $increment->increment_value,
                                                2
                                            ) }}%
                                        @else
                                            {{ $formatter->money(
                                                $increment->increment_value
                                            ) }}
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            background-color: #f8fafc;
                                            font-size: 13px;
                                            color: #64748b;
                                        "
                                    >
                                        New Monthly Rent
                                    </td>

                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            font-size: 15px;
                                            font-weight: 700;
                                            text-align: right;
                                        "
                                    >
                                        {{ $formatter->money(
                                            $increment->new_rent_amount
                                        ) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            background-color: #f8fafc;
                                            font-size: 13px;
                                            color: #64748b;
                                        "
                                    >
                                        Effective Date
                                    </td>

                                    <td
                                        style="
                                            padding: 14px;
                                            border: 1px solid #e2e8f0;
                                            font-size: 14px;
                                            font-weight: 600;
                                            text-align: right;
                                        "
                                    >
                                        {{ $formatter->date($increment->effective_date) }}
                                    </td>
                                </tr>
                            </table>

                            <p
                                style="
                                    margin:
                                        0
                                        0
                                        18px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Your current rent will remain unchanged
                                until the effective date stated above.
                            </p>

                            <p
                                style="
                                    margin:
                                        0
                                        0
                                        18px;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Please contact
                                {{ $organisationName }}
                                if you require any clarification regarding
                                this notice.
                            </p>

                            <p
                                style="
                                    margin:
                                        26px
                                        0
                                        0;
                                    font-size: 15px;
                                    line-height: 1.7;
                                "
                            >
                                Regards,<br>

                                <strong>
                                    {{ $organisationName }}
                                </strong>
                            </p>

                            @if(
                                $managingOrganisation?->phone
                                || $managingOrganisation?->email
                            )
                                <div
                                    style="
                                        margin-top: 28px;
                                        padding-top: 18px;
                                        border-top: 1px solid #e2e8f0;
                                        font-size: 12px;
                                        line-height: 1.6;
                                        color: #64748b;
                                    "
                                >
                                    @if($managingOrganisation?->phone)
                                        {{ $managingOrganisation->phone }}
                                    @endif

                                    @if(
                                        $managingOrganisation?->phone
                                        && $managingOrganisation?->email
                                    )
                                        <br>
                                    @endif

                                    @if($managingOrganisation?->email)
                                        {{ $managingOrganisation->email }}
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
