<?php

namespace App\Console\Commands;

use App\Services\Accounting\OpeningBalanceReconciliationService;
use App\Console\Concerns\ResolvesOrganisationOption;
use App\Support\OrganisationContext;
use Illuminate\Console\Command;
use Throwable;

class OpeningBalanceReconcileCommand extends Command
{
    use ResolvesOrganisationOption;

    protected $signature =
        'patrimoine:opening-balance-reconcile
        {--json : Output reconciliation as JSON}
        {--organisation= : Organisation ID (required when several organisations exist)}';

    protected $description =
        'Validate the V1.0.5 opening accounting cutover without modifying data';

    public function handle(
        OpeningBalanceReconciliationService $service
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
            fn (): int => $this->runScoped($service)
        );
    }

    /**
     * The original command body, executed with the organisation
     * context bound.
     */
    private function runScoped(
        OpeningBalanceReconciliationService $service
    ): int {
        try {
            $result =
                $service->reconcile();
        } catch (Throwable $exception) {
            $this->error(
                'Opening-balance reconciliation failed: '
                . $exception->getMessage()
            );

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_THROW_ON_ERROR
                )
            );

            return $result['reconciled']
                ? self::SUCCESS
                : self::FAILURE;
        }

        $this->newLine();
        $this->info(
            'V1.0.5 Opening Balance Reconciliation'
        );
        $this->newLine();

        $this->table(
            ['Check', 'Result'],
            [
                [
                    'Cutover initialized',
                    $this->yesNo(
                        $result[
                            'cutover_initialized'
                        ]
                    ),
                ],
                [
                    'Operational source reconciliation',
                    $this->yesNo(
                        $result[
                            'operational_discovery'
                        ][
                            'source_totals_match_positions'
                        ]
                    ),
                ],
                [
                    'Opening position count',
                    $result[
                        'operational_discovery'
                    ][
                        'positions'
                    ],
                ],
                [
                    'Opening Journal entries',
                    $result[
                        'opening_journal'
                    ][
                        'entries'
                    ],
                ],
                [
                    'Position count matches',
                    $this->yesNo(
                        $result[
                            'opening_journal'
                        ][
                            'position_count_matches'
                        ]
                    ),
                ],
                [
                    'Account totals match',
                    $this->yesNo(
                        $result[
                            'opening_journal'
                        ][
                            'account_totals_match'
                        ]
                    ),
                ],
                [
                    'Every opening entry balanced',
                    $this->yesNo(
                        $result[
                            'opening_journal'
                        ][
                            'all_entries_balanced'
                        ]
                    ),
                ],
                [
                    'Reconciliation read-only',
                    $this->yesNo(
                        $result[
                            'journal_mutation_check'
                        ][
                            'unchanged'
                        ]
                    ),
                ],
            ]
        );

        if (
            count(
                $result['accounts']
            ) > 0
        ) {
            $this->newLine();

            $this->table(
                [
                    'Account',
                    'Expected Net',
                    'Actual Net',
                    'Difference',
                    'Match',
                ],
                array_map(
                    fn (array $row): array => [
                        $row['account'],
                        $row['expected_net'],
                        $row['actual_net'],
                        $row['difference'],
                        $this->yesNo(
                            $row['matches']
                        ),
                    ],
                    $result['accounts']
                )
            );
        }

        $this->newLine();

        if (
            $result['status']
            === 'not_initialized'
        ) {
            $this->warn(
                'Opening cutover has not been initialized.'
            );

            $this->line(
                'This is expected on a pre-cutover development database.'
            );

            return self::FAILURE;
        }

        if (! $result['reconciled']) {
            $this->error(
                'OPENING BALANCE RECONCILIATION FAILED.'
            );

            return self::FAILURE;
        }

        $this->info(
            'OPENING BALANCE RECONCILIATION PASSED.'
        );

        return self::SUCCESS;
    }

    private function yesNo(bool $value): string
    {
        return $value
            ? 'YES'
            : 'NO';
    }
}
