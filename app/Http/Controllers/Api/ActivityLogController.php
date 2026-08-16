<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Administrator-only read API for Patrimoine Activity Log events.
 *
 * Activity Log is deliberately immutable:
 * - index supports searching/filtering/pagination;
 * - show exposes the complete historical event;
 * - no create, update or delete endpoint exists.
 *
 * Normal scalar filters use the indexes created with the Activity Log
 * foundation. Free-text search is intentionally broader and may inspect
 * structured historical context when an Administrator explicitly searches.
 */
class ActivityLogController extends Controller
{
    /**
     * Return newest Activity Log events first.
     *
     * Supported filters:
     * - date range;
     * - current User identifier;
     * - frozen actor role;
     * - action;
     * - entity type;
     * - free-text historical context.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],

            'user_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'role' => [
                'nullable',
                Rule::enum(UserRole::class),
            ],

            'action' => [
                'nullable',
                'string',
                'max:100',
            ],

            'entity_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ]);

        $query = ActivityLog::query();

        /*
         * Date filters are inclusive because Administrators generally reason
         * about complete calendar days rather than exact timestamp bounds.
         */
        if (isset($validated['from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $validated['from']
            );
        }

        if (isset($validated['to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $validated['to']
            );
        }

        /*
         * user_id points to the current User record where that identity still
         * exists. Historical identity remains preserved independently in the
         * frozen actor_* fields.
         */
        if (isset($validated['user_id'])) {
            $query->where(
                'user_id',
                $validated['user_id']
            );
        }

        if (isset($validated['role'])) {
            $query->where(
                'actor_role',
                $validated['role']
            );
        }

        if (isset($validated['action'])) {
            $query->where(
                'action',
                $validated['action']
            );
        }

        if (isset($validated['entity_type'])) {
            $query->where(
                'entity_type',
                $validated['entity_type']
            );
        }

        /*
         * Free-text search intentionally uses frozen historical fields rather
         * than current related records. This keeps results understandable
         * after Users or business records have changed or been deleted.
         *
         * Structured JSON is included because useful business context such as
         * references, changed values and document/report metadata may exist
         * only in those historical snapshots.
         */
        if (
            isset($validated['search'])
            && trim($validated['search']) !== ''
        ) {
            $search = trim(
                $validated['search']
            );

            $pattern = "%{$search}%";

            $query->where(
                function ($query) use ($pattern): void {
                    $query
                        ->where(
                            'actor_name',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'actor_email',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'actor_role',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'action',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'entity_type',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'entity_id',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'entity_label',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'ip_address',
                            'like',
                            $pattern
                        )
                        ->orWhereRaw(
                            'CAST(before_values AS CHAR) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'CAST(after_values AS CHAR) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'CAST(snapshot AS CHAR) LIKE ?',
                            [$pattern]
                        )
                        ->orWhereRaw(
                            'CAST(metadata AS CHAR) LIKE ?',
                            [$pattern]
                        );
                }
            );
        }

        /*
         * The list projection deliberately excludes potentially large
         * structured snapshots. Activity Q can retrieve one event through
         * show() when an Administrator opens its detail view.
         */
        $events = $query
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
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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
     *
     * Structured before/after values, snapshots and metadata are intentionally
     * available here rather than loading every detail into the list endpoint.
     */
    public function show(
        ActivityLog $activityLog
    ): JsonResponse {
        return response()->json(
            $activityLog
        );
    }
}
