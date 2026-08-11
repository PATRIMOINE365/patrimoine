<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the tenant-fund ledger with Consumable Advance consumption.
 *
 * Consumable Advance funding already exists as `advance_funding`.
 * This migration adds the corresponding debit category used when held
 * advance money is released to settle a tenant rent Invoice.
 *
 * The original migration is intentionally left unchanged because it is
 * already part of Patrimoine's committed schema history.
 */
return new class extends Migration
{
    /**
     * Add advance_consumption to the allowed ledger categories.
     */
    public function up(): void
    {
        Schema::table(
            'tenant_fund_transactions',
            function (Blueprint $table): void {
                $table->enum('category', [
                    'reserve_funding',
                    'advance_funding',
                    'deposit_funding',
                    'rent_consumption',
                    'advance_consumption',
                    'deposit_deduction',
                    'refund',
                    'transfer',
                    'adjustment',
                ])->change();
            }
        );
    }

    /**
     * Restore the previous category set.
     *
     * Rollback is only safe when no advance_consumption transactions exist.
     * Production rollback procedures must therefore remove/reclassify such
     * transactions before reversing this migration.
     */
    public function down(): void
    {
        Schema::table(
            'tenant_fund_transactions',
            function (Blueprint $table): void {
                $table->enum('category', [
                    'reserve_funding',
                    'advance_funding',
                    'deposit_funding',
                    'rent_consumption',
                    'deposit_deduction',
                    'refund',
                    'transfer',
                    'adjustment',
                ])->change();
            }
        );
    }
};
