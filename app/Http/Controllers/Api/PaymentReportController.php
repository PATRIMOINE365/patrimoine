<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentReportRequest;
use App\Services\Reports\PaymentReportService;
use Illuminate\Http\JsonResponse;

class PaymentReportController extends Controller
{
    public function __invoke(
        PaymentReportRequest $request,
        PaymentReportService $service
    ): JsonResponse {
        return response()->json(
            $service->generate(
                $request->filters()
            )
        );
    }
}
