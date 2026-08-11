<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Notifications\EmailDeliveryService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Send rent reminders for unpaid and partially paid Invoices.
 *
 * Patrimoine V1 reminder rules:
 *
 * - only issued/partial Invoices with an outstanding amount are eligible;
 * - reminders may be sent for invoices due today or overdue;
 * - one Invoice failure does not abort the entire reminder run;
 * - optional --as-of allows deterministic testing and administrative runs.
 */
#[Signature('patrimoine:send-rent-reminders {--as-of= : Process reminders as of YYYY-MM-DD}')]
#[Description('Send rent reminders for due and overdue invoices')]
class SendRentReminders extends Command
{
    /**
     * Execute the reminder run.
     */
    public function handle(
        EmailDeliveryService $service
    ): int {
        $asOf = $this->resolveAsOfDate();

        if ($asOf === null) {
            return self::FAILURE;
        }

        $processed = 0;
        $sent = 0;
        $failed = 0;

        Invoice::query()
            ->whereIn('status', [
                'issued',
                'partial',
            ])
            ->whereDate(
                'due_date',
                '<=',
                $asOf->toDateString()
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($invoices) use (
                    $service,
                    &$processed,
                    &$sent,
                    &$failed
                ): void {
                    foreach ($invoices as $invoice) {
                        $processed++;

                        /*
                         * paidAmount()/outstandingAmount() are derived values.
                         * This prevents reminders for fully settled invoices
                         * even if their persisted status was stale.
                         */
                        if ($invoice->outstandingAmount() <= 0) {
                            continue;
                        }

                        try {
                            $service->sendRentReminder($invoice);

                            $sent++;

                            $this->line(
                                sprintf(
                                    'Invoice %s: reminder sent.',
                                    $invoice->invoice_number
                                )
                            );
                        } catch (Throwable $exception) {
                            $failed++;

                            report($exception);

                            $this->error(
                                sprintf(
                                    'Invoice %s failed: %s',
                                    $invoice->invoice_number,
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
                'Rent reminder run complete as of %s.',
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
                    'Invoices processed',
                    $processed,
                ],
                [
                    'Reminders sent',
                    $sent,
                ],
                [
                    'Failures',
                    $failed,
                ],
            ]
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Resolve optional reminder cutoff date.
     */
    private function resolveAsOfDate(): ?Carbon
    {
        $asOf = $this->option('as-of');

        if ($asOf === null || trim((string) $asOf) === '') {
            return now()->startOfDay();
        }

        try {
            $date = Carbon::createFromFormat(
                'Y-m-d',
                (string) $asOf
            );

            if (
                $date === false
                || $date->format('Y-m-d') !== $asOf
            ) {
                throw new \InvalidArgumentException();
            }

            return $date->startOfDay();
        } catch (Throwable) {
            $this->error(
                'The --as-of option must be a valid date in YYYY-MM-DD format.'
            );

            return null;
        }
    }
}
