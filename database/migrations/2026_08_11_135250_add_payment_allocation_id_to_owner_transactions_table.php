<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link owner rent-entitlement transactions to the tenant payment
 * allocation that created them.
 *
 * This provides:
 * - complete audit traceability;
 * - protection against duplicate owner entitlement;
 * - a direct link from collected tenant cash to owner funds held.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_transactions', function (Blueprint $table) {
            $table->foreignId('payment_allocation_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('payment_allocations')
                ->restrictOnDelete();

            /*
             * One payment allocation may generate one entitlement entry
             * for each owner, but never more than one per owner account.
             */
            $table->unique(
                ['payment_allocation_id', 'owner_account_id'],
                'owner_payment_allocation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('owner_transactions', function (Blueprint $table) {
            $table->dropUnique('owner_payment_allocation_unique');

            $table->dropForeign([
                'payment_allocation_id',
            ]);

            $table->dropColumn('payment_allocation_id');
        });
    }
};
