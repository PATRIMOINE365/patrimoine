<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create tenant fund accounts.
 *
 * Tenant fund accounts represent money held on behalf of a tenant that
 * is separate from ordinary rent invoice settlement.
 *
 * Patrimoine 1.0 supports:
 *
 * - rent_reserve:
 *   Advance funds intentionally preserved until the lease enters the
 *   termination-notice workflow.
 *
 * - consumable_advance:
 *   Advance funds that may be used against normal rent obligations.
 *
 * - security_deposit:
 *   Refundable deposit held against damage or other permitted deductions.
 *
 * The current balance is never stored directly on this table. It is
 * calculated from TenantFundTransaction records so the financial history
 * remains auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_fund_accounts', function (Blueprint $table) {
            $table->id();

            /*
             * Every held-fund account belongs to one Lease.
             */
            $table->foreignId('lease_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Type of tenant money being held.
             *
             * A Lease may have at most one account of each type.
             */
            $table->enum('type', [
                'rent_reserve',
                'consumable_advance',
                'security_deposit',
            ]);

            /*
             * Account lifecycle.
             *
             * active = transactions may still be recorded.
             * closed = retained for historical/audit purposes.
             */
            $table->enum('status', [
                'active',
                'closed',
            ])->default('active');

            $table->timestamps();

            $table->unique(['lease_id', 'type']);
            $table->index(['lease_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_fund_accounts');
    }
};
