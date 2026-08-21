<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentIncrementRequest;
use App\Models\Lease;
use App\Models\RentIncrement;
use App\Services\ActivityLogService;
use App\Services\RentIncrementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * API surface for the discretionary rent-increment workflow (v1.0.6).
 *
 * Scheduling and cancelling are the only operations exposed over HTTP.
 * Applying an increment stays exclusively with the daily
 * patrimoine:apply-due-rent-increments command so rent changes always
 * happen through the controlled scheduler path, never ad hoc.
 */
class RentIncrementController extends Controller
{
    /**
     * List a Lease's rent increments, most recent effective date first.
     */
    public function index(
        Lease $lease
    ): JsonResponse {
        $increments = $lease
            ->rentIncrements()
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'rent_increments' => $increments,
        ]);
    }

    /**
     * Schedule a future rent increment on a Lease.
     */
    public function store(
        StoreRentIncrementRequest $request,
        Lease $lease,
        RentIncrementService $service,
        ActivityLogService $activityLog
    ): JsonResponse {
        try {
            $increment = $service->schedule(
                lease: $lease,
                incrementType: $request->validated('increment_type'),
                incrementValue: $request->validated('increment_value'),
                effectiveDate: $request->validated('effective_date'),
            );
        } catch (RuntimeException $exception) {
            /*
             * Business-rule refusals (pending increment exists, interval
             * too short, non-increasing rent, …) surface as validation
             * errors so API clients handle one failure shape.
             */
            throw ValidationException::withMessages([
                'rent_increment' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'lease.rent_increment_scheduled',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel: 'Lease #'.$lease->id,
            snapshot: $this->snapshot($increment),
        );

        return response()->json(
            data: [
                'rent_increment' => $increment,
            ],
            status: 201
        );
    }

    /**
     * Cancel a scheduled rent increment before it has been applied.
     */
    public function cancel(
        Request $request,
        RentIncrement $rentIncrement,
        RentIncrementService $service,
        ActivityLogService $activityLog
    ): JsonResponse {
        try {
            $rentIncrement = $service->cancel(
                $rentIncrement
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'rent_increment' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'lease.rent_increment_cancelled',
            request: $request,
            entityType: 'lease',
            entityId: $rentIncrement->lease_id,
            entityLabel: 'Lease #'.$rentIncrement->lease_id,
            snapshot: $this->snapshot($rentIncrement),
        );

        return response()->json([
            'rent_increment' => $rentIncrement,
        ]);
    }

    /**
     * Audit snapshot of an increment's financially relevant fields.
     *
     * @return array<string, mixed>
     */
    private function snapshot(
        RentIncrement $increment
    ): array {
        return [
            'rent_increment_id' => $increment->id,
            'lease_id' => $increment->lease_id,
            'old_rent_amount' => $increment->old_rent_amount,
            'new_rent_amount' => $increment->new_rent_amount,
            'increment_type' => $increment->increment_type,
            'increment_value' => $increment->increment_value,
            'effective_date' => $increment->effective_date?->toDateString(),
            'status' => $increment->status,
        ];
    }
}
