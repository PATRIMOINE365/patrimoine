<?php

namespace Tests\Feature;

use App\Services\Accounting\OpeningBalanceDiscoveryService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningBalanceDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_empty_application_has_empty_opening_position(): void
    {
        $result = app(
            OpeningBalanceDiscoveryService::class
        )->discover();

        $this->assertSame(
            [],
            $result['positions']
        );

        $this->assertSame(
            [],
            $result['totals']
        );

        $this->assertSame(
            0,
            $result['counts']['positions']
        );

        $this->assertTrue(
            $result['reconciliation'][
                'all_positions_valid'
            ]
        );
    }

    public function test_empty_discovery_reconciles_operational_sources_to_positions(): void
    {
        $result = app(
            OpeningBalanceDiscoveryService::class
        )->discover();

        $this->assertTrue(
            $result['reconciliation'][
                'source_totals_match_positions'
            ]
        );

        $this->assertSame(
            $result['reconciliation'][
                'source_totals'
            ],
            $result['reconciliation'][
                'position_totals'
            ]
        );

        $this->assertSame(
            0,
            $result['reconciliation'][
                'journal_entries_created'
            ]
        );

        $this->assertSame(
            0,
            $result['reconciliation'][
                'journal_lines_created'
            ]
        );
    }

    public function test_discovery_is_strictly_read_only_for_financial_journal(): void
    {
        $beforeEntries = \DB::table(
            'journal_entries'
        )->count();

        $beforeLines = \DB::table(
            'journal_lines'
        )->count();

        app(
            OpeningBalanceDiscoveryService::class
        )->discover();

        $this->assertSame(
            $beforeEntries,
            \DB::table(
                'journal_entries'
            )->count()
        );

        $this->assertSame(
            $beforeLines,
            \DB::table(
                'journal_lines'
            )->count()
        );
    }

    public function test_dry_run_command_does_not_post_journal_entries(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-dry-run'
        )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );
    }

    public function test_dry_run_json_is_available_for_cutover_review(): void
    {
        $this->artisan(
            'patrimoine:opening-balance-dry-run',
            [
                '--json' => true,
            ]
        )
            ->expectsOutputToContain(
                '"positions"'
            )
            ->assertSuccessful();
    }
}
