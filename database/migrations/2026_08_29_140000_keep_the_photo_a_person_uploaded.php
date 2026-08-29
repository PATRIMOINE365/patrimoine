<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.31: keep the picture, not only the crop of it.
 *
 * Until now the only thing stored was the small square the browser cut
 * out, so changing your mind about the framing meant finding the original
 * file again. The optimised picture is kept beside it now, along with
 * where the frame was, so the cropper can reopen exactly where it was left.
 *
 * The source is a MEDIUMBLOB: a plain BLOB stops at 64 kB and a picture
 * worth re-cropping does not fit in that. It never leaves the server
 * except when its owner reopens the cropper.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'profile_photo_source')) {
                $table->mediumText('profile_photo_source')
                    ->nullable()
                    ->after('profile_photo_mime');

                $table->string('profile_photo_source_mime', 32)
                    ->nullable()
                    ->after('profile_photo_source');

                /*
                 * Where the frame sat: centre and zoom, as fractions of
                 * the picture, so it survives the picture being resized.
                 */
                $table->string('profile_photo_crop', 128)
                    ->nullable()
                    ->after('profile_photo_source_mime');
            }
        });

        /*
         * mediumText gives the right storage class; the column holds bytes.
         */
        if (Schema::hasColumn('users', 'profile_photo_source')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                Schema::getConnection()->statement(
                    'ALTER TABLE `users` MODIFY `profile_photo_source` MEDIUMBLOB NULL'
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_photo_source',
                'profile_photo_source_mime',
                'profile_photo_crop',
            ]);
        });
    }
};
