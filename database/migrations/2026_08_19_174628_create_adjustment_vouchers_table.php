<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_vouchers', function (Blueprint $table) {
            $table->id();

            $table->string('voucher_number')->unique();

            /*
             * Shared adjustment source.
             *
             * owner_account
             * tenant_fund
             *
             * Future contextual balance-maintaining accounts can reuse
             * the same voucher infrastructure without introducing
             * account-specific document tables.
             */
            $table->string('account_type');
            $table->unsignedBigInteger('account_id');

            /*
             * Immutable human-readable context snapshots.
             *
             * The voucher must remain understandable even if the
             * underlying Party/Lease/account labels later change.
             */
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_label');
            $table->string('account_label');

            $table->bigInteger('previous_balance');
            $table->bigInteger('corrected_balance');
            $table->bigInteger('difference');

            $table->text('reason');

            $table->date('adjustment_date');

            /*
             * Preserve both the relationship and the actor snapshot.
             */
            $table->foreignId('performed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('performed_by_name');

            /*
             * Link back to the immutable operational adjustment record.
             * The generic pair avoids coupling the shared voucher table
             * to one adjustment implementation.
             */
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');

            $table->timestamps();

            $table->index(['account_type', 'account_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustment_vouchers');
    }
};
