<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1.0.34: somewhere to record that a person has been erased.
 *
 * A tenant or an owner can ask to be forgotten, and the answer cannot be a
 * plain deletion: the invoices, payments and journal entries their letting
 * produced are accounting records, and the law that requires those kept is
 * the same law that lets us refuse to destroy them.
 *
 * So erasure here means the person, not the record. Every identifying
 * field on the party is overwritten with a permanent reference — the name
 * becomes "Erased party #248" — and the ledger keeps pointing at the same
 * row, still balancing and still able to explain itself, with nobody
 * identifiable behind it.
 *
 * This column is what marks that as done. The record stays visible to the
 * organisation — it needs to know the row exists and why the accounts
 * reference it — but it can never be erased twice, and the erasure sets the
 * e-mail policy to `never` so nothing is sent to an address that no longer
 * exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table): void {
            $table->timestamp('erased_at')
                ->nullable()
                ->after('notes');

            $table->index('erased_at');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table): void {
            $table->dropIndex(['erased_at']);

            $table->dropColumn('erased_at');
        });
    }
};
