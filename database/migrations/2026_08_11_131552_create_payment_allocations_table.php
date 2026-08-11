<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the payment_allocations table.
 *
 * A PaymentAllocation records how much of a Payment was applied to a
 * specific Invoice.
 *
 * Example:
 *
 * Payment: GHS 15,000
 *
 * - GHS 5,000 -> oldest Invoice
 * - GHS 7,000 -> second Invoice
 * - GHS 3,000 -> third Invoice
 *
 * Any part of the Payment not represented by allocations remains
 * unapplied and can later be handled as advance/credit according to
 * Patrimoine's financial rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();

            /*
             * Payment from which this allocation originates.
             *
             * Allocations are dependent accounting records and may be
             * removed if an unposted Payment is removed.
             */
            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Invoice being settled by this allocation.
             *
             * Invoice deletion remains restricted so a financial audit
             * trail cannot be silently destroyed.
             */
            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Amount of this Payment allocated to this Invoice.
             */
            $table->unsignedBigInteger('amount');

            $table->timestamps();

            /*
             * One Payment should have only one allocation row for a given
             * Invoice. Additional settlement can occur through another
             * Payment and therefore another PaymentAllocation record.
             */
            $table->unique(['payment_id', 'invoice_id']);

            $table->index(['invoice_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
