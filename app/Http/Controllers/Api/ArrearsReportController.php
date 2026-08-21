<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArrearsReportRequest;
use App\Services\Reports\ArrearsReportService;
use Illuminate\Http\JsonResponse;

class ArrearsReportController extends Controller
{
    public function __invoke(
        ArrearsReportRequest $request,
        ArrearsReportService $service
    ): JsonResponse {
        return response()->json(
            $service->generate(
                $request->filters()
            )
        );
    }
}
