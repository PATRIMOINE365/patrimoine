<?php

namespace App\Console\Concerns;

use App\Models\Organisation;
use App\Support\OrganisationContext;
use Closure;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * Runs a console workload once per active organisation, with the
 * organisation context bound for the duration of each run.
 *
 * V1.0.10 multi-tenancy: scheduled jobs (billing, reminders, notices,
 * increments) are platform-wide crons but tenant-scoped workloads. The
 * binding guarantees every query and every created row inside the
 * callback belongs to exactly one organisation, and each organisation's
 * configured language is applied for any mail generated during its run.
 *
 * One organisation's failure never blocks the others: the callback's
 * exception is reported and iteration continues.
 */
trait IteratesOrganisations
{
    /**
     * Execute the callback once per active organisation.
     *
     * The callback receives the Organisation and must return a command
     * exit code. The worst exit code across organisations is returned.
     *
     * @param  Closure(Organisation): int  $callback
     */
    protected function forEachOrganisation(Closure $callback): int
    {
        $exitCode = self::SUCCESS;

        $organisations = Organisation::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($organisations as $organisation) {
            $this->line(sprintf(
                'Organisation #%d — %s',
                $organisation->id,
                $organisation->name
            ));

            try {
                $organisationExit = OrganisationContext::runAs(
                    (int) $organisation->id,
                    function () use ($callback, $organisation): int {
                        /*
                         * Mail and documents generated during this
                         * organisation's run must use its configured
                         * language.
                         */
                        app(
                            \App\Services\ApplicationLocaleService::class
                        )->applyLanguage();

                        return $callback($organisation);
                    }
                );

                $exitCode = max($exitCode, $organisationExit);
            } catch (Throwable $exception) {
                report($exception);

                $this->error(sprintf(
                    'Organisation #%d failed: %s',
                    $organisation->id,
                    $exception->getMessage()
                ));

                $exitCode = self::FAILURE;
            } finally {
                /*
                 * Restore the platform default language between
                 * organisations.
                 */
                App::setLocale(
                    (string) config('app.locale', 'en')
                );
            }
        }

        return $exitCode;
    }
}
