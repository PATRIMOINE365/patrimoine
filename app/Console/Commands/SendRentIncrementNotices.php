<?php

namespace App\Console\Commands;

use App\Exceptions\PartyEmailSuppressedException;
use App\Models\RentIncrement;
use App\Services\Notifications\EmailDeliveryService;
use App\Services\RentIncrementService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use App\Console\Concerns\IteratesOrganisations;
use Illuminate\Console\Command;
use Throwable;

/**
 * Send advance notifications for scheduled rent increments.
 *
 * Patrimoine V1 rules:
 *
 * - a tenant must be notified three calendar months before an approved
 *   rent increment becomes effective;
 * - only scheduled increments are eligible;
 * - an increment is notified only once;
 * - if the exact three-month notification date was missed, the next
 *   successful daily run sends the outstanding notice;
 * - notices are never sent after the increment has already taken effect;
 * - a failed email attempt does not mark the increment as notified;
 * - one failed notification does not stop notices for other tenants.
 *
 * The optional --as-of option supports deterministic tests and controlled
 * administrative runs.
 */
#[Signature(
    'patrimoine:send-rent-increment-notices
    {--as-of= : Process rent increment notices as of YYYY-MM-DD}'
)]
#[Description(
    'Send tenant notices for rent increments becoming effective within three months'
)]
class SendRentIncrementNotices extends Command
{
    /**
     * Number of calendar months of advance notice provided to tenants.
     */
    private const NOTICE_MONTHS = 3;

    use IteratesOrganisations;

    /**
     * Execute the rent-increment notification run.
     *
     * V1.0.10 multi-tenancy: notices are sent once per active
     * organisation with that organisation's context and language bound.
     */
    public function handle(
        EmailDeliveryService $emailDelivery,
        RentIncrementService $rentIncrementService
    ): int {
        $asOf =
            $this->resolveAsOfDate();

        if ($asOf === null) {
            return self::FAILURE;
        }

        return $this->forEachOrganisation(
            fn (): int => $this->runNoticesForOrganisation(
                $emailDelivery,
                $rentIncrementService,
                $asOf
            )
        );
    }

    /**
     * Run one organisation's notice pass.
     */
    private function runNoticesForOrganisation(
        EmailDeliveryService $emailDelivery,
        RentIncrementService $rentIncrementService,
        Carbon $asOf
    ): int {
        $licensing = app(\App\Services\LicensingService::class);

        /*
         * V1.0.10 licensing: automated notices are a Professional
         * feature, and the monthly email allowance is a hard cap.
         * Skipped mail is NOT queued for later; unsent notices remain
         * eligible for a future run.
         */
        if (! $licensing->allows('automated_reminders')) {
            $this->line(
                '  Automated notices are not included in this plan; skipped.'
            );

            return self::SUCCESS;
        }

        $processed = 0;
        $eligible = 0;
        $sent = 0;
        $suppressed = 0;
        $failed = 0;

        /*
         * Query only records that can potentially require notification.
         *
         * Date-window eligibility is evaluated using Carbon below because
         * "three calendar months before" is a calendar rule rather than a
         * fixed number of days.
         */
        RentIncrement::query()
            ->where(
                'status',
                'scheduled'
            )
            ->whereNull(
                'notification_sent_at'
            )
            ->whereDate(
                'effective_date',
                '>',
                $asOf->toDateString()
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($increments) use (
                    $emailDelivery,
                    $rentIncrementService,
                    $asOf,
                    $licensing,
                    &$processed,
                    &$eligible,
                    &$sent,
                    &$suppressed,
                    &$failed
                ): void {
                    foreach ($increments as $increment) {
                        $processed++;

                        if (
                            ! $this->isNotificationDue(
                                $increment,
                                $asOf
                            )
                        ) {
                            continue;
                        }

                        $eligible++;

                        if (! $licensing->canSendAutomatedEmail()) {
                            $this->line(
                                'Monthly email allowance exhausted; remaining notices skipped.'
                            );

                            return;
                        }

                        try {
                            /*
                             * Email delivery must succeed before the
                             * notification timestamp is persisted.
                             *
                             * This ordering is important: SMTP or application
                             * failures leave the record eligible for the next
                             * scheduled run instead of falsely recording a
                             * successful notice.
                             */
                            $emailDelivery
                                ->sendRentIncrementNotice(
                                    $increment
                                );

                            $rentIncrementService
                                ->markNotificationSent(
                                    $increment
                                );

                            $sent++;

                            $this->line(
                                sprintf(
                                    'Rent increment #%d for Lease #%d: notice sent.',
                                    $increment->id,
                                    $increment->lease_id
                                )
                            );
                        } catch (PartyEmailSuppressedException) {
                            /*
                             * V1.0.29: the tenant is excluded from
                             * Patrimoine emails. This is a deliberate
                             * configuration, not a failure, so the run
                             * stays green and simply counts it.
                             *
                             * The increment is NOT marked as notified:
                             * if emails are switched back on before the
                             * effective date, the notice still goes out.
                             */
                            $suppressed++;
                        } catch (Throwable $exception) {
                            $failed++;

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
                'Rent increment notification run complete as of %s.',
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
                    'Scheduled increments processed',
                    $processed,
                ],
                [
                    'Notices due',
                    $eligible,
                ],
                [
                    'Notices sent',
                    $sent,
                ],
                [
                    'Skipped (emails switched off)',
                    $suppressed,
                ],
                [
                    'Failures',
                    $failed,
                ],
            ]
        );

        /*
         * A non-zero exit status allows cron/monitoring to detect that some
         * tenants were not successfully notified while preserving successful
         * notices sent during the same run.
         */
        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Determine whether the three-month notice is due as of the supplied date.
     *
     * Example:
     *
     * Effective date:    2027-08-15
     * Notification date: 2027-05-15
     *
     * A run on 2027-05-15 is eligible.
     * A run after 2027-05-15 remains eligible until the day before the
     * effective date if no successful notice has yet been recorded.
     */
    private function isNotificationDue(
        RentIncrement $increment,
        Carbon $asOf
    ): bool {
        $effectiveDate =
            $increment
                ->effective_date
                ->copy()
                ->startOfDay();

        /*
         * Once the effective date arrives, this is no longer an advance
         * notification. Applying the increment is handled by a separate
         * operational process.
         */
        if (
            $asOf->gte(
                $effectiveDate
            )
        ) {
            return false;
        }

        $notificationDate =
            $effectiveDate
                ->copy()
                ->subMonthsNoOverflow(
                    self::NOTICE_MONTHS
                )
                ->startOfDay();

        return $asOf->gte(
            $notificationDate
        );
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
             * createFromFormat may normalize invalid dates, therefore the
             * final representation is compared with the supplied value.
             */
            if (
                $date === false
                || $date->format(
                    'Y-m-d'
                ) !== $asOf
            ) {
                throw new \InvalidArgumentException;
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
