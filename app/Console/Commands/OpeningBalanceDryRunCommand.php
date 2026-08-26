<?php

namespace App\Console\Commands;

use App\Services\Accounting\OpeningBalanceDiscoveryService;
use App\Console\Concerns\ResolvesOrganisationOption;
use App\Support\OrganisationContext;
use Illuminate\Console\Command;

class OpeningBalanceDryRunCommand extends Command
{
    use ResolvesOrganisationOption;

    protected $signature =
        'patrimoine:opening-balance-dry-run
        {--json : Output complete discovery result as JSON}
        {--organisation= : Organisation ID (required when several organisations exist)}';

    protected $description =
        'Calculate the V1.0.5 opening Journal position without posting anything';

    public function handle(
        OpeningBalanceDiscoveryService $discovery
    ): int {
        /*
         * V1.0.10 multi-tenancy: the opening-balance suite operates
         * on exactly one organisation's books.
         */
        $organisation = $this->resolveOrganisationOrFail();

        if ($organisation === null) {
            return self::FAILURE;
        }

        return OrganisationContext::runAs(
            (int) $organisation->id,
            fn (): int => $this->runScoped($discovery)
        );
    }

    /**
     * The original command body, executed with the organisation
     * context bound.
     */
    private function runScoped(
        OpeningBalanceDiscoveryService $discovery
    ): int {
        $beforeEntries = \DB::table(
            'journal_entries'
        )->count();

        $beforeLines = \DB::table(
            'journal_lines'
        )->count();

        $result = $discovery->discover();

        $afterEntries = \DB::table(
            'journal_entries'
        )->count();

        $afterLines = \DB::table(
            'journal_lines'
        )->count();

        if (
            $beforeEntries !== $afterEntries
            || $beforeLines !== $afterLines
        ) {
            $this->error(
                'Opening-balance dry-run mutated the Financial Journal.'
            );

            return self::FAILURE;
        }

        if (
            ! $result['reconciliation'][
                'all_positions_valid'
            ]
            || ! $result['reconciliation'][
                'source_totals_match_positions'
            ]
        ) {
            $this->error(
                'Opening-balance discovery failed operational reconciliation.'
            );

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );

            return self::SUCCESS;
        }

        $this->info(
            'V1.0.5 Opening Balance Dry Run'
        );

        $this->newLine();

        $this->table(
            [
                'Category',
                'Positions',
            ],
            [
                [
                    'Tenant funds',
                    $result['counts']['tenant_funds'],
                ],
                [
                    'Owner balances',
                    $result['counts']['owner_balances'],
                ],
                [
                    'Rent receivables',
                    $result['counts']['rent_receivables'],
                ],
                [
                    'Security deposit debt receivables',
                    $result['counts'][
                        'security_deposit_debt_receivables'
                    ],
                ],
                [
                    'TOTAL',
                    $result['counts']['positions'],
                ],
            ]
        );

        $this->newLine();

        $this->table(
            [
                'Account',
                'Debit',
                'Credit',
                'Net',
            ],
            collect(
                $result['totals']
            )
                ->map(
                    fn (
                        array $total,
                        string $account
                    ): array => [
                        $account,
                        $total['debit'],
                        $total['credit'],
                        $total['net'],
                    ]
                )
                ->values()
                ->all()
        );

        $this->newLine();

        $this->line(
            'Journal entries created: '
            .($afterEntries - $beforeEntries)
        );

        $this->line(
            'Journal lines created: '
            .($afterLines - $beforeLines)
        );

        return self::SUCCESS;
    }
}
