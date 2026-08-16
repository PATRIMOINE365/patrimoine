<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;
use App\Services\BusinessRecordDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * REST API controller for Patrimoine Buildings.
 */
class BuildingController extends Controller
{
    /**
     * Return Buildings with ownership and Unit information.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Building::query()
            ->with([
                'ownerships.party',
                'units',
            ]);



        /*
        |--------------------------------------------------------------------------
        | Building / Unit Search
        |--------------------------------------------------------------------------
        |
        | The Properties screen represents Buildings together with their Units.
        | A user should therefore be able to find a Property either by:
        |
        | - Building name;
        | - Building location;
        | - Building address;
        | - Building description;
        | - Unit name / number;
        | - Unit description.
        |
        | When a Unit matches, the parent Building is returned so the normal
        | Properties UI can render the Building and all of its Units.
        |
        */

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'location',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'address',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'units',
                            function ($unitQuery) use ($search) {
                                $unitQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'description',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }





        return response()->json(
            $query
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
     * Create a Building and its complete ownership allocation.
     */
    public function store(
        StoreBuildingRequest $request
    ): JsonResponse {
        $building = DB::transaction(
            function () use ($request): Building {
                $validated = $request->validated();

                $owners = $validated['owners'];

                unset($validated['owners']);

                $building = Building::create($validated);

                foreach ($owners as $owner) {
                    $building->ownerships()->create([
                        'party_id' => $owner['party_id'],
                        'ownership_percentage' =>
                            $owner['ownership_percentage'],
                    ]);
                }

                return $building->load([
                    'ownerships.party',
                    'units',
                ]);
            }
        );

        return response()->json(
            data: $building,
            status: 201
        );
    }

    /**
     * Return a single Building with owners and Units.
     */
    public function show(Building $building): JsonResponse
    {
        return response()->json(
            $building->load([
                'ownerships.party',
                'units',
            ])
        );
    }

    /**
     * Update Building details and replace its ownership allocation.
     */
    public function update(
        UpdateBuildingRequest $request,
        Building $building
    ): JsonResponse {
        $building = DB::transaction(
            function () use ($request, $building): Building {
                $validated = $request->validated();

                $owners = $validated['owners'];

                unset($validated['owners']);

                $building->update($validated);

                /*
                 * Patrimoine V1 does not preserve historical ownership
                 * percentage changes. The current allocation is therefore
                 * replaced as one complete set.
                 */
                $building->ownerships()->delete();

                foreach ($owners as $owner) {
                    $building->ownerships()->create([
                        'party_id' => $owner['party_id'],
                        'ownership_percentage' =>
                            $owner['ownership_percentage'],
                    ]);
                }

                return $building->refresh()->load([
                    'ownerships.party',
                    'units',
                ]);
            }
        );

        return response()->json($building);
    }

    /**
     * Delete an unreferenced Building.
     *
     * Foreign-key restrictions protect contractual and financial history.
     */
    public function destroy(
        Building $building,
        BusinessRecordDeletionService $deletions
    ): JsonResponse {
        $message =
            $deletions->deleteBuilding(
                $building
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
