<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.42: the data-protection tools are off unless an organisation asks.
 *
 * Download this party's data and Erase this party are the two controls on
 * the parties list that act on a real person's records rather than on the
 * business. They are there for a reason — somebody exercising their right
 * to a copy, or to be forgotten — but they are not day-to-day work, and a
 * pair of buttons sitting on every row of every list is an invitation to
 * press one.
 *
 * Off for every organisation, including the ones that already exist: the
 * capability is unchanged, only whether the buttons are on screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->boolean('data_tools_enabled')
                    ->default(false)
                    ->after('party_emails_enabled');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->dropColumn('data_tools_enabled');
            }
        );
    }
};
