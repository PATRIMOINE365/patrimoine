<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * One row per calendar year protects Journal reference allocation.
         *
         * Example:
         * year        = 2026
         * next_number = 42
         *
         * means the next Journal reference will be JRN-2026-000042.
         */
        Schema::create('journal_sequences', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('year')->unique();

            $table->unsignedBigInteger('next_number')
                ->default(1);

            $table->timestamps();
        });

        /*
         * JournalEntry is the immutable header for one complete accounting
         * event.
         *
         * Monetary debit/credit values live in journal_lines.
         */
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('journal_number', 32)->unique();

            $table->date('journal_date');

            $table->timestamp('posted_at');

            $table->string('transaction_type', 100);

            $table->text('description')->nullable();

            /*
             * Source records are deliberately not foreign-key constrained.
             *
             * V1.0.5 requires Journal history to survive destructive deletion
             * of operational records such as a Lease and its transactions.
             */
            $table->string('source_type', 150)->nullable();

            $table->unsignedBigInteger('source_id')->nullable();

            /*
             * User relation may disappear later, but the frozen actor snapshot
             * remains permanently readable.
             */
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('actor_name_snapshot')->nullable();

            /*
             * Frozen business context:
             * Tenant, Owner, Lease, Building, Unit, account descriptions,
             * transaction references, etc.
             */
            $table->json('snapshot')->nullable();

            /*
             * Non-essential structured posting metadata.
             */
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('journal_date');

            $table->index('transaction_type');

            $table->index(
                ['source_type', 'source_id']
            );

            $table->index('actor_user_id');
        });

        /*
         * Each JournalLine represents one debit or one credit against one
         * system accounting account.
         *
         * Account snapshots are stored alongside the foreign key so historical
         * Journal lines remain understandable even if account presentation
         * changes later.
         */
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->foreignId('accounting_account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->unsignedBigInteger('debit_amount')
                ->default(0);

            $table->unsignedBigInteger('credit_amount')
                ->default(0);

            $table->string('account_code_snapshot', 20);

            $table->string('account_name_snapshot');

            $table->string('account_type_snapshot', 30);

            $table->text('memo')->nullable();

            /*
             * Optional per-line contextual data.
             *
             * This will later allow a posting to retain specific Lease,
             * Tenant, Owner or fund-account context without coupling the
             * permanent Journal to deletable operational records.
             */
            $table->json('snapshot')->nullable();

            $table->timestamps();

            $table->index('accounting_account_id');

            $table->index(
                ['journal_entry_id', 'accounting_account_id']
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');

        Schema::dropIfExists('journal_entries');

        Schema::dropIfExists('journal_sequences');
    }
};
