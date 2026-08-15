<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add organisation-wide presentation settings.
 *
 * Language and currency are deliberately stored as stable string codes
 * instead of database enums. This keeps future expansion possible without
 * requiring schema redesign whenever Patrimoine gains another supported
 * language or currency.
 *
 * Existing installations retain their V1.0.1 behaviour through English/GHS
 * compatibility defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'language',
                        20
                    )
                    ->default('en')
                    ->after('default_vat_rate');

                $table
                    ->string(
                        'currency',
                        20
                    )
                    ->default('GHS')
                    ->after('language');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'language',
                    'currency',
                ]);
            }
        );
    }
};
