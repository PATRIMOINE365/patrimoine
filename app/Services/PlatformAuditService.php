<?php

namespace App\Services;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Records platform-console actions in TWO audit trails at once:
 *
 * - the internal platform organisation's Activity Log, carrying the
 *   full detail and the real staff actor;
 * - the affected customer organisation's own Activity Log, attributed
 *   to the operating company rather than to an individual staff
 *   member, so customers see WHAT happened without the console
 *   exposing WHO on the Kality side performed it.
 */
class PlatformAuditService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {
    }

    /**
     * Record one platform action against a customer organisation.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $action,
        User $admin,
        Request $request,
        Organisation $customerOrganisation,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?string $entityLabel = null,
        ?array $metadata = null,
    ): void {
        /*
         * Platform copy: real actor, target organisation named in the
         * metadata.
         */
        $this->activityLog->record(
            action: $action,
            actor: $admin,
            request: $request,
            entityType: $entityType ?? 'organisation',
            entityId: $entityId ?? $customerOrganisation->id,
            entityLabel: $entityLabel ?? $customerOrganisation->name,
            metadata: array_merge(
                $metadata ?? [],
                [
                    'customer_organisation_id' => $customerOrganisation->id,
                    'customer_organisation' => $customerOrganisation->name,
                ]
            ),
            organisationId: (int) $admin->organisation_id,
        );

        /*
         * Customer copy: the company acts, not the individual. The
         * request object is deliberately NOT passed so the staff
         * account can never be auto-resolved as the actor; client
         * context is carried over explicitly.
         */
        $this->activityLog->record(
            action: $action,
            entityType: $entityType ?? 'organisation',
            entityId: $entityId ?? $customerOrganisation->id,
            entityLabel: $entityLabel ?? $customerOrganisation->name,
            metadata: $metadata,
            actorName: (string) config('legal.product.name').' (platform)',
            actorEmail: (string) config('legal.mailboxes.support'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            organisationId: (int) $customerOrganisation->id,
        );
    }
}
