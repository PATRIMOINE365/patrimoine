<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use App\Services\PlatformAuditService;
use App\Services\RegistrationService;
use App\Services\UserPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The support tools customers actually write in about: verification
 * emails, locked accounts and forgotten passwords.
 *
 * Deliberately operates only on CUSTOMER organisations — platform
 * staff manage themselves through their own Users page.
 */
class AdminSupportController extends Controller
{
    /**
     * Re-send a customer user's verification email.
     */
    public function resendVerification(
        int $userId,
        Request $request,
        RegistrationService $registration,
        PlatformAuditService $audit
    ): JsonResponse {
        [$user, $organisation] = $this->customerUser($userId);

        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'user' => ['This account is already verified.'],
            ]);
        }

        $registration->resendVerification($user->email);

        $audit->record(
            action: 'platform.verification_resent',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    /**
     * Change a customer user's role.
     *
     * Routed through UserAdministrationService like the customer's own
     * Users page, so the Administrator-coverage safeguard applies here
     * too: support cannot strip the last administrator from an
     * organisation and lock everyone out.
     */
    public function changeRole(
        int $userId,
        Request $request,
        \App\Services\UserAdministrationService $administration,
        PlatformAuditService $audit
    ): JsonResponse {
        $validated = $request->validate([
            'role' => [
                'required',
                \Illuminate\Validation\Rule::in(
                    array_column(\App\Enums\UserRole::cases(), 'value')
                ),
            ],
        ]);

        [$user, $organisation] = $this->customerUser($userId);

        $previous = $user->role instanceof \BackedEnum
            ? $user->role->value
            : (string) $user->role;

        $updated = \App\Support\OrganisationContext::runAs(
            (int) $organisation->id,
            fn (): User => $administration->changeRole(
                $request->user(),
                $user,
                \App\Enums\UserRole::from($validated['role'])
            )
        );

        $audit->record(
            action: 'platform.user_role_changed',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $updated->id,
            entityLabel: $updated->name,
            metadata: [
                'from' => $previous,
                'to' => $validated['role'],
            ],
        );

        return response()->json([
            'id' => $updated->id,
            'name' => $updated->name,
            'role' => $updated->role instanceof \BackedEnum
                ? $updated->role->value
                : (string) $updated->role,
        ]);
    }

    /**
     * Activate or deactivate a customer user.
     */
    public function setActive(
        int $userId,
        Request $request,
        PlatformAuditService $audit
    ): JsonResponse {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        [$user, $organisation] = $this->customerUser($userId);

        $activating = (bool) $validated['is_active'];

        if (! $activating) {
            /*
             * Never strand a customer: their last active administrator
             * cannot be deactivated from the console.
             */
            $otherActiveAdministrators = User::withoutGlobalScopes()
                ->where('organisation_id', $organisation->id)
                ->where('role', 'administrator')
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->exists();

            if (
                $user->role->value === 'administrator'
                && ! $otherActiveAdministrators
            ) {
                throw ValidationException::withMessages([
                    'user' => [
                        'This is the organisation\'s last active administrator.',
                    ],
                ]);
            }
        }

        $user->forceFill([
            'is_active' => $activating,
        ])->save();

        /*
         * Same lifecycle as the customer's own Users page: an account
         * created dormant is invited the moment it is activated.
         */
        if ($activating) {
            \App\Support\OrganisationContext::runAs(
                (int) $organisation->id,
                fn () => app(\App\Services\UserInvitationService::class)
                    ->sendIfNeverAccepted($user->refresh())
            );
        }

        if (! $activating) {
            /*
             * A deactivated account loses its sessions immediately.
             */
            $user->tokens()->delete();
        }

        $audit->record(
            action: $activating
                ? 'platform.user_reactivated'
                : 'platform.user_deactivated',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => $activating
                ? 'User reactivated.'
                : 'User deactivated.',
        ]);
    }

    /**
     * Send a customer user a password-reset link.
     */
    public function sendPasswordReset(
        int $userId,
        Request $request,
        UserPasswordService $passwords,
        PlatformAuditService $audit
    ): JsonResponse {
        [$user, $organisation] = $this->customerUser($userId);

        $passwords->sendResetLink($user->email);

        $audit->record(
            action: 'platform.password_reset_sent',
            admin: $request->user(),
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => 'Password reset link sent.',
        ]);
    }

    /**
     * Resolve a user belonging to a CUSTOMER organisation.
     *
     * @return array{0: User, 1: Organisation}
     */
    /**
     * Create a user inside a customer organisation, on their behalf.
     *
     * Everything runs inside OrganisationContext::runAs() so the rules
     * that depend on the tenant evaluate against the CUSTOMER, not the
     * platform: the reserved-domain guard correctly refuses an
     * @patrimoine365.com address here, and the plan's user limit is the
     * customer's own.
     */
    public function createUser(
        Request $request,
        Organisation $organisation,
        \App\Services\UserInvitationService $invitations,
        \App\Services\ActivityLogService $activityLog,
        PlatformAuditService $audit
    ): JsonResponse {
        /*
         * Platform staff manage themselves through their own Users page.
         */
        if ($organisation->is_platform) {
            throw ValidationException::withMessages([
                'organisation' => 'Platform staff are managed from the Users page, not here.',
            ]);
        }

        $actor = $request->user();

        $created = \App\Support\OrganisationContext::runAs(
            (int) $organisation->id,
            function () use (
                $request,
                $organisation,
                $invitations,
                $activityLog,
                $actor
            ): User {
                $validated = validator(
                    $request->all(),
                    [
                        'given_names' => ['nullable', 'string', 'max:255'],
                        'surname' => ['nullable', 'string', 'max:255', 'required_without:name'],
                        'name' => ['nullable', 'string', 'max:255', 'required_without:surname'],

                        'email' => [
                            'required',
                            'email',
                            'max:255',
                            \Illuminate\Validation\Rule::unique('users', 'email'),

                            /*
                             * The platform domain belongs to staff alone;
                             * a customer organisation may never recruit one.
                             */
                            function (string $attribute, mixed $value, \Closure $fail): void {
                                if (str_ends_with(
                                    mb_strtolower(trim((string) $value)),
                                    '@'.User::PLATFORM_EMAIL_DOMAIN
                                )) {
                                    $fail(__('api.user_management.platform_domain_reserved'));
                                }
                            },
                        ],

                        'phone' => ['nullable', 'string', 'max:50'],

                        'role' => [
                            'required',
                            \Illuminate\Validation\Rule::in(
                                array_column(\App\Enums\UserRole::cases(), 'value')
                            ),
                        ],

                        'is_active' => ['nullable', 'boolean'],
                    ]
                )->validate();

                $isActive = (bool) ($validated['is_active'] ?? true);

                if ($isActive) {
                    app(\App\Services\LicensingService::class)
                        ->assertCanAddUser();
                }

                /*
                 * Creating the account and inviting it are one action: a
                 * refused invitation must not leave a half-made user
                 * behind for the operator to trip over on the retry.
                 */
                return \Illuminate\Support\Facades\DB::transaction(
                    function () use (
                        $validated,
                        $isActive,
                        $invitations,
                        $activityLog,
                        $request,
                        $organisation,
                        $actor
                    ): User {
                        $user = User::create([
                            'given_names' => $validated['given_names'] ?? null,
                            'surname' => $validated['surname'] ?? null,
                            'name' => $validated['name']
                                ?? trim(
                                    ($validated['given_names'] ?? '')
                                    .' '
                                    .($validated['surname'] ?? '')
                                ),
                            'email' => $validated['email'],
                            'phone' => $validated['phone'] ?? null,
                            'role' => $validated['role'],
                            'is_active' => $isActive,
                            'password' => \Illuminate\Support\Str::random(64),
                            'email_verified_at' => null,
                        ]);

                        /*
                         * An inactive account cannot sign in, so there is
                         * nothing to invite it to yet. It is invited when
                         * somebody activates it.
                         */
                        if ($isActive) {
                            $invitations->send($user);
                        }

                        /*
                         * The customer's own activity log records what was
                         * done inside their organisation, and by whom.
                         */
                        $activityLog->record(
                            action: 'user.created',
                            actor: $actor,
                            request: $request,
                            entityType: 'user',
                            entityId: $user->id,
                            entityLabel: $user->name,
                            metadata: [
                                'performed_by_platform_staff' => true,
                                'invitation_sent' => $isActive,
                            ],
                            organisationId: (int) $organisation->id,
                        );

                        return $user;
                    }
                );
            }
        );

        $audit->record(
            action: 'platform.user_created',
            admin: $actor,
            request: $request,
            customerOrganisation: $organisation,
            entityType: 'user',
            entityId: $created->id,
            entityLabel: $created->name,
            metadata: [
                'email' => $created->email,
                'role' => $created->role instanceof \BackedEnum
                    ? $created->role->value
                    : (string) $created->role,
                'is_active' => (bool) $created->is_active,
            ],
        );

        return response()->json([
            'id' => $created->id,
            'name' => $created->name,
            'email' => $created->email,
            'is_active' => (bool) $created->is_active,
            'invitation_sent' => (bool) $created->is_active,
        ], 201);
    }

    private function customerUser(int $userId): array
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);

        $organisation = Organisation::query()
            ->customers()
            ->findOrFail($user->organisation_id);

        return [$user, $organisation];
    }
}
