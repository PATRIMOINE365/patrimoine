<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CustomerAccountClosedMail;
use App\Mail\OrganisationClosedMail;
use App\Models\Organisation;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\PlatformOrganisationDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Closing your own account.
 *
 * The privacy policy promises that an organisation can have everything it
 * holds destroyed on request. Until now that request had to be made to
 * Kality staff, who did it from the console. This is the same destruction,
 * asked for by the people whose data it is.
 *
 * It is irreversible, and it takes the leases, the invoices, the payments
 * and the journal with it. Three things are required before it runs:
 *
 * 1. the caller is an administrator of the organisation;
 * 2. the organisation's exact name is typed back;
 * 3. the administrator re-enters their own password.
 *
 * The customer's own activity log dies with the rest of their data — that
 * is what deletion means — so the record of the closure is written to the
 * platform organisation's log instead, and Kality is told by e-mail.
 */
class OrganisationDeletionController extends Controller
{
    /**
     * Permanently destroy the caller's own organisation.
     */
    public function destroy(
        Request $request,
        PlatformOrganisationDeletionService $deletion,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated = $request->validate([
            'name_confirmation' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        /** @var User $administrator */
        $administrator = $request->user();

        $organisation = Organisation::withoutGlobalScopes()
            ->findOrFail($administrator->organisation_id);

        /*
         * Kality's own organisation runs the platform. Deleting it from a
         * customer-facing screen would take the console, the staff accounts
         * and every audit record with it.
         */
        if ($organisation->isPlatform()) {
            throw ValidationException::withMessages([
                'organisation' => [
                    __('api.organisation.platform_cannot_close'),
                ],
            ]);
        }

        if (! Hash::check($validated['password'], $administrator->password)) {
            throw ValidationException::withMessages([
                'password' => [
                    __('api.auth.password_confirmation_failed'),
                ],
            ]);
        }

        /*
         * Compared exactly. Somebody who cannot reproduce the name in front
         * of them is not somebody who should be doing this.
         */
        if ($validated['name_confirmation'] !== $organisation->name) {
            throw ValidationException::withMessages([
                'name_confirmation' => [
                    __('api.organisation.name_confirmation_mismatch'),
                ],
            ]);
        }

        $closed = [
            'id' => (int) $organisation->id,
            'name' => (string) $organisation->name,
            'administrator' => (string) $administrator->name,
            'email' => (string) $administrator->email,
            'role' => $administrator->role->value,
            'ip' => $request->ip(),
            'agent' => $request->userAgent(),
        ];

        $platform = Organisation::withoutGlobalScopes()
            ->where('is_platform', true)
            ->first();

        $deleted = $deletion->destroy($organisation);

        /*
         * Written after the fact, and to somebody else's log, because the
         * organisation that would have owned this record no longer exists.
         *
         * The actor is passed as a snapshot rather than as a User. The
         * account that did this was deleted a line ago, so a live link to
         * it would be a foreign key pointing at nothing — the identity is
         * preserved the way every other frozen audit field is.
         */
        if ($platform !== null) {
            $activityLog->record(
                action: 'organisation.closed_by_customer',
                entityType: 'organisation',
                entityId: $closed['id'],
                entityLabel: $closed['name'],
                metadata: [
                    'closed_by' => $closed['administrator'],
                    'closed_by_email' => $closed['email'],
                    'rows_deleted' => array_sum($deleted),
                    'tables' => $deleted,
                ],
                actorName: $closed['administrator'],
                actorEmail: $closed['email'],
                actorRole: $closed['role'],
                ipAddress: $closed['ip'],
                userAgent: $closed['agent'],
                organisationId: (int) $platform->id,
            );
        }

        /*
         * The data is already gone. A mail that will not send is Kality's
         * problem to notice, never a 500 for somebody who has just closed
         * their account and has nothing left to retry with.
         */
        try {
            Mail::to(
                (string) config(
                    'legal.mailboxes.hello',
                    'hello@patrimoine365.com'
                )
            )->send(
                new CustomerAccountClosedMail(
                    organisationName: $closed['name'],
                    administratorName: $closed['administrator'],
                    administratorEmail: $closed['email'],
                    rowsDeleted: array_sum($deleted),
                )
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        /*
         * V1.0.50: the person who closed it is told too. Until now only
         * Kality heard; the administrator was left with a sign-out and
         * no written word that it had happened. Same rule as above: a
         * mail that fails is reported, never a 500.
         */
        try {
            Mail::to($closed['email'])->send(
                new OrganisationClosedMail(
                    organisationName: $closed['name'],
                    administratorName: $closed['administrator'],
                    closedOn: now()->toDateString(),
                )
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => __('api.organisation.closed'),
            'rows_deleted' => array_sum($deleted),
        ]);
    }
}
