<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use Illuminate\Support\Collection;

/**
 * Defines and installs Patrimoine's system-managed Chart of Accounts.
 *
 * Account codes are permanent accounting identifiers. Once Journal posting
 * begins, existing codes must never be silently repurposed for a different
 * accounting meaning.
 */
class SystemChartOfAccounts
{
    public const CASH = '1000';

    public const BANK = '1010';

    public const MOBILE_PAYMENT_CLEARING = '1020';

    public const TENANT_RENT_RECEIVABLE = '1100';

    public const SECURITY_DEPOSIT_DEBT_RECEIVABLE = '1110';

    public const RENT_RESERVE_HELD = '2000';

    public const CONSUMABLE_ADVANCE_HELD = '2010';

    public const SECURITY_DEPOSIT_HELD = '2020';

    public const OWNER_FUNDS_PAYABLE = '2100';

    public const AGENT_COMMISSION_PAYABLE = '2110';

    public const VAT_PAYABLE = '2120';

    public const OPENING_BALANCE_CLEARING = '3000';

    public const ADJUSTMENT_CLEARING = '3010';

    public const LEASE_DELETION_CLEARING = '3020';

    public const RENT_BILLING_CLEARING = '4000';

    public const SECURITY_DEPOSIT_RECOVERY = '4010';

    public const MANAGEMENT_FEE_INCOME = '5000';

    public const AGENT_COMMISSION_EXPENSE = '5010';

    public const PROPERTY_EXPENSE_CLEARING = '5020';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            $this->definition(
                self::CASH,
                'Cash',
                AccountingAccount::TYPE_ASSET,
                AccountingAccount::NORMAL_DEBIT,
                'Physical cash held by Patrimoine.'
            ),
            $this->definition(
                self::BANK,
                'Bank',
                AccountingAccount::TYPE_ASSET,
                AccountingAccount::NORMAL_DEBIT,
                'Funds held in bank accounts.'
            ),
            $this->definition(
                self::MOBILE_PAYMENT_CLEARING,
                'Mobile Payment Clearing',
                AccountingAccount::TYPE_ASSET,
                AccountingAccount::NORMAL_DEBIT,
                'Funds received through mobile payment channels.'
            ),
            $this->definition(
                self::TENANT_RENT_RECEIVABLE,
                'Tenant Rent Receivable',
                AccountingAccount::TYPE_ASSET,
                AccountingAccount::NORMAL_DEBIT,
                'Outstanding contractual rent owed by tenants.'
            ),
            $this->definition(
                self::SECURITY_DEPOSIT_DEBT_RECEIVABLE,
                'Security Deposit Debt Receivable',
                AccountingAccount::TYPE_ASSET,
                AccountingAccount::NORMAL_DEBIT,
                'Tenant debt arising from security-deposit close-out.'
            ),
            $this->definition(
                self::RENT_RESERVE_HELD,
                'Rent Reserve Held',
                AccountingAccount::TYPE_LIABILITY,
                AccountingAccount::NORMAL_CREDIT,
                'Rent Reserve money held for tenants.'
            ),
            $this->definition(
                self::CONSUMABLE_ADVANCE_HELD,
                'Consumable Advance Held',
                AccountingAccount::TYPE_LIABILITY,
                AccountingAccount::NORMAL_CREDIT,
                'Consumable rent advance held for tenants.'
            ),
            $this->definition(
                self::SECURITY_DEPOSIT_HELD,
                'Security Deposit Held',
                AccountingAccount::TYPE_LIABILITY,
                AccountingAccount::NORMAL_CREDIT,
                'Refundable security-deposit money held for tenants.'
            ),
            $this->definition(
                self::OWNER_FUNDS_PAYABLE,
                'Owner Funds Payable',
                AccountingAccount::TYPE_LIABILITY,
                AccountingAccount::NORMAL_CREDIT,
                'Net funds held by Patrimoine for property owners.'
            ),
            $this->definition(
                self::AGENT_COMMISSION_PAYABLE,
                'Agent Commission Payable',
                AccountingAccount::TYPE_LIABILITY,
                AccountingAccount::NORMAL_CREDIT,
                'Agent commission amounts payable.'
            ),
            $this->definition(
                self::VAT_PAYABLE,
                'VAT Payable',
                AccountingAccount::TYPE_LIABILITY,
                AccountingAccount::NORMAL_CREDIT,
                'VAT charged on management fees and owed to the tax authority.'
            ),
            $this->definition(
                self::OPENING_BALANCE_CLEARING,
                'Opening Balance Clearing',
                AccountingAccount::TYPE_EQUITY,
                AccountingAccount::NORMAL_CREDIT,
                'Balances the V1.0.5 migration opening accounting position.'
            ),
            $this->definition(
                self::ADJUSTMENT_CLEARING,
                'Adjustment Clearing',
                AccountingAccount::TYPE_CLEARING,
                AccountingAccount::NORMAL_DEBIT,
                'Counter-account for explicit balance corrections.'
            ),
            $this->definition(
                self::LEASE_DELETION_CLEARING,
                'Lease Deletion Clearing',
                AccountingAccount::TYPE_CLEARING,
                AccountingAccount::NORMAL_DEBIT,
                'Internal clearing account available for destructive lease workflows.'
            ),
            $this->definition(
                self::RENT_BILLING_CLEARING,
                'Rent Billing Clearing',
                AccountingAccount::TYPE_CLEARING,
                AccountingAccount::NORMAL_CREDIT,
                'Balances rent receivables without recognizing owner entitlement before collection.'
            ),
            $this->definition(
                self::SECURITY_DEPOSIT_RECOVERY,
                'Security Deposit Recovery',
                AccountingAccount::TYPE_CLEARING,
                AccountingAccount::NORMAL_CREDIT,
                'Counter-account for security-deposit close-out debt.'
            ),
            $this->definition(
                self::MANAGEMENT_FEE_INCOME,
                'Management Fee Income',
                AccountingAccount::TYPE_INCOME,
                AccountingAccount::NORMAL_CREDIT,
                'Management fees earned by the managing organisation.'
            ),
            $this->definition(
                self::AGENT_COMMISSION_EXPENSE,
                'Agent Commission Expense',
                AccountingAccount::TYPE_EXPENSE,
                AccountingAccount::NORMAL_DEBIT,
                'Agent commission expense recognized by Patrimoine.'
            ),
            $this->definition(
                self::PROPERTY_EXPENSE_CLEARING,
                'Property Expense Clearing',
                AccountingAccount::TYPE_CLEARING,
                AccountingAccount::NORMAL_DEBIT,
                'Counter-account for property expenses charged through owner ledgers.'
            ),
        ];
    }

    /**
     * Ensure every required system account exists.
     *
     * Existing rows are updated by permanent account code so installation is
     * idempotent and safe to invoke during deployment/bootstrap operations.
     *
     * @return Collection<int, AccountingAccount>
     */
    public function install(): Collection
    {
        return collect($this->definitions())
            ->map(function (array $definition): AccountingAccount {
                return AccountingAccount::query()->updateOrCreate(
                    ['code' => $definition['code']],
                    $definition
                );
            });
    }

    /**
     * Resolve one required system account by its permanent code.
     */
    public function account(string $code): AccountingAccount
    {
        return AccountingAccount::query()
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(
        string $code,
        string $name,
        string $type,
        string $normalBalance,
        string $description
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normalBalance,
            'is_system' => true,
            'is_active' => true,
            'description' => $description,
        ];
    }
}
