<?php

namespace App\Services\Accounting;

use App\Services\Documents\DocumentNumberService;

/**
 * Allocates permanent annual Financial Journal references.
 *
 * Numbering format:
 *
 *     JRN-2026-000001
 *
 * The sequence restarts each calendar year.
 *
 * Allocation must occur inside the same database transaction that creates
 * the Journal entry so a failed posting cannot consume a Journal number.
 *
 * V1.0.36: this service invented the per-organisation, per-year counter
 * that the other nine series lacked, and it was the only one that got
 * numbering right. That counter has been generalised into
 * DocumentNumberService and every series now shares it, so the journal
 * delegates rather than keeping a second copy of the mechanism it
 * originally proved. The numbers it produces are unchanged, and the
 * migration carried journal_sequences across before anything read the
 * new table.
 */
class JournalNumberService
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    public function next(int $year): string
    {
        return $this->numbers->next('JRN', $year);
    }
}
