<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create security-deposit settlements.
 *
 * A Settlement represents the final close-out calculation for a Lease's
 * security deposit.
 *
 * Example:
 *
 * Deposit held:     GHS 10,000
 * Deductions:       GHS  3,000
 * Refund due:       GHS  7,000
 * Tenant debt:      GHS      0
 *
 * If deductions exceed the deposit:
 *
 * Deposit held:     GHS 10,000
 * Deductions:       GHS 13,000
 * Refund due:       GHS      0
 * Tenant debt:      GHS  3,000
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_deposit_settlements', function (Blueprint $table) {
            $table->id();

            /*
             * Patrimoine 1.0 supports one final security-deposit
             * settlement per Lease.
             */
            $table->foreignId('lease_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Snapshot values recorded at settlement time.
             *
             * These are stored rather than recalculated forever so later
             * changes to historical ledger records cannot silently rewrite
             * an already-issued settlement/refund voucher.
             */
            $table->unsignedBigInteger('deposit_amount');
            $table->unsignedBigInteger('deduction_amount');
            $table->unsignedBigInteger('refund_amount');
            $table->unsignedBigInteger('tenant_debt_amount');

            /*
             * Date the settlement was finalized.
             */
            $table->date('settlement_date');

            /*
             * Refund voucher/reference generated during settlement.
             *
             * It may remain NULL when no refund is due.
             */
            $table->string('refund_voucher_number')
                ->nullable()
                ->unique();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique('lease_id');
            $table->index('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_deposit_settlements');
    }
};
