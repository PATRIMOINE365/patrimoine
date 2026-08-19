<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensure every existing Building Owner has a consolidated OwnerAccount.
 *
 * Earlier Patrimoine versions created OwnerAccounts only when an owner-side
 * financial event occurred. As a result, a legitimate property owner with
 * no rent collection, expense or previous deposit could have no account and
 * therefore could not be selected when recording an Owner Deposit.
 *
 * OwnerAccount is now considered part of the ownership domain itself.
 */
return new class extends Migration
{
    /**
     * Backfill accounts for all existing property owners.
     */
    public function up(): void
    {
        $now = now();

        /*
         * One Party may own several Buildings. DISTINCT ensures only one
         * candidate account is generated for each owner Party.
         *
         * INSERT OR IGNORE respects the existing unique constraint on
         * owner_accounts.party_id and therefore leaves existing accounts
         * untouched.
         */
        $ownerPartyIds =
            DB::table('building_owners')
                ->select('party_id')
                ->distinct()
                ->pluck('party_id');

        foreach ($ownerPartyIds as $partyId) {
            DB::table('owner_accounts')
                ->insertOrIgnore([
                    'party_id' => $partyId,

                    'status' => 'active',

                    'created_at' => $now,

                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * Intentionally preserve OwnerAccounts when rolling back.
     *
     * OwnerAccounts may already contain financial history by the time a
     * rollback occurs. Deleting them would destroy or violate accounting
     * relationships and is therefore unsafe.
     */
    public function down(): void
    {
        /*
         * No destructive rollback.
         */
    }
};
