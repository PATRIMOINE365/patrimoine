<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\Reports\FundsReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FundsReportExportController extends Controller
{
    public function pdf(
        Request $request,
        FundsReportExportService $exports,
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
        Request $request,
        FundsReportExportService $exports,
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
        Request $request,
        FundsReportExportService $exports,
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
        Request $request,
        FundsReportExportService $exports,
        ActivityLogService $activityLog,
        string $format
    ): Response {
        $filename =
            'funds-report-'
            .now()->format('Y-m-d')
            .'.'
            .$format;

        $contents =
            match ($format) {
                'pdf' => $exports->pdf(),

                'csv' => $exports->csv(),

                'xlsx' => $exports->xlsx(),
            };

        $activityLog->record(
            action: 'report.exported',
            request: $request,
            entityType: 'report',
            entityId: null,
            entityLabel: __('reports.funds_report'),
            metadata: [
                'report_type' => 'funds',

                'format' => $format,

                'filename' => $filename,
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
