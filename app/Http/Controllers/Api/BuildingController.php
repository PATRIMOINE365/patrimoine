<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;
use App\Services\ActivityLogService;
use App\Services\BusinessActivitySnapshotService;
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
        /*
         * V1.0.42: archived records leave the lists and the pickers. The
         * filter is here rather than in a global scope on purpose — a
         * scope would reach the reports and the documents too, and a set
         * of accounts whose totals move when somebody tidies a list is a
         * set of accounts nobody can trust.
         */
        $query = Building::query()
            ->with([
                'ownerships.party',
                'units' => fn ($unitQuery) => $unitQuery
                    ->notArchived()
                    ->withExists([
                        'leases as is_occupied' => fn ($leaseQuery) => $leaseQuery
                            ->whereIn('status', ['active', 'notice']),
                    ]),
            ])
            ->notArchived();

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

        $buildings =
            $query
                ->orderBy('name')
                ->paginate(
                    perPage: min(
                        max((int) $request->input('per_page', 25), 1),
                        100
                    )
                );

        /*
         * So each row can label its own button: Delete while the record
         * can still go, Archive once it cannot.
         *
         * V1.0.43: the units too. Their rows carry the same button and had
         * always drawn Delete, so a unit that had been let offered an
         * action the server was always going to refuse.
         */
        $buildings->getCollection()->each(
            function ($building): void {
                $building->append('is_deletable');

                $building->units->each->append('is_deletable');
            }
        );

        return response()->json($buildings);
    }

    /**
     * Create a Building and its complete ownership allocation.
     */
    public function store(
        StoreBuildingRequest $request,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $building = DB::transaction(
            function () use (
                $request,
                $activityLog,
                $snapshots
            ): Building {
                $validated = $request->validated();

                $owners = $validated['owners'];

                unset($validated['owners']);

                $building = Building::create($validated);

                foreach ($owners as $owner) {
                    $building->ownerships()->create([
                        'party_id' => $owner['party_id'],
                        'ownership_percentage' => $owner['ownership_percentage'],
                    ]);
                }

                $building->load([
                    'ownerships.party',
                    'units' => fn ($unitQuery) => $unitQuery
                        ->withExists([
                            'leases as is_occupied' => fn ($leaseQuery) => $leaseQuery
                                ->whereIn('status', ['active', 'notice']),
                        ]),
                ]);

                $activityLog->record(
                    action: 'building.created',
                    request: $request,
                    entityType: 'building',
                    entityId: $building->id,
                    entityLabel: $building->name,
                    snapshot: $snapshots->building($building),
                );

                return $building;
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
                'units' => fn ($unitQuery) => $unitQuery
                    ->withExists([
                        'leases as is_occupied' => fn ($leaseQuery) => $leaseQuery
                            ->whereIn('status', ['active', 'notice']),
                    ]),
            ])
        );
    }

    /**
     * Update Building details and replace its ownership allocation.
     */
    public function update(
        UpdateBuildingRequest $request,
        Building $building,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $building = DB::transaction(
            function () use (
                $request,
                $building,
                $activityLog,
                $snapshots
            ): Building {
                $beforeSnapshot =
                    $snapshots->building(
                        $building
                            ->fresh()
                            ->load('ownerships.party')
                    );

                $validated = $request->validated();

                $owners = $validated['owners'];

                unset($validated['owners']);

                $building->update($validated);

                $building->ownerships()->delete();

                foreach ($owners as $owner) {
                    $building->ownerships()->create([
                        'party_id' => $owner['party_id'],
                        'ownership_percentage' => $owner['ownership_percentage'],
                    ]);
                }

                $building = $building
                    ->refresh()
                    ->load([
                        'ownerships.party',
                        'units' => fn ($unitQuery) => $unitQuery
                            ->withExists([
                                'leases as is_occupied' => fn ($leaseQuery) => $leaseQuery
                                    ->whereIn('status', ['active', 'notice']),
                            ]),
                    ]);

                $afterSnapshot =
                    $snapshots->building($building);

                [$before, $after] =
                    $snapshots->changes(
                        $beforeSnapshot,
                        $afterSnapshot
                    );

                if ($before !== []) {
                    $activityLog->record(
                        action: 'building.updated',
                        request: $request,
                        entityType: 'building',
                        entityId: $building->id,
                        entityLabel: $building->name,
                        before: $before,
                        after: $after,
                    );
                }

                return $building;
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
        Request $request,
        Building $building,
        BusinessRecordDeletionService $deletions,
        ActivityLogService $activityLog,
        BusinessActivitySnapshotService $snapshots
    ): JsonResponse {
        $targetId = $building->id;
        $targetLabel = $building->name;
        $snapshot =
            $snapshots->building($building);

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

        $activityLog->record(
            action: 'building.deleted',
            request: $request,
            entityType: 'building',
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
