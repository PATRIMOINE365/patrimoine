<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create owner payout records.
 *
 * A payout represents money actually released to an owner.
 *
 * One payout may cover financial activity from several Buildings and
 * Units. Detailed attribution is stored in owner_payout_allocations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_account_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->date('payout_date');

            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'momo',
                'cheque', // added in 2026_08_27_090000_add_cheque_payment_method
            ]);

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['owner_account_id', 'payout_date'],
                'owner_payout_account_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payouts');
    }
};
