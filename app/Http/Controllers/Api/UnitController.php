<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\BusinessRecordDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        StoreUnitRequest $request
    ): JsonResponse {
        $unit = Unit::create(
            $request->validated()
        );

        return response()->json(
            data: $unit->load('building'),
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
        Unit $unit
    ): JsonResponse {
        $unit->update(
            $request->validated()
        );

        return response()->json(
            $unit->refresh()->load('building')
        );
    }

    /**
     * Delete a Unit if no protected Lease history references it.
     */
    public function destroy(
        Unit $unit,
        BusinessRecordDeletionService $deletions
    ): JsonResponse {
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

        return response()->json(
            data: null,
            status: 204
        );
    }
}
