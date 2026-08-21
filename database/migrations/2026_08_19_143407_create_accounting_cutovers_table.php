<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'accounting_cutovers',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('cutover_key', 100)
                    ->unique();

                $table->date(
                    'cutover_date'
                );

                $table->string(
                    'status',
                    30
                );

                $table
                    ->unsignedInteger(
                        'position_count'
                    )
                    ->default(0);

                $table
                    ->unsignedInteger(
                        'journal_entry_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'completed_at'
                    )
                    ->nullable();

                $table
                    ->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'status',
                        'cutover_date',
                    ]
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'accounting_cutovers'
        );
    }
};
