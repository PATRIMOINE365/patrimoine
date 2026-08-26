<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Services\ActivityLogService;
use App\Services\PlatformOrganisationDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * The most dangerous button on the platform: permanent destruction of
 * a customer organisation and everything it owns.
 *
 * Guard rails, all mandatory:
 *
 * 1. the organisation must already be SUSPENDED (a deliberate,
 *    separate prior step);
 * 2. the organisation's exact name must be typed back;
 * 3. the administrator must re-enter their own password.
 *
 * The audit record survives only on the platform side — the customer's
 * own log dies with their data, which is the point of deletion.
 */
class AdminOrganisationDeletionController extends Controller
{
    /**
     * Permanently destroy a customer organisation.
     */
    public function destroy(
        int $organisationId,
        Request $request,
        PlatformOrganisationDeletionService $deletion,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated = $request->validate([
            'name_confirmation' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = $request->user();

        if (
            ! Hash::check(
                $validated['password'],
                $admin->password
            )
        ) {
            throw ValidationException::withMessages([
                'password' => [
                    __('api.auth.password_confirmation_failed'),
                ],
            ]);
        }

        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($organisationId);

        if ($organisation->status !== 'suspended') {
            throw ValidationException::withMessages([
                'organisation' => [
                    'Suspend the organisation before deleting it.',
                ],
            ]);
        }

        if ($validated['name_confirmation'] !== $organisation->name) {
            throw ValidationException::withMessages([
                'name_confirmation' => [
                    'The organisation name does not match.',
                ],
            ]);
        }

        $deletedName = $organisation->name;

        $deleted = $deletion->destroy($organisation);

        $activityLog->record(
            action: 'platform.organisation_deleted',
            actor: $admin,
            request: $request,
            entityType: 'organisation',
            entityId: $organisationId,
            entityLabel: $deletedName,
            metadata: [
                'rows_deleted' => array_sum($deleted),
                'tables' => $deleted,
            ],
            organisationId: (int) $admin->organisation_id,
        );

        return response()->json([
            'message' => 'Organisation permanently deleted.',
            'rows_deleted' => array_sum($deleted),
        ]);
    }
}
