<?php

namespace App\Services;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Own the complete lifecycle of V1.0.3 User invitations.
 *
 * Creating/resending an invitation invalidates every previous usable link.
 * Invitation secrets are never stored in plain text.
 */
class UserInvitationService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationLocaleService $locale
    ) {
    }

    /**
     * Issue a new 24-hour invitation and send it to the User.
     *
     * @return UserInvitation newly persisted invitation
     */
    public function send(User $user): UserInvitation
    {
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'user' => __(
                    'api.user_invitation.inactive_user'
                ),
            ]);
        }

        $plainToken = Str::random(64);

        $invitation = DB::transaction(
            function () use (
                $user,
                $plainToken
            ): UserInvitation {
                /*
                 * Resending immediately invalidates all previous invitation
                 * links for this User.
                 */
                UserInvitation::query()
                    ->where('user_id', $user->id)
                    ->whereNull('accepted_at')
                    ->whereNull('invalidated_at')
                    ->update([
                        'invalidated_at' => now(),
                    ]);

                return UserInvitation::create([
                    'user_id' => $user->id,
                    'token_hash' => hash(
                        'sha256',
                        $plainToken
                    ),
                    'expires_at' => now()->addHours(24),
                ]);
            }
        );

        $organisation =
            $this->identity->managingOrganisation();

        $organisationName =
            $organisation?->legal_name
            ?? $organisation?->name
            ?? 'Patrimoine';

        /*
         * The email link targets the browser application. Activity F2 will
         * provide the corresponding screen/API consumption workflow.
         */
        $url = url(
            '/invitation?token='
            . urlencode($plainToken)
            . '&email='
            . urlencode($user->email)
        );

        Mail::to($user->email)
            ->locale($this->locale->language())
            ->send(
                new UserInvitationMail(
                    user: $user,
                    invitationUrl: $url,
                    organisationName: $organisationName
                )
            );

        return $invitation;
    }

    /**
     * Consume a valid invitation and establish the User's chosen password.
     */
    public function accept(
        string $email,
        string $plainToken,
        string $password
    ): User {
        return DB::transaction(
            function () use (
                $email,
                $plainToken,
                $password
            ): User {
                $user = User::query()
                    ->where('email', mb_strtolower(trim($email)))
                    ->lockForUpdate()
                    ->first();

                $invitation = UserInvitation::query()
                    ->where(
                        'token_hash',
                        hash('sha256', $plainToken)
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                 * Use one generic failure for invalid, expired, consumed or
                 * superseded links.
                 */
                if (
                    $user === null
                    || $invitation === null
                    || $invitation->user_id !== $user->id
                    || ! $invitation->isUsable()
                    || ! $user->isActive()
                ) {
                    throw ValidationException::withMessages([
                        'token' => __(
                            'api.user_invitation.invalid'
                        ),
                    ]);
                }

                $user->password = $password;
                $user->email_verified_at = now();
                $user->save();

                /*
                 * Password establishment invalidates any authentication token
                 * that might somehow predate invitation acceptance.
                 */
                $user->tokens()->delete();

                $invitation->accepted_at = now();
                $invitation->save();

                return $user->refresh();
            }
        );
    }
}
