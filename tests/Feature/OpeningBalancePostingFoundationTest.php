<?php

namespace Tests\Feature;

use App\Services\Accounting\OpeningBalancePostingService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OpeningBalancePostingFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemChartOfAccounts::class)->install();
    }

    public function test_opening_position_posts_one_balanced_entry_per_account(): void
    {
        $entries = app(
            OpeningBalancePostingService::class
        )->post(
            [
                [
                    'account_code' =>
                        SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,

                    'direction' =>
                        'credit',

                    'amount' =>
                        5000,

                    'source_type' =>
                        'tenant_fund_account',

                    'source_id' =>
                        10,

                    'snapshot' => [
                        'lease_reference' =>
                            'LEASE-001',
                    ],
                ],
                [
                    'account_code' =>
                        SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                    'direction' =>
                        'credit',

                    'amount' =>
                        3000,

                    'source_type' =>
                        'owner_account',

                    'source_id' =>
                        20,
                ],
            ],
            '2026-08-19'
        );

        $this->assertCount(2, $entries);

        foreach ($entries as $entry) {
            $this->assertTrue(
                $entry->isBalanced()
            );

            $this->assertSame(
                OpeningBalancePostingService::TRANSACTION_TYPE,
                $entry->transaction_type
            );

            $this->assertSame(
                OpeningBalancePostingService::DESCRIPTION,
                $entry->description
            );

            $this->assertSame(
                '2026-08-19',
                $entry->journal_date->toDateString()
            );
        }

        $this->assertDatabaseCount(
            'journal_entries',
            2
        );

        $this->assertDatabaseCount(
            'journal_lines',
            4
        );
    }

    public function test_opening_balance_supports_debit_position(): void
    {
        $entries = app(
            OpeningBalancePostingService::class
        )->post(
            [
                [
                    'account_code' =>
                        SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                    'direction' =>
                        'debit',

                    'amount' =>
                        12000,
                ],
            ],
            '2026-08-19'
        );

        $entry = $entries[0];

        $receivable = $entry->lines
            ->firstWhere(
                'account_code_snapshot',
                SystemChartOfAccounts::TENANT_RENT_RECEIVABLE
            );

        $clearing = $entry->lines
            ->firstWhere(
                'account_code_snapshot',
                SystemChartOfAccounts::OPENING_BALANCE_CLEARING
            );

        $this->assertSame(
            12000,
            $receivable->debit_amount
        );

        $this->assertSame(
            12000,
            $clearing->credit_amount
        );
    }

    public function test_invalid_opening_position_rolls_back_all_entries(): void
    {
        try {
            app(
                OpeningBalancePostingService::class
            )->post(
                [
                    [
                        'account_code' =>
                            SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,

                        'direction' =>
                            'credit',

                        'amount' =>
                            3000,
                    ],
                    [
                        'account_code' =>
                            SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,

                        'direction' =>
                            'invalid',

                        'amount' =>
                            1000,
                    ],
                ],
                '2026-08-19'
            );

            $this->fail(
                'Expected invalid opening position to fail.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'direction',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );

        $this->assertDatabaseCount(
            'journal_sequences',
            0
        );
    }

    public function test_empty_opening_position_is_a_no_op(): void
    {
        $entries = app(
            OpeningBalancePostingService::class
        )->post(
            [],
            '2026-08-19'
        );

        $this->assertSame([], $entries);

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );
    }
}
