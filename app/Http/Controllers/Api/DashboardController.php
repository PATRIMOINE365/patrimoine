<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only API controller for Patrimoine dashboard data.
 *
 * All calculations remain in DashboardService so this controller only
 * handles HTTP input, serialization and response structure.
 */
class DashboardController extends Controller
{
    /**
     * Return the primary dashboard summary.
     *
     * Optional query parameter:
     * - as_of: YYYY-MM-DD date used for due/overdue calculations.
     */
    public function summary(
        Request $request,
        DashboardService $service
    ): JsonResponse {
        $asOfDate = $this->resolveAsOfDate($request);

        return response()->json([
            'as_of' => $asOfDate->toDateString(),
            'metrics' => $service->metrics($asOfDate),
        ]);
    }

    /**
     * Return overdue tenant obligations.
     *
     * Optional query parameter:
     * - as_of: YYYY-MM-DD.
     */
    public function overdue(
        Request $request,
        DashboardService $service
    ): JsonResponse {
        $asOfDate = $this->resolveAsOfDate($request);

        $invoices = $service->overdueInvoices($asOfDate);

        return response()->json([
            'as_of' => $asOfDate->toDateString(),
            'count' => $invoices->count(),
            'data' => $invoices->map(
                fn ($invoice): array => $this->serializeInvoice($invoice)
            )->values(),
        ]);
    }

    /**
     * Return tenant obligations becoming due soon.
     *
     * Optional query parameters:
     * - as_of: YYYY-MM-DD.
     * - days: look-ahead window between 1 and 90 days.
     */
    public function upcoming(
        Request $request,
        DashboardService $service
    ): JsonResponse {
        $validated = $request->validate([
            'as_of' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'days' => [
                'nullable',
                'integer',
                'between:1,90',
            ],
        ]);

        $asOfDate = isset($validated['as_of'])
            ? Carbon::parse($validated['as_of'])->startOfDay()
            : now()->startOfDay();

        $days = (int) ($validated['days'] ?? 7);

        $invoices = $service->upcomingInvoices(
            $asOfDate,
            $days
        );

        return response()->json([
            'as_of' => $asOfDate->toDateString(),
            'days' => $days,
            'count' => $invoices->count(),
            'data' => $invoices->map(
                fn ($invoice): array => $this->serializeInvoice($invoice)
            )->values(),
        ]);
    }

    /**
     * Resolve and validate the dashboard as-of date.
     */
    private function resolveAsOfDate(Request $request): Carbon
    {
        $validated = $request->validate([
            'as_of' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        return isset($validated['as_of'])
            ? Carbon::parse($validated['as_of'])->startOfDay()
            : now()->startOfDay();
    }

    /**
     * Convert an Invoice into a compact dashboard obligation record.
     *
     * @return array<string, mixed>
     */
    private function serializeInvoice($invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => $invoice->due_date->toDateString(),
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paidAmount(),
            'outstanding_amount' => $invoice->outstandingAmount(),

            'tenant' => [
                'id' => $invoice->lease->tenant->id,
                'name' => $invoice->lease->tenant->name
                    ?? $invoice->lease->tenant->legal_name,
            ],

            'unit' => [
                'id' => $invoice->lease->unit->id,
                'name' => $invoice->lease->unit->name,
            ],

            'building' => [
                'id' => $invoice->lease->unit->building->id,
                'name' => $invoice->lease->unit->building->name,
            ],
        ];
    }
}
