<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguish rent invoices from Security Deposit close-out debt and
 * connect final settlements to the receivable created for excess deductions.
 *
 * Patrimoine keeps both obligations in the Invoice/Payment framework so
 * ordinary tenant Payments can settle either one. The invoice type prevents
 * non-rent collections from accidentally creating owner rent entitlement,
 * management fees or other rent-specific accounting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            /*
             * Existing invoices are rent invoices, therefore the default keeps
             * all historical records backward compatible.
             */
            $table->enum('type', [
                'rent',
                'security_deposit_debt',
            ])
                ->default('rent')
                ->after('invoice_number');

            $table->index([
                'lease_id',
                'type',
                'status',
            ]);
        });

        Schema::table(
            'security_deposit_settlements',
            function (Blueprint $table) {
                /*
                 * When deductions exceed the held deposit, settlement creates
                 * one receivable Invoice for the excess tenant debt.
                 *
                 * NULL means that settlement created no tenant debt.
                 */
                $table->foreignId('debt_invoice_id')
                    ->nullable()
                    ->after('tenant_debt_amount')
                    ->constrained('invoices')
                    ->restrictOnDelete();

                $table->unique('debt_invoice_id');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'security_deposit_settlements',
            function (Blueprint $table) {
                $table->dropUnique([
                    'debt_invoice_id',
                ]);

                $table->dropConstrainedForeignId(
                    'debt_invoice_id'
                );
            }
        );

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex([
                'lease_id',
                'type',
                'status',
            ]);

            $table->dropColumn('type');
        });
    }
};
