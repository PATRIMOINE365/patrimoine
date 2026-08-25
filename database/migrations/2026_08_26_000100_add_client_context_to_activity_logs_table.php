<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity Log events learn frozen client context: the raw User-Agent
 * header plus the browser, platform and device derived from it at the
 * moment the event was recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'activity_logs',
            function (Blueprint $table): void {
                $table->string('user_agent', 1024)
                    ->nullable()
                    ->after('ip_address');

                $table->string('browser', 120)
                    ->nullable()
                    ->after('user_agent');

                $table->string('platform', 120)
                    ->nullable()
                    ->after('browser');

                $table->string('device', 40)
                    ->nullable()
                    ->after('platform');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'activity_logs',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'user_agent',
                    'browser',
                    'platform',
                    'device',
                ]);
            }
        );
    }
};
