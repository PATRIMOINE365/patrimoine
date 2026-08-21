<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OccupancyReportRequest;
use App\Services\Reports\OccupancyReportService;
use Illuminate\Http\JsonResponse;

class OccupancyReportController extends Controller
{
    public function __invoke(
        OccupancyReportRequest $request,
        OccupancyReportService $service
    ): JsonResponse {
        return response()->json(
            $service->generate(
                $request->filters()
            )
        );
    }
}
