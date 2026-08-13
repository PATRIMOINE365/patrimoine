<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add application-wide financial defaults.
 *
 * The default VAT rate is used only when preparing a new Lease.
 *
 * Existing Leases retain their own contractual VAT rate and historical
 * Invoices retain their independently snapshotted VAT values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table
                    ->decimal(
                        'default_vat_rate',
                        5,
                        2
                    )
                    ->default(18.00)
                    ->after(
                        'managing_organisation_party_id'
                    );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'default_vat_rate'
                );
            }
        );
    }
};
