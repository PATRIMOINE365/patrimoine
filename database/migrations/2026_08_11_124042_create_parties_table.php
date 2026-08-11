<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['person', 'organisation', 'association']);

            $table->string('name')->nullable();
            $table->string('legal_name')->nullable();

            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->string('contact_person_email')->nullable();

            $table->string('id_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('vat_tin')->nullable();

            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('name');
            $table->index('legal_name');
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
