<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_owners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('party_id')
                ->constrained()
                ->restrictOnDelete();

            /*
            * Ownership is expressed as a percentage and may contain decimals.
            * Example: three owners may hold 33.33%, 33.33%, and 33.34%.
            *
            * Validation at the application/service layer will ensure that the
            * ownership allocated to a building totals 100%.
            */
            $table->decimal('ownership_percentage', 5, 2);

            $table->timestamps();

            $table->unique(['building_id', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_owners');
    }
};
