<?php

namespace App\Console\Commands;

use App\Services\Accounting\OpeningBalanceReadinessService;
use App\Console\Concerns\ResolvesOrganisationOption;
use App\Support\OrganisationContext;
use Illuminate\Console\Command;
use JsonException;

class OpeningBalanceReadinessCommand extends Command
{
    use ResolvesOrganisationOption;

    protected $signature =
        'patrimoine:opening-balance-readiness
        {--json : Emit machine-readable JSON}
        {--organisation= : Organisation ID (required when several organisations exist)}';

    protected $description =
        'Read-only V1.0.5 accounting cutover readiness and completion gate';

    public function handle(
        OpeningBalanceReadinessService $service
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
        OpeningBalanceReadinessService $service
    ): int {
        $result =
            $service->evaluate();

        if ((bool) $this->option('json')) {
            return $this->renderJson(
                $result
            );
        }

        $this->newLine();

        $this->info(
            'V1.0.5 Accounting Cutover Readiness'
        );

        $this->newLine();

        $rows = [
            [
                'State',
                $result['state']
                ?? 'unknown',
            ],
            [
                'Gate passed',
                $this->yesNo(
                    (bool) (
                        $result['passed']
                        ?? false
                    )
                ),
            ],
            [
                'Cutover initialized',
                $this->yesNo(
                    (bool) (
                        $result['cutover_initialized']
                        ?? false
                    )
                ),
            ],
            [
                'Operational sources reconciled',
                $this->yesNo(
                    (bool) (
                        $result['source_reconciled']
                        ?? false
                    )
                ),
            ],
            [
                'Read-only validation',
                $this->yesNo(
                    (bool) (
                        $result['read_only']
                        ?? false
                    )
                ),
            ],
        ];

        if (
            array_key_exists(
                'position_count',
                $result
            )
            && $result['position_count']
                !== null
        ) {
            $rows[] = [
                'Opening positions',
                (string) $result[
                    'position_count'
                ],
            ];
        }

        if (
            $result[
                'cutover_initialized'
            ]
            ?? false
        ) {
            $rows[] = [
                'Post-cutover reconciled',
                $this->yesNo(
                    (bool) (
                        $result[
                            'post_cutover_reconciled'
                        ]
                        ?? false
                    )
                ),
            ];

            if (
                array_key_exists(
                    'position_count_matches',
                    $result
                )
            ) {
                $rows[] = [
                    'Position count matches',
                    $this->yesNo(
                        (bool) $result[
                            'position_count_matches'
                        ]
                    ),
                ];
            }

            if (
                array_key_exists(
                    'account_totals_match',
                    $result
                )
            ) {
                $rows[] = [
                    'Account totals match',
                    $this->yesNo(
                        (bool) $result[
                            'account_totals_match'
                        ]
                    ),
                ];
            }

            if (
                array_key_exists(
                    'all_entries_balanced',
                    $result
                )
            ) {
                $rows[] = [
                    'Every opening entry balanced',
                    $this->yesNo(
                        (bool) $result[
                            'all_entries_balanced'
                        ]
                    ),
                ];
            }
        } else {
            $rows[] = [
                'Ready for controlled cutover',
                $this->yesNo(
                    (bool) (
                        $result[
                            'ready_for_cutover'
                        ]
                        ?? false
                    )
                ),
            ];
        }

        $this->table(
            [
                'Check',
                'Result',
            ],
            $rows
        );

        $blockers =
            $result['blockers']
            ?? [];

        if ($blockers !== []) {
            $this->newLine();

            $this->warn(
                'Blockers:'
            );

            foreach ($blockers as $blocker) {
                $this->line(
                    ' - '.$blocker
                );
            }
        }

        $this->newLine();

        if (! ($result['passed'] ?? false)) {
            $this->error(
                'ACCOUNTING CUTOVER SAFETY GATE FAILED.'
            );

            return self::FAILURE;
        }

        if (
            ($result['state'] ?? null)
            ===
            OpeningBalanceReadinessService::
                STATE_READY_FOR_CUTOVER
        ) {
            $this->info(
                'ACCOUNTING CUTOVER SAFETY GATE PASSED — READY FOR CONTROLLED CUTOVER.'
            );

            return self::SUCCESS;
        }

        $this->info(
            'ACCOUNTING CUTOVER SAFETY GATE PASSED — CUTOVER IS RECONCILED.'
        );

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function renderJson(
        array $result
    ): int {
        try {
            $this->line(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_THROW_ON_ERROR
                )
            );
        } catch (JsonException $exception) {
            $this->error(
                'Could not encode readiness result: '
                .$exception->getMessage()
            );

            return self::FAILURE;
        }

        return ($result['passed'] ?? false)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function yesNo(
        bool $value
    ): string {
        return $value
            ? 'YES'
            : 'NO';
    }
}
