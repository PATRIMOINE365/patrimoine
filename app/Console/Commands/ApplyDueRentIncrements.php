<?php

namespace App\Console\Commands;

use App\Models\RentIncrement;
use App\Services\RentIncrementService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Apply scheduled rent increments whose effective dates have arrived.
 *
 * Patrimoine V1 rules:
 *
 * - only explicitly scheduled increments may be applied;
 * - reaching an effective date never creates a new increment automatically;
 * - due scheduled increments update the Lease's current rent;
 * - application advances the Lease's next eligible increment date;
 * - already applied or cancelled increments are ignored;
 * - the operation is idempotent;
 * - one failed increment does not stop other due increments.
 *
 * The optional --as-of option supports deterministic tests and controlled
 * administrative processing.
 */
#[Signature(
    'patrimoine:apply-due-rent-increments
    {--as-of= : Apply rent increments due through YYYY-MM-DD}'
)]
#[Description(
    'Apply scheduled rent increments whose effective dates have arrived'
)]
class ApplyDueRentIncrements extends Command
{
    /**
     * Execute the automatic rent-increment application run.
     */
    public function handle(
        RentIncrementService $service
    ): int {
        $asOf =
            $this->resolveAsOfDate();

        if ($asOf === null) {
            return self::FAILURE;
        }

        $processed = 0;
        $applied = 0;
        $failed = 0;

        /*
         * Query only increments that are both approved and due.
         *
         * The service remains authoritative for contractual validation.
         * This query merely avoids loading records that clearly require no
         * action during the current run.
         */
        RentIncrement::query()
            ->where(
                'status',
                'scheduled'
            )
            ->whereDate(
                'effective_date',
                '<=',
                $asOf->toDateString()
            )
            ->orderBy(
                'id'
            )
            ->chunkById(
                100,
                function ($increments) use (
                    $service,
                    $asOf,
                    &$processed,
                    &$applied,
                    &$failed
                ): void {
                    foreach ($increments as $increment) {
                        $processed++;

                        try {
                            $service->apply(
                                $increment,
                                $asOf
                            );

                            $applied++;

                            $this->line(
                                sprintf(
                                    'Rent increment #%d for Lease #%d applied.',
                                    $increment->id,
                                    $increment->lease_id
                                )
                            );
                        } catch (Throwable $exception) {
                            $failed++;

                            /*
                             * Preserve portfolio-wide processing even when
                             * one increment requires manual intervention.
                             */
                            report(
                                $exception
                            );

                            $this->error(
                                sprintf(
                                    'Rent increment #%d for Lease #%d failed: %s',
                                    $increment->id,
                                    $increment->lease_id,
                                    $exception->getMessage()
                                )
                            );
                        }
                    }
                }
            );

        $this->newLine();

        $this->info(
            sprintf(
                'Rent increment application run complete through %s.',
                $asOf->toDateString()
            )
        );

        $this->table(
            [
                'Metric',
                'Count',
            ],
            [
                [
                    'Due increments processed',
                    $processed,
                ],
                [
                    'Increments applied',
                    $applied,
                ],
                [
                    'Failures',
                    $failed,
                ],
            ]
        );

        /*
         * Monitoring must be able to detect a partially completed run while
         * successful increments remain committed.
         */
        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Resolve the optional processing date.
     *
     * Without --as-of, today's application date is used.
     */
    private function resolveAsOfDate(): ?Carbon
    {
        $asOf =
            $this->option(
                'as-of'
            );

        if (
            $asOf === null
            || trim(
                (string) $asOf
            ) === ''
        ) {
            return now()
                ->startOfDay();
        }

        try {
            $date =
                Carbon::createFromFormat(
                    '!Y-m-d',
                    (string) $asOf
                );

            /*
             * Reject normalized invalid dates such as 2026-02-30.
             */
            if (
                $date === false
                || $date->format(
                    'Y-m-d'
                ) !== $asOf
            ) {
                throw new \InvalidArgumentException();
            }

            return $date
                ->startOfDay();
        } catch (Throwable) {
            $this->error(
                'The --as-of option must be a valid date in YYYY-MM-DD format.'
            );

            return null;
        }
    }
}
