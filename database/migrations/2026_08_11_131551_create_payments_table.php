<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the payments table.
 *
 * A Payment represents money received from a tenant.
 *
 * Important accounting rule:
 * A Payment does not directly "belong" to one Invoice. Instead, it may
 * be allocated across several invoices through payment_allocations.
 *
 * This structure supports:
 * - partial payments;
 * - one payment settling several invoices;
 * - FIFO allocation;
 * - unapplied/overpaid balances;
 * - later audit of exactly how a payment was distributed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            /*
             * Every tenant payment is associated with a Lease.
             *
             * This gives us the contractual context needed when finding
             * the oldest outstanding invoices during FIFO allocation.
             */
            $table->foreignId('lease_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Payment amount received.
             *
             * Patrimoine stores monetary values as whole currency units.
             */
            $table->unsignedBigInteger('amount');

            /*
             * Actual date the money was received.
             *
             * This intentionally supports backdated entries.
             */
            $table->date('payment_date');

            /*
             * Supported payment channels in Patrimoine 1.0.
             */
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'momo',
            ]);

            /*
             * Optional external reference.
             *
             * Examples:
             * - bank transaction reference;
             * - MoMo transaction ID;
             * - receipt/reference number supplied by the payer.
             */
            $table->string('reference')->nullable();

            /*
             * Name of the person who physically collected a cash payment.
             *
             * This is nullable because it is not applicable to bank
             * transfer or MoMo payments.
             *
             * We keep this as a historical text snapshot rather than a
             * foreign key so future user-account changes do not rewrite
             * historical payment records.
             */
            $table->string('collector_name')->nullable();

            /*
             * Optional administrative notes about the payment.
             */
            $table->text('notes')->nullable();

            $table->timestamps();

            /*
             * Common reconciliation and reporting paths.
             */
            $table->index(['lease_id', 'payment_date']);
            $table->index('payment_method');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
