<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create owner payout allocations.
 *
 * These rows explain which positive owner-ledger transactions were
 * covered by a payout.
 *
 * This allows one payout to consolidate funds originating from several
 * Buildings, Units or rent periods while preserving traceability.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_payout_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_payout_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Allocation points to an OwnerTransaction, normally a credit
             * such as rent entitlement or owner deposit.
             */
            $table->foreignId('owner_transaction_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->timestamps();

            $table->unique(
                ['owner_payout_id', 'owner_transaction_id'],
                'owner_payout_txn_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payout_allocations');
    }
};
