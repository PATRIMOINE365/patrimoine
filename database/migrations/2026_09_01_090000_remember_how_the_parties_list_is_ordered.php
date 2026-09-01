<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.43: sorting people by surname is an organisation's decision.
 *
 * It used to be a tickbox above the parties list, which meant it was a
 * habit of whoever happened to be at that browser: a colleague opening the
 * same list on the same data saw it in a different order, and the choice
 * was lost the moment the page reloaded somewhere else. It belongs in
 * Settings with the other preferences, and it belongs to the organisation
 * rather than to the machine.
 *
 * Off for every organisation that already exists, which is exactly the
 * order they have been reading all along.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->boolean('sort_parties_by_surname')
                    ->default(false)
                    ->after('data_tools_enabled');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->dropColumn('sort_parties_by_surname');
            }
        );
    }
};
