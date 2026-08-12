<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add receipt metadata to owner ledger transactions.
 *
 * Owner deposits represent actual money received by Patrimoine and therefore
 * need sufficient information to appear alongside tenant receipts in the
 * unified Payments register.
 *
 * These fields remain nullable because many OwnerTransaction categories
 * (management fees, rent entitlement, expenses, payouts, etc.) are accounting
 * movements rather than incoming payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_transactions', function (Blueprint $table) {
            $table->string('payment_method')
                ->nullable()
                ->after('transaction_date');

            $table->string('deposit_purpose')
                ->nullable()
                ->after('payment_method');

            $table->string('collector_name')
                ->nullable()
                ->after('deposit_purpose');

            $table->index(
                'payment_method',
                'owner_transaction_payment_method_idx'
            );

            $table->index(
                'deposit_purpose',
                'owner_transaction_deposit_purpose_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('owner_transactions', function (Blueprint $table) {
            $table->dropIndex(
                'owner_transaction_payment_method_idx'
            );

            $table->dropIndex(
                'owner_transaction_deposit_purpose_idx'
            );

            $table->dropColumn([
                'payment_method',
                'deposit_purpose',
                'collector_name',
            ]);
        });
    }
};
