<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the tenant fund transaction ledger.
 *
 * Every movement into or out of a tenant-held fund account is represented
 * as a transaction. Balances are therefore derived from ledger history
 * instead of being manually updated fields.
 *
 * Example Rent Reserve:
 *
 * + 30,000 reserve_funding
 * -  5,000 rent_consumption
 * -  5,000 rent_consumption
 *
 * Current balance = 20,000.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_fund_transactions', function (Blueprint $table) {
            $table->id();

            /*
             * Fund account affected by this transaction.
             */
            $table->foreignId('tenant_fund_account_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Optional originating Payment.
             *
             * This allows us to trace money received from a tenant into
             * the reserve, consumable advance or security-deposit ledger.
             */
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Optional Invoice associated with consumption of held funds.
             *
             * Example: Rent Reserve being consumed to settle rent during
             * a termination-notice period.
             */
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Credits increase the tenant fund account.
             * Debits reduce it.
             */
            $table->enum('direction', [
                'credit',
                'debit',
            ]);

            /*
             * Business reason for the ledger movement.
             *
             * The combination of direction and category makes reports
             * understandable without relying on free-form descriptions.
             */
            $table->enum('category', [
                'reserve_funding',
                'advance_funding',
                'deposit_funding',
                'rent_consumption',
                'deposit_deduction',
                'refund',
                'transfer',
                'adjustment',
            ]);

            /*
             * Monetary values remain whole currency units.
             */
            $table->unsignedBigInteger('amount');

            /*
             * Effective accounting date.
             *
             * This supports legitimate backdated transactions.
             */
            $table->date('transaction_date');

            /*
             * Optional external or administrative reference.
             */
            $table->string('reference')->nullable();

            /*
             * Explanation required for unusual transactions such as
             * adjustments, deductions or manual transfers.
             */
            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            * Short explicit name avoids MySQL's 64-character identifier limit.
            */
            $table->index(
                ['tenant_fund_account_id', 'transaction_date'],
                'tenant_fund_account_date_idx'
            );

            $table->index('category');
            $table->index('payment_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_fund_transactions');
    }
};
