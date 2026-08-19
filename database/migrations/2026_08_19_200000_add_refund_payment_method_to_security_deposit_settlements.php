<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'security_deposit_settlements',
            function (Blueprint $table): void {
                /*
                 * A Security Deposit refund is an outgoing asset movement.
                 *
                 * The field is nullable because settlements with no refund
                 * do not have a payout channel.
                 */
                $table
                    ->string('refund_payment_method')
                    ->nullable()
                    ->after('refund_amount');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'security_deposit_settlements',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'refund_payment_method'
                );
            }
        );
    }
};
