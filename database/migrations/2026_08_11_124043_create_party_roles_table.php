<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('party_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('role', [
                'tenant',
                'owner',
                'agent',
                'managing_organisation',
            ]);

            $table->timestamps();

            $table->unique(['party_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_roles');
    }
};
