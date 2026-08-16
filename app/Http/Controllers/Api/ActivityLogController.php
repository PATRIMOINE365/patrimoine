<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administrator-only read API for Patrimoine Activity Log events.
 *
 * Activity Log is deliberately immutable:
 * - index supports searching/filtering/pagination;
 * - show exposes the complete historical event;
 * - no create, update or delete endpoint exists.
 */
class ActivityLogController extends Controller
{
    /**
     * Return newest Activity Log events first.
     */
    public function index(
        Request $request,
        ActivityLogQueryService $activityLogQuery
    ): JsonResponse {
        $validated =
            $activityLogQuery
                ->validatedFilters(
                    $request
                );

        /*
         * List responses deliberately exclude potentially large structured
         * historical fields. Complete detail remains available through show().
         */
        $events =
            $activityLogQuery
                ->query($validated)
                ->select([
                    'id',
                    'user_id',
                    'actor_name',
                    'actor_email',
                    'actor_role',
                    'action',
                    'entity_type',
                    'entity_id',
                    'entity_label',
                    'ip_address',
                    'created_at',
                ])
                ->paginate(
                    perPage: (int) (
                        $validated['per_page']
                        ?? 25
                    )
                );

        return response()->json(
            $events
        );
    }

    /**
     * Return the complete immutable historical event.
     */
    public function show(
        ActivityLog $activityLog
    ): JsonResponse {
        return response()->json(
            $activityLog
        );
    }
}
