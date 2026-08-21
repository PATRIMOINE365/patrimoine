<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Services\ActivityLogService;
use App\Services\LeaseHistory\LeaseFinancialHistoryExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeaseFinancialHistoryExportController extends Controller
{
    public function pdf(
        Request $request,
        Lease $lease,
        LeaseFinancialHistoryExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        $filename =
            "lease-financial-history-{$lease->id}.pdf";

        $contents =
            $exports->pdf(
                $lease
            );

        $this->recordExport(
            $activityLog,
            $request,
            $lease,
            'pdf',
            $filename
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'attachment; filename="'
                    .$filename
                    .'"',
            ]
        );
    }

    public function csv(
        Request $request,
        Lease $lease,
        LeaseFinancialHistoryExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        $filename =
            "lease-financial-history-{$lease->id}.csv";

        $contents =
            $exports->csv(
                $lease
            );

        $this->recordExport(
            $activityLog,
            $request,
            $lease,
            'csv',
            $filename
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Content-Disposition' => 'attachment; filename="'
                    .$filename
                    .'"',
            ]
        );
    }

    public function xlsx(
        Request $request,
        Lease $lease,
        LeaseFinancialHistoryExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        $filename =
            "lease-financial-history-{$lease->id}.xlsx";

        $contents =
            $exports->xlsx(
                $lease
            );

        $this->recordExport(
            $activityLog,
            $request,
            $lease,
            'xlsx',
            $filename
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' => 'attachment; filename="'
                    .$filename
                    .'"',
            ]
        );
    }

    private function recordExport(
        ActivityLogService $activityLog,
        Request $request,
        Lease $lease,
        string $format,
        string $filename
    ): void {
        $activityLog->record(
            action: 'report.exported',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel: 'Lease #'.$lease->id,
            metadata: [
                'report_type' => 'lease_financial_history',

                'format' => $format,

                'filename' => $filename,
            ],
        );
    }
}
