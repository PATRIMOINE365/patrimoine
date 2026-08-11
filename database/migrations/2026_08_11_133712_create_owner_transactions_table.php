<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the owner accounting ledger.
 *
 * Every amount owed to or charged against an owner is represented by an
 * OwnerTransaction.
 *
 * Credits increase funds due to the owner.
 * Debits reduce funds due to the owner.
 *
 * Examples:
 * - rent entitlement      => credit
 * - owner deposit         => credit
 * - management fee        => debit
 * - agent commission      => debit
 * - property expense      => debit
 * - owner payout          => debit
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_account_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Building context is optional because some owner-level
             * transactions may span several properties.
             */
            $table->foreignId('building_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Unit is optional because ownership exists at Building level,
             * but rent or expenses may still be attributable to one Unit.
             */
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Optional Lease reference for rent-derived accounting entries.
             */
            $table->foreignId('lease_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Optional Invoice reference for traceability from tenant
             * billing into owner accounting.
             */
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Credits increase owner funds held.
             * Debits decrease owner funds held.
             */
            $table->enum('direction', [
                'credit',
                'debit',
            ]);

            /*
             * Business reason for the transaction.
             */
            $table->enum('category', [
                'rent_entitlement',
                'owner_deposit',
                'management_fee',
                'agent_commission',
                'expense',
                'payout',
                'adjustment',
            ]);

            $table->unsignedBigInteger('amount');

            /*
             * Effective accounting date. Backdating is supported where
             * administratively appropriate.
             */
            $table->date('transaction_date');

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            /*
             * Explicit short names avoid MySQL identifier-length issues.
             */
            $table->index(
                ['owner_account_id', 'transaction_date'],
                'owner_account_date_idx'
            );

            $table->index(
                ['building_id', 'transaction_date'],
                'owner_building_date_idx'
            );

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_transactions');
    }
};
