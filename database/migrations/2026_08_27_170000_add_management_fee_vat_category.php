<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.23 VAT on management fees.
 *
 * VAT stops being charged on rent and is charged on the managing
 * organisation's fee instead, then billed to the Owner. The Owner ledger
 * therefore needs a category of its own so the fee and the VAT on it stay
 * separately readable, and so the Accounting page can total them apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->enum('category', [
                    'rent_entitlement',
                    'owner_deposit',
                    'management_fee',
                    'management_fee_vat',
                    'agent_commission',
                    'expense',
                    'payout',
                    'adjustment',
                    'reserve_transfer',
                ])->change();
            }
        );
    }

    public function down(): void
    {
        /*
         * Refuse to narrow the enumeration while VAT charges exist, because
         * dropping the value would silently rewrite recorded owner ledgers.
         */
        $recorded = \Illuminate\Support\Facades\DB::table('owner_transactions')
            ->where('category', 'management_fee_vat')
            ->count();

        if ($recorded > 0) {
            throw new RuntimeException(
                sprintf(
                    'Cannot remove the management_fee_vat category: %d owner transaction(s) still use it.',
                    $recorded
                )
            );
        }

        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->enum('category', [
                    'rent_entitlement',
                    'owner_deposit',
                    'management_fee',
                    'agent_commission',
                    'expense',
                    'payout',
                    'adjustment',
                    'reserve_transfer',
                ])->change();
            }
        );
    }
};
