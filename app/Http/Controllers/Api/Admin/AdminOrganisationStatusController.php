<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Services\PlatformAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Suspension lifecycle: sign-in refused, data intact, reversible with
 * one click.
 */
class AdminOrganisationStatusController extends Controller
{
    use Concerns\ReentersPassword;

    /**
     * Suspend a customer organisation.
     */
    public function suspend(
        int $organisationId,
        Request $request,
        PlatformAuditService $audit
    ): JsonResponse {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $organisation = Organisation::query()
            ->customers()
            ->where('status', 'active')
            ->findOrFail($organisationId);

        /*
         * V1.0.51: suspension is a customer outage; it asks for the
         * administrator's own password.
         */
        $this->requirePasswordReentry(
            $request,
            'organisation.suspend',
            $organisation
        );

        $organisation->update(['status' => 'suspended']);

        $audit->record(
            action: 'platform.organisation_suspended',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            metadata: [
                'reason' => $validated['reason'] ?? null,
            ],
        );

        return response()->json([
            'message' => 'Organisation suspended.',
        ]);
    }

    /**
     * Reactivate a suspended customer organisation.
     */
    public function reactivate(
        int $organisationId,
        Request $request,
        PlatformAuditService $audit
    ): JsonResponse {
        $organisation = Organisation::query()
            ->customers()
            ->where('status', 'suspended')
            ->findOrFail($organisationId);

        $organisation->update(['status' => 'active']);

        $audit->record(
            action: 'platform.organisation_reactivated',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
        );

        return response()->json([
            'message' => 'Organisation reactivated.',
        ]);
    }
}
