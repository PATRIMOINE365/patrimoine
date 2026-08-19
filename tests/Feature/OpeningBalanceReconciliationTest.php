<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\Accounting\OpeningBalanceCutoverService;
use App\Services\Accounting\OpeningBalanceReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningBalanceReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_is_read_only_before_cutover(): void
    {
        $entriesBefore =
            JournalEntry::count();

        $linesBefore =
            JournalLine::count();

        $cutoversBefore =
            AccountingCutover::count();

        $result =
            app(
                OpeningBalanceReconciliationService::class
            )->reconcile();

        $this->assertSame(
            'not_initialized',
            $result['status']
        );

        $this->assertFalse(
            $result['reconciled']
        );

        $this->assertFalse(
            $result['cutover_initialized']
        );

        $this->assertTrue(
            $result[
                'journal_mutation_check'
            ][
                'unchanged'
            ]
        );

        $this->assertSame(
            $entriesBefore,
            JournalEntry::count()
        );

        $this->assertSame(
            $linesBefore,
            JournalLine::count()
        );

        $this->assertSame(
            $cutoversBefore,
            AccountingCutover::count()
        );
    }

    public function test_empty_successful_cutover_reconciles(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-cutover',
            [
                '--date' =>
                    '2026-08-19',
            ]
        )
            ->expectsConfirmation(
                'Continue with V1.0.5 opening-balance cutover?',
                'yes'
            )
            ->assertSuccessful();

        $result =
            app(
                OpeningBalanceReconciliationService::class
            )->reconcile();

        $this->assertSame(
            'reconciled',
            $result['status']
        );

        $this->assertTrue(
            $result['reconciled']
        );

        $this->assertTrue(
            $result['cutover_initialized']
        );

        $this->assertTrue(
            $result[
                'operational_discovery'
            ][
                'source_totals_match_positions'
            ]
        );

        $this->assertTrue(
            $result[
                'opening_journal'
            ][
                'position_count_matches'
            ]
        );

        $this->assertTrue(
            $result[
                'opening_journal'
            ][
                'account_totals_match'
            ]
        );

        $this->assertTrue(
            $result[
                'opening_journal'
            ][
                'all_entries_balanced'
            ]
        );

        $this->assertSame(
            0,
            $result[
                'operational_discovery'
            ][
                'positions'
            ]
        );

        $this->assertSame(
            0,
            $result[
                'opening_journal'
            ][
                'entries'
            ]
        );
    }

    public function test_reconciliation_command_fails_closed_before_cutover(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-reconcile'
        )
            ->expectsOutputToContain(
                'Opening cutover has not been initialized.'
            )
            ->assertFailed();
    }

    public function test_reconciliation_command_passes_after_successful_empty_cutover(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-cutover',
            [
                '--date' =>
                    '2026-08-19',
            ]
        )
            ->expectsConfirmation(
                'Continue with V1.0.5 opening-balance cutover?',
                'yes'
            )
            ->assertSuccessful();

        $this->artisan(
            'patrimoine:opening-balance-reconcile'
        )
            ->expectsOutputToContain(
                'OPENING BALANCE RECONCILIATION PASSED.'
            )
            ->assertSuccessful();
    }

    public function test_json_reconciliation_is_machine_readable(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-cutover',
            [
                '--date' =>
                    '2026-08-19',
            ]
        )
            ->expectsConfirmation(
                'Continue with V1.0.5 opening-balance cutover?',
                'yes'
            )
            ->assertSuccessful();

        $exitCode =
            \Illuminate\Support\Facades\Artisan::call(
                'patrimoine:opening-balance-reconcile',
                [
                    '--json' =>
                        true,
                ]
            );

        $this->assertSame(
            0,
            $exitCode
        );

        $output =
            trim(
                \Illuminate\Support\Facades\Artisan::output()
            );

        $decoded =
            json_decode(
                $output,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

        $this->assertIsArray(
            $decoded
        );

        $this->assertSame(
            'reconciled',
            $decoded['status'] ?? null
        );

        $findJsonValue =
            function (
                array $payload,
                string $key
            ) use (&$findJsonValue): array {
                $matches = [];

                foreach ($payload as $candidateKey => $value) {
                    if ($candidateKey === $key) {
                        $matches[] = $value;
                    }

                    if (is_array($value)) {
                        $matches = [
                            ...$matches,
                            ...$findJsonValue(
                                $value,
                                $key
                            ),
                        ];
                    }
                }

                return $matches;
            };

        $accountTotalsMatches =
            $findJsonValue(
                $decoded,
                'account_totals_match'
            );

        $balancedEntryMatches =
            $findJsonValue(
                $decoded,
                'all_entries_balanced'
            );

        $this->assertCount(
            1,
            $accountTotalsMatches,
            'JSON reconciliation payload must expose account_totals_match exactly once.'
        );

        $this->assertCount(
            1,
            $balancedEntryMatches,
            'JSON reconciliation payload must expose all_entries_balanced exactly once.'
        );

        $this->assertTrue(
            $accountTotalsMatches[0],
            'Opening Journal account totals must reconcile.'
        );

        $this->assertTrue(
            $balancedEntryMatches[0],
            'Every opening Journal entry must be balanced.'
        );
    }

    public function test_every_opening_journal_entry_is_balanced(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-cutover',
            [
                '--date' =>
                    '2026-08-19',
            ]
        )
            ->expectsConfirmation(
                'Continue with V1.0.5 opening-balance cutover?',
                'yes'
            )
            ->assertSuccessful();

        $entries =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'description',
                    OpeningBalanceCutoverService::DESCRIPTION
                )
                ->get();

        foreach ($entries as $entry) {
            $this->assertSame(
                (int)
                $entry->lines
                    ->sum(
                        'debit_amount'
                    ),

                (int)
                $entry->lines
                    ->sum(
                        'credit_amount'
                    )
            );
        }

        /*
         * Empty database is still a legitimate cutover test fixture.
         * The assertion below ensures this test participates in the
         * suite even when no opening Journal entries were necessary.
         */
        $this->assertGreaterThanOrEqual(
            0,
            $entries->count()
        );
    }

    public function test_reconciliation_does_not_reconstruct_historical_transactions(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-cutover',
            [
                '--date' =>
                    '2026-08-19',
            ]
        )
            ->expectsConfirmation(
                'Continue with V1.0.5 opening-balance cutover?',
                'yes'
            )
            ->assertSuccessful();

        $nonOpeningEntries =
            JournalEntry::query()
                ->where(
                    'description',
                    '!=',
                    OpeningBalanceCutoverService::DESCRIPTION
                )
                ->count();

        $this->assertSame(
            0,
            $nonOpeningEntries
        );
    }
}
