<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Party;
use App\Models\Unit;
use App\Services\Reports\BuildingReportService;
use App\Services\Reports\ManagingOrganisationReportService;
use App\Services\Reports\OwnerReportService;
use App\Services\Reports\TenantStatementService;
use App\Services\Reports\UnitReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Read-only reporting endpoints for Patrimoine.
 *
 * Report calculations remain inside dedicated report services.
 * This controller only validates request parameters, resolves models,
 * and returns the resulting projections.
 */
class ReportController extends Controller
{
    /**
     * Return one owner's consolidated financial report.
     */
    public function owner(
        Request $request,
        Party $party,
        OwnerReportService $service
    ): JsonResponse {
        [$from, $to] = $this->validatedPeriod($request);

        return response()->json(
            $service->generate(
                owner: $party,
                from: $from,
                to: $to
            )
        );
    }

    /**
     * Return one Building's operational and financial report.
     */
    public function building(
        Request $request,
        Building $building,
        BuildingReportService $service
    ): JsonResponse {
        [$from, $to] = $this->validatedPeriod($request);

        return response()->json(
            $service->generate(
                building: $building,
                from: $from,
                to: $to
            )
        );
    }

    /**
     * Return one Unit's tenancy and financial history.
     */
    public function unit(
        Request $request,
        Unit $unit,
        UnitReportService $service
    ): JsonResponse {
        [$from, $to] = $this->validatedPeriod($request);

        return response()->json(
            $service->generate(
                unit: $unit,
                from: $from,
                to: $to
            )
        );
    }

    /**
     * Return the consolidated statement for one tenant Party.
     */
    public function tenant(
        Request $request,
        Party $party,
        TenantStatementService $service
    ): JsonResponse {
        [$from, $to] = $this->validatedPeriod($request);

        return response()->json(
            $service->generate(
                tenant: $party,
                from: $from,
                to: $to
            )
        );
    }

    /**
     * Return the portfolio-wide managing organisation report.
     */
    public function managingOrganisation(
        Request $request,
        ManagingOrganisationReportService $service
    ): JsonResponse {
        [$from, $to] = $this->validatedPeriod($request);

        return response()->json(
            $service->generate(
                from: $from,
                to: $to
            )
        );
    }

    /**
     * Validate the optional reporting period.
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
}
