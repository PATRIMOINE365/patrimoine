<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\FinancialJournalExportService;
use App\Services\FinancialJournalQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FinancialJournalExportController extends Controller
{
    /*
     * There is deliberately no pdf() here.
     *
     * dompdf builds a Frame per cell and holds the whole document in
     * memory, and this rendered EVERY matching entry: 276 entries needed
     * 456 MB against the 128 MB the live box allows, and it is
     * superlinear, so it worsens as a portfolio grows. A one-month filter
     * did not rescue it either - a single month of a twelve-unit
     * portfolio is 168 entries.
     *
     * CSV and XLSX stream, carry the same columns, and are what an
     * accountant loads anyway.
     */

    public function csv(
        Request $request,
        FinancialJournalQueryService $query,
        FinancialJournalExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        return $this->export(
            $request,
            $query,
            $exports,
            $activityLog,
            'csv'
        );
    }

    public function xlsx(
        Request $request,
        FinancialJournalQueryService $query,
        FinancialJournalExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        return $this->export(
            $request,
            $query,
            $exports,
            $activityLog,
            'xlsx'
        );
    }

    private function export(
        Request $request,
        FinancialJournalQueryService $query,
        FinancialJournalExportService $exports,
        ActivityLogService $activityLog,
        string $format
    ): Response {
        $validated =
            $query->validatedFilters(
                $request,
                includePagination: false
            );

        $filters =
            $query->exportFilterSnapshot(
                $validated
            );

        $entries =
            $query
                ->query(
                    $validated
                )
                ->with([
                    'lines',
                    'reversedEntry:id,journal_number',
                    'reversalEntry:id,journal_number',
                ])
                ->get();

        $contents =
            match ($format) {
                'pdf' =>
                    $exports->pdf(
                        $entries,
                        $filters
                    ),

                'csv' =>
                    $exports->csv(
                        $entries
                    ),

                'xlsx' =>
                    $exports->xlsx(
                        $entries
                    ),
            };

        $filename =
            'financial-journal-'
            .now()->format(
                'Y-m-d'
            )
            .'.'
            .$format;

        $activityLog->record(
            action: 'report.exported',
            request: $request,
            entityType: 'financial_journal',
            entityId: null,
            entityLabel: __(
                'financial_journal.title'
            ),
            metadata: [
                'report_type' =>
                    'financial_journal',

                'format' =>
                    $format,

                'filename' =>
                    $filename,

                'filters' =>
                    $filters,

                'record_count' =>
                    $entries->count(),
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    match ($format) {
                        'pdf' =>
                            'application/pdf',

                        'csv' =>
                            'text/csv; charset=UTF-8',

                        'xlsx' =>
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    },

                'Content-Disposition' =>
                    'attachment; filename="'
                    .$filename
                    .'"',
            ]
        );
    }
}
