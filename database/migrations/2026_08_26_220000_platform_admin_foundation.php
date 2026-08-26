<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.11 platform administration foundation.
 *
 * - organisations.is_platform marks the internal Kality Ltd staff
 *   organisation, excluded from every customer-facing count, list and
 *   licensing rule;
 * - licences learn to carry the commercial side of an issuance (amount
 *   received, payment method and reference) and can be revoked without
 *   losing their history.
 *
 * Idempotent throughout: MySQL DDL is non-transactional.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('organisations', 'is_platform')) {
            Schema::table('organisations', function (Blueprint $table): void {
                $table->boolean('is_platform')->default(false);
            });
        }

        if (! Schema::hasColumn('licenses', 'amount')) {
            Schema::table('licenses', function (Blueprint $table): void {
                /*
                 * Whole currency units, matching the application-wide
                 * money convention. Nullable: historical and
                 * grandfathered licences carry no payment.
                 */
                $table->unsignedBigInteger('amount')->nullable();

                $table->string('currency', 10)->nullable();

                /*
                 * bank_transfer | momo | cash | other
                 */
                $table->string('payment_method', 30)->nullable();

                $table->string('payment_reference')->nullable();

                /*
                 * A revoked licence stops entitling immediately but
                 * remains in the organisation's licence history.
                 */
                $table->timestamp('revoked_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            $table->dropColumn([
                'amount',
                'currency',
                'payment_method',
                'payment_reference',
                'revoked_at',
            ]);
        });

        Schema::table('organisations', function (Blueprint $table): void {
            $table->dropColumn('is_platform');
        });
    }
};
