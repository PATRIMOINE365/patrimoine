<?php

namespace App\Services\Accounting;

use App\Models\JournalSequence;
use Illuminate\Support\Facades\DB;

/**
 * Allocates permanent annual Financial Journal references.
 *
 * Numbering format:
 *
 *     JRN-2026-000001
 *
 * The sequence restarts each calendar year.
 *
 * Allocation must occur inside the same database transaction that creates the
 * Journal entry so a failed posting cannot consume a Journal number.
 */
class JournalNumberService
{
    public function next(int $year): string
    {
        /*
         * insertOrIgnore handles the first posting of a new year without
         * requiring application-level existence checks.
         *
         * V1.1.0 multi-tenancy: each organisation runs its own annual
         * sequence — the raw insert stamps the bound organisation and
         * the JournalSequence read below is organisation-scoped.
         */
        DB::table('journal_sequences')->insertOrIgnore([
            'organisation_id' => \App\Support\OrganisationContext::id(),
            'year' => $year,
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Lock the annual sequence row until the surrounding posting
         * transaction commits.
         */
        $sequence = JournalSequence::query()
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail();

        $number = $sequence->next_number;

        $sequence->next_number = $number + 1;

        $sequence->save();

        return sprintf(
            'JRN-%04d-%06d',
            $year,
            $number
        );
    }
}
