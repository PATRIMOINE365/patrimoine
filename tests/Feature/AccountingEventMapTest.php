<?php

namespace Tests\Feature;

use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AccountingEventMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_fixed_event_uses_defined_system_accounts(): void
    {
        $chart = app(SystemChartOfAccounts::class);

        $validCodes = collect(
            $chart->definitions()
        )->pluck('code');

        foreach (
            app(AccountingEventMap::class)->fixedDefinitions()
            as $event => $mapping
        ) {
            $this->assertContains(
                $mapping['debit'],
                $validCodes,
                "Unknown debit account for {$event}"
            );

            $this->assertContains(
                $mapping['credit'],
                $validCodes,
                "Unknown credit account for {$event}"
            );

            $this->assertNotSame(
                $mapping['debit'],
                $mapping['credit']
            );
        }
    }

    public function test_rent_invoice_does_not_create_owner_payable(): void
    {
        $mapping = app(
            AccountingEventMap::class
        )->fixed(
            AccountingEventMap::EVENT_RENT_INVOICE
        );

        $this->assertSame(
            SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
            $mapping['debit']
        );

        $this->assertSame(
            SystemChartOfAccounts::RENT_BILLING_CLEARING,
            $mapping['credit']
        );

        $this->assertNotSame(
            SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            $mapping['credit']
        );
    }

    public function test_collected_rent_entitlement_creates_owner_payable(): void
    {
        $mapping = app(
            AccountingEventMap::class
        )->fixed(
            AccountingEventMap::EVENT_OWNER_RENT_ENTITLEMENT
        );

        $this->assertSame(
            SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            $mapping['credit']
        );
    }

    public function test_payment_assets_are_resolved_by_payment_method(): void
    {
        $map = app(AccountingEventMap::class);

        $this->assertSame(
            SystemChartOfAccounts::CASH,
            $map->paymentAsset('cash')
        );

        $this->assertSame(
            SystemChartOfAccounts::BANK,
            $map->paymentAsset('bank')
        );

        $this->assertSame(
            SystemChartOfAccounts::MOBILE_PAYMENT_CLEARING,
            $map->paymentAsset('mobile_payment')
        );
    }

    public function test_tenant_fund_liabilities_are_contextual(): void
    {
        $map = app(AccountingEventMap::class);

        $this->assertSame(
            SystemChartOfAccounts::RENT_RESERVE_HELD,
            $map->tenantFundLiability('rent_reserve')
        );

        $this->assertSame(
            SystemChartOfAccounts::CONSUMABLE_ADVANCE_HELD,
            $map->tenantFundLiability('consumable_advance')
        );

        $this->assertSame(
            SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,
            $map->tenantFundLiability('security_deposit')
        );
    }

    public function test_tenant_withdrawal_does_not_hard_code_one_fund_account(): void
    {
        $mapping = app(
            AccountingEventMap::class
        )->contextual(
            AccountingEventMap::EVENT_TENANT_WITHDRAWAL
        );

        $this->assertSame(
            'tenant_fund_liability',
            $mapping['variable']
        );

        $this->assertSame(
            'payment_asset',
            $mapping['counter_variable']
        );
    }

    public function test_adjustment_does_not_hard_code_owner_account(): void
    {
        $mapping = app(
            AccountingEventMap::class
        )->contextual(
            AccountingEventMap::EVENT_ADJUSTMENT
        );

        $this->assertSame(
            'balance_account',
            $mapping['variable']
        );

        $this->assertSame(
            SystemChartOfAccounts::ADJUSTMENT_CLEARING,
            $mapping['counter']
        );
    }

    public function test_unknown_fixed_event_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(AccountingEventMap::class)
            ->fixed('does_not_exist');
    }

    public function test_unknown_payment_method_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(AccountingEventMap::class)
            ->paymentAsset('cheque');
    }

    public function test_unknown_tenant_fund_type_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        app(AccountingEventMap::class)
            ->tenantFundLiability('unknown');
    }
}
