<?php

namespace App\Services;

use App\Mail\MfaCodeMail;
use App\Models\MfaChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Email-based multi-factor authentication.
 *
 * Every sign-in requires a six-digit code delivered to the account's
 * email address. Codes are single-use, stored hashed, expire after
 * MfaChallenge::CODE_LIFETIME_MINUTES and die after
 * MfaChallenge::MAX_ATTEMPTS wrong answers.
 */
class MfaService
{
    /**
     * Open a fresh challenge for the user and email them the code.
     *
     * Any previous unconsumed challenges are invalidated so exactly one
     * code is ever valid per account.
     */
    public function start(User $user): MfaChallenge
    {
        $code = $this->generateCode();

        $challenge = DB::transaction(
            function () use ($user, $code): MfaChallenge {
                MfaChallenge::query()
                    ->where('user_id', $user->id)
                    ->whereNull('consumed_at')
                    ->update([
                        'consumed_at' => now(),
                    ]);

                return MfaChallenge::create([
                    'user_id' => $user->id,
                    'token' => Str::random(64),
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(
                        MfaChallenge::CODE_LIFETIME_MINUTES
                    ),
                    'attempts' => 0,
                ]);
            }
        );

        Mail::to($user->email)->send(
            new MfaCodeMail(
                user: $user,
                code: $code
            )
        );

        return $challenge;
    }

    /**
     * Answer a challenge. Returns the authenticated user on success.
     *
     * @throws ValidationException with a localized, non-enumerating
     *         message on any failure
     */
    public function verify(string $token, string $code): User
    {
        $challenge = MfaChallenge::query()
            ->where('token', $token)
            ->first();

        if ($challenge === null || ! $challenge->isUsable()) {
            throw ValidationException::withMessages([
                'code' => [
                    __('api.auth.mfa_challenge_expired'),
                ],
            ]);
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');

            throw ValidationException::withMessages([
                'code' => [
                    $challenge->refresh()->isUsable()
                        ? __('api.auth.mfa_code_invalid')
                        : __('api.auth.mfa_challenge_expired'),
                ],
            ]);
        }

        $challenge->forceFill([
            'consumed_at' => now(),
        ])->save();

        $user = $challenge->user;

        if ($user === null || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'code' => [
                    __('api.auth.mfa_challenge_expired'),
                ],
            ]);
        }

        return $user;
    }

    /**
     * Email a fresh code for an existing, still-usable challenge.
     *
     * The challenge keeps its token and attempt count; only the code
     * and its expiry are renewed.
     */
    public function resend(string $token): void
    {
        $challenge = MfaChallenge::query()
            ->where('token', $token)
            ->whereNull('consumed_at')
            ->first();

        if (
            $challenge === null
            || $challenge->attempts >= MfaChallenge::MAX_ATTEMPTS
        ) {
            /*
             * Silently ignore: resend must not disclose whether a
             * token corresponds to a live sign-in attempt.
             */
            return;
        }

        $user = $challenge->user;

        if ($user === null || ! $user->isActive()) {
            return;
        }

        $code = $this->generateCode();

        $challenge->forceFill([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(
                MfaChallenge::CODE_LIFETIME_MINUTES
            ),
        ])->save();

        Mail::to($user->email)->send(
            new MfaCodeMail(
                user: $user,
                code: $code
            )
        );
    }

    /**
     * A uniformly random six-digit code, leading zeros allowed.
     */
    private function generateCode(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
