<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('entry_kind', 32)
                ->default('financial')
                ->after('journal_number');

            $table->foreignId('reversal_of_id')
                ->nullable()
                ->after('entry_kind')
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->foreignId('reversed_by_id')
                ->nullable()
                ->after('reversal_of_id')
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->text('reversal_reason')
                ->nullable()
                ->after('reversed_by_id');

            $table->index('entry_kind');
            $table->index('reversal_of_id');
            $table->index('reversed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['reversed_by_id']);
            $table->dropForeign(['reversal_of_id']);

            $table->dropIndex(['entry_kind']);
            $table->dropIndex(['reversal_of_id']);
            $table->dropIndex(['reversed_by_id']);

            $table->dropColumn([
                'entry_kind',
                'reversal_of_id',
                'reversed_by_id',
                'reversal_reason',
            ]);
        });
    }
};
