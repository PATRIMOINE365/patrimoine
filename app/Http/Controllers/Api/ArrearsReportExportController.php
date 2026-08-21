<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArrearsReportRequest;
use App\Services\ActivityLogService;
use App\Services\Reports\ArrearsReportExportService;
use Illuminate\Http\Response;

class ArrearsReportExportController extends Controller
{
    public function pdf(
        ArrearsReportRequest $request,
        ArrearsReportExportService $exports,
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
        ArrearsReportRequest $request,
        ArrearsReportExportService $exports,
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
        ArrearsReportRequest $request,
        ArrearsReportExportService $exports,
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
        ArrearsReportRequest $request,
        ArrearsReportExportService $exports,
        ActivityLogService $activityLog,
        string $format
    ): Response {
        $filters =
            $request->filters();

        $filename =
            'arrears-report-'
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
            entityLabel: __('reports.arrears_report'),
            metadata: [
                'report_type' => 'arrears',

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
