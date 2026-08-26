<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\EmailVerificationMail;
use App\Models\ApplicationSetting;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\User;
use App\Services\Accounting\OpeningBalanceCutoverService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Support\OrganisationContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Provisions a brand-new Patrimoine 365 organisation from the public
 * signup page.
 *
 * One transactional unit creates:
 *
 * - the Organisation (30-day Professional trial);
 * - its managing organisation Party and settings row;
 * - its chart of accounts, with the Financial Journal activated
 *   through a zero-balance opening cutover (a new organisation has no
 *   pre-journal history to reconcile);
 * - the first Administrator, unverified, with the accepted Terms of
 *   Service and Privacy Policy versions recorded.
 *
 * The verification email is sent after the transaction commits so a
 * failed signup can never send mail for an organisation that does not
 * exist.
 */
class RegistrationService
{
    /**
     * Verification links stay valid this many hours.
     */
    public const VERIFICATION_LIFETIME_HOURS = 48;

    /**
     * Length of the introductory Professional trial, in days.
     */
    public const TRIAL_DAYS = 30;

    public function __construct(
        private readonly SystemChartOfAccounts $chartOfAccounts,
        private readonly OpeningBalanceCutoverService $openingBalanceCutover,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    /**
     * Create the organisation and its first administrator.
     *
     * @param  array{
     *     organisation_name: string,
     *     given_names: string,
     *     surname: string,
     *     email: string,
     *     phone?: string|null,
     *     password: string,
     *     language: string,
     * }  $data
     */
    public function register(array $data, Request $request): User
    {
        $email = mb_strtolower(trim($data['email']));

        $plainVerificationToken = Str::random(64);

        $user = DB::transaction(
            function () use (
                $data,
                $email,
                $plainVerificationToken,
                $request
            ): User {
                $organisation = Organisation::create([
                    'name' => trim($data['organisation_name']),
                    'status' => 'active',
                    'trial_ends_on' => now()
                        ->addDays(self::TRIAL_DAYS)
                        ->toDateString(),
                ]);

                return OrganisationContext::runAs(
                    (int) $organisation->id,
                    fn (): User => $this->provisionInsideOrganisation(
                        $organisation,
                        $data,
                        $email,
                        $plainVerificationToken,
                        $request
                    )
                );
            }
        );

        $this->sendVerificationEmail($user, $plainVerificationToken);

        /*
         * V1.0.11: tell the operating company. A failure here must
         * never cost the customer their signup.
         */
        try {
            $organisation = Organisation::query()
                ->find($user->organisation_id);

            if ($organisation !== null) {
                Mail::to(
                    (string) config('legal.mailboxes.hello', 'hello@patrimoine365.com')
                )->send(
                    new \App\Mail\SignupAlertMail(
                        organisation: $organisation,
                        administrator: $user
                    )
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $user;
    }

    /**
     * Issue a fresh verification link for a not-yet-verified account.
     *
     * Quietly does nothing for unknown or already-verified addresses so
     * the endpoint cannot be used to probe which emails exist.
     */
    public function resendVerification(string $email): void
    {
        /*
         * Emails are platform-wide identities; the lookup ignores any
         * bound organisation context so the platform console can
         * re-send verification for customers of every organisation.
         */
        $user = User::withoutGlobalScopes()
            ->where('email', mb_strtolower(trim($email)))
            ->whereNull('email_verified_at')
            ->whereNotNull('email_verification_token_hash')
            ->first();

        if ($user === null) {
            return;
        }

        $plainToken = Str::random(64);

        $user->forceFill([
            'email_verification_token_hash' => hash('sha256', $plainToken),
            'email_verification_expires_at' => now()
                ->addHours(self::VERIFICATION_LIFETIME_HOURS),
        ])->save();

        $this->sendVerificationEmail($user, $plainToken);
    }

    /**
     * Consume a verification token and mark the account verified.
     *
     * Returns the verified user, or null when the token is unknown,
     * expired or already used.
     */
    public function verifyEmail(string $plainToken, Request $request): ?User
    {
        $user = User::query()
            ->where(
                'email_verification_token_hash',
                hash('sha256', $plainToken)
            )
            ->whereNull('email_verified_at')
            ->first();

        if ($user === null) {
            return null;
        }

        if (
            $user->email_verification_expires_at === null
            || $user->email_verification_expires_at->isPast()
        ) {
            return null;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_token_hash' => null,
            'email_verification_expires_at' => null,
        ])->save();

        $this->activityLog->record(
            action: 'auth.email_verified',
            request: $request,
            entityType: 'user',
            entityId: $user->id,
            entityLabel: $user->name,
            actorName: $user->name,
            actorEmail: $user->email,
            organisationId: (int) $user->organisation_id,
        );

        return $user;
    }

    /**
     * Everything created inside the new organisation's own context.
     */
    private function provisionInsideOrganisation(
        Organisation $organisation,
        array $data,
        string $email,
        string $plainVerificationToken,
        Request $request
    ): User {
        /*
         * The managing organisation Party mirrors the business identity
         * exactly as the retired setup wizard did; the customer refines
         * legal details later under Settings.
         */
        $party = Party::create([
            'type' => 'organisation',
            'name' => $organisation->name,
            'legal_name' => $organisation->name,
            'email' => $email,
            'phone' => $data['phone'] ?? null,
        ]);

        $party->roles()->create([
            'role' => 'managing_organisation',
        ]);

        ApplicationSetting::create([
            'managing_organisation_party_id' => $party->id,
            'default_vat_rate' => 0,
            'language' => $data['language'],
            'currency' => 'GHS',
        ]);

        /*
         * A new organisation starts journal-ready: install the system
         * chart of accounts and post the (empty) opening position so
         * the Financial Journal records every transaction from day one.
         * Journal activation is best-effort — a failure here must never
         * cost the customer their signup.
         */
        $this->chartOfAccounts->install();

        try {
            $this->openingBalanceCutover->execute(
                CarbonImmutable::now()
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        $user = User::create([
            'given_names' => trim($data['given_names']),
            'surname' => trim($data['surname']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => UserRole::Administrator,
            'is_active' => true,
        ]);

        $user->forceFill([
            'email_verification_token_hash' => hash(
                'sha256',
                $plainVerificationToken
            ),
            'email_verification_expires_at' => now()
                ->addHours(self::VERIFICATION_LIFETIME_HOURS),
            'legal_accepted_at' => now(),
            'legal_terms_version' => (string) config('legal.terms_version'),
            'legal_privacy_version' => (string) config('legal.privacy_version'),
            'legal_accepted_ip' => $request->ip(),
        ])->save();

        $this->activityLog->record(
            action: 'organisation.registered',
            request: $request,
            entityType: 'organisation',
            entityId: $organisation->id,
            entityLabel: $organisation->name,
            actorName: $user->name,
            actorEmail: $user->email,
            metadata: [
                'trial_ends_on' => (string) $organisation->trial_ends_on?->toDateString(),
                'terms_version' => (string) config('legal.terms_version'),
                'privacy_version' => (string) config('legal.privacy_version'),
            ],
            organisationId: (int) $organisation->id,
        );

        return $user;
    }

    /**
     * Send (or re-send) the verification email.
     */
    private function sendVerificationEmail(
        User $user,
        string $plainToken
    ): void {
        $url = url(
            '/verify-email?token='.urlencode($plainToken)
        );

        $organisationName =
            Organisation::query()
                ->whereKey($user->organisation_id)
                ->value('name')
            ?? (string) config('legal.product.name');

        Mail::to($user->email)->send(
            new EmailVerificationMail(
                user: $user,
                verificationUrl: $url,
                organisationName: $organisationName
            )
        );
    }
}
