<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogExportService;
use App\Services\ActivityLogQueryService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrator-only Activity Log PDF/CSV export endpoints.
 *
 * Unlike passive Activity Log browsing, an export/download is a meaningful
 * human action and therefore records one Activity Log event after the
 * exported historical result set has been generated.
 *
 * The matching rows are resolved before the export event is recorded so the
 * export never recursively contains the event describing that same export.
 */
class ActivityLogExportController extends Controller
{
    /**
     * Export every Activity Log event matching the active filters as PDF.
     */
    public function pdf(
        Request $request,
        ActivityLogQueryService $activityLogQuery,
        ActivityLogExportService $export,
        ActivityLogService $activityLog
    ): Response {
        $validated =
            $activityLogQuery
                ->validatedFilters(
                    $request,
                    includePagination: false
                );

        $filters =
            $activityLogQuery
                ->exportFilterSnapshot(
                    $validated
                );

        /*
         * Resolve the matching historical rows before recording the export.
         *
         * This preserves one meaningful event for the export while ensuring
         * that event does not appear recursively inside the file being
         * generated.
         */
        $events =
            $activityLogQuery
                ->query(
                    $validated
                )
                ->get();

        $contents =
            $export->pdf(
                $events,
                $filters
            );

        $activityLog->record(
            action: 'activity_log.exported',
            request: $request,
            entityType: 'activity_log',
            entityLabel: 'Activity Log',
            metadata: [
                'format' => 'pdf',
                'filters' => $filters,
                'record_count' => $events->count(),
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'attachment; filename="activity-log.pdf"',
            ]
        );
    }

    /**
     * Export every Activity Log event matching the active filters as CSV.
     */
    public function csv(
        Request $request,
        ActivityLogQueryService $activityLogQuery,
        ActivityLogExportService $export,
        ActivityLogService $activityLog
    ): Response {
        $validated =
            $activityLogQuery
                ->validatedFilters(
                    $request,
                    includePagination: false
                );

        $filters =
            $activityLogQuery
                ->exportFilterSnapshot(
                    $validated
                );

        $events =
            $activityLogQuery
                ->query(
                    $validated
                )
                ->get();

        $contents =
            $export->csv(
                $events
            );

        $activityLog->record(
            action: 'activity_log.exported',
            request: $request,
            entityType: 'activity_log',
            entityLabel: 'Activity Log',
            metadata: [
                'format' => 'csv',
                'filters' => $filters,
                'record_count' => $events->count(),
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Content-Disposition' => 'attachment; filename="activity-log.csv"',
            ]
        );
    }

    /**
     * V1.0.7: export every Activity Log event matching the active filters
     * as an XLSX workbook — completing the PDF/XLSX/CSV download rule.
     */
    public function xlsx(
        Request $request,
        ActivityLogQueryService $activityLogQuery,
        ActivityLogExportService $export,
        ActivityLogService $activityLog
    ): Response {
        $validated =
            $activityLogQuery
                ->validatedFilters(
                    $request,
                    includePagination: false
                );

        $filters =
            $activityLogQuery
                ->exportFilterSnapshot(
                    $validated
                );

        $events =
            $activityLogQuery
                ->query(
                    $validated
                )
                ->get();

        $contents =
            $export->xlsx(
                $events
            );

        $activityLog->record(
            action: 'activity_log.exported',
            request: $request,
            entityType: 'activity_log',
            entityLabel: 'Activity Log',
            metadata: [
                'format' => 'xlsx',
                'filters' => $filters,
                'record_count' => $events->count(),
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' => 'attachment; filename="activity-log.xlsx"',
            ]
        );
    }
}
