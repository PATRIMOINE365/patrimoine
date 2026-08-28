<?php

namespace App\Services\Notifications;

use App\Exceptions\PartyEmailSuppressedException;
use App\Models\ApplicationSetting;
use App\Models\Party;
use App\Services\ActivityLogService;
use App\Support\OrganisationContext;

/**
 * Decides whether Patrimoine may email a given Party.
 *
 * Two levels answer that question:
 *
 * - the organisation-wide switch in Settings
 *   (`application_settings.party_emails_enabled`);
 * - the Party's own policy (`parties.email_policy`), which either follows
 *   the organisation (`inherit`), overrides it upwards (`always`) or
 *   overrides it downwards (`never`).
 *
 * This service is the ONLY place that answer is worked out. Every send
 * path asks it, so a Party can never be silenced in one workspace and
 * emailed from another.
 *
 * It governs mail addressed to PARTIES — tenants, owners and agents.
 * Mail addressed to Patrimoine users (sign-in codes, invitations,
 * password resets, licence notices) is how people reach their own
 * account and is deliberately outside its reach.
 */
class PartyEmailPolicyService
{
    /**
     * The organisation switch, memoized per organisation.
     *
     * Scheduled runs walk every organisation in one process, so the
     * cache is keyed by organisation rather than held as a single value.
     *
     * @var array<string, bool>
     */
    private array $organisationSwitch = [];

    public function __construct(
        private readonly ActivityLogService $activityLog
    ) {}

    /**
     * Is the organisation currently emailing its parties at all?
     */
    public function organisationSendsPartyEmail(): bool
    {
        $key = (string) (
            OrganisationContext::idOrNull()
            ?? 'none'
        );

        if (! array_key_exists($key, $this->organisationSwitch)) {
            $settings =
                ApplicationSetting::query()
                    ->first();

            /*
             * A fresh install has no settings row yet. Sending is the
             * historical behaviour, so absence means "yes".
             */
            $this->organisationSwitch[$key] =
                $settings === null
                    ? true
                    : (bool) $settings->party_emails_enabled;
        }

        return $this->organisationSwitch[$key];
    }

    /**
     * May this Party be emailed?
     */
    public function allows(
        Party $party
    ): bool {
        return match ($party->email_policy) {
            'never' => false,
            'always' => true,
            default => $this->organisationSendsPartyEmail(),
        };
    }

    /**
     * Why a Party is not being emailed: the organisation switch, or the
     * Party's own exclusion.
     */
    public function suppressionReason(
        Party $party
    ): ?string {
        if ($this->allows($party)) {
            return null;
        }

        return $party->email_policy === 'never'
            ? 'party'
            : 'organisation';
    }

    /**
     * Refuse the send unless this Party may be emailed.
     *
     * `$documentType` names what was withheld ('invoice', 'receipt',
     * 'owner_expense_bill'…) for the activity log.
     *
     * `$audit` is false for scheduled runs — reminders and increment
     * notices sweep every open invoice nightly, and recording one entry
     * per skipped invoice would bury the activity log in noise. Those
     * commands report their skipped count instead.
     */
    public function ensureAllowed(
        Party $party,
        ?string $documentType = null,
        bool $audit = true
    ): void {
        $reason =
            $this->suppressionReason(
                $party
            );

        if ($reason === null) {
            return;
        }

        if ($audit) {
            $this->activityLog->record(
                action: 'email.suppressed',
                entityType: 'party',
                entityId: $party->id,
                entityLabel: $party->name
                    ?? $party->legal_name,
                metadata: [
                    'document_type' => $documentType,
                    'reason' => $reason,
                    'email_policy' => $party->email_policy,
                ],
            );
        }

        throw new PartyEmailSuppressedException(
            $reason === 'party'
                ? __('business.email.suppressed_for_party')
                : __('business.email.suppressed_by_organisation'),
            party: $party,
            reason: $reason,
        );
    }
}
