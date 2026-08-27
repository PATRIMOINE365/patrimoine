<?php

namespace App\Services\Accounting;

use InvalidArgumentException;

/**
 * Central accounting mapping for V1.0.5.
 *
 * Fixed events expose both debit and credit accounts directly.
 *
 * Contextual events deliberately expose only the fixed side of the posting.
 * The operational service must supply the variable account based on:
 *
 * - payment method;
 * - selected Tenant fund account;
 * - selected Owner/Tenant balance account;
 * - transaction direction.
 *
 * This prevents permanent accounting semantics from being incorrectly
 * hard-coded to Bank, Consumable Advance, or Owner Funds Payable.
 */
class AccountingEventMap
{
    public const EVENT_RENT_INVOICE =
        'rent_invoice';

    public const EVENT_SECURITY_DEPOSIT_DEBT_INVOICE =
        'security_deposit_debt_invoice';

    public const EVENT_RENT_RECEIPT =
        'rent_receipt';

    public const EVENT_RENT_RESERVE_FUNDING =
        'rent_reserve_funding';

    public const EVENT_ADVANCE_FUNDING =
        'advance_funding';

    public const EVENT_SECURITY_DEPOSIT_FUNDING =
        'security_deposit_funding';

    public const EVENT_RENT_RESERVE_CONSUMPTION =
        'rent_reserve_consumption';

    public const EVENT_ADVANCE_CONSUMPTION =
        'advance_consumption';

    public const EVENT_SECURITY_DEPOSIT_APPLIED =
        'security_deposit_applied';

    public const EVENT_SECURITY_DEPOSIT_REFUND =
        'security_deposit_refund';

    public const EVENT_TENANT_WITHDRAWAL =
        'tenant_withdrawal';

    public const EVENT_TENANT_FUND_EXPENSE =
        'tenant_fund_expense';

    public const EVENT_TENANT_EXPENSE_SETTLEMENT =
        'tenant_expense_settlement';

    public const EVENT_OWNER_DEPOSIT =
        'owner_deposit';

    public const EVENT_OWNER_PAYOUT =
        'owner_payout';

    public const EVENT_OWNER_EXPENSE =
        'owner_expense';

    public const EVENT_OWNER_RENT_ENTITLEMENT =
        'owner_rent_entitlement';

    public const EVENT_MANAGEMENT_FEE =
        'management_fee';

    public const EVENT_AGENT_COMMISSION =
        'agent_commission';

    public const EVENT_ADJUSTMENT =
        'adjustment';

    public const EVENT_TENANT_FUND_TRANSFER =
        'tenant_fund_transfer';

    /**
     * Events whose complete double-entry mapping is always fixed.
     *
     * @return array<string, array<string, string>>
     */
    public function fixedDefinitions(): array
    {
        return [
            self::EVENT_RENT_INVOICE => [
                'debit' =>
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                'credit' =>
                    SystemChartOfAccounts::RENT_BILLING_CLEARING,
            ],

            self::EVENT_SECURITY_DEPOSIT_DEBT_INVOICE => [
                'debit' =>
                    SystemChartOfAccounts::SECURITY_DEPOSIT_DEBT_RECEIVABLE,

                'credit' =>
                    SystemChartOfAccounts::SECURITY_DEPOSIT_RECOVERY,
            ],

            self::EVENT_RENT_RESERVE_CONSUMPTION => [
                'debit' =>
                    SystemChartOfAccounts::RENT_RESERVE_HELD,

                'credit' =>
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
            ],

            self::EVENT_ADVANCE_CONSUMPTION => [
                'debit' =>
                    SystemChartOfAccounts::CONSUMABLE_ADVANCE_HELD,

                'credit' =>
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
            ],

            /*
             * Security Deposit retained against final deductions.
             *
             * Patrimoine creates a Security Deposit debt Invoice only for
             * deductions exceeding the amount actually held. The portion
             * covered by the held deposit therefore becomes recovery
             * directly and does not create or settle a receivable.
             */
            self::EVENT_SECURITY_DEPOSIT_APPLIED => [
                'debit' =>
                    SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,

                'credit' =>
                    SystemChartOfAccounts::SECURITY_DEPOSIT_RECOVERY,
            ],

            self::EVENT_OWNER_EXPENSE => [
                'debit' =>
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                'credit' =>
                    SystemChartOfAccounts::PROPERTY_EXPENSE_CLEARING,
            ],

            self::EVENT_OWNER_RENT_ENTITLEMENT => [
                'debit' =>
                    SystemChartOfAccounts::RENT_BILLING_CLEARING,

                'credit' =>
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            ],

            self::EVENT_MANAGEMENT_FEE => [
                'debit' =>
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                'credit' =>
                    SystemChartOfAccounts::MANAGEMENT_FEE_INCOME,
            ],

            /*
             * Agent commission is contractually charged to the Owners by the
             * existing operational Owner ledger.
             *
             * Therefore recognition reduces Owner Funds Payable and creates
             * the liability owed to the Agent.
             *
             * Payment of that liability, if/when introduced as a separate
             * operational workflow, will be a distinct Journal event.
             */
            self::EVENT_AGENT_COMMISSION => [
                'debit' =>
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                'credit' =>
                    SystemChartOfAccounts::AGENT_COMMISSION_PAYABLE,
            ],
        ];
    }

    /**
     * Events where one side depends on runtime business context.
     *
     * @return array<string, array<string, string>>
     */
    public function contextualDefinitions(): array
    {
        return [
            self::EVENT_RENT_RECEIPT => [
                'variable' =>
                    'payment_asset',

                'credit' =>
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
            ],

            self::EVENT_RENT_RESERVE_FUNDING => [
                'variable' =>
                    'payment_asset',

                'credit' =>
                    SystemChartOfAccounts::RENT_RESERVE_HELD,
            ],

            self::EVENT_ADVANCE_FUNDING => [
                'variable' =>
                    'payment_asset',

                'credit' =>
                    SystemChartOfAccounts::CONSUMABLE_ADVANCE_HELD,
            ],

            self::EVENT_SECURITY_DEPOSIT_FUNDING => [
                'variable' =>
                    'payment_asset',

                'credit' =>
                    SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,
            ],

            self::EVENT_SECURITY_DEPOSIT_REFUND => [
                'debit' =>
                    SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,

                'variable' =>
                    'payment_asset',
            ],

            self::EVENT_TENANT_WITHDRAWAL => [
                'variable' =>
                    'tenant_fund_liability',

                'counter_variable' =>
                    'payment_asset',
            ],

            /*
             * V1.0.8: a lease-specific expense settled from tenant-held
             * funds. Economically identical to a Withdrawal: held funds
             * leave the liability through the payment asset.
             */
            self::EVENT_TENANT_FUND_EXPENSE => [
                'variable' =>
                    'tenant_fund_liability',

                'counter_variable' =>
                    'payment_asset',
            ],

            /*
             * V1.0.8: an expense Invoice settled from tenant-held funds.
             *
             * No cash moves at settlement time — the held liability
             * absorbs an expense already recognized in the clearing
             * account, mirroring the owner-expense treatment. The debit
             * side varies with the fund account type.
             */
            self::EVENT_TENANT_EXPENSE_SETTLEMENT => [
                'variable' =>
                    'tenant_fund_liability',

                'credit' =>
                    SystemChartOfAccounts::PROPERTY_EXPENSE_CLEARING,
            ],

            self::EVENT_OWNER_DEPOSIT => [
                'variable' =>
                    'payment_asset',

                'credit' =>
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            ],

            self::EVENT_OWNER_PAYOUT => [
                'debit' =>
                    SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                'variable' =>
                    'payment_asset',
            ],

            self::EVENT_ADJUSTMENT => [
                'variable' =>
                    'balance_account',

                'counter' =>
                    SystemChartOfAccounts::ADJUSTMENT_CLEARING,
            ],
        ];
    }

    /**
     * Resolve one fixed event.
     *
     * @return array<string, string>
     */
    public function fixed(string $event): array
    {
        $definitions = $this->fixedDefinitions();

        if (! array_key_exists($event, $definitions)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown fixed accounting event: %s',
                    $event
                )
            );
        }

        return $definitions[$event];
    }

    /**
     * Resolve one contextual event definition.
     *
     * @return array<string, string>
     */
    public function contextual(string $event): array
    {
        $definitions = $this->contextualDefinitions();

        if (! array_key_exists($event, $definitions)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown contextual accounting event: %s',
                    $event
                )
            );
        }

        return $definitions[$event];
    }

    /**
     * Resolve payment-method asset account.
     */
    public function paymentAsset(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash' =>
                SystemChartOfAccounts::CASH,

            /*
             * Patrimoine's operational Payment domain uses
             * `bank_transfer`.
             *
             * `bank` remains accepted as a compatibility alias for the
             * accounting foundation introduced before runtime integration.
             */
            'bank_transfer',
            'bank' =>
                SystemChartOfAccounts::BANK,

            /*
             * Patrimoine's operational Payment domain uses `momo`.
             *
             * `mobile_payment` remains accepted as a compatibility alias
             * because the accounting vocabulary intentionally uses the
             * generic Mobile Payment label.
             */
            'momo',
            'mobile_payment' =>
                SystemChartOfAccounts::MOBILE_PAYMENT_CLEARING,

            /*
             * A cheque is a bank instrument: it is banked rather than
             * held, so it settles through the same Bank asset account as
             * a transfer. No separate clearing account is introduced,
             * which would otherwise change the chart of accounts and the
             * opening-balance reconciliation.
             */
            'cheque' =>
                SystemChartOfAccounts::BANK,

            default =>
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported payment method for accounting: %s',
                        $paymentMethod
                    )
                ),
        };
    }

    /**
     * Resolve Tenant fund liability by existing operational fund type.
     */
    public function tenantFundLiability(
        string $fundType
    ): string {
        return match ($fundType) {
            'rent_reserve' =>
                SystemChartOfAccounts::RENT_RESERVE_HELD,

            'consumable_advance' =>
                SystemChartOfAccounts::CONSUMABLE_ADVANCE_HELD,

            'security_deposit' =>
                SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,

            default =>
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported Tenant fund type for accounting: %s',
                        $fundType
                    )
                ),
        };
    }
}
