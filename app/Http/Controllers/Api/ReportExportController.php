<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Party;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\Reports\BuildingReportService;
use App\Services\Reports\Exports\ReportExportService;
use App\Services\Reports\ManagingOrganisationReportService;
use App\Services\Reports\OwnerReportService;
use App\Services\Reports\TenantStatementService;
use App\Services\Reports\UnitReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * PDF and CSV export endpoints for Patrimoine formal reports.
 *
 * All accounting values originate from the same report services used
 * by the JSON API. This controller only chooses the output format.
 */
class ReportExportController extends Controller
{
    public function ownerPdf(
        Request $request,
        Party $party,
        OwnerReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->pdf(
            __('reports.owner_report'),
            $reports->generate(
                $party,
                $from,
                $to
            )
        );

        $filename =
            "owner-report-{$party->id}.pdf";

        $this->recordExport(
            $activityLog,
            $request,
            'owner',
            'pdf',
            $filename,
            'party',
            $party->id,
            $this->partyLabel($party)
        );

        return $this->pdfResponse(
            $contents,
            $filename
        );
    }

    public function ownerCsv(
        Request $request,
        Party $party,
        OwnerReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->csv(
            $reports->generate(
                $party,
                $from,
                $to
            )
        );

        $filename =
            "owner-report-{$party->id}.csv";

        $this->recordExport(
            $activityLog,
            $request,
            'owner',
            'csv',
            $filename,
            'party',
            $party->id,
            $this->partyLabel($party)
        );

        return $this->csvResponse(
            $contents,
            $filename
        );
    }

    public function buildingPdf(
        Request $request,
        Building $building,
        BuildingReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->pdf(
            __('reports.building_report'),
            $reports->generate(
                $building,
                $from,
                $to
            )
        );

        $filename =
            "building-report-{$building->id}.pdf";

        $this->recordExport(
            $activityLog,
            $request,
            'building',
            'pdf',
            $filename,
            'building',
            $building->id,
            $building->name
        );

        return $this->pdfResponse(
            $contents,
            $filename
        );
    }

    public function buildingCsv(
        Request $request,
        Building $building,
        BuildingReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->csv(
            $reports->generate(
                $building,
                $from,
                $to
            )
        );

        $filename =
            "building-report-{$building->id}.csv";

        $this->recordExport(
            $activityLog,
            $request,
            'building',
            'csv',
            $filename,
            'building',
            $building->id,
            $building->name
        );

        return $this->csvResponse(
            $contents,
            $filename
        );
    }

    public function unitPdf(
        Request $request,
        Unit $unit,
        UnitReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->pdf(
            __('reports.unit_report'),
            $reports->generate(
                $unit,
                $from,
                $to
            )
        );

        $filename =
            "unit-report-{$unit->id}.pdf";

        $this->recordExport(
            $activityLog,
            $request,
            'unit',
            'pdf',
            $filename,
            'unit',
            $unit->id,
            $unit->name
        );

        return $this->pdfResponse(
            $contents,
            $filename
        );
    }

    public function unitCsv(
        Request $request,
        Unit $unit,
        UnitReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->csv(
            $reports->generate(
                $unit,
                $from,
                $to
            )
        );

        $filename =
            "unit-report-{$unit->id}.csv";

        $this->recordExport(
            $activityLog,
            $request,
            'unit',
            'csv',
            $filename,
            'unit',
            $unit->id,
            $unit->name
        );

        return $this->csvResponse(
            $contents,
            $filename
        );
    }

    public function tenantPdf(
        Request $request,
        Party $party,
        TenantStatementService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->pdf(
            __('reports.tenant_statement'),
            $reports->generate(
                $party,
                $from,
                $to
            )
        );

        $filename =
            "tenant-statement-{$party->id}.pdf";

        $this->recordExport(
            $activityLog,
            $request,
            'tenant_statement',
            'pdf',
            $filename,
            'party',
            $party->id,
            $this->partyLabel($party)
        );

        return $this->pdfResponse(
            $contents,
            $filename
        );
    }

    public function tenantCsv(
        Request $request,
        Party $party,
        TenantStatementService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->csv(
            $reports->generate(
                $party,
                $from,
                $to
            )
        );

        $filename =
            "tenant-statement-{$party->id}.csv";

        $this->recordExport(
            $activityLog,
            $request,
            'tenant_statement',
            'csv',
            $filename,
            'party',
            $party->id,
            $this->partyLabel($party)
        );

        return $this->csvResponse(
            $contents,
            $filename
        );
    }

    public function managingOrganisationPdf(
        Request $request,
        ManagingOrganisationReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->pdf(
            __('reports.managing_organisation_report'),
            $reports->generate(
                $from,
                $to
            )
        );

        $filename =
            'managing-organisation-report.pdf';

        $this->recordExport(
            $activityLog,
            $request,
            'managing_organisation',
            'pdf',
            $filename
        );

        return $this->pdfResponse(
            $contents,
            $filename
        );
    }

    public function managingOrganisationCsv(
        Request $request,
        ManagingOrganisationReportService $reports,
        ReportExportService $exports,
        ActivityLogService $activityLog
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $contents = $exports->csv(
            $reports->generate(
                $from,
                $to
            )
        );

        $filename =
            'managing-organisation-report.csv';

        $this->recordExport(
            $activityLog,
            $request,
            'managing_organisation',
            'csv',
            $filename
        );

        return $this->csvResponse(
            $contents,
            $filename
        );
    }

    /**
     * Record one successful manual report export.
     */
    private function recordExport(
        ActivityLogService $activityLog,
        Request $request,
        string $reportType,
        string $format,
        string $filename,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?string $entityLabel = null
    ): void {
        $activityLog->record(
            action: 'report.exported',
            request: $request,
            entityType: $entityType,
            entityId: $entityId,
            entityLabel: $entityLabel,
            metadata: [
                'report_type' => $reportType,
                'format' => $format,
                'filename' => $filename,
            ],
        );
    }

    /**
     * Resolve a stable human-readable Party label.
     */
    private function partyLabel(Party $party): string
    {
        return $party->name
            ?? $party->legal_name
            ?? "Party #{$party->id}";
    }

    /**
     * Validate optional export reporting period.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function validatedPeriod(Request $request): array
    {
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        if (
            $from !== null
            && $to !== null
            && $from > $to
        ) {
            throw ValidationException::withMessages([
                'to' => [
                    'The report end date must be on or after the start date.',
                ],
            ]);
        }

        return [
            $from,
            $to,
        ];
    }

    private function pdfResponse(
        string $contents,
        string $filename
    ): Response {
        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    private function csvResponse(
        string $contents,
        string $filename
    ): Response {
        return response(
            $contents,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
