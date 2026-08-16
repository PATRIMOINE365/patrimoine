<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Preserve authentication for users upgraded from Patrimoine V1.0.2.
 *
 * V1.0.2 users already had working application accounts and passwords.
 * They existed before V1.0.3 introduced invitation/password-setup state.
 *
 * Therefore an existing V1.0.2 user with a null email_verified_at value
 * must not suddenly be treated as an invitation-pending account after the
 * upgrade. Mark those pre-existing accounts as verified automatically.
 *
 * This is deliberately a separate corrective migration because the earlier
 * V1.0.3 RBAC migration may already have run on development/release systems.
 */
return new class extends Migration
{
    /**
     * Preserve existing V1.0.2 account access.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNotNull('password')
            ->update([
                'email_verified_at' => now(),
            ]);
    }

    /**
     * This data correction is intentionally irreversible.
     *
     * We cannot safely distinguish an originally verified V1.0.2 account
     * from one corrected by this migration after the fact. Clearing
     * verification during rollback could lock legitimate users out.
     */
    public function down(): void
    {
        // Intentionally no-op.
    }
};
