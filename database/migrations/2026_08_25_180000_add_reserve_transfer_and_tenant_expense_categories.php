<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.8 owner dual-balance and tenant expenses.
 *
 * - owner_transactions gains `reserve_transfer`: a manual movement
 *   between the owner's two virtual sub-balances (Payout account and
 *   Deposit/Expense account). Direction `credit` moves money INTO the
 *   Deposit/Expense account from the Payout account; `debit` moves it
 *   back. The transfer is internal, so it never changes the owner's
 *   total balance — balance derivations exclude the category.
 *
 * - tenant_fund_transactions gains `expense`: money drawn from one of
 *   the tenant's fund accounts to settle a lease-specific expense.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->enum('category', [
                    'rent_entitlement',
                    'owner_deposit',
                    'management_fee',
                    'agent_commission',
                    'expense',
                    'payout',
                    'adjustment',
                    'reserve_transfer',
                ])->change();
            }
        );

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
                    'withdrawal',
                    'expense',
                ])->change();
            }
        );
    }

    /**
     * Rollback is only safe when no reserve_transfer or tenant expense
     * transactions exist yet.
     */
    public function down(): void
    {
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->enum('category', [
                    'rent_entitlement',
                    'owner_deposit',
                    'management_fee',
                    'agent_commission',
                    'expense',
                    'payout',
                    'adjustment',
                ])->change();
            }
        );

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
                    'withdrawal',
                ])->change();
            }
        );
    }
};
