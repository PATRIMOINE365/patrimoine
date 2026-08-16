<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend Patrimoine users for the V1.0.3 RBAC foundation.
 *
 * Upgrade rule:
 * V1.0.2 had one effective operational role: property_manager.
 * Existing V1.0.2 users are therefore promoted automatically to
 * administrator so an upgrade cannot reduce their existing access.
 *
 * No manual production database update is required.
 */
return new class extends Migration
{
    /**
     * Apply the V1.0.3 User foundation.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            /*
             * Phone is intentionally nullable because existing V1.0.2 users
             * have no phone value to migrate.
             */
            $table->string('phone', 50)
                ->nullable()
                ->after('email');

            /*
             * Existing and newly created accounts are active unless an
             * Administrator explicitly disables them later.
             */
            $table->boolean('is_active')
                ->default(true)
                ->after('role');

            $table->index('is_active');
        });

        /*
         * Preserve privileges during the V1.0.2 -> V1.0.3 upgrade.
         *
         * V1.0.2 supported property_manager as its application role, so all
         * such existing accounts become Administrators automatically.
         */
        DB::table('users')
            ->where('role', 'property_manager')
            ->update([
                'role' => 'administrator',
            ]);
    }

    /**
     * Restore the V1.0.2 User structure when rolling back this migration.
     */
    public function down(): void
    {
        /*
         * Before V1.0.3 User Management exists, Administrator corresponds to
         * the former privileged Property Manager account.
         */
        DB::table('users')
            ->where('role', 'administrator')
            ->update([
                'role' => 'property_manager',
            ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_active']);

            $table->dropColumn([
                'phone',
                'is_active',
            ]);
        });
    }
};
