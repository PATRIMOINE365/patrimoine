<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            /*
             * Intended vacate / termination date selected when formal
             * termination is initiated.
             *
             * This is lifecycle state and deliberately remains separate
             * from the contractual end_date.
             */
            $table
                ->date('termination_date')
                ->nullable()
                ->after('termination_notice_date');

            /*
             * Controls treatment of the final partial rental period:
             *
             * prorate = calculate a prorated final period
             * full    = charge the full final billing period
             * none    = do not charge final-period rent
             *
             * NULL means termination has not yet established this choice.
             */
            $table
                ->string('termination_final_rent_mode', 20)
                ->nullable()
                ->after('termination_date');

            /*
             * Operational status to restore when a Termination in Progress
             * is cancelled.
             *
             * This is intentionally lifecycle metadata, not a contractual
             * term-version field.
             */
            $table
                ->string('termination_previous_status', 20)
                ->nullable()
                ->after('termination_final_rent_mode');

            /*
             * Explicit completion timestamp.
             *
             * Reaching termination_date does not itself terminate the Lease.
             * Completion is a deliberate user action in Phase 9.
             */
            $table
                ->timestamp('termination_completed_at')
                ->nullable()
                ->after('termination_previous_status');

            $table->index('termination_date');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex([
                'termination_date',
            ]);

            $table->dropColumn([
                'termination_date',
                'termination_final_rent_mode',
                'termination_previous_status',
                'termination_completed_at',
            ]);
        });
    }
};
