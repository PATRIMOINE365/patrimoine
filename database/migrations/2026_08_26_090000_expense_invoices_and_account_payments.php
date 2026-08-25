<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.8 expense invoices and account payments.
 *
 * Expenses stop settling themselves. Recording a tenant expense now
 * creates an EXP- Invoice; recording an owner expense bill keeps its
 * OEB- document unpaid. Both are then settled explicitly through the
 * new Pay flow, which draws from a chosen account and can be reversed.
 *
 * Schema consequences:
 *
 * - invoices.type learns 'expense';
 * - expense invoices carry itemized invoice_lines;
 * - tenant fund ledger learns the 'expense_settlement' category and a
 *   reversal link so a cancelled payment points at what it reverses;
 * - the owner ledger links expense payments to their bill, records
 *   which owner account section funded them, and carries the same
 *   reversal link;
 * - owner entitlement rows released by fund-account payments remember
 *   the tenant fund transaction that produced them, so cancelling that
 *   payment can reverse exactly its own entitlement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->enum('type', [
                'rent',
                'security_deposit_debt',
                'expense',
            ])
                ->default('rent')
                ->change();
        });

        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * One human-entered expense line.
             *
             * Amounts are whole currency units, exactly like every other
             * monetary value in Patrimoine.
             */
            $table->string('description');
            $table->unsignedBigInteger('amount');

            $table->timestamps();

            $table->index('invoice_id');
        });

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
                    'expense_settlement',
                ])->change();

                /*
                 * A reversal transaction points at the transaction it
                 * reverses. A transaction is considered cancelled exactly
                 * when a reversal row references it.
                 */
                $table->foreignId('reversal_of_transaction_id')
                    ->nullable()
                    ->constrained('tenant_fund_transactions')
                    ->restrictOnDelete();
            }
        );

        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                /*
                 * Expense payments settle a specific owner expense bill.
                 */
                $table->foreignId('owner_expense_bill_id')
                    ->nullable()
                    ->constrained('owner_expense_bills')
                    ->restrictOnDelete();

                /*
                 * V1.0.8 dual owner balances: which side of the owner's
                 * money funded this expense payment. NULL means the
                 * historical default, the Deposit/Expense account.
                 */
                $table->enum('funding_source', [
                    'deposit_account',
                    'payout_account',
                ])->nullable();

                $table->foreignId('reversal_of_transaction_id')
                    ->nullable()
                    ->constrained('owner_transactions')
                    ->restrictOnDelete();

                /*
                 * Rent entitlement released by a tenant fund-account
                 * payment remembers its source, so cancelling the payment
                 * reverses exactly its own entitlement rows.
                 */
                $table->foreignId('tenant_fund_transaction_id')
                    ->nullable()
                    ->constrained('tenant_fund_transactions')
                    ->restrictOnDelete();
            }
        );

        /*
         * Historical expense bills settled themselves at creation: each
         * line already debited the owner ledger with the bill number as
         * its reference. Linking those debits to their bill makes the
         * derived payment state read "paid" for them, which is exactly
         * what happened operationally.
         */
        $bills = DB::table('owner_expense_bills')
            ->get(['id', 'bill_number', 'owner_account_id']);

        foreach ($bills as $bill) {
            DB::table('owner_transactions')
                ->where('owner_account_id', $bill->owner_account_id)
                ->where('category', 'expense')
                ->where('direction', 'debit')
                ->where('reference', $bill->bill_number)
                ->update([
                    'owner_expense_bill_id' => $bill->id,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table(
            'owner_transactions',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId('tenant_fund_transaction_id');
                $table->dropConstrainedForeignId('reversal_of_transaction_id');
                $table->dropColumn('funding_source');
                $table->dropConstrainedForeignId('owner_expense_bill_id');
            }
        );

        Schema::table(
            'tenant_fund_transactions',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId('reversal_of_transaction_id');

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

        Schema::dropIfExists('invoice_lines');

        Schema::table('invoices', function (Blueprint $table): void {
            $table->enum('type', [
                'rent',
                'security_deposit_debt',
            ])
                ->default('rent')
                ->change();
        });
    }
};
