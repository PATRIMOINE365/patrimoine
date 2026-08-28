<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_chart_of_accounts_can_be_installed(): void
    {
        $chart = app(SystemChartOfAccounts::class);

        $accounts = $chart->install();

        $this->assertCount(19, $accounts);
        $this->assertDatabaseCount('accounting_accounts', 19);

        $this->assertDatabaseHas('accounting_accounts', [
            'code' => SystemChartOfAccounts::CASH,
            'name' => 'Cash',
            'type' => AccountingAccount::TYPE_ASSET,
            'normal_balance' => AccountingAccount::NORMAL_DEBIT,
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('accounting_accounts', [
            'code' => SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            'type' => AccountingAccount::TYPE_LIABILITY,
            'normal_balance' => AccountingAccount::NORMAL_CREDIT,
        ]);
    }

    public function test_system_chart_installation_is_idempotent(): void
    {
        $chart = app(SystemChartOfAccounts::class);

        $chart->install();
        $chart->install();

        $this->assertDatabaseCount('accounting_accounts', 19);
    }

    public function test_every_system_account_has_a_valid_type_and_normal_balance(): void
    {
        app(SystemChartOfAccounts::class)->install();

        $validTypes = [
            AccountingAccount::TYPE_ASSET,
            AccountingAccount::TYPE_LIABILITY,
            AccountingAccount::TYPE_EQUITY,
            AccountingAccount::TYPE_INCOME,
            AccountingAccount::TYPE_EXPENSE,
            AccountingAccount::TYPE_CLEARING,
        ];

        $accounts = AccountingAccount::query()->get();

        $this->assertNotEmpty($accounts);

        foreach ($accounts as $account) {
            $this->assertContains($account->type, $validTypes);
            $this->assertContains(
                $account->normal_balance,
                [
                    AccountingAccount::NORMAL_DEBIT,
                    AccountingAccount::NORMAL_CREDIT,
                ]
            );
        }
    }

    public function test_system_account_can_be_resolved_by_permanent_code(): void
    {
        $chart = app(SystemChartOfAccounts::class);

        $chart->install();

        $account = $chart->account(SystemChartOfAccounts::SECURITY_DEPOSIT_HELD);

        $this->assertSame(
            SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,
            $account->code
        );

        $this->assertSame(
            AccountingAccount::TYPE_LIABILITY,
            $account->type
        );
    }
}
