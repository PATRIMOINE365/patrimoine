<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use App\Services\Accounting\OpeningBalanceCutoverService;
use App\Services\Accounting\OpeningBalanceDiscoveryService;
use App\Services\Accounting\SystemChartOfAccounts;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OpeningBalanceCutoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_cutover_command_requires_explicit_date(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-cutover',
            [
                '--yes' =>
                    true,
            ]
        )
            ->expectsOutputToContain(
                '--date=YYYY-MM-DD'
            )
            ->assertFailed();

        $this->assertDatabaseCount(
            'accounting_cutovers',
            0
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );
    }

    public function test_cutover_refuses_failed_reconciliation(): void
    {
        $this->bindDiscovery(
            $this->discoveryResult(
                [],
                reconciled: false
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'operational reconciliation failed'
        );

        try {
            app(
                OpeningBalanceCutoverService::class
            )->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );
        } finally {
            $this->assertDatabaseCount(
                'accounting_cutovers',
                0
            );

            $this->assertDatabaseCount(
                'journal_entries',
                0
            );
        }
    }

    public function test_empty_reconciled_cutover_completes_once(): void
    {
        $this->bindDiscovery(
            $this->discoveryResult(
                []
            )
        );

        $service =
            app(
                OpeningBalanceCutoverService::class
            );

        $first =
            $service->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );

        $second =
            $service->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            AccountingCutover::STATUS_COMPLETED,
            $first->status
        );

        $this->assertSame(
            0,
            $first->position_count
        );

        $this->assertSame(
            0,
            $first->journal_entry_count
        );

        $this->assertDatabaseCount(
            'accounting_cutovers',
            1
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );
    }

    public function test_completed_cutover_cannot_be_repeated_with_different_date(): void
    {
        $this->bindDiscovery(
            $this->discoveryResult(
                []
            )
        );

        $service =
            app(
                OpeningBalanceCutoverService::class
            );

        $service->execute(
            CarbonImmutable::parse(
                '2026-08-19'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'already posted using cutover date'
        );

        $service->execute(
            CarbonImmutable::parse(
                '2026-08-20'
            )
        );
    }

    public function test_real_opening_positions_create_one_journal_entry_per_position(): void
    {
        $positions = [
            $this->position(
                category:
                    'rent_receivable',
                account:
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                direction:
                    'debit',
                amount:
                    1200,
                sourceId:
                    101
            ),

            $this->position(
                category:
                    'tenant_fund',
                account:
                    '2000',
                direction:
                    'credit',
                amount:
                    500,
                sourceId:
                    202,
                snapshot: [
                    'fund_type' =>
                        'rent_reserve',

                    'lease_id' =>
                        88,

                    'tenant_id' =>
                        44,
                ]
            ),
        ];

        $this->bindDiscovery(
            $this->discoveryResult(
                $positions
            )
        );

        $cutover =
            app(
                OpeningBalanceCutoverService::class
            )->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );

        $this->assertSame(
            2,
            $cutover->position_count
        );

        $this->assertSame(
            2,
            $cutover->journal_entry_count
        );

        $this->assertSame(
            AccountingCutover::STATUS_COMPLETED,
            $cutover->status
        );

        $this->assertDatabaseCount(
            'journal_entries',
            2
        );

        $entries =
            JournalEntry::query()
                ->orderBy('id')
                ->get();

        $this->assertCount(
            2,
            $entries
        );

        foreach ($entries as $entry) {
            $this->assertSame(
                '2026-08-19',
                $entry
                    ->journal_date
                    ->toDateString()
            );

            $this->assertSame(
                OpeningBalanceCutoverService::DESCRIPTION,
                $entry->description
            );

            $debitTotal =
                (int) $entry
                    ->lines
                    ->sum('debit_amount');

            $creditTotal =
                (int) $entry
                    ->lines
                    ->sum('credit_amount');

            $this->assertGreaterThan(
                0,
                $debitTotal
            );

            $this->assertGreaterThan(
                0,
                $creditTotal
            );

            $this->assertSame(
                $debitTotal,
                $creditTotal
            );
        }
    }

    public function test_second_run_does_not_duplicate_opening_entries(): void
    {
        $positions = [
            $this->position(
                category:
                    'rent_receivable',
                account:
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                direction:
                    'debit',
                amount:
                    1000,
                sourceId:
                    501
            ),
        ];

        $this->bindDiscovery(
            $this->discoveryResult(
                $positions
            )
        );

        $service =
            app(
                OpeningBalanceCutoverService::class
            );

        $first =
            $service->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );

        $second =
            $service->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount(
            'accounting_cutovers',
            1
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );
    }

    public function test_failure_in_any_position_rolls_back_entire_cutover(): void
    {
        $positions = [
            $this->position(
                category:
                    'rent_receivable',
                account:
                    SystemChartOfAccounts::TENANT_RENT_RECEIVABLE,
                direction:
                    'debit',
                amount:
                    1000,
                sourceId:
                    701
            ),

            $this->position(
                category:
                    'invalid_test_position',
                account:
                    'ACCOUNT-DOES-NOT-EXIST',
                direction:
                    'debit',
                amount:
                    999,
                sourceId:
                    702
            ),
        ];

        $this->bindDiscovery(
            $this->discoveryResult(
                $positions
            )
        );

        try {
            app(
                OpeningBalanceCutoverService::class
            )->execute(
                CarbonImmutable::parse(
                    '2026-08-19'
                )
            );

            $this->fail(
                'Expected opening-balance posting failure.'
            );
        } catch (\Throwable) {
            $this->assertDatabaseCount(
                'accounting_cutovers',
                0
            );

            $this->assertDatabaseCount(
                'journal_entries',
                0
            );

            $this->assertDatabaseCount(
                'journal_lines',
                0
            );
        }
    }

    /**
     * @param array<int,array<string,mixed>> $positions
     */
    private function bindDiscovery(
        array $result
    ): void {
        $mock =
            Mockery::mock(
                OpeningBalanceDiscoveryService::class
            );

        $mock
            ->shouldReceive(
                'discover'
            )
            ->andReturn(
                $result
            );

        $this->app->instance(
            OpeningBalanceDiscoveryService::class,
            $mock
        );
    }

    /**
     * @param array<int,array<string,mixed>> $positions
     * @return array<string,mixed>
     */
    private function discoveryResult(
        array $positions,
        bool $reconciled = true
    ): array {
        return [
            'generated_at' =>
                now()
                    ->toIso8601String(),

            'positions' =>
                $positions,

            'totals' =>
                [],

            'counts' => [
                'positions' =>
                    count(
                        $positions
                    ),
            ],

            'reconciliation' => [
                'all_positions_valid' =>
                    true,

                'source_totals_match_positions' =>
                    $reconciled,

                'journal_entries_created' =>
                    0,

                'journal_lines_created' =>
                    0,

                'source_totals' =>
                    [],

                'position_totals' =>
                    [],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $snapshot
     * @return array<string,mixed>
     */
    private function position(
        string $category,
        string $account,
        string $direction,
        int $amount,
        int $sourceId,
        array $snapshot = []
    ): array {
        return [
            'category' =>
                $category,

            'account_code' =>
                $account,

            'direction' =>
                $direction,

            'amount' =>
                $amount,

            'source_type' =>
                'Tests\\SyntheticOpeningSource',

            'source_id' =>
                $sourceId,

            'snapshot' =>
                $snapshot,
        ];
    }
}
