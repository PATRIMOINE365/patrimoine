<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create itemized security-deposit deductions.
 *
 * Each row represents one charge applied against a tenant's security
 * deposit during lease close-out.
 *
 * Examples:
 * - damaged lock;
 * - repainting;
 * - broken fitting;
 * - cleaning charge.
 *
 * The actual reduction of the security-deposit fund account is recorded
 * separately in the tenant fund ledger so the accounting history remains
 * complete and auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_deposit_deductions', function (Blueprint $table) {
            $table->id();

            /*
             * Every deduction belongs to one Lease.
             */
            $table->foreignId('lease_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Human-readable reason for the deduction.
             */
            $table->string('description');

            /*
             * Patrimoine stores money as whole currency units.
             */
            $table->unsignedBigInteger('amount');

            /*
             * Date the deduction was assessed.
             */
            $table->date('deduction_date');

            /*
             * Optional administrative reference, work-order number,
             * inspection reference or supporting-document reference.
             */
            $table->string('reference')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['lease_id', 'deduction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_deposit_deductions');
    }
};
