<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Party;
use App\Models\Unit;
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
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        $report = $reports->generate(
            $party,
            $from,
            $to
        );

        return $this->pdfResponse(
            $exports->pdf(
                __('reports.owner_report'),
                $report
            ),
            "owner-report-{$party->id}.pdf"
        );
    }

    public function ownerCsv(
        Request $request,
        Party $party,
        OwnerReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->csvResponse(
            $exports->csv(
                $reports->generate(
                    $party,
                    $from,
                    $to
                )
            ),
            "owner-report-{$party->id}.csv"
        );
    }

    public function buildingPdf(
        Request $request,
        Building $building,
        BuildingReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->pdfResponse(
            $exports->pdf(
                __('reports.building_report'),
                $reports->generate(
                    $building,
                    $from,
                    $to
                )
            ),
            "building-report-{$building->id}.pdf"
        );
    }

    public function buildingCsv(
        Request $request,
        Building $building,
        BuildingReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->csvResponse(
            $exports->csv(
                $reports->generate(
                    $building,
                    $from,
                    $to
                )
            ),
            "building-report-{$building->id}.csv"
        );
    }

    public function unitPdf(
        Request $request,
        Unit $unit,
        UnitReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->pdfResponse(
            $exports->pdf(
                __('reports.unit_report'),
                $reports->generate(
                    $unit,
                    $from,
                    $to
                )
            ),
            "unit-report-{$unit->id}.pdf"
        );
    }

    public function unitCsv(
        Request $request,
        Unit $unit,
        UnitReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->csvResponse(
            $exports->csv(
                $reports->generate(
                    $unit,
                    $from,
                    $to
                )
            ),
            "unit-report-{$unit->id}.csv"
        );
    }

    public function tenantPdf(
        Request $request,
        Party $party,
        TenantStatementService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->pdfResponse(
            $exports->pdf(
                __('reports.tenant_statement'),
                $reports->generate(
                    $party,
                    $from,
                    $to
                )
            ),
            "tenant-statement-{$party->id}.pdf"
        );
    }

    public function tenantCsv(
        Request $request,
        Party $party,
        TenantStatementService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->csvResponse(
            $exports->csv(
                $reports->generate(
                    $party,
                    $from,
                    $to
                )
            ),
            "tenant-statement-{$party->id}.csv"
        );
    }

    public function managingOrganisationPdf(
        Request $request,
        ManagingOrganisationReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->pdfResponse(
            $exports->pdf(
                __('reports.managing_organisation_report'),
                $reports->generate(
                    $from,
                    $to
                )
            ),
            'managing-organisation-report.pdf'
        );
    }

    public function managingOrganisationCsv(
        Request $request,
        ManagingOrganisationReportService $reports,
        ReportExportService $exports
    ): Response {
        [$from, $to] = $this->validatedPeriod($request);

        return $this->csvResponse(
            $exports->csv(
                $reports->generate(
                    $from,
                    $to
                )
            ),
            'managing-organisation-report.csv'
        );
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
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',
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
                'Content-Type' =>
                    'text/csv; charset=UTF-8',

                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
