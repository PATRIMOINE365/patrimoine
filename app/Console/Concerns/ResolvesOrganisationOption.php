<?php

namespace App\Console\Concerns;

use App\Models\Organisation;

/**
 * Resolves the --organisation option for per-organisation maintenance
 * commands (the opening-balance suite).
 *
 * With exactly one organisation in the database the option may be
 * omitted; with several it is required, so an operator can never run an
 * accounting cutover against the wrong customer by accident.
 */
trait ResolvesOrganisationOption
{
    /**
     * Return the target organisation, or null after printing an error.
     */
    protected function resolveOrganisationOrFail(): ?Organisation
    {
        $option = trim((string) $this->option('organisation'));

        if ($option !== '') {
            $organisation = Organisation::query()->find((int) $option);

            if ($organisation === null) {
                $this->error(sprintf(
                    'Organisation #%s does not exist.',
                    $option
                ));

                return null;
            }

            return $organisation;
        }

        $count = Organisation::query()->count();

        if ($count === 1) {
            return Organisation::query()->first();
        }

        $this->error(
            $count === 0
                ? 'No organisation exists yet.'
                : 'Several organisations exist; pass --organisation=ID.'
        );

        return null;
    }
}
