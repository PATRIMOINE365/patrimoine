<?php

namespace App\Services;

use App\Mail\UserPasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Central service for V1.0.3 password ownership and reset workflows.
 *
 * Administrators may request the same reset workflow for another User, but
 * they never receive or choose that User's password.
 */
class UserPasswordService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationLocaleService $locale
    ) {}

    /**
     * Send the normal reset flow when the account is eligible.
     *
     * Public callers receive a generic success response regardless of whether
     * the address exists, preventing account enumeration.
     */
    public function sendResetLink(string $email): void
    {
        /*
         * Emails are platform-wide identities; the lookup deliberately
         * ignores any bound organisation context so the platform
         * console can serve customers of every organisation.
         */
        $user = User::withoutGlobalScopes()
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        /*
         * Do not reveal unknown, disabled, or not-yet-invited identities.
         * First-time account ownership belongs to the invitation workflow.
         */
        if (
            $user === null
            || ! $user->isActive()
            || $user->email_verified_at === null
        ) {
            return;
        }

        $token = Password::broker()->createToken($user);

        /*
         * Resolve the same Managing Organisation identity used by Patrimoine's
         * other user-facing emails. Fall back to the product name before
         * organisation setup is available.
         */
        $organisation =
            $this->identity->managingOrganisation();

        $organisationName =
            $organisation?->legal_name
            ?? $organisation?->name
            ?? 'Patrimoine';

        $url = url(
            '/reset-password?token='
            .urlencode($token)
            .'&email='
            .urlencode($user->email)
        );

        Mail::to($user->email)
            ->locale($this->locale->language())
            ->send(
                new UserPasswordResetMail(
                    user: $user,
                    resetUrl: $url,
                    organisationName: $organisationName
                )
            );
    }

    /**
     * Consume Laravel's password-broker token and establish a new password.
     */
    public function reset(
        string $email,
        string $token,
        string $password
    ): void {
        $normalizedEmail =
            mb_strtolower(trim($email));

        $status = Password::broker()->reset(
            [
                'email' => $normalizedEmail,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (
                User $user,
                string $newPassword
            ): void {
                if (! $user->isActive()) {
                    throw ValidationException::withMessages([
                        'email' => __(
                            'api.password.account_disabled'
                        ),
                    ]);
                }

                $user->password = $newPassword;
                $user->setRememberToken(
                    Str::random(60)
                );
                $user->save();

                /*
                 * A password reset invalidates all existing API sessions.
                 */
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'token' => __(
                    'api.password.invalid_reset'
                ),
            ]);
        }
    }

    /**
     * Change the authenticated User's own password.
     */
    public function changeOwnPassword(
        User $user,
        string $currentPassword,
        string $newPassword
    ): void {
        if (
            ! Hash::check(
                $currentPassword,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' => __(
                    'api.password.current_incorrect'
                ),
            ]);
        }

        $user->password = $newPassword;
        $user->setRememberToken(
            Str::random(60)
        );
        $user->save();

        /*
         * Require fresh authentication on every device after a password
         * change, including the token used to make this request.
         */
        $user->tokens()->delete();
    }
}
