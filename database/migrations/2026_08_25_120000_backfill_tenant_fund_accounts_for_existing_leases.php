<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V1.0.8: fund accounts are provisioned eagerly with the Lease instead of
 * materializing on first funding, so Tenants > Accounts always presents the
 * complete held-funds position and Transfers can reach any account.
 *
 * This migration brings existing Leases up to that shape. Terminated Leases
 * are left alone: their funds were settled at termination, and creating
 * fresh active accounts on a closed tenancy would only add noise.
 */
return new class extends Migration
{
    private const FUND_TYPES = [
        'rent_reserve',
        'consumable_advance',
        'security_deposit',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::FUND_TYPES as $type) {
            $missing = DB::table('leases')
                ->where('leases.status', '<>', 'terminated')
                ->whereNotExists(function ($query) use ($type) {
                    $query
                        ->select(DB::raw(1))
                        ->from('tenant_fund_accounts')
                        ->whereColumn(
                            'tenant_fund_accounts.lease_id',
                            'leases.id'
                        )
                        ->where(
                            'tenant_fund_accounts.type',
                            $type
                        );
                })
                ->pluck('leases.id');

            foreach ($missing as $leaseId) {
                DB::table('tenant_fund_accounts')->insert([
                    'lease_id' => $leaseId,
                    'type' => $type,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Only the accounts this migration could have created are removable:
     * an account that has recorded transactions carries financial history
     * and must survive a rollback.
     */
    public function down(): void
    {
        DB::table('tenant_fund_accounts')
            ->whereNotExists(function ($query) {
                $query
                    ->select(DB::raw(1))
                    ->from('tenant_fund_transactions')
                    ->whereColumn(
                        'tenant_fund_transactions.tenant_fund_account_id',
                        'tenant_fund_accounts.id'
                    );
            })
            ->delete();
    }
};
