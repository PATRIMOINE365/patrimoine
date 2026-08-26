<?php

namespace App\Services;

use App\Models\License;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Resolves and enforces the current organisation's Patrimoine 365 plan.
 *
 * Plan resolution order:
 *
 *   1. the newest licence row covering today;
 *   2. the introductory Professional trial while it runs;
 *   3. the Free plan.
 *
 * Enforcement philosophy (from the licensing plan document):
 *
 * - active leases are the only real licensing metric; the parties cap
 *   is an anti-abuse ceiling and history never forces an upgrade;
 * - over-quota blocks CREATING new records of the limited kind only —
 *   existing data is never locked or degraded;
 * - email caps stop automated mail first; transactional document mail
 *   keeps flowing; sign-in/MFA email is never blocked or counted.
 */
class LicensingService
{
    /**
     * The lease statuses that count against the active-lease quota.
     *
     * @var list<string>
     */
    private const COUNTED_LEASE_STATUSES = ['active', 'notice'];

    /**
     * The effective plan key for the bound organisation.
     */
    public function plan(): string
    {
        $organisation = $this->organisation();

        if ($organisation === null) {
            return (string) config('licensing.fallback_plan', 'free');
        }

        return $this->planFor($organisation);
    }

    /**
     * The effective plan of an explicit organisation — used by the
     * platform console, which reads across customers with no bound
     * organisation context.
     */
    public function planFor(Organisation $organisation): string
    {
        /*
         * V1.0.11: the internal platform organisation is not a
         * customer; its staff must never hit plan walls.
         */
        if ($organisation->isPlatform()) {
            return (string) config('licensing.trial_plan', 'professional');
        }

        $licensed = $organisation
            ->licenses
            ->first(
                fn (License $license): bool => $license->coversToday()
            );

        if ($licensed !== null) {
            return $licensed->plan;
        }

        if ($organisation->onTrial()) {
            return (string) config('licensing.trial_plan', 'professional');
        }

        return (string) config('licensing.fallback_plan', 'free');
    }

    /**
     * Whether an explicit organisation is on its trial with no
     * covering licence.
     */
    public function onTrialFor(Organisation $organisation): bool
    {
        if (! $organisation->onTrial()) {
            return false;
        }

        return $organisation
            ->licenses
            ->first(
                fn (License $license): bool => $license->coversToday()
            ) === null;
    }

    /**
     * Usage of an explicit organisation, bypassing the bound context.
     *
     * @return array<string, int>
     */
    public function usageFor(Organisation $organisation): array
    {
        $id = (int) $organisation->id;

        $emailRow = DB::table('organisation_email_counters')
            ->where('organisation_id', $id)
            ->where('period', now()->format('Y-m'))
            ->first();

        return [
            'users' => User::withoutGlobalScopes()
                ->where('organisation_id', $id)
                ->where('is_active', true)
                ->count(),

            'active_leases' => Lease::withoutGlobalScopes()
                ->where('organisation_id', $id)
                ->whereIn('status', self::COUNTED_LEASE_STATUSES)
                ->count(),

            'parties' => Party::withoutGlobalScopes()
                ->where('organisation_id', $id)
                ->count(),

            'emails_this_month' => $emailRow === null
                ? 0
                : (int) $emailRow->automated_sent
                    + (int) $emailRow->transactional_sent,
        ];
    }

    /**
     * Whether the bound organisation is currently inside its trial and
     * holds no covering licence.
     */
    public function onTrial(): bool
    {
        $organisation = $this->organisation();

        if ($organisation === null || ! $organisation->onTrial()) {
            return false;
        }

        return $organisation
            ->licenses
            ->first(
                fn (License $license): bool => $license->coversToday()
            ) === null;
    }

    /**
     * Whether the current plan includes a boolean feature.
     */
    public function allows(string $feature): bool
    {
        return (bool) (
            $this->planDefinition()['features'][$feature]
            ?? false
        );
    }

    /**
     * A named numeric limit of the current plan (null = unlimited).
     */
    public function limit(string $limit): ?int
    {
        $value = $this->planDefinition()['limits'][$limit] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * Current usage against every limited resource.
     *
     * @return array<string, int>
     */
    public function usage(): array
    {
        return [
            'users' => User::query()
                ->where('is_active', true)
                ->count(),

            'active_leases' => $this->activeLeaseCount(),

            'parties' => Party::query()->count(),

            'emails_this_month' => $this->emailsSentThisMonth(),
        ];
    }

    /**
     * Throw unless one more ACTIVE lease may exist.
     */
    public function assertCanActivateLease(): void
    {
        $limit = $this->limit('active_leases');

        if ($limit !== null && $this->activeLeaseCount() >= $limit) {
            throw ValidationException::withMessages([
                'status' => [
                    __('api.license.lease_limit_reached'),
                ],
            ]);
        }
    }

    /**
     * Throw unless one more Party may be created.
     */
    public function assertCanCreateParty(): void
    {
        $limit = $this->limit('parties');

        if ($limit !== null && Party::query()->count() >= $limit) {
            throw ValidationException::withMessages([
                'name' => [
                    __('api.license.party_limit_reached'),
                ],
            ]);
        }
    }

    /**
     * Throw unless one more active internal user may exist.
     */
    public function assertCanAddUser(): void
    {
        $limit = $this->limit('users');

        if (
            $limit !== null
            && User::query()->where('is_active', true)->count() >= $limit
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    __('api.license.user_limit_reached'),
                ],
            ]);
        }
    }

    /**
     * Whether one more AUTOMATED email (reminder, notice) may be sent
     * right now: the plan must include automated mail and the monthly
     * allowance must not be exhausted.
     */
    public function canSendAutomatedEmail(): bool
    {
        if (! $this->allows('automated_reminders')) {
            return false;
        }

        $cap = $this->limit('emails_per_month');

        return $cap === null
            || $this->emailsSentThisMonth() < $cap;
    }

    /**
     * Record one delivered product email.
     *
     * Sign-in/MFA and account-verification mail is never counted.
     */
    public function registerEmail(string $kind): void
    {
        $organisationId = OrganisationContext::idOrNull();

        if ($organisationId === null) {
            return;
        }

        $period = now()->format('Y-m');

        $column = $kind === 'automated'
            ? 'automated_sent'
            : 'transactional_sent';

        /*
         * Atomic upsert keeps the counter correct under the concurrent
         * sends a scheduler run can produce.
         */
        DB::table('organisation_email_counters')->upsert(
            [[
                'organisation_id' => $organisationId,
                'period' => $period,
                'automated_sent' => $kind === 'automated' ? 1 : 0,
                'transactional_sent' => $kind === 'automated' ? 0 : 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['organisation_id', 'period'],
            [
                $column => DB::raw($column.' + 1'),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * The full plan matrix for presentation (license page).
     *
     * @return array<string, mixed>
     */
    public function presentation(): array
    {
        $organisation = $this->organisation();

        return [
            'plan' => $this->plan(),
            'plan_label' => (string) ($this->planDefinition()['label'] ?? ''),
            'on_trial' => $this->onTrial(),
            'trial_ends_on' => $organisation?->trial_ends_on?->toDateString(),
            'limits' => $this->planDefinition()['limits'] ?? [],
            'features' => $this->planDefinition()['features'] ?? [],
            'usage' => $this->usage(),
            'plans' => config('licensing.plans', []),
        ];
    }

    /**
     * Leases currently counting against the quota.
     */
    private function activeLeaseCount(): int
    {
        return Lease::query()
            ->whereIn('status', self::COUNTED_LEASE_STATUSES)
            ->count();
    }

    /**
     * Product emails (automated + transactional) sent this calendar
     * month by the bound organisation.
     */
    private function emailsSentThisMonth(): int
    {
        $organisationId = OrganisationContext::idOrNull();

        if ($organisationId === null) {
            return 0;
        }

        $row = DB::table('organisation_email_counters')
            ->where('organisation_id', $organisationId)
            ->where('period', now()->format('Y-m'))
            ->first();

        if ($row === null) {
            return 0;
        }

        return (int) $row->automated_sent
            + (int) $row->transactional_sent;
    }

    /**
     * The bound organisation, licences eager-loaded.
     */
    private function organisation(): ?Organisation
    {
        $organisationId = OrganisationContext::idOrNull();

        if ($organisationId === null) {
            return null;
        }

        return Organisation::query()
            ->with('licenses')
            ->find($organisationId);
    }

    /**
     * The active plan's config definition.
     *
     * @return array<string, mixed>
     */
    private function planDefinition(): array
    {
        return (array) config(
            'licensing.plans.'.$this->plan(),
            []
        );
    }
}
