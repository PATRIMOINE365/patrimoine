<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            /*
             * Marks the historical Advance Payment used when reconstructing
             * the financial opening position of an existing Lease.
             *
             * A Lease may have at most one such Payment.
             */
            $table->boolean('is_opening_advance')
                ->default(false)
                ->after('notes');

            $table->index(
                ['lease_id', 'is_opening_advance'],
                'payments_lease_opening_advance_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(
                'payments_lease_opening_advance_idx'
            );

            $table->dropColumn(
                'is_opening_advance'
            );
        });
    }
};
