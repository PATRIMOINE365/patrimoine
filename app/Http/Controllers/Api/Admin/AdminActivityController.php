<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\PageSize;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The platform's own audit trail: every console action performed by
 * Kality staff, newest first.
 */
class AdminActivityController extends Controller
{
    /**
     * Paginated platform activity.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        /** @var User $admin */
        $admin = $request->user();

        $page = ActivityLog::withoutGlobalScopes()
            ->where('organisation_id', $admin->organisation_id)
            ->orderByDesc('id')
            ->paginate(PageSize::fromRequest($request));

        return response()->json([
            'data' => collect($page->items())->map(
                fn (ActivityLog $event): array => [
                    'id' => $event->id,
                    'action' => $event->action,
                    'actor' => $event->actor_name,
                    'entity_type' => $event->entity_type,
                    'entity_label' => $event->entity_label,
                    'customer_organisation' =>
                        $event->metadata['customer_organisation'] ?? null,
                    'metadata' => $event->metadata,
                    'ip_address' => $event->ip_address,
                    'created_at' => $event->created_at?->toDateTimeString(),
                ]
            ),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                /*
                 * The control's summary line is built from these. Without
                 * them it read "Showing 0-0 of 27".
                 */
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ]);
    }
}
