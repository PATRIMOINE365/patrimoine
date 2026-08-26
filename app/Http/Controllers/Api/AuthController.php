<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Authentication endpoints for Patrimoine application users.
 *
 * Patrimoine users are administrative accounts and remain separate from
 * domain Parties such as owners, tenants and agents.
 *
 * Sanctum personal access tokens are supported for API and future mobile
 * clients. The first-party web application may later use Sanctum's
 * stateful session authentication instead.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user and issue a Sanctum personal access token.
     *
     * The plain-text token is returned only at creation time. Sanctum
     * stores only the hashed representation in the database.
     */
    public function login(
        Request $request,
        ActivityLogService $activityLog,
        MfaService $mfa
    ): JsonResponse {

        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        /*
         * Look up the account independently from the password so the same
         * generic validation response can be returned for either an unknown
         * email address or an incorrect password.
         */
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (
            $user === null
            || ! Hash::check(
                $credentials['password'],
                $user->password
            )
        ) {
            /*
             * Failed login attempts are the explicit exception to Activity
             * Log's successful-action rule.
             *
             * Do not attach the resolved User as the actor: possession of an
             * email address does not prove that the person attempting login
             * is that User. When the account exists it is recorded only as
             * affected entity context.
             */
            $this->recordFailedLogin(
                activityLog: $activityLog,
                request: $request,
                email: $credentials['email'],
                reason: 'invalid_credentials',
                user: $user
            );

            throw ValidationException::withMessages([
                'email' => [
                    __('api.auth.invalid_credentials'),
                ],
            ]);
        }

        /*
         * Disabled accounts cannot authenticate even when the supplied
         * password is correct.
         */
        if (! $user->isActive()) {
            $this->recordFailedLogin(
                activityLog: $activityLog,
                request: $request,
                email: $credentials['email'],
                reason: 'account_disabled',
                user: $user
            );

            throw ValidationException::withMessages([
                'email' => [
                    __('api.auth.account_disabled'),
                ],
            ]);
        }

        /*
         * A newly invited User does not own the account until the invitation
         * workflow establishes a password and verifies the email address.
         */
        if ($user->email_verified_at === null) {
            /*
             * V1.0.10: a self-registered administrator still holding a
             * verification token must verify their address; an invited
             * user must complete the invitation workflow instead.
             */
            $awaitingVerification =
                $user->email_verification_token_hash !== null;

            $this->recordFailedLogin(
                activityLog: $activityLog,
                request: $request,
                email: $credentials['email'],
                reason: $awaitingVerification
                    ? 'verification_required'
                    : 'setup_required',
                user: $user
            );

            throw ValidationException::withMessages([
                'email' => [
                    $awaitingVerification
                        ? __('api.auth.verification_required')
                        : __('api.auth.setup_required'),
                ],
            ]);
        }

        /*
         * V1.0.10 multi-tenancy: a suspended organisation refuses
         * sign-in outright.
         */
        $organisation = $user->organisation;

        if ($organisation !== null && ! $organisation->isActive()) {
            $this->recordFailedLogin(
                activityLog: $activityLog,
                request: $request,
                email: $credentials['email'],
                reason: 'organisation_suspended',
                user: $user
            );

            throw ValidationException::withMessages([
                'email' => [
                    __('api.auth.organisation_suspended'),
                ],
            ]);
        }

        /*
         * V1.0.10: a correct password no longer signs the user in. Every
         * sign-in requires a six-digit code emailed to the account; the
         * browser exchanges the challenge token plus code for the API
         * token at /auth/mfa/verify.
         */
        $challenge = $mfa->start($user);

        return response()->json([
            'mfa_required' => true,
            'challenge_token' => $challenge->token,
            'email_hint' => $this->maskEmail($user->email),
        ]);
    }

    /**
     * Exchange a challenge token and emailed six-digit code for a
     * Sanctum access token. This is the second and final step of every
     * Patrimoine sign-in.
     */
    public function mfaVerify(
        Request $request,
        ActivityLogService $activityLog,
        MfaService $mfa
    ): JsonResponse {
        $validated = $request->validate([
            'challenge_token' => [
                'required',
                'string',
                'size:64',
            ],
            'code' => [
                'required',
                'string',
                'size:6',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        try {
            $user = $mfa->verify(
                $validated['challenge_token'],
                $validated['code']
            );
        } catch (ValidationException $exception) {
            /*
             * Failed code entry is a failed sign-in attempt. The
             * challenge token maps to one known user, but that user is
             * recorded only as affected entity context, exactly like a
             * wrong password.
             */
            $challengeUser = \App\Models\MfaChallenge::query()
                ->where('token', $validated['challenge_token'])
                ->first()
                ?->user;

            if ($challengeUser !== null) {
                $this->recordFailedLogin(
                    activityLog: $activityLog,
                    request: $request,
                    email: $challengeUser->email,
                    reason: 'mfa_code_invalid',
                    user: $challengeUser
                );
            }

            throw $exception;
        }

        $tokenName = $validated['device_name']
            ?? 'patrimoine-api';

        $token = $user->createToken($tokenName);

        /*
         * Successful authentication is one meaningful human action.
         * The password, code and access token are deliberately excluded.
         */
        $activityLog->record(
            action: 'auth.login',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'user' => $this->serializeUser($user),
        ]);
    }

    /**
     * Email a fresh code for a pending sign-in challenge.
     *
     * Always answers 200 so the endpoint cannot be used to probe which
     * challenge tokens are live.
     */
    public function mfaResend(
        Request $request,
        MfaService $mfa
    ): JsonResponse {
        $validated = $request->validate([
            'challenge_token' => [
                'required',
                'string',
                'size:64',
            ],
        ]);

        $mfa->resend($validated['challenge_token']);

        return response()->json([
            'message' => __('api.auth.mfa_code_resent'),
        ]);
    }

    /**
     * Obscure an email address for on-screen hints, keeping only the
     * first character of the local part and domain name.
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(
            explode('@', $email, 2),
            2,
            ''
        );

        $maskPart = static function (string $part): string {
            if (mb_strlen($part) <= 1) {
                return $part.'****';
            }

            return mb_substr($part, 0, 1).'****';
        };

        $domainName = $domain;
        $domainTail = '';

        $lastDot = mb_strrpos($domain, '.');

        if ($lastDot !== false) {
            $domainName = mb_substr($domain, 0, $lastDot);
            $domainTail = mb_substr($domain, $lastDot);
        }

        return $maskPart($local)
            .'@'
            .$maskPart($domainName)
            .$domainTail;
    }

    /**
     * Return the currently authenticated Patrimoine user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(
            $this->serializeUser($user)
        );
    }


    /**
     * Update the currently authenticated user's own profile.
     *
     * Role and active status cannot be changed through this endpoint.
     * A password change additionally requires the user's current password.
     */
    public function updateMe(
        Request $request,
        ActivityLogService $activityLog
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            /*
             * V1.0.7 structured names: the profile edits given_names +
             * surname; plain name stays accepted when no surname is sent.
             */
            'name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:surname',
            ],

            'given_names' => [
                'nullable',
                'string',
                'max:255',
            ],

            'surname' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore($user->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'current_password' => [
                'nullable',
                'required_with:password',
                'string',
            ],

            'password' => [
                'nullable',
                'required_with:current_password',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $changingPassword =
            filled(
                $validated['password']
                ?? null
            );

        /*
         * Verify ownership before changing anything.
         *
         * This guarantees that an incorrect current password cannot result
         * in either a password change or partial profile changes.
         */
        if (
            $changingPassword
            && ! Hash::check(
                $validated['current_password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' => [
                    __(
                        'api.password.current_password_incorrect'
                    ),
                ],
            ]);
        }

        $before = [
            'name' =>
                $user->name,

            'email' =>
                $user->email,

            'phone' =>
                $user->phone,
        ];

        DB::transaction(
            function () use (
                $user,
                $validated,
                $changingPassword
            ): void {
                /*
                 * Structured parts win; the model recomposes name on save.
                 * A plain name (no surname sent) is applied directly.
                 */
                if (filled($validated['surname'] ?? null)) {
                    $user->given_names =
                        $validated['given_names'] ?? null;

                    $user->surname =
                        $validated['surname'];
                } elseif (filled($validated['name'] ?? null)) {
                    $user->name =
                        $validated['name'];
                }

                $user->email =
                    mb_strtolower(
                        trim(
                            $validated['email']
                        )
                    );

                $user->phone =
                    $validated['phone']
                    ?? null;

                if ($changingPassword) {
                    /*
                     * User model password casting performs the hash.
                     */
                    $user->password =
                        $validated['password'];
                }

                $user->save();
            }
        );

        $user->refresh();

        $after = [
            'name' =>
                $user->name,

            'email' =>
                $user->email,

            'phone' =>
                $user->phone,
        ];

        $changedBefore = [];
        $changedAfter = [];

        foreach (
            $before
            as $field => $value
        ) {
            if (
                $value
                !== $after[$field]
            ) {
                $changedBefore[$field] =
                    $value;

                $changedAfter[$field] =
                    $after[$field];
            }
        }

        /*
         * Password values are deliberately never written to Activity Log.
         *
         * Continue using the established user.updated action so presentation
         * and export behaviour remain compatible with V1.0.3.
         */
        if (
            $changedBefore !== []
            || $changingPassword
        ) {
            $activityLog->record(
                action: 'user.updated',
                request: $request,
                entityType: 'user',
                entityId: $user->id,
                entityLabel: $user->name,
                before: $changedBefore,
                after: $changedAfter,
                metadata:
                    $changingPassword
                        ? [
                            'password_changed' =>
                                true,
                        ]
                        : []
            );
        }

        if ($changingPassword) {
            /*
             * Password ownership has changed. Revoke existing sessions,
             * including the token making this request.
             */
            $user->tokens()->delete();
        }

        return response()->json(
            $this->serializeUser(
                $user
            )
        );
    }

    /**
     * Mark the current Patrimoine release announcement as seen.
     *
     * Release acknowledgement is presentation state rather than a
     * meaningful business action, so it is deliberately not written to
     * Activity Log.
     */
    public function acknowledgeReleaseNotification(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $currentRelease =
            (string) config(
                'patrimoine.release'
            );

        if (
            $user->last_seen_release
            !== $currentRelease
        ) {
            $user->last_seen_release =
                $currentRelease;

            $user->save();
        }

        return response()->json([
            'current_release' =>
                $currentRelease,

            'last_seen_release' =>
                $user->last_seen_release,

            'has_unread_release_notification' =>
                false,
        ]);
    }

    /**
     * Revoke the Sanctum token used for the current API request.
     */
    /**
     * V1.0.8: re-verify the CURRENT user's password before an
     * irreversible action. The browser calls this from the shared
     * danger-confirmation dialog immediately before a DELETE.
     */
    public function confirmPassword(
        \Illuminate\Http\Request $request
    ): \Illuminate\Http\JsonResponse {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (
            ! \Illuminate\Support\Facades\Hash::check(
                $validated['password'],
                $request->user()->password
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => [
                    __('api.auth.password_confirmation_failed'),
                ],
            ]);
        }

        return response()->json([
            'confirmed' => true,
        ]);
    }

    public function logout(
        Request $request,
        ActivityLogService $activityLog
    ): JsonResponse {

        /** @var User $user */
        $user = $request->user();

        /*
         * API-token authentication provides a current access token.
         * The null-safe operator also keeps this endpoint compatible with
         * future stateful Sanctum authentication.
         */
        $user->currentAccessToken()?->delete();

        /*
         * Token revocation is the consequence of logout, not a separate
         * Activity Log event.
         */
        $activityLog->record(
            action: 'auth.logout',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
        );

        return response()->json([
            'message' => __('api.auth.logged_out'),
        ]);
    }

    /**
     * Record one unsuccessful authentication attempt without attributing
     * the attempt to an authenticated User.
     *
     * The submitted password is deliberately unavailable to this method and
     * must never be written to Activity Log.
     */
    private function recordFailedLogin(
        ActivityLogService $activityLog,
        Request $request,
        string $email,
        string $reason,
        ?User $user = null
    ): void {
        $activityLog->record(
            action: 'auth.login_failed',
            request: $request,
            actorEmail: mb_strtolower(
                trim($email)
            ),
            entityType: $user === null
                    ? null
                    : 'user',
            entityId: $user?->id,
            entityLabel: $user?->name,
            metadata: [
                'reason' => $reason,
            ],
            organisationId: $user === null
                ? null
                : (int) $user->organisation_id,
        );
    }

    /**
     * Produce the public API representation of an application user.
     *
     * Authentication responses deliberately expose only the fields needed
     * by clients and never return password or remember-token information.
     *
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
