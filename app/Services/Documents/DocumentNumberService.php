<?php

namespace App\Services\Documents;

use App\Models\DocumentSequence;
use App\Support\OrganisationContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * V1.0.36: the one place a document number comes from.
 *
 * Every series used to find its next number by reading the highest one
 * already issued. Three things were wrong with that, and only the first
 * is the one anybody thinks of:
 *
 *   - two requests read the same highest number and both return it;
 *   - "highest" was decided by sorting the number as text, which holds
 *     only while every number is exactly six digits wide;
 *   - deleting the newest document let the next one REUSE its number.
 *
 * The last is the worst. A crash is loud; two documents quietly carrying
 * the same reference is not, and an accounting series cannot have it.
 *
 * A counter row per organisation per year answers all three: the number
 * is taken from the counter rather than from the documents, so nothing
 * that happens to a document afterwards can affect what comes next.
 *
 * The row is taken under `lockForUpdate` inside a transaction, so a
 * second request waits rather than reading the same value. Callers
 * already inside a transaction join it; callers who are not get one of
 * their own.
 */
class DocumentNumberService
{
    /**
     * Every series Patrimoine issues, and what the prefix means.
     *
     * Kept here rather than passed as a string so a typo is a failure
     * rather than a new series nobody knows exists.
     *
     * @var array<string, string>
     */
    public const SERIES = [
        'INV' => 'Rent invoice',
        'EXP' => 'Tenant expense invoice',
        'SDD' => 'Security deposit debt invoice',
        'OEB' => 'Owner expense bill',
        'RCT' => 'Payment receipt',
        'WDR' => 'Tenant fund withdrawal receipt',
        'ADV' => 'Adjustment voucher',
        'SDV' => 'Security deposit settlement voucher',
        'OTR' => 'Owner reserve transfer',
        'TRF' => 'Tenant fund transfer',
        'TEX' => 'Tenant fund expense',
        'JRN' => 'Financial journal entry',
    ];

    /**
     * The next number in a series, as it appears on the document.
     *
     * PREFIX-YYYY-NNNNNN. The year is part of the number rather than
     * only part of the key, so a reference read down a telephone says
     * which year's books it belongs to without anybody looking it up.
     */
    public function next(string $series, ?int $year = null): string
    {
        return sprintf(
            '%s-%04d-%06d',
            $series,
            $year ??= (int) now()->year,
            $this->allocate($series, $year)
        );
    }

    /**
     * Take the next number out of the counter and leave it advanced.
     */
    public function allocate(string $series, ?int $year = null): int
    {
        if (! array_key_exists($series, self::SERIES)) {
            throw new InvalidArgumentException(
                'Unknown document series: '.$series
            );
        }

        $year ??= (int) now()->year;

        return DB::transaction(
            function () use ($series, $year): int {
                /*
                 * insertOrIgnore rather than firstOrCreate: two requests
                 * arriving for a year nobody has used yet must not both
                 * try to create the row and have the second one fail.
                 */
                DB::table('document_sequences')->insertOrIgnore([
                    'organisation_id' => OrganisationContext::id(),
                    'series' => $series,
                    'year' => $year,
                    'next_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = DocumentSequence::query()
                    ->where('series', $series)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->firstOrFail();

                $number = (int) $sequence->next_number;

                $sequence->next_number = $number + 1;
                $sequence->save();

                return $number;
            }
        );
    }
}
