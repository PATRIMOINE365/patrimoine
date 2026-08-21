<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cash_receiver_user_id')
                ->nullable()
                ->after('collector_name')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('cash_receiver_name')
                ->nullable()
                ->after('cash_receiver_user_id');
        });

        Schema::table('owner_transactions', function (Blueprint $table) {
            $table->foreignId('cash_receiver_user_id')
                ->nullable()
                ->after('collector_name')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('cash_receiver_name')
                ->nullable()
                ->after('cash_receiver_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('owner_transactions', function (Blueprint $table) {
            $table->dropForeign(['cash_receiver_user_id']);
            $table->dropColumn([
                'cash_receiver_user_id',
                'cash_receiver_name',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cash_receiver_user_id']);
            $table->dropColumn([
                'cash_receiver_user_id',
                'cash_receiver_name',
            ]);
        });
    }
};
