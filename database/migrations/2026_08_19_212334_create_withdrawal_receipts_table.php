<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'withdrawal_receipts',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'receipt_number'
                )->unique();

                $table->foreignId(
                    'tenant_fund_transaction_id'
                )
                    ->unique()
                    ->constrained(
                        'tenant_fund_transactions'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'tenant_fund_account_id'
                )
                    ->constrained(
                        'tenant_fund_accounts'
                    );

                $table->foreignId(
                    'lease_id'
                )
                    ->constrained();

                $table->foreignId(
                    'tenant_id'
                )
                    ->constrained(
                        'parties'
                    );

                $table->enum(
                    'fund_type',
                    [
                        'rent_reserve',
                        'consumable_advance',
                        'security_deposit',
                    ]
                );

                $table->unsignedBigInteger(
                    'amount'
                );

                $table->enum(
                    'payment_method',
                    [
                        'cash',
                        'bank_transfer',
                        'momo',
                    ]
                );

                $table->date(
                    'transaction_date'
                );

                $table->string(
                    'reference'
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                /*
                 * Frozen readable context.
                 */
                $table->string(
                    'tenant_name'
                );

                $table->string(
                    'lease_label'
                )->nullable();

                $table->string(
                    'building_label'
                )->nullable();

                $table->string(
                    'unit_label'
                )->nullable();

                $table->foreignId(
                    'performed_by_user_id'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                $table->string(
                    'performed_by_name'
                );

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'withdrawal_receipts'
        );
    }
};
