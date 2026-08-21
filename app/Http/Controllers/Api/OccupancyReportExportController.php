<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OccupancyReportRequest;
use App\Services\ActivityLogService;
use App\Services\Reports\OccupancyReportExportService;
use Illuminate\Http\Response;

class OccupancyReportExportController extends Controller
{
    public function pdf(
        OccupancyReportRequest $request,
        OccupancyReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        return $this->export(
            $request,
            $exports,
            $activityLog,
            'pdf'
        );
    }

    public function csv(
        OccupancyReportRequest $request,
        OccupancyReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        return $this->export(
            $request,
            $exports,
            $activityLog,
            'csv'
        );
    }

    public function xlsx(
        OccupancyReportRequest $request,
        OccupancyReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        return $this->export(
            $request,
            $exports,
            $activityLog,
            'xlsx'
        );
    }

    private function export(
        OccupancyReportRequest $request,
        OccupancyReportExportService $exports,
        ActivityLogService $activityLog,
        string $format
    ): Response {
        $filters =
            $request->filters();

        $filename =
            'occupancy-report-'
            .now()->format('Y-m-d')
            .'.'
            .$format;

        $contents =
            match ($format) {
                'pdf' => $exports->pdf(
                    $filters
                ),

                'csv' => $exports->csv(
                    $filters
                ),

                'xlsx' => $exports->xlsx(
                    $filters
                ),
            };

        $activityLog->record(
            action: 'report.exported',
            request: $request,
            entityType: 'report',
            entityId: null,
            entityLabel: __('reports.occupancy_report'),
            metadata: [
                'report_type' => 'occupancy',

                'format' => $format,

                'filename' => $filename,

                'filters' => $filters,
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => match ($format) {
                    'pdf' => 'application/pdf',

                    'csv' => 'text/csv; charset=UTF-8',

                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },

                'Content-Disposition' => 'attachment; filename="'
                    .$filename
                    .'"',
            ]
        );
    }
}
