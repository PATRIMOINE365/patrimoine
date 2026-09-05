<?php

use App\Support\DocumentSequenceBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.50: a receipt is numbered by its organisation, not by the
 * installation.
 *
 * Receipts were the one document still numbered from a database id, and
 * a database id is allocated across every organisation on the
 * installation. A customer's very first receipt therefore read
 * RCT-000142, and a tenant holding two receipts could read the whole
 * platform's payment volume off the difference between them. Every
 * other series has come from the per-organisation counter since 1.0.36;
 * this brings receipts across.
 *
 * Existing payments keep exactly the number their receipt already
 * carried — a reference that has been printed and sent must never change
 * under it — and each organisation's counter is seeded one past the
 * highest it had already issued, the same rule every other series
 * followed when it moved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('receipt_number', 32)
                ->nullable()
                ->after('lease_id');

            $table->unique(
                ['organisation_id', 'receipt_number'],
                'payments_organisation_receipt_number_unique'
            );
        });

        DB::table('payments')
            ->whereNull('receipt_number')
            ->orderBy('id')
            ->chunkById(500, function ($payments): void {
                foreach ($payments as $payment) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'receipt_number' => sprintf('RCT-%06d', $payment->id),
                        ]);
                }
            });

        DocumentSequenceBackfill::run();
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_organisation_receipt_number_unique');
            $table->dropColumn('receipt_number');
        });
    }
};
