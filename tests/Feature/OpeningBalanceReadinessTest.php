<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\Accounting\OpeningBalanceReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OpeningBalanceReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_pre_cutover_database_is_ready(): void
    {
        $result =
            app(
                OpeningBalanceReadinessService::class
            )->evaluate();

        $this->assertSame(
            OpeningBalanceReadinessService::
                STATE_READY_FOR_CUTOVER,
            $result['state']
        );

        $this->assertTrue(
            $result['passed']
        );

        $this->assertTrue(
            $result['ready_for_cutover']
        );

        $this->assertFalse(
            $result['cutover_initialized']
        );

        $this->assertTrue(
            $result['source_reconciled']
        );

        $this->assertTrue(
            $result['read_only']
        );

        $this->assertSame(
            [],
            $result['blockers']
        );
    }

    public function test_readiness_command_passes_on_safe_pre_cutover_database(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-readiness'
        )
            ->expectsOutputToContain(
                'READY FOR CONTROLLED CUTOVER'
            )
            ->assertSuccessful();
    }

    public function test_json_readiness_is_machine_readable(): void
    {
        $exitCode =
            Artisan::call(
                'patrimoine:opening-balance-readiness',
                [
                    '--json' =>
                        true,
                ]
            );

        $this->assertSame(
            0,
            $exitCode
        );

        $decoded =
            json_decode(
                trim(
                    Artisan::output()
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

        $this->assertIsArray(
            $decoded
        );

        $this->assertSame(
            OpeningBalanceReadinessService::
                STATE_READY_FOR_CUTOVER,
            $decoded['state']
        );

        $this->assertTrue(
            $decoded['passed']
        );

        $this->assertTrue(
            $decoded['read_only']
        );
    }

    public function test_readiness_evaluation_does_not_mutate_accounting_tables(): void
    {
        $before = [
            'accounting_cutovers' =>
                AccountingCutover::query()
                    ->count(),

            'journal_entries' =>
                JournalEntry::query()
                    ->count(),

            'journal_lines' =>
                JournalLine::query()
                    ->count(),
        ];

        app(
            OpeningBalanceReadinessService::class
        )->evaluate();

        $after = [
            'accounting_cutovers' =>
                AccountingCutover::query()
                    ->count(),

            'journal_entries' =>
                JournalEntry::query()
                    ->count(),

            'journal_lines' =>
                JournalLine::query()
                    ->count(),
        ];

        $this->assertSame(
            $before,
            $after
        );
    }

    public function test_successful_empty_cutover_moves_gate_to_reconciled_state(): void
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
                OpeningBalanceReadinessService::class
            )->evaluate();

        $this->assertSame(
            OpeningBalanceReadinessService::
                STATE_CUTOVER_RECONCILED,
            $result['state']
        );

        $this->assertTrue(
            $result['passed']
        );

        $this->assertTrue(
            $result['cutover_initialized']
        );

        $this->assertFalse(
            $result['ready_for_cutover']
        );

        $this->assertTrue(
            $result[
                'post_cutover_reconciled'
            ]
        );

        $this->assertTrue(
            $result['read_only']
        );

        $this->assertSame(
            [],
            $result['blockers']
        );
    }

    public function test_readiness_command_passes_after_successful_empty_cutover(): void
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
            'patrimoine:opening-balance-readiness'
        )
            ->expectsOutputToContain(
                'CUTOVER IS RECONCILED'
            )
            ->assertSuccessful();
    }

    public function test_phase_2_gate_does_not_perform_cutover_itself(): void
    {
        $this->assertSame(
            0,
            AccountingCutover::query()
                ->count()
        );

        $this->artisan(
            'patrimoine:opening-balance-readiness'
        )
            ->assertSuccessful();

        $this->assertSame(
            0,
            AccountingCutover::query()
                ->count()
        );

        $this->assertSame(
            0,
            JournalEntry::query()
                ->count()
        );

        $this->assertSame(
            0,
            JournalLine::query()
                ->count()
        );
    }
}
