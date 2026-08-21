<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contractual fields preserved in each immutable Lease term version.
     *
     * Lease identity and lifecycle state remain on the Lease itself.
     *
     * @var list<string>
     */
    private array $termFields = [
        'unit_id',
        'tenant_id',
        'agent_id',
        'start_date',
        'end_date',
        'rent_amount',
        'payment_frequency',
        'due_day',
        'vat_rate',
        'proration_amount',
        'security_deposit_amount',
        'advance_payment_amount',
        'rent_reserve_amount',
        'rent_increment_type',
        'rent_increment_value',
        'next_rent_increment_date',
        'management_fee_type',
        'management_fee_value',
        'agent_commission_amount',
        'notes',
    ];

    public function up(): void
    {
        Schema::create(
            'lease_term_versions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('lease_id')
                    ->constrained()
                    ->restrictOnDelete();

                /*
                 * Sequence within one Lease.
                 *
                 * Version 1 is the baseline contractual state.
                 * Later Phase 8 work will append versions for Extend and
                 * reactivation rather than overwriting historical terms.
                 */
                $table->unsignedInteger(
                    'version_number'
                );

                /*
                 * Why this version exists.
                 *
                 * Phase 8A creates baseline records only. Extension and
                 * reactivation are reserved for later Phase 8 steps.
                 */
                $table->string(
                    'event_type',
                    32
                )->default('baseline');

                /*
                 * Date from which these contractual terms apply.
                 *
                 * For the baseline this is the original Lease start date.
                 * A later version will close the previous effective period
                 * and establish a new effective_from date.
                 */
                $table->date(
                    'effective_from'
                );

                $table->date(
                    'effective_to'
                )->nullable();

                /*
                 * Frozen contractual state.
                 *
                 * Storing one complete snapshot makes the record resilient
                 * to future Lease changes and preserves fields that Phase 8
                 * itself will not permit users to edit.
                 */
                $table->json('terms');

                /*
                 * NULL denotes migration/system baseline creation.
                 *
                 * Future human Extend operations will store their actor.
                 */
                $table->foreignId(
                    'created_by_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'created_at'
                )->useCurrent();

                /*
                 * There is intentionally no updated_at column:
                 * term versions are append-only historical records.
                 */

                $table->unique(
                    [
                        'lease_id',
                        'version_number',
                    ],
                    'lease_term_versions_lease_version_unique'
                );

                $table->index(
                    [
                        'lease_id',
                        'effective_from',
                    ],
                    'lease_term_versions_effective_idx'
                );
            }
        );

        /*
         * Upgrade path:
         *
         * Capture every Lease that already exists when V1.0.5 Phase 8A
         * reaches an upgraded database.
         *
         * This does not pretend to reconstruct previous edits that occurred
         * before term versioning existed. It establishes the authoritative
         * baseline state from which all future Extend operations are
         * historically preserved.
         */
        DB::table('leases')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($leases): void {
                    foreach ($leases as $lease) {
                        $terms = [];

                        foreach (
                            $this->termFields as $field
                        ) {
                            $terms[$field] =
                                $lease->{$field};
                        }

                        DB::table(
                            'lease_term_versions'
                        )->insertOrIgnore([
                            'lease_id' => $lease->id,

                            'version_number' => 1,

                            'event_type' => 'baseline',

                            'effective_from' => $lease->start_date,

                            'effective_to' => null,

                            'terms' => json_encode(
                                $terms,
                                JSON_THROW_ON_ERROR
                            ),

                            'created_by_user_id' => null,

                            'created_at' => now(),
                        ]);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'lease_term_versions'
        );
    }
};
