<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.11: user profile photos.
 *
 * The processed avatar is stored IN the database: it is re-encoded
 * server-side to a small square JPEG (well under the 64KB BLOB limit),
 * so it survives container redeploys without a persistent volume,
 * rides every database backup, and can never be a filesystem path
 * problem.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'profile_photo')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->binary('profile_photo')->nullable();

                $table->string('profile_photo_mime', 32)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_photo',
                'profile_photo_mime',
            ]);
        });
    }
};
