<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store singleton application-level configuration for Patrimoine.
 *
 * Patrimoine 1.0 is not multi-tenant. The application therefore keeps
 * one configured managing organisation that acts as the issuer and
 * administrative identity for invoices, receipts, reports and emails.
 */
return new class extends Migration
{
    /**
     * Create the application settings table.
     */
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();

            /*
             * Party representing the configured managing organisation.
             *
             * The Party itself contains the legal, contact and banking
             * information. This table only identifies which Party is the
             * application's managing organisation.
             */
            $table->foreignId('managing_organisation_party_id')
                ->nullable()
                ->constrained('parties')
                ->restrictOnDelete();

            $table->timestamps();

            /*
             * Only one application settings row is expected in V1.
             * The controller/service layer enforces the singleton behavior.
             */
            $table->unique('managing_organisation_party_id');
        });
    }

    /**
     * Drop application settings.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
