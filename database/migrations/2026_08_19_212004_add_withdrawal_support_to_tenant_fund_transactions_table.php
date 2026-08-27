<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'tenant_fund_transactions',
            function (Blueprint $table): void {
                /*
                 * Withdrawal is a distinct outgoing Tenant-fund event.
                 *
                 * Existing transaction categories remain unchanged.
                 */
                $table->enum('category', [
                    'reserve_funding',
                    'advance_funding',
                    'deposit_funding',
                    'rent_consumption',
                    'advance_consumption',
                    'deposit_deduction',
                    'refund',
                    'transfer',
                    'adjustment',
                    'withdrawal',
                ])->change();

                /*
                 * Incoming funding derives its method from Payment.
                 *
                 * A Withdrawal has no Payment record, therefore its payout
                 * method must live on the operational transaction itself.
                 */
                $table->enum('payment_method', [
                    'cash',
                    'bank_transfer',
                    'momo',
                    'cheque', // added in 2026_08_27_090000_add_cheque_payment_method
                ])
                    ->nullable()
                    ->after('transaction_date');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'tenant_fund_transactions',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'payment_method'
                );

                $table->enum('category', [
                    'reserve_funding',
                    'advance_funding',
                    'deposit_funding',
                    'rent_consumption',
                    'advance_consumption',
                    'deposit_deduction',
                    'refund',
                    'transfer',
                    'adjustment',
                ])->change();
            }
        );
    }
};
