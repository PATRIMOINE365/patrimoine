<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Central writer for Patrimoine Activity Log events.
 *
 * Controllers and services introduced in later Activity Log activities
 * should record meaningful successful human actions through this service
 * rather than writing directly to activity_logs.
 */
class ActivityLogService
{
    public function __construct(
        private readonly UserAgentContextService $userAgentContext,
    ) {
    }

    /**
     * Record one meaningful human action.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $action,
        ?User $actor = null,
        ?Request $request = null,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?string $entityLabel = null,
        ?array $before = null,
        ?array $after = null,
        ?array $snapshot = null,
        ?array $metadata = null,
        ?string $actorName = null,
        ?string $actorEmail = null,
        ?string $actorRole = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ActivityLog {
        /*
         * Resolve authenticated identity automatically when a request is
         * supplied and the caller did not explicitly provide an actor.
         */
        if ($actor === null && $request !== null) {
            $requestUser = $request->user();

            if ($requestUser instanceof User) {
                $actor = $requestUser;
            }
        }

        /*
         * Snapshot identity now. Never rely on future User state to explain
         * who performed this historical action.
         */
        if ($actor !== null) {
            $actorName ??= $actor->name;
            $actorEmail ??= $actor->email;
            $actorRole ??= $actor->role->value;
        }

        $ipAddress ??= $request?->ip();
        $userAgent ??= $request?->userAgent();

        /*
         * Freeze the client context now, exactly like actor identity:
         * historical events must never be re-interpreted later.
         */
        $clientContext =
            $this->userAgentContext->parse($userAgent);

        return ActivityLog::create([
            'user_id' => $actor?->getKey(),
            'actor_name' => $actorName,
            'actor_email' => $actorEmail,
            'actor_role' => $actorRole,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId === null
                ? null
                : (string) $entityId,
            'entity_label' => $entityLabel,
            'before_values' => $this->normalize($before),
            'after_values' => $this->normalize($after),
            'snapshot' => $this->normalize($snapshot),
            'metadata' => $this->normalize($metadata),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent === null
                ? null
                : mb_substr($userAgent, 0, 1024),
            'browser' => $clientContext['browser'],
            'platform' => $clientContext['platform'],
            'device' => $clientContext['device'],
        ]);
    }

    /**
     * Remove null entries while preserving meaningful false/zero values.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function normalize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $normalized = Arr::where(
            $values,
            static fn (mixed $value): bool => $value !== null
        );

        return $normalized === []
            ? null
            : $normalized;
    }
}
