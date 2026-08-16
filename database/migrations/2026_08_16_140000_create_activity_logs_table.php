<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the permanent Patrimoine human-action Activity Log.
     *
     * Historical accountability must survive later User changes or deletion,
     * so actor identity is snapshotted rather than derived solely from the
     * current users table.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();

            /*
             * Nullable by design:
             * - deleted Users must not destroy historical accountability;
             * - some events, such as failed login attempts, may not have a
             *   resolved User identity.
             */
            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Frozen actor identity at the time of the event.
             *
             * These fields remain authoritative historical evidence even if
             * the User is later renamed, changes role, or is deleted.
             */
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('actor_role')->nullable();

            /*
             * Stable internal action identifier.
             *
             * Examples introduced by later activities will include values
             * such as auth.login, user.created and payment.recorded.
             * User-facing labels are localized separately.
             */
            $table->string('action', 100);

            /*
             * Optional affected business/application record.
             *
             * We deliberately store the type and identifier rather than a
             * database foreign key because Activity Log history must survive
             * deletion of the source record.
             */
            $table->string('entity_type', 100)->nullable();
            $table->string('entity_id', 100)->nullable();
            $table->string('entity_label')->nullable();

            /*
             * Structured historical detail.
             *
             * before_values / after_values support changed-field auditing.
             * snapshot supports meaningful create/delete state.
             * metadata is reserved for event-specific non-secret context.
             */
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('metadata')->nullable();

            /*
             * V1.0.3 records IP address but intentionally does not introduce
             * browser/device/user-agent tracking.
             *
             * 45 characters accommodates IPv4 and IPv6 textual forms.
             */
            $table->string('ip_address', 45)->nullable();

            /*
             * Activity Log events are immutable historical facts. There is no
             * updated_at because application code must never edit an event.
             */
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('user_id');
            $table->index('actor_role');
            $table->index('action');
            $table->index('entity_type');
            $table->index(
                ['entity_type', 'entity_id'],
                'activity_logs_entity_lookup_index'
            );
        });
    }

    /**
     * Remove the table only when rolling back the migration itself.
     *
     * Normal Patrimoine application behavior exposes no Activity Log
     * deletion mechanism.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
