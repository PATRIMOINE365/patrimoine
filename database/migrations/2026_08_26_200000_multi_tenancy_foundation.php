<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1.1.0 multi-tenancy foundation.
 *
 * Introduces organisations, licensing and MFA storage, then stamps
 * every business table with a NOT NULL organisation_id foreign key.
 *
 * An installation that already contains data (pre-prod, live prod) is
 * adopted as Organisation #1: every existing row is assigned to it, the
 * existing users keep working, and a perpetual Professional licence is
 * issued so the founding installation is never constrained by plan
 * limits it predates.
 *
 * Fresh databases (tests, brand-new deployments) create organisations
 * exclusively through the public signup flow.
 */
return new class extends Migration
{
    /**
     * Every table whose rows belong to exactly one organisation.
     *
     * Order is irrelevant; the backfill is a straight UPDATE per table.
     *
     * @var list<string>
     */
    private const TENANT_TABLES = [
        'users',
        'user_invitations',
        'parties',
        'party_roles',
        'buildings',
        'building_owners',
        'units',
        'leases',
        'lease_term_versions',
        'invoices',
        'invoice_lines',
        'payments',
        'payment_allocations',
        'tenant_fund_accounts',
        'tenant_fund_transactions',
        'security_deposit_deductions',
        'security_deposit_settlements',
        'security_deposit_applications',
        'owner_accounts',
        'owner_transactions',
        'owner_expenses',
        'owner_expense_bills',
        'owner_payouts',
        'owner_payout_allocations',
        'withdrawal_receipts',
        'adjustment_vouchers',
        'rent_increments',
        'activity_logs',
        'accounting_accounts',
        'accounting_cutovers',
        'journal_entries',
        'journal_lines',
        'journal_sequences',
        'application_settings',
    ];

    /**
     * Single-column unique constraints that become unique per
     * organisation, so two customers can each run their own numbering
     * sequences without ever colliding.
     *
     * @var array<string, string>
     */
    private const UNIQUES_TO_SCOPE = [
        'invoices' => 'invoice_number',
        'owner_expense_bills' => 'bill_number',
        'adjustment_vouchers' => 'voucher_number',
        'withdrawal_receipts' => 'receipt_number',
        'security_deposit_settlements' => 'refund_voucher_number',
        'journal_entries' => 'journal_number',
        'journal_sequences' => 'year',
        'accounting_accounts' => 'code',
        'accounting_cutovers' => 'cutover_key',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * 1. Platform tables.
         */
        Schema::create('organisations', function (Blueprint $table): void {
            $table->id();

            $table->string('name');

            /*
             * active | suspended. Suspension keeps data intact but
             * refuses sign-in and API access.
             */
            $table->string('status', 20)->default('active');

            /*
             * Every self-service signup starts a 30-day Professional
             * trial. NULL means no trial applies (adopted installs).
             */
            $table->date('trial_ends_on')->nullable();

            $table->timestamps();
        });

        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * free | standard | professional.
             */
            $table->string('plan', 20);

            $table->date('starts_on');

            /*
             * NULL expiry = perpetual licence (grandfathered installs).
             */
            $table->date('expires_on')->nullable();

            $table->string('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('mfa_challenges', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Opaque token identifying the pending challenge in the
             * browser. Random 64 hex characters.
             */
            $table->string('token', 64)->unique();

            /*
             * The 6-digit code, stored only as a hash.
             */
            $table->string('code_hash');

            $table->timestamp('expires_at');

            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamp('consumed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create(
            'organisation_email_counters',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organisation_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * Calendar month the counter covers, e.g. "2026-08".
                 */
                $table->string('period', 7);

                /*
                 * Scheduler-driven mail (reminders, notices).
                 */
                $table->unsignedInteger('automated_sent')->default(0);

                /*
                 * User-triggered document mail (invoices, receipts,
                 * vouchers, bills).
                 */
                $table->unsignedInteger('transactional_sent')->default(0);

                $table->timestamps();

                $table->unique(['organisation_id', 'period']);
            }
        );

        /*
         * 2. Adopt an already-populated installation as Organisation #1.
         */
        $firstOrganisationId = null;

        $hasExistingData = DB::table('users')->exists()
            || DB::table('parties')->exists();

        if ($hasExistingData) {
            $now = now();

            $organisationName = $this->existingInstallationName();

            $firstOrganisationId = DB::table('organisations')->insertGetId([
                'name' => $organisationName,
                'status' => 'active',
                'trial_ends_on' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('licenses')->insert([
                'organisation_id' => $firstOrganisationId,
                'plan' => 'professional',
                'starts_on' => $now->toDateString(),
                'expires_on' => null,
                'notes' => 'Founding installation, grandfathered at V1.1.0.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        /*
         * 3. organisation_id on every tenant-owned table: add nullable,
         *    backfill, then tighten to NOT NULL + foreign key.
         */
        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table(
                $tableName,
                function (Blueprint $table): void {
                    $table->unsignedBigInteger('organisation_id')
                        ->nullable();

                    $table->index('organisation_id');
                }
            );

            if ($firstOrganisationId !== null) {
                DB::table($tableName)->update([
                    'organisation_id' => $firstOrganisationId,
                ]);
            }

            /*
             * activity_logs keeps a NULLABLE organisation column:
             * failed sign-in attempts against unknown email addresses
             * are platform-level events that belong to no organisation.
             * Rows with a NULL organisation are visible to nobody
             * through the scoped API.
             */
            if ($tableName !== 'activity_logs') {
                Schema::table(
                    $tableName,
                    function (Blueprint $table): void {
                        $table->unsignedBigInteger('organisation_id')
                            ->nullable(false)
                            ->change();
                    }
                );
            }

            if (DB::getDriverName() !== 'sqlite') {
                /*
                 * SQLite cannot add a foreign key to an existing table;
                 * the application-level guarantees carry the test
                 * environment, while MySQL additionally enforces the
                 * constraint at the schema level.
                 */
                Schema::table(
                    $tableName,
                    function (Blueprint $table): void {
                        $table->foreign('organisation_id')
                            ->references('id')
                            ->on('organisations')
                            ->restrictOnDelete();
                    }
                );
            }
        }

        /*
         * 4. Numbering and key uniqueness becomes per-organisation.
         */
        foreach (self::UNIQUES_TO_SCOPE as $tableName => $column) {
            Schema::table(
                $tableName,
                function (Blueprint $table) use ($column): void {
                    $table->dropUnique([$column]);

                    $table->unique(['organisation_id', $column]);
                }
            );
        }

        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                /*
                 * Exactly one settings row per organisation.
                 */
                $table->unique('organisation_id');
            }
        );

        /*
         * 5. Signup, legal acceptance and email verification state.
         */
        Schema::table('users', function (Blueprint $table): void {
            /*
             * Pending email-verification token (hashed) for
             * self-service signup.
             */
            $table->string('email_verification_token_hash', 64)
                ->nullable();

            $table->timestamp('email_verification_expires_at')
                ->nullable();

            /*
             * Terms & privacy acceptance captured at signup. Versions
             * identify the document revisions that were accepted.
             */
            $table->timestamp('legal_accepted_at')->nullable();

            $table->string('legal_terms_version', 20)->nullable();

            $table->string('legal_privacy_version', 20)->nullable();

            $table->string('legal_accepted_ip', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'email_verification_token_hash',
                'email_verification_expires_at',
                'legal_accepted_at',
                'legal_terms_version',
                'legal_privacy_version',
                'legal_accepted_ip',
            ]);
        });

        Schema::table(
            'application_settings',
            function (Blueprint $table): void {
                $table->dropUnique(['organisation_id']);
            }
        );

        foreach (self::UNIQUES_TO_SCOPE as $tableName => $column) {
            Schema::table(
                $tableName,
                function (Blueprint $table) use ($column): void {
                    $table->dropUnique(['organisation_id', $column]);

                    $table->unique([$column]);
                }
            );
        }

        foreach (array_reverse(self::TENANT_TABLES) as $tableName) {
            Schema::table(
                $tableName,
                function (Blueprint $table) use ($tableName): void {
                    if (DB::getDriverName() !== 'sqlite') {
                        $table->dropForeign(['organisation_id']);
                    }

                    $table->dropIndex(['organisation_id']);

                    $table->dropColumn('organisation_id');
                }
            );
        }

        Schema::dropIfExists('organisation_email_counters');
        Schema::dropIfExists('mfa_challenges');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('organisations');
    }

    /**
     * Best available display name for the installation being adopted as
     * Organisation #1: its configured managing organisation Party.
     */
    private function existingInstallationName(): string
    {
        $settings = DB::table('application_settings')->first();

        if (
            $settings !== null
            && $settings->managing_organisation_party_id !== null
        ) {
            $party = DB::table('parties')
                ->where('id', $settings->managing_organisation_party_id)
                ->first();

            if ($party !== null && trim((string) $party->name) !== '') {
                return $party->name;
            }
        }

        return 'Organisation 1';
    }
};
