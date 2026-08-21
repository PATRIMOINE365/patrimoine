<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.7: a Unit can be marked as commercial (shop, office, kiosk) as
 * opposed to residential. Used by the Properties workspace, filters,
 * reports and the dashboard occupancy breakdown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->boolean('is_commercial')
                ->default(false)
                ->after('description')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('is_commercial');
        });
    }
};
