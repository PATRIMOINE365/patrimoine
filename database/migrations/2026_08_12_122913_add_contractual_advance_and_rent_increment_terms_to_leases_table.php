<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add contractual advance-payment and rent-increment terms to Leases.
 *
 * Important accounting distinction:
 *
 * These fields describe what was agreed in the Lease contract.
 * They do NOT represent actual money received or current tenant-fund
 * balances.
 *
 * Actual Rent Reserve and Consumable Advance balances continue to be
 * derived exclusively from TenantFundAccount and TenantFundTransaction.
 */
return new class extends Migration
{
    /**
     * Add the additional contractual Lease terms.
     */
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table): void {
            /*
             * Total advance payment contractually expected from the Tenant.
             *
             * Example:
             * Advance Payment = GHS 60,000
             * Rent Reserve    = GHS 15,000
             *
             * Contractual Consumable Advance = GHS 45,000.
             *
             * The actual financial balances are still created only when
             * tenant Payments are received and classified.
             */
            $table->unsignedBigInteger('advance_payment_amount')
                ->default(0)
                ->after('security_deposit_amount');

            /*
             * Portion of the contractual Advance Payment intended to remain
             * protected as Rent Reserve until termination notice.
             */
            $table->unsignedBigInteger('rent_reserve_amount')
                ->default(0)
                ->after('advance_payment_amount');

            /*
             * Contractual rent-increase configuration.
             *
             * none       = no currently configured increase;
             * percentage = increment_value is a percentage;
             * fixed      = increment_value is a whole-currency amount.
             */
            $table->enum('rent_increment_type', [
                'none',
                'percentage',
                'fixed',
            ])
                ->default('none')
                ->after('rent_reserve_amount');

            /*
             * Percentage or fixed amount according to rent_increment_type.
             *
             * Decimal storage is required because percentage increases may
             * contain fractional percentages.
             */
            $table->decimal('rent_increment_value', 12, 2)
                ->default(0)
                ->after('rent_increment_type');

            /*
             * Date on which the next configured rent increment takes effect.
             *
             * Automatic recurring increments are intentionally not inferred
             * from this field. A recurrence rule can be introduced once that
             * business rule is explicitly defined.
             */
            $table->date('next_rent_increment_date')
                ->nullable()
                ->after('rent_increment_value');
        });
    }

    /**
     * Remove the contractual fields.
     */
    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table): void {
            $table->dropColumn([
                'advance_payment_amount',
                'rent_reserve_amount',
                'rent_increment_type',
                'rent_increment_value',
                'next_rent_increment_date',
            ]);
        });
    }
};
