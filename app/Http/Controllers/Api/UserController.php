<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserAdministrationService;
use App\Services\UserInvitationService;
use App\Services\UserPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Administrator-only REST API for Patrimoine application Users.
 *
 * Application Users are authentication/authorization identities and remain
 * separate from domain Parties such as owners, tenants and agents.
 */
class UserController extends Controller
{
    /**
     * Return a searchable and filterable paginated User directory.
     *
     * Supported filters:
     * - role;
     * - active status;
     * - free-text search across name, email and phone.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $role = UserRole::tryFrom(
                $request->string('role')->toString()
            );

            /*
             * Invalid role filters simply produce no matches rather than
             * accidentally broadening an Administrator's search.
             */
            if ($role === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('role', $role->value);
            }
        }

        if ($request->has('is_active')) {
            $active = filter_var(
                $request->input('is_active'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($active === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('is_active', $active);
            }
        }

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        $users = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(
                perPage: min(
                    max(
                        (int) $request->input(
                            'per_page',
                            25
                        ),
                        1
                    ),
                    100
                )
            );

        return response()->json($users);
    }

    /**
     * Create a User identity.
     *
     * Activity F will send the invitation and provide the real password-setup
     * workflow. Until that occurs, a cryptographically random internal
     * password makes the account unusable with any known default credential.
     */
    public function store(
        StoreUserRequest $request,
        UserInvitationService $invitations,
        ActivityLogService $activityLog,
        \App\Services\LicensingService $licensing
    ): JsonResponse {
        $validated = $request->validated();

        /*
         * V1.0.10 licensing: the plan bounds ACTIVE internal users.
         */
        if ($validated['is_active'] ?? true) {
            $licensing->assertCanAddUser();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,

            /*
             * Never create a predictable/default password.
             *
             * User password ownership begins with Activity F's secure
             * invitation/password-setup flow.
             */
            'password' => Str::random(64),

            /*
             * Invitation acceptance, not account creation, will establish
             * verified ownership of the email address.
             */
            'email_verified_at' => null,
        ]);

        /*
         * Creating a User starts the secure first-password ownership flow.
         * No Administrator-selected or default password is disclosed.
         */
        $invitations->send($user);

        /*
         * Account creation and its initial invitation are one human action.
         * Internal password/token material is never included in the snapshot.
         */
        $activityLog->record(
            action: 'user.created',
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            snapshot: $this->activitySnapshot($user),
            metadata: [
                'invitation_sent' => true,
            ],
        );

        return response()->json(
            data: $user,
            status: 201
        );
    }

    /**
     * Resend a User's password-setup invitation.
     *
     * UserInvitationService atomically invalidates every previous usable
     * invitation before issuing the new 24-hour link.
     */
    public function resendInvitation(
        Request $request,
        User $user,
        UserInvitationService $invitations,
        ActivityLogService $activityLog
    ): JsonResponse {
        $invitations->send($user);

        $activityLog->record(
            action: 'user.invitation_resent',
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            snapshot: $this->activitySnapshot($user),
        );

        return response()->json([
            'message' => __(
                'api.user_invitation.resent'
            ),
        ]);
    }

    /**
     * Initiate the normal password-reset workflow for another User.
     *
     * The Administrator never receives or chooses the User's password.
     */
    public function initiatePasswordReset(
        Request $request,
        User $user,
        UserPasswordService $passwords,
        ActivityLogService $activityLog
    ): JsonResponse {
        $passwords->sendResetLink(
            $user->email
        );

        /*
         * The meaningful event is the Administrator initiating the workflow.
         * Password reset tokens and passwords are never logged.
         */
        $activityLog->record(
            action: 'user.password_reset_requested',
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            snapshot: $this->activitySnapshot($user),
        );

        return response()->json([
            'message' => __(
                'api.password.administrator_reset_requested'
            ),
        ]);
    }

    /**
     * Return one application User.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    /**
     * Update User identity, role and active status.
     *
     * Identity fields are updated directly while security-sensitive role and
     * active-status changes pass through UserAdministrationService.
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        UserAdministrationService $administration,
        ActivityLogService $activityLog
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validated();

        /*
         * Freeze the original public User state before any part of the
         * compound update executes. One HTTP update produces at most one
         * Activity Log event regardless of how many fields changed.
         */
        $beforeSnapshot = $this->activitySnapshot(
            $user->fresh()
        );

        $updated = DB::transaction(
            function () use (
                $validated,
                $user,
                $actor,
                $administration
            ): User {
                $identity = array_intersect_key(
                    $validated,
                    array_flip([
                        'name',
                        'email',
                        'phone',
                    ])
                );

                if ($identity !== []) {
                    $user->update($identity);
                    $user->refresh();
                }

                if (array_key_exists('role', $validated)) {
                    $updatedRole = $validated['role'];

                    if (! $updatedRole instanceof UserRole) {
                        $updatedRole = UserRole::from(
                            $updatedRole
                        );
                    }

                    $user = $administration->changeRole(
                        $actor,
                        $user,
                        $updatedRole
                    );
                }

                if (
                    array_key_exists(
                        'is_active',
                        $validated
                    )
                ) {
                    $user =
                        $administration
                            ->changeActiveStatus(
                                $actor,
                                $user,
                                (bool) $validated[
                                    'is_active'
                                ]
                            );
                }

                return $user->refresh();
            }
        );

        $afterSnapshot = $this->activitySnapshot(
            $updated
        );

        [$before, $after] = $this->activityChanges(
            $beforeSnapshot,
            $afterSnapshot
        );

        /*
         * A no-op PATCH is not a meaningful human action and therefore does
         * not create Activity Log noise.
         */
        if ($before !== []) {
            $activityLog->record(
                action: 'user.updated',
                request: $request,
                entityType: 'user',
                entityId: $updated->id,
                entityLabel: $updated->name,
                before: $before,
                after: $after,
            );
        }

        return response()->json($updated);
    }

    /**
     * Delete another User through the central Administrator safeguards.
     */
    public function destroy(
        Request $request,
        User $user,
        UserAdministrationService $administration,
        ActivityLogService $activityLog
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        /*
         * Preserve the target identity before deletion. The Activity Log row
         * intentionally has no foreign-key dependency on the deleted User.
         */
        $targetId = $user->id;
        $targetLabel = $user->name;
        $snapshot = $this->activitySnapshot($user);

        $administration->delete(
            $actor,
            $user
        );

        $activityLog->record(
            action: 'user.deleted',
            actor: $actor,
            request: $request,
            entityType: 'user',
            entityId: $targetId,
            entityLabel: $targetLabel,
            snapshot: $snapshot,
        );

        return response()->json(
            data: null,
            status: 204
        );
    }

    /**
     * Return the non-secret User state suitable for historical auditing.
     *
     * @return array<string, mixed>
     */
    private function activitySnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'is_active' => $user->isActive(),
            'email_verified' => $user->email_verified_at !== null,
        ];
    }

    /**
     * Return only fields whose values actually changed.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{
     *     0: array<string, mixed>,
     *     1: array<string, mixed>
     * }
     */
    private function activityChanges(
        array $before,
        array $after
    ): array {
        $changedBefore = [];
        $changedAfter = [];

        foreach ($before as $field => $value) {
            if (($after[$field] ?? null) === $value) {
                continue;
            }

            $changedBefore[$field] = $value;
            $changedAfter[$field] =
                $after[$field] ?? null;
        }

        return [
            $changedBefore,
            $changedAfter,
        ];
    }
}
