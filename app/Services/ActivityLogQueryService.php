<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Central read/query contract for the immutable Patrimoine Activity Log.
 *
 * The browser list, API pagination and Activity Log exports must use the
 * same validation and filtering rules so an exported result cannot differ
 * silently from what the Administrator searched for.
 */
class ActivityLogQueryService
{
    /**
     * Validate Activity Log filters supplied by an HTTP request.
     *
     * Pagination fields are optionally accepted for the normal list API.
     *
     * @return array<string, mixed>
     */
    public function validatedFilters(
        Request $request,
        bool $includePagination = true
    ): array {
        $rules = [
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
        ];

        if ($includePagination) {
            $rules['page'] = [
                'nullable',
                'integer',
                'min:1',
            ];

            $rules['per_page'] = [
                'nullable',
                'integer',
                'between:1,100',
            ];
        }

        return $request->validate(
            $rules
        );
    }

    /**
     * Build the canonical filtered Activity Log query.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<ActivityLog>
     */
    public function query(
        array $filters
    ): Builder {
        $query =
            ActivityLog::query();

        if (isset($filters['from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['from']
            );
        }

        if (isset($filters['to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['to']
            );
        }

        if (isset($filters['user_id'])) {
            $query->where(
                'user_id',
                $filters['user_id']
            );
        }

        if (isset($filters['role'])) {
            $query->where(
                'actor_role',
                $filters['role']
            );
        }

        if (isset($filters['action'])) {
            $query->where(
                'action',
                $filters['action']
            );
        }

        if (isset($filters['entity_type'])) {
            $query->where(
                'entity_type',
                $filters['entity_type']
            );
        }

        if (
            isset($filters['search'])
            && trim(
                (string) $filters['search']
            ) !== ''
        ) {
            $search =
                trim(
                    (string) $filters['search']
                );

            $pattern =
                "%{$search}%";

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
                        ->orWhere(
                            'browser',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'platform',
                            'like',
                            $pattern
                        )
                        ->orWhere(
                            'device',
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

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Return only filters which materially describe an export.
     *
     * Pagination is deliberately absent because Activity Log exports contain
     * the complete matching result set rather than one browser page.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportFilterSnapshot(
        array $filters
    ): array {
        return array_filter(
            [
                'from' => $filters['from']
                    ?? null,

                'to' => $filters['to']
                    ?? null,

                'user_id' => $filters['user_id']
                    ?? null,

                'role' => $filters['role']
                    ?? null,

                'action' => $filters['action']
                    ?? null,

                'entity_type' => $filters['entity_type']
                    ?? null,

                'search' => $filters['search']
                    ?? null,
            ],
            static fn (mixed $value): bool => $value !== null
                && $value !== ''
        );
    }
}
