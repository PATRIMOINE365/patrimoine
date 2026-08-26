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

        /*
         * V1.0.9: fetch the expiry and increment lists once and feed
         * their counts into metrics(), so neither query runs twice per
         * request. The occupancy percentage is likewise derived from the
         * unit counts metrics() already computed.
         */
        $expiringLeases = $service->expiringLeases($asOfDate);
        $upcomingIncrements = $service->upcomingIncrements($asOfDate);

        $metrics = $service->metrics(
            $asOfDate,
            $expiringLeases->count(),
            $upcomingIncrements->count()
        );

        return response()->json([
            'as_of' => $asOfDate->toDateString(),
            'metrics' => $metrics,

            /*
             * V1.0.7 dashboard additions: occupancy percentage, trailing
             * collections trend, expiring leases and upcoming increments —
             * one round trip paints the whole dashboard.
             */
            'occupancy_rate' => $metrics['total_units'] > 0
                ? (int) round(
                    $metrics['occupied_units'] * 100 / $metrics['total_units']
                )
                : 0,

            'collections_trend' => $service->collectionsTrend($asOfDate),

            'expiring_leases' => $expiringLeases
                ->map(fn ($lease): array => [
                    'id' => $lease->id,
                    'tenant_name' => $lease->tenant?->name,
                    'building_name' => $lease->unit?->building?->name,
                    'unit_name' => $lease->unit?->name,
                    'end_date' => $lease->end_date?->toDateString(),
                    'status' => $lease->status,
                ])
                ->values(),

            'upcoming_increments' => $upcomingIncrements
                ->map(fn ($increment): array => [
                    'id' => $increment->id,
                    'lease_id' => $increment->lease_id,
                    'tenant_name' => $increment->lease?->tenant?->name,
                    'old_rent_amount' => $increment->old_rent_amount,
                    'new_rent_amount' => $increment->new_rent_amount,
                    'effective_date' => $increment->effective_date?->toDateString(),
                ])
                ->values(),
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
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paidAmount(),
            'outstanding_amount' => $invoice->outstandingAmount(),

            'tenant' => [
                'id' => $invoice->lease?->tenant?->id,
                'name' => $invoice->lease?->tenant?->name
                    ?? $invoice->lease?->tenant?->legal_name,
            ],

            'unit' => [
                'id' => $invoice->lease?->unit?->id,
                'name' => $invoice->lease?->unit?->name,
            ],

            'building' => [
                'id' => $invoice->lease?->unit?->building?->id,
                'name' => $invoice->lease?->unit?->building?->name,
            ],
        ];
    }
}
