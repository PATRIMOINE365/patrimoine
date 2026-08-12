<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow one PaymentAllocation to create different owner accounting
 * categories for the same owner.
 *
 * A collected tenant payment allocation can legitimately produce:
 * - rent_entitlement credit;
 * - management_fee debit.
 *
 * The original unique constraint on:
 *
 *     payment_allocation_id + owner_account_id
 *
 * incorrectly prevented this.
 *
 * MySQL also requires an index beginning with payment_allocation_id
 * to support the foreign key, so a dedicated non-unique index is
 * created before removing the original unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Create a dedicated index for the payment_allocation_id
         * foreign key before removing the old unique index.
         */
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->index(
                    'payment_allocation_id',
                    'owner_payment_allocation_fk_idx'
                );
            }
        );

        /*
         * The old unique constraint does not include category and therefore
         * prevents both rent entitlement and management fee entries from
         * referencing the same PaymentAllocation for the same owner.
         */
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'owner_payment_allocation_unique'
                );
            }
        );

        /*
         * Different accounting categories may now coexist for the same
         * PaymentAllocation and owner, while duplicate postings of the
         * same category remain prohibited.
         */
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'payment_allocation_id',
                        'owner_account_id',
                        'category',
                    ],
                    'owner_payment_allocation_category_unique'
                );
            }
        );
    }

    public function down(): void
    {
        /*
         * Remove the expanded uniqueness rule first.
         */
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'owner_payment_allocation_category_unique'
                );
            }
        );

        /*
         * Restore the original uniqueness rule.
         *
         * This may fail during rollback if the database already contains
         * both rent_entitlement and management_fee records for the same
         * PaymentAllocation and owner, because those records would violate
         * the old constraint.
         */
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'payment_allocation_id',
                        'owner_account_id',
                    ],
                    'owner_payment_allocation_unique'
                );
            }
        );

        /*
         * The restored unique index can once again support the foreign key,
         * so the temporary dedicated FK index is no longer required.
         */
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'owner_payment_allocation_fk_idx'
                );
            }
        );
    }
};
