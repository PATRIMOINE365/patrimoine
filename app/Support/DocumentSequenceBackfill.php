<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.36: start every counter where that organisation's numbering had
 * already reached.
 *
 * This is the half of the change that touches real books. Restarting each
 * series at one would have been simpler and would not even have collided,
 * because the new numbers carry a year and the old ones do not — but a
 * reference that has been on a document sent to somebody must never come
 * back, in any shape.
 *
 * So each counter is seeded one past the highest number that organisation
 * has already issued in that series. From next January each series
 * restarts at one on its own, which is what a year-qualified series is
 * for.
 *
 * Idempotent: insert-or-ignore, then raise. Running it twice cannot lower
 * a counter, and it cannot lower the journal's counter either — that one
 * is copied from journal_sequences, where it is authoritative, rather
 * than derived from the entries.
 */
class DocumentSequenceBackfill
{
    /**
     * Where each stored series has already reached.
     *
     * The digits at the end are what is read, so a flat INV-000123 and a
     * year-qualified WDR-2026-000123 are both understood.
     *
     * @var array<string, array{table: string, column: string}>
     */
    public const SOURCES = [
        'INV' => ['table' => 'invoices', 'column' => 'invoice_number'],
        'EXP' => ['table' => 'invoices', 'column' => 'invoice_number'],
        'SDD' => ['table' => 'invoices', 'column' => 'invoice_number'],
        'OEB' => ['table' => 'owner_expense_bills', 'column' => 'bill_number'],
        'WDR' => ['table' => 'withdrawal_receipts', 'column' => 'receipt_number'],
        'ADV' => ['table' => 'adjustment_vouchers', 'column' => 'voucher_number'],
        'SDV' => ['table' => 'security_deposit_settlements', 'column' => 'refund_voucher_number'],
        'OTR' => ['table' => 'owner_transactions', 'column' => 'reference'],
        'TRF' => ['table' => 'tenant_fund_transactions', 'column' => 'reference'],
        'TEX' => ['table' => 'tenant_fund_transactions', 'column' => 'reference'],
    ];

    public static function run(?int $year = null): void
    {
        $year ??= (int) now()->year;

        self::carryTheJournalAcross();

        foreach (self::SOURCES as $series => $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            foreach (self::highestPerOrganisation($series, $source) as $organisation => $value) {
                self::raiseTo($organisation, $series, $year, $value + 1);
            }
        }
    }

    /**
     * The journal's counter is authoritative where it stands: it is the
     * next number, not the highest issued, and a posting that failed may
     * legitimately have consumed one. Copy it rather than deriving it.
     */
    private static function carryTheJournalAcross(): void
    {
        if (! Schema::hasTable('journal_sequences')) {
            return;
        }

        foreach (DB::table('journal_sequences')->get() as $row) {
            self::raiseTo(
                (int) $row->organisation_id,
                'JRN',
                (int) $row->year,
                (int) $row->next_number
            );
        }
    }

    /**
     * @param  array{table: string, column: string}  $source
     * @return array<int, int>
     */
    private static function highestPerOrganisation(string $series, array $source): array
    {
        $rows = DB::table($source['table'])
            ->select('organisation_id', $source['column'].' as number')
            ->whereNotNull($source['column'])
            ->where($source['column'], 'like', $series.'-%')
            ->get();

        $highest = [];

        foreach ($rows as $row) {
            if (! preg_match('/(\d+)$/', (string) $row->number, $matches)) {
                continue;
            }

            $organisation = (int) $row->organisation_id;

            $highest[$organisation] = max(
                $highest[$organisation] ?? 0,
                (int) $matches[1]
            );
        }

        return $highest;
    }

    private static function raiseTo(
        int $organisation,
        string $series,
        int $year,
        int $next
    ): void {
        DB::table('document_sequences')->insertOrIgnore([
            'organisation_id' => $organisation,
            'series' => $series,
            'year' => $year,
            'next_number' => $next,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('document_sequences')
            ->where('organisation_id', $organisation)
            ->where('series', $series)
            ->where('year', $year)
            ->where('next_number', '<', $next)
            ->update([
                'next_number' => $next,
                'updated_at' => now(),
            ]);
    }
}
