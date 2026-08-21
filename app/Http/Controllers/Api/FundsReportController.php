<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\FundsReportService;
use Illuminate\Http\JsonResponse;

class FundsReportController extends Controller
{
    public function __invoke(
        FundsReportService $service
    ): JsonResponse {
        return response()->json(
            $service->generate()
        );
    }
}
