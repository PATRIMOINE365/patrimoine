<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the application authorization role to Patrimoine users.
 *
 * Patrimoine 1.0 currently supports one operational role:
 *
 * - property_manager
 *
 * Keeping the role on the application User is intentional. Application
 * users are separate from domain Parties such as owners, tenants and agents.
 */
return new class extends Migration
{
    /**
     * Add the user's Patrimoine application role.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 50)
                ->default('property_manager')
                ->after('email_verified_at');

            $table->index('role');
        });
    }

    /**
     * Remove the application role.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
