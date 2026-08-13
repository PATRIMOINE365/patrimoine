<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store the full history of approved and applied rent increments.
 *
 * Rent increments are separate from Lease configuration because Patrimoine
 * must preserve the historical details of every increase independently of
 * the Lease's current rent amount.
 *
 * Business rules implemented by the application layer:
 *
 * - rent increments are discretionary rather than automatic;
 * - an increment may be percentage-based or fixed;
 * - a scheduled increment must have a future effective date;
 * - the tenant must be notified three months before it takes effect;
 * - once applied, the new rent becomes the Lease's current rent;
 * - at least twelve months must pass before another increment may take effect.
 */
return new class extends Migration
{
    /**
     * Create the rent increment history table.
     */
    public function up(): void
    {
        Schema::create(
            'rent_increments',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Lease whose rent is being changed.
                 *
                 * Historical rent increments must remain attached to their
                 * Lease and therefore prevent accidental Lease deletion.
                 */
                $table->foreignId('lease_id')
                    ->constrained('leases')
                    ->restrictOnDelete();

                /*
                 * Rent amount that applied immediately before this increment.
                 *
                 * Patrimoine stores all monetary amounts as whole currency
                 * units.
                 */
                $table->unsignedBigInteger(
                    'old_rent_amount'
                );

                /*
                 * Method used to calculate the increase.
                 *
                 * percentage:
                 *     increment_value is a percentage rate.
                 *
                 * fixed:
                 *     increment_value is a whole-currency amount added to
                 *     the existing rent.
                 */
                $table->enum(
                    'increment_type',
                    [
                        'percentage',
                        'fixed',
                    ]
                );

                /*
                 * Percentage rate or fixed monetary amount.
                 *
                 * Decimal storage permits percentage values such as 7.50%.
                 *
                 * Fixed increments are still expected to represent whole
                 * currency units at the application-validation layer.
                 */
                $table->decimal(
                    'increment_value',
                    12,
                    2
                );

                /*
                 * Final monthly rent that becomes effective after applying
                 * the increment.
                 *
                 * This value is persisted rather than recalculated later so
                 * historical records remain exact even if calculation rules
                 * evolve.
                 */
                $table->unsignedBigInteger(
                    'new_rent_amount'
                );

                /*
                 * Date on which the new rent takes effect.
                 */
                $table->date(
                    'effective_date'
                );

                /*
                 * Lifecycle:
                 *
                 * scheduled:
                 *     approved by management but not yet effective.
                 *
                 * applied:
                 *     new rent has been written to the Lease.
                 *
                 * cancelled:
                 *     approved increment was withdrawn before application.
                 */
                $table->enum(
                    'status',
                    [
                        'scheduled',
                        'applied',
                        'cancelled',
                    ]
                )->default('scheduled');

                /*
                 * Timestamp recording when the three-month tenant notice
                 * was successfully sent.
                 *
                 * NULL means the notice has not yet been delivered.
                 */
                $table->timestamp(
                    'notification_sent_at'
                )->nullable();

                /*
                 * Timestamp recording when the scheduled increment was
                 * applied to the Lease.
                 */
                $table->timestamp(
                    'applied_at'
                )->nullable();

                /*
                 * Timestamp recording cancellation of the increment.
                 */
                $table->timestamp(
                    'cancelled_at'
                )->nullable();

                $table->timestamps();

                /*
                 * Support the daily notification and application queries.
                 */
                $table->index([
                    'status',
                    'effective_date',
                ]);

                $table->index([
                    'notification_sent_at',
                    'effective_date',
                ]);

                /*
                 * A Lease cannot have two increments taking effect on the
                 * same date.
                 */
                $table->unique([
                    'lease_id',
                    'effective_date',
                ]);
            }
        );
    }

    /**
     * Remove rent increment history.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'rent_increments'
        );
    }
};
