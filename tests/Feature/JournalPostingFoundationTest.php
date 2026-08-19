<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class JournalPostingFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemChartOfAccounts::class)->install();
    }

    public function test_balanced_journal_transaction_can_be_posted(): void
    {
        $entry = app(JournalPostingService::class)->post(
            [
                'journal_date' => '2026-08-19',
                'transaction_type' => 'foundation_test',
                'description' => 'Test cash receipt.',
                'source_type' => 'test',
                'source_id' => 10,
                'snapshot' => [
                    'tenant' => 'Test Tenant',
                ],
            ],
            [
                [
                    'account_code' => SystemChartOfAccounts::CASH,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_code' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ]
        );

        $this->assertSame(
            'JRN-2026-000001',
            $entry->journal_number
        );

        $this->assertSame(1000, $entry->debitTotal());
        $this->assertSame(1000, $entry->creditTotal());
        $this->assertTrue($entry->isBalanced());

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);

        $this->assertDatabaseHas('journal_entries', [
            'journal_number' => 'JRN-2026-000001',
            'transaction_type' => 'foundation_test',
            'source_type' => 'test',
            'source_id' => 10,
        ]);
    }

    public function test_journal_lines_freeze_account_identity(): void
    {
        $entry = $this->postSimpleTransaction();

        $cashLine = $entry->lines
            ->firstWhere(
                'account_code_snapshot',
                SystemChartOfAccounts::CASH
            );

        $this->assertNotNull($cashLine);

        $this->assertSame('Cash', $cashLine->account_name_snapshot);

        $this->assertSame(
            AccountingAccount::TYPE_ASSET,
            $cashLine->account_type_snapshot
        );
    }

    public function test_journal_number_increments_within_year_and_restarts_next_year(): void
    {
        $first = $this->postSimpleTransaction(
            '2026-12-31'
        );

        $second = $this->postSimpleTransaction(
            '2026-12-31'
        );

        $third = $this->postSimpleTransaction(
            '2027-01-01'
        );

        $this->assertSame(
            'JRN-2026-000001',
            $first->journal_number
        );

        $this->assertSame(
            'JRN-2026-000002',
            $second->journal_number
        );

        $this->assertSame(
            'JRN-2027-000001',
            $third->journal_number
        );
    }

    public function test_unbalanced_transaction_is_rejected_without_persistence(): void
    {
        try {
            app(JournalPostingService::class)->post(
                [
                    'journal_date' => '2026-08-19',
                    'transaction_type' => 'invalid_test',
                ],
                [
                    [
                        'account_code' => SystemChartOfAccounts::CASH,
                        'debit' => 1000,
                        'credit' => 0,
                    ],
                    [
                        'account_code' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                        'debit' => 0,
                        'credit' => 999,
                    ],
                ]
            );

            $this->fail(
                'Expected unbalanced Journal transaction to be rejected.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'not balanced',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_lines', 0);
        $this->assertDatabaseCount('journal_sequences', 0);
    }

    public function test_each_normal_journal_line_must_be_exclusively_debit_or_credit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Each Journal line must contain either a debit or a credit.'
        );

        app(JournalPostingService::class)->post(
            [
                'journal_date' => '2026-08-19',
                'transaction_type' => 'invalid_line_test',
            ],
            [
                [
                    'account_code' => SystemChartOfAccounts::CASH,
                    'debit' => 1000,
                    'credit' => 1000,
                ],
                [
                    'account_code' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ]
        );
    }

    public function test_unknown_account_rolls_back_entire_posting_and_journal_number(): void
    {
        try {
            app(JournalPostingService::class)->post(
                [
                    'journal_date' => '2026-08-19',
                    'transaction_type' => 'unknown_account_test',
                ],
                [
                    [
                        'account_code' => '999999',
                        'debit' => 1000,
                        'credit' => 0,
                    ],
                    [
                        'account_code' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                        'debit' => 0,
                        'credit' => 1000,
                    ],
                ]
            );

            $this->fail(
                'Expected unknown account to reject Journal posting.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'Unknown or inactive',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_lines', 0);
        $this->assertDatabaseCount('journal_sequences', 0);
    }

    public function test_actor_identity_is_frozen_when_journal_is_posted(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Journal User',
        ]);

        $entry = app(JournalPostingService::class)->post(
            [
                'journal_date' => '2026-08-19',
                'transaction_type' => 'actor_snapshot_test',
                'actor_user_id' => $user->id,
            ],
            [
                [
                    'account_code' => SystemChartOfAccounts::CASH,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_code' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ]
        );

        $this->assertSame(
            'Original Journal User',
            $entry->actor_name_snapshot
        );

        $user->update([
            'name' => 'Renamed User',
        ]);

        $entry->refresh();

        $this->assertSame(
            'Original Journal User',
            $entry->actor_name_snapshot
        );
    }

    public function test_posted_journal_entry_and_lines_are_immutable(): void
    {
        $entry = $this->postSimpleTransaction();

        try {
            $entry->description = 'Changed description';
            $entry->save();

            $this->fail(
                'Expected Journal entry update to be rejected.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Posted Journal entries are immutable.',
                $exception->getMessage()
            );
        }

        try {
            $entry->delete();

            $this->fail(
                'Expected Journal entry deletion to be rejected.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Posted Journal entries cannot be deleted.',
                $exception->getMessage()
            );
        }

        $line = $entry->lines->first();

        $this->assertInstanceOf(
            JournalLine::class,
            $line
        );

        try {
            $line->memo = 'Changed memo';
            $line->save();

            $this->fail(
                'Expected Journal line update to be rejected.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Posted Journal lines are immutable.',
                $exception->getMessage()
            );
        }

        try {
            $line->delete();

            $this->fail(
                'Expected Journal line deletion to be rejected.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Posted Journal lines cannot be deleted.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    private function postSimpleTransaction(
        string $date = '2026-08-19'
    ): JournalEntry {
        return app(JournalPostingService::class)->post(
            [
                'journal_date' => $date,
                'transaction_type' => 'simple_test',
            ],
            [
                [
                    'account_code' => SystemChartOfAccounts::CASH,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_code' => SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ]
        );
    }
}
