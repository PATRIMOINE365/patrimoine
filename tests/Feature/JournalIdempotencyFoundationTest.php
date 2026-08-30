<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Services\Accounting\AccountingBootstrapService;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalIdempotencyFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemChartOfAccounts::class)->install();
    }

    public function test_same_idempotency_key_returns_existing_entry(): void
    {
        $service = app(JournalPostingService::class);

        $header = [
            'journal_date' => '2026-08-19',
            'transaction_type' => 'idempotency_test',
            'description' => 'Retry-safe posting.',
            'idempotency_key' => 'payment:123:receipt',
        ];

        $lines = [
            [
                'account_code' =>
                    SystemChartOfAccounts::CASH,

                'debit' => 5000,
                'credit' => 0,
            ],
            [
                'account_code' =>
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                'debit' => 0,
                'credit' => 5000,
            ],
        ];

        $first = $service->post(
            $header,
            $lines
        );

        $second = $service->post(
            $header,
            $lines
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            $first->journal_number,
            $second->journal_number
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );

        $this->assertDatabaseCount(
            'journal_lines',
            2
        );

        /*
         * V1.0.36: the journal's counter is the shared one now. Its
         * numbers are unchanged; the row lives in document_sequences.
         */
        $this->assertDatabaseCount(
            'document_sequences',
            1
        );
    }

    public function test_different_idempotency_keys_create_different_entries(): void
    {
        $service = app(JournalPostingService::class);

        $first = $this->postJournalEntry(
            $service,
            'payment:1'
        );

        $second = $this->postJournalEntry(
            $service,
            'payment:2'
        );

        $this->assertNotSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            'JRN-2026-000001',
            $first->journal_number
        );

        $this->assertSame(
            'JRN-2026-000002',
            $second->journal_number
        );
    }

    public function test_posting_without_idempotency_key_still_creates_new_entries(): void
    {
        $service = app(JournalPostingService::class);

        $header = [
            'journal_date' => '2026-08-19',
            'transaction_type' => 'non_idempotent_test',
        ];

        $lines = $this->lines();

        $first = $service->post(
            $header,
            $lines
        );

        $second = $service->post(
            $header,
            $lines
        );

        $this->assertNotSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount(
            'journal_entries',
            2
        );
    }

    public function test_journal_posting_reinstalls_missing_system_chart_before_posting(): void
    {
        AccountingAccount::query()->delete();

        $this->assertDatabaseCount(
            'accounting_accounts',
            0
        );

        $entry = app(
            JournalPostingService::class
        )->post(
            [
                'journal_date' =>
                    '2026-08-19',

                'transaction_type' =>
                    'bootstrap_boundary_test',

                'idempotency_key' =>
                    'bootstrap-boundary:1',
            ],
            [
                [
                    'account_code' =>
                        SystemChartOfAccounts::CASH,

                    'debit' =>
                        1000,

                    'credit' =>
                        0,
                ],
                [
                    'account_code' =>
                        SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                    'debit' =>
                        0,

                    'credit' =>
                        1000,
                ],
            ]
        );

        $this->assertDatabaseCount(
            'accounting_accounts',
            19
        );

        $this->assertTrue(
            $entry->isBalanced()
        );
    }

    public function test_accounting_bootstrap_installs_system_chart(): void
    {
        AccountingAccount::query()->delete();

        $this->assertDatabaseCount(
            'accounting_accounts',
            0
        );

        app(
            AccountingBootstrapService::class
        )->bootstrap();

        $this->assertDatabaseCount(
            'accounting_accounts',
            19
        );
    }

    public function test_accounting_bootstrap_is_idempotent(): void
    {
        $bootstrap = app(
            AccountingBootstrapService::class
        );

        $bootstrap->bootstrap();
        $bootstrap->bootstrap();

        $this->assertDatabaseCount(
            'accounting_accounts',
            19
        );
    }

    private function postJournalEntry(
        JournalPostingService $service,
        string $key
    ) {
        return $service->post(
            [
                'journal_date' => '2026-08-19',
                'transaction_type' =>
                    'idempotency_test',

                'idempotency_key' =>
                    $key,
            ],
            $this->lines()
        );
    }

    private function lines(): array
    {
        return [
            [
                'account_code' =>
                    SystemChartOfAccounts::CASH,

                'debit' => 1000,
                'credit' => 0,
            ],
            [
                'account_code' =>
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,

                'debit' => 0,
                'credit' => 1000,
            ],
        ];
    }
}
