<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Services\InvoiceGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Generate all rent Invoices currently due for eligible Leases.
 *
 * The command is intentionally idempotent because
 * InvoiceGenerationService::generateDueInvoices() skips billing periods
 * that already have an Invoice.
 *
 * One Lease failure does not abort the entire billing run. Failures are
 * reported individually so other valid Leases can still be processed.
 */
#[Signature('patrimoine:generate-due-invoices {--through= : Generate invoices due through YYYY-MM-DD}')]
#[Description('Generate all missing rent invoices for active and notice leases')]
class GenerateDueInvoices extends Command
{
    /**
     * Execute the automated billing run.
     */
    public function handle(
        InvoiceGenerationService $service
    ): int {
        $throughDate = $this->resolveThroughDate();

        if ($throughDate === null) {
            return self::FAILURE;
        }

        $generatedCount = 0;
        $processedLeaseCount = 0;
        $failedLeaseCount = 0;

        /*
         * Process only Leases that may legitimately produce invoices.
         *
         * chunkById keeps memory usage predictable when Patrimoine grows
         * beyond the initial customer and property portfolio.
         */
        Lease::query()
            ->whereIn('status', [
                'active',
                'notice',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($leases) use (
                    $service,
                    $throughDate,
                    &$generatedCount,
                    &$processedLeaseCount,
                    &$failedLeaseCount
                ): void {
                    foreach ($leases as $lease) {
                        $processedLeaseCount++;

                        try {
                            $generated = $service
                                ->generateDueInvoices(
                                    $lease,
                                    $throughDate
                                );

                            $count = count($generated);

                            $generatedCount += $count;

                            if ($count > 0) {
                                $this->line(
                                    sprintf(
                                        'Lease #%d: generated %d invoice(s).',
                                        $lease->id,
                                        $count
                                    )
                                );
                            }
                        } catch (Throwable $exception) {
                            $failedLeaseCount++;

                            /*
                             * Continue with the remaining Leases instead of
                             * allowing one malformed Lease to halt billing
                             * for the entire property portfolio.
                             */
                            report($exception);

                            $this->error(
                                sprintf(
                                    'Lease #%d failed: %s',
                                    $lease->id,
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
                'Billing run complete through %s.',
                $throughDate->toDateString()
            )
        );

        $this->table(
            [
                'Metric',
                'Count',
            ],
            [
                [
                    'Leases processed',
                    $processedLeaseCount,
                ],
                [
                    'Invoices generated',
                    $generatedCount,
                ],
                [
                    'Leases failed',
                    $failedLeaseCount,
                ],
            ]
        );

        /*
         * Return failure when any Lease could not be processed. This allows
         * cron/monitoring systems to detect an incomplete billing run while
         * still preserving successfully generated invoices for other Leases.
         */
        return $failedLeaseCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Resolve the optional billing cutoff date.
     *
     * Without --through, today's application date is used.
     */
    private function resolveThroughDate(): ?Carbon
    {
        $through = $this->option('through');

        if ($through === null || trim((string) $through) === '') {
            return now()->startOfDay();
        }

        try {
            $date = Carbon::createFromFormat(
                'Y-m-d',
                (string) $through
            );

            /*
             * createFromFormat can normalize invalid dates in some cases,
             * so compare the final representation with the supplied value.
             */
            if (
                $date === false
                || $date->format('Y-m-d') !== $through
            ) {
                throw new \InvalidArgumentException;
            }

            return $date->startOfDay();
        } catch (Throwable) {
            $this->error(
                'The --through option must be a valid date in YYYY-MM-DD format.'
            );

            return null;
        }
    }
}
