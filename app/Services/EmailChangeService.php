<?php

namespace App\Services;

use App\Mail\EmailChangeCompletedMail;
use App\Mail\EmailChangeCurrentCodeMail;
use App\Mail\EmailChangeProposedCodeMail;
use App\Models\EmailChangeRequest;
use App\Models\MfaChallenge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The one way a sign-in email address changes.
 *
 * A stolen bearer token used to be enough to rewrite the login email —
 * and the replacement inherited verified status, so sign-in codes and
 * password resets immediately went to the thief, and the real owner's
 * recovery pointed at somebody else's mailbox. This flow puts two
 * independent barriers in the way: the current password, and a code the
 * OLD mailbox must answer, before the NEW mailbox proves itself and the
 * account is finally touched.
 *
 * Rules the implementation must keep:
 *
 *  - the user's row is untouched until the final step, so the old
 *    address keeps working — and keeps receiving recovery mail — for as
 *    long as the change is pending;
 *  - a proposed address has NO account authority: nothing outside this
 *    service ever reads one;
 *  - one pending change per user — a new request supersedes the old;
 *  - both codes bind to this exact request; changing the address starts
 *    a fresh request with fresh proof;
 *  - completion re-checks availability and the staff-domain rule, swaps
 *    the address, marks it verified and rotates every session — in one
 *    transaction;
 *  - the old mailbox is told at the start and at the end, so an owner
 *    who did not ask finds out while it still matters.
 *
 * Every path that changes an email goes through here: the profile flow
 * below and the platform console's support path both end in apply().
 */
class EmailChangeService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly AccessTokenService $accessTokens,
    ) {}

    /**
     * Step 1 — open a request: current password + the proposed address.
     *
     * Any previous pending request dies here, whatever step it was on:
     * exactly one change is ever in flight per user, and proof gathered
     * for an earlier address can never carry over.
     *
     * @return EmailChangeRequest the open request, token included
     *
     * @throws ValidationException on a wrong password, an address that
     *         is unavailable, or one the domain rule refuses
     */
    public function initiate(
        User $user,
        string $proposedEmail,
        string $currentPassword,
        ?Request $request = null
    ): EmailChangeRequest {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [
                    __('api.auth.password_confirmation_failed'),
                ],
            ]);
        }

        $proposedEmail = $this->normalize($proposedEmail);

        $this->assertAddressUsable($user, $proposedEmail);

        $code = $this->generateCode();

        $change = DB::transaction(
            function () use ($user, $proposedEmail, $code): EmailChangeRequest {
                $this->supersedeOpenRequests($user);

                return EmailChangeRequest::create([
                    'user_id' => $user->id,
                    'token' => Str::random(64),
                    'proposed_email' => $proposedEmail,
                    'current_code_hash' => Hash::make($code),
                    'code_expires_at' => now()->addMinutes(
                        EmailChangeRequest::CODE_LIFETIME_MINUTES
                    ),
                    'attempts' => 0,
                    'resends' => 0,
                    'last_sent_at' => now(),
                ]);
            }
        );

        /*
         * The code goes to the CURRENT mailbox, and the mail names the
         * proposed replacement: the person authorising the change sees
         * exactly what they are authorising, and an owner who never
         * asked learns somebody is trying — with the code they need
         * withheld from the thief, since it is in the victim's inbox.
         */
        Mail::to($user->email)->send(
            new EmailChangeCurrentCodeMail(
                user: $user,
                proposedEmail: $proposedEmail,
                code: $code
            )
        );

        $this->activityLog->record(
            action: 'user.email_change_requested',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            metadata: [
                'proposed_email' => $proposedEmail,
            ],
        );

        return $change;
    }

    /**
     * Step 2 — the CURRENT mailbox answers its code.
     *
     * On success the code for the PROPOSED mailbox is minted and sent,
     * and the clock restarts for it.
     */
    public function verifyCurrentMailbox(
        User $user,
        string $token,
        string $code
    ): EmailChangeRequest {
        $change = $this->openRequest($user, $token);

        if (! $change->awaitingCurrentMailbox()) {
            throw ValidationException::withMessages([
                'code' => [__('api.email_change.request_expired')],
            ]);
        }

        $this->assertCodeAnswers(
            $change,
            $code,
            $change->current_code_hash
        );

        $proposedCode = $this->generateCode();

        $change->forceFill([
            'current_verified_at' => now(),
            'proposed_code_hash' => Hash::make($proposedCode),
            'code_expires_at' => now()->addMinutes(
                EmailChangeRequest::CODE_LIFETIME_MINUTES
            ),
            'last_sent_at' => now(),
        ])->save();

        Mail::to($change->proposed_email)->send(
            new EmailChangeProposedCodeMail(
                user: $user,
                proposedEmail: $change->proposed_email,
                code: $proposedCode
            )
        );

        return $change;
    }

    /**
     * Step 3 — the PROPOSED mailbox answers, and the change happens.
     *
     * Availability and the domain rule are checked AGAIN inside the
     * transaction: the world may have moved since step 1, and the swap
     * must not land on an address that is no longer allowed.
     *
     * Every session dies with the old address — including whichever
     * leaked copy of the current token may have started all this — and
     * one fresh token is minted for the device completing the flow.
     *
     * @return array{change: EmailChangeRequest, token: string}
     */
    public function verifyProposedMailbox(
        User $user,
        string $token,
        string $code,
        ?Request $request = null
    ): array {
        $change = $this->openRequest($user, $token);

        if (! $change->awaitingProposedMailbox()) {
            throw ValidationException::withMessages([
                'code' => [__('api.email_change.request_expired')],
            ]);
        }

        $this->assertCodeAnswers(
            $change,
            $code,
            (string) $change->proposed_code_hash
        );

        $previousEmail = $user->email;

        $plainToken = DB::transaction(
            function () use ($user, $change, $request): string {
                $this->assertAddressUsable(
                    $user,
                    $change->proposed_email
                );

                $this->apply($user, $change->proposed_email);

                $change->forceFill([
                    'completed_at' => now(),
                ])->save();

                /*
                 * The browser that finished the flow keeps working: its
                 * old token just died with every other session, so a
                 * fresh one is minted for it in the same breath.
                 */
                return $request === null
                    ? ''
                    : $this->accessTokens
                        ->issue($user, $request)
                        ->plainTextToken;
            }
        );

        /*
         * Told after commit: mail failure must never undo a completed
         * change, and a completed change must never go untold.
         */
        Mail::to($previousEmail)->send(
            new EmailChangeCompletedMail(
                user: $user,
                previousEmail: $previousEmail,
                newEmail: $change->proposed_email
            )
        );

        $this->activityLog->record(
            action: 'user.email_changed',
            actor: $user,
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            before: ['email' => $previousEmail],
            after: ['email' => $change->proposed_email],
        );

        return [
            'change' => $change,
            'token' => $plainToken,
        ];
    }

    /**
     * Send the outstanding stage's code again.
     *
     * The same stage, a fresh code, a renewed expiry — never a second
     * live code for one stage. Cooldown and a lifetime cap keep the flow
     * from being a mail cannon pointed at somebody's inbox.
     */
    public function resend(User $user, string $token): EmailChangeRequest
    {
        $change = $this->openRequest($user, $token);

        if (
            $change->last_sent_at !== null
            && $change->last_sent_at
                ->addSeconds(EmailChangeRequest::RESEND_COOLDOWN_SECONDS)
                ->isFuture()
        ) {
            throw ValidationException::withMessages([
                'code' => [__('api.email_change.resend_cooldown')],
            ]);
        }

        if ($change->resends >= EmailChangeRequest::MAX_RESENDS) {
            throw ValidationException::withMessages([
                'code' => [__('api.email_change.resend_limit')],
            ]);
        }

        $code = $this->generateCode();

        $stage = $change->awaitingCurrentMailbox()
            ? 'current_code_hash'
            : 'proposed_code_hash';

        $change->forceFill([
            $stage => Hash::make($code),
            'code_expires_at' => now()->addMinutes(
                EmailChangeRequest::CODE_LIFETIME_MINUTES
            ),
            'resends' => $change->resends + 1,
            'last_sent_at' => now(),
        ])->save();

        if ($change->awaitingCurrentMailbox()) {
            Mail::to($user->email)->send(
                new EmailChangeCurrentCodeMail(
                    user: $user,
                    proposedEmail: $change->proposed_email,
                    code: $code
                )
            );
        } else {
            Mail::to($change->proposed_email)->send(
                new EmailChangeProposedCodeMail(
                    user: $user,
                    proposedEmail: $change->proposed_email,
                    code: $code
                )
            );
        }

        return $change;
    }

    /**
     * Close the user's open request, if one is open.
     *
     * Cancelling is not an error when nothing is pending: the outcome
     * the user asked for — no pending change — is already true.
     */
    public function cancel(User $user): void
    {
        EmailChangeRequest::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->update([
                'cancelled_at' => now(),
                'cancelled_reason' => 'user',
            ]);
    }

    /**
     * The user's currently open request, for the interface to resume.
     */
    public function pendingFor(User $user): ?EmailChangeRequest
    {
        $change = EmailChangeRequest::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->latest('id')
            ->first();

        return $change !== null && $change->isUsable()
            ? $change
            : null;
    }

    /**
     * Actually swap the address — the ONE place a sign-in email is
     * written.
     *
     * The new address is marked verified as it lands: whoever reached
     * here proved control of it (the flow above) or is platform staff
     * acting on a verified support request (the console path). Every
     * session dies with the old address, along with any open sign-in
     * challenge and any password-reset token addressed to it.
     */
    public function apply(User $user, string $newEmail): void
    {
        $newEmail = $this->normalize($newEmail);

        $previousEmail = (string) $user->email;

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ])->save();

        $user->tokens()->delete();

        MfaChallenge::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        /*
         * A reset link mailed to the old address must not survive the
         * change: it was addressed to a mailbox that no longer owns the
         * account. The table is keyed by email, framework-managed.
         */
        DB::table('password_reset_tokens')
            ->whereIn('email', [$newEmail, $previousEmail])
            ->delete();
    }

    /**
     * The console's support path: platform staff set the address for a
     * user who cannot reach their old mailbox.
     *
     * Same one write path as the self-service flow, same notification
     * to the old address. Availability and the domain rule hold here
     * too — support is a bypass of the mailbox proofs, never of the
     * invariants.
     */
    public function applyForSupport(
        User $user,
        string $newEmail
    ): void {
        $newEmail = $this->normalize($newEmail);

        $this->assertAddressUsable($user, $newEmail);

        $previousEmail = $user->email;

        DB::transaction(function () use ($user, $newEmail): void {
            $this->supersedeOpenRequests($user);

            $this->apply($user, $newEmail);
        });

        /*
         * Both addresses hear: the old one because it must be able to
         * raise the alarm, the new one because the person who wrote to
         * support needs to know their request is done.
         */
        foreach ([$previousEmail, $newEmail] as $address) {
            Mail::to($address)->send(
                new EmailChangeCompletedMail(
                    user: $user,
                    previousEmail: $previousEmail,
                    newEmail: $newEmail
                )
            );
        }
    }

    /**
     * Refuse an address the account cannot move to.
     *
     * Told plainly when the address is taken: Komla chose helpfulness
     * over enumeration-hiding here — the caller is an authenticated
     * user changing their own address, not an anonymous probe.
     */
    private function assertAddressUsable(
        User $user,
        string $email
    ): void {
        if ($email === $this->normalize($user->email)) {
            throw ValidationException::withMessages([
                'email' => [__('api.email_change.same_address')],
            ]);
        }

        $taken = User::withoutGlobalScopes()
            ->where('email', $email)
            ->whereKeyNot($user->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'email' => [__('api.email_change.address_taken')],
            ]);
        }

        /*
         * The platform domain is load-bearing: staff may not leave it —
         * that would silently revoke console access — and a customer
         * may never take it. Checked at initiation AND at completion.
         */
        $isPlatformDomain = str_ends_with(
            $email,
            '@'.User::PLATFORM_EMAIL_DOMAIN
        );

        $isPlatformMember =
            (bool) $user->organisation?->is_platform;

        if ($isPlatformMember && ! $isPlatformDomain) {
            throw ValidationException::withMessages([
                'email' => [
                    __('api.user_management.platform_domain_required'),
                ],
            ]);
        }

        if (! $isPlatformMember && $isPlatformDomain) {
            throw ValidationException::withMessages([
                'email' => [
                    __('api.user_management.platform_domain_reserved'),
                ],
            ]);
        }
    }

    /**
     * The user's open request for this token, or a refusal that says
     * nothing about why — an expired, cancelled, superseded and
     * never-existed request all read the same from outside.
     */
    private function openRequest(
        User $user,
        string $token
    ): EmailChangeRequest {
        $change = EmailChangeRequest::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->first();

        if ($change === null || ! $change->isUsable()) {
            throw ValidationException::withMessages([
                'code' => [__('api.email_change.request_expired')],
            ]);
        }

        return $change;
    }

    /**
     * Count a wrong code against the request, killing it on the third.
     */
    private function assertCodeAnswers(
        EmailChangeRequest $change,
        string $code,
        string $hash
    ): void {
        if (Hash::check($code, $hash)) {
            return;
        }

        $change->increment('attempts');
        $change->refresh();

        if ($change->attempts >= EmailChangeRequest::MAX_ATTEMPTS) {
            $change->forceFill([
                'cancelled_at' => now(),
                'cancelled_reason' => 'attempts',
            ])->save();

            throw ValidationException::withMessages([
                'code' => [__('api.email_change.request_expired')],
            ]);
        }

        throw ValidationException::withMessages([
            'code' => [__('api.email_change.code_invalid')],
        ]);
    }

    /**
     * Close every open request so exactly one is ever pending.
     */
    private function supersedeOpenRequests(User $user): void
    {
        EmailChangeRequest::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->update([
                'cancelled_at' => now(),
                'cancelled_reason' => 'superseded',
            ]);
    }

    private function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
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
