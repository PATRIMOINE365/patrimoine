<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create owner financial accounts.
 *
 * Each owner Party has one accounting ledger in Patrimoine.
 *
 * The current owner balance is deliberately not stored here. It is
 * calculated from OwnerTransaction records so every movement remains
 * auditable and negative balances automatically carry forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_accounts', function (Blueprint $table) {
            $table->id();

            /*
             * Party acting as the property owner.
             *
             * One Party may own several Buildings but still uses one
             * consolidated OwnerAccount.
             */
            $table->foreignId('party_id')
                ->constrained('parties')
                ->restrictOnDelete();

            $table->enum('status', [
                'active',
                'closed',
            ])->default('active');

            $table->timestamps();

            /*
             * Patrimoine 1.0 keeps one consolidated account per owner.
             */
            $table->unique('party_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_accounts');
    }
};
