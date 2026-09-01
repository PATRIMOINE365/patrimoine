<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.43: the security deposit taken when a Lease is entered.
 *
 * Every Lease already owns a Security Deposit fund account from the moment
 * it exists, and the lease form has always asked for the amount — but
 * nothing ever put money into it, so the figure on the contract and the
 * balance on the account disagreed from day one. The deposit is now
 * received at Lease creation like the opening advance beside it.
 *
 * This flag is what makes that idempotent. Lease initialization runs again
 * on every update, and a deposit taken twice is a deposit the tenant is
 * owed twice: at most one opening deposit Payment may exist per Lease.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->boolean('is_opening_deposit')
                ->default(false)
                ->after('is_opening_advance');

            $table->index(
                ['lease_id', 'is_opening_deposit'],
                'payments_lease_opening_deposit_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(
                'payments_lease_opening_deposit_idx'
            );

            $table->dropColumn(
                'is_opening_deposit'
            );
        });
    }
};
