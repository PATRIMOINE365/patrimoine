<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.29 control over what Patrimoine sends to Parties.
 *
 * Two levels, deliberately:
 *
 * - `application_settings.party_emails_enabled` is the organisation-wide
 *   switch. Turning it off silences every message Patrimoine would send to
 *   a tenant, owner or agent.
 * - `parties.email_policy` is the per-Party exception. `inherit` follows
 *   the organisation switch, `always` keeps emailing that Party even while
 *   the organisation is silent, and `never` excludes that Party even while
 *   the organisation is sending.
 *
 * Both default to today's behaviour, so upgrading changes nothing for
 * anybody: the switch starts on and every Party inherits it.
 *
 * Neither column affects mail addressed to Patrimoine USERS — sign-in
 * codes, invitations, password resets and licence notices are how people
 * reach their own account and are never suppressed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->boolean('party_emails_enabled')
                    ->default(true)
                    ->after('currency');
            }
        );

        Schema::table(
            'parties',
            function (Blueprint $table): void {
                $table->enum('email_policy', [
                    'inherit',
                    'always',
                    'never',
                ])
                    ->default('inherit')
                    ->after('email');

                $table->index('email_policy');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'parties',
            function (Blueprint $table): void {
                $table->dropIndex(['email_policy']);
                $table->dropColumn('email_policy');
            }
        );

        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->dropColumn('party_emails_enabled');
            }
        );
    }
};
