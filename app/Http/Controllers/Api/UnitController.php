<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\BusinessActivitySnapshotService;
use App\Services\BusinessRecordDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * REST API controller for individually leasable Units.
 */
class UnitController extends Controller
{
    /**
     * Return Units with their parent Building.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::query()
            ->with('building');

        if ($request->filled('building_id')) {
            $query->where(
                'building_id',
                (int) $request->input('building_id')
            );
        }

        // V1.0.7: filter by commercial/residential classification.
        if ($request->filled('is_commercial')) {
            $query->where(
                'is_commercial',
                $request->boolean('is_commercial')
            );
        }

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(
                'name',
                'like',
                "%{$search}%"
            );
        }

        return response()->json(
            $query
                ->orderBy('building_id')
                ->orderBy('name')
                ->paginate(
                    perPage: min(
                        max((int) $request->input('per_page', 25), 1),
                        100
                    )
                )
        );
    }

    /**
     * Create a Unit.
     */
    public function store(
        StoreUnitRequest $request,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $unit = DB::transaction(
            function () use (
                $request,
                $activityLog,
                $snapshots
            ): Unit {
                $unit = Unit::create(
                    $request->validated()
                )->load('building');

                $activityLog->record(
                    action: 'unit.created',
                    request: $request,
                    entityType: 'unit',
                    entityId: $unit->id,
                    entityLabel: $unit->name,
                    snapshot: $snapshots->unit($unit),
                );

                return $unit;
            }
        );

        return response()->json(
            data: $unit,
            status: 201
        );
    }

    /**
     * Return one Unit with Building and Lease history.
     */
    public function show(Unit $unit): JsonResponse
    {
        return response()->json(
            $unit->load([
                'building',
                'leases.tenant',
            ])
        );
    }

    /**
     * Update a Unit.
     */
    public function update(
        UpdateUnitRequest $request,
        Unit $unit,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $unit = DB::transaction(
            function () use (
                $request,
                $unit,
                $activityLog,
                $snapshots
            ): Unit {
                $beforeSnapshot =
                    $snapshots->unit(
                        $unit->fresh()->load('building')
                    );

                $unit->update(
                    $request->validated()
                );

                $unit = $unit
                    ->refresh()
                    ->load('building');

                $afterSnapshot =
                    $snapshots->unit($unit);

                [$before, $after] =
                    $snapshots->changes(
                        $beforeSnapshot,
                        $afterSnapshot
                    );

                if ($before !== []) {
                    $activityLog->record(
                        action: 'unit.updated',
                        request: $request,
                        entityType: 'unit',
                        entityId: $unit->id,
                        entityLabel: $unit->name,
                        before: $before,
                        after: $after,
                    );
                }

                return $unit;
            }
        );

        return response()->json($unit);
    }

    /**
     * Delete a Unit if no protected Lease history references it.
     */
    public function destroy(
        Request $request,
        Unit $unit,
        BusinessRecordDeletionService $deletions,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $targetId = $unit->id;
        $targetLabel = $unit->name;
        $snapshot =
            $snapshots->unit($unit);

        $message =
            $deletions->deleteUnit(
                $unit
            );

        if ($message !== null) {
            return response()->json(
                [
                    'message' => $message,
                ],
                409
            );
        }

        $activityLog->record(
            action: 'unit.deleted',
            request: $request,
            entityType: 'unit',
            entityId: $targetId,
            entityLabel: $targetLabel,
            snapshot: $snapshot,
        );

        return response()->json(
            data: null,
            status: 204
        );
    }
}
