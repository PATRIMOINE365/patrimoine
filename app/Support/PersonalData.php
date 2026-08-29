<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * What Patrimoine holds about a person, and where.
 *
 * One class answers three questions that must never disagree: what to hand
 * somebody who asks for their data, what to destroy when somebody asks to
 * be forgotten, and what to write in the record of processing. Answering
 * them in three places is how a privacy policy ends up describing software
 * that does something else.
 *
 * Two kinds of person appear in Patrimoine and they are not the same:
 *
 *   - a USER is a colleague with an account. Kality Ltd is the controller
 *     for them, because the account exists on Kality's side of the line.
 *
 *   - a PARTY is a tenant, an owner or an agent. The customer organisation
 *     is the controller for them; Kality only processes. So a party's data
 *     is produced and erased BY the organisation, never by us on our own
 *     initiative.
 *
 * Everything here is read-only except erase(), which is deliberate and
 * irreversible.
 */
final class PersonalData
{
    /**
     * The identifying fields on a party.
     *
     * This list is the definition of "identity" for erasure. A new personal
     * column on parties belongs here, and PersonalDataTest fails if one
     * appears that this does not name.
     *
     * @var list<string>
     */
    public const PARTY_IDENTITY = [
        'name',
        'given_names',
        'surname',
        'legal_name',
        'phone',
        'phone_country',
        'alternate_phone',
        'alternate_phone_country',
        'email',
        'address',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_phone_country',
        'contact_person_email',
        'id_number',
        'registration_number',
        'vat_tin',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'notes',
    ];

    /**
     * Everything held about one colleague.
     *
     * @return array<string, mixed>
     */
    public static function forUser(User $user): array
    {
        return [
            'produced_at' => now()->toIso8601String(),
            'about' => 'A Patrimoine user account',

            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'given_names' => $user->given_names,
                'surname' => $user->surname,
                'email' => $user->email,
                'telephone' => $user->phone,
                'telephone_country' => $user->phone_country,
                'role' => $user->role?->value,
                'is_active' => (bool) $user->is_active,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'has_photograph' => $user->profile_photo !== null,
            ],

            'organisation' => [
                'id' => $user->organisation_id,
                'name' => optional(
                    DB::table('organisations')->find($user->organisation_id)
                )->name,
            ],

            /*
             * Sessions and tokens are listed without their secrets. The
             * point of the list is "these are the ways your account can be
             * used"; printing the token would create a new risk to answer
             * a request about an old one.
             */
            'access_tokens' => DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->get(['id', 'name', 'last_used_at', 'created_at'])
                ->map(fn ($row): array => (array) $row)
                ->all(),

            /*
             * What this person did, as recorded. The log also holds the
             * address, browser and device each action came from, which is
             * personal data about them and is therefore included.
             */
            'activity' => ActivityLog::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get([
                    'id',
                    'action',
                    'entity_type',
                    'entity_label',
                    'ip_address',
                    'browser',
                    'platform',
                    'device',
                    'created_at',
                ])
                ->map(fn (ActivityLog $event): array => $event->toArray())
                ->all(),

            'notes' => [
                'Your password is stored only as a hash and cannot be exported.',
                'One-time sign-in codes are stored hashed and expire within minutes.',
                'Activity log entries are kept indefinitely, as an audit record.',
            ],
        ];
    }

    /**
     * Everything held about one tenant, owner or agent.
     *
     * Written for the organisation to hand to the person who asked, so it
     * carries the financial history too: what they were invoiced, what they
     * paid, what is held for them and what was paid out.
     *
     * @return array<string, mixed>
     */
    public static function forParty(Party $party): array
    {
        $party->loadMissing('roles');

        $record = [];

        foreach (self::PARTY_IDENTITY as $field) {
            $record[$field] = $party->{$field};
        }

        return [
            'produced_at' => now()->toIso8601String(),
            'about' => 'A party recorded in Patrimoine',

            'party' => array_merge(
                [
                    'id' => $party->id,
                    'type' => $party->type,
                    'erased_at' => $party->erased_at?->toIso8601String(),
                    'created_at' => $party->created_at?->toIso8601String(),
                    'updated_at' => $party->updated_at?->toIso8601String(),
                ],
                $record
            ),

            'roles' => $party->roles->pluck('role')->all(),

            'leases_as_tenant' => self::rows('leases', 'tenant_id', $party->id),
            'leases_as_agent' => self::rows('leases', 'agent_id', $party->id),
            'property_ownership' => self::rows('building_owners', 'party_id', $party->id),

            'invoices' => self::throughLeases('invoices', $party->id),
            'payments' => self::throughLeases('payments', $party->id),

            'tenant_fund_accounts' => self::throughLeases('tenant_fund_accounts', $party->id),
            'owner_accounts' => self::rows('owner_accounts', 'party_id', $party->id),

            'activity' => ActivityLog::query()
                ->where('entity_type', 'party')
                ->where('entity_id', (string) $party->id)
                ->orderBy('id')
                ->get(['id', 'action', 'entity_label', 'actor_name', 'created_at'])
                ->map(fn (ActivityLog $event): array => $event->toArray())
                ->all(),

            'notes' => [
                'Financial records are kept for as long as the law requires them, '
                    .'even after a request to be forgotten. What is erased is the '
                    .'identity, not the accounts.',
                'Patrimoine keeps no copy of the e-mail it sends. What was sent, '
                    .'and when, is recorded in the activity log above; the message '
                    .'itself lives only in the recipient’s inbox.',
            ],
        ];
    }

    /**
     * Everything the organisation holds, for the organisation itself.
     *
     * @return array<string, mixed>
     */
    public static function forOrganisation(int $organisationId): array
    {
        $tables = [
            'parties', 'party_roles', 'buildings', 'building_owners', 'units',
            'leases', 'lease_term_versions', 'rent_increments',
            'invoices', 'invoice_lines', 'payments', 'payment_allocations',
            'tenant_fund_accounts', 'tenant_fund_transactions',
            'security_deposit_settlements', 'security_deposit_deductions',
            'owner_accounts', 'owner_transactions', 'owner_expenses',
            'owner_expense_bills', 'owner_payouts', 'owner_payout_allocations',
            'withdrawal_receipts', 'adjustment_vouchers',
            'journal_entries', 'journal_lines',
            'accounting_accounts', 'users', 'activity_logs',
        ];

        $data = [
            'produced_at' => now()->toIso8601String(),
            'about' => 'Everything Patrimoine holds for this organisation',
        ];

        foreach ($tables as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $query = DB::table($table)
                ->where('organisation_id', $organisationId);

            /*
             * Credentials are never exported. A copy of every password hash
             * in one downloadable file is a liability, not a right.
             */
            $rows = $query->get()->map(function ($row): array {
                $row = (array) $row;

                unset(
                    $row['password'],
                    $row['remember_token'],
                    $row['email_verification_token_hash'],
                    $row['profile_photo'],
                    $row['profile_photo_mime'],
                    $row['profile_photo_crop'],
                    $row['profile_photo_source'],
                    $row['profile_photo_source_mime']
                );

                return $row;
            });

            $data[$table] = $rows->all();
        }

        return $data;
    }

    /**
     * Erase the person, keep the record.
     *
     * Every identifying field is overwritten with a permanent reference, the
     * party is marked erased, and it is silenced so that nothing is ever
     * sent to an address that no longer exists. The financial rows continue
     * to point at the same row, which is the whole design: the accounts
     * still balance and still explain themselves, with nobody identifiable
     * behind them.
     *
     * Irreversible by construction — the old values are overwritten, not
     * moved somewhere else.
     *
     * @return string the reference the record now carries
     */
    public static function erase(Party $party): string
    {
        $reference = 'Erased party #'.$party->id;

        $blank = array_fill_keys(self::PARTY_IDENTITY, null);

        $blank['name'] = $reference;

        /*
         * Never e-mail an erased party. Their address is gone, but the
         * policy is set explicitly so that restoring an address by hand
         * could not quietly start sending again.
         */
        $blank['email_policy'] = 'never';

        $blank['erased_at'] = now();

        $party->forceFill($blank)->save();

        return $reference;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rows(string $table, string $column, mixed $value): array
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return [];
        }

        if (! DB::getSchemaBuilder()->hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->where($column, $value)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    /**
     * Rows reached through the party's leases.
     *
     * @return list<array<string, mixed>>
     */
    private static function throughLeases(string $table, int $partyId): array
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return [];
        }

        if (! DB::getSchemaBuilder()->hasColumn($table, 'lease_id')) {
            return [];
        }

        $leases = DB::table('leases')
            ->where('tenant_id', $partyId)
            ->pluck('id');

        if ($leases->isEmpty()) {
            return [];
        }

        return DB::table($table)
            ->whereIn('lease_id', $leases)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }
}
