<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'security_deposit_applications',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('lease_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->foreignId('invoice_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->foreignId('tenant_fund_transaction_id')
                    ->unique()
                    ->constrained()
                    ->restrictOnDelete();

                $table->unsignedBigInteger('amount');

                $table->date('application_date');

                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index([
                    'lease_id',
                    'application_date',
                ]);

                $table->index('invoice_id');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'security_deposit_applications'
        );
    }
};
