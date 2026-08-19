<?php

namespace App\Console\Commands;

use App\Services\Accounting\OpeningBalanceCutoverService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class OpeningBalanceCutoverCommand extends Command
{
    protected $signature =
        'patrimoine:opening-balance-cutover
        {--date= : Required V1.0.5 deployment/cutover date in YYYY-MM-DD}
        {--yes : Execute without interactive confirmation}';

    protected $description =
        'Post the reconciled V1.0.5 opening Financial Journal position';

    public function handle(
        OpeningBalanceCutoverService $cutover
    ): int {
        $date =
            trim(
                (string)
                $this->option(
                    'date'
                )
            );

        if ($date === '') {
            $this->error(
                'The --date=YYYY-MM-DD option is required. No cutover date will be assumed.'
            );

            return self::FAILURE;
        }

        try {
            $cutoverDate =
                CarbonImmutable::createFromFormat(
                    '!Y-m-d',
                    $date
                );
        } catch (Throwable) {
            $this->error(
                'Invalid cutover date. Use YYYY-MM-DD.'
            );

            return self::FAILURE;
        }

        if (
            $cutoverDate
                ->format('Y-m-d')
            !== $date
        ) {
            $this->error(
                'Invalid cutover date. Use YYYY-MM-DD.'
            );

            return self::FAILURE;
        }

        $this->warn(
            sprintf(
                'This will post the permanent V1.0.5 Financial Journal opening position dated %s.',
                $date
            )
        );

        $this->line(
            'The operation is atomic and cannot be partially committed.'
        );

        if (
            ! $this->option(
                'yes'
            )
            &&
            ! $this->confirm(
                'Continue with V1.0.5 opening-balance cutover?',
                false
            )
        ) {
            $this->warn(
                'Cutover cancelled.'
            );

            return self::FAILURE;
        }

        try {
            $result =
                $cutover->execute(
                    $cutoverDate
                );
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->info(
            'V1.0.5 opening-balance cutover completed.'
        );

        $this->table(
            [
                'Cutover',
                'Date',
                'Positions',
                'Journal Entries',
                'Status',
            ],
            [
                [
                    $result
                        ->cutover_key,

                    $result
                        ->cutover_date
                        ->toDateString(),

                    $result
                        ->position_count,

                    $result
                        ->journal_entry_count,

                    $result
                        ->status,
                ],
            ]
        );

        return self::SUCCESS;
    }
}
