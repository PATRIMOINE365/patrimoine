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
    public function pdf(
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
            'pdf'
        );
    }

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
