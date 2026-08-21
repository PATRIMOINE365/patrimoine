<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.7 Owner Expense Billing.
 *
 * Introduces owner expense bills: a batch of expense lines billed
 * DIRECTLY to one owner from the Owners workspace, without any Building
 * context.
 *
 * Structural changes:
 *
 * 1. New `owner_expense_bills` header table. One bill groups the expense
 *    lines recorded together and carries the printed bill identity
 *    (bill number, bill date, total).
 *
 * 2. `owner_expenses.building_id` becomes NULLABLE.
 *
 *    A NULL building_id means a direct-to-owner billed expense: the
 *    expense was charged straight to one OwnerAccount through an
 *    owner expense bill rather than shared across Building ownership.
 *
 * 3. New nullable `owner_expenses.owner_expense_bill_id` links each
 *    direct line back to its bill. Building-allocated expenses keep it
 *    NULL.
 *
 * Laravel 13 modifies columns natively (doctrine/dbal has not been
 * required since Laravel 11), so ->nullable()->change() is safe here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_expense_bills', function (Blueprint $table) {
            $table->id();

            /*
             * The owner billed. Financial history must survive, so the
             * OwnerAccount cannot be deleted while bills reference it.
             */
            $table->foreignId('owner_account_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Human-facing bill identity, e.g. OEB-000001.
             */
            $table->string('bill_number')->unique();

            $table->date('bill_date');

            /*
             * Sum of all expense lines, in whole currency units.
             */
            $table->unsignedBigInteger('total_amount');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['owner_account_id', 'bill_date'],
                'owner_expense_bill_account_date_idx'
            );
        });

        /*
         * Building context becomes optional on owner expenses.
         *
         * NULL building_id = direct-to-owner billed expense.
         */
        Schema::table('owner_expenses', function (Blueprint $table) {
            $table->foreignId('building_id')
                ->nullable()
                ->change();
        });

        Schema::table('owner_expenses', function (Blueprint $table) {
            /*
             * Link a direct expense line to its owner expense bill.
             *
             * Bills are financial history and cannot be deleted while
             * their lines exist.
             */
            $table->foreignId('owner_expense_bill_id')
                ->nullable()
                ->after('unit_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('owner_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'owner_expense_bill_id'
            );
        });

        Schema::table('owner_expenses', function (Blueprint $table) {
            $table->foreignId('building_id')
                ->nullable(false)
                ->change();
        });

        Schema::dropIfExists('owner_expense_bills');
    }
};
