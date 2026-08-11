<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create property expenses charged to owners.
 *
 * An expense is recorded against a Building and may optionally target
 * a specific Unit.
 *
 * A later OwnerAccountingService will distribute the expense across
 * Building owners according to their ownership percentages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->string('description');

            /*
             * Full expense amount before ownership allocation.
             */
            $table->unsignedBigInteger('amount');

            $table->date('expense_date');

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['building_id', 'expense_date'],
                'owner_expense_building_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_expenses');
    }
};
