<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the most recent Patrimoine release announcement seen by each
     * application user.
     *
     * Null means that the user has not acknowledged the current release.
     */
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'last_seen_release',
                        50
                    )
                    ->nullable()
                    ->after(
                        'is_active'
                    );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'last_seen_release'
                );
            }
        );
    }
};