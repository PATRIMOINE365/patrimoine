<?php

namespace App\Services;

use App\Models\Organisation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Permanently destroys a customer organisation and every row it owns.
 *
 * The privacy policy promises full deletion on request; this service
 * honours it. Tables are cleared children-first so every row-level
 * foreign key (restrictOnDelete throughout the schema) is satisfied,
 * inside one transaction: the organisation is gone entirely or not at
 * all.
 *
 * Guard rails live in the controller (suspension-first, typed name,
 * password re-authentication); this service adds only the last one —
 * it refuses the platform organisation.
 */
class PlatformOrganisationDeletionService
{
    /**
     * Organisation-owned tables in FK-safe, children-first order.
     *
     * @var list<string>
     */
    private const DELETION_ORDER = [
        'activity_logs',
        'organisation_email_counters',
        'journal_lines',
        'journal_entries',
        'journal_sequences',
        'accounting_cutovers',
        'security_deposit_applications',
        'withdrawal_receipts',
        'adjustment_vouchers',
        'owner_payout_allocations',
        'owner_transactions',
        'owner_payouts',
        'payment_allocations',
        'tenant_fund_transactions',
        'tenant_fund_accounts',
        'security_deposit_deductions',
        'security_deposit_settlements',
        'invoice_lines',
        'invoices',
        'payments',
        'owner_expenses',
        'owner_expense_bills',
        'owner_accounts',
        'rent_increments',
        'lease_wizard_drafts',
        'lease_term_versions',
        'leases',
        'units',
        'building_owners',
        'buildings',
        'party_roles',
        'application_settings',
        'parties',
        'accounting_accounts',
        'user_invitations',
    ];

    /**
     * Destroy the organisation and everything it owns.
     *
     * @return array<string, int> rows deleted per table
     */
    public function destroy(Organisation $organisation): array
    {
        if ($organisation->isPlatform()) {
            throw new RuntimeException(
                'The platform organisation cannot be deleted.'
            );
        }

        return DB::transaction(
            function () use ($organisation): array {
                $deleted = [];

                foreach (self::DELETION_ORDER as $table) {
                    $deleted[$table] = DB::table($table)
                        ->where(
                            'organisation_id',
                            $organisation->id
                        )
                        ->delete();
                }

                /*
                 * Per-user artefacts that key on the user rather than
                 * the organisation.
                 */
                $userIds = DB::table('users')
                    ->where('organisation_id', $organisation->id)
                    ->pluck('id');

                $userEmails = DB::table('users')
                    ->where('organisation_id', $organisation->id)
                    ->pluck('email');

                $deleted['mfa_challenges'] = DB::table('mfa_challenges')
                    ->whereIn('user_id', $userIds)
                    ->delete();

                $deleted['personal_access_tokens'] =
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', \App\Models\User::class)
                        ->whereIn('tokenable_id', $userIds)
                        ->delete();

                $deleted['password_reset_tokens'] =
                    DB::table('password_reset_tokens')
                        ->whereIn('email', $userEmails)
                        ->delete();

                $deleted['users'] = DB::table('users')
                    ->where('organisation_id', $organisation->id)
                    ->delete();

                $deleted['licenses'] = DB::table('licenses')
                    ->where('organisation_id', $organisation->id)
                    ->delete();

                DB::table('organisations')
                    ->where('id', $organisation->id)
                    ->delete();

                $deleted['organisations'] = 1;

                return array_filter($deleted);
            }
        );
    }
}
