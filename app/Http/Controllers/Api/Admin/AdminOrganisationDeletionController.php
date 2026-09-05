<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Services\ActivityLogService;
use App\Services\PlatformOrganisationDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Mail\OrganisationDeletedMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

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
    use Concerns\ReentersPassword;

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

        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($organisationId);

        /*
         * V1.0.51: a wrong password is written to the platform trail
         * and the route is throttled — a copied token could otherwise
         * guess it at leisure.
         */
        $this->requirePasswordReentry(
            $request,
            'organisation.delete',
            $organisation,
            'password'
        );

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

        /*
         * V1.0.51: the people whose data is about to go are told, in
         * their language. Collected before the rows are destroyed;
         * sent after, and a mail that fails is reported, never a 500
         * for a deletion that has already happened.
         */
        $administrators = User::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->where('role', 'administrator')
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get(['name', 'email']);

        $language = (string) (
            DB::table('application_settings')
                ->where('organisation_id', $organisation->id)
                ->value('language')
            ?? 'en'
        );

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

        foreach ($administrators as $administrator) {
            try {
                Mail::to($administrator->email)
                    ->locale($language)
                    ->send(
                        new OrganisationDeletedMail(
                            organisationName: $deletedName,
                            administratorName: (string) $administrator->name,
                            deletedOn: now()->toDateString(),
                        )
                    );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'message' => 'Organisation permanently deleted.',
            'rows_deleted' => array_sum($deleted),
        ]);
    }
}
